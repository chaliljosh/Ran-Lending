<?php

namespace App\Http\Controllers;

use App\Mail\PaymentPartiallyRecorded;
use App\Mail\PaymentReceiptRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReceiptReviewController extends Controller
{
    // Parehong disk ng AdminController (RECEIPT_DISK at BORROWER_RECEIPT_DISK)
    private const RECEIPT_DISK = 'valid_ids';
    private const BORROWER_RECEIPT_DISK = 'payment_receipts';

    /**
     * Kulang ang binayad pero totoo ang receipt (hal. 1500 sa 1900).
     * Ire-record ang natanggap na amount. HINDI pa Paid ang installment:
     * walang paid_at, amount_paid lang ang nadadagdagan.
     * May kasamang ledger row (installment_payments, kind = 'partial')
     * at kopya ng receipt para makita sa Payment Details.
     */
    public function recordPartial(Request $request, $id)
    {
        $request->validate([
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'paid_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'remarks'   => ['nullable', 'string', 'max:500'],
        ], [
            'amount.required'           => 'Please enter the amount received.',
            'amount.min'                => 'The amount must be greater than zero.',
            'paid_date.before_or_equal' => 'The payment date cannot be in the future.',
        ]);

        $adminId  = Session::get('user_id');
        $amount   = round((float) $request->input('amount'), 2);
        $paidDate = $request->filled('paid_date') ? $request->input('paid_date') : now()->toDateString();
        $remarks  = trim((string) $request->input('remarks', ''));

        $copiedPath = null; // kopya ng receipt; buburahin kung pumalya ang transaction

        try {
            $result = DB::transaction(function () use ($id, $adminId, $amount, $paidDate, $remarks, &$copiedPath) {
                $schedule = DB::table('loan_payment_schedules')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$schedule) {
                    throw new \RuntimeException('Installment not found.');
                }

                if (!is_null($schedule->paid_at)) {
                    throw new \RuntimeException('This installment is already marked as paid.');
                }

                $receipt = DB::table('payment_receipts')
                    ->where('schedule_id', $id)
                    ->where('status', 'submitted')
                    ->lockForUpdate()
                    ->first();

                if (!$receipt) {
                    throw new \RuntimeException('There is no pending receipt for this installment.');
                }

                $balance = round((float) $schedule->amount_due - (float) $schedule->amount_paid, 2);

                // Kung sapat na o sobra, dapat sa normal na Record Payment dumaan.
                if ($amount >= $balance) {
                    throw new \RuntimeException(
                        'This amount covers the full balance of ₱' . number_format($balance, 2) . '. Use Record Payment instead.'
                    );
                }

                // Kopyahin ang receipt ng borrower para makita ito sa Payment Details
                // kahit may kasunod pang receipt para sa balance.
                if (!empty($receipt->user_file_path)) {
                    $ext = pathinfo($receipt->user_file_path, PATHINFO_EXTENSION) ?: 'jpg';
                    $path = 'payment-receipts/' . $schedule->application_id . '/'
                        . $id . '-partial-' . Str::random(12) . '.' . $ext;

                    try {
                        $contents = Storage::disk(self::BORROWER_RECEIPT_DISK)->get($receipt->user_file_path);
                        Storage::disk(self::RECEIPT_DISK)->put($path, $contents);
                        $copiedPath = $path;
                    } catch (\Throwable $e) {
                        Log::error("Partial receipt copy failed (schedule {$id}): " . $e->getMessage());
                        throw new \RuntimeException('Failed to attach the borrower receipt. Please try again.');
                    }
                }

                $newPaid = round((float) $schedule->amount_paid + $amount, 2);

                DB::table('loan_payment_schedules')->where('id', $id)->update([
                    'amount_paid' => $newPaid,
                    'updated_at'  => now(),
                ]);

                // Ledger: isang row kada bayad
                DB::table('installment_payments')->insert([
                    'schedule_id'    => $schedule->id,
                    'application_id' => $schedule->application_id,
                    'amount'         => $amount,
                    'paid_on'        => $paidDate,
                    'kind'           => 'partial',
                    'receipt_path'   => $copiedPath,
                    'admin_id'       => $adminId,
                    'created_at'     => now(),
                ]);

                // 'verified' ang receipt na ito (tinanggap ang ₱amount), kaya
                // mawawala na ang "submitted" at makakapag-upload ulit si borrower.
                DB::table('payment_receipts')->where('receipt_id', $receipt->receipt_id)->update([
                    'status'             => 'verified',
                    'amount_recorded'    => $amount,
                    'recorded_paid_date' => $paidDate,
                    'admin_remarks'      => $remarks !== '' ? $remarks : null,
                    'reviewed_by'        => $adminId,
                    'reviewed_at'        => now(),
                    'updated_at'         => now(),
                ]);

                return [
                    'schedule'    => $schedule,
                    'new_paid'    => $newPaid,
                    'new_balance' => round((float) $schedule->amount_due - $newPaid, 2),
                ];
            });
        } catch (\RuntimeException $e) {
            $this->discardCopy($copiedPath);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            $this->discardCopy($copiedPath);
            Log::error("Record partial failed (schedule {$id}): " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to record the partial payment.'], 500);
        }

        $this->sendPartialEmail($result['schedule'], $amount, $result['new_balance'], $remarks);

        return response()->json([
            'success'             => true,
            'message'             => 'Partial payment recorded. The borrower was notified.',
            'schedule_id'         => (int) $id,
            'amount_paid_display' => number_format($result['new_paid'], 2),
            'balance_display'     => number_format($result['new_balance'], 2),
        ]);
    }

    /**
     * Malabo, mali, o peke ang receipt. Walang amount na nare-record.
     * Magiging 'rejected' ang receipt at makakapag-upload ulit si borrower.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'remarks.required' => 'Please enter the reason.',
            'remarks.min'      => 'Please enter a reason (at least 5 characters).',
        ]);

        $adminId = Session::get('user_id');
        $remarks = trim($request->input('remarks'));

        $schedule = DB::table('loan_payment_schedules')->where('id', $id)->first();

        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Installment not found.'], 404);
        }

        $updated = DB::table('payment_receipts')
            ->where('schedule_id', $id)
            ->where('status', 'submitted')
            ->update([
                'status'        => 'rejected',
                'admin_remarks' => $remarks,
                'reviewed_by'   => $adminId,
                'reviewed_at'   => now(),
                'updated_at'    => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['success' => false, 'message' => 'There is no pending receipt for this installment.'], 422);
        }

        $this->sendRejectedEmail($schedule, $remarks);

        return response()->json([
            'success'     => true,
            'message'     => 'Receipt rejected. The borrower was notified.',
            'schedule_id' => (int) $id,
        ]);
    }

    // ---------- Helpers ----------

    /** Burahin ang kinopyang receipt kung pumalya ang transaction (iwas orphan file). */
    private function discardCopy(?string $path): void
    {
        if (!$path) {
            return;
        }

        try {
            Storage::disk(self::RECEIPT_DISK)->delete($path);
        } catch (\Throwable $ignored) {
        }
    }

    // ---------- Emails ----------

    private function findBorrower($applicationId)
    {
        $loan = DB::table('loan_applications')->where('application_id', $applicationId)->first();

        if (!$loan) {
            return null;
        }

        $borrower = DB::table('users as u')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'u.user_id')
            ->where('u.user_id', $loan->user_id)
            ->select('u.email', 'u.name', 'bpd.first_name')
            ->first();

        if ($borrower) {
            // Isinasama ang loan para magamit ng email ang loan number (LN-XXXXXXXX)
            $borrower->loan = $loan;
        }

        return $borrower;
    }

    private function sendPartialEmail($schedule, float $received, float $balance, string $remarks): void
    {
        try {
            $borrower = $this->findBorrower($schedule->application_id);

            if (!$borrower || empty($borrower->email)) {
                return;
            }

            $name = $borrower->first_name ?: ($borrower->name ?: 'Borrower');

            Mail::to($borrower->email)->send(new PaymentPartiallyRecorded(
                $borrower->loan,
                $name,
                (int) $schedule->installment_no,
                (float) $schedule->amount_due,
                $received,
                $balance,
                $remarks !== '' ? $remarks : null
            ));
        } catch (\Throwable $e) {
            // Hindi dapat pumalya ang pag-record dahil lang sa email.
            Log::warning("Partial payment email failed (schedule {$schedule->id}): " . $e->getMessage());
        }
    }

    private function sendRejectedEmail($schedule, string $remarks): void
    {
        try {
            $borrower = $this->findBorrower($schedule->application_id);

            if (!$borrower || empty($borrower->email)) {
                return;
            }

            $name = $borrower->first_name ?: ($borrower->name ?: 'Borrower');

            Mail::to($borrower->email)->send(new PaymentReceiptRejected(
                $borrower->loan,
                $name,
                (int) $schedule->installment_no,
                (float) $schedule->amount_due,
                $remarks
            ));
        } catch (\Throwable $e) {
            Log::warning("Receipt rejected email failed (schedule {$schedule->id}): " . $e->getMessage());
        }
    }
}