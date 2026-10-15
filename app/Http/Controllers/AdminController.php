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
            'paymentTrend', 'recentPayments'
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
    $loanUuids = DB::table('loan_applications')
        ->whereIn('application_id', $pendingLoans->pluck('application_id')
            ->merge($onHoldLoans->pluck('application_id'))
            ->merge($overdueRows->pluck('application_id'))
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

    return $items
        ->map(function ($n) use ($readKeys) {
            $n['read'] = $readKeys->has($n['key']);
            return $n;
        })
        ->sortByDesc('ts')
        ->values();
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

    $collected = (float) DB::table('loan_payment_schedules')
        ->whereBetween('paid_at', [$start, $end])
        ->sum('amount_paid');

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

    // UUID lang ang nasa URL. Ang integer na application_id ay internal na lang.
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
    $interestRate = $app->loan_amount > 0
        ? round(($totalInterest / $app->loan_amount) * 100, 2)
        : 0;

    // Mga receipt na in-upload ng borrower na hinihintay pa ang admin
    $borrowerReceipts = DB::table('payment_receipts')
        ->where('application_id', $applicationId)
        ->where('status', 'submitted')
        ->get()
        ->keyBy('schedule_id');

    return view('admin.ongoing-loan-detail', [
        'app' => $app,
        'applicationId' => $applicationId,
        'adminId' => $adminId,
        'borrowerProfilePicture' => $this->getProfilePictureUrl($app),
        'allReviews' => $this->getAllLoanReviews($applicationId),
        'comments' => $this->getLoanComments($applicationId),
        'paymentSchedule' => $schedule,
        'borrowerReceipts' => $borrowerReceipts,
        'totalCount' => $totalCount,
        'paidCount' => $paidCount,
        'amountCollected' => $amountCollected,
        'monthlyPayment' => $monthlyPayment,
        'totalInterest' => $totalInterest,
        'interestRate' => $interestRate,
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

private function sendPaymentRecordedEmail($schedule, int $applicationId, bool $isFullyPaid): void
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

        Mail::to($borrower->email)->send(
            new PaymentRecorded(
                $loan,
                $displayName,
                (int) $schedule->installment_no,
                (float) $schedule->amount_due,
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

    // Signed URLs para sa Valid ID images (private bucket, 30 minutes validity)
    $idFrontUrl = !empty($app->id_file_path)
        ? Storage::disk('valid_ids')->temporaryUrl($app->id_file_path, now()->addMinutes(30))
        : null;
    $idBackUrl = !empty($app->id_file_path_back)
        ? Storage::disk('valid_ids')->temporaryUrl($app->id_file_path_back, now()->addMinutes(30))
        : null;

    return view('admin.loan-application-detail', [
        'app' => $app,
        'address' => $this->getBorrowerAddress($app->detail_id),
        'work' => $this->getBorrowerWork($app->detail_id),
        'applicationId' => $uuid, // uuid na para gumana ang route() sa Blade forms
        'adminId' => $adminId,
        'borrowerProfilePicture' => $this->getProfilePictureUrl($app),
        'idFrontUrl' => $idFrontUrl,
        'idBackUrl' => $idBackUrl,
        'myReview' => $this->getMyLoanReview($internalId, $adminId),
        'allReviews' => $this->getAllLoanReviews($internalId),
        'approvedCount' => $this->getLoanApprovedCount($internalId),
        'comments' => $this->getLoanComments($internalId),
        'paymentSchedule' => $this->getPaymentSchedule($internalId),
        'pendingCount' => $this->getPendingVerificationCount($adminId),
        'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        'viewStatus' => $app->status,
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
        if ($oldestOverdue) {
            $penaltyRow = DB::table('loan_penalties')
                ->where('schedule_id', $oldestOverdue->id)
                ->where('status', 'unpaid')
                ->first();

            if ($penaltyRow) {
                $currentPenalty = (float) $penaltyRow->penalty_amount;
                $penaltyId = $penaltyRow->id;
            }
        }

        $amountDue = $oldestOverdue ? ((float) $oldestOverdue->amount_due - (float) $oldestOverdue->amount_paid) : 0.0;
        $totalOutstanding = $amountDue + $currentPenalty;

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

    // Iwas double-record (hal. dalawang admin na sabay nag-click)
    if (!is_null($schedule->paid_at)) {
        if ($wantsJson) {
            return response()->json(['success' => false, 'message' => 'This installment is already marked as paid.'], 422);
        }
        return back()->with('error', 'This installment is already marked as paid.');
    }

    // Ang Ongoing Loan Detail page ay nagpapadala ng require_proof=1.
    // Ang ibang pages na tumatawag pa rin sa endpoint na ito (walang date/receipt)
    // ay gagana pa rin gaya ng dati hanggang mailipat sila sa bagong modal.
    $requireProof = $request->boolean('require_proof');

    // Galing sa "View Receipt" modal: gagamitin ang receipt na in-upload ng borrower
    $useBorrowerReceipt = $request->boolean('use_borrower_receipt');

    $request->validate([
        'paid_date' => [Rule::requiredIf($requireProof), 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        'receipt'   => [Rule::requiredIf($requireProof && !$useBorrowerReceipt), 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
    ], [
        'paid_date.required'        => 'Please select the date the payment was made.',
        'paid_date.before_or_equal' => 'The payment date cannot be in the future.',
        'receipt.required'          => 'Please attach the receipt.',
        'receipt.mimes'             => 'The receipt must be a JPG or PNG image.',
        'receipt.max'               => 'The receipt may not be larger than 5MB.',
    ]);

    // startOfDay para hindi mabilang na "late" ang bayad na on-time sa due date
    // (ang computeBorrowerPerformance ay nagko-compare ng paid_at > due_date).
    $paidAt = $request->filled('paid_date')
        ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('paid_date'))->startOfDay()
        : now();

    // ---- I-upload muna ang receipt (kung meron) ----
    $receiptPath = null;
    if ($request->hasFile('receipt')) {
        $file = $request->file('receipt');
        $receiptPath = 'payment-receipts/' . $schedule->application_id . '/'
            . $id . '-' . Str::random(12) . '.' . $file->guessExtension();

        try {
            Storage::disk(self::RECEIPT_DISK)->put($receiptPath, file_get_contents($file->getRealPath()));
        } catch (\Throwable $e) {
            Log::error("Receipt upload failed (schedule {$id}): " . $e->getMessage());
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'Failed to upload the receipt. Please try again.'], 500);
            }
            return back()->with('error', 'Failed to upload the receipt. Please try again.');
        }
    }

    // Walang bagong file pero gagamitin ang receipt ng borrower -> kopyahin sa valid_ids disk
    // para pareho ang ayos ng lahat ng recorded payments (View Payment Details, Void, atbp.)
    if (!$request->hasFile('receipt') && $useBorrowerReceipt) {
        $borrowerReceipt = DB::table('payment_receipts')
            ->where('schedule_id', $id)
            ->where('status', 'submitted')
            ->first();

        if (!$borrowerReceipt || empty($borrowerReceipt->user_file_path)) {
            $msg = 'The borrower receipt was not found. Please upload a receipt.';
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

    try {
        DB::transaction(function () use ($id, $schedule, $paidAt, $receiptPath, $adminId) {
            $update = [
                'paid_at' => $paidAt,
                'amount_paid' => $schedule->amount_due,
            ];
            if ($receiptPath) {
                $update['receipt_path'] = $receiptPath;
            }

            DB::table('loan_payment_schedules')->where('id', $id)->update($update);

            DB::table('loan_penalties')
                ->where('schedule_id', $id)
                ->where('status', 'unpaid')
                ->update([
                    'status' => 'paid',
                    'updated_at' => now(),
                ]);

            // I-mark na verified ang receipt na in-upload ng borrower (kung meron)
            DB::table('payment_receipts')
                ->where('schedule_id', $id)
                ->where('status', 'submitted')
                ->update([
                    'status' => 'verified',
                    'amount_recorded' => $schedule->amount_due,
                    'recorded_paid_date' => $paidAt->toDateString(),
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);
        });
    } catch (\Throwable $e) {
        // Huwag mag-iwan ng "orphan" file kapag pumalya ang DB
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

    $this->sendPaymentRecordedEmail($schedule, (int) $applicationId, !$hasUnpaid);

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

        // ---- Para sa Defaulted Loan Detail stat cards ----
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
            'message' => 'Payment marked as paid.',
            'schedule_id' => (int) $id,
            'amount_paid_display' => number_format((float) $schedule->amount_due, 2),
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

    return back()->with('success', 'Payment marked as paid.');
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

public function voidPayment(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ], [
            'reason.required' => 'Please enter a reason.',
            'reason.min'      => 'Please enter a reason (at least 5 characters).',
        ]);

        $adminId = Session::get('user_id');

        try {
            DB::transaction(function () use ($request, $id, $adminId) {
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

                DB::table('installment_payment_logs')->insert([
                    'installment_id' => $schedule->id,
                    'action'         => 'voided',
                    'amount'         => $schedule->amount_paid,
                    'paid_at'        => \Carbon\Carbon::parse($schedule->paid_at)->toDateString(),
                    'receipt_path'   => $schedule->receipt_path,
                    'reason'         => trim($request->input('reason')),
                    'admin_id'       => $adminId,
                    'created_at'     => now(),
                ]);

                DB::table('loan_payment_schedules')
                    ->where('id', $id)
                    ->update([
                        'paid_at'      => null,
                        'amount_paid'  => 0,
                        'receipt_path' => null,
                    ]);

                DB::table('loan_applications')
                    ->where('application_id', $schedule->application_id)
                    ->where('status', 'completed')
                    ->update(['status' => 'active']);
            });
              } catch (\Illuminate\Database\QueryException $e) {
            Log::error("Void payment failed (schedule {$id}): " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to void the payment.'], 500);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error("Void payment failed (schedule {$id}): " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to void the payment.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment voided successfully.',
        ]);
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
     * Emails the borrower a proof of payment for ONE recorded installment.
     * Expects: proof (JPG/PNG, max 5MB) and schedule_id (the installment row).
     */
    public function sendProofToBorrower(Request $request, $applicationId)
{
    $request->validate([
        'proof'       => 'required|file|mimes:jpg,jpeg,png|max:5120',
        'schedule_id' => 'required|integer',
    ], [
        'proof.required'       => 'Please attach the proof.',
        'proof.mimes'          => 'The proof must be a JPG or PNG image.',
        'proof.max'            => 'The proof may not be larger than 5MB.',
        'schedule_id.required' => 'Please choose the installment this proof is for.',
    ]);

    $loan = DB::table('loan_applications')
        ->where('application_id', (int) $applicationId)
        ->first();

    if (!$loan) {
        return response()->json(['success' => false, 'message' => 'Loan not found.'], 404);
    }

    // The installment must belong to this loan and already be recorded as paid.
    $schedule = DB::table('loan_payment_schedules')
        ->where('id', (int) $request->input('schedule_id'))
        ->where('application_id', $loan->application_id)
        ->first();

    if (!$schedule) {
        return response()->json(['success' => false, 'message' => 'Installment not found.'], 404);
    }

    if (is_null($schedule->paid_at)) {
        return response()->json(['success' => false, 'message' => 'Proof can only be sent for a payment that is already recorded.'], 422);
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
    $loanCode = 'LN-' . str_pad($loan->application_id, 6, '0', STR_PAD_LEFT);

    // Hindi tulad ng ibang emails, dito hindi natin nilulunok ang error:
    // ang email mismo ang trabaho, kaya dapat malaman ng admin kung pumalya.
    try {
        Mail::to($borrower->email)->send(new LoanProofSent(
            $loan,
            $displayName,
            $schedule,
            $file->getRealPath(),
            $file->getMimeType(),
            "payment-proof-{$loanCode}-installment-{$schedule->installment_no}.{$ext}"
        ));
    } catch (\Throwable $e) {
        Log::error("Send proof failed (loan {$applicationId}, installment {$schedule->installment_no}): " . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Failed to send the email. Please try again.'], 500);
    }

    Log::info("Payment proof emailed (loan {$applicationId}, installment {$schedule->installment_no}) by admin " . Session::get('user_id'));

    return response()->json([
        'success' => true,
        'message' => "Proof sent to {$borrower->email}.",
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
                'bpd.id_type', 'bpd.id_number', 'bpd.id_file_path', 'bpd.id_file_path_back',
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

        $penalties = collect($data->penalties ?? [])->map(function ($p) {
            $periods = (int) $p->late_fee_periods;
            $rate = $periods > 0 ? round($p->penalty_amount / $periods, 2) : 0;

            $logsForApp = collect($p->point_logs ?? [])->values();
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

            return $p;
        });

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
    $monthlyPayment = $totalCount > 0 ? ($app->total_amount / $totalCount) : 0;
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

public function penaltyTrackingDetail($id)
{
    $adminId = Session::get('user_id');

    $p = DB::table('loan_penalties as lp')
        ->join('loan_payment_schedules as sch', 'lp.schedule_id', '=', 'sch.id')
        ->join('loan_applications as la', 'lp.application_id', '=', 'la.application_id')
        ->leftJoin('borrowerpersonaldetails as bpd', 'bpd.user_id', '=', 'la.user_id')
        ->leftJoin('users as waiver', 'lp.waived_by', '=', 'waiver.user_id')
        ->where('lp.id', $id)
        ->select(
            'lp.*',
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

    return view('admin.penalty-tracking-detail', [
        'p' => $p,
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