<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Mail\OtpMail;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * My Profile (view-only) + Settings (editable fields lang).
 *
 * Nag-e-extend sa BorrowerController para magamit ang commonData()
 * (header/nav data). Kailangang gawing `protected` ang commonData() doon.
 *
 * Ano ang pwedeng baguhin ng borrower mismo:
 *   - civil status, address            -> updateDetails()
 *   - cellphone number, email          -> sendContactOtp() + verifyContactOtp()
 *   - pangalan, birthday, gender,
 *     citizenship, monthly income      -> submitChangeRequest() (admin ang nag-aapprove)
 */
class BorrowerAccountController extends BorrowerController
{
    private const CIVIL_STATUSES = ['single', 'taken', 'married', 'widowed', 'separated'];

    private const OTP_MINUTES = 10;

    // Parehong values sa registration form.
    private const INCOME_BRACKETS = [
        'Below ₱10,000',
        '₱10,000 - ₱20,000',
        '₱20,001 - ₱30,000',
        '₱30,001 - ₱50,000',
        '₱50,001 - ₱100,000',
        'Above ₱100,000',
    ];

    // field key => label na ipinapakita sa borrower
    private const CHANGE_REQUEST_FIELDS = [
        'name'           => 'Full name',
        'birthday'       => 'Birthday',
        'gender'         => 'Gender',
        'citizenship'    => 'Citizenship',
        'monthly_income' => 'Monthly income',
    ];

    // =====================================================================
    // PAGES
    // =====================================================================

