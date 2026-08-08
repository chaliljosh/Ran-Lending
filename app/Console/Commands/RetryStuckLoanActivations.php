<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\LoanActivationService;

// One-time cleanup command — para sa mga loan application na naka-stuck sa
// status='approved' kahit kumpleto na yung 3 approvals nila, dahil na-timeout
// yung LoanActivationService noon (partial o walang schedule rows na nagawa).
// Usage: php artisan loans:retry-activation
class RetryStuckLoanActivations extends Command
{
    protected $signature = 'loans:retry-activation';
    protected $description = 'Retry payment-schedule generation + activation for approved loans stuck without a complete schedule';

    public function handle()
    {
        $stuck = DB::table('loan_applications')->where('status', 'approved')->get();

        if ($stuck->isEmpty()) {
            $this->info('Wala namang naka-stuck na approved application.');
            return;
        }

        foreach ($stuck as $app) {
            $scheduleCount = DB::table('loan_payment_schedules')
                ->where('application_id', $app->application_id)
                ->count();

            $expectedCount = (int) $app->loan_term;

            if ($scheduleCount >= $expectedCount && $scheduleCount > 0) {
                // May kumpleto na palang schedule pero hindi lang na-flip
                // ang status — i-flip na lang, huwag nang mag-generate ulit.
                DB::table('loan_applications')->where('application_id', $app->application_id)
                    ->update(['status' => 'active']);
                $this->info("Application #{$app->application_id}: kumpleto na pala yung schedule, in-active na lang.");
                continue;
            }

            if ($scheduleCount > 0) {
                // Partial schedule (e.g. 1 of 3 rows) — linisin muna bago
                // mag-regenerate, kasi may "alreadyHasSchedule" guard sa
                // loob ng LoanActivationService na titigil kung may laman na.
                DB::table('loan_payment_schedules')->where('application_id', $app->application_id)->delete();
                $this->warn("Application #{$app->application_id}: na-clear ang {$scheduleCount} partial row(s).");
            }

            LoanActivationService::activate($app->application_id);

            $newStatus = DB::table('loan_applications')->where('application_id', $app->application_id)->value('status');

            if ($newStatus === 'active') {
                $this->info("Application #{$app->application_id}: successfully activated.");
            } else {
                $this->error("Application #{$app->application_id}: hindi pa rin na-activate — tignan ang laravel.log.");
            }
        }
    }
}