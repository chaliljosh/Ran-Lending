<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Services\PenaltyHelper;
use App\Services\LoanActivationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\LoanStatusUpdated;
use App\Mail\PaymentRecorded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Mail\LoanProofSent;
use App\Mail\PaymentVoided;
use App\Mail\BorrowerInfoEdited;
use Illuminate\Support\Facades\Validator;
use App\Mail\PaymentPartiallyRecorded;


class AdminController extends Controller
{
     public function dashboard(Request $request)
{
   
    $adminId = Session::get('user_id');


    $row = DB::selectOne('SELECT admin_dashboard_data(?) as data', [$adminId]);
    $data = json_decode($row->data);
  

    $totalApplications = $data->loan_stats->total_applications;
    $pendingApplications = $data->loan_stats->pending_applications;
    $activeLoans = $data->loan_stats->active_loans;
    $totalActiveAmount = $data->loan_stats->total_active_amount;
    $totalBorrowers = $data->user_stats->total_borrowers;
    $activeBorrowers = $data->user_stats->active_borrowers;
    $pendingCount = $data->pending_count_for_admin;
    $pendingLoanCount = $data->pending_loan_count_for_admin;
  $recentApplications = collect($data->recent_applications);

$recentUuids = DB::table('loan_applications')
    ->whereIn('application_id', $recentApplications->pluck('application_id')->all())
    ->pluck('uuid', 'application_id');

foreach ($recentApplications as $a) {
    $a->uuid = $recentUuids[$a->application_id] ?? null;
}
    $overdueApplicationsCount = $data->overdue_applications_count;
    $upcomingPaymentsRaw = collect($data->upcoming_payments);

    // ---- Void requests na hinihintay ang boto ng admin na ito ----
    // Hindi kasama: yung siya mismo ang nag-request, at yung naiboto na niya
    // (parehong rules ng notifications).
    $pendingVoidAll = DB::table('payment_void_requests as r')
        ->join('users as req', 'r.requested_by', '=', 'req.user_id')
        ->join('loan_payment_schedules as sch', 'r.schedule_id', '=', 'sch.id')
        ->join('loan_applications as la', 'r.application_id', '=', 'la.application_id')
        ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
        ->where('r.status', 'pending')
        ->where('r.requested_by', '!=', $adminId)
        ->whereNotExists(function ($q) use ($adminId) {
            $q->select(DB::raw(1))->from('payment_void_votes as v')
                ->whereColumn('v.request_id', 'r.id')
                ->where('v.admin_id', $adminId);
        })
        ->select(
            'r.id', 'r.created_at', 'r.amount_snapshot',
            'la.uuid as loan_uuid',
            'req.name as requester_name',
            'sch.installment_no',
            'bpd.first_name', 'bpd.last_name'
        )
        ->orderByDesc('r.created_at')
        ->get()
        ->map(function ($row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $row->borrower_name = $name !== '' ? $name : 'Borrower';
            $row->url = route('admin.ongoing-loan-detail', $row->loan_uuid) . '?from=ongoing-loans';
            return $row;
        });

    $pendingVoidCount = $pendingVoidAll->count();
    $pendingVoidRequests = $pendingVoidAll->take(5);

    // I-share yung badge counts na ito sa cache para hindi na kailangan pang
    // mag-query ulit ang ibang pages (allBorrowers, loanApplications, atbp.)
    // sa loob ng 30s window.
    Cache::put("admin_pending_verif_count_{$adminId}", $pendingCount, 30);
    Cache::put("admin_pending_loan_count_{$adminId}", $pendingLoanCount, 30);

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
            'icon' => 'feather-rotate-ccw',
            'color' => 'warning',
            'label' => 'Void Requests Need Your Vote',
            'count' => $pendingVoidCount,
            'action' => 'Review',
            'url' => $pendingVoidCount > 0
                ? $pendingVoidRequests->first()->url
                : route('admin.ongoing-loans'),
        ],
      
    ];

    $upcomingPayments = $upcomingPaymentsRaw->map(function ($row) {
        $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
        $row->borrower_name = $name !== '' ? $name : $row->fallback_name;
        return $row;
    });

    $recentActivity = Cache::remember('admin_recent_activity', 60, function () {
        return $this->buildRecentActivity(5);
    });

   
   $headerData = $this->getHeaderData($adminId, $data->admin, $data->notifications_data);

    // ---- Payment Overview (Collected / Expected / Outstanding / Overdue) ----
    $overviewMonth = now();
    $currentOverview = $this->computePaymentOverview($overviewMonth);
    $previousOverview = $this->computePaymentOverview($overviewMonth->copy()->subMonth());

    $collectedThisMonth = $currentOverview['collected'];
    $expectedThisMonth = $currentOverview['expected'];
    $outstandingThisMonth = $currentOverview['outstanding'];
    $overdueThisMonth = $currentOverview['overdue'];

    $collectedPctChange = $this->pctChange($collectedThisMonth, $previousOverview['collected']);
    $expectedPctChange = $this->pctChange($expectedThisMonth, $previousOverview['expected']);
    $outstandingPctChange = $this->pctChange($outstandingThisMonth, $previousOverview['outstanding']);
    $overduePctChange = $this->pctChange($overdueThisMonth, $previousOverview['overdue']);

    // ---- NEW: Collected trend (last 6 months) — para sa bar chart sa Payment Overview ----
    $paymentTrend = collect(range(5, 0))->map(function ($i) use ($overviewMonth) {
        $month = $overviewMonth->copy()->subMonths($i);
        $collected = $this->computePaymentOverview($month)['collected'];
        return ['label' => $month->format('M'), 'collected' => $collected];
    });

    $trendMax = max($paymentTrend->max('collected'), 1); // iwas div-by-zero kapag lahat 0

    $paymentTrend = $paymentTrend->map(function ($point) use ($trendMax) {
        $point['pct'] = (int) round(($point['collected'] / $trendMax) * 100);
        return $point;
    })->all();

    // ---- Recent Payments (para sa table sa ilalim ng Payment Overview) ----
    $recentPayments = DB::table('loan_payment_schedules as sch')
        ->join('loan_applications as la', 'sch.application_id', '=', 'la.application_id')
        ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
        ->leftJoin('users as u', 'la.user_id', '=', 'u.user_id')
        ->whereNotNull('sch.paid_at')
        ->select('sch.amount_paid', 'sch.paid_at', 'bpd.first_name', 'bpd.last_name', 'u.name as fallback_name')
        ->orderByDesc('sch.paid_at')
        ->limit(5)
        ->get()
        ->map(function ($row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $row->borrower_name = $name !== '' ? $name : $row->fallback_name;
            return $row;
        });

    return view('admin.dashboard', array_merge(
        $headerData,
        compact(
            'totalApplications', 'pendingApplications',
            'activeLoans', 'totalActiveAmount', 'totalBorrowers', 'activeBorrowers',
            'recentApplications', 'pendingCount', 'pendingLoanCount',
            'attentionItems', 'upcomingPayments', 'recentActivity',
            'overviewMonth', 'collectedThisMonth', 'expectedThisMonth',
            'outstandingThisMonth', 'overdueThisMonth',
            'collectedPctChange', 'expectedPctChange', 'outstandingPctChange', 'overduePctChange',
            'paymentTrend', 'recentPayments',
            'pendingVoidRequests', 'pendingVoidCount'
        )
    ));
}

