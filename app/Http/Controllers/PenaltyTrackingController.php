<?php

namespace App\Http\Controllers;

use App\Models\LoanPenalty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PenaltyTrackingController extends Controller
{
    /**
     * Kanban board view — shows ALL penalties split into three columns
     * (unpaid / paid / waived) at once.
     *
     * Borrower name isn't pulled through Eloquent relations (there's no
     * LoanApplication model) — instead we do one direct join query:
     *   active_loans -> loan_applications -> borrowerpersonaldetails
     * matching the same pattern already used in AdminController.
     */
    public function index(Request $request)
    {
        $adminId = Session::get('user_id');

        $penalties = LoanPenalty::with(['schedule', 'activeLoan'])
            ->orderByDesc('created_at')
            ->get();

        $activeLoanIds = $penalties->pluck('active_loan_id')->filter()->unique()->values();

        $borrowerLookup = DB::table('active_loans as al')
            ->join('loan_applications as la', 'la.application_id', '=', 'al.loan_application_id')
            ->join('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->whereIn('al.id', $activeLoanIds)
            ->select('al.id as active_loan_id', 'bpd.first_name', 'bpd.last_name')
            ->get()
            ->keyBy('active_loan_id');

        $mapped = $penalties->map(function ($p) use ($borrowerLookup) {
            $borrower = $borrowerLookup->get($p->active_loan_id);

            return (object) [
                'id'             => $p->id,
                'first_name'     => $borrower->first_name ?? '—',
                'last_name'      => $borrower->last_name ?? '',
                'installment_no' => $p->schedule->installment_no ?? '—',
                'due_date'       => $p->schedule->due_date ?? $p->created_at,
                'days_late'      => $p->days_late ?? 0,
                'amount_due'     => $p->schedule->amount_due ?? 0,
                'penalty_amount' => $p->penalty_amount ?? 0,
                'status'         => $p->status,
                'waived_reason'  => $p->waived_reason,
            ];
        });

        $unpaid = $mapped->where('status', 'unpaid')->values();
        $paid   = $mapped->where('status', 'paid')->values();
        $waived = $mapped->where('status', 'waived')->values();

        return view('admin.penalty-tracking', [
            'unpaid' => $unpaid,
            'paid'   => $paid,
            'waived' => $waived,

            'totalPenaltiesCount' => $mapped->count(),
            'outstandingAmount'   => $unpaid->sum('penalty_amount'),
            'totalPaidAmount'     => $paid->sum('penalty_amount'),
            'totalWaivedAmount'   => $waived->sum('penalty_amount'),

            // Same sidebar badge counts used across the other admin pages.
            'pendingCount'     => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }

    public function markPaid(LoanPenalty $penalty)
    {
        $penalty->update(['status' => 'paid']);
        return back()->with('success', 'Penalty marked as paid.');
    }

    public function waive(Request $request, LoanPenalty $penalty)
    {
        $request->validate(['waived_reason' => 'required|string|max:255']);

        $penalty->update([
            'status'        => 'waived',
            'waived_by'     => session('admin_id'),
            'waived_reason' => $request->waived_reason,
        ]);

        return back()->with('success', 'Penalty waived.');
    }

    /* -----------------------------------------------------------
     | Same sidebar badge helpers used in AdminController — duplicated
     | here since this is a separate controller. If you'd rather not
     | duplicate, move these two into a shared trait/base controller.
     |-----------------------------------------------------------*/

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
}