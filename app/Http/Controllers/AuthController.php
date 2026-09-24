<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;

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
        $lastName        = trim($request->input('lastName'));
        $firstName       = trim($request->input('firstName'));
        $middleName      = trim($request->input('middleName'));
        $email           = $this->normalizeEmail($request->input('email'));
        $password        = $request->input('password');
        $confirmPassword = $request->input('confirmPassword');
        $birthMonth      = $request->input('birthMonth');
        $birthDay        = $request->input('birthDay');
        $birthYear       = $request->input('birthYear');

        if (!$lastName || !$firstName || !$email || !$password || !$confirmPassword) {
            return response()->json(['success' => false, 'message' => 'Please fill in all required fields.']);
        }

                // Middle name ay optional, pero kung meron, isang letra lang dapat.
        if ($middleName !== '' && !preg_match('/^[A-Za-z]$/', $middleName)) {
            return response()->json(['success' => false, 'message' => 'Middle name must be a single letter.']);
        }

        if ($password !== $confirmPassword) {
            return response()->json(['success' => false, 'message' => 'Passwords do not match.']);
        }

        if (strlen($password) < 8) {
            return response()->json(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        }

        if ($birthMonth && $birthDay && $birthYear) {
            $birthday = sprintf('%04d-%02d-%02d', $birthYear, $birthMonth, $birthDay);
            $age = \Carbon\Carbon::parse($birthday)->age;
            if ($age < 18) {
                return response()->json(['success' => false, 'message' => 'You must be at least 18 years old to register.']);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Please enter your complete birthday.']);
        }

        // Uniqueness check gamit ang NORMALIZED email — kaya kahit
        // "juan+2@gmail.com" o "j.uan@gmail.com" ang itype, mahaharang
        // pa rin kung existing na ang "juan@gmail.com".
        $exists = DB::table('users')->where('email', $email)->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Email is already registered.']);
        }

        Session::put('pending_register', [
            'first_name'  => $firstName,
            'middle_name' => $middleName,
            'last_name'   => $lastName,
            'email'       => $email,
            'password'    => $password,
            'birthday'    => $birthday,
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

        // Ibinabalik yung NORMALIZED email para 'yun din ang gamitin ng
        // frontend sa susunod na verify/resend calls — dapat magkatugma
        // laging ang email na pinapadala pabalik-balik.
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

        DB::table('otp_verifications')->where('id', $record->id)->update(['is_used' => 1]);

        $fullName = trim($pending['first_name'] . ' ' . $pending['middle_name'] . ' ' . $pending['last_name']);

        DB::table('users')->insert([
            'first_name'    => $pending['first_name'],
            'middle_name'   => $pending['middle_name'],
            'last_name'     => $pending['last_name'],
            'name'          => $fullName,
            'email'         => $pending['email'], // naka-normalize na dahil galing sa register()
            'password_hash' => Hash::make($pending['password']),
            'birthday'      => $pending['birthday'],
            'role'          => 'borrower',
            'is_active'     => 1,
            'created_at'    => now(),
        ]);

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
}