public function verificationDetail(Request $request, $id)
{
    $adminId = Session::get('user_id');
    $isUuid = Str::isUuid($id);

    $query = DB::table('borrowerpersonaldetails as bpd')
        ->join('users as u', 'bpd.user_id', '=', 'u.user_id')
        ->leftJoin('borroweraddress as ba', 'ba.detail_id', '=', 'bpd.detail_id')
        ->leftJoin('borrowerworkinfo as bwi', 'bwi.detail_id', '=', 'bpd.detail_id')
        ->leftJoin('comakerinfo as ci', 'ci.detail_id', '=', 'bpd.detail_id')
        ->select(
            'bpd.*',
            'u.email as reg_email', 'u.profile_picture', 'u.user_id as borrower_user_id',
            'ba.region', 'ba.province', 'ba.city', 'ba.barangay', 'ba.zipcode', 'ba.home_address',
            'bwi.employment_status', 'bwi.company_name', 'bwi.position', 'bwi.years_in_company',
            'ci.last_name as co_last', 'ci.first_name as co_first', 'ci.middle_name as co_middle',
            'ci.relationship', 'ci.contact_number as co_contact'
        );

    $d = $isUuid
        ? $query->where('bpd.uuid', $id)->first()
        : $query->where('bpd.detail_id', $id)->first();

    if (!$d) {
        return redirect()->route('admin.borrowers');
    }

    $detailId = $d->detail_id;
    $detailUuid = $d->uuid;

    $idFrontUrl = !empty($d->id_file_path)
        ? Storage::disk('valid_ids')->temporaryUrl($d->id_file_path, now()->addMinutes(30))
        : null;
    $idBackUrl = !empty($d->id_file_path_back)
        ? Storage::disk('valid_ids')->temporaryUrl($d->id_file_path_back, now()->addMinutes(30))
        : null;

    $allReviews = DB::table('detailsapproval as da')
        ->join('users as u', 'da.admin_id', '=', 'u.user_id')
        ->where('da.detail_id', $detailId)
        ->select('da.*', 'u.name as admin_name')
        ->orderBy('da.reviewed_at')
        ->get();

    // ---- Loan stats (para sa header card) ----
    $loanStats = [
        'total' => DB::table('loan_applications')->where('user_id', $d->borrower_user_id)->count(),
        'active' => 0,
        'completed' => DB::table('loan_applications')->where('user_id', $d->borrower_user_id)->where('status', 'completed')->count(),
        'overdue' => 0,
        'active_application_id' => null,
    ];
    $activeLoan = DB::table('loan_applications')
        ->where('user_id', $d->borrower_user_id)
        ->whereIn('status', ['active', 'approved'])
        ->first();
    if ($activeLoan) {
        $loanStats['active'] = 1;
        $loanStats['active_application_id'] = $activeLoan->application_id;
    }

    return view('admin.verification-detail', [
        'd' => $d,
        'detailId' => $detailId,
        'detailUuid' => $detailUuid,
        'borrowerProfilePicture' => $this->getProfilePictureUrl((object) ['profile_picture' => $d->profile_picture]),
        'idFrontUrl' => $idFrontUrl,
        'idBackUrl' => $idBackUrl,
        'allReviews' => $allReviews,
        'comments' => collect(),
        'adminId' => $adminId,
        'loanStats' => $loanStats,
        'viewOnly' => true,
        'pendingCount' => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
    ]);
}
    
 public function getHeaderData($adminId, $preloadedAdmin = null, $preloadedNotifData = null)
{
    $admin = $preloadedAdmin ?? Cache::remember("admin_profile_{$adminId}", 60, function () use ($adminId) {
        return DB::table('users')->where('user_id', $adminId)->first();
    });

    if ($preloadedAdmin) {
        Cache::put("admin_profile_{$adminId}", $preloadedAdmin, 60);
    }

    $adminName = $admin->name ?? 'Admin';

    $notifications = Cache::remember("admin_notifications_{$adminId}", 30, function () use ($adminId, $preloadedNotifData) {
        return $this->buildAdminNotifications($adminId, $preloadedNotifData);
    });

    return [
        'adminProfilePicture' => $this->getProfilePictureUrl($admin),
        'adminName' => $adminName,
        'adminRole' => $admin && !empty($admin->role) ? ucfirst($admin->role) : 'Administrator',
        'adminInitials' => $this->getInitials($adminName),
        'adminEmail' => $admin->email ?? '',
        'lastLoginAt' => $admin->last_login_at ?? null,
        'adminIsActive' => $admin ? (bool) ($admin->is_active ?? true) : true,
        'passwordChangedAt' => !empty($admin->password_changed_at)
            ? \Carbon\Carbon::parse($admin->password_changed_at)->format('M d, Y g:i A')
            : null,
        'notifications' => $notifications,
        'unreadNotifCount' => $notifications->where('read', false)->count(),
    ];
}
    // ============ NEW: Account Settings ============

    public function accountSettings()
    {
        $adminId = Session::get('user_id');

        return view('admin.account-settings', array_merge(
            $this->getHeaderData($adminId),
            [
                'pendingCount' => $this->getPendingVerificationCount($adminId),
                'pendingLoanCount' => $this->getPendingLoanCount($adminId),
            ]
        ));
    }

  private function getProfilePictureUrl($admin)
{
    if ($admin && !empty($admin->profile_picture)) {
        return Storage::disk('supabase')->url($admin->profile_picture);
    }
    return null;
}
    

    public function updateProfilePicture(Request $request)
{
    $request->validate([
        'profile_picture' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $adminId = Session::get('user_id');
    $admin = DB::table('users')->where('user_id', $adminId)->first();

    // Alisin muna yung dating picture, kung meron
    if (!empty($admin->profile_picture)) {
        Storage::disk('supabase')->delete($admin->profile_picture);
    }

    $file = $request->file('profile_picture');
    $filename = 'profile-pictures/' . uniqid() . '.' . $file->getClientOriginalExtension();
    Storage::disk('supabase')->put($filename, file_get_contents($file), 'public');

    DB::table('users')->where('user_id', $adminId)->update([
        'profile_picture' => $filename,
    ]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Profile picture updated.',
            'profile_picture_url' => Storage::disk('supabase')->url($filename),
        ]);
    }

    return back()->with('success', 'Profile picture updated.');
}

    public function removeProfilePicture(Request $request)
{
    $adminId = Session::get('user_id');
    $admin = DB::table('users')->where('user_id', $adminId)->first();

    if (!empty($admin->profile_picture)) {
        Storage::disk('supabase')->delete($admin->profile_picture);
    }

    DB::table('users')->where('user_id', $adminId)->update([
        'profile_picture' => null,
    ]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Profile picture removed.',
        ]);
    }

    return back()->with('success', 'Profile picture removed.');
}

    public function updateAccountPassword(Request $request)
{
    $request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:8|confirmed',
    ]);

    $adminId = Session::get('user_id');
    $admin = DB::table('users')->where('user_id', $adminId)->first();

    if (!$admin || !Hash::check($request->current_password, $admin->password_hash)) {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'errors' => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }
        return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
    }

    $changedAt = now();

    DB::table('users')->where('user_id', $adminId)->update([
        'password_hash' => Hash::make($request->new_password),
        'password_changed_at' => $changedAt,
    ]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
            // Server-formatted text — JS just drops this in directly,
            // no guessing/reformatting needed on the frontend.
            'password_changed_at_display' => $changedAt->format('M d, Y g:i A'),
        ]);
    }

    return back()->with('success', 'Password updated successfully.');
}

    // ============ NEW: Notifications ============

    /**

     */
       private function buildAdminNotifications($adminId, $preloaded = null)
{
    $items = collect();

    if ($preloaded) {
        $pendingLoans = collect($preloaded->pending_loans);
        $onHoldLoans = collect($preloaded->on_hold_loans);
        $overdueRows = collect($preloaded->overdue_rows);
        $readKeys = collect($preloaded->read_keys)->flip();
    } else {
        // Fallback — ginagamit ito ng notificationsIndex(), notificationOpen(),
        // notificationsMarkAllRead() na wala namang RPC data.

        $pendingLoans = DB::table('loan_applications as la')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('la.status', 'pending')
            ->whereNotExists(function ($q) use ($adminId) {
                $q->select(DB::raw(1))->from('loan_approvals as lap')
                    ->whereColumn('lap.application_id', 'la.application_id')
                    ->where('lap.admin_id', $adminId);
            })
            ->select('la.application_id', 'la.applied_at', 'bpd.first_name', 'bpd.last_name')
            ->orderByDesc('la.application_id')
            ->limit(20)
            ->get();

        $onHoldLoans = DB::table('loan_applications as la')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('la.status', 'on_hold')
            ->select('la.application_id', 'la.applied_at', 'bpd.first_name', 'bpd.last_name')
            ->orderByDesc('la.application_id')
            ->limit(10)
            ->get();

        $overdueRows = DB::table('loan_payment_schedules as sch')
            ->join('loan_applications as la', 'sch.application_id', '=', 'la.application_id')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
            ->where('la.status', 'active')
            ->whereNull('sch.paid_at')
            ->where('sch.due_date', '<', now())
            ->select('la.application_id', 'sch.due_date', 'bpd.first_name', 'bpd.last_name')
            ->orderBy('sch.due_date')
            ->get()
            ->unique('application_id')
            ->take(20);

        $readKeys = DB::table('admin_notification_reads')
            ->where('admin_id', $adminId)
            ->pluck('notification_key')
            ->flip();
    }

    // Kunin ang uuid ng lahat ng application na lalabas sa notifications (isang query lang).
    // Ginagamit ito sa mga link papunta sa loan-application-detail.
       // ---- Void requests ----
    // Para sa ibang admin: may void request na hinihintay ang boto nila
    $pendingVoids = DB::table('payment_void_requests as r')
        ->join('users as req', 'r.requested_by', '=', 'req.user_id')
        ->join('loan_payment_schedules as sch', 'r.schedule_id', '=', 'sch.id')
        ->where('r.status', 'pending')
        ->where('r.requested_by', '!=', $adminId)
        ->whereNotExists(function ($q) use ($adminId) {
            $q->select(DB::raw(1))->from('payment_void_votes as v')
                ->whereColumn('v.request_id', 'r.id')
                ->where('v.admin_id', $adminId);
        })
        ->select('r.id', 'r.application_id', 'r.created_at', 'req.name as requester_name', 'sch.installment_no')
        ->orderByDesc('r.created_at')
        ->limit(20)
        ->get();

    // Para sa nag-request: approved o rejected na (huling 7 araw)
    $voidResults = DB::table('payment_void_requests as r')
        ->join('loan_payment_schedules as sch', 'r.schedule_id', '=', 'sch.id')
        ->where('r.requested_by', $adminId)
        ->whereIn('r.status', ['approved', 'rejected'])
        ->where('r.resolved_at', '>=', now()->subDays(7))
        ->select('r.id', 'r.application_id', 'r.status', 'r.resolved_at', 'sch.installment_no')
        ->orderByDesc('r.resolved_at')
        ->limit(10)
        ->get();

    // Kunin ang uuid ng lahat ng application na lalabas sa notifications (isang query lang).
    $loanUuids = DB::table('loan_applications')
        ->whereIn('application_id', $pendingLoans->pluck('application_id')
            ->merge($onHoldLoans->pluck('application_id'))
            ->merge($overdueRows->pluck('application_id'))
            ->merge($pendingVoids->pluck('application_id'))
            ->merge($voidResults->pluck('application_id'))
            ->unique()
            ->all())
        ->pluck('uuid', 'application_id');

    foreach ($pendingLoans as $row) {
        $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
        $items->push([
            'key'      => 'loan:' . $row->application_id,
            'title'    => 'Loan Application Decision Needed',
            'subtitle' => $name !== '' ? $name : ('Application #' . $row->application_id),
            'icon'     => 'feather-file-text',
            'color'    => 'primary',
            'url'      => route('admin.loan-application-detail', $loanUuids[$row->application_id]),
            'ts'       => $row->applied_at ?? now(),
        ]);
    }

    foreach ($onHoldLoans as $row) {
        $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
        $items->push([
            'key'      => 'onhold:' . $row->application_id,
            'title'    => 'Application Placed On Hold',
            'subtitle' => $name !== '' ? $name : ('Application #' . $row->application_id),
            'icon'     => 'feather-pause-circle',
            'color'    => 'secondary',
            'url'      => route('admin.loan-application-detail', $loanUuids[$row->application_id]),
            'ts'       => $row->applied_at ?? now(),
        ]);
    }

    foreach ($overdueRows as $row) {
        $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
        $items->push([
            'key'      => 'overdue:' . $row->application_id,
            'title'    => 'Overdue Payment',
            'subtitle' => $name !== '' ? $name : ('Application #' . $row->application_id),
            'icon'     => 'feather-alert-triangle',
            'color'    => 'danger',
            'url'      => route('admin.overdue-payment-detail', $loanUuids[$row->application_id]),
            'ts'       => $row->due_date,
        ]);
    }

        foreach ($pendingVoids as $row) {
        $items->push([
            'key'      => 'void:' . $row->id,
            'title'    => 'Void Request Needs Your Vote',
            'subtitle' => ($row->requester_name ?? 'An admin') . ' · Installment #' . $row->installment_no,
            'icon'     => 'feather-rotate-ccw',
            'color'    => 'warning',
            'url'      => route('admin.ongoing-loan-detail', $loanUuids[$row->application_id]) . '?from=ongoing-loans',
            'ts'       => $row->created_at,
        ]);
    }


    foreach ($voidResults as $row) {
        $approved = $row->status === 'approved';
        $items->push([
            'key'      => 'voidresult:' . $row->id,
            'title'    => $approved ? 'Void Request Approved' : 'Void Request Rejected',
            'subtitle' => 'Installment #' . $row->installment_no,
            'icon'     => $approved ? 'feather-check-circle' : 'feather-x-circle',
            'color'    => $approved ? 'success' : 'danger',
            'url'      => route('admin.ongoing-loan-detail', $loanUuids[$row->application_id]) . '?from=ongoing-loans',
            'ts'       => $row->resolved_at,
        ]);
    }

    return $items
        ->map(function ($n) use ($readKeys) {
            $n['read'] = $readKeys->has($n['key']);
            return $n;
        })
        ->sortByDesc('ts')
        ->values();
}

