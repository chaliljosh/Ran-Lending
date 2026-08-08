<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Ginagamit pag na-approve na ang isang loan application (3 admins) —
// gumagawa ng buong payment schedule (isang row per buwan, base sa
// loan_term), tapos ini-activate yung loan application.
class LoanActivationService
{
    public static function activate($applicationId): void
    {
        $app = DB::table('loan_applications')->where('application_id', $applicationId)->first();

        if (!$app) {
            return;
        }

     
        $alreadyHasSchedule = DB::table('loan_payment_schedules')
            ->where('application_id', $applicationId)
            ->exists();

        if ($alreadyHasSchedule) {
            return;
        }

        $termMonths = (int) $app->loan_term;
        $monthlyPayment = (float) $app->monthly_payment;
        $startDate = Carbon::now(); // araw ng pag-approve = simula ng loan

     
        $rows = [];
        for ($i = 1; $i <= $termMonths; $i++) {
            $rows[] = [
                'application_id' => $applicationId,
                'installment_no' => $i,
                'due_date' => $startDate->copy()->addMonths($i),
                'amount_due' => $monthlyPayment,
                'amount_paid' => 0,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

      
        try {
            DB::transaction(function () use ($rows, $applicationId) {
                DB::table('loan_payment_schedules')->insert($rows);

                // markahan na "active" — disbursed/ongoing na ang loan
                DB::table('loan_applications')->where('application_id', $applicationId)->update([
                    'status' => 'active',
                ]);
            });
        } catch (\Throwable $e) {
           
            Log::error("LoanActivationService::activate failed for application_id={$applicationId}: " . $e->getMessage());
        }
    }
}