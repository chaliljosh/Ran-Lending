<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Statement of Account (SOA) — downloadable PDF para sa borrower,
 * available lang kapag FULLY PAID na ang loan.
 *
 * Layout (galing sa beginner-bookkeeping.com/statement-of-account):
 *  - Header: RAN Serenity + borrower name/address
 *  - Loan ID + statement date
 *  - Opening balance = total payable (principal + interest)
 *  - Ledger: Date | Ref | Description | Charges | Payment | Balance
 *  - Penalties = ang katumbas ng "interest / finance charge" nila
 *  - Final balance (malaki, bold) + "PAID IN FULL"
 *  - Comments
 */
class StatementOfAccountController extends Controller
{
    // Palitan mo ito ng totoong contact details ng RAN Serenity.
   private const COMPANY = [
        'name'    => 'RAN Serenity',
        'address' => 'New Cabalan, Olongapo City',
        'email'   => 'ranserenity77@gmail.com',
        'phone'   => '+63 969 189 8835',
    ];

    public function download(Request $request, $id)
    {
        $userId = Session::get('user_id');

        // Ownership check: sariling loan lang ng borrower ang pwedeng i-download.
        $app = DB::table('loan_applications')
            ->where('application_id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$app) {
            abort(404);
        }

        $schedule = DB::table('loan_payment_schedules')
            ->where('application_id', $app->application_id)
            ->orderBy('installment_no')
            ->get();

        // Fully paid lang: lahat ng installment ay may paid_at. Naka-check ito
        // sa server, hindi lang sa blade, para hindi ma-bypass gamit ang URL.
        $isFullyPaid = $schedule->isNotEmpty()
            && $schedule->every(fn ($s) => !is_null($s->paid_at));

        if (!$isFullyPaid) {
            abort(403, 'Your Statement of Account is available once the loan is fully paid.');
        }

        // ── Borrower ──
        $details = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();
        $address = $details
            ? DB::table('borroweraddress')->where('detail_id', $details->detail_id)->first()
            : null;

        $borrowerName = $this->borrowerName($details);
        $borrowerAddress = $this->borrowerAddress($address);

        // ── Loan summary ──
        $loanId = 'LN-' . strtoupper(substr((string) $app->uuid, 0, 8));

        $purposeLabel = ($app->loan_purpose === 'others' && !empty($app->loan_purpose_other))
            ? $app->loan_purpose_other
            : config('loan_labels.loan_purposes.' . $app->loan_purpose, 'Others');

        $releasedAt = $app->proof_sent_at ?: $app->applied_at;
        $openingDate = Carbon::parse($releasedAt);
        $totalPayable = round((float) $app->total_amount, 2);

        // ── Ledger sources ──
        $scheduleById = $schedule->keyBy('id');

        $payments = DB::table('installment_payments')
            ->where('application_id', $app->application_id)
            ->whereNull('voided_at')
            ->orderBy('paid_on')
            ->orderBy('id')
            ->get();

        // Waived na penalty ay hindi isinasama sa SOA.
        $penalties = DB::table('loan_penalties')
            ->where('application_id', $app->application_id)
            ->where('status', '!=', 'waived')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $entries = collect();

        foreach ($penalties as $p) {
            $no = $scheduleById->get($p->schedule_id)->installment_no ?? '—';
            $entries->push([
                'ts'          => Carbon::parse($p->created_at)->startOfDay()->timestamp,
                'order'       => 1,
                'seq'         => (int) $p->id,
                'date'        => Carbon::parse($p->created_at),
                'ref'         => 'PEN-' . str_pad((string) $p->id, 5, '0', STR_PAD_LEFT),
                'description' => 'Late payment penalty, Installment #' . $no
                    . ' (' . (int) $p->days_late . ' days late)',
                'charge'      => round((float) $p->penalty_amount, 2),
                'payment'     => 0.0,
            ]);
        }

        $scheduleIdsWithPayments = $payments->pluck('schedule_id')->unique()->all();

        foreach ($payments as $pay) {
            $no = $scheduleById->get($pay->schedule_id)->installment_no ?? '—';
            $desc = 'Payment received, Installment #' . $no . ' (' . $this->kindLabel($pay->kind) . ')';

            if ((float) $pay->penalty_amount > 0) {
                $desc .= ', incl. ₱' . number_format((float) $pay->penalty_amount, 2) . ' penalty';
            }

            $entries->push([
                'ts'          => Carbon::parse($pay->paid_on)->startOfDay()->timestamp,
                'order'       => 2,
                'seq'         => (int) $pay->id,
                'date'        => Carbon::parse($pay->paid_on),
                'ref'         => 'PMT-' . str_pad((string) $pay->id, 5, '0', STR_PAD_LEFT),
                'description' => $desc,
                'charge'      => 0.0,
                'payment'     => round((float) $pay->amount, 2),
            ]);
        }

        // Fallback: installment na paid na pero walang row sa installment_payments
        // (hal. lumang data). Gamitin ang schedule mismo para hindi mawala sa SOA.
        foreach ($schedule as $s) {
            if (in_array($s->id, $scheduleIdsWithPayments, true)) {
                continue;
            }
            if (is_null($s->paid_at) || (float) $s->amount_paid <= 0) {
                continue;
            }

            $entries->push([
                'ts'          => Carbon::parse($s->paid_at)->startOfDay()->timestamp,
                'order'       => 2,
                'seq'         => (int) $s->id,
                'date'        => Carbon::parse($s->paid_at),
                'ref'         => 'INS-' . str_pad((string) $s->installment_no, 2, '0', STR_PAD_LEFT),
                'description' => 'Payment received, Installment #' . $s->installment_no . ' (Full payment)',
                'charge'      => 0.0,
                'payment'     => round((float) $s->amount_paid, 2),
            ]);
        }

        $entries = $entries->sortBy([
            ['ts', 'asc'],
            ['order', 'asc'],
            ['seq', 'asc'],
        ])->values();

        // Running balance: nagsisimula sa total payable (opening balance).
        $running = $totalPayable;
        $ledger = $entries->map(function ($e) use (&$running) {
            $running = round($running + $e['charge'] - $e['payment'], 2);
            $e['balance'] = $running;
            return $e;
        })->all();

        $totalPenalties = round((float) $penalties->sum('penalty_amount'), 2);
        $totalPayments  = round(collect($ledger)->sum('payment'), 2);
        $totalCharges   = round($totalPayable + $totalPenalties, 2);
        $balance        = round($totalCharges - $totalPayments, 2);

        if (abs($balance) < 0.005) {
            $balance = 0.0;
        }

                $isSettled = $balance <= 0.0;

        $borrowerId      = 'BR-' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT);
        $loanStatusLabel = $isSettled ? 'Fully Paid' : ucfirst(str_replace('_', ' ', (string) $app->status));
        $statementRef    = 'SOA-' . $loanId . '-' . now()->format('Ymd');