private function forgetAllAdminNotificationCaches(): void
{
    $adminIds = DB::table('users')->where('role', 'admin')->pluck('user_id');
    foreach ($adminIds as $aid) {
        Cache::forget("admin_notifications_{$aid}");
    }
    Cache::forget('admin_recent_activity');
}

    public function notificationOpen(Request $request, $key)
    {
        $adminId = Session::get('user_id');

        DB::table('admin_notification_reads')->updateOrInsert(
           
            ['admin_id' => $adminId, 'notification_key' => $key],
            ['read_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );

         Cache::forget("admin_notifications_{$adminId}");

       $redirect = $request->query('redirect');
if ($redirect && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
    return redirect($redirect);
}

        return redirect()->route('admin.dashboard');
    }

    public function notificationsMarkAllRead(Request $request)
    {
        $adminId = Session::get('user_id');
        $notifications = $this->buildAdminNotifications($adminId);

        foreach ($notifications->where('read', false) as $n) {
            DB::table('admin_notification_reads')->updateOrInsert(
                ['admin_id' => $adminId, 'notification_key' => $n['key']],
                ['read_at' => now(), 'updated_at' => now(), 'created_at' => now()]
            );
        }
          Cache::forget("admin_notifications_{$adminId}");
        return response()->json(['success' => true]);
    }

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

    public function allBorrowers(Request $request)
    {
        $adminId = Session::get('user_id');
        $sort = $request->query('sort', 'newest');

        $rpcRow = DB::selectOne('SELECT admin_all_borrowers_data(?) as data', [$sort]);
        $data = json_decode($rpcRow->data);

        $borrowers = [];
        foreach (($data->borrowers ?? []) as $row) {
            $fullName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
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
                'loan_status' => $row->loan_status,
                'total_loans' => $row->total_loans,
                'last_activity' => $row->latest_applied_at ?? $row->created_at,
                'has_activity' => $row->latest_status !== null,
                'joined_at' => $row->created_at,
            ];
        }

        $stats = (array) $data->stats;

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


private function computePaymentOverview(\Carbon\Carbon $month)
{
    $start = $month->copy()->startOfMonth();
    $end = $month->copy()->endOfMonth();
 
    // Ledger: lahat ng natanggap na bayad (partial + full) sa buwan na ito,
    // hindi kasama ang na-void na.
    $collected = (float) DB::table('installment_payments')
        ->whereNull('voided_at')
        ->whereBetween('paid_on', [$start->toDateString(), $end->toDateString()])
        ->sum('amount');
 
    $expected = (float) DB::table('loan_payment_schedules')
        ->whereBetween('due_date', [$start, $end])
        ->sum('amount_due');
 
    $outstanding = (float) DB::table('loan_payment_schedules')
        ->whereNull('paid_at')
        ->where('due_date', '<=', $end)
        ->selectRaw('COALESCE(SUM(amount_due - amount_paid), 0) as total')
        ->value('total');
 
    $overdue = (float) DB::table('loan_payment_schedules')
        ->whereNull('paid_at')
        ->where('due_date', '<', $end->copy()->min(now()))
        ->selectRaw('COALESCE(SUM(amount_due - amount_paid), 0) as total')
        ->value('total');
 
    return compact('collected', 'expected', 'outstanding', 'overdue');
}

private function pctChange($current, $previous)
{
    if ((float) $previous == 0.0) {
        return (float) $current == 0.0 ? 0 : 100;
    }
    return (int) round((($current - $previous) / $previous) * 100);
}
public function ongoingLoans(Request $request)
{
    $adminId = Session::get('user_id');
    $sort = $request->query('sort', 'newest');

    $rpcRow = DB::selectOne('SELECT admin_ongoing_loans_data() as data');
    $data = json_decode($rpcRow->data);

    $ongoingLoans = collect($data->ongoing_loans ?? [])->map(function ($row) {
        $fullName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
        $displayName = $fullName !== '' ? $fullName : $row->email;
        $initials = $fullName !== ''
            ? strtoupper(substr($row->first_name, 0, 1) . substr($row->last_name, 0, 1))
            : strtoupper(substr($row->email, 0, 2));

        return [
            'application_id'   => $row->application_id,
            'user_id'          => $row->user_id,
            'name'             => $displayName,
            'initials'         => $initials,
            'email'            => $row->email,
            'loan_amount'      => $row->loan_amount,
            'total_amount'     => $row->total_amount,
            'loan_term'        => $row->loan_term,
            'applied_at'       => $row->applied_at,
            'paid_count'       => $row->paid_count,
            'total_count'      => $row->total_count,
            'next_due_date'    => $row->next_due_date,
            'next_due_amount'  => $row->next_due_amount,
            'amount_collected' => $row->amount_collected,
        ];
    });

    // uuid ng bawat loan
    $uuids = DB::table('loan_applications')
        ->whereIn('application_id', $ongoingLoans->pluck('application_id')->all())
        ->pluck('uuid', 'application_id');

    // uuid ng bawat borrower (galing sa borrowerpersonaldetails)
    $borrowerUuids = DB::table('borrowerpersonaldetails')
        ->whereIn('user_id', $ongoingLoans->pluck('user_id')->unique()->all())
        ->pluck('uuid', 'user_id');

    $ongoingLoans = $ongoingLoans->map(function ($loan) use ($uuids, $borrowerUuids) {
        $loan['uuid'] = $uuids[$loan['application_id']] ?? null;

        $borrowerUuid = $borrowerUuids[$loan['user_id']] ?? null;
        $loan['borrower_id'] = $borrowerUuid
            ? 'BRW-' . strtoupper(substr($borrowerUuid, 0, 8))
            : '—';

        return $loan;
    });

    $ongoingLoans = $ongoingLoans->sortBy(
        fn ($loan) => strtotime($loan['applied_at']),
        SORT_REGULAR,
        $sort !== 'oldest' // descending gamit 'newest' (default)
    )->values()->all();

    $stats = (array) $data->stats;

    return view('admin.ongoing-loans', [
        'ongoingLoans'     => $ongoingLoans,
        'stats'            => $stats,
        'sort'             => $sort,
        'pendingCount'     => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
    ]);
}
     public function ongoingLoanDetail(Request $request, $uuid)
{
    $adminId = Session::get('user_id');

    $applicationId = (int) DB::table('loan_applications')
        ->where('uuid', $uuid)
        ->value('application_id');

    if (!$applicationId) {
        return redirect()->route('admin.ongoing-loans');
    }

    $app = $this->getLoanApplication($applicationId);

    if (!$app) {
        return redirect()->route('admin.ongoing-loans');
    }

    $schedule = $this->getPaymentSchedule($applicationId);

    $totalCount = $schedule->count();
    $paidCount = $schedule->filter(fn($s) => !is_null($s->paid_at))->count();
    $amountCollected = $schedule->sum('amount_paid');

    $monthlyPayment = $totalCount > 0 ? ($app->total_amount / $totalCount) : 0;
    $totalInterest = max(0, $app->total_amount - $app->loan_amount);

    $term = (int) ($app->loan_term ?: $totalCount);
    $interestRate = ($app->loan_amount > 0 && $term > 0)
        ? round(($totalInterest / $app->loan_amount / $term) * 100, 2)
        : 0;

    $borrowerReceipts = DB::table('payment_receipts')
        ->where('application_id', $applicationId)
        ->where('status', 'submitted')
        ->get()
        ->keyBy('schedule_id');

    $voidRequests = DB::table('payment_void_requests as r')
        ->join('users as u', 'r.requested_by', '=', 'u.user_id')
        ->join('loan_payment_schedules as sch', 'r.schedule_id', '=', 'sch.id')
        ->where('r.application_id', $applicationId)
        ->where('r.status', 'pending')
        ->select('r.*', 'u.name as requester_name', 'sch.installment_no')
        ->get()
        ->keyBy('schedule_id');

    $voidHistory = DB::table('payment_void_requests as r')
        ->join('users as u', 'r.requested_by', '=', 'u.user_id')
        ->join('loan_payment_schedules as sch', 'r.schedule_id', '=', 'sch.id')
        ->where('r.application_id', $applicationId)
        ->where('r.status', '!=', 'pending')
        ->select('r.*', 'u.name as requester_name', 'sch.installment_no')
        ->orderByDesc('r.resolved_at')
        ->limit(10)
        ->get();

    $voidVotes = DB::table('payment_void_votes as v')
        ->join('users as u', 'v.admin_id', '=', 'u.user_id')
        ->whereIn('v.request_id', $voidRequests->pluck('id')->merge($voidHistory->pluck('id'))->all())
        ->select('v.*', 'u.name as admin_name')
        ->orderBy('v.reviewed_at')
        ->get()
        ->groupBy('request_id');

    // ---- Bagong Apply for a Loan form: address, source of income, labels, at signed URLs
    // ng ID / selfie / payslip. Parehong helpers ng loanApplicationDetail().
    $address = $this->getBorrowerAddress($app->detail_id);
    $work = $this->getBorrowerWork($app->detail_id);
    $appDisplay = $this->applicationDisplayData($app, $work);
    $appDocs = $this->applicationDocuments($app, $work);

    // B2: Lahat ng bayad (partial + full) kada installment, para sa Payment Details
    $paymentsBySchedule = DB::table('installment_payments')
        ->where('application_id', $applicationId)
        ->whereNull('voided_at')
        ->orderBy('paid_on')
        ->orderBy('id')
        ->get()
        ->groupBy('schedule_id')
        ->map(fn ($rows) => $this->ledgerPayload($rows));

    return view('admin.ongoing-loan-detail', [
        'app' => $app,
        'address' => $address,
        'work' => $work,
        'appDisplay' => $appDisplay,
        'appDocs' => $appDocs,
        'applicationId' => $applicationId,
        'adminId' => $adminId,
        'borrowerProfilePicture' => $this->getProfilePictureUrl($app),
        'allReviews' => $this->getAllLoanReviews($applicationId),
        'comments' => $this->getLoanComments($applicationId),
        'paymentSchedule' => $schedule,
        'borrowerReceipts' => $borrowerReceipts,
        'paymentsBySchedule' => $paymentsBySchedule, // B2
        'totalCount' => $totalCount,
        'paidCount' => $paidCount,
        'amountCollected' => $amountCollected,
        'monthlyPayment' => $monthlyPayment,
        'totalInterest' => $totalInterest,
        'interestRate' => $interestRate,
        'voidRequests' => $voidRequests,
        'voidHistory' => $voidHistory,
        'voidVotes' => $voidVotes,
        'requiredVoidApprovals' => self::REQUIRED_VOID_APPROVALS,
        'voidWindowDays' => self::VOID_WINDOW_DAYS,
        'pendingCount' => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
    ]);
}
          public function overduePayments(Request $request)
    {
        $adminId = Session::get('user_id');
        $sort = $request->query('sort', 'most_late');

        $rpcRow = DB::selectOne('SELECT admin_overdue_payments_data(?) as data', [self::DEFAULT_DAYS_LATE]);
        $data = json_decode($rpcRow->data);

        $overdueLoans = collect($data->overdue_loans ?? [])->map(function ($row) {
            $fullName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $displayName = $fullName !== '' ? $fullName : $row->email;
            $initials = $fullName !== ''
                ? strtoupper(substr($row->first_name, 0, 1) . substr($row->last_name, 0, 1))
                : strtoupper(substr($row->email, 0, 2));

            return [
                'application_id'  => $row->application_id,
                'user_id'         => $row->user_id,
                'name'            => $displayName,
                'initials'        => $initials,
                'email'           => $row->email,
                'loan_amount'     => $row->loan_amount,
                'total_amount'    => $row->total_amount,
                'applied_at'      => $row->applied_at,
                'overdue_count'   => $row->overdue_count,
                'total_count'     => $row->total_count,
                'oldest_due_date' => $row->oldest_due_date,
                'days_late'       => $row->days_late,
                'overdue_amount'  => $row->overdue_amount,
                'is_defaulted'    => $row->is_defaulted,
            ];
        });

        // uuid ng bawat loan (para sa links)
        $uuids = DB::table('loan_applications')
            ->whereIn('application_id', $overdueLoans->pluck('application_id')->all())
            ->pluck('uuid', 'application_id');

        // uuid ng bawat borrower (galing sa borrowerpersonaldetails)
        $borrowerUuids = DB::table('borrowerpersonaldetails')
            ->whereIn('user_id', $overdueLoans->pluck('user_id')->unique()->all())
            ->pluck('uuid', 'user_id');

        $overdueLoans = $overdueLoans->map(function ($loan) use ($uuids, $borrowerUuids) {
            $loan['uuid'] = $uuids[$loan['application_id']] ?? null;

            $borrowerUuid = $borrowerUuids[$loan['user_id']] ?? null;
            $loan['borrower_id'] = $borrowerUuid
                ? 'BRW-' . strtoupper(substr($borrowerUuid, 0, 8))
                : '—';

            return $loan;
        });

        $overdueLoans = $overdueLoans->sortBy(function ($loan) use ($sort) {
            if ($sort === 'newest') return -strtotime($loan['applied_at']);
            if ($sort === 'oldest') return strtotime($loan['applied_at']);
            return -$loan['days_late']; // most_late (default)
        })->values()->all();

        $stats = (array) $data->stats;

        return view('admin.overdue-payments', [
            'overdueLoans'     => $overdueLoans,
            'stats'            => $stats,
            'sort'             => $sort,
            'pendingCount'     => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }
   private function getPendingVerificationCount($adminId)
{
    return DB::table('borrowerpersonaldetails as bpd')
        ->where('bpd.status', 'pending')
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
            ->where('la.status', 'pending')
            ->whereNotExists(function ($query) use ($adminId) {
                $query->select(DB::raw(1))
                    ->from('loan_approvals as lap')
                    ->whereColumn('lap.application_id', 'la.application_id')
                    ->where('lap.admin_id', $adminId);
            })
            ->count();
    }

private function sendLoanStatusEmail(int $applicationId, string $status): void
{
    if (!in_array($status, ['approved', 'rejected', 'on_hold'], true)) {
        return;
    }

    try {
        $loan = DB::table('loan_applications')
            ->where('application_id', $applicationId)
            ->first();

        if (!$loan) {
            return;
        }

        $borrower = DB::table('users as u')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'u.user_id')
            ->where('u.user_id', $loan->user_id)
            ->select('u.email', 'u.name', 'bpd.first_name')
            ->first();

        if (!$borrower || empty($borrower->email)) {
            return;
        }

        $displayName = $borrower->first_name ?: ($borrower->name ?: 'Borrower');

        Mail::to($borrower->email)->send(
            new LoanStatusUpdated($loan, $displayName, $status)
        );
    } catch (\Throwable $e) {
        // Hindi dapat pumalya ang approval dahil lang sa email.
        Log::warning("Loan status email failed (application {$applicationId}): " . $e->getMessage());
    }
}

private function sendPaymentRecordedEmail($schedule, int $applicationId, bool $isFullyPaid, ?float $receivedNow = null): void
{
    try {
        $loan = DB::table('loan_applications')
            ->where('application_id', $applicationId)
            ->first();
 
        if (!$loan) {
            return;
        }
 
        $borrower = DB::table('users as u')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'u.user_id')
            ->where('u.user_id', $loan->user_id)
            ->select('u.email', 'u.name', 'bpd.first_name')
            ->first();
 
        if (!$borrower || empty($borrower->email)) {
            return;
        }
 
        $displayName = $borrower->first_name ?: ($borrower->name ?: 'Borrower');
 
        // Susunod na unpaid installment (null kung fully paid na)
        $nextUnpaid = DB::table('loan_payment_schedules')
            ->where('application_id', $applicationId)
            ->whereNull('paid_at')
            ->orderBy('installment_no')
            ->first();
 
        // Kung walang ibinigay na amount, balik sa buong amount_due (lumang behavior)
        $amountForEmail = $receivedNow !== null && $receivedNow > 0
            ? $receivedNow
            : (float) $schedule->amount_due;
 
        Mail::to($borrower->email)->send(
            new PaymentRecorded(
                $loan,
                $displayName,
                (int) $schedule->installment_no,
                $amountForEmail,
                $isFullyPaid,
                $nextUnpaid ? (string) $nextUnpaid->due_date : null,
                $nextUnpaid ? (float) $nextUnpaid->amount_due : null,
            )
        );
    } catch (\Throwable $e) {
        // Hindi dapat pumalya ang pag-record ng payment dahil lang sa email.
        Log::warning("Payment recorded email failed (schedule {$schedule->id}): " . $e->getMessage());
    }
}

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

        return redirect()->route('admin.loan-applications')
            ->with('success', true)
            ->with('decisionStatus', $newStatus);
    }

    $viewStatus = $request->query('status', 'pending');

    return view('admin.loan-applications', array_merge(
        $this->buildLoanApplicationsListData($adminId, $viewStatus),
        [
            'viewStatus' => $viewStatus,
            'pendingCount' => $this->getPendingVerificationCount($adminId),
        ]
    ));
}
     public function loanApplicationDetail(Request $request, $uuid)
{
    $adminId = Session::get('user_id');

    // UUID lang ang nasa URL. Ang integer na application_id ay internal na lang.
    $internalId = DB::table('loan_applications')
        ->where('uuid', $uuid)
        ->value('application_id');

    abort_if(!$internalId, 404);

    $app = $this->getLoanApplication($internalId);

    abort_if(!$app, 404);

    if ($request->isMethod('post')) {
        if ($request->filled('comment')) {
            $this->saveLoanComment($internalId, $adminId, $request->input('comment'));
            return redirect()->route('admin.loan-application-detail', $uuid);
        }

        if ($request->filled('action')) {
            // Integer ang kailangan ng saveLoanDecision, hindi ang uuid
            $this->saveLoanDecision($request->merge(['application_id' => $internalId]), $adminId);

            $newStatus = DB::table('loan_applications')
                ->where('application_id', $internalId)
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

    // Address at source of income ng borrower
    $address = $this->getBorrowerAddress($app->detail_id);
    $work = $this->getBorrowerWork($app->detail_id);

    // Readable labels (galing sa config/loan_labels.php) imbes na raw codes
    $appDisplay = $this->applicationDisplayData($app, $work);

    // Signed URLs (private bucket, 30 minutes validity) para sa Front ID, Back ID,
    // Selfie with ID at Pay Slip. Walang file = null, kaya hindi nag-e-error ang
    // page sa mga lumang application.
    $appDocs = $this->applicationDocuments($app, $work);

    return view('admin.loan-application-detail', [
        'app' => $app,
        'address' => $address,
        'work' => $work,
        'appDisplay' => $appDisplay,
        'appDocs' => $appDocs,
        'applicationId' => $uuid, // uuid na para gumana ang route() sa Blade forms
        'adminId' => $adminId,
        'borrowerProfilePicture' => $this->getProfilePictureUrl($app),
        'idFrontUrl' => $appDocs['front']['url'] ?? null,
        'idBackUrl' => $appDocs['back']['url'] ?? null,
        'myReview' => $this->getMyLoanReview($internalId, $adminId),
        'allReviews' => $this->getAllLoanReviews($internalId),
        'approvedCount' => $this->getLoanApprovedCount($internalId),
        'comments' => $this->getLoanComments($internalId),
        'paymentSchedule' => $this->getPaymentSchedule($internalId),
        'pendingCount' => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        'viewStatus' => $app->status,
        // Borrower info editing
        'canEditInfo' => $this->canEditBorrowerInfo($app),
        'infoEdits' => $this->buildBorrowerInfoEdits($app->detail_id, $app, $address, $work),
        'editOptions' => $this->borrowerEditOptions(),
    ]);
}
    public function activeBorrowerDetail(Request $request, $id)
    {
        $adminId = Session::get('user_id');
        $applicationId = (int) $id;

        $app = $this->getLoanApplication($applicationId);

        if (!$app) {
            return redirect()->route('admin.active-borrowers');
        }

        $schedule = $this->getPaymentSchedule($applicationId);

        $totalCount = $schedule->count();
        $paidCount = $schedule->filter(fn($s) => !is_null($s->paid_at))->count();
        $progressPct = $totalCount > 0 ? (int) round(($paidCount / $totalCount) * 100) : 0;

        $overdueSchedules = $schedule->filter(function ($s) {
            return is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
        });

        $maxDaysLate = $overdueSchedules->isNotEmpty()
            ? $overdueSchedules->max(fn($s) => \Carbon\Carbon::parse($s->due_date)->diffInDays(now()))
            : 0;

        if ($overdueSchedules->count() === 0) {
            $standing = 'good';
            $standingLabel = 'Good Standing';
        } elseif ($overdueSchedules->count() === 1 && $maxDaysLate < 30) {
            $standing = 'delayed';
            $standingLabel = 'Payment Delayed';
        } else {
            $standing = 'risk';
            $standingLabel = 'At Risk';
        }

        return view('admin.active-borrower-detail', [
            'app' => $app,
            'applicationId' => $applicationId,
            'paymentSchedule' => $schedule,
            'totalCount' => $totalCount,
            'paidCount' => $paidCount,
            'progressPct' => $progressPct,
            'standing' => $standing,
            'standingLabel' => $standingLabel,
            'pendingCount' => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }

    public function overduePaymentDetail(Request $request, $uuid)
{
    $adminId = Session::get('user_id');

    // UUID lang ang nasa URL. Ang integer na application_id ay internal na lang.
    $applicationId = (int) DB::table('loan_applications')
        ->where('uuid', $uuid)
        ->value('application_id');

    if (!$applicationId) {
        return redirect()->route('admin.overdue-payments');
    }

    $app = $this->getLoanApplication($applicationId);

    if (!$app) {
        return redirect()->route('admin.overdue-payments');
    }

    $schedule = $this->getPaymentSchedule($applicationId);

    $overdueSchedules = $schedule->filter(function ($s) {
        return is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
    })->sortBy('due_date')->values();

    $oldestOverdue = $overdueSchedules->first();

    $currentPenalty = 0.0;
    $penaltyId = null;

    // Gamit ang existing na PenaltyHelper (₱50 kada 7 days, galing config/penalty.php)
    // para laging updated ang penalty pagbukas ng page, at may row na para sa Waive.
    foreach ($overdueSchedules as $s) {
        $calc = PenaltyHelper::calculatePenalty($s);

        if ($calc['amount'] <= 0) {
            continue; // wala pang 7 days
        }

        $row = DB::table('loan_penalties')->where('schedule_id', $s->id)->first();

        if (!$row) {
            DB::table('loan_penalties')->insert([
                'schedule_id'      => $s->id,
                'application_id'   => $s->application_id,
                'days_late'        => $calc['days_late'],
                'late_fee_periods' => $calc['periods'],
                'penalty_amount'   => $calc['amount'],
                'status'           => 'unpaid',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } elseif ($row->status === 'unpaid') {
            // Huwag galawin ang paid/waived na
            DB::table('loan_penalties')->where('id', $row->id)->update([
                'days_late'        => $calc['days_late'],
                'late_fee_periods' => $calc['periods'],
                'penalty_amount'   => $calc['amount'],
                'updated_at'       => now(),
            ]);
        }

        if ($s->id === $oldestOverdue->id) {
            $current = DB::table('loan_penalties')
                ->where('schedule_id', $s->id)
                ->where('status', 'unpaid')
                ->first();

            if ($current) {
                $currentPenalty = (float) $current->penalty_amount;
                $penaltyId = $current->id;
            }
        }
    }

    $amountDue = $oldestOverdue ? ((float) $oldestOverdue->amount_due - (float) $oldestOverdue->amount_paid) : 0.0;
    $totalOutstanding = $amountDue + $currentPenalty;

    // Receipt na in-upload ng borrower para sa installment na ito (hinihintay pa ang admin).
    // Null kung wala, kaya hindi lalabas ang "View Receipt" button.
    $borrowerReceipt = $oldestOverdue
        ? DB::table('payment_receipts')
            ->where('schedule_id', $oldestOverdue->id)
            ->where('status', 'submitted')
            ->orderByDesc('submitted_at')
            ->first()
        : null;

    return view('admin.overdue-payment-detail', [
        'app' => $app,
        'applicationId' => $applicationId,
        'loanUuid' => $uuid,
        'borrowerProfilePicture' => $this->getProfilePictureUrl($app),
        'paymentSchedule' => $schedule,
        'overdueCount' => $overdueSchedules->count(),
        'daysLate' => $oldestOverdue ? \Carbon\Carbon::parse($oldestOverdue->due_date)->diffInDays(now()) : 0,
        'overdueAmount' => $overdueSchedules->sum(fn($s) => $s->amount_due - $s->amount_paid),
        'oldestOverdue' => $oldestOverdue,
        'currentPenalty' => $currentPenalty,
        'penaltyId' => $penaltyId,
        'amountDue' => $amountDue,
        'totalOutstanding' => $totalOutstanding,
        'borrowerReceipt' => $borrowerReceipt,
        'pendingCount' => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
    ]);
}

     public function markInstallmentPaid(Request $request, $id)
    {
        $wantsJson = $request->ajax() || $request->wantsJson();
 
        $schedule = DB::table('loan_payment_schedules')->where('id', $id)->first();
 
        if (!$schedule) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'Installment not found.'], 404);
            }
            return back()->with('error', 'Installment not found.');
        }
 
        if (!is_null($schedule->paid_at)) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'This installment is already marked as paid.'], 422);
            }
            return back()->with('error', 'This installment is already marked as paid.');
        }
 
        $useBorrowerReceipt = $request->boolean('use_borrower_receipt');
 
        $request->validate([
            'paid_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount'    => ['nullable', 'numeric', 'gt:0'],
        ], [
            'paid_date.before_or_equal' => 'The payment date cannot be in the future.',
            'amount.gt'                 => 'The amount must be greater than zero.',
        ]);
 
        $requestedAmount = $request->filled('amount') ? round((float) $request->input('amount'), 2) : null;
 
        $paidAt = $request->filled('paid_date')
            ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('paid_date'))->startOfDay()
            : now();
 
        $receiptPath = null;
        if ($useBorrowerReceipt) {
            $borrowerReceipt = DB::table('payment_receipts')
                ->where('schedule_id', $id)
                ->where('status', 'submitted')
                ->first();
 
            if (!$borrowerReceipt || empty($borrowerReceipt->user_file_path)) {
                $msg = 'The borrower receipt was not found.';
                if ($wantsJson) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }
 
            $ext = pathinfo($borrowerReceipt->user_file_path, PATHINFO_EXTENSION) ?: 'jpg';
            $receiptPath = 'payment-receipts/' . $schedule->application_id . '/'
                . $id . '-' . Str::random(12) . '.' . $ext;
 
            try {
                $contents = Storage::disk(self::BORROWER_RECEIPT_DISK)->get($borrowerReceipt->user_file_path);
                Storage::disk(self::RECEIPT_DISK)->put($receiptPath, $contents);
            } catch (\Throwable $e) {
                Log::error("Borrower receipt copy failed (schedule {$id}): " . $e->getMessage());
                $msg = 'Failed to attach the borrower receipt. Please try again.';
                if ($wantsJson) {
                    return response()->json(['success' => false, 'message' => $msg], 500);
                }
                return back()->with('error', $msg);
            }
        }
 
        $applicationId = $schedule->application_id;
        $adminId = Session::get('user_id');
 
        $receivedNow = 0.0;
        $isPartial = false;
        $newAmountPaid = 0.0;
        $remainingBalance = 0.0;
 
        try {
            DB::transaction(function () use ($id, $paidAt, $receiptPath, $adminId, $requestedAmount, &$receivedNow, &$isPartial, &$newAmountPaid, &$remainingBalance, &$schedule) {
                $locked = DB::table('loan_payment_schedules')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->first();
 
                if (!$locked || !is_null($locked->paid_at)) {
                    throw new \RuntimeException('This installment is already marked as paid.');
                }
 
                $schedule = $locked;
 
                $balance = max(0.0, round((float) $locked->amount_due - (float) $locked->amount_paid, 2));
                $receivedNow = $requestedAmount ?? $balance;
 
                if ($receivedNow > $balance) {
                    throw new \RuntimeException('The amount cannot be more than the remaining balance of ₱' . number_format($balance, 2) . '.');
                }
 
                $isPartial = $receivedNow < $balance;
                $newAmountPaid = round((float) $locked->amount_paid + $receivedNow, 2);
                $remainingBalance = round($balance - $receivedNow, 2);
 
                $update = [
                    'amount_paid' => $newAmountPaid,
                    'updated_at'  => now(),
                ];
 
                if (!$isPartial) {
                    $update['paid_at'] = $paidAt;
                    $update['amount_paid'] = $locked->amount_due;
                    if ($receiptPath) {
                        $update['receipt_path'] = $receiptPath;
                    }
                }
 
                DB::table('loan_payment_schedules')->where('id', $id)->update($update);
 
                if ($receivedNow > 0) {
                    DB::table('installment_payments')->insert([
                        'schedule_id'    => $locked->id,
                        'application_id' => $locked->application_id,
                        'amount'         => $receivedNow,
                        'paid_on'        => $paidAt->toDateString(),
                        'kind'           => $isPartial ? 'partial' : 'full',
                        'receipt_path'   => $receiptPath,
                        'admin_id'       => $adminId,
                        'created_at'     => now(),
                    ]);
                }
 
                if (!$isPartial) {
                    DB::table('loan_penalties')
                        ->where('schedule_id', $id)
                        ->where('status', 'unpaid')
                        ->update([
                            'status'     => 'paid',
                            'updated_at' => now(),
                        ]);
                }
 
                DB::table('payment_receipts')
                    ->where('schedule_id', $id)
                    ->where('status', 'submitted')
                    ->update([
                        'status'             => 'verified',
                        'amount_recorded'    => $receivedNow,
                        'recorded_paid_date' => $paidAt->toDateString(),
                        'reviewed_by'        => $adminId,
                        'reviewed_at'        => now(),
                        'updated_at'         => now(),
                    ]);
            });
        } catch (\RuntimeException $e) {
            if ($receiptPath) {
                try { Storage::disk(self::RECEIPT_DISK)->delete($receiptPath); } catch (\Throwable $ignored) {}
            }
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            if ($receiptPath) {
                try { Storage::disk(self::RECEIPT_DISK)->delete($receiptPath); } catch (\Throwable $ignored) {}
            }
            throw $e;
        }
 
        $hasUnpaid = DB::table('loan_payment_schedules')
            ->where('application_id', $applicationId)
            ->whereNull('paid_at')
            ->exists();
 
        if (!$hasUnpaid) {
            DB::table('loan_applications')
                ->where('application_id', $applicationId)
                ->update(['status' => 'completed']);
        }
 
        if ($isPartial) {
            $this->sendPartialPaymentEmail($schedule, (int) $applicationId, $receivedNow, $remainingBalance);
        } else {
            $this->sendPaymentRecordedEmail($schedule, (int) $applicationId, !$hasUnpaid, $receivedNow);
        }
 
        if ($wantsJson) {
            $app = DB::table('loan_applications')->where('application_id', $applicationId)->first();
            $allSchedules = DB::table('loan_payment_schedules')
                ->where('application_id', $applicationId)
                ->orderBy('installment_no')
                ->get();
 
            $paidCount = $allSchedules->filter(fn($s) => !is_null($s->paid_at))->count();
            $totalCount = $allSchedules->count();
            $amountCollected = (float) $allSchedules->sum('amount_paid');
            $remaining = max(0, ($app->total_amount ?? 0) - $amountCollected);
            $nextUnpaid = $allSchedules->first(fn($s) => is_null($s->paid_at));
            $progressPct = $totalCount > 0 ? round(($paidCount / $totalCount) * 100) : 0;
 
            $dueInLabel = '—';
            if ($nextUnpaid) {
                $dueDate = \Carbon\Carbon::parse($nextUnpaid->due_date);
                if ($dueDate->isPast()) {
                    $dueInLabel = 'Overdue';
                } else {
                    $days = (int) now()->diffInDays($dueDate);
                    $dueInLabel = $days . ' ' . ($days == 1 ? 'day' : 'days');
                }
            }
 
            $overdueSchedulesNow = $allSchedules->filter(function ($s) {
                return is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
            })->sortBy('due_date')->values();
 
            $oldestOverdueNow = $overdueSchedulesNow->first();
            $daysLateNow = $oldestOverdueNow
                ? \Carbon\Carbon::parse($oldestOverdueNow->due_date)->diffInDays(now())
                : 0;
            $outstandingAmountNow = $overdueSchedulesNow->sum(fn($s) => $s->amount_due - $s->amount_paid);
            $unpaidPenaltiesNow = (float) DB::table('loan_penalties')
                ->where('application_id', $applicationId)
                ->where('status', 'unpaid')
                ->sum('penalty_amount');
 
            return response()->json([
                'success' => true,
                'message' => $isPartial ? 'Partial payment recorded.' : 'Payment marked as paid.',
                'is_partial' => $isPartial,
                'remaining_balance_display' => number_format($remainingBalance, 2),
                'schedule_id' => (int) $id,
                'amount_paid_display' => number_format($isPartial ? $newAmountPaid : (float) $schedule->amount_due, 2),
                'received_now_display' => number_format($receivedNow, 2),
                'payments' => $this->ledgerPayload(
                    DB::table('installment_payments')
                        ->where('schedule_id', $id)
                        ->whereNull('voided_at')
                        ->orderBy('paid_on')
                        ->orderBy('id')
                        ->get()
                ),
                'paid_on_display' => $paidAt->format('M d, Y'),
                'receipt_url' => $receiptPath ? route('admin.installment-receipt', $id) : null,
                'paid_count' => $paidCount,
                'total_count' => $totalCount,
                'amount_collected_display' => number_format($amountCollected, 2),
                'remaining_display' => number_format($remaining, 2),
                'progress_pct' => $progressPct,
                'next_due_date_display' => $nextUnpaid ? \Carbon\Carbon::parse($nextUnpaid->due_date)->format('M d, Y') : '—',
                'due_in_label' => $dueInLabel,
                'is_fully_paid' => is_null($nextUnpaid),
                'loan_status' => $app->status ?? null,
                'days_late' => $daysLateNow,
                'outstanding_amount_display' => number_format($outstandingAmountNow, 2),
                'unpaid_penalties_display' => number_format($unpaidPenaltiesNow, 2),
            ]);
        }
 
        return back()->with('success', $isPartial ? 'Partial payment recorded.' : 'Payment marked as paid.');
    }

