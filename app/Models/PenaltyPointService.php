<?php

namespace App\Services;

use App\Models\ActiveLoan;
use App\Models\BorrowerPenaltyPoint;
use App\Models\BorrowerPenaltyPointLog;

class PenaltyPointsService
{
    // Tinitingnan yung schedule ng isang active loan, hinahanap yung
    // magkakasunod na "walang bayad talaga" na installments, tapos
    // nag-a-award ng point kada kumpletong grupo ng 3.
    public static function evaluate(ActiveLoan $loan): void
    {
        $userId = $loan->loanApplication->user_id ?? null;

        if (!$userId) {
            return;
        }

        $schedules = $loan->paymentSchedules()->orderBy('installment_no')->get();

        $consecutiveMissed = 0;
        $missedStart = null;

        foreach ($schedules as $schedule) {
            $isMissedMonth = $schedule->due_date->isPast() && $schedule->amount_paid == 0;

            if ($isMissedMonth) {
                $consecutiveMissed++;
                $missedStart ??= $schedule->installment_no;

                if ($consecutiveMissed === config('penalty.months_per_point')) {
                    self::awardPoint(
                        $userId,
                        $loan,
                        "Installments #{$missedStart}–#{$schedule->installment_no} unpaid (3 consecutive months)"
                    );
                    $consecutiveMissed = 0;
                    $missedStart = null;
                }
            } else {
                $consecutiveMissed = 0;
                $missedStart = null;
            }
        }
    }

    protected static function awardPoint(int $userId, ActiveLoan $loan, string $reason): void
    {
        $record = BorrowerPenaltyPoint::firstOrCreate(['user_id' => $userId]);

        if ($record->points >= config('penalty.max_penalty_points')) {
            return;
        }

        $record->increment('points');

        BorrowerPenaltyPointLog::create([
            'user_id' => $userId,
            'active_loan_id' => $loan->id,
            'reason' => $reason,
        ]);

        if ($record->fresh()->points >= config('penalty.max_penalty_points') && !$record->disqualified_at) {
            $record->update(['disqualified_at' => now()]);
        }
    }

    // Tinatawag kapag na-fully settle na ng borrower yung outstanding
    // balance nila — nag-se-set ng 90-day countdown papuntang
    // reapplication eligibility.
    public static function markSettled(int $userId): void
    {
        $record = BorrowerPenaltyPoint::where('user_id', $userId)->first();
        if (!$record || !$record->disqualified_at) {
            return;
        }

        $record->update([
            'settled_at' => now(),
            'reapplication_eligible_at' => now()->addDays(config('penalty.reapplication_wait_days')),
        ]);
    }
}