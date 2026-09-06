<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BorrowerController extends Controller
{
    // Shared helper — kinukuha ang common data na kailangan ng nav sa lahat ng borrower pages
    private function commonData($userId)
    {
        $user = DB::table('users')->where('user_id', $userId)->first();
        $details = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();
        $detailsStatus = $details->status ?? 'incomplete';

        $approvedCount = 0;
        if ($details) {
            $approvedCount = DB::table('detailsapproval')
                ->where('detail_id', $details->detail_id)
                ->where('decision', 'approved')
                ->count();
        }

        $isFullyApproved = $detailsStatus === 'approved' && $approvedCount >= 3;

        if ($details && $details->first_name) {
            $middleInitial = $details->middle_name ? substr($details->middle_name, 0, 1) . '.' : '';
            $userName = trim($details->first_name . ' ' . $middleInitial . ' ' . $details->last_name);
        } else {
            $userName = Session::get('user_name', '');
        }

        $initials = '';
        foreach (explode(' ', trim($userName)) as $part) {
            if ($part) $initials .= strtoupper(substr($part, 0, 1));
        }
        $initials = substr($initials, 0, 2);

        $profilePicture = $this->getProfilePictureUrl($userId);

        $userEmail = $user->email ?? '';
        $userPhone = $details->cellphone_num ?? '';
        $isActive = $user ? (bool) ($user->is_active ?? true) : true;
        $lastLoginAt = $user->last_login_at ?? null;

        $headerNotifications = $this->buildBorrowerNotifications($userId);
        $unreadNotifCount = $headerNotifications->where('read', false)->count();

        return compact(
            'details', 'detailsStatus', 'approvedCount', 'isFullyApproved',
            'userName', 'initials', 'profilePicture',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        );
    }

    private function getProfilePictureUrl($userId)
    {
        $user = DB::table('users')->where('user_id', $userId)->first();
        if ($user && !empty($user->profile_picture) && Storage::disk('public')->exists($user->profile_picture)) {
            return Storage::disk('public')->url($user->profile_picture);
        }
        return null;
    }

    private function buildPaymentSchedule($app)
    {
        $schedule = [];

        if (!$app) {
            return $schedule;
        }

        $term        = (int) $app->loan_term;
        $totalAmount = (float) $app->total_amount;
        $appliedDate = Carbon::parse($app->applied_at);
        $monthlyBase = floor(($totalAmount / $term) * 100) / 100;
        $runningTotal = 0;

        for ($i = 1; $i <= $term; $i++) {
            $due = $appliedDate->copy()->addMonths($i)->day(15);
            $amount = ($i === $term) ? round($totalAmount - $runningTotal, 2) : $monthlyBase;
            $runningTotal += $amount;

            $schedule[] = ['month' => $i, 'due' => $due, 'amount' => $amount];
        }

        return $schedule;
    }

    private function buildBorrowerNotifications($userId)
    {
        $items = collect();

        $readKeys = DB::table('borrower_notification_reads')
            ->where('user_id', $userId)
            ->whereNotNull('read_at')
            ->pluck('notification_key')
            ->flip();

        $today = Carbon::today();

        $applications = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->orderByDesc('applied_at')
            ->get();

        $applicationIds = $applications->pluck('application_id');

        foreach ($applications as $app) {
            if (!in_array($app->status, ['approved', 'active', 'rejected'], true)) {
                continue;
            }

            $decidedAt = DB::table('loan_approvals')
                ->where('application_id', $app->application_id)
                ->whereIn('decision', ['approved', 'rejected'])
                ->orderByDesc('reviewed_at')
                ->value('reviewed_at') ?? $app->applied_at;

            $isApproved = in_array($app->status, ['approved', 'active'], true);
            $key = 'loan_status:' . $app->application_id . ':' . $app->status;

            $items->push([
                'key'      => $key,
                'url'      => route('borrower.loan-details'),
                'icon'     => $isApproved ? 'feather-check-circle' : 'feather-x-circle',
                'color'    => $isApproved ? 'success' : 'danger',
                'title'    => $isApproved ? 'Loan Application Approved' : 'Loan Application Rejected',
                'subtitle' => $isApproved
                    ? 'Your loan of ₱' . number_format($app->loan_amount, 2) . ' has been approved.'
                    : 'Your loan application was not approved.',
                'read'     => $readKeys->has($key),
                'ts'       => Carbon::parse($decidedAt),
            ]);
        }

        $details = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();

        if ($details && $details->status === 'approved') {
            $approvedCount = DB::table('detailsapproval')
                ->where('detail_id', $details->detail_id)
                ->where('decision', 'approved')
                ->count();

            if ($approvedCount >= 3) {
                $decidedAt = DB::table('detailsapproval')
                    ->where('detail_id', $details->detail_id)
                    ->where('decision', 'approved')
                    ->orderByDesc('reviewed_at')
                    ->value('reviewed_at') ?? now();

                $key = 'eligible:' . $details->detail_id;

                $items->push([
                    'key'      => $key,
                    'url'      => route('borrower.apply-loan'),
                    'icon'     => 'feather-unlock',
                    'color'    => 'success',
                    'title'    => "You're Now Eligible to Apply",
                    'subtitle' => 'Your verification is complete — you can now apply for a loan.',
                    'read'     => $readKeys->has($key),
                    'ts'       => Carbon::parse($decidedAt),
                ]);
            }
        }

        if ($applicationIds->isNotEmpty()) {
            $recentPaid = DB::table('loan_payment_schedules')
                ->whereIn('application_id', $applicationIds)
                ->whereNotNull('paid_at')
                ->where('paid_at', '>=', now()->subDays(14))
                ->get();

            foreach ($recentPaid as $s) {
                $key = 'paid:' . $s->application_id . ':' . $s->installment_no;

                $items->push([
                    'key'      => $key,
                    'url'      => route('borrower.loan-details'),
                    'icon'     => 'feather-check-circle',
                    'color'    => 'success',
                    'title'    => 'Payment Received',
                    'subtitle' => 'Your payment of ₱' . number_format($s->amount_paid, 2) . ' was confirmed.',
                    'read'     => $readKeys->has($key),
                    'ts'       => Carbon::parse($s->paid_at),
                ]);
            }

            $penalties = DB::table('loan_penalties')
                ->whereIn('application_id', $applicationIds)
                ->where('status', 'unpaid')
                ->get();

            foreach ($penalties as $p) {
                $key = 'penalty:' . $p->id;

                $items->push([
                    'key'      => $key,
                    'url'      => route('borrower.loan-details'),
                    'icon'     => 'feather-alert-triangle',
                    'color'    => 'danger',
                    'title'    => 'Penalty Charged',
                    'subtitle' => 'A penalty of ₱' . number_format($p->penalty_amount, 2) . ' was added due to late payment.',
                    'read'     => $readKeys->has($key),
                    'ts'       => Carbon::parse($p->created_at),
                ]);
            }
        }

        $candidateApp = $applications->first(fn($a) => in_array($a->status, ['active', 'approved'], true));

        if ($candidateApp) {
            $schedule = DB::table('loan_payment_schedules')
                ->where('application_id', $candidateApp->application_id)
                ->whereNull('paid_at')
                ->orderBy('installment_no')
                ->get();

            foreach ($schedule as $s) {
                $due = Carbon::parse($s->due_date);
                $daysUntilDue = $today->diffInDays($due, false);

                if ($daysUntilDue > 14) {
                    continue;
                }

                $key = 'payment:' . $candidateApp->application_id . ':' . $s->installment_no;
                $isLate = $daysUntilDue < 0;

                $items->push([
                    'key'      => $key,
                    'url'      => route('borrower.loan-details'),
                    'icon'     => $isLate ? 'feather-alert-circle' : 'feather-clock',
                    'color'    => $isLate ? 'danger' : 'primary',
                    'title'    => $isLate ? 'Payment Overdue' : 'Upcoming Payment',
                    'subtitle' => ($isLate ? 'Was due ' : 'Due ') . $due->format('M j, Y'),
                    'read'     => $readKeys->has($key),
                    'ts'       => $due,
                ]);
            }
        }

        return $items->sortByDesc('ts')->values();
    }

    public function dashboard(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        $latestLoan = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->orderByDesc('applied_at')
            ->first();

        $loanStatusMap = [
            'pending'    => ['label' => 'Pending Review', 'bar' => 'bg-warning', 'progress' => 30],
            'approved'   => ['label' => 'Approved',       'bar' => 'bg-success', 'progress' => 60],
            'active'     => ['label' => 'Active',         'bar' => 'bg-primary', 'progress' => 80],
            'completed'  => ['label' => 'Completed',      'bar' => 'bg-success', 'progress' => 100],
            'rejected'   => ['label' => 'Rejected',       'bar' => 'bg-danger',  'progress' => 100],
            'on_hold'    => ['label' => 'On Hold',        'bar' => 'bg-secondary', 'progress' => 40],
            'defaulted'  => ['label' => 'Defaulted',      'bar' => 'bg-danger',  'progress' => 100],
        ];

        $loanStatus = $latestLoan
            ? ($loanStatusMap[$latestLoan->status] ?? ['label' => 'Unknown', 'bar' => 'bg-secondary', 'progress' => 0])
            : ['label' => 'No Application', 'bar' => 'bg-secondary', 'progress' => 0];

        $app = null;
        $outstandingAmount = 0;
        $progressPct = 0;
        $totalCount = 0;
        $paidCount = 0;
        $nextUnpaid = null;
        $isOverdue = false;
        $daysOverdue = 0;
        $approvedAt = null;
        $activatedAt = null;
        $calDaysWithPayments = collect();
        $notifications = collect();
        $recentPayments = collect();

        $calMonthParam = $request->query('cal_month');
        try {
            $calMonth = $calMonthParam
                ? Carbon::createFromFormat('Y-m', $calMonthParam)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Exception $e) {
            $calMonth = Carbon::now()->startOfMonth();
        }
        $calPrevMonth = $calMonth->copy()->subMonth()->format('Y-m');
        $calNextMonth = $calMonth->copy()->addMonth()->format('Y-m');

        $candidateApp = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'approved'])
            ->orderByDesc('applied_at')
            ->first();

        if ($candidateApp) {
            $schedule = DB::table('loan_payment_schedules')
                ->where('application_id', $candidateApp->application_id)
                ->orderBy('installment_no')
                ->get();

            if ($schedule->isNotEmpty()) {
                $app = $candidateApp;

                $totalCount = $schedule->count();
                $paidCount = $schedule->filter(fn($s) => !is_null($s->paid_at))->count();
                $progressPct = $totalCount > 0 ? (int) round(($paidCount / $totalCount) * 100) : 0;
                $outstandingAmount = max(0, (float) $app->total_amount - (float) $schedule->sum('amount_paid'));

                $nextUnpaid = $schedule->first(fn($s) => is_null($s->paid_at));
                if ($nextUnpaid) {
                    $dueDate = Carbon::parse($nextUnpaid->due_date);
                    $isOverdue = $dueDate->isPast();
                    $daysOverdue = $isOverdue ? $dueDate->diffInDays(now()) : 0;
                }

                $approvedAt = DB::table('loan_approvals')
                    ->where('application_id', $app->application_id)
                    ->where('decision', 'approved')
                    ->orderByDesc('reviewed_at')
                    ->value('reviewed_at');

                $activatedAt = $approvedAt;

                $calDaysWithPayments = $schedule
                    ->filter(fn($s) => Carbon::parse($s->due_date)->isSameMonth($calMonth))
                    ->mapWithKeys(fn($s) => [Carbon::parse($s->due_date)->day => true]);

                $today = Carbon::today();
                $notifItems = [];

                foreach ($schedule->whereNull('paid_at') as $s) {
                    $due = Carbon::parse($s->due_date);
                    $daysUntilDue = $today->diffInDays($due, false);

                    if ($daysUntilDue <= 14) {
                        $isLate = $daysUntilDue < 0;
                        $notifItems[] = [
                            'icon'    => $isLate ? 'feather-alert-circle' : 'feather-clock',
                            'color'   => $isLate ? 'danger' : 'primary',
                            'title'   => $isLate ? 'Payment Overdue' : 'Upcoming Payment',
                            'message' => $isLate
                                ? 'Your payment of ₱' . number_format($s->amount_due, 2) . ' was due on ' . $due->format('F j, Y') . '.'
                                : 'Your payment of ₱' . number_format($s->amount_due, 2) . ' is due on ' . $due->format('F j, Y') . '.',
                            'ts'      => $due,
                            'sort'    => $daysUntilDue,
                        ];
                    }
                }

                usort($notifItems, fn($a, $b) => $a['sort'] <=> $b['sort']);
                $notifications = collect($notifItems);

                $recentPayments = $schedule
                    ->filter(fn($s) => !is_null($s->paid_at))
                    ->sortByDesc('paid_at')
                    ->take(3)
                    ->values();
            }
        }

        return view('borrower.dashboard', compact(
            'userName', 'initials', 'profilePicture', 'detailsStatus', 'approvedCount',
            'isFullyApproved', 'latestLoan', 'loanStatus', 'app', 'notifications', 'recentPayments',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'outstandingAmount', 'progressPct', 'totalCount', 'paidCount', 'nextUnpaid',
            'isOverdue', 'daysOverdue', 'approvedAt', 'activatedAt',
            'calMonth', 'calDaysWithPayments', 'calPrevMonth', 'calNextMonth',
            'headerNotifications', 'unreadNotifCount'
        ));
    }

    public function personalDetails(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        $user = DB::table('users')->where('user_id', $userId)->first();
        $regData = [
            'lastName'   => $details->last_name ?? $user->last_name ?? '',
            'firstName'  => $details->first_name ?? $user->first_name ?? '',
            'middleName' => $details->middle_name ?? $user->middle_name ?? '',
            'email'      => $details->email ?? $user->email ?? '',
            'birthday'   => $details->birthday ?? $user->birthday ?? '',
        ];

        return view('borrower.personal-details', compact(
            'userName', 'initials', 'profilePicture', 'detailsStatus', 'approvedCount', 'isFullyApproved', 'regData', 'details',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        ));
    }

    public function savePersonalDetails(Request $request)
    {
        $userId = Session::get('user_id');

        $existing = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();

        // ID Front / Back — dalawang hiwalay na files na ngayon (dati
        // isa lang na "validId"). Kinukuha ang existing paths bilang
        // fallback kung hindi na muling nag-upload (hal. sa resubmit
        // pagkatapos ng rejection, kung same ID pa rin ang gagamitin).
        $idFilePath = $existing->id_file_path ?? null;
        if ($request->hasFile('validIdFront')) {
            $idFilePath = $request->file('validIdFront')->store('ids', 'public');
        }

        $idFilePathBack = $existing->id_file_path_back ?? null;
        if ($request->hasFile('validIdBack')) {
            $idFilePathBack = $request->file('validIdBack')->store('ids', 'public');
        }

        $personalData = [
            'last_name'         => $request->input('lastName'),
            'first_name'        => $request->input('firstName'),
            'middle_name'       => $request->input('middleName'),
            'suffix'            => $request->input('suffix'),
            'birthday'          => $request->input('birthday'),
            'gender'            => $request->input('gender'),
            'civil_status'      => $request->input('civilStatus'),
            'citizenship'       => $request->input('citizenship'),
            'birth_country'     => $request->input('birthCountry'),
            'birth_province'    => $request->input('birthProvince'),
            'birth_city'        => $request->input('birthCity'),
            'cellphone_num'     => $request->input('cellphone'),
            'id_type'           => $request->input('idType'),
            'id_number'         => $request->input('idNumber'),
            'id_file_path'      => $idFilePath,
            'id_file_path_back' => $idFilePathBack,
            'monthly_income'    => $request->input('monthlyIncome'),
            'email'             => $request->input('email'),
            'status'            => 'pending',
        ];

        $addressData = [
            'region'       => $request->input('region'),
            'province'     => $request->input('province'),
            'city'         => $request->input('city'),
            'barangay'     => $request->input('barangay'),
            'zipcode'      => $request->input('zipcode'),
            'home_address' => $request->input('homeAddress'),
        ];

        $workData = [
            'company_name'      => $request->input('companyName'),
            'position'          => $request->input('position'),
            'employment_status' => $request->input('employmentStatus'),
            'monthly_income'    => $request->input('monthlyIncome'),
            'years_in_company'  => $request->input('yearsInCompany'),
        ];

        $comakerData = [
            'last_name'      => $request->input('coMakerLastName'),
            'first_name'     => $request->input('coMakerFirstName'),
            'middle_name'    => $request->input('coMakerMiddleName'),
            'relationship'   => $request->input('coMakerRelationship'),
            'contact_number' => $request->input('coMakerContact'),
        ];

        try {
            if ($existing) {
                $detailId = $existing->detail_id;

                DB::table('borrowerpersonaldetails')->where('user_id', $userId)->update($personalData);
                DB::table('borroweraddress')->where('detail_id', $detailId)->update($addressData);
                DB::table('borrowerworkinfo')->where('detail_id', $detailId)->update($workData);
                DB::table('comakerinfo')->where('detail_id', $detailId)->update($comakerData);

                DB::table('detailsapproval')->where('detail_id', $detailId)->delete();
            } else {
                $personalData['user_id'] = $userId;
                $detailId = DB::table('borrowerpersonaldetails')->insertGetId($personalData, 'detail_id');

                $addressData['detail_id'] = $detailId;
                DB::table('borroweraddress')->insert($addressData);

                $workData['detail_id'] = $detailId;
                DB::table('borrowerworkinfo')->insert($workData);

                $comakerData['detail_id'] = $detailId;
                DB::table('comakerinfo')->insert($comakerData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Personal details submitted successfully!',
                'detail_id' => $detailId,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // ── Bagong unlocked features (pag fully approved na ng 3 admins) ──

    public function applyLoan(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        if (!$isFullyApproved) {
            return redirect()->route('borrower.dashboard');
        }

        $existingApplication = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved', 'active', 'on_hold'])
            ->orderByDesc('applied_at')
            ->first();

        $hasPendingApplication = (bool) $existingApplication;

        return view('borrower.apply-loan', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved', 'hasPendingApplication', 'existingApplication',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        ));
    }

    public function submitLoanApplication(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        if (!$isFullyApproved) {
            return response()->json(['success' => false, 'message' => 'You are not yet eligible to apply for a loan.'], 403);
        }

        $existing = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved', 'active', 'on_hold'])
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'You already have an active or pending loan application.'], 422);
        }

        $loanAmount = (float) $request->input('loanAmount');
        $loanTerm   = (int) $request->input('loanTerm');

        if ($loanAmount < 1000 || $loanAmount > 10000) {
            return response()->json(['success' => false, 'message' => 'Loan amount must be between ₱1,000 and ₱10,000.'], 422);
        }
        if (!in_array($loanTerm, [3, 6, 12], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid loan term.'], 422);
        }

        $interestRate   = 5.00;
        $totalInterest  = $loanAmount * ($interestRate / 100) * $loanTerm;
        $totalAmount    = $loanAmount + $totalInterest;
        $monthlyPayment = round($totalAmount / $loanTerm, 2);

        try {
            $applicationId = DB::table('loan_applications')->insertGetId([
                'user_id'         => $userId,
                'loan_amount'     => $loanAmount,
                'loan_term'       => $loanTerm,
                'interest_rate'   => $interestRate,
                 'total_interest'  => $totalInterest,
                'total_amount'    => $totalAmount,
                'monthly_payment' => $monthlyPayment,
                'loan_purpose'    => $request->input('loanPurpose'),
                'referral_source' => $request->input('referralSource'),
                'referral_name'   => $request->input('referralName'),
                'status'          => 'pending',
                'applied_at'      => now(),
            ], 'application_id');

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully!',
                'application_id' => $applicationId,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function loanDetails(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        if (!$isFullyApproved) {
            return redirect()->route('borrower.dashboard');
        }

        $app = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->orderByDesc('applied_at')
            ->first();

        $statusMap = [
            'pending'   => ['label' => 'Pending Review', 'badge' => 'bg-warning', 'icon' => 'feather-clock',          'icon_color' => 'text-warning'],
            'approved'  => ['label' => 'Approved',       'badge' => 'bg-success', 'icon' => 'feather-check-circle',   'icon_color' => 'text-success'],
            'active'    => ['label' => 'Active',         'badge' => 'bg-primary', 'icon' => 'feather-check-circle',   'icon_color' => 'text-primary'],
            'completed' => ['label' => 'Completed',      'badge' => 'bg-success', 'icon' => 'feather-check-circle',   'icon_color' => 'text-success'],
            'rejected'  => ['label' => 'Rejected',       'badge' => 'bg-danger',  'icon' => 'feather-x-circle',       'icon_color' => 'text-danger'],
            'on_hold'   => ['label' => 'On Hold',        'badge' => 'bg-secondary', 'icon' => 'feather-pause-circle', 'icon_color' => 'text-secondary'],
            'defaulted' => ['label' => 'Defaulted',      'badge' => 'bg-danger',  'icon' => 'feather-alert-triangle', 'icon_color' => 'text-danger'],
        ];

        $status = $app
            ? ($statusMap[$app->status] ?? ['label' => 'Unknown', 'badge' => 'bg-secondary', 'icon' => 'feather-help-circle', 'icon_color' => 'text-secondary'])
            : null;

        $loanId = $app ? 'LN-' . str_pad($app->application_id, 6, '0', STR_PAD_LEFT) : null;

        $realSchedule = $app
            ? DB::table('loan_payment_schedules')
                ->where('application_id', $app->application_id)
                ->orderBy('installment_no')
                ->get()
            : collect();

        $totalCount = 0;
        $paidCount = 0;
        $amountCollected = 0;
        $remainingBalance = $app->total_amount ?? 0;
        $nextUnpaid = null;
        $isOverdue = false;

        if ($realSchedule->isNotEmpty()) {
            $paymentSchedule = $realSchedule->map(function ($s) {
                $due = Carbon::parse($s->due_date);
                $rowStatus = !is_null($s->paid_at)
                    ? 'paid'
                    : ($due->isPast() ? 'overdue' : 'pending');

                return [
                    'month'  => $s->installment_no,
                    'due'    => $due,
                    'amount' => (float) $s->amount_due,
                    'status' => $rowStatus,
                ];
            })->all();

            $totalCount = $realSchedule->count();
            $paidCount = $realSchedule->filter(fn($s) => !is_null($s->paid_at))->count();
            $amountCollected = (float) $realSchedule->sum('amount_paid');
            $remainingBalance = max(0, (float) $app->total_amount - $amountCollected);

            $nextUnpaidRow = $realSchedule->first(fn($s) => is_null($s->paid_at));
            if ($nextUnpaidRow) {
                $nextUnpaid = $nextUnpaidRow;
                $isOverdue = Carbon::parse($nextUnpaidRow->due_date)->isPast();
            }
        } else {
            $paymentSchedule = array_map(function ($row) {
                $row['status'] = 'pending';
                return $row;
            }, $this->buildPaymentSchedule($app));
        }

        return view('borrower.loan-details', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved', 'app', 'status', 'loanId', 'paymentSchedule',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount',
            'totalCount', 'paidCount', 'amountCollected', 'remainingBalance', 'nextUnpaid', 'isOverdue'
        ));
    }

    public function notifications(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        if (!$isFullyApproved) {
            return redirect()->route('borrower.dashboard');
        }

        $app = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->orderByDesc('applied_at')
            ->first();

        $notifications = [];

        if ($app) {
            $today = Carbon::today();
            foreach ($this->buildPaymentSchedule($app) as $row) {
                $daysUntilDue = $today->diffInDays($row['due'], false);

                if ($daysUntilDue <= 14) {
                    if ($daysUntilDue < 0) {
                        $notifications[] = [
                            'type'    => 'danger',
                            'icon'    => 'feather-alert-circle',
                            'title'   => 'Payment Overdue',
                            'message' => 'Your payment of ₱' . number_format($row['amount'], 2) . ' was due on ' . $row['due']->format('F j, Y') . '.',
                            'sort'    => $daysUntilDue,
                        ];
                    } else {
                        $notifications[] = [
                            'type'    => 'warning',
                            'icon'    => 'feather-clock',
                            'title'   => 'Upcoming Payment',
                            'message' => 'Your payment of ₱' . number_format($row['amount'], 2) . ' is due on ' . $row['due']->format('F j, Y') . '.',
                            'sort'    => $daysUntilDue,
                        ];
                    }
                }
            }

            usort($notifications, fn($a, $b) => $a['sort'] <=> $b['sort']);
        }

        return view('borrower.notifications', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved', 'app', 'notifications',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        ));
    }

    public function openNotification(Request $request, $key)
    {
        $userId = Session::get('user_id');

        DB::table('borrower_notification_reads')->updateOrInsert(
            ['user_id' => $userId, 'notification_key' => $key],
            ['read_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );

        $redirect = $request->query('redirect');

        if ($redirect && str_starts_with($redirect, '/')) {
            return redirect($redirect);
        }

        return redirect()->route('borrower.dashboard');
    }

    public function markAllNotificationsRead(Request $request)
    {
        $userId = Session::get('user_id');
        $notifications = $this->buildBorrowerNotifications($userId);

        foreach ($notifications->where('read', false) as $n) {
            DB::table('borrower_notification_reads')->updateOrInsert(
                ['user_id' => $userId, 'notification_key' => $n['key']],
                ['read_at' => now(), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return response()->json(['success' => true]);
    }

    public function dashboardCalendar(Request $request)
    {
        $userId = Session::get('user_id');

        $calMonthParam = $request->query('cal_month');
        try {
            $calMonth = $calMonthParam
                ? Carbon::createFromFormat('Y-m', $calMonthParam)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Exception $e) {
            $calMonth = Carbon::now()->startOfMonth();
        }
        $calPrevMonth = $calMonth->copy()->subMonth()->format('Y-m');
        $calNextMonth = $calMonth->copy()->addMonth()->format('Y-m');

        $candidateApp = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'approved'])
            ->orderByDesc('applied_at')
            ->first();

        $calDaysWithPayments = collect();
        $nextUnpaid = null;

        if ($candidateApp) {
            $schedule = DB::table('loan_payment_schedules')
                ->where('application_id', $candidateApp->application_id)
                ->orderBy('installment_no')
                ->get();

            if ($schedule->isNotEmpty()) {
                $nextUnpaid = $schedule->first(fn($s) => is_null($s->paid_at));

                $calDaysWithPayments = $schedule
                    ->filter(fn($s) => Carbon::parse($s->due_date)->isSameMonth($calMonth))
                    ->mapWithKeys(fn($s) => [Carbon::parse($s->due_date)->day => true]);
            }
        }

        return view('borrower.partials.payment-calendar', compact(
            'calMonth', 'calPrevMonth', 'calNextMonth', 'calDaysWithPayments', 'nextUnpaid'
        ));
    }

    public function profile(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        if (!$isFullyApproved) {
            return redirect()->route('borrower.dashboard');
        }

        $profileRow = DB::table('borrowerpersonaldetails as bpd')
            ->leftJoin('borroweraddress as ba', 'ba.detail_id', '=', 'bpd.detail_id')
            ->leftJoin('borrowerworkinfo as bwi', 'bwi.detail_id', '=', 'bpd.detail_id')
            ->leftJoin('comakerinfo as ci', 'ci.detail_id', '=', 'bpd.detail_id')
            ->where('bpd.user_id', $userId)
            ->select(
                'bpd.*',
                'ba.region', 'ba.province', 'ba.city', 'ba.barangay', 'ba.zipcode', 'ba.home_address',
                'bwi.employment_status', 'bwi.company_name', 'bwi.position', 'bwi.years_in_company',
                'ci.last_name as co_last', 'ci.first_name as co_first', 'ci.relationship', 'ci.contact_number as co_contact'
            )
            ->first();

        $profileData = $profileRow ? (array) $profileRow : [];

        return view('borrower.profile', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved', 'profileData',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        ));
    }

    public function settings(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        if (!$isFullyApproved) {
            return redirect()->route('borrower.dashboard');
        }

        return view('borrower.settings', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        ));
    }

    public function getAccountSettings(Request $request)
    {
        $userId = Session::get('user_id');
        $user = DB::table('users')->where('user_id', $userId)->first();
        $details = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();

        return response()->json([
            'success' => true,
            'name'    => $user->name ?? '',
            'email'   => $user->email ?? '',
            'phone'   => $details->cellphone_num ?? '',
        ]);
    }

    public function updateAccountSettings(Request $request)
    {
        $userId = Session::get('user_id');

        $fullName = trim($request->input('full_name'));
        $email    = trim($request->input('email'));
        $phone    = trim($request->input('phone'));

        if (!$fullName || !$email) {
            return response()->json(['success' => false, 'message' => 'Full name and email are required.'], 422);
        }

        $emailTaken = DB::table('users')
            ->where('email', $email)
            ->where('user_id', '!=', $userId)
            ->exists();

        if ($emailTaken) {
            return response()->json(['success' => false, 'message' => 'This email is already in use.'], 422);
        }

        DB::table('users')->where('user_id', $userId)->update([
            'name'  => $fullName,
            'email' => $email,
        ]);

        Session::put('user_name', $fullName);
        Session::put('user_email', $email);

        $hasDetails = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->exists();
        if ($hasDetails) {
            DB::table('borrowerpersonaldetails')->where('user_id', $userId)->update([
                'cellphone_num' => $phone,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Profile information updated.']);
    }

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $userId = Session::get('user_id');
        $user = DB::table('users')->where('user_id', $userId)->first();

        if (!empty($user->profile_picture) && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $request->file('profile_picture')->store('profile-pictures', 'public');

        DB::table('users')->where('user_id', $userId)->update([
            'profile_picture' => $path,
        ]);

        Session::put('user_profile_picture', $path);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated.',
                'profile_picture_url' => Storage::disk('public')->url($path),
            ]);
        }

        return back()->with('success', 'Profile picture updated.');
    }

    public function removeProfilePicture(Request $request)
    {
        $userId = Session::get('user_id');
        $user = DB::table('users')->where('user_id', $userId)->first();

        if (!empty($user->profile_picture) && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        DB::table('users')->where('user_id', $userId)->update([
            'profile_picture' => null,
        ]);

        Session::forget('user_profile_picture');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile picture removed.',
            ]);
        }

        return back()->with('success', 'Profile picture removed.');
    }

    public function updateProfileSection(Request $request)
    {
        $userId = Session::get('user_id');
        $details = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();

        if (!$details) {
            return response()->json(['success' => false, 'message' => 'Profile not found.'], 404);
        }

        $section = $request->input('section');

        try {
            if ($section === 'personal') {
                DB::table('borrowerpersonaldetails')->where('user_id', $userId)->update([
                    'civil_status'  => $request->input('civilStatus'),
                    'cellphone_num' => $request->input('phone'),
                ]);
            } elseif ($section === 'address') {
                DB::table('borroweraddress')->where('detail_id', $details->detail_id)->update([
                    'province'     => $request->input('province'),
                    'city'         => $request->input('city'),
                    'barangay'     => $request->input('barangay'),
                    'zipcode'      => $request->input('zipcode'),
                    'home_address' => $request->input('homeAddress'),
                ]);
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid section.'], 422);
            }

            return response()->json(['success' => true, 'message' => 'Changes saved successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function faq(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        if (!$isFullyApproved) {
            return redirect()->route('borrower.dashboard');
        }

        return view('borrower.faq', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        ));
    }

    public function terms(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        if (!$isFullyApproved) {
            return redirect()->route('borrower.dashboard');
        }

        return view('borrower.terms', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        ));
    }

    public function privacy(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        if (!$isFullyApproved) {
            return redirect()->route('borrower.dashboard');
        }

        return view('borrower.privacy', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        ));
    }

    public function changePassword(Request $request)
    {
        $userId = Session::get('user_id');
        $user = DB::table('users')->where('user_id', $userId)->first();

        $current = $request->input('current_password');
        $new     = $request->input('new_password');
        $confirm = $request->input('confirm_password');

        if (!$user || !password_verify($current, $user->password_hash)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }

        if ($new !== $confirm) {
            return response()->json(['success' => false, 'message' => 'New passwords do not match.'], 422);
        }

        if (strlen($new) < 8) {
            return response()->json(['success' => false, 'message' => 'New password must be at least 8 characters.'], 422);
        }

        DB::table('users')->where('user_id', $userId)->update([
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
        ]);

        return response()->json(['success' => true, 'message' => 'Password changed successfully!']);
    }
}