   public function profile(Request $request)
    {
        $userId = Session::get('user_id');
        $common = $this->commonData($userId);
        extract($common);
 
        if (!$isFullyApproved) {
            return redirect()->route('borrower.dashboard');
        }
 
        $profileData = $this->loadProfileData($userId);
 
        // Loan History — kasama ang uuid (para pareho ang Loan ID format sa
        // Loan Details at admin: LN-XXXXXXXX).
        $loans = DB::table('loan_applications')
            ->where('user_id', $userId)
            ->select(
                'application_id', 'uuid', 'loan_amount', 'loan_term',
                'monthly_payment', 'total_amount', 'total_interest',
                'status', 'applied_at', 'proof_sent_at'
            )
            ->orderByDesc('applied_at')
            ->get();
 
        // Ang schedule at paid status ay galing sa PAREHONG RPC na ginagamit ng
        // BorrowerController::loanDetails() (borrower_loan_details_data), para laging
        // magkapareho ang "paid" count, amount paid, at next due date sa dalawang page.
        $loans = $loans->map(function ($loan) use ($userId) {
            $row = DB::selectOne(
                'select borrower_loan_details_data(?, ?) as data',
                [$userId, $loan->application_id]
            );
            $ld = $row ? json_decode($row->data, true) : [];
 
            // Siguraduhing ang schedule ay para sa loan na ito mismo.
            $selected = $ld['selected_app'] ?? null;
            $sameLoan = $selected && (int) $selected['application_id'] === (int) $loan->application_id;
 
            $rows = $sameLoan
                ? collect($ld['schedule'] ?? [])->map(fn ($s) => (object) $s)->sortBy('installment_no')->values()
                : collect();
 
            $count = $rows->count();
            $totalInterest = (float) ($loan->total_interest ?? 0);
            $interestEach = $count > 0 ? round($totalInterest / $count, 2) : 0.0;
            $interestUsed = 0.0;
 
            $schedule = $rows->map(function ($s, $i) use ($count, $interestEach, $totalInterest, &$interestUsed) {
                $interest = ($i === $count - 1)
                    ? round($totalInterest - $interestUsed, 2)
                    : $interestEach;
                $interestUsed += $interest;
 
                return (object) [
                    'installment_no' => $s->installment_no,
                    'due_date'       => $s->due_date,
                    'amount_due'     => (float) $s->amount_due,
                    'principal'      => round((float) $s->amount_due - $interest, 2),
                    'interest'       => $interest,
                    'paid_at'        => $s->paid_at ?? null,
                ];
            });
 
            $nextUnpaid = $rows->first(fn ($s) => empty($s->paid_at));
 
            $loan->schedule       = $schedule;
            $loan->schedule_count = $count;
            $loan->paid_count     = $rows->filter(fn ($s) => !empty($s->paid_at))->count();
            $loan->amount_paid    = (float) $rows->sum('amount_paid');
            $loan->next_due_date  = $nextUnpaid->due_date ?? null;
            $loan->released_at    = $loan->proof_sent_at;
 
            return $loan;
        });
 
        return view('borrower.profile', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved', 'profileData',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount',
            'loans'
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

        $profileData = $this->loadProfileData($userId);

        // Mga field na may pending change request na.
        $pendingFields = DB::table('profile_change_requests')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->pluck('field')
            ->all();

        $changeFields = self::CHANGE_REQUEST_FIELDS;
        $incomeBrackets = self::INCOME_BRACKETS;

        return view('borrower.settings', compact(
            'userName', 'initials', 'profilePicture', 'isFullyApproved', 'profileData',
            'userEmail', 'userPhone', 'isActive', 'lastLoginAt',
            'headerNotifications', 'unreadNotifCount',
            'pendingFields', 'changeFields', 'incomeBrackets'
        ));
    }

    // =====================================================================
    // EDITABLE: civil status + address
    // =====================================================================

    public function updateDetails(Request $request)
    {
        $userId  = Session::get('user_id');
        $details = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();

        if (!$details) {
            return response()->json(['success' => false, 'message' => 'Profile not found.'], 404);
        }

        $request->merge([
            'province'    => trim((string) $request->input('province')),
            'city'        => trim((string) $request->input('city')),
            'barangay'    => trim((string) $request->input('barangay')),
            'zipcode'     => trim((string) $request->input('zipcode')),
            'homeAddress' => trim((string) $request->input('homeAddress')),
        ]);

        $validator = Validator::make($request->all(), [
            'civilStatus' => ['required', Rule::in(self::CIVIL_STATUSES)],
            'province'    => ['required', 'string', 'max:150'],
            'city'        => ['required', 'string', 'max:150'],
            'barangay'    => ['required', 'string', 'max:150'],
            'zipcode'     => ['nullable', 'digits:4'],
            'homeAddress' => ['nullable', 'string', 'max:255'],
        ], [
            'civilStatus.required' => 'Please select your civil status.',
            'civilStatus.in'       => 'Please select a valid civil status.',
            'zipcode.digits'       => 'Zipcode must be 4 digits.',
        ], [
            'homeAddress' => 'home address',
            'civilStatus' => 'civil status',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $current = DB::table('borrowerpersonaldetails as bpd')
            ->leftJoin('borroweraddress as ba', 'ba.detail_id', '=', 'bpd.detail_id')
            ->where('bpd.detail_id', $details->detail_id)
            ->select('bpd.civil_status', 'ba.province', 'ba.city', 'ba.barangay', 'ba.zipcode', 'ba.home_address')
            ->first();

        $new = [
            'civil_status' => $request->input('civilStatus'),
            'province'     => $request->input('province'),
            'city'         => $request->input('city'),
            'barangay'     => $request->input('barangay'),
            'zipcode'      => $request->input('zipcode') ?: null,
            'home_address' => $request->input('homeAddress') ?: null,
        ];

        // Alin lang ang talagang nagbago (para sa update at sa audit log).
        $changes = [];
        foreach ($new as $field => $value) {
            $old = $current->{$field} ?? null;
            if ((string) $old !== (string) $value) {
                $changes[$field] = [$old, $value];
            }
        }

        if (!$changes) {
            return response()->json(['success' => true, 'message' => 'No changes to save.']);
        }

        try {
            DB::transaction(function () use ($details, $new, $changes, $userId, $request) {
                if (isset($changes['civil_status'])) {
                    DB::table('borrowerpersonaldetails')
                        ->where('user_id', $userId)
                        ->update(['civil_status' => $new['civil_status']]);
                }

                $addressChanges = array_intersect_key(
                    $new,
                    array_flip(array_diff(array_keys($changes), ['civil_status']))
                );

                if ($addressChanges) {
                    DB::table('borroweraddress')->updateOrInsert(
                        ['detail_id' => $details->detail_id],
                        $addressChanges
                    );
                }

                $this->audit($userId, $changes, 'self', $request->ip());
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Could not save your changes. Please try again.'], 500);
        }

        // Audit Logs: civil status / address na binago ng borrower mismo
        $oldValues = [];
        $newValues = [];
        foreach ($changes as $field => [$before, $after]) {
            $oldValues[$field] = $before;
            $newValues[$field] = $after;
        }

        AuditLogger::log('account.details_changed', [
            'entity_type' => 'user',
            'entity_id'   => $userId,
            'description' => 'Updated own ' . implode(', ', array_map(fn ($f) => str_replace('_', ' ', $f), array_keys($changes))),
            'old'         => $oldValues,
            'new'         => $newValues,
        ]);

        return response()->json(['success' => true, 'message' => 'Changes saved successfully!']);
    }

    // =====================================================================
    // EDITABLE (with OTP): cellphone number at email
    //
    //   phone -> OTP ay ipinapadala sa KASALUKUYANG email ng user
    //   email -> OTP ay ipinapadala sa BAGONG email
    //
    // Gumagamit ng otp_verifications table (parehong gamit sa registration)
    // at ng session para sa pending na pagbabago.
    // =====================================================================

    public function sendContactOtp(Request $request)
    {
        $userId = Session::get('user_id');
        $field  = $request->input('field');

        if (!in_array($field, ['phone', 'email'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid request.'], 422);
        }

        // Per-user na limit (dagdag sa throttle ng route).
        $rlKey = 'contact-otp:' . $userId;
        if (RateLimiter::tooManyAttempts($rlKey, 5)) {
            $minutes = ceil(RateLimiter::availableIn($rlKey) / 60);
            return response()->json([
                'success' => false,
                'message' => "Too many requests. Please try again in {$minutes} minute(s).",
            ], 429);
        }
        RateLimiter::hit($rlKey, 600);

        $user    = DB::table('users')->where('user_id', $userId)->first();
        $details = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();

        if (!$user || !$details) {
            return response()->json(['success' => false, 'message' => 'Profile not found.'], 404);
        }

        if ($field === 'phone') {
            $digits = $this->normalizePhoneDigits((string) $request->input('value'));

            if (!$digits) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid mobile number (e.g. +63 912 345 6789).',
                ], 422);
            }

            if ($this->phoneDigits($details->cellphone_num) === $digits) {
                return response()->json(['success' => false, 'message' => 'That is already your current number.'], 422);
            }

            $inUse = DB::table('borrowerpersonaldetails')
                ->where('user_id', '!=', $userId)
                ->whereRaw("regexp_replace(cellphone_num, '\\D', '', 'g') = ?", [$digits])
                ->exists();

            if ($inUse) {
                return response()->json(['success' => false, 'message' => 'This mobile number is already registered.'], 422);
            }

            // Parehong format ng nasa DB: "+63 9XXXXXXXXX"
            $value    = '+63 ' . substr($digits, 2);
            $otpEmail = $user->email;
            $type     = 'change_phone';
        } else {
            $email = $this->normalizeEmail((string) $request->input('value'));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
                return response()->json(['success' => false, 'message' => 'Please enter a valid email address.'], 422);
            }

            if ($email === $this->normalizeEmail((string) $user->email)) {
                return response()->json(['success' => false, 'message' => 'That is already your current email.'], 422);
            }

            $taken = DB::table('users')
                ->where('email', $email)
                ->where('user_id', '!=', $userId)
                ->exists();

            if ($taken) {
                return response()->json(['success' => false, 'message' => 'Email is already registered.'], 422);
            }

            $value    = $email;
            $otpEmail = $email;
            $type     = 'change_email';
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('otp_verifications')->where('email', $otpEmail)->where('type', $type)->delete();
        DB::table('otp_verifications')->insert([
            'email'      => $otpEmail,
            'otp'        => Hash::make($otp),
            'type'       => $type,
            'expires_at' => now()->addMinutes(self::OTP_MINUTES),
        ]);

        try {
            Mail::to($otpEmail)->send(new OtpMail((string) ($details->first_name ?? ''), $otp));
        } catch (\Throwable $e) {
            report($e);
            DB::table('otp_verifications')->where('email', $otpEmail)->where('type', $type)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Could not send the code. Please try again in a moment.',
            ], 500);
        }

        Session::put('pending_contact_change', [
            'user_id'   => $userId,
            'field'     => $field,
            'value'     => $value,
            'otp_email' => $otpEmail,
            'type'      => $type,
            'attempts'  => 0,
        ]);

        return response()->json([
            'success' => true,
            'sent_to' => $this->maskEmail($otpEmail),
        ]);
    }

    public function verifyContactOtp(Request $request)
    {
        $userId  = Session::get('user_id');
        $pending = Session::get('pending_contact_change');

        if (!$pending || (string) ($pending['user_id'] ?? '') !== (string) $userId) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please start again.'], 422);
        }

        $otp = trim((string) $request->input('otp'));
        if (!preg_match('/^\d{6}$/', $otp)) {
            return response()->json(['success' => false, 'message' => 'Please enter the 6-digit code.'], 422);
        }

        $record = DB::table('otp_verifications')
            ->where('email', $pending['otp_email'])
            ->where('type', $pending['type'])
            ->where('expires_at', '>', now())
            ->where('is_used', 0)
            ->first();

        if (!$record || !Hash::check($otp, $record->otp)) {
            $pending['attempts'] = ($pending['attempts'] ?? 0) + 1;

            if ($pending['attempts'] >= 5) {
                Session::forget('pending_contact_change');
                DB::table('otp_verifications')
                    ->where('email', $pending['otp_email'])
                    ->where('type', $pending['type'])
                    ->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Too many wrong attempts. Please request a new code.',
                ], 429);
            }

            Session::put('pending_contact_change', $pending);
            return response()->json(['success' => false, 'message' => 'Invalid or expired code.'], 422);
        }

