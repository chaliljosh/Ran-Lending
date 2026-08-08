<?php

namespace App\Services;

use App\Models\LoanPaymentSchedule;

class PenaltyCalculator
{
    // Flat ₱50 kada 7 araw na late. Halimbawa: 15 days late =
    // floor(15/7) = 2 periods = ₱100.
    public static function calculate(LoanPaymentSchedule $schedule): array
    {
        $daysLate = $schedule->daysLate();

        if ($daysLate <= 0) {
            return ['amount' => 0, 'periods' => 0, 'days_late' => 0];
        }

        $periodDays = config('penalty.late_fee_period_days');
        $feeAmount = config('penalty.late_fee_amount');

        $periods = intdiv($daysLate, $periodDays);
        $amount = $periods * $feeAmount;

        return [
            'amount' => $amount,
            'periods' => $periods,
            'days_late' => $daysLate,
        ];
    }
}