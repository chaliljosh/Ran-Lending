<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Dalawa na ang trabaho nito:
//
// 1. activate()       - tinatawag pag na-approve na ng 3 admins.
//                       Status lang ang binabago ("active").
//                       WALA nang payment schedule dito.
//
// 2. createSchedule() - tinatawag pag nag-send na ng Proof of Release ang admin.
//                       Dito ginagawa ang payment schedule, base sa petsa ng release.
class LoanActivationService
{
    // Pag na-approve na ng 3 admins: markahan lang na "active" ang loan.
    public static function activate($applicationId): void
    {
        try {
            DB::table('loan_applications')
                ->where('application_id', $applicationId)
                ->update(['status' => 'active']);
        } catch (\Throwable $e) {
            Log::error("LoanActivationService::activate failed for application_id={$applicationId}: " . $e->getMessage());
        }
    }

    // Gumagawa ng buong payment schedule (isang row kada buwan, base sa loan_term).
    //
    // Due date ng installment #n = petsa ng release + n buwan, minus 1 araw.
    // Halimbawa: release Oct 27 -> Nov 26, Dec 26, ...
    //
    // Sinasadya na walang try/catch dito: kapag pumalya, mag-throw para
    // ma-rollback ng tumawag (sendProofToBorrower) ang transaction.
    public static function createSchedule($applicationId, $releaseDate = null): void
    {
        $app = DB::table('loan_applications')->where('application_id', $applicationId)->first();

        if (!$app) {
            throw new \RuntimeException('Loan not found.');
        }

        // Isang beses lang per loan. Kung may schedule na, wala nang gagawin.
        $alreadyHasSchedule = DB::table('loan_payment_schedules')
            ->where('application_id', $applicationId)
            ->exists();

        if ($alreadyHasSchedule) {
            return;
        }

        $termMonths = (int) $app->loan_term;

        if ($termMonths < 1) {
            throw new \RuntimeException('Invalid loan term.');
        }

        $monthlyPayment = (float) $app->monthly_payment;
        $totalAmount = (float) $app->total_amount;

        // Petsa lang ang kailangan (walang oras)
        $release = $releaseDate ? Carbon::parse($releaseDate) : Carbon::now();
        $release = $release->copy()->startOfDay();

        $rows = [];
        $runningTotal = 0;

        for ($i = 1; $i <= $termMonths; $i++) {
            // 1 buwan after release, minus 1 araw.
            // addMonthsNoOverflow para hindi lumampas ang buwan (hal. release sa 31st).
            $dueDate = $release->copy()->addMonthsNoOverflow($i)->subDay();

            // Ang huling installment ang sumasalo ng sobra/kulang na centavos,
            // para eksaktong tumama sa total_amount ang kabuuan.
            $amountDue = ($i === $termMonths)
                ? round($totalAmount - $runningTotal, 2)
                : $monthlyPayment;

            $runningTotal += $amountDue;

            $rows[] = [
                'application_id' => $applicationId,
                'installment_no' => $i,
                'due_date' => $dueDate->toDateString(),
                'amount_due' => $amountDue,
                'amount_paid' => 0,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('loan_payment_schedules')->insert($rows);
    }
}