        $oldValue = null;

        try {
            DB::transaction(function () use ($pending, $record, $userId, $request, &$oldValue) {
                DB::table('otp_verifications')->where('id', $record->id)->update(['is_used' => 1]);

                if ($pending['field'] === 'phone') {
                    $old = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->value('cellphone_num');
                    $oldValue = $old;

                    DB::table('borrowerpersonaldetails')
                        ->where('user_id', $userId)
                        ->update(['cellphone_num' => $pending['value']]);

                    $this->audit($userId, ['cellphone_num' => [$old, $pending['value']]], 'self', $request->ip());
                } else {
                    $old = DB::table('users')->where('user_id', $userId)->value('email');
                    $oldValue = $old;

                    DB::table('users')->where('user_id', $userId)->update(['email' => $pending['value']]);
                    DB::table('borrowerpersonaldetails')->where('user_id', $userId)->update(['email' => $pending['value']]);

                    $this->audit($userId, ['email' => [$old, $pending['value']]], 'self', $request->ip());
                }
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                return response()->json(['success' => false, 'message' => 'That value is already registered.'], 422);
            }
            report($e);
            return response()->json(['success' => false, 'message' => 'Could not save your changes. Please try again.'], 500);
        }

        if ($pending['field'] === 'email') {
            Session::put('user_email', $pending['value']);
        }

        Session::forget('pending_contact_change');

        // Audit Logs: cellphone o email na na-verify gamit ang OTP
        $isPhone = $pending['field'] === 'phone';
        AuditLogger::log($isPhone ? 'account.phone_changed' : 'account.email_changed', [
            'entity_type' => 'user',
            'entity_id'   => $userId,
            'description' => $isPhone ? 'Changed cellphone number (verified by OTP)' : 'Changed email address (verified by OTP)',
            'old'         => [$pending['field'] => $oldValue],
            'new'         => [$pending['field'] => $pending['value']],
        ]);

        return response()->json([
            'success' => true,
            'field'   => $pending['field'],
            'value'   => $pending['value'],
            'message' => $pending['field'] === 'phone'
                ? 'Cellphone number updated.'
                : 'Email updated.',
        ]);
    }

