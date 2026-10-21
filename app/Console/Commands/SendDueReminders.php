<?php

namespace App\Console\Commands;

use App\Mail\PaymentDueReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Nagpapadala ng reminder email sa mga borrower na malapit na ang due date ng bayad.
//
// Manual na takbo:      php artisan loans:send-due-reminders
// Ibang bilang ng araw: php artisan loans:send-due-reminders --days=5
// Ulitin kahit naipadala na (pang-test): php artisan loans:send-due-reminders --force
class SendDueReminders extends Command
{
    protected $signature = 'loans:send-due-reminders {--days=3} {--force}';

    protected $description = 'Email borrowers whose next payment is due soon';

    public function handle()
    {
        $days  = (int) $this->option('days');
        $force = (bool) $this->option('force');

        $today = Carbon::today();
        $until = $today->copy()->addDays($days);

        // Mga unpaid na installment na due mula ngayon hanggang $days na araw
        $query = DB::table('loan_payment_schedules as s')
            ->join('loan_applications as la', 'la.application_id', '=', 's.application_id')
            ->join('users as u', 'u.user_id', '=', 'la.user_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->whereIn('la.status', ['active', 'approved'])
            ->whereNull('s.paid_at')
            ->whereBetween('s.due_date', [$today->toDateString(), $until->toDateString()])
            ->select(
                's.id', 's.installment_no', 's.amount_due', 's.due_date',
                'la.uuid as loan_uuid',
                'u.email', 'u.name', 'bpd.first_name'
            );

        // Kapag walang --force, laktawan ang mga naipadalhan na
        if (!$force) {
            $query->whereNull('s.reminder_sent_at');
        }

        $rows = $query->get();

        $sent = 0;

        foreach ($rows as $row) {
            if (empty($row->email)) {
                continue;
            }

            $displayName = $row->first_name ?: ($row->name ?: 'Borrower');
            $loanId      = 'LN-' . strtoupper(substr($row->loan_uuid, 0, 8));
            $daysLeft    = (int) $today->diffInDays(Carbon::parse($row->due_date), false);

            try {
                Mail::to($row->email)->send(new PaymentDueReminder(
                    $loanId,
                    $displayName,
                    (int) $row->installment_no,
                    (float) $row->amount_due,
                    (string) $row->due_date,
                    $daysLeft
                ));

                DB::table('loan_payment_schedules')
                    ->where('id', $row->id)
                    ->update(['reminder_sent_at' => now()]);

                $sent++;
            } catch (\Throwable $e) {
                Log::warning("Due reminder failed (schedule {$row->id}): " . $e->getMessage());
            }
        }

        $this->info("Due reminders sent: {$sent} of {$rows->count()}");

        return self::SUCCESS;
    }
}