public function installmentReceipt($id)
{
    $path = DB::table('loan_payment_schedules')->where('id', $id)->value('receipt_path');

    if (!$path) {
        abort(404);
    }

    return redirect(
        Storage::disk(self::RECEIPT_DISK)->temporaryUrl($path, now()->addMinutes(10))
    );
}

public function installmentPaymentReceipt($id)
{
    $path = DB::table('installment_payments')->where('id', $id)->value('receipt_path');

    if (!$path) {
        abort(404);
    }

    return redirect(
        Storage::disk(self::RECEIPT_DISK)->temporaryUrl($path, now()->addMinutes(10))
    );
}

/** Ledger rows -> array para sa Payment Details modal. */
private function ledgerPayload($rows): array
{
    return collect($rows)->map(function ($p) {
        return [
            'date'   => \Carbon\Carbon::parse($p->paid_on)->format('M d, Y'),
            'amount' => number_format((float) $p->amount, 2),
            'kind'   => $p->kind,
            'url'    => !empty($p->receipt_path) ? route('admin.installment-payment-receipt', $p->id) : '',
        ];
    })->values()->all();
}   

public function borrowerReceiptFile(Request $request, $id)
{
    $receipt = DB::table('payment_receipts')->where('receipt_id', $id)->first();

    if (!$receipt || empty($receipt->user_file_path)) {
        abort(404);
    }

    $disk = Storage::disk(self::BORROWER_RECEIPT_DISK);

    // ?download=1 -> dadaan sa Laravel para talagang ma-download
    if ($request->boolean('download')) {
        $ext = pathinfo($receipt->user_file_path, PATHINFO_EXTENSION) ?: 'jpg';
        return $disk->download($receipt->user_file_path, "receipt-installment-{$receipt->installment_no}.{$ext}");
    }

    return redirect($disk->temporaryUrl($receipt->user_file_path, now()->addMinutes(10)));
}

