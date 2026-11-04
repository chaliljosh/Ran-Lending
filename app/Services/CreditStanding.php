<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreditStanding
{
    public const LATE_DAYS = 7;
    public const BAD_WAIT_MONTHS = 3;

    /**
     * Credit standing ng isang borrower.
     * Gumagamit ng forUsers() para iisa lang ang logic.
     *
     * @return array{standing:string,label:string,color:string,reason:string,wait_until:?Carbon,late_count:int,worst_days_late:int}
     */
    public static function forUser(int $userId): array
    {
        return self::forUsers([$userId])[$userId];
    }

    /**
     * Bulk version: isang query para sa lahat ng schedules, tapos group sa PHP.
     *
     * @param  int[] $userIds
     * @return array<int, array>  [user_id => result array]
     */
    public static function forUsers(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (!$userIds) {
            return [];
        }

        // Pinakahuling completed/defaulted loan ng bawat user
        $loans = DB::table('loan_applications')
            ->whereIn('user_id', $userIds)
            ->whereIn('status', ['completed', 'defaulted'])
            ->orderByDesc('applied_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($g) => $g->first());

        // Schedules ng mga loan na iyon (isang query)
        $schedules = DB::table('loan_payment_schedules')
            ->whereIn('application_id', $loans->pluck('application_id')->all())
            ->select('application_id', 'due_date', 'paid_at')
            ->get()
            ->groupBy('application_id');

        // Pinakamatagal na overdue sa active loan ng bawat user (isang query)
        $activeOldest = DB::table('loan_payment_schedules as s')
            ->join('loan_applications as la', 'la.application_id', '=', 's.application_id')
            ->whereIn('la.user_id', $userIds)
            ->where('la.status', 'active')
            ->whereNull('s.paid_at')
            ->where('s.due_date', '<', now())
            ->groupBy('la.user_id')
            ->selectRaw('la.user_id, MIN(s.due_date) as oldest_due')
            ->pluck('oldest_due', 'user_id');

        $out = [];
        foreach ($userIds as $uid) {
            $activeLate = isset($activeOldest[$uid])
                ? (int) Carbon::parse($activeOldest[$uid])->startOfDay()->diffInDays(Carbon::today())
                : 0;

            $lastLoan = $loans->get($uid);

            // Walang natapos na loan pa
            if (!$lastLoan) {
                $out[$uid] = $activeLate >= self::LATE_DAYS
                    ? self::result('watch', 'Watchlist', 'warning',
                        "Current loan is {$activeLate} days overdue.", null, 1, $activeLate)
                    : self::result('new', 'New Borrower', 'secondary',
                        'No completed loan yet.', null, 0, 0);
                continue;
            }

            $rows = $schedules->get($lastLoan->application_id, collect());

            $lateCount = 0;
            $worst = 0;
            foreach ($rows as $s) {
                $due  = Carbon::parse($s->due_date)->startOfDay();
                $paid = $s->paid_at ? Carbon::parse($s->paid_at)->startOfDay() : Carbon::today();
                $days = (int) $due->diffInDays($paid, false);
                if ($days >= self::LATE_DAYS) {
                    $lateCount++;
                    $worst = max($worst, $days);
                }
            }

            // BAD: defaulted o may installment na na-late nang 7 araw pataas
            if ($lastLoan->status === 'defaulted' || $lateCount > 0) {
                $lastPaid = $rows->whereNotNull('paid_at')->max('paid_at');
                $finished = $lastPaid
                    ? Carbon::parse($lastPaid)->startOfDay()
                    : ($rows->isNotEmpty()
                        ? Carbon::parse($rows->max('due_date'))->startOfDay()
                        : Carbon::today());

                $canApplyOn = $finished->copy()->addMonths(self::BAD_WAIT_MONTHS);
                $waitUntil = Carbon::today()->lt($canApplyOn) ? $canApplyOn : null;

                $reason = $lastLoan->status === 'defaulted'
                    ? 'Previous loan was defaulted.'
                    : "{$lateCount} installment(s) paid ≥ " . self::LATE_DAYS . " days late (worst: {$worst} days).";

                $out[$uid] = self::result('bad', 'Bad Credit', 'danger', $reason, $waitUntil, $lateCount, $worst);
                continue;
            }

            // WATCH: malinis ang huling loan pero overdue ngayon ang kasalukuyan
            if ($activeLate >= self::LATE_DAYS) {
                $out[$uid] = self::result('watch', 'Watchlist', 'warning',
                    "Previous loan was clean, but current loan is {$activeLate} days overdue.", null, 0, $activeLate);
                continue;
            }

            // GOOD
            $out[$uid] = self::result('good', 'Good Credit', 'success',
                'All installments of the last loan were paid on time.', null, 0, 0);
        }

        return $out;
    }

    private static function result($standing, $label, $color, $reason, $waitUntil, $lateCount, $worst): array
    {
        return [
            'standing'        => $standing,
            'label'           => $label,
            'color'           => $color,
            'reason'          => $reason,
            'wait_until'      => $waitUntil,
            'late_count'      => $lateCount,
            'worst_days_late' => $worst,
        ];
    }
}