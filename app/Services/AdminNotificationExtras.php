<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mga dagdag na notification ng admin (loans, payments, system)
 * na wala sa buildAdminNotifications() ng AdminController.
 *
 * Bawat item: key, title, subtitle, icon, color, url, ts (Carbon), stale (bool).
 * Ang 'read' flag ay nilalagay ng AdminController, hindi dito.
 *
 * - 'stale' = mas matanda sa STALE_DAYS, kaya read na agad (para hindi mag-unread
 *   ang lahat ng lumang history). Ang mga kailangan pang aksyunan (receipt na
 *   hindi pa na-verify, loan na naghihintay ng release) ay hindi kailanman stale.
 * - Naka-cache nang ilang segundo kada admin. Bawat block ay may sariling
 *   try/catch, kaya kung pumalya ang isa, tuloy pa rin ang iba.
 */
class AdminNotificationExtras
{
    private const CACHE_SECONDS = 30;
    private const RECENT_DAYS   = 14; // gaano kalayo ang binabalikan ng mga bagong event
    private const STALE_DAYS    = 7;  // mas matanda dito = read na agad

    public static function build(int $adminId): Collection
    {
        return Cache::remember("admin_notif_extras_{$adminId}", self::CACHE_SECONDS, function () use ($adminId) {
            return self::compute($adminId);
        });
    }

