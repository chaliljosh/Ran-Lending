<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Services\PenaltyHelper;
use App\Services\LoanActivationService;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $adminId = Session::get('user_id');

        $pendingVerificationCount = DB::table('borrowerpersonaldetails')->where('status', 'pending')->count();

        $totalApplications = DB::table('loan_applications')->count();
        $pendingApplications = DB::table('loan_applications')->where('status', 'pending')->count();
        $activeLoans = DB::table('loan_applications')->where('status', 'active')->count();
        $totalActiveAmount = DB::table('loan_applications')->where('status', 'active')->sum('total_amount');
        $totalBorrowers = DB::table('users')->where('role', 'borrower')->count();
        $activeBorrowers = DB::table('users')->where('role', 'borrower')->where('is_active', true)->count();

        $recentApplications = DB::table('loan_applications as la')
            ->join('users as u', 'la.user_id', '=', 'u.user_id')
            ->select('la.*', 'u.name as borrower_name')
            ->orderByDesc('la.applied_at')
            ->limit(5)
            ->get();

        // Sidebar's Borrower Verification dropdown badge — same helper used
        // by verificationDetail()/loanApplicationDetail() for the same reason.
        $pendingCount = $this->getPendingVerificationCount($adminId);
        $pendingLoanCount = $this->getPendingLoanCount($adminId);

        // -----------------------------------------------------------------
        // Attention Required — built purely from counts already computable
        // from existing tables. Items only appear when their count is > 0,
        // per the "no fake alerts" requirement.
        // -----------------------------------------------------------------
        $overdueApplicationsCount = DB::table('loan_payment_schedules as sch')
            ->join('loan_applications as la', 'sch.application_id', '=', 'la.application_id')
            ->where('la.status', 'active')
            ->whereNull('sch.paid_at')
            ->where('sch.due_date', '<', now())
            ->distinct()
            ->count('la.application_id');

        // Always shown as a fixed 3-item list (Overdue / Pending Applications /
        // Pending Verification), counts included even when 0 — counts still
        // come straight from the DB queries above, never hardcoded.
        $attentionItems = [
            [
                'icon' => 'feather-alert-triangle',
                'color' => 'danger',
                'label' => 'Overdue Payments',
                'count' => $overdueApplicationsCount,
                'action' => 'View',
                'url' => route('admin.overdue-payments'),
            ],
            [
                'icon' => 'feather-file-text',
                'color' => 'primary',
                'label' => 'Pending Applications',
                'count' => $pendingApplications,
                'action' => 'Review',
                'url' => route('admin.loan-applications'),
            ],
            [
                'icon' => 'feather-user-check',
                'color' => 'warning',
                'label' => 'Pending Verification',
                'count' => $pendingVerificationCount,
                'action' => 'Review',
                'url' => route('admin.borrower-verification'),
            ],
        ];

        // -----------------------------------------------------------------
        // Upcoming Payments — next unpaid installments on active loans,
        // due today or later, soonest first.
        // -----------------------------------------------------------------
        $upcomingPaymentsRaw = DB::table('loan_payment_schedules as sch')
            ->join('loan_applications as la', 'sch.application_id', '=', 'la.application_id')
            ->join('users as u', 'la.user_id', '=', 'u.user_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('la.status', 'active')
            ->whereNull('sch.paid_at')
            ->where('sch.due_date', '>=', now()->startOfDay())
            ->select('sch.due_date', 'sch.amount_due', 'u.name as fallback_name', 'bpd.first_name', 'bpd.last_name')
            ->orderBy('sch.due_date', 'asc')
            ->limit(5)
            ->get();

        $upcomingPayments = $upcomingPaymentsRaw->map(function ($row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $row->borrower_name = $name !== '' ? $name : $row->fallback_name;
            return $row;
        });

        // -----------------------------------------------------------------
        // Recent Activity — merged from real admin actions already recorded
        // in the system (loan approvals, borrower verification decisions,
        // waived penalties, paid penalties), sorted by most recent.
        // -----------------------------------------------------------------
        $recentActivity = $this->buildRecentActivity(5);

        // -----------------------------------------------------------------
        // Sidebar admin profile — logged-in admin's actual name/role.
        // -----------------------------------------------------------------
        $admin = DB::table('users')->where('user_id', $adminId)->first();
        $adminName = $admin->name ?? 'Admin';
        $adminRole = $admin && !empty($admin->role) ? ucfirst($admin->role) : 'Administrator';
        $adminInitials = $this->getInitials($adminName);

        return view('admin.dashboard', compact(
            'pendingVerificationCount', 'totalApplications', 'pendingApplications',
            'activeLoans', 'totalActiveAmount', 'totalBorrowers', 'activeBorrowers',
            'recentApplications', 'pendingCount', 'pendingLoanCount',
            'attentionItems', 'upcomingPayments', 'recentActivity',
            'adminName', 'adminRole', 'adminInitials'
        ));
    }

    /**
     * Recent Activity feed — pulls the most recent rows from every table
     * that already records "who did what, when":
     *   - loan_approvals      -> loan application approved/rejected
     *   - detailsapproval      -> borrower verification approved/rejected
     *   - loan_penalties       -> penalty waived (has waived_by, so the
     *                             admin can be named) or marked paid (no
     *                             admin column exists for this action in
     *                             the current schema, so it's shown without
     *                             an admin name rather than guessing one)
     * All sources are merged and sorted by timestamp, newest first.
     */
    private function buildRecentActivity($limit = 5)
    {
        $activities = collect();

        $loanDecisions = DB::table('loan_approvals as lap')
            ->join('users as admin', 'lap.admin_id', '=', 'admin.user_id')
            ->join('loan_applications as la', 'lap.application_id', '=', 'la.application_id')
            ->join('users as bu', 'la.user_id', '=', 'bu.user_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->select('lap.decision', 'lap.reviewed_at as ts', 'admin.name as admin_name', 'bpd.first_name', 'bpd.last_name', 'bu.name as borrower_fallback')
            ->orderByDesc('lap.reviewed_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $borrower = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) ?: $row->borrower_fallback;
                $approved = $row->decision === 'approved';
                return [
                    'icon' => $approved ? 'feather-check-circle' : 'feather-x-circle',
                    'color' => $approved ? 'success' : 'danger',
                    'text' => ($row->admin_name ?? 'Admin') . ' ' . ($approved ? 'approved a loan application' : 'rejected a loan application'),
                    'meta' => $borrower,
                    'ts' => $row->ts,
                ];
            });

        $verifyDecisions = DB::table('detailsapproval as da')
            ->join('users as admin', 'da.admin_id', '=', 'admin.user_id')
            ->join('borrowerpersonaldetails as bpd', 'da.detail_id', '=', 'bpd.detail_id')
            ->select('da.decision', 'da.reviewed_at as ts', 'admin.name as admin_name', 'bpd.first_name', 'bpd.last_name')
            ->orderByDesc('da.reviewed_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $borrower = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                $approved = $row->decision === 'approved';
                return [
                    'icon' => 'feather-user-check',
                    'color' => $approved ? 'success' : 'danger',
                    'text' => ($row->admin_name ?? 'Admin') . ' ' . ($approved ? 'verified a borrower' : 'rejected a borrower verification'),
                    'meta' => $borrower,
                    'ts' => $row->ts,
                ];
            });

        $waivedPenalties = DB::table('loan_penalties as lp')
            ->join('users as admin', 'lp.waived_by', '=', 'admin.user_id')
            ->join('loan_applications as la', 'lp.application_id', '=', 'la.application_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('lp.status', 'waived')
            ->select('lp.updated_at as ts', 'admin.name as admin_name', 'bpd.first_name', 'bpd.last_name')
            ->orderByDesc('lp.updated_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $borrower = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                return [
                    'icon' => 'feather-slash',
                    'color' => 'secondary',
                    'text' => ($row->admin_name ?? 'Admin') . ' waived a penalty',
                    'meta' => $borrower,
                    'ts' => $row->ts,
                ];
            });

        $paidPenalties = DB::table('loan_penalties as lp')
            ->join('loan_applications as la', 'lp.application_id', '=', 'la.application_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('lp.status', 'paid')
            ->select('lp.updated_at as ts', 'bpd.first_name', 'bpd.last_name')
            ->orderByDesc('lp.updated_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $borrower = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                return [
                    'icon' => 'feather-check-circle',
                    'color' => 'success',
                    'text' => 'Penalty marked as Paid',
                    'meta' => $borrower,
                    'ts' => $row->ts,
                ];
            });

        return $activities
            ->concat($loanDecisions)
            ->concat($verifyDecisions)
            ->concat($waivedPenalties)
            ->concat($paidPenalties)
            ->filter(fn ($a) => !empty($a['ts']))
            ->sortByDesc('ts')
            ->values()
            ->take($limit);
    }

    /**
     * "Chalil Joshua David" -> "CD", "Admin" -> "A". Used for the sidebar
     * profile avatar initials.
     */
    private function getInitials($name)
    {
        $parts = preg_split('/\s+/', trim((string) $name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            if ($p !== '') {
                $initials .= strtoupper(substr($p, 0, 1));
            }
        }
        return $initials !== '' ? $initials : 'A';
    }

    // Simpleng listahan ng lahat ng borrowers — pangalan, email, status.
    // Walang approve/reject dito, view-only page lang.
    public function allBorrowers(Request $request)
    {
        $adminId = Session::get('user_id');
        $sort = $request->query('sort', 'newest');
 
        $rows = DB::table('users as u')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'u.user_id')
            ->where('u.role', 'borrower')
            ->select(
                'u.user_id', 'u.email', 'u.is_active', 'u.created_at',
                'bpd.detail_id', 'bpd.first_name', 'bpd.last_name'
            )
            ->orderBy('u.created_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->get();
 
        $borrowers = [];
        $stats = ['total' => 0, 'active_loan' => 0, 'no_loan' => 0, 'suspended' => 0];
 
        foreach ($rows as $row) {
            $loans = DB::table('loan_applications')
                ->where('user_id', $row->user_id)
                ->orderByDesc('applied_at')
                ->get();
 
            $latest = $loans->first();
            $totalLoans = $loans->count();
 
            if (!$row->is_active) {
                $loanStatus = 'suspended';
            } elseif (!$latest) {
                $loanStatus = 'no_loan';
            } elseif (in_array($latest->status, ['pending', 'on_hold'], true)) {
                $loanStatus = 'pending';
            } elseif (in_array($latest->status, ['approved', 'active'], true)) {
                $loanStatus = 'active_loan';
            } elseif ($latest->status === 'completed') {
                $loanStatus = 'completed';
            } else {
                // rejected / defaulted — walang "active" na loan, ituturing
                // na parang No Loan para sa displaying purposes.
                $loanStatus = 'no_loan';
            }
 
            $fullName = trim($row->first_name . ' ' . $row->last_name);
            $displayName = $fullName !== '' ? $fullName : $row->email;
            $initials = $fullName !== ''
                ? strtoupper(substr($row->first_name, 0, 1) . substr($row->last_name, 0, 1))
                : strtoupper(substr($row->email, 0, 2));
 
            $borrowers[] = [
                'user_id' => $row->user_id,
                'borrower_id' => 'BRW-' . str_pad($row->user_id, 4, '0', STR_PAD_LEFT),
                'detail_id' => $row->detail_id,
                'name' => $displayName,
                'initials' => $initials,
                'email' => $row->email,
                'is_active' => (bool) $row->is_active,
                'loan_status' => $loanStatus,
                'total_loans' => $totalLoans,
                'last_activity' => $latest->applied_at ?? $row->created_at,
                'has_activity' => (bool) $latest,
                'joined_at' => $row->created_at,
            ];
 
            $stats['total']++;
            if ($loanStatus === 'active_loan') $stats['active_loan']++;
            if ($loanStatus === 'no_loan') $stats['no_loan']++;
            if (!$row->is_active) $stats['suspended']++;
        }
 
        return view('admin.borrowers', [
            'borrowers' => $borrowers,
            'stats' => $stats,
            'sort' => $sort,
            'pendingCount' => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }

    public function activeBorrowers(Request $request)
{
    $adminId = Session::get('user_id');
    $sort = $request->query('sort', 'newest');
 
    $rows = DB::table('loan_applications as la')
        ->join('users as u', 'la.user_id', '=', 'u.user_id')
        ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
        ->whereIn('la.status', ['approved', 'active'])
        ->select(
            'la.application_id', 'la.loan_amount', 'la.loan_term', 'la.loan_purpose',
            'la.total_amount', 'la.status', 'la.applied_at',
            'u.user_id', 'u.email',
            'bpd.detail_id', 'bpd.first_name', 'bpd.last_name'
        )
        ->orderBy('la.applied_at', $sort === 'oldest' ? 'asc' : 'desc')
        ->get();
 
    $activeBorrowers = [];
    foreach ($rows as $row) {
        $fullName = trim($row->first_name . ' ' . $row->last_name);
        $displayName = $fullName !== '' ? $fullName : $row->email;
        $initials = $fullName !== ''
            ? strtoupper(substr($row->first_name, 0, 1) . substr($row->last_name, 0, 1))
            : strtoupper(substr($row->email, 0, 2));
 
        $activeBorrowers[] = [
            'application_id' => $row->application_id,
            'user_id'        => $row->user_id,
            'borrower_id'    => 'BRW-' . str_pad($row->user_id, 4, '0', STR_PAD_LEFT),
            'detail_id'      => $row->detail_id,
            'name'           => $displayName,
            'initials'       => $initials,
            'email'          => $row->email,
            'loan_amount'    => $row->loan_amount,
            'loan_term'      => $row->loan_term,
            'loan_purpose'   => $row->loan_purpose,
            'total_amount'   => $row->total_amount,
            'applied_at'     => $row->applied_at,
        ];
    }
 
    $stats = [
        'total'        => count($activeBorrowers),
        'total_amount' => collect($activeBorrowers)->sum('total_amount'),
        'avg_amount'   => count($activeBorrowers) ? collect($activeBorrowers)->avg('loan_amount') : 0,
    ];
 
    return view('admin.active-borrowers', [
        'activeBorrowers' => $activeBorrowers,
        'stats'           => $stats,
        'sort'            => $sort,
        'pendingCount'     => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
    ]);
}

    /**
     * Active Loans > Ongoing Loans — mga loan na status = 'active' AT walang
     * overdue unpaid installment (paid_at null + due_date < today). Yung mga
     * may overdue installment, sa "Overdue Payments" tab na lang lalabas
     * (hiwalay na page/method mamaya).
     */
    public function ongoingLoans(Request $request)
    {
        $adminId = Session::get('user_id');
        $sort = $request->query('sort', 'newest');

        $apps = DB::table('loan_applications as la')
            ->join('users as u', 'la.user_id', '=', 'u.user_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('la.status', 'active')
            ->select(
                'la.application_id', 'la.loan_amount', 'la.loan_term', 'la.loan_purpose',
                'la.total_amount', 'la.applied_at',
                'u.user_id', 'u.email',
                'bpd.detail_id', 'bpd.first_name', 'bpd.last_name'
            )
            ->orderBy('la.applied_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->get();

        $ongoingLoans = [];
        $stats = ['total' => 0, 'total_amount' => 0, 'total_collected' => 0];

        foreach ($apps as $row) {
            $schedules = DB::table('loan_payment_schedules')
                ->where('application_id', $row->application_id)
                ->orderBy('installment_no')
                ->get();

            if ($schedules->isEmpty()) continue;

            $hasOverdue = $schedules->contains(function ($s) {
                return is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
            });

            // Kung may overdue installment, hindi na ito "ongoing" — sa
            // Overdue Payments tab na lang lalabas ito.
            if ($hasOverdue) continue;

            $paidCount = $schedules->filter(fn($s) => !is_null($s->paid_at))->count();
            $totalCount = $schedules->count();
            $nextUnpaid = $schedules->first(fn($s) => is_null($s->paid_at));
            $amountCollected = $schedules->sum('amount_paid');

            $fullName = trim($row->first_name . ' ' . $row->last_name);
            $displayName = $fullName !== '' ? $fullName : $row->email;
            $initials = $fullName !== ''
                ? strtoupper(substr($row->first_name, 0, 1) . substr($row->last_name, 0, 1))
                : strtoupper(substr($row->email, 0, 2));

            $ongoingLoans[] = [
                'application_id'   => $row->application_id,
                'user_id'          => $row->user_id,
                'borrower_id'      => 'BRW-' . str_pad($row->user_id, 4, '0', STR_PAD_LEFT),
                'name'             => $displayName,
                'initials'         => $initials,
                'email'            => $row->email,
                'loan_amount'      => $row->loan_amount,
                'total_amount'     => $row->total_amount,
                'loan_term'        => $row->loan_term,
                'applied_at'       => $row->applied_at,
                'paid_count'       => $paidCount,
                'total_count'      => $totalCount,
                'next_due_date'    => $nextUnpaid->due_date ?? null,
                'next_due_amount'  => $nextUnpaid->amount_due ?? null,
                'amount_collected' => $amountCollected,
            ];

            $stats['total']++;
            $stats['total_amount'] += $row->total_amount;
            $stats['total_collected'] += $amountCollected;
        }

        return view('admin.ongoing-loans', [
            'ongoingLoans'     => $ongoingLoans,
            'stats'            => $stats,
            'sort'             => $sort,
            'pendingCount'     => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }

    /**
     * Active Loans > Overdue Payments — kabaligtaran ng ongoingLoans():
     * mga loan na status = 'active' AT may kahit isang unpaid installment
     * na lagpas na sa due_date (paid_at null). Ito yung mga na-exclude sa
     * Ongoing Loans dahil dito na sila dapat lumabas.
     */
    public function overduePayments(Request $request)
    {
        $adminId = Session::get('user_id');
        $sort = $request->query('sort', 'most_late');

        $apps = DB::table('loan_applications as la')
            ->join('users as u', 'la.user_id', '=', 'u.user_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('la.status', 'active')
            ->select(
                'la.application_id', 'la.loan_amount', 'la.loan_term', 'la.loan_purpose',
                'la.total_amount', 'la.applied_at',
                'u.user_id', 'u.email',
                'bpd.detail_id', 'bpd.first_name', 'bpd.last_name'
            )
            ->get();

        $overdueLoans = [];
        $stats = ['total' => 0, 'total_overdue_amount' => 0, 'total_overdue_installments' => 0];

        foreach ($apps as $row) {
            $schedules = DB::table('loan_payment_schedules')
                ->where('application_id', $row->application_id)
                ->orderBy('installment_no')
                ->get();

            $overdueSchedules = $schedules->filter(function ($s) {
                return is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
            })->sortBy('due_date')->values();

            // Ongoing Loans lang ang bahala sa mga walang overdue —
            // dito, kailangan may kahit isa.
            if ($overdueSchedules->isEmpty()) continue;

            $oldestOverdue = $overdueSchedules->first();
            $daysLate = \Carbon\Carbon::parse($oldestOverdue->due_date)->diffInDays(now());
            $overdueAmount = $overdueSchedules->sum(fn($s) => $s->amount_due - $s->amount_paid);

            $fullName = trim($row->first_name . ' ' . $row->last_name);
            $displayName = $fullName !== '' ? $fullName : $row->email;
            $initials = $fullName !== ''
                ? strtoupper(substr($row->first_name, 0, 1) . substr($row->last_name, 0, 1))
                : strtoupper(substr($row->email, 0, 2));

            $overdueLoans[] = [
                'application_id'      => $row->application_id,
                'user_id'             => $row->user_id,
                'borrower_id'         => 'BRW-' . str_pad($row->user_id, 4, '0', STR_PAD_LEFT),
                'name'                => $displayName,
                'initials'            => $initials,
                'email'               => $row->email,
                'loan_amount'         => $row->loan_amount,
                'total_amount'        => $row->total_amount,
                'applied_at'          => $row->applied_at,
                'overdue_count'       => $overdueSchedules->count(),
                'total_count'         => $schedules->count(),
                'oldest_due_date'     => $oldestOverdue->due_date,
                'days_late'           => $daysLate,
                'overdue_amount'      => $overdueAmount,
            ];

            $stats['total']++;
            $stats['total_overdue_amount'] += $overdueAmount;
            $stats['total_overdue_installments'] += $overdueSchedules->count();
        }

        // "Most days late" default — pinaka-apurahan makikita muna.
        // "Newest"/"Oldest" gumagamit ng applied_at, tulad ng ibang pages.
        $overdueLoans = collect($overdueLoans)->sortBy(function ($loan) use ($sort) {
            if ($sort === 'newest') return -strtotime($loan['applied_at']);
            if ($sort === 'oldest') return strtotime($loan['applied_at']);
            // most_late (default) — pinakamatagal na overdue muna
            return -$loan['days_late'];
        })->values()->all();

        return view('admin.overdue-payments', [
            'overdueLoans'     => $overdueLoans,
            'stats'            => $stats,
            'sort'             => $sort,
            'pendingCount'     => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }
 
 
    /**
     * List page — Pending/Reviewed tabs (default), or a filtered
     * Approved/Rejected/On Hold view via ?status=... (same pattern as
     * loanApplications()).
     * GET  -> shows the list
     * POST -> approve/reject a borrower's details (called from the pending cards,
     *         supports both normal form submit and the fetch()/AJAX flow in the JS)
     */
    public function borrowerVerification(Request $request)
    {
        $adminId = Session::get('user_id');

        if ($request->isMethod('post') && $request->has('action')) {
            $detailId = $request->input('detail_id');
            $this->saveDecision($request, $adminId);

            $newStatus = DB::table('borrowerpersonaldetails')
                ->where('detail_id', $detailId)
                ->value('status');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'status' => $newStatus,
                ]);
            }

            return redirect()->route('admin.borrower-verification')
                ->with('success', true)
                ->with('decisionStatus', $newStatus);
        }

        $viewStatus = $request->query('status', 'pending');

        return view('admin.borrower-verification', array_merge(
            $this->buildVerificationListData($adminId, $viewStatus),
            ['viewStatus' => $viewStatus]
        ));
    }

    public function verificationDetail(Request $request, $id)
    {
        $adminId = Session::get('user_id');
        $detailId = (int) $id;

        $d = $this->getBorrowerDetail($detailId);

        if (!$d) {
            return redirect()->route('admin.borrower-verification');
        }

        if ($request->isMethod('post')) {
            if ($request->filled('comment')) {
                $this->saveComment($detailId, $adminId, $request->input('comment'));
                return redirect()->route('admin.verification-detail', $detailId);
            }

            if ($request->filled('action')) {
                $this->saveDecision($request->merge(['detail_id' => $detailId]), $adminId);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'redirect' => route('admin.borrower-verification'),
                    ]);
                }

                return redirect()->route('admin.borrower-verification')->with('success', true);
            }
        }

        return view('admin.verification-detail', [
            'd' => $d,
            'detailId' => $detailId,
            'adminId' => $adminId,
            'myReview' => $this->getMyReview($detailId, $adminId),
            'allReviews' => $this->getAllReviews($detailId),
            'comments' => $this->getComments($detailId),
            // Nav is duplicated on every admin page, and both sidebar badges
            // (Borrower Verification + Loan Applications) reference these —
            // this page's own section has no ?? fallback in the Blade, so it
            // needs to be passed explicitly or it throws Undefined variable.
            'pendingCount' => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
            // Highlights the sidebar submenu item matching THIS borrower's
            // current status (not wherever the admin navigated from) — e.g.
            // an already-approved borrower highlights "Approved", not
            // "Pending", regardless of which list link they clicked.
            'viewStatus' => $d->status,
        ]);
    }

    // Lightweight count-only version of buildVerificationListData()'s pending
    // query — used by pages that only need the sidebar badge number, not the
    // full pending list (verification-detail, loan-application-detail).
    private function getPendingVerificationCount($adminId)
    {
        return DB::table('borrowerpersonaldetails as bpd')
            ->whereIn('bpd.status', ['pending', 'on_hold'])
            ->whereNotExists(function ($query) use ($adminId) {
                $query->select(DB::raw(1))
                    ->from('detailsapproval as da')
                    ->whereColumn('da.detail_id', 'bpd.detail_id')
                    ->where('da.admin_id', $adminId);
            })
            ->count();
    }

    // Same idea as getPendingVerificationCount() above, but for loan_applications.
    private function getPendingLoanCount($adminId)
    {
        return DB::table('loan_applications as la')
            ->whereIn('la.status', ['pending', 'on_hold'])
            ->whereNotExists(function ($query) use ($adminId) {
                $query->select(DB::raw(1))
                    ->from('loan_approvals as lap')
                    ->whereColumn('lap.application_id', 'la.application_id')
                    ->where('lap.admin_id', $adminId);
            })
            ->count();
    }

    /* -----------------------------------------------------------
     | Shared helpers — used by both borrowerVerification() and
     | verificationDetail() so the approve/reject logic lives in
     | one place only.
     |-----------------------------------------------------------*/

    private function saveDecision(Request $request, $adminId)
    {
        $detailId = $request->input('detail_id');
        $action = $request->input('action');
        $remarks = trim($request->input('remarks', ''));

        $existing = DB::table('detailsapproval')
            ->where('detail_id', $detailId)
            ->where('admin_id', $adminId)
            ->first();

        if ($existing) {
            DB::table('detailsapproval')
                ->where('detail_id', $detailId)
                ->where('admin_id', $adminId)
                ->update([
                    'decision' => $action,
                    'remarks' => $remarks,
                    'reviewed_at' => now(),
                ]);
        } else {
            DB::table('detailsapproval')->insert([
                'detail_id' => $detailId,
                'admin_id' => $adminId,
                'admin_role' => 'admin',
                'decision' => $action,
                'remarks' => $remarks,
                'reviewed_at' => now(),
            ]);
        }

        $this->refreshDetailStatus($detailId);
    }

    private function refreshDetailStatus($detailId)
    {
        $counts = DB::table('detailsapproval')
            ->where('detail_id', $detailId)
            ->selectRaw("SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END) as a, SUM(CASE WHEN decision = 'rejected' THEN 1 ELSE 0 END) as r")
            ->first();

        if ($counts->a >= 3) {
            $status = 'approved';
        } elseif ($counts->r >= 3) {
            $status = 'rejected';
        } elseif ($counts->r > 0) {
            $status = 'on_hold';
        } else {
            $status = 'pending';
        }

        DB::table('borrowerpersonaldetails')->where('detail_id', $detailId)->update(['status' => $status]);
    }

    private function saveComment($detailId, $adminId, $comment)
    {
        DB::table('detailscomments')->insert([
            'detail_id' => $detailId,
            'admin_id' => $adminId,
            'comment' => trim($comment),
            'created_at' => now(),
        ]);
    }

    private function getBorrowerDetail($detailId)
    {
        return DB::table('borrowerpersonaldetails as bpd')
            ->join('users as u', 'bpd.user_id', '=', 'u.user_id')
            ->leftJoin('borroweraddress as ba', 'ba.detail_id', '=', 'bpd.detail_id')
            ->leftJoin('borrowerworkinfo as bwi', 'bwi.detail_id', '=', 'bpd.detail_id')
            ->leftJoin('comakerinfo as ci', 'ci.detail_id', '=', 'bpd.detail_id')
            ->where('bpd.detail_id', $detailId)
            ->select(
                'bpd.*',
                'u.email as reg_email',
                'u.created_at as registered_at',
                'ba.region', 'ba.province', 'ba.city', 'ba.barangay', 'ba.zipcode', 'ba.home_address',
                'bwi.employment_status', 'bwi.company_name', 'bwi.position', 'bwi.years_in_company',
                'ci.last_name as co_last', 'ci.first_name as co_first', 'ci.middle_name as co_middle',
                'ci.relationship', 'ci.contact_number as co_contact'
            )
            ->first();
    }

    private function getMyReview($detailId, $adminId)
    {
        return DB::table('detailsapproval')
            ->where('detail_id', $detailId)
            ->where('admin_id', $adminId)
            ->first();
    }

    private function getAllReviews($detailId)
    {
        return DB::table('detailsapproval as da')
            ->join('users as u', 'da.admin_id', '=', 'u.user_id')
            ->where('da.detail_id', $detailId)
            ->select('da.*', 'u.name as admin_name')
            ->orderBy('da.reviewed_at')
            ->get();
    }

    private function getComments($detailId)
    {
        return DB::table('detailscomments as dc')
            ->join('users as u', 'dc.admin_id', '=', 'u.user_id')
            ->where('dc.detail_id', $detailId)
            ->select('dc.*', 'u.name as admin_name')
            ->orderBy('dc.created_at')
            ->get();
    }

    /* -----------------------------------------------------------
     | List-page data (Pending/Reviewed tabs when $viewStatus is
     | 'pending', or a flat Approved/Rejected/On Hold list — same
     | shape as buildLoanApplicationsListData() below).
     |-----------------------------------------------------------*/

    private function buildVerificationListData($adminId, $viewStatus = 'pending')
    {
        $pendingRows = DB::table('borrowerpersonaldetails as bpd')
            ->join('users as u', 'bpd.user_id', '=', 'u.user_id')
            ->whereIn('bpd.status', ['pending', 'on_hold'])
            ->whereNotExists(function ($query) use ($adminId) {
                $query->select(DB::raw(1))
                    ->from('detailsapproval as da')
                    ->whereColumn('da.detail_id', 'bpd.detail_id')
                    ->where('da.admin_id', $adminId);
            })
            ->select(
                'bpd.detail_id', 'bpd.last_name', 'bpd.first_name', 'bpd.middle_name',
                'bpd.cellphone_num', 'bpd.status', 'u.email'
            )
            ->orderByDesc('bpd.detail_id')
            ->get();

        $pendingDetails = [];
        foreach ($pendingRows as $row) {
            $counts = DB::table('detailsapproval')
                ->where('detail_id', $row->detail_id)
                ->selectRaw("SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END) as a, SUM(CASE WHEN decision = 'rejected' THEN 1 ELSE 0 END) as r")
                ->first();

            $pendingDetails[] = [
                'detail_id' => $row->detail_id,
                'last_name' => $row->last_name,
                'first_name' => $row->first_name,
                'middle_name' => $row->middle_name,
                'cellphone_num' => $row->cellphone_num,
                'email' => $row->email,
                'status' => $row->status,
                'approved_count' => (int) $counts->a,
                'rejected_count' => (int) $counts->r,
                'already_reviewed' => false,
            ];
        }

        $reviewedDetails = DB::table('detailsapproval as da')
            ->join('borrowerpersonaldetails as bpd', 'da.detail_id', '=', 'bpd.detail_id')
            ->join('users as u', 'bpd.user_id', '=', 'u.user_id')
            ->where('da.admin_id', $adminId)
            ->select(
                'da.detail_id', 'da.decision', 'da.remarks', 'da.reviewed_at',
                'bpd.last_name', 'bpd.first_name', 'u.email'
            )
            ->orderByDesc('da.reviewed_at')
            ->get();

        // Used only when viewing Approved / Rejected / On Hold via the sidebar
        // dropdown — shows ALL borrowers at that final status, not just the
        // ones this admin personally reviewed (that's what Reviewed tab is for).
        $filteredDetails = collect();
        if ($viewStatus !== 'pending') {
            $filteredDetails = DB::table('borrowerpersonaldetails as bpd')
                ->join('users as u', 'bpd.user_id', '=', 'u.user_id')
                ->where('bpd.status', $viewStatus)
                ->select(
                    'bpd.detail_id', 'bpd.last_name', 'bpd.first_name', 'bpd.middle_name',
                    'bpd.cellphone_num', 'bpd.status', 'u.email'
                )
                ->orderByDesc('bpd.detail_id')
                ->get();
        }

        $pendingCount = count($pendingDetails);

        return compact('pendingDetails', 'reviewedDetails', 'filteredDetails', 'pendingCount');
    }

    /* -----------------------------------------------------------
     | Loan Applications — same 3-admin approval pattern as the
     | personal-details verification above, but for loan_applications.
     |-----------------------------------------------------------*/

    public function loanApplications(Request $request)
{
    $adminId = Session::get('user_id');

    if ($request->isMethod('post') && $request->has('action')) {
        // ...unchanged...
    }

    $viewStatus = $request->query('status', 'pending');

    return view('admin.loan-applications', array_merge(
        $this->buildLoanApplicationsListData($adminId, $viewStatus),
        [
            'viewStatus' => $viewStatus,
            // Sidebar's Borrower Verification badge needs this —
            // buildLoanApplicationsListData() only returns pendingLoanCount,
            // not pendingCount, so this page never had it.
            'pendingCount' => $this->getPendingVerificationCount($adminId),
        ]
    ));
}

    public function loanApplicationDetail(Request $request, $id)
    {
        $adminId = Session::get('user_id');
        $applicationId = (int) $id;

        $app = $this->getLoanApplication($applicationId);

        if (!$app) {
            return redirect()->route('admin.loan-applications');
        }

        if ($request->isMethod('post')) {
            if ($request->filled('comment')) {
                $this->saveLoanComment($applicationId, $adminId, $request->input('comment'));
                return redirect()->route('admin.loan-application-detail', $applicationId);
            }

            if ($request->filled('action')) {
                $this->saveLoanDecision($request->merge(['application_id' => $applicationId]), $adminId);

                $newStatus = DB::table('loan_applications')
                    ->where('application_id', $applicationId)
                    ->value('status');

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'status' => $newStatus,
                        'redirect' => route('admin.loan-applications'),
                    ]);
                }

                return redirect()->route('admin.loan-applications')
                    ->with('success', true)
                    ->with('decisionStatus', $newStatus);
            }
        }

        return view('admin.loan-application-detail', [
            'app' => $app,
            'address' => $this->getBorrowerAddress($app->detail_id),
            'work' => $this->getBorrowerWork($app->detail_id),
            'applicationId' => $applicationId,
            'adminId' => $adminId,
            'myReview' => $this->getMyLoanReview($applicationId, $adminId),
            'allReviews' => $this->getAllLoanReviews($applicationId),
            'approvedCount' => $this->getLoanApprovedCount($applicationId),
            'comments' => $this->getLoanComments($applicationId),
            // Payment Schedule section — may laman lang kung 'active' na o
            // 'completed' na yung loan (yun lang may na-generate na schedule
            // via LoanActivationService). Wala nito ang mga pending/approved-
            // pero-di-pa-active/rejected/on_hold applications.
            'paymentSchedule' => $this->getPaymentSchedule($applicationId),
            // Same reasoning as verificationDetail() above — this page's own
            // sidebar section (Loan Applications) has no ?? fallback in the Blade.
            'pendingCount' => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
            // Highlights based on THIS application's current status, not
            // wherever the admin navigated from — same fix as verificationDetail().
            'viewStatus' => $app->status,
        ]);
    }

    /**
     * Overdue Payments > View Details — a focused, read-only view showing
     * just the borrower card, an overdue overview, Loan Summary, and the
     * Payment Schedule. Deliberately kept separate from
     * loanApplicationDetail() (Personal Info / Address / Work / Discussion /
     * Admin Review / decision modal) so that page's approve/reject flow
     * never has to be touched just to adjust this simpler overdue view.
     */
    public function overduePaymentDetail(Request $request, $id)
    {
        $adminId = Session::get('user_id');
        $applicationId = (int) $id;

        $app = $this->getLoanApplication($applicationId);

        if (!$app) {
            return redirect()->route('admin.overdue-payments');
        }

        $schedule = $this->getPaymentSchedule($applicationId);

        $overdueSchedules = $schedule->filter(function ($s) {
            return is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
        })->sortBy('due_date')->values();

        $oldestOverdue = $overdueSchedules->first();

        return view('admin.overdue-payment-detail', [
            'app' => $app,
            'applicationId' => $applicationId,
            'paymentSchedule' => $schedule,
            'overdueCount' => $overdueSchedules->count(),
            'daysLate' => $oldestOverdue ? \Carbon\Carbon::parse($oldestOverdue->due_date)->diffInDays(now()) : 0,
            'overdueAmount' => $overdueSchedules->sum(fn($s) => $s->amount_due - $s->amount_paid),
            'pendingCount' => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }

    // Buong payment schedule ng isang loan application — installment #, due
    // date, amount, at status (paid/late/pending). Ginagamit ng Payment
    // Schedule section sa loan-application-detail.blade.php at
    // overdue-payment-detail.blade.php.
    private function getPaymentSchedule($applicationId)
    {
        return DB::table('loan_payment_schedules')
            ->where('application_id', $applicationId)
            ->orderBy('installment_no')
            ->get();
    }

    private function saveLoanComment($applicationId, $adminId, $comment)
    {
        DB::table('loan_approval_comments')->insert([
            'application_id' => $applicationId,
            'admin_id' => $adminId,
            'comment' => trim($comment),
            'created_at' => now(),
        ]);
    }

    private function getLoanComments($applicationId)
    {
        return DB::table('loan_approval_comments as lac')
            ->join('users as u', 'lac.admin_id', '=', 'u.user_id')
            ->where('lac.application_id', $applicationId)
            ->select('lac.*', 'u.name as admin_name')
            ->orderBy('lac.created_at')
            ->get();
    }

    private function saveLoanDecision(Request $request, $adminId)
    {
        $applicationId = $request->input('application_id');
        $action = $request->input('action');
        $remarks = trim($request->input('remarks', ''));

        $existing = DB::table('loan_approvals')
            ->where('application_id', $applicationId)
            ->where('admin_id', $adminId)
            ->first();

        if ($existing) {
            DB::table('loan_approvals')
                ->where('application_id', $applicationId)
                ->where('admin_id', $adminId)
                ->update([
                    'decision' => $action,
                    'remarks' => $remarks,
                    'reviewed_at' => now(),
                ]);
        } else {
            DB::table('loan_approvals')->insert([
                'application_id' => $applicationId,
                'admin_id' => $adminId,
                'admin_role' => 'admin',
                'decision' => $action,
                'remarks' => $remarks,
                'reviewed_at' => now(),
            ]);
        }

        $this->refreshLoanApplicationStatus($applicationId);
    }

   private function refreshLoanApplicationStatus($applicationId)
    {
        $counts = DB::table('loan_approvals')
            ->where('application_id', $applicationId)
            ->selectRaw("SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END) as a, SUM(CASE WHEN decision = 'rejected' THEN 1 ELSE 0 END) as r")
            ->first();

        if ($counts->a >= 3) {
            $status = 'approved';
        } elseif ($counts->r >= 3) {
            $status = 'rejected';
        } elseif ($counts->r > 0) {
            $status = 'on_hold';
        } else {
            $status = 'pending';
        }

        DB::table('loan_applications')->where('application_id', $applicationId)->update(['status' => $status]);

        // Pag umabot sa 3 approvals, i-generate na yung buong payment
        // schedule, tapos i-flip yung status papuntang "active"
        // (LoanActivationService mismo ang nag-o-overwrite ng status).
        if ($status === 'approved') {
            LoanActivationService::activate($applicationId);
        }
    }

    private function getLoanApplication($applicationId)
    {
        return DB::table('loan_applications as la')
            ->join('users as u', 'la.user_id', '=', 'u.user_id')
            ->join('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('la.application_id', $applicationId)
            ->select(
                'la.*',
                'u.email',
                'bpd.detail_id', 'bpd.first_name', 'bpd.last_name', 'bpd.middle_name', 'bpd.suffix',
                'bpd.birthday', 'bpd.gender', 'bpd.civil_status', 'bpd.citizenship',
                'bpd.cellphone_num', 'bpd.monthly_income',
                'bpd.id_type', 'bpd.id_number', 'bpd.id_file_path',
                'bpd.birth_country', 'bpd.birth_province', 'bpd.birth_city'
            )
            ->first();
    }

    private function getBorrowerAddress($detailId)
    {
        if (!$detailId) return null;

        return DB::table('borroweraddress')->where('detail_id', $detailId)->first();
    }

    private function getBorrowerWork($detailId)
    {
        if (!$detailId) return null;

        return DB::table('borrowerworkinfo')->where('detail_id', $detailId)->first();
    }

    private function getMyLoanReview($applicationId, $adminId)
    {
        return DB::table('loan_approvals')
            ->where('application_id', $applicationId)
            ->where('admin_id', $adminId)
            ->first();
    }

    private function getLoanApprovedCount($applicationId)
    {
        return DB::table('loan_approvals')
            ->where('application_id', $applicationId)
            ->where('decision', 'approved')
            ->count();
    }

    private function getAllLoanReviews($applicationId)
    {
        return DB::table('loan_approvals as la')
            ->join('users as u', 'la.admin_id', '=', 'u.user_id')
            ->where('la.application_id', $applicationId)
            ->select('la.*', 'u.name as admin_name')
            ->orderBy('la.reviewed_at')
            ->get();
    }

    // Same shape as buildVerificationListData() above — excludes applications this
    // admin already reviewed from Pending (they move to Reviewed instead).
    private function buildLoanApplicationsListData($adminId, $viewStatus = 'pending')
    {
        $pendingRows = DB::table('loan_applications as la')
            ->join('users as u', 'la.user_id', '=', 'u.user_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->whereIn('la.status', ['pending', 'on_hold'])
            ->whereNotExists(function ($query) use ($adminId) {
                $query->select(DB::raw(1))
                    ->from('loan_approvals as lap')
                    ->whereColumn('lap.application_id', 'la.application_id')
                    ->where('lap.admin_id', $adminId);
            })
            ->select(
                'la.application_id', 'la.loan_amount', 'la.loan_term', 'la.loan_purpose',
                'la.status', 'la.applied_at', 'u.email',
                'bpd.first_name', 'bpd.last_name', 'bpd.middle_name', 'bpd.cellphone_num'
            )
            ->orderByDesc('la.application_id')
            ->get();

        $pendingApplications = [];
        foreach ($pendingRows as $row) {
            $counts = DB::table('loan_approvals')
                ->where('application_id', $row->application_id)
                ->selectRaw("SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END) as a, SUM(CASE WHEN decision = 'rejected' THEN 1 ELSE 0 END) as r")
                ->first();

            $pendingApplications[] = [
                'application_id' => $row->application_id,
                'last_name' => $row->last_name,
                'first_name' => $row->first_name,
                'middle_name' => $row->middle_name,
                'email' => $row->email,
                'cellphone_num' => $row->cellphone_num,
                'loan_amount' => $row->loan_amount,
                'loan_term' => $row->loan_term,
                'loan_purpose' => $row->loan_purpose,
                'status' => $row->status,
                'applied_at' => $row->applied_at,
                'approved_count' => (int) $counts->a,
                'rejected_count' => (int) $counts->r,
            ];
        }

        $reviewedApplications = DB::table('loan_approvals as lap')
            ->join('loan_applications as la', 'lap.application_id', '=', 'la.application_id')
            ->join('users as u', 'la.user_id', '=', 'u.user_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('lap.admin_id', $adminId)
            ->select(
                'lap.application_id', 'lap.decision', 'lap.remarks', 'lap.reviewed_at',
                'bpd.last_name', 'bpd.first_name', 'u.email', 'la.loan_amount',
                DB::raw('(SELECT COUNT(*) FROM loan_approvals WHERE application_id = la.application_id) as total_reviewed')
            )
            ->orderByDesc('lap.reviewed_at')
            ->get();

        $filteredApplications = collect();
        if ($viewStatus !== 'pending') {
            // "Approved" ay dapat kasama rin ang 'active' — dahil automatic
            // na nagfli-flip ang status papuntang 'active' pagkatapos
            // ma-generate ang payment schedule (LoanActivationService), wala
            // nang application na permanenteng nakatigil sa eksaktong
            // status='approved'. Kung hindi ito pagsasamahin, laging
            // magiging empty ang Approved tab.
            $statusFilter = $viewStatus === 'approved' ? ['approved', 'active'] : [$viewStatus];

            $filteredApplications = DB::table('loan_applications as la')
                ->join('users as u', 'la.user_id', '=', 'u.user_id')
                ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
                ->whereIn('la.status', $statusFilter)
                ->select(
                    'la.application_id', 'la.loan_amount', 'la.loan_term', 'la.loan_purpose',
                    'la.status', 'la.applied_at', 'u.email',
                    'bpd.first_name', 'bpd.last_name'
                )
                ->orderByDesc('la.application_id')
                ->get();
        }

        $pendingLoanCount = count($pendingApplications);

        return compact('pendingApplications', 'reviewedApplications', 'filteredApplications', 'pendingLoanCount');
    }

    /* -----------------------------------------------------------
     | Penalty Tracking — Kanban board (unpaid/paid/waived all at
     | once), matching the shape penalty-tracking.blade.php expects.
     | Uses the same raw table/column names already confirmed working
     | here: loan_penalties.schedule_id -> loan_payment_schedules.id,
     | loan_penalties.application_id -> loan_applications.application_id.
     |-----------------------------------------------------------*/

    public function penaltyTracking(Request $request)
    {
        $adminId = Session::get('user_id');

        $penalties = DB::table('loan_penalties as lp')
            ->join('loan_payment_schedules as sch', 'lp.schedule_id', '=', 'sch.id')
            ->join('loan_applications as la', 'lp.application_id', '=', 'la.application_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->select(
                'lp.*',
                'sch.installment_no', 'sch.due_date', 'sch.amount_due',
                'bpd.first_name', 'bpd.last_name'
            )
            ->orderByDesc('lp.created_at')
            ->get();

        $unpaid = $penalties->where('status', 'unpaid')->values();
        $paid   = $penalties->where('status', 'paid')->values();
        $waived = $penalties->where('status', 'waived')->values();

        return view('admin.penalty-tracking', [
            'unpaid' => $unpaid,
            'paid'   => $paid,
            'waived' => $waived,

            'totalPenaltiesCount' => $penalties->count(),
            'outstandingAmount'   => $unpaid->sum('penalty_amount'),
            'totalPaidAmount'     => $paid->sum('penalty_amount'),
            'totalWaivedAmount'   => $waived->sum('penalty_amount'),

            'pendingCount'     => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }

    /**
     * Active Loans > Completed Loans — mga loan na status = 'completed'
     * (lahat ng installments bayad na, LoanActivationService/payment flow
     * na ang nag-flip nito papuntang 'completed').
     */
    public function completedLoans(Request $request)
    {
        $adminId = Session::get('user_id');
        $sort = $request->query('sort', 'newest');

        $apps = DB::table('loan_applications as la')
            ->join('users as u', 'la.user_id', '=', 'u.user_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('la.status', 'completed')
            ->select(
                'la.application_id', 'la.loan_amount', 'la.loan_term', 'la.loan_purpose',
                'la.total_amount', 'la.applied_at',
                'u.user_id', 'u.email',
                'bpd.detail_id', 'bpd.first_name', 'bpd.last_name'
            )
            ->orderBy('la.applied_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->get();

        $completedLoans = [];
        $stats = ['total' => 0, 'total_amount' => 0, 'total_interest' => 0];

        foreach ($apps as $row) {
            $schedules = DB::table('loan_payment_schedules')
                ->where('application_id', $row->application_id)
                ->orderBy('installment_no')
                ->get();

            $fullName = trim($row->first_name . ' ' . $row->last_name);
            $displayName = $fullName !== '' ? $fullName : $row->email;
            $initials = $fullName !== ''
                ? strtoupper(substr($row->first_name, 0, 1) . substr($row->last_name, 0, 1))
                : strtoupper(substr($row->email, 0, 2));

            $interestEarned = max(0, $row->total_amount - $row->loan_amount);

            $completedLoans[] = [
                'application_id'   => $row->application_id,
                'user_id'          => $row->user_id,
                'borrower_id'      => 'BRW-' . str_pad($row->user_id, 4, '0', STR_PAD_LEFT),
                'name'             => $displayName,
                'initials'         => $initials,
                'email'            => $row->email,
                'loan_amount'      => $row->loan_amount,
                'total_amount'     => $row->total_amount,
                'loan_term'        => $row->loan_term,
                'applied_at'       => $row->applied_at,
                // Huling binayaran na installment — ginagamit bilang
                // "Completed On" date sa listahan.
                'completed_at'     => $schedules->max('paid_at'),
                'amount_collected' => $schedules->sum('amount_paid'),
            ];

            $stats['total']++;
            $stats['total_amount'] += $row->total_amount;
            $stats['total_interest'] += $interestEarned;
        }

        // "Recently Completed" gumagamit ng completed_at (huling bayad);
        // "Newest"/"Oldest" gumagamit pa rin ng applied_at, tulad ng ibang pages.
        if (in_array($sort, ['completed_newest', 'completed_oldest'], true)) {
            $completedLoans = collect($completedLoans)->sortBy(function ($loan) use ($sort) {
                $t = $loan['completed_at'] ? strtotime($loan['completed_at']) : 0;
                return $sort === 'completed_oldest' ? $t : -$t;
            })->values()->all();
        }

        return view('admin.completed-loans', [
            'completedLoans'   => $completedLoans,
            'stats'            => $stats,
            'sort'             => $sort,
            'pendingCount'     => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }

    /**
     * Completed Loans > View Details — same focused pattern as
     * overduePaymentDetail(): borrower card + Loan Summary + Payment
     * Schedule lang, hiwalay pa rin sa loanApplicationDetail()'s
     * approve/reject flow.
     */
    public function completedLoanDetail(Request $request, $id)
{
    $adminId = Session::get('user_id');
    $applicationId = (int) $id;

    $app = $this->getLoanApplication($applicationId);

    if (!$app) {
        return redirect()->route('admin.completed-loans');
    }

    $schedule = $this->getPaymentSchedule($applicationId);

    return view('admin.completed-loan-detail', [
        'app' => $app,
        'applicationId' => $applicationId,
        'paymentSchedule' => $schedule,
        'completedAt' => $schedule->max('paid_at'),
        'amountCollected' => $schedule->sum('amount_paid'),
        // Borrower Performance section — computed lang, walang bagong
        // table/column na kailangan (gamit lang ang existing schedule +
        // loan_penalties data).
        'borrowerPerformance' => $this->computeBorrowerPerformance($applicationId, $schedule),
        'pendingCount' => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
    ]);
}

/**
 * Borrower Performance (Completed Loan Detail) — kinukwenta base sa
 * aktwal na payment schedule ng loan na ito:
 *   - total_payments: bilang ng installments (dapat lahat bayad na
 *     dahil 'completed' na ang loan)
 *   - late_payments: bilang ng installments na ang paid_at ay lagpas
 *     na sa due_date (o walang paid_at pero naka-mark na 'late' na
 *     status bilang fallback)
 *   - total_penalties: kabuuang penalty_amount mula sa loan_penalties
 *     para sa application na ito (lahat ng status — unpaid/paid/waived
 *     — dahil history ng aktwal na na-incur, hindi lang ng nabayaran)
 *
 * Rating thresholds — base lang sa bilang ng late payments
 * (simpleng rule, hindi na ratio-based):
 *   Excellent — 0 late payments
 *   Good      — 1 late payment
 *   Fair      — 2 late payments
 *   Poor      — 3 o higit pang late payments
 *
 * Ang description ay dynamic — inilalarawan yung aktwal na payment
 * behavior (hal. "all installments were paid late" kung talagang
 * lahat late, hindi lang generic na text).
 */
private function computeBorrowerPerformance($applicationId, $schedule)
{
    $totalPayments = $schedule->count();

    $latePayments = $schedule->filter(function ($s) {
        if (!is_null($s->paid_at) && !is_null($s->due_date)) {
            return \Carbon\Carbon::parse($s->paid_at)->gt(\Carbon\Carbon::parse($s->due_date));
        }
        // Fallback: kung walang paid_at pa rin pero naka-mark na 'late'
        // ang status (edge case, hindi dapat mangyari sa completed loan).
        return ($s->status ?? null) === 'late';
    })->count();

    $totalPenalties = (float) DB::table('loan_penalties')
        ->where('application_id', $applicationId)
        ->sum('penalty_amount');

    if ($latePayments === 0) {
        $rating = 'excellent';
        $label = 'Excellent Repayment History';
        $description = 'All installments were paid on or before their due dates.';
    } elseif ($latePayments === 1) {
        $rating = 'good';
        $label = 'Good Repayment History';
        $description = 'Loan was completed with a mostly on-time repayment history — 1 installment was paid late.';
    } elseif ($latePayments === 2) {
        $rating = 'fair';
        $label = 'Fair Repayment History';
        $description = "Loan was completed with some delayed payments — {$latePayments} of {$totalPayments} installments were paid late.";
    } else {
        $rating = 'poor';
        $label = 'Poor Repayment History';

        if ($latePayments === $totalPayments) {
            $description = 'Loan was fully completed, but all installments were paid late.';
        } else {
            $description = "Loan was fully completed, but multiple installments were paid late ({$latePayments} of {$totalPayments}).";
        }
    }

    return [
        'rating' => $rating,
        'label' => $label,
        'description' => $description,
        'total_payments' => $totalPayments,
        'late_payments' => $latePayments,
        'total_penalties' => $totalPenalties,
    ];
}

    public function markPenaltyPaid(Request $request, $id)
{
    DB::table('loan_penalties')->where('id', $id)->update([
        'status' => 'paid',
        'updated_at' => now(),
    ]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Penalty marked as paid.',
        ]);
    }

    return back()->with('success', 'Penalty marked as paid.');
}

public function waivePenalty(Request $request, $id)
{
    $request->validate(['waived_reason' => 'required|string|max:255']);
    $adminId = Session::get('user_id');

    DB::table('loan_penalties')->where('id', $id)->update([
        'status' => 'waived',
        'waived_by' => $adminId,
        'waived_reason' => $request->waived_reason,
        'updated_at' => now(),
    ]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Penalty waived.',
            'waived_reason' => $request->waived_reason,
        ]);
    }

    return back()->with('success', 'Penalty waived.');
}
    // Ensure class is properly closed
}