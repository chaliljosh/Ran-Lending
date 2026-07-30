<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
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
            ->limit(4)
            ->get();

        return view('admin.dashboard', compact(
            'pendingVerificationCount', 'totalApplications', 'pendingApplications',
            'activeLoans', 'totalActiveAmount', 'totalBorrowers', 'activeBorrowers',
            'recentApplications'
        ));
    }

    /**
     * List page — Pending / Reviewed tabs.
     * GET  -> shows the list
     * POST -> approve/reject a borrower's details (called from the pending cards,
     *         supports both normal form submit and the fetch()/AJAX flow in the JS)
     */
    public function borrowerVerification(Request $request)
    {
        $adminId = Session::get('user_id');

        if ($request->isMethod('post') && $request->has('action')) {
            $this->saveDecision($request, $adminId);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('admin.borrower-verification')->with('success', true);
        }

        return view('admin.borrower-verification', $this->buildVerificationListData($adminId));
    }

    /**
     * Single borrower page — full details, discussion thread, decision panel.
     * GET  -> shows the details
     * POST -> either adds a comment OR saves a decision, depending on which
     *         field was submitted
     */
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
        ]);
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
     | List-page data (Pending / Reviewed tabs)
     |-----------------------------------------------------------*/

    private function buildVerificationListData($adminId)
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

        $pendingCount = count($pendingDetails);

        return compact('pendingDetails', 'reviewedDetails', 'pendingCount');
    }

    /* -----------------------------------------------------------
     | Loan Applications — same 3-admin approval pattern as the
     | personal-details verification above, but for loan_applications.
     |-----------------------------------------------------------*/

    public function loanApplications(Request $request)
    {
        $adminId = Session::get('user_id');

        if ($request->isMethod('post') && $request->has('action')) {
            $applicationId = $request->input('application_id');
            $this->saveLoanDecision($request, $adminId);

            $newStatus = DB::table('loan_applications')
                ->where('application_id', $applicationId)
                ->value('status');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'status' => $newStatus,
                ]);
            }

            // "decisionStatus" tells the list page which of the result modals
            // to show (submitted / on hold / approved / rejected) — without
            // this, the page has no way to know what actually happened.
            return redirect()->route('admin.loan-applications')
                ->with('success', true)
                ->with('decisionStatus', $newStatus);
        }

        $viewStatus = $request->query('status', 'pending');

        return view('admin.loan-applications', array_merge(
            $this->buildLoanApplicationsListData($adminId, $viewStatus),
            ['viewStatus' => $viewStatus]
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

                // Same fix as loanApplications() above — the "Make a Decision"
                // modal on THIS page submits as a normal form (not fetch/AJAX),
                // so it was always hitting the redirect branch below with no
                // status info attached. That's why only the generic "Decision
                // submitted" message ever showed, even when the application
                // just got fully approved or placed on hold.
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
        ]);
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

        // Used only when viewing Approved / Rejected / On Hold via the sidebar filter
        $filteredApplications = collect();
        if ($viewStatus !== 'pending') {
            $filteredApplications = DB::table('loan_applications as la')
                ->join('users as u', 'la.user_id', '=', 'u.user_id')
                ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
                ->where('la.status', $viewStatus)
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
}