    // Ito ba ang pinakamatagal na overdue na installment ng loan?
    // Pareho ng logic ng overduePaymentDetail: hindi pa bayad at lampas na ang due date,
    // at ang pinakamatagal ang pinipili. Kung pumalya ang query, false ang sagot,
    // kaya sa Ongoing Loan Detail pa rin pupunta.
    private static function isOldestOverdueSchedule($applicationId, $scheduleId): bool
    {
        try {
            $oldestId = DB::table('loan_payment_schedules')
                ->where('application_id', $applicationId)
                ->whereNull('paid_at')
                ->where('due_date', '<', now())
                ->orderBy('due_date')
                ->orderBy('installment_no')
                ->value('id');

            return $oldestId !== null && (int) $oldestId === (int) $scheduleId;
        } catch (\Throwable $e) {
            Log::warning('Admin notification overdue check failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function compute(int $adminId): Collection
    {
        $items = collect();
        $since = now()->subDays(self::RECENT_DAYS);
        $staleBefore = now()->subDays(self::STALE_DAYS);

        $money = fn ($v) => '₱' . number_format((float) $v, 2);
        $code = fn ($uuid) => 'LN-' . strtoupper(substr((string) $uuid, 0, 8));
        $nameOf = function ($first, $last, $fallback = 'Borrower') {
            $n = trim(($first ?? '') . ' ' . ($last ?? ''));
            return $n !== '' ? $n : $fallback;
        };

        $safe = function (string $label, callable $fn) {
            try {
                $fn();
            } catch (\Throwable $e) {
                Log::warning("Admin notification block '{$label}' failed: " . $e->getMessage());
            }
        };

        $push = function (array $n, bool $sticky = false) use ($items, $staleBefore) {
            $n['ts'] = Carbon::parse($n['ts']);
            $n['stale'] = !$sticky && $n['ts']->lt($staleBefore);
            $items->push($n);
        };

        // ================= LOANS =================

        // ---------- Approved na, naghihintay ng Proof of Release ----------
        $safe('waiting_release', function () use ($push, $nameOf, $money, $code) {
            $rows = DB::table('loan_applications as la')
                ->join('users as u', 'la.user_id', '=', 'u.user_id')
                ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
                ->whereIn('la.status', ['approved', 'active'])
                ->whereNull('la.proof_sent_at')
                ->select(
                    'la.application_id', 'la.uuid', 'la.loan_amount', 'la.applied_at',
                    'u.email', 'bpd.first_name', 'bpd.last_name',
                    DB::raw("(SELECT MAX(reviewed_at) FROM loan_approvals
                              WHERE application_id = la.application_id AND decision = 'approved') AS approved_at")
                )
                ->orderByDesc('la.application_id')
                ->limit(15)
                ->get();

            foreach ($rows as $r) {
                $push([
                    'key'      => 'waitrelease:' . $r->application_id,
                    'title'    => 'Approved Loan Waiting for Release',
                    'subtitle' => $nameOf($r->first_name, $r->last_name, $r->email) . ' · ' . $code($r->uuid) . ' · ' . $money($r->loan_amount),
                    'icon'     => 'feather-send',
                    'color'    => 'primary',
                    'url'      => route('admin.waiting-for-release-detail', $r->uuid),
                    'ts'       => $r->approved_at ?? $r->applied_at,
                ], true);
            }
        });

        // ---------- Loan Released ----------
        $safe('released', function () use ($push, $nameOf, $money, $code, $since) {
            $rows = DB::table('loan_applications as la')
                ->join('users as u', 'la.user_id', '=', 'u.user_id')
                ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
                ->whereIn('la.status', ['approved', 'active'])
                ->whereNotNull('la.proof_sent_at')
                ->where('la.proof_sent_at', '>=', $since)
                ->select('la.application_id', 'la.uuid', 'la.loan_amount', 'la.proof_sent_at',
                    'u.email', 'bpd.first_name', 'bpd.last_name')
                ->orderByDesc('la.proof_sent_at')
                ->limit(15)
                ->get();

            foreach ($rows as $r) {
                $push([
                    'key'      => 'released:' . $r->application_id,
                    'title'    => 'Loan Released',
                    'subtitle' => $nameOf($r->first_name, $r->last_name, $r->email) . ' · ' . $code($r->uuid) . ' · ' . $money($r->loan_amount),
                    'icon'     => 'feather-check-circle',
                    'color'    => 'success',
                    'url'      => route('admin.ongoing-loan-detail', $r->uuid) . '?from=ongoing-loans',
                    'ts'       => $r->proof_sent_at,
                ]);
            }
        });

        // ---------- Loan Fully Paid ----------
        $safe('completed', function () use ($push, $nameOf, $code, $since) {
            $rows = DB::table('loan_applications as la')
                ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
                ->where('la.status', 'completed')
                ->select(
                    'la.application_id', 'la.uuid', 'la.applied_at', 'bpd.first_name', 'bpd.last_name',
                    DB::raw('(SELECT MAX(paid_at) FROM loan_payment_schedules
                              WHERE application_id = la.application_id) AS finished_at')
                )
                ->orderByDesc('finished_at')
                ->limit(30)
                ->get();

            foreach ($rows as $r) {
                if (empty($r->finished_at) || Carbon::parse($r->finished_at)->lt($since)) {
                    continue;
                }

                $push([
                    'key'      => 'completed:' . $r->application_id,
                    'title'    => 'Loan Fully Paid',
                    'subtitle' => $nameOf($r->first_name, $r->last_name) . ' · ' . $code($r->uuid),
                    'icon'     => 'feather-award',
                    'color'    => 'success',
                    'url'      => route('admin.completed-loan-detail', $r->uuid),
                    'ts'       => $r->finished_at,
                ]);
            }
        });

        // ---------- Loan Defaulted ----------
        $safe('defaulted', function () use ($push, $nameOf, $code, $since) {
            $rows = DB::table('loan_applications as la')
                ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
                ->where('la.status', 'defaulted')
                ->select('la.application_id', 'la.uuid', 'la.applied_at', 'la.updated_at',
                    'bpd.first_name', 'bpd.last_name')
                ->orderByDesc('la.updated_at')
                ->limit(10)
                ->get();

            foreach ($rows as $r) {
                $at = $r->updated_at ?? $r->applied_at;
                if (empty($at) || Carbon::parse($at)->lt($since)) {
                    continue;
                }

                $push([
                    'key'      => 'defaulted:' . $r->application_id,
                    'title'    => 'Loan Marked as Defaulted',
                    'subtitle' => $nameOf($r->first_name, $r->last_name) . ' · ' . $code($r->uuid),
                    'icon'     => 'feather-alert-octagon',
                    'color'    => 'danger',
                    'url'      => route('admin.defaulted-loan-detail', $r->application_id),
                    'ts'       => $at,
                ]);
            }
        });

        // ================= PAYMENTS =================

        // ---------- Receipt na hinihintay ang verification ----------
        $safe('receipts', function () use ($push, $nameOf) {
            $rows = DB::table('payment_receipts as pr')
                ->join('loan_applications as la', 'la.application_id', '=', 'pr.application_id')
                ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
                ->where('pr.status', 'submitted')
                ->select('pr.receipt_id', 'pr.application_id', 'pr.schedule_id', 'pr.installment_no', 'pr.submitted_at',
                    'la.uuid', 'bpd.first_name', 'bpd.last_name')
                ->orderByDesc('pr.submitted_at')
                ->limit(20)
                ->get();

            foreach ($rows as $r) {
                if (empty($r->submitted_at)) {
                    continue;
                }

                // Default: Ongoing Loan Detail. Kung ito ang pinakamatagal na overdue na installment,
                // Overdue Payment Detail (yun lang ang receipt na lumalabas sa page na yun).
                $url = route('admin.ongoing-loan-detail', $r->uuid) . '?from=ongoing-loans';

                if (self::isOldestOverdueSchedule($r->application_id, $r->schedule_id)) {
                    try {
                        $url = route('admin.overdue-payment-detail', $r->uuid);
                    } catch (\Throwable $e) {
                        Log::warning("Admin notification: overdue detail route failed: " . $e->getMessage());
                    }
                }

                $push([
                    'key'      => 'receipt:' . $r->receipt_id,
                    'title'    => 'Payment Receipt to Verify',
                    'subtitle' => $nameOf($r->first_name, $r->last_name) . ' · Installment #' . $r->installment_no,
                    'icon'     => 'feather-upload-cloud',
                    'color'    => 'primary',
                    'url'      => $url,
                    'ts'       => $r->submitted_at,
                ], true);
            }
        });

        // ---------- Penalty Charged ----------
        $safe('penalties', function () use ($push, $nameOf, $money, $since) {
            $rows = DB::table('loan_penalties as lp')
                ->join('loan_applications as la', 'la.application_id', '=', 'lp.application_id')
                ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
                ->where('lp.status', 'unpaid')
                ->where('lp.created_at', '>=', $since)
                ->select('lp.*', 'la.uuid as loan_uuid', 'bpd.first_name', 'bpd.last_name')
                ->orderByDesc('lp.created_at')
                ->limit(15)
                ->get();

            foreach ($rows as $p) {
                $inst = $p->installment_no ?? null;

                $push([
                    'key'      => 'penalty:' . $p->id,
                    'title'    => 'Penalty Charged',
                    'subtitle' => $nameOf($p->first_name, $p->last_name) . ' · ' . $money($p->penalty_amount)
                        . ($inst ? ' · Installment #' . $inst : ''),
                    'icon'     => 'feather-alert-circle',
                    'color'    => 'warning',
                    'url'      => $inst
                        ? route('admin.penalty-tracking-detail', [$p->loan_uuid, $inst])
                        : route('admin.penalty-tracking'),
                    'ts'       => $p->created_at,
                ]);
            }
        });

        // ================= SYSTEM =================

        // ---------- Welcome ----------
        $safe('welcome', function () use ($push, $adminId) {
            $createdAt = DB::table('users')->where('user_id', $adminId)->value('created_at');

            if ($createdAt) {
                $push([
                    'key'      => 'welcome',
                    'title'    => 'Welcome to RAN Serenity',
                    'subtitle' => 'Your admin account is ready.',
                    'icon'     => 'feather-smile',
                    'color'    => 'primary',
                    'url'      => route('admin.dashboard'),
                    'ts'       => $createdAt,
                ]);
            }
        });

        return $items->values();
    }
}