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

        return compact('details', 'detailsStatus', 'approvedCount', 'isFullyApproved', 'userName', 'initials');
    }

    // Builds the loan payment schedule/notifications shared by dashboard, loan-details, and notifications pages.
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
                    $message = $daysUntilDue < 0
                        ? 'Your payment of ₱' . number_format($row['amount'], 2) . ' was due on ' . $row['due']->format('F j, Y') . '.'
                        : 'Your payment of ₱' . number_format($row['amount'], 2) . ' is due on ' . $row['due']->format('F j, Y') . '.';

                    $notifications[] = ['message' => $message, 'sort' => $daysUntilDue];
                }
            }

            usort($notifications, fn($a, $b) => $a['sort'] <=> $b['sort']);
        }

        return view('borrower.dashboard', compact(
            'userName', 'initials', 'detailsStatus', 'approvedCount',
            'isFullyApproved', 'latestLoan', 'loanStatus', 'app', 'notifications'
        ));
    }

    public function personalDetails(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);

        // Registration data para sa auto-fill (readonly fields)
        $user = DB::table('users')->where('user_id', $userId)->first();
        $regData = [
            'lastName'   => $details->last_name ?? $user->last_name ?? '',
            'firstName'  => $details->first_name ?? $user->first_name ?? '',
            'middleName' => $details->middle_name ?? $user->middle_name ?? '',
            'email'      => $details->email ?? $user->email ?? '',
            'birthday'   => $details->birthday ?? $user->birthday ?? '',
        ];

        return view('borrower.personal-details', compact(
            'userName', 'initials', 'detailsStatus', 'approvedCount', 'isFullyApproved', 'regData', 'details'
        ));
    }

    public function savePersonalDetails(Request $request)
    {
        $userId = Session::get('user_id');

        $existing = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();

        $idFilePath = $existing->id_file_path ?? null;
        if ($request->hasFile('validId')) {
            $idFilePath = $request->file('validId')->store('ids', 'public');
        }

        $personalData = [
            'last_name'      => $request->input('lastName'),
            'first_name'     => $request->input('firstName'),
            'middle_name'    => $request->input('middleName'),
            'suffix'         => $request->input('suffix'),
            'birthday'       => $request->input('birthday'),
            'gender'         => $request->input('gender'),
            'civil_status'   => $request->input('civilStatus'),
            'citizenship'    => $request->input('citizenship'),
            'birth_country'  => $request->input('birthCountry'),
            'birth_province' => $request->input('birthProvince'),
            'birth_city'     => $request->input('birthCity'),
            'cellphone_num'  => $request->input('cellphone'),
            'id_type'        => $request->input('idType'),
            'id_number'      => $request->input('idNumber'),
            'id_file_path'   => $idFilePath,
            'monthly_income' => $request->input('monthlyIncome'),
            'email'          => $request->input('email'),
            'status'         => 'pending',
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

        // Isa lang ang pwedeng "buhay" na application sa isang pagkakataon.
        $existingApplication = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved', 'active', 'on_hold'])
            ->orderByDesc('applied_at')
            ->first();

        $hasPendingApplication = (bool) $existingApplication;

        return view('borrower.apply-loan', compact(
            'userName', 'initials', 'isFullyApproved', 'hasPendingApplication', 'existingApplication'
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

        $interestRate   = 5.00; // Fixed 5% monthly, same as sa lumang system.
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
        $paymentSchedule = $this->buildPaymentSchedule($app);

        return view('borrower.loan-details', compact(
            'userName', 'initials', 'isFullyApproved', 'app', 'status', 'loanId', 'paymentSchedule'
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

        return view('borrower.notifications', compact('userName', 'initials', 'isFullyApproved', 'app', 'notifications'));
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

        return view('borrower.profile', compact('userName', 'initials', 'isFullyApproved', 'profileData'));
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