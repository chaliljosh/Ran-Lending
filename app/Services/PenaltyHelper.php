<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Lahat ng penalty computation logic dito lang — parehong style ng
// AdminController mo (DB::table() query builder, walang models).
class PenaltyHelper
{
    // Flat ₱50 kada 7 araw na late.
    public static function calculatePenalty($schedule): array
    {
        if ($schedule->status === 'paid') {
            return ['amount' => 0, 'periods' => 0, 'days_late' => 0];
        }

        $daysLate = Carbon::parse($schedule->due_date)->diffInDays(now(), false);
        $daysLate = max(0, (int) $daysLate);

        if ($daysLate <= 0) {
            return ['amount' => 0, 'periods' => 0, 'days_late' => 0];
        }

        $periodDays = config('penalty.late_fee_period_days');
        $feeAmount = config('penalty.late_fee_amount');

        $periods = intdiv($daysLate, $periodDays);
        $amount = $periods * $feeAmount;

        return ['amount' => $amount, 'periods' => $periods, 'days_late' => $daysLate];
    }

    // Tinitingnan yung schedule ng isang loan application, hinahanap
    // yung magkakasunod na "walang bayad talaga" na installments,
    // tapos nag-a-award ng point kada kumpletong grupo ng 3.
    public static function evaluatePenaltyPoints($applicationId): void
    {
        $userId = DB::table('loan_applications')
            ->where('application_id', $applicationId)
            ->value('user_id');

        if (!$userId) {
            return;
        }

        $schedules = DB::table('loan_payment_schedules')
            ->where('application_id', $applicationId)
            ->orderBy('installment_no')
            ->get();

        $consecutiveMissed = 0;
        $missedStart = null;

        foreach ($schedules as $schedule) {
            $isMissedMonth = Carbon::parse($schedule->due_date)->isPast() && $schedule->amount_paid == 0;

            if ($isMissedMonth) {
                $consecutiveMissed++;
                $missedStart ??= $schedule->installment_no;

                if ($consecutiveMissed === config('penalty.months_per_point')) {
                    self::awardPoint(
                        $userId,
                        $applicationId,
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

    private static function awardPoint($userId, $applicationId, $reason): void
    {
        $record = DB::table('borrower_penalty_points')->where('user_id', $userId)->first();

        if (!$record) {
            $id = DB::table('borrower_penalty_points')->insertGetId([
                'user_id' => $userId,
                'points' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $record = DB::table('borrower_penalty_points')->find($id);
        }

        if ($record->points >= config('penalty.max_penalty_points')) {
            return;
        }

        $newPoints = $record->points + 1;

        DB::table('borrower_penalty_points')
            ->where('user_id', $userId)
            ->update([
                'points' => $newPoints,
                'disqualified_at' => $newPoints >= config('penalty.max_penalty_points') && !$record->disqualified_at
                    ? now()
                    : $record->disqualified_at,
                'updated_at' => now(),
            ]);

        DB::table('borrower_penalty_point_logs')->insert([
            'user_id' => $userId,
            'application_id' => $applicationId,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Tinatawag pag na-fully settle na ng borrower yung outstanding
    // balance nila — nag-se-set ng 90-day countdown papuntang
    // reapplication eligibility.
    public static function markSettled($userId): void
    {
        $record = DB::table('borrower_penalty_points')->where('user_id', $userId)->first();
        if (!$record || !$record->disqualified_at) {
            return;
        }

        DB::table('borrower_penalty_points')->where('user_id', $userId)->update([
            'settled_at' => now(),
            'reapplication_eligible_at' => now()->addDays(config('penalty.reapplication_wait_days')),
            'updated_at' => now(),
        ]);
    }

    // True kung disqualified pa rin ang borrower ngayon.
    public static function isDisqualified($userId): bool
    {
        $record = DB::table('borrower_penalty_points')->where('user_id', $userId)->first();

        if (!$record || !$record->disqualified_at) {
            return false;
        }
        if ($record->reapplication_eligible_at && now()->greaterThanOrEqualTo($record->reapplication_eligible_at)) {
            return false;
        }
        return true;
    }
}