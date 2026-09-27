<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BorrowerController extends Controller
{
    // Shared helper — kinukuha ang common data na kailangan ng nav sa lahat ng borrower pages.
    // Isang RPC call na lang (borrower_common_data) sa halip na 6+ magkakahiwalay na queries,
    // kasama na dun yung N+1 loop na dati nasa buildBorrowerNotifications().
    // Kinukuha ang common fields (userName, initials, profilePicture, atbp.) mula sa
    // decoded RPC json. Hiwalay na function para magamit ito ng commonData() (para sa
    // karamihan ng pages) at ng dashboard() (na may sariling merged RPC call).
    private function extractCommonFields($data)
    {
        $user = $data['user'] ? (object) $data['user'] : null;
        $details = $data['details'] ? (object) $data['details'] : null;
        $detailsStatus = $details->status ?? 'incomplete';

        // Wala nang admin approval queue — direkta nang 'approved' ang
        // status pagka-submit (tingnan ang savePersonalDetails()), kaya
        // ang detailsStatus na lang ang batayan. Tinanggal na ang mga
        // dating $approvedCount / $rejectedCount / $isOnHold / $rejectionRemarks
        // dahil wala nang 3-admin voting flow.
        $isFullyApproved = $detailsStatus === 'approved';

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

        // Binubuo na lang ang URL gamit ang string. Dati Storage::disk('supabase')->url()
        // ang gamit, pero binubuo nito ang buong S3 client sa bawat request (~170ms),
        // kahit walang network call na kasama.
        $storageUrl = rtrim((string) config('filesystems.disks.supabase.url'), '/');
        $profilePicture = ($user && !empty($user->profile_picture))
            ? $storageUrl . '/' . ltrim($user->profile_picture, '/')
            : null;

        $userEmail = $user->email ?? '';
        $userPhone = $details->cellphone_num ?? '';
        $isActive = $user ? (bool) ($user->is_active ?? true) : true;
        $lastLoginAt = $user->last_login_at ?? null;

        $headerNotifications = $this->buildBorrowerNotificationsFromData($data);
        $unreadNotifCount = $headerNotifications->where('read', false)->count();

        return compact(
            'details', 'detailsStatus', 'isFullyApproved',
            'userName', 'initials', 'profilePicture',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        );
    }

    private function commonData($userId)
    {
        $row = DB::selectOne('select borrower_common_data(?) as data', [$userId]);
        $data = json_decode($row->data, true);

        return $this->extractCommonFields($data);
    }

    /**
     * Loan amount eligibility — first-time borrowers are capped at
     * ₱10,000. Once they have at least one fully completed loan,
     * they're eligible for up to ₱100,000. Used by both applyLoan()
     * (to render the correct banner/input limits) and
     * submitLoanApplication() (to enforce it server-side).
     */
    private function getLoanEligibility($userId)
    {
        $completedCount = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        $isFirstTime = $completedCount === 0;
        $maxAmount = $isFirstTime ? 10000 : 100000;

        return compact('isFirstTime', 'maxAmount');
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
            $due = $appliedDate->copy()->addMonthsNoOverflow($i);
            $amount = ($i === $term) ? round($totalAmount - $runningTotal, 2) : $monthlyBase;
            $runningTotal += $amount;

            $schedule[] = ['month' => $i, 'due' => $due, 'amount' => $amount];
        }

        return $schedule;
    }

    // Kapalit ng dating buildBorrowerNotifications(). Parehong logic sa pag-buo ng
    // notification cards, pero gamit na yung bulk data na kasama na sa borrower_common_data
    // RPC — walang bagong query dito, kaya wala nang N+1 kahit ilan pang loan applications.
    private function buildBorrowerNotificationsFromData($data)
    {
        $items = collect();
        $readKeys = collect($data['read_keys'])->flip();
        $today = Carbon::today();

        $applications = collect($data['applications']);

        foreach ($applications as $app) {
            if (!in_array($app['status'], ['approved', 'active', 'rejected'], true)) {
                continue;
            }

            $isApproved = in_array($app['status'], ['approved', 'active'], true);
            $key = 'loan_status:' . $app['application_id'] . ':' . $app['status'];

            $items->push([
                'key'      => $key,
                'url'      => route('borrower.loan-details'),
                'icon'     => $isApproved ? 'feather-check-circle' : 'feather-x-circle',
                'color'    => $isApproved ? 'success' : 'danger',
                'title'    => $isApproved ? 'Loan Application Approved' : 'Loan Application Rejected',
                'subtitle' => $isApproved
                    ? 'Your loan of ₱' . number_format($app['loan_amount'], 2) . ' has been approved.'
                    : 'Your loan application was not approved.',
                'read'     => $readKeys->has($key),
                'ts'       => Carbon::parse($app['decided_at']),
            ]);
        }

        $details = $data['details'] ? (object) $data['details'] : null;

        // Wala nang 3-admin approval count — direktang trinu-trigger na
        // lang base sa status === 'approved'.
        if ($details && $details->status === 'approved') {
            $key = 'eligible:' . $details->detail_id;

            $items->push([
                'key'      => $key,
                'url'      => route('borrower.apply-loan'),
                'icon'     => 'feather-unlock',
                'color'    => 'success',
                'title'    => "You're Now Eligible to Apply",
                'subtitle' => 'Your details are complete — you can now apply for a loan.',
                'read'     => $readKeys->has($key),
                'ts'       => $data['eligible_decided_at'] ? Carbon::parse($data['eligible_decided_at']) : now(),
            ]);
        }

        foreach ($data['recent_paid'] as $s) {
            $key = 'paid:' . $s['application_id'] . ':' . $s['installment_no'];

            $items->push([
                'key'      => $key,
                'url'      => route('borrower.loan-details'),
                'icon'     => 'feather-check-circle',
                'color'    => 'success',
                'title'    => 'Payment Received',
                'subtitle' => 'Your payment of ₱' . number_format($s['amount_paid'], 2) . ' was confirmed.',
                'read'     => $readKeys->has($key),
                'ts'       => Carbon::parse($s['paid_at']),
            ]);
        }

        foreach ($data['unpaid_penalties'] as $p) {
            $key = 'penalty:' . $p['id'];

            $items->push([
                'key'      => $key,
                'url'      => route('borrower.loan-details'),
                'icon'     => 'feather-alert-triangle',
                'color'    => 'danger',
                'title'    => 'Penalty Charged',
                'subtitle' => 'A penalty of ₱' . number_format($p['penalty_amount'], 2) . ' was added due to late payment.',
                'read'     => $readKeys->has($key),
                'ts'       => Carbon::parse($p['created_at']),
            ]);
        }

        foreach ($data['upcoming_schedule'] as $s) {
            $due = Carbon::parse($s['due_date']);
            $daysUntilDue = $today->diffInDays($due, false);

            if ($daysUntilDue > 14) {
                continue;
            }

            $key = 'payment:' . $s['application_id'] . ':' . $s['installment_no'];
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

        return $items->sortByDesc('ts')->values();
    }

    public function dashboard(Request $request)
    {
        $userId = Session::get('user_id');

        // Isang RPC call na lang para sa dashboard — pinagsama na ang common data
        // (nav/header) at ang dashboard-specific data (loan, schedule) sa isang
        // round-trip, sa halip na dalawang magkahiwalay na tawag papunta sa Supabase.
        $row = DB::selectOne('select borrower_dashboard_full_data(?) as data', [$userId]);
        $dashData = json_decode($row->data, true);

        $common = $this->extractCommonFields($dashData);
        extract($common);

        $latestLoan = $dashData['latest_loan'] ? (object) $dashData['latest_loan'] : null;
        $candidateApp = $dashData['candidate_app'] ? (object) $dashData['candidate_app'] : null;
        $scheduleRaw = collect($dashData['schedule'])->map(fn($s) => (object) $s);
        $approvedAtRaw = $dashData['approved_at'] ?? null;

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
        $amountPaid = 0;
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

        if ($candidateApp) {
            $schedule = $scheduleRaw;

            if ($schedule->isNotEmpty()) {
                $app = $candidateApp;

                $totalCount = $schedule->count();
                $paidCount = $schedule->filter(fn($s) => !is_null($s->paid_at))->count();
                $progressPct = $totalCount > 0 ? (int) round(($paidCount / $totalCount) * 100) : 0;
                $outstandingAmount = max(0, (float) $app->total_amount - (float) $schedule->sum('amount_paid'));
                $amountPaid = (float) $schedule->sum('amount_paid');

                $nextUnpaid = $schedule->first(fn($s) => is_null($s->paid_at));
                if ($nextUnpaid) {
                    $dueDate = Carbon::parse($nextUnpaid->due_date);
                    $isOverdue = $dueDate->isPast();
                    $daysOverdue = $isOverdue ? $dueDate->diffInDays(now()) : 0;
                }

                $approvedAt = $approvedAtRaw;
                $activatedAt = $approvedAtRaw;

                $calDaysWithPayments = $schedule
                    ->filter(fn($s) => Carbon::parse($s->due_date)->isSameMonth($calMonth))
                    ->mapWithKeys(fn($s) => [Carbon::parse($s->due_date)->day => true]);

                $today = Carbon::today();
                $notifItems = [];

                foreach ($schedule->filter(fn($s) => is_null($s->paid_at)) as $s) {
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
            'userName', 'initials', 'profilePicture', 'detailsStatus',
            'isFullyApproved', 'latestLoan', 'loanStatus', 'app', 'notifications', 'recentPayments',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'outstandingAmount', 'amountPaid', 'progressPct', 'totalCount', 'paidCount', 'nextUnpaid',
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

        // Saved address / work / co-maker — para ma-prefill ang form
        // kapag nag-eedit ang borrower.
        $address = $details
            ? DB::table('borroweraddress')->where('detail_id', $details->detail_id)->first()
            : null;
        $work = $details
            ? DB::table('borrowerworkinfo')->where('detail_id', $details->detail_id)->first()
            : null;
        $comaker = $details
            ? DB::table('comakerinfo')->where('detail_id', $details->detail_id)->first()
            : null;

        return view('borrower.personal-details', compact(
            'userName', 'initials', 'profilePicture', 'detailsStatus', 'isFullyApproved', 'regData', 'details',
            'address', 'work', 'comaker',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount'
        ));
    }

    public function savePersonalDetails(Request $request)
    {
        $userId = Session::get('user_id');

        $existing = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();

        $request->validate([
            'validIdFront' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'validIdBack'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'email'        => 'nullable|email',
            'cellphone'    => 'nullable|string|max:20',
            'idTypeOthers' => 'required_if:idType,others|nullable|string|max:100',
            'idNumber'     => [
                'required',
                'string',
                Rule::unique('borrowerpersonaldetails', 'id_number')
                    ->ignore($existing->detail_id ?? null, 'detail_id'),
            ],
        ], [
            'idNumber.unique' => 'This ID number is already registered under another account.',
            'idTypeOthers.required_if' => 'Please specify your ID type.',
        ]);

        // ID Front / Back — papunta sa 'valid_ids' disk (private bucket)
        $idFilePath = $existing->id_file_path ?? null;
        if ($request->hasFile('validIdFront')) {
            if ($idFilePath) {
                Storage::disk('valid_ids')->delete($idFilePath);
            }
            $file = $request->file('validIdFront');
            $idFilePath = 'ids/' . uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('valid_ids')->put($idFilePath, file_get_contents($file));
        }

        $idFilePathBack = $existing->id_file_path_back ?? null;
        if ($request->hasFile('validIdBack')) {
            if ($idFilePathBack) {
                Storage::disk('valid_ids')->delete($idFilePathBack);
            }
            $file = $request->file('validIdBack');
            $idFilePathBack = 'ids/' . uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('valid_ids')->put($idFilePathBack, file_get_contents($file));
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
            // Naka-save lang kapag "others" ang piniling ID type;
            // kung hindi, null para walang naiiwang lumang value.
            'id_type_others'    => $request->input('idType') === 'others'
                                    ? trim($request->input('idTypeOthers', ''))
                                    : null,
            'id_number'         => $request->input('idNumber'),
            'id_file_path'      => $idFilePath,
            'id_file_path_back' => $idFilePathBack,
            'monthly_income'    => $request->input('monthlyIncome'),
            'email'             => $request->input('email'),
            'status'            => 'approved',
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

        try {
            if ($existing) {
                $detailId = $existing->detail_id;

                DB::table('borrowerpersonaldetails')->where('user_id', $userId)->update($personalData);

                // updateOrInsert() para gumawa ng row kung wala pa,
                // mag-update kung meron na.
                DB::table('borroweraddress')->updateOrInsert(
                    ['detail_id' => $detailId],
                    $addressData
                );

                DB::table('borrowerworkinfo')->updateOrInsert(
                    ['detail_id' => $detailId],
                    $workData
                );
            } else {
                $personalData['user_id'] = $userId;
                $personalData['uuid'] = (string) Str::uuid();
                $personalData['created_at'] = now();
                $detailId = DB::table('borrowerpersonaldetails')->insertGetId($personalData, 'detail_id');

                $addressData['detail_id'] = $detailId;
                DB::table('borroweraddress')->insert($addressData);

                $workData['detail_id'] = $detailId;
                DB::table('borrowerworkinfo')->insert($workData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Personal details submitted successfully!',
                'detail_id' => $detailId,
            ]);
        } catch (QueryException $e) {
            // Postgres unique_violation = SQLSTATE 23505. Huling harang
            // kung nakalusot sa validate() dahil sa race condition.
            if ($e->getCode() === '23505') {
                return response()->json([
                    'success' => false,
                    'message' => 'This ID number is already registered under another account.',
                ], 422);
            }

            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ── Bagong unlocked features (pagkatapos mag-submit ng personal details) ──

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

        // First-time borrowers are capped at ₱10,000; once they've
        // completed at least one loan, they're eligible up to ₱100,000.
        $eligibility = $this->getLoanEligibility($userId);
        $isFirstTime = $eligibility['isFirstTime'];
        $maxLoanAmount = $eligibility['maxAmount'];

        return view('borrower.apply-loan', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved', 'hasPendingApplication', 'existingApplication',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount',
            'isFirstTime', 'maxLoanAmount'
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

        // Same eligibility check as applyLoan() — enforced here too since
        // the frontend limit can be bypassed (devtools, direct API call).
        $eligibility = $this->getLoanEligibility($userId);
        $maxLoanAmount = $eligibility['maxAmount'];

        $loanAmount = (float) $request->input('loanAmount');
        $loanTerm   = (int) $request->input('loanTerm');

        if ($loanAmount < 1000 || $loanAmount > $maxLoanAmount) {
            return response()->json(['success' => false, 'message' => 'Loan amount must be between ₱1,000 and ₱' . number_format($maxLoanAmount) . '.'], 422);
        }
        if (!in_array($loanTerm, [3, 6, 12], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid loan term.'], 422);
        }

        $interestRate   = 5.00;
        $totalInterest  = $loanAmount * ($interestRate / 100) * $loanTerm;
        $totalAmount    = $loanAmount + $totalInterest;
        $monthlyPayment = round($totalAmount / $loanTerm, 2);

        try {
            // Ang "check-then-insert" ay iisang atomic operation gamit ang
            // DB::transaction() + lockForUpdate(), para hindi makalusot ang
            // double-click o dalawang tabs.
            $result = DB::transaction(function () use ($userId, $loanAmount, $loanTerm, $interestRate, $totalInterest, $totalAmount, $monthlyPayment, $request) {
                $existing = DB::table('loan_applications')
                    ->where('user_id', $userId)
                    ->whereIn('status', ['pending', 'approved', 'active', 'on_hold'])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return ['duplicate' => true];
                }

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

                return ['duplicate' => false, 'application_id' => $applicationId];
            });

            if ($result['duplicate']) {
                return response()->json(['success' => false, 'message' => 'You already have an active or pending loan application.'], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully!',
                'application_id' => $result['application_id'],
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                return response()->json(['success' => false, 'message' => 'You already have an active or pending loan application.'], 422);
            }

            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
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

        $requestedLoanId = $request->query('loan');

        $row = DB::selectOne('select borrower_loan_details_data(?, ?) as data', [$userId, $requestedLoanId]);
        $ldData = json_decode($row->data, true);

        $loans = collect($ldData['loans'])->map(fn($l) => (object) $l);
        $app = $ldData['selected_app'] ? (object) $ldData['selected_app'] : null;
        $realSchedule = collect($ldData['schedule'])->map(fn($s) => (object) $s);

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
                    'month'   => $s->installment_no,
                    'due'     => $due,
                    'amount'  => (float) $s->amount_due,
                    'status'  => $rowStatus,
                    'paid_at' => !is_null($s->paid_at) ? Carbon::parse($s->paid_at) : null,
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
            'totalCount', 'paidCount', 'amountCollected', 'remainingBalance', 'nextUnpaid', 'isOverdue',
            'loans'
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

        // Isang query na lang (insertOrIgnore). Dati updateOrInsert = 2 queries
        // (select tapos insert/update). Kung nasa read table na ang key, walang
        // kailangang baguhin, kaya okay lang na i-ignore.
        DB::table('borrower_notification_reads')->insertOrIgnore([
            'user_id'          => $userId,
            'notification_key' => $key,
            'read_at'          => now(),
            'updated_at'       => now(),
            'created_at'       => now(),
        ]);

        // Naka-block ang "//evil.com" protocol-relative bypass.
        $redirect = $request->query('redirect');

        if ($redirect && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
            return redirect($redirect);
        }

        return redirect()->route('borrower.dashboard');
    }

    public function markAllNotificationsRead(Request $request)
    {
        $userId = Session::get('user_id');

        $row = DB::selectOne('select borrower_common_data(?) as data', [$userId]);
        $data = json_decode($row->data, true);
        $notifications = $this->buildBorrowerNotificationsFromData($data);

        // Kunin muna lahat ng unread, tapos isang insert na lang para sa lahat.
        // Dati may updateOrInsert sa loob ng foreach (2 queries kada notification).
        // Unread na ang mga ito, ibig sabihin wala pa sa read table, kaya insert lang.
        $rows = [];
        foreach ($notifications->where('read', false) as $n) {
            $rows[] = [
                'user_id'          => $userId,
                'notification_key' => $n['key'],
                'read_at'          => now(),
                'updated_at'       => now(),
                'created_at'       => now(),
            ];
        }

        if (!empty($rows)) {
            DB::table('borrower_notification_reads')->insertOrIgnore($rows);
        }

        return response()->json(['success' => true]);
    }

    public function dashboardCalendar(Request $request)
{
    $t0 = microtime(true);
    $requestStart = $_SERVER['REQUEST_TIME_FLOAT'] ?? $t0;

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

    $t1 = microtime(true);

    $schedule = DB::table('loan_payment_schedules as lps')
        ->join('loan_applications as la', 'la.application_id', '=', 'lps.application_id')
        ->where('la.user_id', $userId)
        ->whereIn('la.status', ['active', 'approved'])
        ->orderByDesc('la.applied_at')
        ->orderBy('lps.installment_no')
        ->select('lps.*', 'la.application_id as la_application_id')
        ->get();

    $t2 = microtime(true);

    $calDaysWithPayments = collect();
    $nextUnpaid = null;

    if ($schedule->isNotEmpty()) {
        $latestApplicationId = $schedule->first()->la_application_id;
        $schedule = $schedule->where('la_application_id', $latestApplicationId)->values();

        $nextUnpaid = $schedule->first(fn($s) => is_null($s->paid_at));

        $calDaysWithPayments = $schedule
            ->filter(fn($s) => Carbon::parse($s->due_date)->isSameMonth($calMonth))
            ->mapWithKeys(fn($s) => [Carbon::parse($s->due_date)->day => true]);
    }

    $t3 = microtime(true);

    $view = view('borrower.partials.payment-calendar', compact(
        'calMonth', 'calPrevMonth', 'calNextMonth', 'calDaysWithPayments', 'nextUnpaid'
    ))->render();

    $t4 = microtime(true);

    \Log::info('dashboardCalendar timing', [
        'bootstrap_to_method_ms' => round(($t0 - $requestStart) * 1000),
        'setup_ms'   => round(($t1 - $t0) * 1000),
        'query_ms'   => round(($t2 - $t1) * 1000),
        'process_ms' => round(($t3 - $t2) * 1000),
        'view_ms'    => round(($t4 - $t3) * 1000),
        'method_total_ms' => round(($t4 - $t0) * 1000),
        'php_start_to_view_done_ms' => round(($t4 - $requestStart) * 1000),
    ]);

    return response($view);
}

public function dashboardCalendarData(Request $request)
{
    $userId = Session::get('user_id');

    $schedule = DB::table('loan_payment_schedules as lps')
        ->join('loan_applications as la', 'la.application_id', '=', 'lps.application_id')
        ->where('la.user_id', $userId)
        ->whereIn('la.status', ['active', 'approved'])
        ->orderByDesc('la.applied_at')
        ->orderBy('lps.installment_no')
        ->select('lps.due_date', 'lps.amount_due', 'lps.paid_at', 'la.application_id as la_application_id')
        ->get();

    if ($schedule->isNotEmpty()) {
        $latestApplicationId = $schedule->first()->la_application_id;
        $schedule = $schedule->where('la_application_id', $latestApplicationId)->values();
    }

    return response()->json([
        'schedule' => $schedule->map(fn($s) => [
            'due_date'   => Carbon::parse($s->due_date)->format('Y-m-d'),
            'amount_due' => (float) $s->amount_due,
            'paid_at'    => $s->paid_at,
        ]),
    ]);
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

        if (!empty($user->profile_picture)) {
            Storage::disk('supabase')->delete($user->profile_picture);
        }

        $file = $request->file('profile_picture');
        $filename = 'profile-pictures/' . uniqid() . '.' . $file->getClientOriginalExtension();
        Storage::disk('supabase')->put($filename, file_get_contents($file), 'public');

        DB::table('users')->where('user_id', $userId)->update([
            'profile_picture' => $filename,
        ]);

        Session::put('user_profile_picture', $filename);

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
        $userId = Session::get('user_id');
        $user = DB::table('users')->where('user_id', $userId)->first();

        if (!empty($user->profile_picture)) {
            Storage::disk('supabase')->delete($user->profile_picture);
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
        return redirect()->route('legal.terms');
    }

    public function privacy(Request $request)
    {
        return redirect()->route('legal.privacy');
    }

    public function changePassword(Request $request)
    {
        $userId = Session::get('user_id');

        // Rate limit key — per-user, para hindi ma-brute-force
        // ang current password kahit na-hijack ang session ng user.
        $rateLimitKey = 'change-password:' . $userId;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
            ], 429);
        }

        $user = DB::table('users')->where('user_id', $userId)->first();

        $current = $request->input('current_password');
        $new     = $request->input('new_password');
        $confirm = $request->input('confirm_password');

        if (!$user || !Hash::check($current, $user->password_hash)) {
            RateLimiter::hit($rateLimitKey, 600); // 10 minutes lockout window
            return response()->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }

        if ($new !== $confirm) {
            return response()->json(['success' => false, 'message' => 'New passwords do not match.'], 422);
        }

        if (strlen($new) < 8) {
            return response()->json(['success' => false, 'message' => 'New password must be at least 8 characters.'], 422);
        }

        DB::table('users')->where('user_id', $userId)->update([
            'password_hash' => Hash::make($new),
        ]);

        // Successful change — clear ang rate limit counter
        RateLimiter::clear($rateLimitKey);

        return response()->json(['success' => true, 'message' => 'Password changed successfully!']);
    }
}