public function requestVoid(Request $request, $id)
{
    $request->validate([
        'reason' => 'required|string|min:5|max:500',
    ], [
        'reason.required' => 'Please enter a reason.',
        'reason.min'      => 'Please enter a reason (at least 5 characters).',
    ]);

    $adminId = Session::get('user_id');
    $reason = trim($request->input('reason'));

    try {
        $result = DB::transaction(function () use ($id, $adminId, $reason) {
            $schedule = DB::table('loan_payment_schedules')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$schedule) {
                throw new \RuntimeException('Installment not found.');
            }
                        if (is_null($schedule->paid_at)) {
                throw new \RuntimeException('This installment is not recorded as paid.');
            }

            // Sa unang 7 araw lang pagkatapos i-record ang payment pwedeng mag-request ng void.
            $windowEnd = $this->voidWindowEnd($schedule);
            if (now()->gt($windowEnd)) {
                throw new \RuntimeException(
                    'The ' . self::VOID_WINDOW_DAYS . '-day period to void this payment ended on '
                    . $windowEnd->format('M d, Y') . '.'
                );
            }
            $hasPending = DB::table('payment_void_requests')
                ->where('schedule_id', $id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPending) {
                throw new \RuntimeException('There is already a pending void request for this installment.');
            }

            $requestId = DB::table('payment_void_requests')->insertGetId([
                'schedule_id'           => $schedule->id,
                'application_id'        => $schedule->application_id,
                'requested_by'          => $adminId,
                'reason'                => $reason,
                'amount_snapshot'       => $schedule->amount_paid,
                'paid_at_snapshot'      => \Carbon\Carbon::parse($schedule->paid_at)->toDateString(),
                'receipt_path_snapshot' => $schedule->receipt_path,
                'status'                => 'pending',
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            DB::table('payment_void_votes')->insert([
                'request_id'  => $requestId,
                'admin_id'    => $adminId,
                'decision'    => 'approved',
                'remarks'     => null,
                'reviewed_at' => now(),
            ]);

            return ['request_id' => $requestId, 'schedule' => $schedule];
        });
    } catch (\RuntimeException $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (\Illuminate\Database\QueryException $e) {
        if ($e->getCode() === '23505') {
            return response()->json([
                'success' => false,
                'message' => 'There is already a pending void request for this installment.',
            ], 422);
        }
        Log::error("Void request failed (schedule {$id}): " . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Failed to submit the void request.'], 500);
    } catch (\Throwable $e) {
        Log::error("Void request failed (schedule {$id}): " . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Failed to submit the void request.'], 500);
    }

    return response()->json([
        'success'    => true,
        'status'     => 'pending',
        'request_id' => $result['request_id'],
        'message'    => 'Void request submitted. Waiting for the other admins to vote.',
    ]);
}

public function voteVoid(Request $request, $id)
{
  $request->validate([
    'decision' => 'required|in:approved,rejected',
    'remarks'  => 'required_if:decision,rejected|nullable|string|min:3|max:255',
], [
    'remarks.required_if' => 'Please enter a reason for rejecting this void request.',
    'remarks.min'         => 'Remarks must be at least 3 characters.',
]);

    $adminId = Session::get('user_id');
    $decision = $request->input('decision');
    $remarks = trim((string) $request->input('remarks', ''));

    try {
        $result = DB::transaction(function () use ($id, $adminId, $decision, $remarks) {
            $voidRequest = DB::table('payment_void_requests')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$voidRequest) {
                throw new \RuntimeException('Void request not found.');
            }
            if ($voidRequest->status !== 'pending') {
                throw new \RuntimeException('This void request is already ' . $voidRequest->status . '.');
            }

            $alreadyVoted = DB::table('payment_void_votes')
                ->where('request_id', $id)
                ->where('admin_id', $adminId)
                ->exists();

            if ($alreadyVoted) {
                throw new \RuntimeException('You have already voted on this request.');
            }

            DB::table('payment_void_votes')->insert([
                'request_id'  => $id,
                'admin_id'    => $adminId,
                'decision'    => $decision,
                'remarks'     => $remarks !== '' ? $remarks : null,
                'reviewed_at' => now(),
            ]);

            if ($decision === 'rejected') {
                DB::table('payment_void_requests')->where('id', $id)->update([
                    'status'      => 'rejected',
                    'resolved_at' => now(),
                    'updated_at'  => now(),
                ]);

                return ['status' => 'rejected', 'schedule' => null, 'reason' => null];
            }

            $approvals = DB::table('payment_void_votes')
                ->where('request_id', $id)
                ->where('decision', 'approved')
                ->count();

            if ($approvals < self::REQUIRED_VOID_APPROVALS) {
                return ['status' => 'pending', 'schedule' => null, 'reason' => null, 'approvals' => $approvals];
            }

            $schedule = $this->executeVoid($voidRequest);

            return ['status' => 'approved', 'schedule' => $schedule, 'reason' => $voidRequest->reason];
        });
    } catch (\RuntimeException $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (\Throwable $e) {
        Log::error("Void vote failed (request {$id}): " . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Failed to record your vote.'], 500);
    }

    if ($result['status'] === 'approved' && $result['schedule']) {
        $this->sendPaymentVoidedEmail($result['schedule'], $result['reason']);
    }

    $messages = [
        'approved' => 'Void approved by all admins. The payment has been voided.',
        'rejected' => 'Void request rejected. The payment remains recorded as paid.',
        'pending'  => 'Your vote has been recorded.',
    ];

    return response()->json([
        'success'            => true,
        'status'             => $result['status'],
        'approvals'          => $result['approvals'] ?? null,
        'required_approvals' => self::REQUIRED_VOID_APPROVALS,
        'message'            => $messages[$result['status']],
    ]);
}

public function cancelVoid(Request $request, $id)
{
    $adminId = Session::get('user_id');

    try {
        DB::transaction(function () use ($id, $adminId) {
            $voidRequest = DB::table('payment_void_requests')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$voidRequest) {
                throw new \RuntimeException('Void request not found.');
            }
            if ($voidRequest->status !== 'pending') {
                throw new \RuntimeException('This void request is already ' . $voidRequest->status . '.');
            }
            if ((int) $voidRequest->requested_by !== (int) $adminId) {
                throw new \RuntimeException('Only the admin who requested the void can cancel it.');
            }

            DB::table('payment_void_requests')->where('id', $id)->update([
                'status'      => 'cancelled',
                'resolved_at' => now(),
                'updated_at'  => now(),
            ]);
        });
    } catch (\RuntimeException $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (\Throwable $e) {
        Log::error("Cancel void failed (request {$id}): " . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Failed to cancel the void request.'], 500);
    }

    return response()->json(['success' => true, 'message' => 'Void request cancelled.']);
}

private function executeVoid($voidRequest)
{
    $schedule = DB::table('loan_payment_schedules')
        ->where('id', $voidRequest->schedule_id)
        ->lockForUpdate()
        ->first();
 
    if (!$schedule) {
        throw new \RuntimeException('Installment not found.');
    }
    if (is_null($schedule->paid_at)) {
        throw new \RuntimeException('This installment is no longer recorded as paid.');
    }
 
    DB::table('installment_payment_logs')->insert([
        'installment_id' => $schedule->id,
        'action'         => 'voided',
        'amount'         => $schedule->amount_paid,
        'paid_at'        => \Carbon\Carbon::parse($schedule->paid_at)->toDateString(),
        'receipt_path'   => $schedule->receipt_path,
        'reason'         => $voidRequest->reason,
        'admin_id'       => $voidRequest->requested_by,
        'request_id'     => $voidRequest->id,
        'created_at'     => now(),
    ]);
 
    DB::table('loan_payment_schedules')
        ->where('id', $schedule->id)
        ->update([
            'paid_at'      => null,
            'amount_paid'  => 0,
            'receipt_path' => null,
        ]);
 
    // Ledger: i-void ang lahat ng rows ng installment na ito (partial + full),
    // kasi ibinabalik sa 0 ang amount_paid.
    DB::table('installment_payments')
        ->where('schedule_id', $schedule->id)
        ->whereNull('voided_at')
        ->update([
            'voided_at'       => now(),
            'void_request_id' => $voidRequest->id,
        ]);
 
    DB::table('loan_applications')
        ->where('application_id', $schedule->application_id)
        ->where('status', 'completed')
        ->update(['status' => 'active']);
 
    DB::table('payment_void_requests')->where('id', $voidRequest->id)->update([
        'status'      => 'approved',
        'resolved_at' => now(),
        'updated_at'  => now(),
    ]);
 
    return $schedule;
}
    /**
     * Kailan nagtatapos ang pwedeng pag-void ng isang recorded na payment.
     * Nagsisimula ang bilang sa "Recorded" date (updated_at, na sine-set ng
     * markInstallmentPaid()), hindi sa Date Paid na pinili ng admin.
     */
    private function voidWindowEnd($schedule): \Carbon\Carbon
    {
        $recordedAt = $schedule->updated_at ?? $schedule->paid_at;

        return \Carbon\Carbon::parse($recordedAt)->addDays(self::VOID_WINDOW_DAYS);
    }

    private function getPaymentSchedule($applicationId)
    {
        return DB::table('loan_payment_schedules')
            ->where('application_id', $applicationId)
            ->orderBy('installment_no')
            ->get();
    }

    /**
     * POST /admin/loans/{applicationId}/send-proof
     * Emails the borrower the proof of loan release (ISANG BESES lang per loan —
     * resibo na na-release na ang loan na nirequest ng borrower; hindi na per installment).
     * Expects: proof (JPG/PNG, max 5MB).
     */
       public function sendProofToBorrower(Request $request, $applicationId)
    {
        $request->validate([
            'proof' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ], [
            'proof.required' => 'Please attach the proof.',
            'proof.mimes'    => 'The proof must be a JPG or PNG image.',
            'proof.max'      => 'The proof may not be larger than 5MB.',
        ]);

        $applicationId = (int) $applicationId;

        $loan = DB::table('loan_applications')
            ->where('application_id', $applicationId)
            ->first();

        if (!$loan) {
            return response()->json(['success' => false, 'message' => 'Loan not found.'], 404);
        }

        // Approved na dapat ang loan bago ma-release
        if (!in_array($loan->status, ['approved', 'active'], true)) {
            return response()->json(['success' => false, 'message' => 'This loan is not approved yet.'], 422);
        }

        // Isang beses lang per loan
        if (!empty($loan->proof_sent_at)) {
            return response()->json(['success' => false, 'message' => 'The proof of release was already sent for this loan.'], 422);
        }

        $borrower = DB::table('users as u')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'u.user_id')
            ->where('u.user_id', $loan->user_id)
            ->select('u.email', 'u.name', 'bpd.first_name')
            ->first();

        if (!$borrower || empty($borrower->email)) {
            return response()->json(['success' => false, 'message' => 'The borrower has no email address on file.'], 422);
        }

        $displayName = $borrower->first_name ?: ($borrower->name ?: 'Borrower');
        $file = $request->file('proof');
        $ext = $file->guessExtension() ?: 'jpg';

        // Parehong format ng admin page: LN- + unang 8 chars ng uuid (hal. LN-34782CDF)
        $loanCode = 'LN-' . strtoupper(substr($loan->uuid, 0, 8));

        // ---- STEP 1: markahan na "sent" AT gawin ang payment schedule ----
        // Iisang transaction ito. Kapag pumalya ang isa, walang maiiwan.
        // Kapag dalawang admin ang sabay na nag-click, isa lang ang makakalusot.
        $sentAt = now();

        try {
            $claimed = DB::transaction(function () use ($applicationId, $sentAt) {
                $updated = DB::table('loan_applications')
                    ->where('application_id', $applicationId)
                    ->whereNull('proof_sent_at')
                    ->update(['proof_sent_at' => $sentAt]);

                if ($updated === 0) {
                    return false;
                }

                // Ang petsa ng release (ngayon) ang simula ng schedule
                \App\Services\LoanActivationService::createSchedule($applicationId, $sentAt);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error("Create payment schedule failed (loan {$applicationId}): " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create the payment schedule. Please try again.'], 500);
        }

        if (!$claimed) {
            return response()->json(['success' => false, 'message' => 'The proof of release was already sent for this loan.'], 422);
        }

        // ---- STEP 2: ipadala ang email ----
        try {
            Mail::to($borrower->email)->send(new LoanProofSent(
                $loan,
                $displayName,
                $file->getRealPath(),
                $file->getMimeType(),
                "proof-of-release-{$loanCode}.{$ext}"
            ));
        } catch (\Throwable $e) {
            Log::error("Send proof of release failed (loan {$applicationId}): " . $e->getMessage());

            // Pumalya ang email, kaya ibalik sa dati para makapag-send ulit:
            // burahin ang schedule (bago pa lang, wala pang bayad) at i-null ang proof_sent_at.
            try {
                DB::transaction(function () use ($applicationId) {
                    DB::table('loan_payment_schedules')->where('application_id', $applicationId)->delete();
                    DB::table('loan_applications')->where('application_id', $applicationId)->update(['proof_sent_at' => null]);
                });
            } catch (\Throwable $undoError) {
                Log::error("Undo after failed proof email also failed (loan {$applicationId}): " . $undoError->getMessage());
            }

            return response()->json(['success' => false, 'message' => 'Failed to send the email. Please try again.'], 500);
        }

        Log::info("Proof of release emailed + schedule created (loan {$applicationId}) by admin " . Session::get('user_id'));

        return response()->json([
            'success' => true,
            'message' => "Proof sent to {$borrower->email}. The payment schedule is now created.",
            'sent_at_display' => $sentAt->format('M d, Y g:i A'),
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

    private function sendPaymentVoidedEmail($schedule, string $reason = ''): void
{
    try {
        $loan = DB::table('loan_applications')
            ->where('application_id', $schedule->application_id)
            ->first();

        if (!$loan) {
            return;
        }

        $borrower = DB::table('users as u')
            ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'u.user_id')
            ->where('u.user_id', $loan->user_id)
            ->select('u.email', 'u.name', 'bpd.first_name')
            ->first();

        if (!$borrower || empty($borrower->email)) {
            return;
        }

        $displayName = $borrower->first_name ?: ($borrower->name ?: 'Borrower');

        Mail::to($borrower->email)->send(
            new PaymentVoided(
                $loan,
                $displayName,
                (int) $schedule->installment_no,
                (float) $schedule->amount_paid,
                now(),
                $reason,
            )
        );
    } catch (\Throwable $e) {
        // Hindi dapat pumalya ang pag-void dahil lang sa email.
        Log::warning("Payment voided email failed (schedule {$schedule->id}): " . $e->getMessage());
    }
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
        $request->validate([
            'action' => 'required|in:approved,rejected',
        ]);

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
    // Kunin ang status BAGO magbago, para malaman kung nagbago talaga ang desisyon.
    $previousStatus = DB::table('loan_applications')
        ->where('application_id', $applicationId)
        ->value('status');

    $counts = DB::table('loan_approvals')
        ->where('application_id', $applicationId)
        ->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END) as a,
            SUM(CASE WHEN decision = 'rejected' THEN 1 ELSE 0 END) as r
        ")
        ->first();

    $requiredReviewers = 3;

    if ($counts->total < $requiredReviewers) {
        $status = 'pending';
    } elseif ($counts->a >= 3) {
        $status = 'approved';
    } elseif ($counts->r >= 3) {
        $status = 'rejected';
    } elseif ($counts->r > 0) {
        $status = 'on_hold';
    } else {
        $status = 'pending';
    }

    DB::table('loan_applications')->where('application_id', $applicationId)->update(['status' => $status]);

    if ($status === 'approved') {
        LoanActivationService::activate($applicationId);
    }

    // Mag-email lang kapag talagang nagbago ang desisyon.
    // Ang 'active' ay binibilang na 'approved', kaya kapag na-activate na ang loan
    // at may admin na nag-save ulit ng parehong desisyon, hindi uulit ang email.
    $before = $previousStatus === 'active' ? 'approved' : $previousStatus;

    if ($status !== $before) {
        $this->sendLoanStatusEmail((int) $applicationId, $status);
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
                'u.profile_picture', // NEW — needed for the avatar image
                'bpd.detail_id', 'bpd.first_name', 'bpd.last_name', 'bpd.middle_name', 'bpd.suffix',
                'bpd.birthday', 'bpd.gender', 'bpd.civil_status', 'bpd.citizenship',
                'bpd.cellphone_num', 'bpd.monthly_income',
                'bpd.id_type', 'bpd.id_type_others', 'bpd.id_number',
                'bpd.id_file_path', 'bpd.id_file_path_back', 'bpd.selfie_id_path',
                'bpd.birth_country', 'bpd.birth_province', 'bpd.birth_city'
            )
            ->first();
    }

    
    // ================================================================
    // Edit borrower info (Loan Application Detail)
    // ================================================================

    /**
     * Fields an admin may correct. Locked on purpose: email (it is the borrower's
     * login), employment status (it decides which fields apply), ID type and documents.
     * 'src' tells us where the CURRENT value lives: app = result of getLoanApplication(),
     * address = borroweraddress row, work = borrowerworkinfo row.
     */
    private const BORROWER_INFO_FIELDS = [
        // ---- personal ----
        'first_name'     => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'First name',       'rules' => ['required', 'string', 'max:50']],
        'middle_name'    => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Middle name',      'rules' => ['nullable', 'string', 'max:50']],
        'last_name'      => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Last name',        'rules' => ['required', 'string', 'max:50']],
        'suffix'         => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Suffix',           'rules' => ['nullable', 'string', 'max:10']],
        'birthday'       => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Birthday',         'rules' => ['required', 'date_format:Y-m-d', 'before:today']],
        'birth_city'     => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Birth city',       'rules' => ['nullable', 'string', 'max:100']],
        'birth_province' => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Birth province',   'rules' => ['nullable', 'string', 'max:100']],
        'birth_country'  => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Birth country',    'rules' => ['nullable', 'string', 'max:100']],
        'gender'         => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Gender',           'rules' => ['required', 'string', 'max:30']],
        'civil_status'   => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Civil status',     'rules' => ['required', 'string', 'max:30']],
        'citizenship'    => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Citizenship',      'rules' => ['required', 'string', 'max:60']],
        'cellphone_num'  => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'personal', 'label' => 'Cellphone',        'rules' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/']],

        // ---- address ----
        'region'          => ['table' => 'borroweraddress', 'src' => 'address', 'section' => 'address', 'label' => 'Region',               'rules' => ['required', 'string', 'max:100']],
        'province'        => ['table' => 'borroweraddress', 'src' => 'address', 'section' => 'address', 'label' => 'Province',             'rules' => ['required', 'string', 'max:100']],
        'city'            => ['table' => 'borroweraddress', 'src' => 'address', 'section' => 'address', 'label' => 'City / Municipality', 'rules' => ['required', 'string', 'max:100']],
        'barangay'        => ['table' => 'borroweraddress', 'src' => 'address', 'section' => 'address', 'label' => 'Barangay',             'rules' => ['required', 'string', 'max:100']],
        'zipcode'         => ['table' => 'borroweraddress', 'src' => 'address', 'section' => 'address', 'label' => 'Zipcode',              'rules' => ['nullable', 'string', 'max:10']],
        'house_no'        => ['table' => 'borroweraddress', 'src' => 'address', 'section' => 'address', 'label' => 'House / Building No.', 'rules' => ['nullable', 'string', 'max:255']],
        'street'          => ['table' => 'borroweraddress', 'src' => 'address', 'section' => 'address', 'label' => 'Street',               'rules' => ['nullable', 'string', 'max:255']],
        'subdivision'     => ['table' => 'borroweraddress', 'src' => 'address', 'section' => 'address', 'label' => 'Subdivision / Village', 'rules' => ['nullable', 'string', 'max:255']],
        'address_details' => ['table' => 'borroweraddress', 'src' => 'address', 'section' => 'address', 'label' => 'Additional details',  'rules' => ['nullable', 'string', 'max:255']],
        'home_address'    => ['table' => 'borroweraddress', 'src' => 'address', 'section' => 'address', 'label' => 'Home address',        'rules' => ['nullable', 'string', 'max:255']],

        // ---- source of income ----
        'company_name'     => ['table' => 'borrowerworkinfo',        'src' => 'work', 'section' => 'income', 'label' => 'Company / School',        'rules' => ['nullable', 'string', 'max:150']],
        'position'         => ['table' => 'borrowerworkinfo',        'src' => 'work', 'section' => 'income', 'label' => 'Job Title',               'rules' => ['nullable', 'string', 'max:100']],
        'years_in_company' => ['table' => 'borrowerworkinfo',        'src' => 'work', 'section' => 'income', 'label' => 'Length of employment',    'rules' => ['nullable', 'string', 'max:50']],
        'primary_source'   => ['table' => 'borrowerworkinfo',        'src' => 'work', 'section' => 'income', 'label' => 'Primary source of funds', 'rules' => ['nullable', 'string', 'max:100']],
        'other_sources'    => ['table' => 'borrowerworkinfo',        'src' => 'work', 'section' => 'income', 'label' => 'Other sources of income', 'rules' => ['nullable', 'string', 'max:255']],
        'monthly_income'   => ['table' => 'borrowerpersonaldetails', 'src' => 'app',  'section' => 'income', 'label' => 'Monthly income',          'rules' => ['nullable', 'string', 'max:50']],

        // ---- valid ID ----
        'id_number' => ['table' => 'borrowerpersonaldetails', 'src' => 'app', 'section' => 'id', 'label' => 'ID number', 'rules' => ['required', 'string', 'max:50']],
    ];

    /** Only applications that are still being reviewed can be corrected. */
    private function canEditBorrowerInfo($app): bool
    {
        return in_array($app->status, ['pending', 'on_hold'], true);
    }

    /** Dropdown options for the edit mode (value => label). */
       /** Dropdown options for the edit mode (value => label). */
    private function borrowerEditOptions(): array
    {
        return [
            'gender' => ['male' => 'Male', 'female' => 'Female'],
            // Dapat tugma sa borrowerpersonaldetails_civil_status_check sa database
            'civil_status' => [
                'single' => 'Single',
                'taken' => 'Taken',
                'married' => 'Married',
                'widowed' => 'Widowed',
                'separated' => 'Separated',
            ],
            'years_in_company' => config('loan_labels.years_in_company', []),
            'primary_source'   => config('loan_labels.primary_sources', []),
        ];
    }

    /** Current stored value of one editable field (normalized for comparison). */
    private function borrowerInfoCurrentValue(string $key, $app, $address, $work)
    {
        $src = self::BORROWER_INFO_FIELDS[$key]['src'];
        $row = $src === 'address' ? $address : ($src === 'work' ? $work : $app);

        $value = $row->{$key} ?? null;

        if ($key === 'birthday' && !empty($value)) {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return $value;
            }
        }

        return $value;
    }

    /** Human-readable value for the page note and the email. */
    private function borrowerInfoDisplay(string $key, $value, bool $mask = false): string
    {
        $v = trim((string) $value);

        if ($v === '') {
            return '—';
        }

        switch ($key) {
            case 'birthday':
                try {
                    return \Carbon\Carbon::parse($v)->format('F d, Y');
                } catch (\Throwable $e) {
                    return $v;
                }
            case 'gender':
            case 'civil_status':
                return ucfirst($v);
            case 'years_in_company':
                return config('loan_labels.years_in_company', [])[$v] ?? $v;
            case 'primary_source':
                return $this->labelFor('primary_sources', $v);
            case 'id_number':
                return $mask ? str_repeat('*', max(0, strlen($v) - 3)) . substr($v, -3) : $v;
            default:
                return $v;
        }
    }

    /**
     * Latest admin edit per field of this borrower, for the yellow highlight.
     * A field is only highlighted while its current value still equals what the
     * admin saved (if the borrower changed it again later, the highlight goes away).
     */
    private function buildBorrowerInfoEdits($detailId, $app, $address, $work): array
    {
        if (!$detailId) {
            return [];
        }

        try {
            $rows = DB::table('borrower_info_edits as e')
                ->join('users as u', 'e.admin_id', '=', 'u.user_id')
                ->where('e.detail_id', $detailId)
                ->orderBy('e.id')
                ->select('e.field', 'e.old_value', 'e.new_value', 'e.created_at', 'u.name as admin_name')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Could not load borrower_info_edits: ' . $e->getMessage());
            return [];
        }

        $latest = [];
        foreach ($rows as $r) {
            $latest[$r->field] = $r; // later rows overwrite earlier ones
        }

        $out = [];
        foreach ($latest as $key => $r) {
            if (!isset(self::BORROWER_INFO_FIELDS[$key])) {
                continue;
            }

            $current = trim((string) $this->borrowerInfoCurrentValue($key, $app, $address, $work));
            if ($current !== trim((string) $r->new_value)) {
                continue;
            }

            $out[$key] = [
                'admin' => $r->admin_name,
                'at'    => \Carbon\Carbon::parse($r->created_at)->format('M d, Y'),
                'ts'    => strtotime($r->created_at),
                'old'   => $this->borrowerInfoDisplay($key, $r->old_value),
            ];
        }

        return $out;
    }

    /**
     * POST /admin/loan-application-detail/{uuid}/borrower-info
     * Expects: section (personal|address|income|id), fields {key: value}, reason.
     */
         public function updateBorrowerInfo(Request $request, $uuid)
    {
        $request->validate([
            'section' => 'required|in:personal,address,income,id',
            'fields'  => 'required|array|min:1',
            'reason'  => 'required|string|min:5|max:255',
        ], [
            'reason.required' => 'Please enter a reason for the change.',
            'reason.min'      => 'Please enter a reason (at least 5 characters).',
        ]);

        $adminId = Session::get('user_id');

        $applicationId = (int) DB::table('loan_applications')->where('uuid', $uuid)->value('application_id');
        $app = $applicationId ? $this->getLoanApplication($applicationId) : null;

        if (!$app) {
            return response()->json(['success' => false, 'message' => 'Application not found.'], 404);
        }

        if (!$this->canEditBorrowerInfo($app)) {
            return response()->json([
                'success' => false,
                'message' => 'Borrower information can only be edited while the application is pending or on hold.',
            ], 422);
        }

        $section = $request->input('section');
        $reason = trim((string) $request->input('reason'));

        // Keep only known fields that belong to this section
        $values = [];
        $rules = [];
        foreach ((array) $request->input('fields') as $key => $value) {
            $def = self::BORROWER_INFO_FIELDS[$key] ?? null;
            if (!$def || $def['section'] !== $section) {
                continue;
            }
            $values[$key] = is_string($value) ? trim($value) : $value;
            $rules[$key] = $def['rules'];
        }

        if (!$values) {
            return response()->json(['success' => false, 'message' => 'Nothing to save.'], 422);
        }

        // Dropdown fields: only accept the configured options (when options exist)
        $options = $this->borrowerEditOptions();
        foreach (['years_in_company', 'primary_source'] as $selectKey) {
            if (isset($rules[$selectKey]) && !empty($options[$selectKey])) {
                $rules[$selectKey][] = Rule::in(array_keys($options[$selectKey]));
            }
        }

        // Same rule as savePersonalDetails(): an ID number can only belong to one borrower
        if (isset($rules['id_number'])) {
            $rules['id_number'][] = Rule::unique('borrowerpersonaldetails', 'id_number')
                ->ignore($app->detail_id, 'detail_id');
        }

        $validator = Validator::make($values, $rules, [
            'birthday.before'     => 'The birthday must be a date in the past.',
            'cellphone_num.regex' => 'Please enter a valid cellphone number.',
            'id_number.unique'    => 'This ID number is already used by another borrower.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Work out what really changed
        $address = $this->getBorrowerAddress($app->detail_id);
        $work = $this->getBorrowerWork($app->detail_id);

        $changes = [];
        foreach ($values as $key => $new) {
            $old = trim((string) $this->borrowerInfoCurrentValue($key, $app, $address, $work));
            $new = trim((string) $new);

            if ($old === $new) {
                continue;
            }

            $changes[$key] = ['def' => self::BORROWER_INFO_FIELDS[$key], 'old' => $old, 'new' => $new];
        }

        // Kapag hindi na "Others" ang Primary Source, hindi na dapat manatili ang
        // lumang Other Sources of Income sa DB (nakatago lang sa page). Gagawin itong
        // null, at lalabas din sa edit log at sa email sa borrower.
        if (isset($changes['primary_source']) && $changes['primary_source']['new'] !== 'others') {
            $oldOther = trim((string) $this->borrowerInfoCurrentValue('other_sources', $app, $address, $work));

            if ($oldOther !== '') {
                $changes['other_sources'] = [
                    'def' => self::BORROWER_INFO_FIELDS['other_sources'],
                    'old' => $oldOther,
                    'new' => '',
                ];
            } else {
                // Walang dapat i-save: huwag payagang may isulat sa other_sources
                unset($changes['other_sources']);
            }
        }

        if (!$changes) {
            return response()->json(['success' => false, 'message' => 'No changes to save.'], 422);
        }

        $batchId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($changes, $app, $applicationId, $adminId, $section, $reason, $batchId) {
                $byTable = [];
                foreach ($changes as $key => $c) {
                    $byTable[$c['def']['table']][$key] = $c['new'] !== '' ? $c['new'] : null;
                }

                foreach ($byTable as $table => $data) {
                    if ($table === 'borrowerpersonaldetails') {
                        DB::table($table)->where('detail_id', $app->detail_id)->update($data);
                    } else {
                        DB::table($table)->updateOrInsert(['detail_id' => $app->detail_id], $data);
                    }
                }

                $now = now();
                $logRows = [];
                foreach ($changes as $key => $c) {
                    $logRows[] = [
                        'batch_id'       => $batchId,
                        'application_id' => $applicationId,
                        'detail_id'      => $app->detail_id,
                        'user_id'        => $app->user_id,
                        'admin_id'       => $adminId,
                        'section'        => $section,
                        'field'          => $key,
                        'field_label'    => $c['def']['label'],
                        'old_value'      => $c['old'] !== '' ? $c['old'] : null,
                        'new_value'      => $c['new'] !== '' ? $c['new'] : null,
                        'reason'         => $reason,
                        'created_at'     => $now,
                    ];
                }
                DB::table('borrower_info_edits')->insert($logRows);
            });
        } catch (\Throwable $e) {
            Log::error("Borrower info edit failed (application {$applicationId}): " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to save the changes. Please try again.'], 500);
        }

        Log::info("Borrower info edited (application {$applicationId}) by admin {$adminId}: " . implode(', ', array_keys($changes)));

        $emailed = $this->sendBorrowerInfoEditedEmail($app, $changes, $reason);

        $count = count($changes);
        $message = $count . ' ' . ($count === 1 ? 'field' : 'fields') . ' updated. '
            . ($emailed ? 'The borrower was notified by email.' : 'The email to the borrower could not be sent.');

        return response()->json(['success' => true, 'message' => $message]);
    }
    private function sendBorrowerInfoEditedEmail($app, array $changes, string $reason): bool
    {
        try {
            if (empty($app->email)) {
                return false;
            }

            $rows = [];
            foreach ($changes as $key => $c) {
                $rows[] = [
                    'label' => $c['def']['label'],
                    'old'   => $this->borrowerInfoDisplay($key, $c['old'], true),
                    'new'   => $this->borrowerInfoDisplay($key, $c['new'], true),
                ];
            }

            $displayName = ($changes['first_name']['new'] ?? null) ?: ($app->first_name ?: 'Borrower');

            Mail::to($app->email)->send(new BorrowerInfoEdited($app, $displayName, $rows, $reason));

            return true;
        } catch (\Throwable $e) {
            // The edit must not fail just because the email failed.
            Log::warning("Borrower info edited email failed (application {$app->application_id}): " . $e->getMessage());
            return false;
        }
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

    // ================================================================
    // Apply for a Loan form — labels at documents (para sa admin review)
    // ================================================================

    /**
     * Gawing readable na label ang isang code galing sa config/loan_labels.php.
     * Kung wala sa listahan (hal. lumang code), ipakita ang code na nilinis
     * (underscores -> spaces). Walang laman = '—'.
     */
    private function labelFor(string $group, $code, string $fallback = '—'): string
    {
        if (is_string($code)) {
            $code = trim($code);
        }

        if ($code === null || $code === '') {
            return $fallback;
        }

        $labels = config("loan_labels.{$group}", []);

        return $labels[$code] ?? ucfirst(str_replace('_', ' ', (string) $code));
    }

    /**
     * Lahat ng readable na label/values na kailangan ng admin review page.
     * $app = result ng getLoanApplication(), $work = result ng getBorrowerWork() (pwedeng null).
     */
   private function applicationDisplayData($app, $work): array
{
    // ---- Loan: purpose (kasama ang "Others" na detalye) ----
    $purpose = $this->labelFor('loan_purposes', $app->loan_purpose ?? null);
    if (($app->loan_purpose ?? null) === 'others' && !empty($app->loan_purpose_other)) {
        $purpose = 'Others — ' . $app->loan_purpose_other;
    }

    // ---- Loan: referral source at pangalan ng nag-refer ----
    $referralSource = $app->referral_source ?? null;
    $referral = $this->labelFor('referral_sources', $referralSource);
    $referralName = !empty($app->referral_name) ? trim($app->referral_name) : null;

    if ($referralName !== null) {
        if ($referralSource === 'friend_family') {
            $referral .= ' — referred by ' . $referralName;
        } elseif ($referralSource === 'others') {
            $referral = 'Others — ' . $referralName;
        } else {
            $referral .= ' — ' . $referralName;
        }
    }

    // ---- Valid ID type (kasama ang "Others" na detalye) ----
    $idType = $this->labelFor('id_types', $app->id_type ?? null);
    if (($app->id_type ?? null) === 'others' && !empty($app->id_type_others)) {
        $idType = 'Others — ' . $app->id_type_others;
    }

    // ---- Source of income ----
    $status = $work->employment_status ?? null;

    $isEmployed = $status === 'employed';
    $isSelfEmployed = $status === 'self_employed';
    $hasJob = $isEmployed || $isSelfEmployed;
    $showFunds = in_array($status, ['student', 'unemployed', 'retired'], true);
    $showBilling = in_array($status, ['unemployed', 'retired', 'others'], true);
    $primarySource = $work->primary_source ?? null;

    // Kapag "Others" ang status, ipakita ang isinulat ng borrower
    $statusLabel = $this->labelFor('employment_statuses', $status);
    if ($status === 'others' && !empty($work->employment_status_other ?? null)) {
        $statusLabel = 'Others — ' . $work->employment_status_other;
    }

    $years = $work->years_in_company ?? null;
    $yearsLabel = '—';
    if ($years !== null && $years !== '') {
        $configured = config('loan_labels.years_in_company', []);
        if (isset($configured[$years])) {
            $yearsLabel = $configured[$years];
        } elseif (is_numeric($years)) {
            // Lumang application: numero lang ang naka-save
            $yearsLabel = $years . ' ' . ((int) $years === 1 ? 'year' : 'years');
        } else {
            $yearsLabel = (string) $years;
        }
    }

    $workData = [
        'has_data'           => !empty($work),
        'status_label'       => $statusLabel,
        'show_company'       => $hasJob || $status === 'student',
        'company_label'      => config("loan_labels.company_name_labels.{$status}", 'Company / School'),
        'company'            => !empty($work->company_name ?? null) ? $work->company_name : '—',
        'show_job'           => $hasJob,
        'position_label'     => 'Job Title',
        'position'           => !empty($work->position ?? null) ? $work->position : '—',
        'years_label'        => config("loan_labels.years_labels.{$status}", 'Years'),
        'years'              => $yearsLabel,
        'show_funds'         => $showFunds,
        'primary_source'     => $this->labelFor('primary_sources', $primarySource),
        // Lalabas lang kapag "Others" ang Primary Source of Funds
        'show_other_sources' => $showFunds && $primarySource === 'others',
        'other_sources'      => !empty($work->other_sources ?? null) ? $work->other_sources : '—',
        'show_payslip'       => $hasJob,
        // Proof of billing: Unemployed, Retired, Others
        'show_billing'       => $showBilling,
    ];

    return [
        'purpose'  => $purpose,
        'referral' => $referral,
        'id_type'  => $idType,
        'work'     => $workData,
    ];
}

    /**
     * Signed URLs (30 minutes) para sa Front ID, Back ID, Selfie with ID at Pay Slip.
     * Private ang disk 'valid_ids', kaya hindi pwedeng direktang URL.
     * Walang file (o pumalya ang pag-sign) = null, para hindi mag-error ang page.
     */
    private function applicationDocuments($app, $work): array
{
    $make = function ($path) {
        if (empty($path)) {
            return null;
        }

        try {
            $url = Storage::disk('valid_ids')->temporaryUrl($path, now()->addMinutes(30));
        } catch (\Throwable $e) {
            Log::warning("Could not sign URL for document {$path}: " . $e->getMessage());
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return [
            'url'      => $url,
            'is_image' => in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
        ];
    };

    return [
        'front'   => $make($app->id_file_path ?? null),
        'back'    => $make($app->id_file_path_back ?? null),
        'selfie'  => $make($app->selfie_id_path ?? null),
        'payslip' => $make($work->payslip_path ?? null),
        'billing' => $make($work->billing_path ?? null),
    ];
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

        private function buildLoanApplicationsListData($adminId, $viewStatus = 'pending')
    {
        $row = DB::selectOne('SELECT admin_loan_applications_list_data(?, ?) as data', [$adminId, $viewStatus]);
        $data = json_decode($row->data);

        $pendingRaw = collect($data->pending_applications ?? []);
        $reviewedApplications = collect($data->reviewed_applications ?? []);
        $filteredApplications = collect($data->filtered_applications ?? []);

        // Isang query para kunin ang uuid ng lahat ng application sa tatlong listahan
        $uuids = DB::table('loan_applications')
            ->whereIn('application_id', $pendingRaw->pluck('application_id')
                ->merge($reviewedApplications->pluck('application_id'))
                ->merge($filteredApplications->pluck('application_id'))
                ->unique()
                ->values()
                ->all())
            ->pluck('uuid', 'application_id');

        foreach ($reviewedApplications as $a) {
            $a->uuid = $uuids[$a->application_id] ?? null;
        }
        foreach ($filteredApplications as $a) {
            $a->uuid = $uuids[$a->application_id] ?? null;
        }

        $pendingApplications = $pendingRaw->map(function ($a) use ($uuids) {
            return [
                'application_id' => $a->application_id,
                'uuid' => $uuids[$a->application_id] ?? null,
                'last_name' => $a->last_name,
                'first_name' => $a->first_name,
                'middle_name' => $a->middle_name,
                'email' => $a->email,
                'cellphone_num' => $a->cellphone_num,
                'loan_amount' => $a->loan_amount,
                'loan_term' => $a->loan_term,
                'loan_purpose' => $a->loan_purpose,
                'status' => $a->status,
                'applied_at' => $a->applied_at,
                'waiting_days' => (int) $a->waiting_days,
                'approved_count' => (int) $a->approved_count,
                'rejected_count' => (int) $a->rejected_count,
            ];
        })->all();

        $pendingLoanCount = count($pendingApplications);
        $newApplicationsCount = $data->new_applications_count ?? 0;
        $needsAttentionCount = $data->needs_attention_count ?? 0;
        $totalApprovedCount = $data->total_approved_count ?? 0;

        return compact(
            'pendingApplications', 'reviewedApplications', 'filteredApplications', 'pendingLoanCount',
            'newApplicationsCount', 'needsAttentionCount', 'totalApprovedCount'
        );
    }
   public function penaltyTracking(Request $request)
{
    $adminId = Session::get('user_id');

    $rpcRow = DB::selectOne('SELECT admin_penalty_tracking_data() as data');
    $data = json_decode($rpcRow->data);

    $penalties = collect($data->penalties ?? []);

    $unpaid = $penalties->where('status', 'unpaid');
    $paid   = $penalties->where('status', 'paid');
    $waived = $penalties->where('status', 'waived');

    $applicationIds = $penalties->pluck('application_id')->unique()->all();

    // Ilang installments lahat ng bawat loan (para sa "3 of 5")
    $installmentTotals = DB::table('loan_payment_schedules')
        ->whereIn('application_id', $applicationIds)
        ->selectRaw('application_id, COUNT(*) as total')
        ->groupBy('application_id')
        ->pluck('total', 'application_id');

    // uuid ng bawat loan (para sa links)
    $loanUuids = DB::table('loan_applications')
        ->whereIn('application_id', $applicationIds)
        ->pluck('uuid', 'application_id');

    // 1 row kada loan
    $loans = $penalties->groupBy('application_id')->map(function ($items, $applicationId) use ($installmentTotals, $loanUuids) {
        $first = $items->first();
        $unpaidItems = $items->where('status', 'unpaid');

        if ($unpaidItems->isNotEmpty()) {
            $status = 'unpaid';
        } elseif ($items->every(fn($p) => $p->status === 'paid')) {
            $status = 'paid';
        } elseif ($items->every(fn($p) => $p->status === 'waived')) {
            $status = 'waived';
        } else {
            $status = 'mixed';
        }

        // Kung may unpaid, yun ang titingnan. Kung wala, lahat.
        $focus = $unpaidItems->isNotEmpty() ? $unpaidItems : $items;
        $main = $focus->sortByDesc('days_late')->first();

        return [
            'application_id'            => $applicationId,
            'loan_uuid'                 => $loanUuids[$applicationId] ?? null,
            'first_name'                => $first->first_name ?? '',
            'last_name'                 => $first->last_name ?? '',
            'installments_with_penalty' => $items->count(),
            'total_installments'        => $installmentTotals[$applicationId] ?? $items->count(),
            'worst_days_late'           => $focus->max('days_late'),
            'total_penalty'             => $items->sum('penalty_amount'),
            'unpaid_penalty'            => $unpaidItems->sum('penalty_amount'),
            'status'                    => $status,
            'main_installment'          => $main->installment_no,
            'items'                     => $items->sortBy('installment_no')->values(),
        ];
    })->values();

    return view('admin.penalty-tracking', [
        'loans' => $loans,

        'totalPenaltiesCount' => $penalties->count(),
        'outstandingAmount'   => $unpaid->sum('penalty_amount'),
        'totalPaidAmount'     => $paid->sum('penalty_amount'),
        'totalWaivedAmount'   => $waived->sum('penalty_amount'),

        'pendingCount'     => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
    ]);
}
    public function completedLoans(Request $request)
{
    $adminId = Session::get('user_id');
    $sort = $request->query('sort', 'newest');

    $rpcRow = DB::selectOne('SELECT admin_completed_loans_data() as data');
    $data = json_decode($rpcRow->data);

    $completedLoans = collect($data->completed_loans ?? [])->map(function ($row) {
        $fullName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
        $displayName = $fullName !== '' ? $fullName : $row->email;
        $initials = $fullName !== ''
            ? strtoupper(substr($row->first_name, 0, 1) . substr($row->last_name, 0, 1))
            : strtoupper(substr($row->email, 0, 2));

        return [
            'application_id'   => $row->application_id,
            'user_id'          => $row->user_id,
            'name'             => $displayName,
            'initials'         => $initials,
            'email'            => $row->email,
            'loan_amount'      => $row->loan_amount,
            'total_amount'     => $row->total_amount,
            'loan_term'        => $row->loan_term,
            'applied_at'       => $row->applied_at,
            'completed_at'     => $row->completed_at,
            'amount_collected' => $row->amount_collected,
        ];
    });

    // uuid ng bawat loan
    $uuids = DB::table('loan_applications')
        ->whereIn('application_id', $completedLoans->pluck('application_id')->all())
        ->pluck('uuid', 'application_id');

    // uuid ng bawat borrower (galing sa borrowerpersonaldetails)
    $borrowerUuids = DB::table('borrowerpersonaldetails')
        ->whereIn('user_id', $completedLoans->pluck('user_id')->unique()->all())
        ->pluck('uuid', 'user_id');

    $completedLoans = $completedLoans->map(function ($loan) use ($uuids, $borrowerUuids) {
        $loan['uuid'] = $uuids[$loan['application_id']] ?? null;

        $borrowerUuid = $borrowerUuids[$loan['user_id']] ?? null;
        $loan['borrower_id'] = $borrowerUuid
            ? 'BRW-' . strtoupper(substr($borrowerUuid, 0, 8))
            : '—';

        return $loan;
    });

    if (in_array($sort, ['newest', 'oldest'], true)) {
        $completedLoans = $completedLoans->sortBy(
            fn ($loan) => strtotime($loan['applied_at']),
            SORT_REGULAR,
            $sort === 'oldest'
        )->values();
    } elseif (in_array($sort, ['completed_newest', 'completed_oldest'], true)) {
        $completedLoans = $completedLoans->sortBy(function ($loan) use ($sort) {
            $t = $loan['completed_at'] ? strtotime($loan['completed_at']) : 0;
            return $sort === 'completed_oldest' ? $t : -$t;
        })->values();
    }
    $completedLoans = $completedLoans->all();

    $stats = (array) $data->stats;

    return view('admin.completed-loans', [
        'completedLoans'   => $completedLoans,
        'stats'            => $stats,
        'sort'             => $sort,
        'pendingCount'     => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
    ]);
}

    public function completedLoanDetail(Request $request, $uuid)
{
    $adminId = Session::get('user_id');

    // UUID lang ang nasa URL. Ang integer na application_id ay internal na lang.
    $applicationId = (int) DB::table('loan_applications')
        ->where('uuid', $uuid)
        ->value('application_id');

    if (!$applicationId) {
        return redirect()->route('admin.completed-loans');
    }

    $app = $this->getLoanApplication($applicationId);

    if (!$app) {
        return redirect()->route('admin.completed-loans');
    }

    $schedule = $this->getPaymentSchedule($applicationId);

    // Same computations used in ongoingLoanDetail() — the view
    // (completed-loan-detail.blade.php) was built from that same
    // template and needs all of these, but they were never being
    // passed here. This is what caused the "Undefined variable"
    // errors for $interestRate, then $monthlyPayment.
    $totalCount = $schedule->count();
    $paidCount = $schedule->filter(fn($s) => !is_null($s->paid_at))->count();
    $monthlyPayment = $totalCount > 0 ? ($app->total_amount / $totalCount) : (float) $app->monthly_payment;
    $totalInterest = max(0, $app->total_amount - $app->loan_amount);
    $interestRate = $app->loan_amount > 0
        ? round(($totalInterest / $app->loan_amount) * 100, 2)
        : 0;

    return view('admin.completed-loan-detail', [
        'app' => $app,
        'applicationId' => $applicationId,
        'borrowerProfilePicture' => $this->getProfilePictureUrl($app),
        'adminId' => $adminId,
        'allReviews' => $this->getAllLoanReviews($applicationId),
        'comments' => $this->getLoanComments($applicationId),
        'paymentSchedule' => $schedule,
        'completedAt' => $schedule->max('paid_at'),
        'amountCollected' => $schedule->sum('amount_paid'),
        'totalCount' => $totalCount,
        'paidCount' => $paidCount,
        'monthlyPayment' => $monthlyPayment,
        'totalInterest' => $totalInterest,
        'interestRate' => $interestRate,
        'borrowerPerformance' => $this->computeBorrowerPerformance($applicationId, $schedule),
        'pendingCount' => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
    ]);
}
        /**
     * Active Loans > Defaulted — a loan is considered "defaulted" here
     * when its OLDEST unpaid installment has crossed DEFAULT_DAYS_LATE
     * days overdue. This is derived (no new status column) — same
     * pattern as overduePayments(), just a higher threshold. Change
     * the constant below if the business wants a different cutoff.
     */
    private const DEFAULT_DAYS_LATE = 90;
    private const REQUIRED_VOID_APPROVALS = 3;

       private const VOID_WINDOW_DAYS = 7;

    private const RECEIPT_DISK = 'valid_ids'; 

    private const BORROWER_RECEIPT_DISK = 'payment_receipts';

    public function defaultedLoans(Request $request)
    {
        $adminId = Session::get('user_id');
        $sort = $request->query('sort', 'most_late');

        $rpcRow = DB::selectOne('SELECT admin_defaulted_loans_data(?) as data', [self::DEFAULT_DAYS_LATE]);
        $data = json_decode($rpcRow->data);

        $defaultedLoans = collect($data->defaulted_loans ?? [])->map(function ($row) {
            $fullName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $displayName = $fullName !== '' ? $fullName : $row->email;
            $initials = $fullName !== ''
                ? strtoupper(substr($row->first_name, 0, 1) . substr($row->last_name, 0, 1))
                : strtoupper(substr($row->email, 0, 2));

            return [
                'application_id'     => $row->application_id,
                'user_id'            => $row->user_id,
                'borrower_id'        => 'BRW-' . str_pad($row->user_id, 4, '0', STR_PAD_LEFT),
                'name'               => $displayName,
                'initials'           => $initials,
                'email'              => $row->email,
                'loan_amount'        => $row->loan_amount,
                'total_amount'       => $row->total_amount,
                'applied_at'         => $row->applied_at,
                'days_late'          => $row->days_late,
                'outstanding_amount' => $row->outstanding_amount,
                'unpaid_penalties'   => $row->unpaid_penalties,
                'last_payment_at'    => $row->last_payment_at,
            ];
        });

        $defaultedLoans = $defaultedLoans->sortBy(function ($loan) use ($sort) {
            if ($sort === 'newest') return -strtotime($loan['applied_at']);
            if ($sort === 'oldest') return strtotime($loan['applied_at']);
            if ($sort === 'highest_balance') return -$loan['outstanding_amount'];
            return -$loan['days_late']; // most_late (default)
        })->values()->all();

        $stats = (array) $data->stats;

        return view('admin.defaulted-loans', [
            'defaultedLoans'   => $defaultedLoans,
            'stats'            => $stats,
            'sort'             => $sort,
            'defaultDaysLate'  => self::DEFAULT_DAYS_LATE,
            'pendingCount'     => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }

    public function defaultedLoanDetail(Request $request, $id)
    {
        $adminId = Session::get('user_id');
        $applicationId = (int) $id;

        $app = $this->getLoanApplication($applicationId);

        if (!$app) {
            return redirect()->route('admin.defaulted-loans');
        }

        $schedule = $this->getPaymentSchedule($applicationId);

        $overdueSchedules = $schedule->filter(function ($s) {
            return is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
        })->sortBy('due_date')->values();

        $oldestOverdue = $overdueSchedules->first();
        $daysLate = $oldestOverdue ? \Carbon\Carbon::parse($oldestOverdue->due_date)->diffInDays(now()) : 0;

        $totalCount = $schedule->count();
        $paidCount = $schedule->filter(fn($s) => !is_null($s->paid_at))->count();
        $amountCollected = $schedule->sum('amount_paid');
        $outstandingAmount = $overdueSchedules->sum(fn($s) => $s->amount_due - $s->amount_paid);

        $unpaidPenalties = (float) DB::table('loan_penalties')
            ->where('application_id', $applicationId)
            ->where('status', 'unpaid')
            ->sum('penalty_amount');

        $lastPayment = $schedule->whereNotNull('paid_at')->sortByDesc('paid_at')->first();

        return view('admin.defaulted-loan-detail', [
            'app' => $app,
            'applicationId' => $applicationId,
            'paymentSchedule' => $schedule,
            'totalCount' => $totalCount,
            'paidCount' => $paidCount,
            'amountCollected' => $amountCollected,
            'outstandingAmount' => $outstandingAmount,
            'unpaidPenalties' => $unpaidPenalties,
            'daysLate' => $daysLate,
            'lastPaymentAt' => $lastPayment->paid_at ?? null,
            'defaultDaysLate' => self::DEFAULT_DAYS_LATE,
            'pendingCount' => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }

private function computeBorrowerPerformance($applicationId, $schedule)
{
    $totalPayments = $schedule->count();

    $latePayments = $schedule->filter(function ($s) {
        if (!is_null($s->paid_at) && !is_null($s->due_date)) {
            return \Carbon\Carbon::parse($s->paid_at)->gt(\Carbon\Carbon::parse($s->due_date));
        }
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

public function penaltyTrackingDetail($uuid, $installment)
{
    $adminId = Session::get('user_id');

    // UUID lang ang nasa URL. Ang integer na application_id ay internal na lang.
    $applicationId = (int) DB::table('loan_applications')
        ->where('uuid', $uuid)
        ->value('application_id');

    if (!$applicationId) {
        return redirect()->route('admin.penalty-tracking');
    }

    $p = DB::table('loan_penalties as lp')
        ->join('loan_payment_schedules as sch', 'lp.schedule_id', '=', 'sch.id')
        ->join('loan_applications as la', 'lp.application_id', '=', 'la.application_id')
        ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
        ->leftJoin('users as waiver', 'lp.waived_by', '=', 'waiver.user_id')
        ->where('lp.application_id', $applicationId)
        ->where('sch.installment_no', (int) $installment)
        ->select(
            'lp.*',
            'la.uuid as loan_uuid',
            'sch.installment_no', 'sch.due_date', 'sch.amount_due', 'sch.paid_at as schedule_paid_at',
            'bpd.first_name', 'bpd.last_name',
            'waiver.name as waived_by_name'
        )
        ->first();

    if (!$p) {
        return redirect()->route('admin.penalty-tracking');
    }

    $periods = (int) $p->late_fee_periods;
    $rate = $periods > 0 ? round($p->penalty_amount / $periods, 2) : 0;

    $logsForApp = DB::table('borrower_penalty_point_logs')
        ->where('application_id', $p->application_id)
        ->orderBy('created_at')
        ->get();

    $useRealLogs = $periods > 0 && $logsForApp->count() === $periods;

    $breakdown = [];
    for ($i = 1; $i <= $periods; $i++) {
        $log = $useRealLogs ? $logsForApp[$i - 1] : null;
        $breakdown[] = [
            'date'         => $log->created_at ?? \Carbon\Carbon::parse($p->due_date)->addDays($i * 7),
            'reason'       => $log->reason ?? ($i * 7) . ' days overdue',
            'days_overdue' => $i * 7,
            'points'       => 1,
            'amount'       => $rate,
        ];
    }

    $p->rate = $rate;
    $p->breakdown = $breakdown;
    $p->payment_status = $p->schedule_paid_at
        ? 'Paid'
        : (\Carbon\Carbon::parse($p->due_date)->isPast() ? 'Overdue' : 'Pending');
    $p->loan_id_display = 'LN-' . str_pad($p->application_id, 5, '0', STR_PAD_LEFT);

    // Lahat ng penalties ng loan na ito (para sa "All Penalties on this Loan" card)
    $loanPenalties = DB::table('loan_penalties as lp')
        ->join('loan_payment_schedules as sch', 'lp.schedule_id', '=', 'sch.id')
        ->where('lp.application_id', $p->application_id)
        ->orderBy('sch.installment_no')
        ->select('lp.id', 'lp.days_late', 'lp.penalty_amount', 'lp.status', 'sch.installment_no', 'sch.due_date')
        ->get();

    return view('admin.penalty-tracking-detail', [
        'p' => $p,
        'loanPenalties' => $loanPenalties,
        'pendingCount' => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
    ]);
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

    public function notificationsIndex(Request $request)
    {
        $adminId = Session::get('user_id');
        $notifications = $this->buildAdminNotifications($adminId);

        return view('admin.notifications', [
            'notifications' => $notifications,
            'pendingCount' => $this->getPendingVerificationCount($adminId),
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }
    // Ensure class is properly closed
}