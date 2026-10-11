<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class PaymentReceiptController extends Controller
{
    // Loan statuses that accept receipt uploads. Also used by
    // BorrowerController::loanDetails() to decide whether to show the button.
    public const UPLOADABLE_LOAN_STATUSES = ['approved', 'active'];

    // true  = only the first unpaid installment accepts a receipt
    // false = every unpaid installment does (the borrower may pay ahead)
    public const NEXT_UNPAID_ONLY = false;

    // Private storage disk (see config/filesystems.php).
    private const DISK = 'payment_receipts';

    private function fail($message, $status)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    /**
     * POST /api/borrower/payment-receipt
     * Upload a receipt for an installment, or replace the one still waiting for review.
     */
    public function submit(Request $request)
    {
        $userId = Session::get('user_id');

        // 10 uploads per 10 minutes per borrower.
        $rateKey = 'payment-receipt:' . $userId;
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            $minutes = (int) ceil(RateLimiter::availableIn($rateKey) / 60);
            return $this->fail('Too many uploads. Please try again in ' . $minutes . ' minute(s).', 429);
        }
        RateLimiter::hit($rateKey, 600);

        $validator = Validator::make($request->all(), [
            'application_id' => 'required|integer',
            'installment_no' => 'required|integer|min:1',
            'receipt'        => 'required|file|mimes:jpg,jpeg,png|mimetypes:image/jpeg,image/png|max:5120',
            'reference_no'   => 'nullable|string|max:100',
            // "tomorrow" on purpose: a borrower in the Philippines (UTC+8) can pick
            // today's date while the server clock is still on the previous day.
            'paid_date'      => 'nullable|date|before_or_equal:tomorrow',
        ], [
            'receipt.required'            => 'Please choose a receipt image.',
            'receipt.file'                => 'The receipt failed to upload. Please try again.',
            'receipt.uploaded'            => 'The receipt failed to upload. Please try again.',
            'receipt.mimes'               => 'Receipt must be a JPG or PNG picture.',
            'receipt.mimetypes'           => 'Receipt must be a JPG or PNG picture.',
            'receipt.max'                 => 'Receipt must be less than 5MB.',
            'paid_date.date'              => 'Please enter a valid paid date.',
            'paid_date.before_or_equal'   => 'Paid date cannot be in the future.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $applicationId = (int) $request->input('application_id');
        $installmentNo = (int) $request->input('installment_no');
        $referenceNo   = trim((string) $request->input('reference_no')) ?: null;
        $paidDate      = $request->input('paid_date') ?: null;

        // The loan must belong to the logged-in borrower.
        $application = DB::table('loan_applications')
            ->where('application_id', $applicationId)
            ->where('user_id', $userId)
            ->first();

        if (!$application) {
            return $this->fail('Loan not found.', 404);
        }

        if (!in_array($application->status, self::UPLOADABLE_LOAN_STATUSES, true)) {
            return $this->fail('Receipts can only be uploaded for an approved or active loan.', 422);
        }

        $newPath = null; // file saved in this request (deleted again if the DB write fails)
        $oldPath = null; // file being replaced (deleted after the DB write succeeds)

        try {
            $result = DB::transaction(function () use ($request, $userId, $applicationId, $installmentNo, $referenceNo, $paidDate, &$newPath, &$oldPath) {
                // Lock the installment row so two uploads can't run at the same time.
                $schedule = DB::table('loan_payment_schedules')
                    ->where('application_id', $applicationId)
                    ->where('installment_no', $installmentNo)
                    ->lockForUpdate()
                    ->first();

                if (!$schedule) {
                    return ['error' => 'Installment not found.', 'code' => 404];
                }

                if (!is_null($schedule->paid_at)) {
                    return ['error' => 'This installment is already paid.', 'code' => 422];
                }

                // When NEXT_UNPAID_ONLY is on, an earlier unpaid installment blocks this one.
                if (self::NEXT_UNPAID_ONLY) {
                    $hasEarlierUnpaid = DB::table('loan_payment_schedules')
                        ->where('application_id', $applicationId)
                        ->where('installment_no', '<', $installmentNo)
                        ->whereNull('paid_at')
                        ->exists();

                    if ($hasEarlierUnpaid) {
                        return ['error' => 'Please settle your earlier installment first.', 'code' => 422];
                    }
                }

                // A receipt that is still waiting for review gets replaced.
                $open = DB::table('payment_receipts')
                    ->where('schedule_id', $schedule->id)
                    ->where('status', 'submitted')
                    ->lockForUpdate()
                    ->first();

                // Save the file. The name is built here, never taken from the user.
                $file = $request->file('receipt');
                $path = 'receipts/' . $applicationId . '/user/' . uniqid() . '.' . $file->extension();

                $stored = Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()));
                if (!$stored) {
                    throw new \RuntimeException('Could not store the receipt file.');
                }
                $newPath = $path;

                $data = [
                    'user_file_path' => $path,
                    'reference_no'   => $referenceNo,
                    'user_paid_date' => $paidDate,
                    'submitted_at'   => now(),
                    'updated_at'     => now(),
                ];

                if ($open) {
                    $oldPath = $open->user_file_path;

                    DB::table('payment_receipts')
                        ->where('receipt_id', $open->receipt_id)
                        ->update($data);

                    $receiptId = $open->receipt_id;
                } else {
                    $receiptId = DB::table('payment_receipts')->insertGetId($data + [
                        'application_id' => $applicationId,
                        'schedule_id'    => $schedule->id,
                        'installment_no' => $installmentNo,
                        'user_id'        => $userId,
                        'status'         => 'submitted',
                        'created_at'     => now(),
                    ], 'receipt_id');
                }

                return ['receipt_id' => $receiptId];
            });
        } catch (\Throwable $e) {
            // Don't leave an orphan file behind.
            if ($newPath) {
                try {
                    Storage::disk(self::DISK)->delete($newPath);
                } catch (\Throwable $ignored) {
                    Log::warning('Could not delete orphan receipt file: ' . $newPath);
                }
            }

            // 23505 = unique violation (two uploads raced past the lock).
            if ($e instanceof QueryException && $e->getCode() === '23505') {
                return $this->fail('A receipt was already submitted for this installment.', 422);
            }

            Log::error('Payment receipt upload failed', ['error' => $e->getMessage()]);
            return $this->fail('Something went wrong while uploading your receipt. Please try again.', 500);
        }

        if (isset($result['error'])) {
            return $this->fail($result['error'], $result['code']);
        }

        // Replace: remove the old file now that the new one is saved.
        if ($oldPath) {
            try {
                Storage::disk(self::DISK)->delete($oldPath);
            } catch (\Throwable $e) {
                Log::warning('Could not delete replaced receipt file: ' . $oldPath);
            }
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Receipt submitted. We\'ll verify it shortly.',
            'receipt_id' => $result['receipt_id'],
        ]);
    }

    /**
     * GET /borrower/payment-receipt/{receipt}/view
     * Redirects to a short-lived signed URL, only for the borrower's own receipt.
     */
    public function view($receiptId)
    {
        $userId = Session::get('user_id');

        $receipt = DB::table('payment_receipts')
            ->where('receipt_id', $receiptId)
            ->where('user_id', $userId)
            ->first();

        if (!$receipt || empty($receipt->user_file_path)) {
            abort(404);
        }

        $url = Storage::disk(self::DISK)->temporaryUrl($receipt->user_file_path, now()->addMinutes(5));

        return redirect()->away($url);
    }
}