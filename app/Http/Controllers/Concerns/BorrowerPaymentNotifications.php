<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mga notification ng borrower na galing sa admin:
 *   1. Na-reject ang inupload na receipt (kasama ang reason)
 *   2. Na-record ang partial payment (kulang pa ang bayad)
 *
 * Hiwalay na file dahil ginagamit ito ng header dropdown (sa bawat page)
 * at ng Notifications page. Isang lugar lang para baguhin.
 *
 * Gamitin sa BorrowerController:  use BorrowerPaymentNotifications;
 */
trait BorrowerPaymentNotifications
{
    /**
     * Para sa header dropdown. Parehong format ng ibang items sa
     * buildBorrowerNotificationsFromData(): key, url, icon, color, title, subtitle, read, ts
     *
     * @param  int         $userId
     * @param  mixed|null  $readKeys  collection ng read keys na naka-flip (->flip()), o null
     */
    protected function buildPaymentUpdateNotifications($userId, $readKeys = null)
    {
        $items = collect();
        $readKeys = $readKeys ?: collect();
        $since = now()->subDays(30);

        // ---- 1. Na-reject na receipt ----
        try {
            $rejected = DB::table('payment_receipts')
                ->where('user_id', $userId)
                ->where('status', 'rejected')
                ->whereNotNull('reviewed_at')
                ->where('reviewed_at', '>=', $since)
                ->select('receipt_id', 'application_id', 'installment_no', 'admin_remarks', 'reviewed_at')
                ->orderByDesc('reviewed_at')
                ->limit(10)
                ->get();

            foreach ($rejected as $r) {
                $key = 'receipt_rejected:' . $r->receipt_id;
                $reason = trim((string) $r->admin_remarks);

                $items->push([
                    'key'      => $key,
                    'url'      => route('borrower.loan-details', ['loan' => $r->application_id]),
                    'icon'     => 'feather-x-circle',
                    'color'    => 'danger',
                    'title'    => 'Receipt Rejected',
                    'subtitle' => 'Your receipt for installment #' . $r->installment_no . ' was rejected.'
                        . ($reason !== '' ? ' Reason: ' . Str::limit($reason, 80) : ' Please upload a clearer receipt.'),
                    'read'     => $readKeys->has($key),
                    'ts'       => Carbon::parse($r->reviewed_at),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Rejected receipt notifications failed', ['error' => $e->getMessage()]);
        }

        // ---- 2. Partial payment na na-record ----
        try {
            $partials = DB::table('installment_payments as ip')
                ->join('loan_applications as la', 'la.application_id', '=', 'ip.application_id')
                ->join('loan_payment_schedules as sch', 'sch.id', '=', 'ip.schedule_id')
                ->where('la.user_id', $userId)
                ->where('ip.kind', 'partial')
                ->whereNull('ip.voided_at')
                ->where('ip.created_at', '>=', $since)
                ->select(
                    'ip.id', 'ip.application_id', 'ip.amount', 'ip.created_at',
                    'sch.installment_no', 'sch.amount_due', 'sch.amount_paid', 'sch.paid_at'
                )
                ->orderByDesc('ip.created_at')
                ->limit(10)
                ->get();

            foreach ($partials as $p) {
                $key = 'partial:' . $p->id;
                $text = 'We recorded ₱' . number_format((float) $p->amount, 2)
                    . ' for installment #' . $p->installment_no . '.';

                // Ipakita lang ang natitirang balance kung hindi pa fully paid ang installment
                if (is_null($p->paid_at)) {
                    $balance = max(0, (float) $p->amount_due - (float) $p->amount_paid);
                    $text .= ' Remaining balance: ₱' . number_format($balance, 2) . '.';
                }

                $items->push([
                    'key'      => $key,
                    'url'      => route('borrower.loan-details', ['loan' => $p->application_id]),
                    'icon'     => 'feather-pie-chart',
                    'color'    => 'primary',
                    'title'    => 'Partial Payment Recorded',
                    'subtitle' => $text,
                    'read'     => $readKeys->has($key),
                    'ts'       => Carbon::parse($p->created_at),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Partial payment notifications failed', ['error' => $e->getMessage()]);
        }

        return $items;
    }

    /**
     * Para sa Notifications page. Parehong data, pero format ng alert cards:
     * type, icon, title, message, sort
     */
    protected function paymentUpdateNotificationsForPage($userId): array
    {
        return $this->buildPaymentUpdateNotifications($userId)
            ->sortByDesc('ts')
            ->map(fn ($n) => [
                'type'    => $n['color'] === 'danger' ? 'danger' : 'info',
                'icon'    => $n['icon'],
                'title'   => $n['title'],
                'message' => $n['subtitle'],
                'sort'    => -1000, // nasa ibabaw ng due date reminders
            ])
            ->values()
            ->all();
    }
}