    // =====================================================================
    // LOCKED FIELDS: change request (admin ang nagre-review)
    // =====================================================================

    public function submitChangeRequest(Request $request)
    {
        $userId  = Session::get('user_id');
        $details = DB::table('borrowerpersonaldetails')->where('user_id', $userId)->first();

        if (!$details) {
            return response()->json(['success' => false, 'message' => 'Profile not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'field'     => ['required', Rule::in(array_keys(self::CHANGE_REQUEST_FIELDS))],
            'new_value' => ['required', 'string', 'max:255'],
            'reason'    => ['required', 'string', 'max:500'],
            'document'  => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
        ], [
            'field.required'     => 'Please choose what you want to change.',
            'field.in'           => 'Please choose a valid field.',
            'new_value.required' => 'Please enter the correct value.',
            'reason.required'    => 'Please tell us why you need this change.',
            'document.required'  => 'Please attach a supporting document.',
            'document.mimes'     => 'The document must be a JPG, PNG or PDF file.',
            'document.mimetypes' => 'The document must be a JPG, PNG or PDF file.',
            'document.max'       => 'The document must be less than 5MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $field    = $request->input('field');
        $newValue = trim((string) $request->input('new_value'));

        // Field-specific na validation ng bagong value.
        if ($field === 'gender' && !in_array(strtolower($newValue), ['male', 'female'], true)) {
            return response()->json(['success' => false, 'message' => 'Gender must be male or female.'], 422);
        }

        if ($field === 'birthday') {
            try {
                $date = Carbon::createFromFormat('Y-m-d', $newValue);
            } catch (\Throwable $e) {
                $date = null;
            }

            if (!$date || $date->format('Y-m-d') !== $newValue || $date->year < 1900) {
                return response()->json(['success' => false, 'message' => 'Please enter a valid birthday.'], 422);
            }
            if ($date->age < 18) {
                return response()->json(['success' => false, 'message' => 'You must be at least 18 years old.'], 422);
            }
        }

        if ($field === 'monthly_income' && !in_array($newValue, self::INCOME_BRACKETS, true)) {
            return response()->json(['success' => false, 'message' => 'Please choose a valid income range.'], 422);
        }

        $hasPending = DB::table('profile_change_requests')
            ->where('user_id', $userId)
            ->where('field', $field)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending request for this field.',
            ], 422);
        }

        $file = $request->file('document');
        $path = 'change-requests/' . uniqid() . '.' . $file->extension();
        Storage::disk('valid_ids')->put($path, file_get_contents($file));

        try {
            DB::table('profile_change_requests')->insert([
                'uuid'          => (string) Str::uuid(),
                'user_id'       => $userId,
                'field'         => $field,
                'old_value'     => $this->currentValueForField($details, $field),
                'new_value'     => $newValue,
                'reason'        => trim((string) $request->input('reason')),
                'document_path' => $path,
                'status'        => 'pending',
                'created_at'    => now(),
            ]);
        } catch (QueryException $e) {
            Storage::disk('valid_ids')->delete($path);

            if ($e->getCode() === '23505') {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a pending request for this field.',
                ], 422);
            }

