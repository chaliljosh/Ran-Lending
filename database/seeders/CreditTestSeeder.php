<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * TEST DATA LANG. Nilalagyan ng lumang loan ang unang 2 borrower na may personal details:
 *   1st borrower -> GOOD   (completed, lahat on-time)
 *   2nd borrower -> BAD    (completed, may installment na na-late nang 10 araw)
 *
 * Lahat ng ginawa ay may marker na loan_purpose_other = 'TEST-CREDIT-SEED'
 * para madaling burahin gamit ang CreditTestCleanupSeeder.
 *
 * Run:  php artisan db:seed --class=CreditTestSeeder
 */
class CreditTestSeeder extends Seeder
{
    public const MARKER = 'TEST-CREDIT-SEED';

    // Palitan kung iba ang valid values ng referral_source sa schema mo.
    public const REFERRAL_SOURCE = 'others';

    public function run(): void
    {
        $borrowers = DB::table('borrowerpersonaldetails')
            ->orderBy('user_id')
            ->limit(2)
            ->pluck('user_id')
            ->values();

        if ($borrowers->isEmpty()) {
            $this->command?->warn('Walang borrower na may personal details.');
            return;
        }

        $scenarios = ['good', 'bad_late'];

        foreach ($borrowers as $i => $userId) {
            DB::transaction(function () use ($userId, $scenarios, $i) {
                $this->createLoan((int) $userId, $scenarios[$i]);
            });

            $this->command?->info("User {$userId}: {$scenarios[$i]}");
        }
    }

    private function createLoan(int $userId, string $scenario): void
    {
        $term = 3;
        $amount = 15000;
        $total = $amount + ($amount * 0.05 * $term);
        $monthly = round($total / $term, 2);
        $appliedAt = Carbon::now()->subMonths(8)->startOfDay();

        // Dapat string na valid sa status CHECK constraint
        $status = 'completed';

        $applicationId = DB::table('loan_applications')->insertGetId([
            'uuid'               => (string) Str::uuid(),
            'user_id'            => $userId,
            'loan_amount'        => $amount,
            'loan_term'          => $term,
            'interest_rate'      => 5.00,
            'total_interest'     => $amount * 0.05 * $term,
            'total_amount'       => $total,
            'monthly_payment'    => $monthly,
            'loan_purpose'       => 'others',
            'loan_purpose_other' => self::MARKER,
            'referral_source'    => self::REFERRAL_SOURCE,
            'status'             => $status,
            'applied_at'         => $appliedAt,
            'proof_sent_at'      => $appliedAt,
        ], 'application_id');

        for ($n = 1; $n <= $term; $n++) {
            $due = $appliedAt->copy()->addMonthsNoOverflow($n);
            $paidAt = $due->copy(); // on-time
            $amountPaid = $monthly;

            if ($scenario === 'bad_late' && $n === 2) {
                $paidAt = $due->copy()->addDays(10); // late nang 10 araw
            }

            DB::table('loan_payment_schedules')->insert([
                'application_id' => $applicationId,
                'installment_no' => $n,
                'due_date'       => $due->toDateString(),
                'amount_due'     => $monthly,
                'amount_paid'    => $amountPaid,
                'paid_at'        => $paidAt,
                'updated_at'     => now(),
            ]);
        }
    }
}