<?php

namespace App\Console\Commands;

use App\Services\PenaltyHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GeneratePenalties extends Command
{
    protected $signature = 'penalties:generate';
    protected $description = 'Check overdue payment schedules, apply late fees, and update penalty points';

    public function handle(): void
    {
     
    $overdue = DB::table('loan_payment_schedules')
        ->where('status', 'pending')
        ->where('due_date', '<', now())
        ->get();

    $count = 0;

    foreach ($overdue as $schedule) {
        $result = PenaltyHelper::calculatePenalty($schedule);

        if ($result['amount'] <= 0) {
            continue;
        }

        DB::table('loan_payment_schedules')
            ->where('id', $schedule->id)
            ->update(['status' => 'late']);

        $existing = DB::table('loan_penalties')->where('schedule_id', $schedule->id)->first();

        if ($existing) {
            DB::table('loan_penalties')->where('schedule_id', $schedule->id)->update([
                'days_late' => $result['days_late'],
                'late_fee_periods' => $result['periods'],
                'penalty_amount' => $result['amount'],
                'updated_at' => now(),
            ]);
        } else {
            DB::table('loan_penalties')->insert([
                'schedule_id' => $schedule->id,
                'application_id' => $schedule->application_id,
                'days_late' => $result['days_late'],
                'late_fee_periods' => $result['periods'],
                'penalty_amount' => $result['amount'],
                'status' => 'unpaid',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $count++;
    }

    $applicationIds = DB::table('loan_payment_schedules')->distinct()->pluck('application_id');
    foreach ($applicationIds as $applicationId) {
        PenaltyHelper::evaluatePenaltyPoints($applicationId);
    }

    $this->info("Processed {$overdue->count()} overdue schedules, {$count} penalties updated.");
    }
}