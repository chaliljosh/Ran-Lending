<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
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
    /** Mensahe kapag may nakarehistro nang may parehong buong pangalan. */
    private const DUPLICATE_NAME_MESSAGE = 'An account with this name is already registered. If this is you, please sign in instead.';

    /** Mga mensahe para sa cellphone number. */
    private const INVALID_PHONE_MESSAGE   = 'Please enter a valid mobile number (e.g. +63 912 345 6789).';
    private const DUPLICATE_PHONE_MESSAGE = 'This mobile number is already registered. Please use a different number.';

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

    /**
     * May nakarehistro na bang borrower na may parehong buong pangalan?
     * Kinukumpara ang first name + middle initial + last name + suffix.
     * Hindi pinapansin ang laki ng letra at sobrang espasyo.
     * ("Jr." at "Sr." ay magkaibang tao.)
     */
    private function fullNameExists(?string $first, ?string $middle, ?string $last, ?string $suffix): bool
    {
        $norm = fn ($v) => preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $v)));

        $first  = $norm($first);
        $last   = $norm($last);
        $middle = mb_substr($norm($middle), 0, 1);
        $suffix = str_replace('.', '', $norm($suffix));

        if ($first === '' || $last === '') {
            return false;
        }

        return DB::table('borrowerpersonaldetails')
            ->whereRaw("regexp_replace(LOWER(TRIM(first_name)), '\\s+', ' ', 'g') = ?", [$first])
            ->whereRaw("regexp_replace(LOWER(TRIM(last_name)), '\\s+', ' ', 'g') = ?", [$last])
            ->whereRaw("LOWER(LEFT(COALESCE(TRIM(middle_name), ''), 1)) = ?", [$middle])
            ->whereRaw("LOWER(REPLACE(COALESCE(TRIM(suffix), ''), '.', '')) = ?", [$suffix])
            ->exists();
    }

    /** Numbers lang ang kukunin sa cellphone (tinatanggal ang +, espasyo, atbp.). */
    private function phoneDigits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    /**
     * Tama ba ang format ng PH mobile number at hindi ba halatang peke?
     * Dapat 639XXXXXXXXX. Bawal ang iisang numero lang ang inuulit
     * (9111111111, 9999999999) at ang 9123456789 / 9876543210.
     */
    private function isValidPhoneNumber(?string $value): bool
    {
        $digits = $this->phoneDigits($value);

        if (!preg_match('/^639\d{9}$/', $digits)) {
            return false;
        }

        $local = substr($digits, 2); // 9XXXXXXXXX

        if (preg_match('/^9(\d)\1{8}$/', $local)) {
            return false;
        }

        if (in_array($local, ['9123456789', '9876543210'], true)) {
            return false;
        }

        return true;
    }

    /**
     * May nakarehistro na bang borrower na may parehong cellphone number?
     * Numbers lang ang kinukumpara, kaya hindi mahalaga ang format na na-save.
     */
    private function cellphoneExists(?string $value): bool
    {
        $digits = $this->phoneDigits($value);

        if ($digits === '') {
            return false;
        }

        return DB::table('borrowerpersonaldetails')
            ->whereRaw("regexp_replace(COALESCE(cellphone_num, ''), '\\D', '', 'g') = ?", [$digits])
            ->exists();
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

        // Audit Logs: admin at borrower
        if (in_array($user->role, ['admin', 'borrower'], true)) {
            AuditLogger::log('auth.login', [
                'entity_type' => 'user',
                'entity_id'   => $user->user_id,
                'description' => ucfirst($user->role) . ' ' . ($user->name ?: $user->email) . ' logged in',
                'actor'       => ['id' => $user->user_id, 'role' => $user->role, 'name' => $user->name],
            ]);
        }

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
        // Audit Logs: i-log muna bago ma-flush ang session (admin at borrower)
        $role = Session::get('user_role');
        if (in_array($role, ['admin', 'borrower'], true) && Session::get('user_id')) {
            AuditLogger::log('auth.logout', [
                'entity_type' => 'user',
                'entity_id'   => Session::get('user_id'),
                'description' => ucfirst($role) . ' ' . (Session::get('user_name') ?: Session::get('user_email')) . ' logged out',
                'actor'       => [
                    'id'   => Session::get('user_id'),
                    'role' => $role,
                    'name' => Session::get('user_name'),
                ],
            ]);
        }

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
            // Initial lang (isang letra) ang tinatanggap dito. Ang register.js ang
            // nagko-convert ng full middle name papunta sa initial bago i-send.
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
                    // Format at peke (hal. 9111111111)
                    if (!$this->isValidPhoneNumber($value)) {
                        $fail(self::INVALID_PHONE_MESSAGE);
                        return;
                    }

                    // Bawal ang numerong nakarehistro na
                    if ($this->cellphoneExists($value)) {
                        $fail(self::DUPLICATE_PHONE_MESSAGE);
                    }
                },
            ],
            'birthCountry'    => ['required', 'string', 'max:100'],
            'birthProvince'   => ['required', 'string', 'max:100'],
            'birthCity'       => ['required', 'string', 'max:100'],
            'region'          => ['required', 'string', 'max:150'],
            'province'        => ['required', 'string', 'max:150'],
            'city'            => ['required', 'string', 'max:150'],
            'barangay'        => ['required', 'string', 'max:150'],
            'zipcode'         => ['nullable', 'digits:4'],
            'homeAddress'     => ['required', 'string', 'max:255'],

            // Permanent address: kailangan lang kapag hindi "same as current"
            'permSame'        => ['nullable', Rule::in(['0', '1'])],
            'permRegion'      => ['required_if:permSame,0', 'nullable', 'string', 'max:150'],
            'permProvince'    => ['required_if:permSame,0', 'nullable', 'string', 'max:150'],
            'permCity'        => ['required_if:permSame,0', 'nullable', 'string', 'max:150'],
            'permBarangay'    => ['required_if:permSame,0', 'nullable', 'string', 'max:150'],
            'permZipcode'     => ['nullable', 'digits:4'],
            'permHomeAddress' => ['required_if:permSame,0', 'nullable', 'string', 'max:255'],
        ], [
            'password.same'    => 'Passwords do not match.',
            'password.min'     => 'Password must be at least 8 characters.',
            'middleName.regex' => 'Middle name must be a single letter.',
            'email.unique'     => 'Email is already registered.',
            'email.email'      => 'Please enter a valid email address.',
            'zipcode.digits'   => 'Zipcode must be 4 digits.',
            'homeAddress.required'         => 'This field is required.',
            'permRegion.required_if'       => 'This field is required.',
            'permProvince.required_if'     => 'This field is required.',
            'permCity.required_if'         => 'This field is required.',
            'permBarangay.required_if'     => 'This field is required.',
            'permHomeAddress.required_if'  => 'This field is required.',
            'permZipcode.digits'           => 'Zipcode must be 4 digits.',
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
            'homeAddress'     => 'home address',
        ]);

        $validator->after(function ($v) use ($request) {
            // Bawal ang parehong buong pangalan, kahit ibang email ang gamit.
            if (!$v->errors()->hasAny(['firstName', 'middleName', 'lastName', 'suffix'])
                && $this->fullNameExists(
                    $request->input('firstName'),
                    $request->input('middleName'),
                    $request->input('lastName'),
                    $request->input('suffix')
                )) {
                $v->errors()->add('firstName', self::DUPLICATE_NAME_MESSAGE);
            }

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

        // Permanent address: kapag "same as current" (default), kinokopya ang current address.
        $samePermanent = $request->input('permSame', '1') !== '0';

        $address = [
            'region'       => $request->input('region'),
            'province'     => $request->input('province'),
            'city'         => $request->input('city'),
            'barangay'     => $request->input('barangay'),
            'zipcode'      => $request->input('zipcode'),
            'home_address' => $request->input('homeAddress'),

            'perm_same_as_current' => $samePermanent,
            'perm_region'          => $samePermanent ? $request->input('region')      : $request->input('permRegion'),
            'perm_province'        => $samePermanent ? $request->input('province')    : $request->input('permProvince'),
            'perm_city'            => $samePermanent ? $request->input('city')        : $request->input('permCity'),
            'perm_barangay'        => $samePermanent ? $request->input('barangay')    : $request->input('permBarangay'),
            'perm_zipcode'         => $samePermanent ? $request->input('zipcode')     : $request->input('permZipcode'),
            'perm_home_address'    => $samePermanent ? $request->input('homeAddress') : $request->input('permHomeAddress'),
        ];

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
            ],
            'address'       => $address,
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

        // Huling check bago gumawa ng account: baka may ibang nakarehistro na
        // ng parehong pangalan habang hinihintay ang OTP.
        if ($this->fullNameExists(
            $pending['first_name'],
            $pending['middle_name'],
            $pending['last_name'],
            $pending['details']['suffix'] ?? null
        )) {
            return response()->json(['success' => false, 'message' => self::DUPLICATE_NAME_MESSAGE]);
        }

        // Huling check din para sa cellphone number.
        if ($this->cellphoneExists($pending['details']['cellphone_num'] ?? null)) {
            return response()->json(['success' => false, 'message' => self::DUPLICATE_PHONE_MESSAGE]);
        }

        $fullName = trim(preg_replace('/\s+/', ' ', $pending['first_name'] . ' ' . $pending['middle_name'] . ' ' . $pending['last_name']));

        try {
            $userId = DB::transaction(function () use ($pending, $record, $fullName) {
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

                return $userId;
            });
        } catch (QueryException $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Could not create your account. Please try again.',
            ], 500);
        }

        Session::forget('pending_register');

        // Audit Logs: natapos ang pag-register ng borrower (pagkatapos ma-verify ang OTP)
        AuditLogger::log('auth.registered', [
            'entity_type' => 'user',
            'entity_id'   => $userId,
            'description' => 'New borrower ' . $fullName . ' registered an account',
            'actor'       => ['id' => $userId, 'role' => 'borrower', 'name' => $fullName],
        ]);

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

    /**
     * POST /api/register/check-name
     * Tinatawag ng register.js pagpindot ng "Next" sa Step 1.
     * Expects: firstName, middleName (initial), lastName, suffix.
     */
    public function checkRegisterName(Request $request)
    {
        if ($this->fullNameExists(
            (string) $request->input('firstName'),
            (string) $request->input('middleName'),
            (string) $request->input('lastName'),
            (string) $request->input('suffix')
        )) {
            return response()->json([
                'success' => false,
                'message' => self::DUPLICATE_NAME_MESSAGE,
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/register/check-cellphone
     * Tinatawag ng register.js pagpindot ng "Next" sa Step 2.
     * Expects: cellphone (hal. "+63 912 345 6789").
     */
    public function checkRegisterCellphone(Request $request)
    {
        $cellphone = (string) $request->input('cellphone');

        if (!$this->isValidPhoneNumber($cellphone)) {
            return response()->json([
                'success' => false,
                'message' => self::INVALID_PHONE_MESSAGE,
            ], 422);
        }

        if ($this->cellphoneExists($cellphone)) {
            return response()->json([
                'success' => false,
                'message' => self::DUPLICATE_PHONE_MESSAGE,
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}