            report($e);
            return response()->json(['success' => false, 'message' => 'Could not submit your request. Please try again.'], 500);
        }

        // Audit Logs: humiling ang borrower na baguhin ang locked field
        AuditLogger::log('account.change_requested', [
            'entity_type' => 'profile_change_request',
            'description' => 'Requested a change of ' . self::CHANGE_REQUEST_FIELDS[$field],
            'old'         => [$field => $this->currentValueForField($details, $field)],
            'new'         => [$field => $newValue],
            'context'     => ['reason' => trim((string) $request->input('reason'))],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request submitted. We will review it and get back to you.',
        ]);
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    /**
     * Piling mga column lang ang kinukuha (hindi kasama ang ID file paths).
     */
    private function loadProfileData($userId): array
    {
        $row = DB::table('borrowerpersonaldetails as bpd')
            ->leftJoin('borroweraddress as ba', 'ba.detail_id', '=', 'bpd.detail_id')
            ->leftJoin('borrowerworkinfo as bwi', 'bwi.detail_id', '=', 'bpd.detail_id')
            ->where('bpd.user_id', $userId)
            ->select(
                'bpd.last_name', 'bpd.first_name', 'bpd.middle_name', 'bpd.birthday',
                'bpd.gender', 'bpd.citizenship', 'bpd.civil_status', 'bpd.cellphone_num',
                'bpd.email', 'bpd.monthly_income',
                'ba.region', 'ba.province', 'ba.city', 'ba.barangay', 'ba.zipcode', 'ba.home_address',
                'bwi.employment_status', 'bwi.company_name', 'bwi.position', 'bwi.years_in_company'
            )
            ->first();

        return $row ? (array) $row : [];
    }

    private function currentValueForField($details, string $field): ?string
    {
        return match ($field) {
            'name'           => trim(implode(' ', array_filter([
                                    $details->first_name ?? '',
                                    $details->middle_name ?? '',
                                    $details->last_name ?? '',
                                ]))),
            'birthday'       => $details->birthday ? (string) $details->birthday : null,
            'gender'         => $details->gender ?? null,
            'citizenship'    => $details->citizenship ?? null,
            'monthly_income' => isset($details->monthly_income) ? (string) $details->monthly_income : null,
            default          => null,
        };
    }

    /**
     * Isang row sa profile_audit_logs bawat nagbagong field.
     * $changes = ['field' => [oldValue, newValue], ...]
     */
    private function audit($userId, array $changes, string $source, ?string $ip): void
    {
        $rows = [];

        foreach ($changes as $field => [$old, $new]) {
            $rows[] = [
                'user_id'    => $userId,
                'field'      => $field,
                'old_value'  => $old,
                'new_value'  => $new,
                'source'     => $source,
                'ip_address' => $ip,
                'created_at' => now(),
            ];
        }

        if ($rows) {
            DB::table('profile_audit_logs')->insert($rows);
        }
    }

    // Kopya ng AuthController::normalizeEmail() (private doon). Kapag gusto
    // mong isang lugar lang, ilipat sa isang helper class at gamitin ng pareho.
    private function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));

        if (!str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        $local = preg_replace('/\+.*$/', '', $local);

        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $local = str_replace('.', '', $local);
        }

        return $local . '@' . $domain;
    }

    // Mga digit lang ng stored/typed na number, hal. "+63 9424423232" -> "639424423232"
    private function phoneDigits(?string $raw): string
    {
        return preg_replace('/\D/', '', (string) $raw);
    }

    // Tumatanggap ng +63 9XX..., 639XX..., o 09XX...; nagbabalik ng "639XXXXXXXXX" o null.
    private function normalizePhoneDigits(string $raw): ?string
    {
        $digits = $this->phoneDigits($raw);

        if (preg_match('/^09\d{9}$/', $digits)) {
            $digits = '63' . substr($digits, 1);
        }

        return preg_match('/^639\d{9}$/', $digits) ? $digits : null;
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return substr($local, 0, 1) . '***@' . $domain;
    }
}