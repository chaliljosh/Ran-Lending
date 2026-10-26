<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * I-normalize ang email bago ito gamitin sa anumang uniqueness check,
     * OTP rate-limit key, o pag-store sa DB. Tinatanggal ang "+alias" na
     * bahagi ng local part (RFC 5233 sub-addressing, suportado ng Gmail,
     * Outlook, Yahoo, atbp.) — ito yung ginagamit na loophole para
     * makagawa ng maraming "magkaibang" account gamit ang parehong
     * totoong inbox (hal. juan@gmail.com at juan+2@gmail.com).
     *
     * Para sa Gmail/Googlemail specifically, tinatanggal din ang mga
     * tuldok (.) sa local part, dahil kinakatas ito ni Gmail sa totoong
     * pag-deliver (juan.delacruz@gmail.com === juandelacruz@gmail.com —
     * parehong loophole din pala 'to). Hindi ito ginagawa sa ibang
     * domain dahil may mga provider na treated bilang significant ang
     * tuldok sa kanilang local part.
     */
    private function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));

        if (!str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        // Tanggalin ang "+alias" suffix
        $local = preg_replace('/\+.*$/', '', $local);

        // Gmail-specific: tanggalin din ang mga tuldok
        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $local = str_replace('.', '', $local);
        }

        return $local . '@' . $domain;
    }

    public function login(Request $request)
    {
        $email    = $this->normalizeEmail($request->input('email'));
        $password = $request->input('password');

        if (!$email || !$password) {
            return response()->json(['success' => false, 'message' => 'Email and password are required.']);
        }

        // Dalawang klase ng rate limit, pareho tinitignan:
        // - per-IP: pumipigil sa isang machine na mag-spam ng attempts
        //   sa maraming accounts.
        // - per-email: pumipigil sa distributed attack (maraming IP,
        //   proxy/VPN/botnet) laban sa isang specific na account —
        //   hindi kayang harangin ng per-IP limit lang.
        $ipKey    = 'login:' . $request->ip();
        $emailKey = 'login-email:' . $email;

        if (RateLimiter::tooManyAttempts($ipKey, 5) || RateLimiter::tooManyAttempts($emailKey, 5)) {
            $seconds = max(
                RateLimiter::availableIn($ipKey),
                RateLimiter::availableIn($emailKey)
            );
            $minutes = ceil($seconds / 60);
            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Try again in {$minutes} minute(s)."
            ]);
        }

        $user = DB::table('users')->where('email', $email)->where('is_active', 1)->first();

        if (!$user || !Hash::check($password, $user->password_hash)) {
            RateLimiter::hit($ipKey, 600);
            RateLimiter::hit($emailKey, 600);
            return response()->json(['success' => false, 'message' => 'Invalid email or password.']);
        }

        RateLimiter::clear($ipKey);
        RateLimiter::clear($emailKey);

        // Walang OTP step — diretso nang mag-lo-login pagkatapos ma-verify
        // ang email/password, kapwa admin at borrower. (Adviser's call:
        // OTP na lang sa register at forgot-password.)
        Session::regenerate();

        Session::put('user_id',    $user->user_id);
        Session::put('user_name',  $user->name);
        Session::put('user_email', $user->email);
        Session::put('user_role',  $user->role);
        Session::put('user_profile_picture', $user->profile_picture ?? null);

        DB::table('users')->where('user_id', $user->user_id)->update([
            'last_login_at' => now(),
        ]);

        $redirect = $user->role === 'admin'
            ? route('admin.dashboard')
            : route('borrower.dashboard');

        return response()->json([
            'success'  => true,
            'redirect' => $redirect,
        ]);
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('login');
    }

    // =====================================================================
    // REGISTRATION FLOW
    // =====================================================================

    public function register(Request $request)
{
    $request->merge([
        'email'      => $this->normalizeEmail((string) $request->input('email')),
        'firstName'  => trim((string) $request->input('firstName')),
        'middleName' => trim((string) $request->input('middleName')),
        'lastName'   => trim((string) $request->input('lastName')),
    ]);

    $validator = Validator::make($request->all(), [
        'firstName'       => ['required', 'string', 'max:100'],
        'middleName'      => ['nullable', 'regex:/^[A-Za-z]$/'],
        'lastName'        => ['required', 'string', 'max:100'],
        'email'           => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        'password'        => [
            'required', 'string', 'min:8',
            function ($attribute, $value, $fail) {
                if (!preg_match('/[A-Z]/', $value)) {
                    $fail('Password must include at least 1 uppercase letter.');
                }
                if (!preg_match('/[0-9]/', $value)) {
                    $fail('Password must include at least 1 number.');
                }
            },
            'same:confirmPassword',
        ],
        'confirmPassword' => ['required'],
        'birthMonth'      => ['required', 'integer'],
        'birthDay'        => ['required', 'integer'],
        'birthYear'       => ['required', 'integer'],
        'suffix'          => ['nullable', Rule::in(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'])],
        'gender'          => ['required', Rule::in(['male', 'female'])],
        'civilStatus'     => ['required', Rule::in(['single', 'taken', 'married', 'widowed', 'separated'])],
        'citizenship'     => ['required', 'string', 'max:100'],
        'cellphone'       => [
            'required', 'string', 'max:20',
            function ($attribute, $value, $fail) {
                if (!preg_match('/^639\d{9}$/', preg_replace('/\D/', '', $value))) {
                    $fail('Please enter a valid mobile number (e.g. +63 912 345 6789).');
                }
            },
        ],
        'birthCountry'    => ['required', 'string', 'max:100'],
        'birthProvince'   => ['required', 'string', 'max:100'],
        'birthCity'       => ['required', 'string', 'max:100'],
        'monthlyIncome'   => ['required', Rule::in([
            'Below ₱10,000',
            '₱10,000 - ₱20,000',
            '₱20,001 - ₱30,000',
            '₱30,001 - ₱50,000',
            '₱50,001 - ₱100,000',
            'Above ₱100,000',
        ])],
        'region'          => ['required', 'string', 'max:150'],
        'province'        => ['required', 'string', 'max:150'],
        'city'            => ['required', 'string', 'max:150'],
        'barangay'        => ['required', 'string', 'max:150'],
        'zipcode'         => ['nullable', 'digits:4'],
        'homeAddress'     => ['nullable', 'string', 'max:255'],
    ], [
        'password.same'    => 'Passwords do not match.',
        'password.min'     => 'Password must be at least 8 characters.',
        'middleName.regex' => 'Middle name must be a single letter.',
        'email.unique'     => 'Email is already registered.',
        'email.email'      => 'Please enter a valid email address.',
        'zipcode.digits'   => 'Zipcode must be 4 digits.',
    ], [
        'firstName'       => 'first name',
        'lastName'        => 'last name',
        'confirmPassword' => 'confirm password',
        'birthMonth'      => 'birth month',
        'birthDay'        => 'birth day',
        'birthYear'       => 'birth year',
        'civilStatus'     => 'civil status',
        'birthCountry'    => 'birth country',
        'birthProvince'   => 'birth province',
        'birthCity'       => 'birth city',
        'monthlyIncome'   => 'monthly income',
        'homeAddress'     => 'home address',
    ]);

    $validator->after(function ($v) use ($request) {
        if ($v->errors()->hasAny(['birthMonth', 'birthDay', 'birthYear'])) {
            return;
        }

        $month = (int) $request->input('birthMonth');
        $day   = (int) $request->input('birthDay');
        $year  = (int) $request->input('birthYear');

        if ($year < 1900 || !checkdate($month, $day, $year)) {
            $v->errors()->add('birthday', 'Please enter a valid birthday.');
            return;
        }

        if (Carbon::create($year, $month, $day)->age < 18) {
            $v->errors()->add('birthday', 'You must be at least 18 years old to register.');
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors()->toArray(),
        ], 422);
    }

    $firstName  = $request->input('firstName');
    $middleName = (string) $request->input('middleName');
    $lastName   = $request->input('lastName');
    $email      = $request->input('email');
    $birthday   = sprintf(
        '%04d-%02d-%02d',
        (int) $request->input('birthYear'),
        (int) $request->input('birthMonth'),
        (int) $request->input('birthDay')
    );

    Session::put('pending_register', [
        'first_name'    => $firstName,
        'middle_name'   => $middleName,
        'last_name'     => $lastName,
        'email'         => $email,
        'password_hash' => Hash::make($request->input('password')),
        'birthday'      => $birthday,
        'details'       => [
            'suffix'         => $request->input('suffix') ?: null,
            'gender'         => $request->input('gender'),
            'civil_status'   => $request->input('civilStatus'),
            'citizenship'    => trim($request->input('citizenship')),
            'birth_country'  => trim($request->input('birthCountry')),
            'birth_province' => trim($request->input('birthProvince')),
            'birth_city'     => trim($request->input('birthCity')),
            'cellphone_num'  => trim($request->input('cellphone')),
            'monthly_income' => $request->input('monthlyIncome'),
        ],
        'address'       => [
            'region'       => $request->input('region'),
            'province'     => $request->input('province'),
            'city'         => $request->input('city'),
            'barangay'     => $request->input('barangay'),
            'zipcode'      => $request->input('zipcode'),
            'home_address' => $request->input('homeAddress'),
        ],
    ]);

    $otp       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = now()->addMinutes(10);

    DB::table('otp_verifications')->where('email', $email)->where('type', 'register')->delete();
    DB::table('otp_verifications')->insert([
        'email'      => $email,
        'otp'        => Hash::make($otp),
        'type'       => 'register',
        'expires_at' => $expiresAt,
    ]);

    Mail::to($email)->send(new \App\Mail\OtpMail($firstName, $otp));

    return response()->json(['success' => true, 'email' => $email]);
}

   public function verifyRegisterOtp(Request $request)
{
    $email = $this->normalizeEmail($request->input('email'));
    $otp   = trim($request->input('otp'));

    if (!$email || !$otp) {
        return response()->json(['success' => false, 'message' => 'OTP is required.']);
    }

    $pending = Session::get('pending_register');
    if (!$pending || $this->normalizeEmail($pending['email']) !== $email) {
        return response()->json(['success' => false, 'message' => 'Session expired. Please register again.']);
    }

    $record = DB::table('otp_verifications')
        ->where('email', $email)
        ->where('type', 'register')
        ->where('expires_at', '>', now())
        ->where('is_used', 0)
        ->first();

    if (!$record || !Hash::check($otp, $record->otp)) {
        return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.']);
    }

    $fullName = trim(preg_replace('/\s+/', ' ', $pending['first_name'] . ' ' . $pending['middle_name'] . ' ' . $pending['last_name']));

    try {
        DB::transaction(function () use ($pending, $record, $fullName) {
            DB::table('otp_verifications')->where('id', $record->id)->update(['is_used' => 1]);

            $userId = DB::table('users')->insertGetId([
                'first_name'    => $pending['first_name'],
                'middle_name'   => $pending['middle_name'],
                'last_name'     => $pending['last_name'],
                'name'          => $fullName,
                'email'         => $pending['email'],
                'password_hash' => $pending['password_hash'],
                'birthday'      => $pending['birthday'],
                'role'          => 'borrower',
                'is_active'     => 1,
                'created_at'    => now(),
            ], 'user_id');

            $detailId = DB::table('borrowerpersonaldetails')->insertGetId(array_merge($pending['details'], [
                'uuid'        => (string) Str::uuid(),
                'user_id'     => $userId,
                'last_name'   => $pending['last_name'],
                'first_name'  => $pending['first_name'],
                'middle_name' => $pending['middle_name'],
                'birthday'    => $pending['birthday'],
                'email'       => $pending['email'],
                'status'      => 'approved',
                'created_at'  => now(),
            ]), 'detail_id');

            DB::table('borroweraddress')->insert(array_merge($pending['address'], [
                'detail_id' => $detailId,
            ]));
        });
    } catch (QueryException $e) {
        report($e);
        return response()->json([
            'success' => false,
            'message' => 'Could not create your account. Please try again.',
        ], 500);
    }

    Session::forget('pending_register');

    return response()->json(['success' => true]);
}

    public function resendRegisterOtp(Request $request)
    {
        $email   = $this->normalizeEmail($request->input('email'));
        $pending = Session::get('pending_register');

        if (!$pending || $this->normalizeEmail($pending['email']) !== $email) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please register again.']);
        }

        $otp       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        DB::table('otp_verifications')->where('email', $email)->where('type', 'register')->delete();
        DB::table('otp_verifications')->insert([
            'email'      => $email,
            'otp'        => Hash::make($otp),
            'type'       => 'register',
            'expires_at' => $expiresAt,
        ]);

        Mail::to($email)->send(new \App\Mail\OtpMail($pending['first_name'], $otp));

        return response()->json(['success' => true]);
    }

    public function checkRegisterEmail(Request $request)
{
    $email = $this->normalizeEmail((string) $request->input('email'));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return response()->json([
            'success' => false,
            'message' => 'Please enter a valid email address.',
        ], 422);
    }

    if (DB::table('users')->where('email', $email)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Email is already registered.',
        ], 422);
    }

    return response()->json(['success' => true]);
}
}