<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DEMO LANG. Nagdadagdag ng ISANG borrower na may BAD CREDIT
 * (completed loan, may installment na na-late nang 10 araw).
 *
 * - Hindi ginagalaw ang mga borrower na nasa Credit Standing list na (may completed loan na).
 * - Kung gusto mo ng specific borrower, ilagay ang user_id sa TARGET_USER_ID.
 * - Gumagamit ng parehong marker ng CreditTestSeeder, kaya buburahin din ito
 *   ng CreditTestCleanupSeeder.
 *
 * Run:  php artisan db:seed --class=CreditBadDemoSeeder
 */
class CreditBadDemoSeeder extends Seeder
{
    // Ilagay dito ang user_id kung may gusto kang specific na borrower. null = auto-pick.
    public const TARGET_USER_ID = null;

    public function run(): void
    {
        $userId = self::TARGET_USER_ID ?? DB::table('borrowerpersonaldetails')
            ->whereNotIn('user_id', DB::table('loan_applications')
                ->where('status', 'completed')
                ->select('user_id'))
            ->orderBy('user_id')
            ->value('user_id');

        if (!$userId) {
            $this->command?->warn(
                'Walang available na borrower (lahat ay nasa list na). ' .
                'Mag-register ng bagong borrower o ilagay ang user_id sa TARGET_USER_ID.'
            );
            return;
        }

        DB::transaction(function () use ($userId) {
            $this->createBadLoan((int) $userId);
        });

        $this->command?->info("User {$userId}: bad credit loan added.");
    }

    private function createBadLoan(int $userId): void
    {
        $term = 3;
        $amount = 15000;
        $interest = $amount * 0.05 * $term;
        $total = $amount + $interest;
        $monthly = round($total / $term, 2);
        $appliedAt = Carbon::now()->subMonths(8)->startOfDay();

        $applicationId = DB::table('loan_applications')->insertGetId([
            'uuid'               => (string) Str::uuid(),
            'user_id'            => $userId,
            'loan_amount'        => $amount,
            'loan_term'          => $term,
            'interest_rate'      => 5.00,
            'total_interest'     => $interest,
            'total_amount'       => $total,
            'monthly_payment'    => $monthly,
            'loan_purpose'       => 'others',
            'loan_purpose_other' => CreditTestSeeder::MARKER,
            'referral_source'    => CreditTestSeeder::REFERRAL_SOURCE,
            'status'             => 'completed',
            'applied_at'         => $appliedAt,
            'proof_sent_at'      => $appliedAt,
        ], 'application_id');

        for ($n = 1; $n <= $term; $n++) {
            $due = $appliedAt->copy()->addMonthsNoOverflow($n);

            // Installment #2 ay na-late nang 10 araw = bad credit
            $paidAt = $n === 2 ? $due->copy()->addDays(10) : $due->copy();

            DB::table('loan_payment_schedules')->insert([
                'application_id' => $applicationId,
                'installment_no' => $n,
                'due_date'       => $due->toDateString(),
                'amount_due'     => $monthly,
                'amount_paid'    => $monthly,
                'paid_at'        => $paidAt,
                'updated_at'     => now(),
            ]);
        }
    }
}