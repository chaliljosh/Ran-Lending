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
            $term        = (int) $app->loan_term;
            $totalAmount = (float) $app->total_amount;
            $appliedDate = Carbon::parse($app->applied_at);
            $monthlyBase = floor(($totalAmount / $term) * 100) / 100;
            $runningTotal = 0;
            $today = Carbon::today();

            for ($i = 1; $i <= $term; $i++) {
                $due = $appliedDate->copy()->addMonths($i)->day(15);
                $amount = ($i === $term) ? round($totalAmount - $runningTotal, 2) : $monthlyBase;
                $runningTotal += $amount;
                $daysUntilDue = $today->diffInDays($due, false);

                if ($daysUntilDue <= 14) {
                    $message = $daysUntilDue < 0
                        ? 'Your payment of ₱' . number_format($amount, 2) . ' was due on ' . $due->format('F j, Y') . '.'
                        : 'Your payment of ₱' . number_format($amount, 2) . ' is due on ' . $due->format('F j, Y') . '.';

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
                $detailId = DB::table('borrowerpersonaldetails')->insertGetId($personalData);

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
}