        if (!$isSettled) {
            // Fully paid ang schedule pero may natitirang balance sa ledger:
            // ibig sabihin hindi tugma ang penalty/payment records. I-log para ma-check.
            Log::warning('SOA balance is not zero for a fully paid loan', [
                'application_id' => $app->application_id,
                'balance'        => $balance,
            ]);
        }

        $settledAt = Carbon::parse($schedule->max('paid_at'));

        $data = [
 'logoPath'        => file_exists(public_path('images/logo.png')) ? public_path('images/logo.png') : null,
        'borrowerId'      => $borrowerId,
'loanStatusLabel' => $loanStatusLabel,
'statementRef'    => $statementRef,
            'company'         => self::COMPANY,
            'loanId'          => $loanId,
            'statementDate'   => now(),
            'borrowerName'    => $borrowerName,
            'borrowerAddress' => $borrowerAddress,
            'borrowerEmail'   => $details->email ?? '',
            'borrowerPhone'   => $details->cellphone_num ?? '',
            'app'             => $app,
            'purposeLabel'    => $purposeLabel,
            'releasedAt'      => $openingDate,
            'settledAt'       => $settledAt,
            'totalPayable'    => $totalPayable,
            'ledger'          => $ledger,
            'openingDate'     => $openingDate,
            'totalPenalties'  => $totalPenalties,
            'totalCharges'    => $totalCharges,
            'totalPayments'   => $totalPayments,
            'balance'         => $balance,
            'isSettled'       => $isSettled,
        ];

        // Audit Logs: hindi dapat pumalya ang download dahil lang dito.
        try {
            AuditLogger::log('loan.soa_downloaded', [
                'entity_type' => 'loan_application',
                'entity_id'   => $app->application_id,
                'description' => 'Downloaded Statement of Account for ' . $loanId,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        $filename = 'SOA-' . $loanId . '.pdf';

        return Pdf::loadView('borrower.soa', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    private function borrowerName($details): string
    {
        if (!$details) {
            return Session::get('user_name', 'Borrower');
        }

        $parts = array_filter([
            $details->first_name ?? '',
            $details->middle_name ?? '',
            $details->last_name ?? '',
            $details->suffix ?? '',
        ], fn ($p) => trim((string) $p) !== '');

        $name = trim(implode(' ', $parts));

        return $name !== '' ? mb_convert_case($name, MB_CASE_TITLE, 'UTF-8') : Session::get('user_name', 'Borrower');
    }

    private function borrowerAddress($address): string
    {
        if (!$address) {
            return '';
        }

        $street = trim((string) ($address->home_address ?? ''));
        if ($street === '') {
            $street = trim(implode(' ', array_filter([
                $address->house_no ?? '',
                $address->street ?? '',
                $address->subdivision ?? '',
            ])));
        }

        $parts = array_filter([
            $street,
            $address->barangay ?? '',
            $address->city ?? '',
            $address->province ?? '',
            !empty($address->zipcode) ? (string) $address->zipcode : '',
        ], fn ($p) => trim((string) $p) !== '');

        return implode(', ', $parts);
    }

    private function kindLabel($kind): string
    {
        return match (strtolower((string) $kind)) {
            'full'    => 'Full payment',
            'partial' => 'Partial payment',
            default   => ucfirst(str_replace(['_', '-'], ' ', (string) $kind)) ?: 'Payment',
        };
    }
}