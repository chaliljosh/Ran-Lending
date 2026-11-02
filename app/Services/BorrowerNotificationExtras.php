<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mga dagdag na notification ng borrower (galing sa admin at sa system)
 * na wala sa borrower_common_data RPC.
 *
 * Bawat item: key, url, icon, color, title, subtitle, ts (Carbon), date_only.
 * Ang 'read' flag ay nilalagay ng BorrowerController, hindi dito.
 *
 * Naka-cache nang ilang segundo kada user para hindi madagdagan ang queries
 * sa bawat page load. Bawat block ay may sariling try/catch, kaya kung
 * pumalya ang isa, tuloy pa rin ang iba.
 */
class BorrowerNotificationExtras
{
    private const CACHE_SECONDS = 30;

    public static function build(int $userId): Collection
    {
        return Cache::remember("borrower_notif_extras_{$userId}", self::CACHE_SECONDS, function () use ($userId) {
            return self::compute($userId);
        });
    }

    private static function compute(int $userId): Collection
    {
        $items = collect();
        $money = fn ($v) => '₱' . number_format((float) $v, 2);

        $safe = function (string $label, callable $fn) {
            try {
                $fn();
            } catch (\Throwable $e) {
                Log::warning("Borrower notification block '{$label}' failed: " . $e->getMessage());
            }
        };

        $push = function (array $n) use ($items) {
            $items->push($n + ['date_only' => false]);
        };

        // ---------- Loans ng borrower ----------
        $loans = collect();
        $safe('loans', function () use (&$loans, $userId) {
            $loans = DB::table('loan_applications')
                ->where('user_id', $userId)
                ->orderByDesc('applied_at')
                ->limit(10)
                ->get();
        });

        $loanIds = $loans->pluck('application_id')->all();

        // ---------- Application Submitted / Loan Released ----------
        $safe('lifecycle', function () use ($loans, $push, $money) {
            foreach ($loans as $loan) {
                $url = route('borrower.loan-details', ['loan' => $loan->application_id]);

                if (!empty($loan->applied_at)) {
                    $push([
                        'key'      => 'submitted:' . $loan->application_id,
                        'url'      => $url,
                        'icon'     => 'feather-file-text',
                        'color'    => 'primary',
                        'title'    => 'Application Submitted',
                        'subtitle' => 'We received your loan application of ' . $money($loan->loan_amount) . '. Our team is reviewing it.',
                        'ts'       => Carbon::parse($loan->applied_at),
                    ]);
                }

                if (!empty($loan->proof_sent_at)) {
                    $push([
                        'key'      => 'released:' . $loan->application_id,
                        'url'      => $url,
                        'icon'     => 'feather-send',
                        'color'    => 'success',
                        'title'    => 'Loan Released',
                        'subtitle' => 'Your loan of ' . $money($loan->loan_amount) . ' has been released. Your payment schedule is now available.',
                        'ts'       => Carbon::parse($loan->proof_sent_at),
                    ]);
                }
            }
        });

        // ---------- On Hold (binabasa ang huling desisyon ng admin) ----------
        $safe('on_hold', function () use ($loans, $push) {
            $holdLoans = $loans->where('status', 'on_hold');
            if ($holdLoans->isEmpty()) {
                return;
            }

            $holdAt = DB::table('loan_approvals')
                ->whereIn('application_id', $holdLoans->pluck('application_id')->all())
                ->groupBy('application_id')
                ->selectRaw('application_id, MAX(reviewed_at) as at')
                ->pluck('at', 'application_id');

            foreach ($holdLoans as $loan) {
                $at = $holdAt[$loan->application_id] ?? $loan->applied_at;

                $push([
                    'key'      => 'onhold:' . $loan->application_id,
                    'url'      => route('borrower.loan-details', ['loan' => $loan->application_id]),
                    'icon'     => 'feather-pause-circle',
                    'color'    => 'warning',
                    'title'    => 'Application Placed On Hold',
                    'subtitle' => 'Our team will contact you to discuss your application.',
                    'ts'       => Carbon::parse($at),
                ]);
            }
        });

        // ---------- Loan Fully Paid ----------
        $safe('completed', function () use ($loans, $push, $money) {
            $done = $loans->where('status', 'completed');
            if ($done->isEmpty()) {
                return;
            }

            $paidAt = DB::table('loan_payment_schedules')
                ->whereIn('application_id', $done->pluck('application_id')->all())
                ->groupBy('application_id')
                ->selectRaw('application_id, MAX(paid_at) as at')
                ->pluck('at', 'application_id');

            foreach ($done as $loan) {
                $at = $paidAt[$loan->application_id] ?? $loan->applied_at;

                $push([
                    'key'      => 'completed:' . $loan->application_id,
                    'url'      => route('borrower.loan-details', ['loan' => $loan->application_id]),
                    'icon'     => 'feather-award',
                    'color'    => 'success',
                    'title'    => 'Loan Fully Paid',
                    'subtitle' => 'Congratulations! Your loan of ' . $money($loan->loan_amount) . ' is fully paid. You can now apply for a new loan.',
                    'ts'       => Carbon::parse($at),
                ]);
            }
        });

        // ---------- Payment Method Assigned (galing sa admin) ----------
        $safe('payment_method', function () use ($loans, $push) {
            $assigned = $loans->filter(
                fn ($l) => !empty($l->payment_method_id) && in_array($l->status, ['approved', 'active'], true)
            );
            if ($assigned->isEmpty()) {
                return;
            }

            $methods = DB::table('payment_methods')
                ->whereIn('id', $assigned->pluck('payment_method_id')->unique()->all())
                ->get()
                ->keyBy('id');

            foreach ($assigned as $loan) {
                $method = $methods->get($loan->payment_method_id);
                if (!$method) {
                    continue;
                }

                $ts = $loan->updated_at ?? $loan->proof_sent_at ?? $loan->applied_at;

                $push([
                    'key'      => 'pm:' . $loan->application_id . ':' . $loan->payment_method_id,
                    'url'      => route('borrower.loan-details', ['loan' => $loan->application_id]),
                    'icon'     => 'feather-credit-card',
                    'color'    => 'primary',
                    'title'    => 'Payment Method Assigned',
                    'subtitle' => 'Send your payments via ' . $method->provider . '. The account details are in your loan details.',
                    'ts'       => Carbon::parse($ts),
                ]);
            }
        });

        // ---------- Receipt Received (hinihintay ang verification ng admin) ----------
        $safe('receipts', function () use ($loanIds, $push) {
            if (!$loanIds) {
                return;
            }

            $rows = DB::table('payment_receipts')
                ->whereIn('application_id', $loanIds)
                ->where('status', 'submitted')
                ->orderByDesc('submitted_at')
                ->limit(10)
                ->get(['receipt_id', 'application_id', 'installment_no', 'submitted_at']);

            foreach ($rows as $r) {
                if (empty($r->submitted_at)) {
                    continue;
                }

                $push([
                    'key'      => 'receipt_submitted:' . $r->receipt_id,
                    'url'      => route('borrower.loan-details', ['loan' => $r->application_id]),
                    'icon'     => 'feather-upload-cloud',
                    'color'    => 'primary',
                    'title'    => 'Receipt Received',
                    'subtitle' => 'Your proof of payment for installment #' . $r->installment_no . ' is waiting for verification.',
                    'ts'       => Carbon::parse($r->submitted_at),
                ]);
            }
        });

        // ---------- Penalty Waived (galing sa admin) ----------
        $safe('penalty_waived', function () use ($loanIds, $push, $money) {
            if (!$loanIds) {
                return;
            }

            $rows = DB::table('loan_penalties')
                ->whereIn('application_id', $loanIds)
                ->where('status', 'waived')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get(['id', 'application_id', 'penalty_amount', 'updated_at']);

            foreach ($rows as $p) {
                if (empty($p->updated_at)) {
                    continue;
                }

                $push([
                    'key'      => 'penalty_waived:' . $p->id,
                    'url'      => route('borrower.loan-details', ['loan' => $p->application_id]),
                    'icon'     => 'feather-percent',
                    'color'    => 'success',
                    'title'    => 'Penalty Waived',
                    'subtitle' => 'An admin waived your penalty of ' . $money($p->penalty_amount) . '.',
                    'ts'       => Carbon::parse($p->updated_at),
                ]);
            }
        });

        // ---------- Your Information Was Updated (galing sa admin) ----------
        $safe('info_edits', function () use ($userId, $push) {
            $rows = DB::table('borrower_info_edits')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->limit(40)
                ->get(['batch_id', 'field_label', 'reason', 'created_at']);

            foreach ($rows->groupBy('batch_id') as $batchId => $group) {
                $first = $group->first();
                $labels = $group->pluck('field_label')->filter()->unique()->values()->all();

                $text = 'An admin updated: ' . implode(', ', array_slice($labels, 0, 3))
                    . (count($labels) > 3 ? ' and ' . (count($labels) - 3) . ' more' : '') . '.';

                if (!empty($first->reason)) {
                    $text .= ' Reason: ' . $first->reason;
                }

                $push([
                    'key'      => 'info_edit:' . $batchId,
                    'url'      => route('borrower.profile'),
                    'icon'     => 'feather-edit-3',
                    'color'    => 'primary',
                    'title'    => 'Your Information Was Updated',
                    'subtitle' => Str::limit($text, 120),
                    'ts'       => Carbon::parse($first->created_at),
                ]);
            }
        });

        // ---------- Welcome (system) ----------
        $safe('welcome', function () use ($userId, $push) {
            $createdAt = DB::table('users')->where('user_id', $userId)->value('created_at');

            if ($createdAt) {
                $push([
                    'key'      => 'welcome',
                    'url'      => route('borrower.dashboard'),
                    'icon'     => 'feather-smile',
                    'color'    => 'primary',
                    'title'    => 'Welcome to RAN Serenity',
                    'subtitle' => 'Your account is ready. Together towards your goals.',
                    'ts'       => Carbon::parse($createdAt),
                ]);
            }
        });

        return $items->values();
    }
}