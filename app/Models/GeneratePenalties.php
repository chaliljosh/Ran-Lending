<?php

namespace App\Console\Commands;

use App\Models\ActiveLoan;
use App\Models\LoanPaymentSchedule;
use App\Models\LoanPenalty;
use App\Services\PenaltyCalculator;
use App\Services\PenaltyPointsService;
use Illuminate\Console\Command;

class GeneratePenalties extends Command
{
    protected $signature = 'penalties:generate';
    protected $description = 'Check overdue payment schedules, apply late fees, and update penalty points';

    public function handle(): void
    {
        $overdue = LoanPaymentSchedule::where('status', 'pending')
            ->where('due_date', '<', now())
            ->get();

        $count = 0;

        foreach ($overdue as $schedule) {
            $result = PenaltyCalculator::calculate($schedule);

            if ($result['amount'] <= 0) {
                continue;
            }

            $schedule->update(['status' => 'late']);

            LoanPenalty::updateOrCreate(
                ['loan_payment_schedule_id' => $schedule->id],
                [
                    'active_loan_id' => $schedule->active_loan_id,
                    'days_late' => $result['days_late'],
                    'late_fee_periods' => $result['periods'],
                    'penalty_amount' => $result['amount'],
                    'status' => 'unpaid',
                ]
            );

            $count++;
        }

        ActiveLoan::where('status', 'ongoing')->get()->each(function ($loan) {
            PenaltyPointsService::evaluate($loan);
        });

        $this->info("Processed {$overdue->count()} overdue schedules, {$count} penalties updated.");
    }
}