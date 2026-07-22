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

    public function borrowerVerification(Request $request)
    {
        $adminId = Session::get('user_id');

        if ($request->isMethod('post') && $request->has('action')) {
            $detailId = $request->input('detail_id');
            $action = $request->input('action');
            $remarks = trim($request->input('remarks', ''));

            $myReview = DB::table('detailsapproval')->where('detail_id', $detailId)->where('admin_id', $adminId)->first();

            if ($myReview) {
                DB::table('detailsapproval')
                    ->where('detail_id', $detailId)->where('admin_id', $adminId)
                    ->update(['decision' => $action, 'remarks' => $remarks, 'reviewed_at' => now()]);
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

            $counts = DB::table('detailsapproval')->where('detail_id', $detailId)
                ->selectRaw("SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END) as a, SUM(CASE WHEN decision = 'rejected' THEN 1 ELSE 0 END) as r")
                ->first();

            if ($counts->a >= 3) {
                $newStatus = 'approved';
            } elseif ($counts->r >= 3) {
                $newStatus = 'rejected';
            } elseif ($counts->r > 0) {
                $newStatus = 'on_hold';
            } else {
                $newStatus = 'pending';
            }

            DB::table('borrowerpersonaldetails')->where('detail_id', $detailId)->update(['status' => $newStatus]);

            return redirect()->route('admin.borrower-verification')->with('success', true);
        }

        $pendingCount = DB::table('borrowerpersonaldetails')->where('status', 'pending')->count();

        $pendingRows = DB::table('borrowerpersonaldetails as bpd')
            ->join('users as u', 'bpd.user_id', '=', 'u.user_id')
            ->whereIn('bpd.status', ['pending', 'on_hold'])
            ->select('bpd.*', 'u.email')
            ->get();

        $pendingDetails = $pendingRows->map(function ($d) use ($adminId) {
            $approvedCount = DB::table('detailsapproval')->where('detail_id', $d->detail_id)->where('decision', 'approved')->count();
            $rejectedCount = DB::table('detailsapproval')->where('detail_id', $d->detail_id)->where('decision', 'rejected')->count();
            $alreadyReviewed = DB::table('detailsapproval')->where('detail_id', $d->detail_id)->where('admin_id', $adminId)->exists();

            return (array) $d + [
                'approved_count' => $approvedCount,
                'rejected_count' => $rejectedCount,
                'already_reviewed' => $alreadyReviewed,
            ];
        });

        $reviewedDetails = DB::table('detailsapproval as da')
            ->join('borrowerpersonaldetails as bpd', 'da.detail_id', '=', 'bpd.detail_id')
            ->where('da.admin_id', $adminId)
            ->select('bpd.*', 'da.decision', 'da.remarks', 'da.reviewed_at')
            ->orderByDesc('da.reviewed_at')
            ->get();

        return view('admin.borrower-verification', compact('pendingCount', 'pendingDetails', 'reviewedDetails'));
    }

    public function verificationDetail(Request $request, $id)
    {
        $adminId = Session::get('user_id');
        $detailId = (int) $id;

        $d = DB::table('borrowerpersonaldetails as bpd')
            ->join('users as u', 'bpd.user_id', '=', 'u.user_id')
            ->leftJoin('borroweraddress as ba', 'ba.detail_id', '=', 'bpd.detail_id')
            ->leftJoin('borrowerworkinfo as bwi', 'bwi.detail_id', '=', 'bpd.detail_id')
            ->leftJoin('comakerinfo as ci', 'ci.detail_id', '=', 'bpd.detail_id')
            ->where('bpd.detail_id', $detailId)
            ->select('bpd.*', 'u.email as reg_email', 'u.created_at as registered_at',
                'ba.region', 'ba.province', 'ba.city', 'ba.barangay', 'ba.zipcode', 'ba.home_address',
                'bwi.employment_status', 'bwi.company_name', 'bwi.position', 'bwi.years_in_company',
                'ci.last_name as co_last', 'ci.first_name as co_first', 'ci.middle_name as co_middle',
                'ci.relationship', 'ci.contact_number as co_contact')
            ->first();

        if (!$d) {
            return redirect()->route('admin.borrower-verification');
        }

        if ($request->isMethod('post')) {
            if ($request->filled('comment')) {
                DB::table('detailscomments')->insert([
                    'detail_id' => $detailId,
                    'admin_id' => $adminId,
                    'comment' => trim($request->input('comment')),
                    'created_at' => now(),
                ]);
                return redirect()->route('admin.verification-detail', $detailId);
            }

            if ($request->filled('action')) {
                $action = $request->input('action');
                $remarks = trim($request->input('remarks', ''));

                $myReview = DB::table('detailsapproval')->where('detail_id', $detailId)->where('admin_id', $adminId)->first();

                if ($myReview) {
                    DB::table('detailsapproval')
                        ->where('detail_id', $detailId)->where('admin_id', $adminId)
                        ->update(['decision' => $action, 'remarks' => $remarks, 'reviewed_at' => now()]);
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

                $counts = DB::table('detailsapproval')->where('detail_id', $detailId)
                    ->selectRaw("SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END) as a, SUM(CASE WHEN decision = 'rejected' THEN 1 ELSE 0 END) as r")
                    ->first();

                if ($counts->a >= 3) {
                    $newStatus = 'approved';
                } elseif ($counts->r >= 3) {
                    $newStatus = 'rejected';
                } elseif ($counts->r > 0) {
                    $newStatus = 'on_hold';
                } else {
                    $newStatus = 'pending';
                }

                DB::table('borrowerpersonaldetails')->where('detail_id', $detailId)->update(['status' => $newStatus]);

                return redirect()->route('admin.borrower-verification')->with('success', true);
            }
        }

        $myReview = DB::table('detailsapproval')->where('detail_id', $detailId)->where('admin_id', $adminId)->first();

        $allReviews = DB::table('detailsapproval as da')
            ->join('users as u', 'da.admin_id', '=', 'u.user_id')
            ->where('da.detail_id', $detailId)
            ->select('da.*', 'u.name as admin_name')
            ->orderBy('da.reviewed_at')
            ->get();

        $comments = DB::table('detailscomments as dc')
            ->join('users as u', 'dc.admin_id', '=', 'u.user_id')
            ->where('dc.detail_id', $detailId)
            ->select('dc.*', 'u.name as admin_name')
            ->orderBy('dc.created_at')
            ->get();

        return view('admin.verification-detail', compact('d', 'detailId', 'adminId', 'myReview', 'allReviews', 'comments'));
    }
}