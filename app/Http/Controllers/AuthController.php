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
   public function login(Request $request)
{
    $email    = trim($request->input('email'));
    $password = $request->input('password');

    if (!$email || !$password) {
        return response()->json(['success' => false, 'message' => 'Email and password are required.']);
    }

    $key = 'login:' . $request->ip();
    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        return response()->json([
            'success' => false,
            'message' => "Too many login attempts. Try again in {$seconds} seconds."
        ]);
    }

    $user = DB::table('users')->where('email', $email)->where('is_active', 1)->first();

    if (!$user || !Hash::check($password, $user->password_hash)) {
        RateLimiter::hit($key, 600);
        return response()->json(['success' => false, 'message' => 'Invalid email or password.']);
    }

    RateLimiter::clear($key);

    if ($user->role === 'admin') {
        Session::put('user_id',    $user->user_id);
        Session::put('user_name',  $user->name);
        Session::put('user_email', $user->email);
        Session::put('user_role',  $user->role);
        Session::put('user_profile_picture', $user->profile_picture ?? null); // ADDED

        DB::table('users')->where('user_id', $user->user_id)->update([
            'last_login_at' => now(),
        ]);

        return response()->json([
            'success'      => true,
            'otp_required' => false,
            'redirect'     => route('admin.dashboard'),
        ]);
    }

    // OTP flow for borrowers
    $otp       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = now()->addMinutes(10);

    DB::table('otp_verifications')->where('email', $email)->where('type', 'login')->delete();
    DB::table('otp_verifications')->insert([
        'email'      => $email,
        'otp'        => $otp,
        'type'       => 'login',
        'expires_at' => $expiresAt,
    ]);

    Session::put('pending_login', [
        'email'      => $user->email,
        'user_id'    => $user->user_id,
        'user_name'  => $user->name,
        'user_email' => $user->email,
        'user_role'  => $user->role,
        'user_profile_picture' => $user->profile_picture ?? null, // ADDED
    ]);

    Mail::to($email)->send(new \App\Mail\OtpMail($user->name, $otp));

    return response()->json([
        'success'      => true,
        'otp_required' => true,
        'masked_email' => $this->maskEmail($email),
    ]);
}

private function maskEmail(string $email): string
{
    [$name, $domain] = explode('@', $email);
    $visible = min(2, strlen($name));
    $masked  = substr($name, 0, $visible) . str_repeat('*', max(strlen($name) - $visible, 3));
    return $masked . '@' . $domain;
}

    public function verifyOtp(Request $request)
    {
        $email = trim($request->input('email'));
        $otp   = trim($request->input('otp'));

        if (!$email || !$otp) {
            return response()->json(['success' => false, 'message' => 'OTP is required.']);
        }

        $pending = Session::get('pending_login');
        if (!$pending || $pending['email'] !== $email) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please log in again.']);
        }

        $record = DB::table('otp_verifications')
            ->where('email', $email)
            ->where('otp', $otp)
            ->where('type', 'login')
            ->where('expires_at', '>', now())
            ->where('is_used', 0)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.']);
        }

        DB::table('otp_verifications')->where('id', $record->id)->update(['is_used' => 1]);

        Session::put('user_id',    $pending['user_id']);
        Session::put('user_name',  $pending['user_name']);
        Session::put('user_email', $pending['user_email']);
        Session::put('user_role',  $pending['user_role']);
        Session::put('user_profile_picture', $pending['user_profile_picture'] ?? null); // ADDED
        Session::forget('pending_login');

        return response()->json([
            'success'  => true,
            'redirect' => route('borrower.dashboard'),
        ]);
    }

    public function resendOtp(Request $request)
    {
        $email   = trim($request->input('email'));
        $pending = Session::get('pending_login');

        if (!$pending || $pending['email'] !== $email) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please log in again.']);
        }

        $otp       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        DB::table('otp_verifications')->where('email', $email)->where('type', 'login')->delete();
        DB::table('otp_verifications')->insert([
            'email'      => $email,
            'otp'        => $otp,
            'type'       => 'login',
            'expires_at' => $expiresAt,
        ]);

        Mail::to($email)->send(new \App\Mail\OtpMail($pending['user_name'], $otp));

        return response()->json(['success' => true]);
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
        $email           = trim($request->input('email'));
        $password        = $request->input('password');
        $confirmPassword = $request->input('confirmPassword');
        $birthMonth      = $request->input('birthMonth');
        $birthDay        = $request->input('birthDay');
        $birthYear       = $request->input('birthYear');

        if (!$lastName || !$firstName || !$email || !$password || !$confirmPassword) {
            return response()->json(['success' => false, 'message' => 'Please fill in all required fields.']);
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
            'otp'        => $otp,
            'type'       => 'register',
            'expires_at' => $expiresAt,
        ]);

        Mail::to($email)->send(new \App\Mail\OtpMail($firstName, $otp));

        return response()->json(['success' => true, 'email' => $email]);
    }

    public function verifyRegisterOtp(Request $request)
    {
        $email = trim($request->input('email'));
        $otp   = trim($request->input('otp'));

        if (!$email || !$otp) {
            return response()->json(['success' => false, 'message' => 'OTP is required.']);
        }

        $pending = Session::get('pending_register');
        if (!$pending || $pending['email'] !== $email) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please register again.']);
        }

        $record = DB::table('otp_verifications')
            ->where('email', $email)
            ->where('otp', $otp)
            ->where('type', 'register')
            ->where('expires_at', '>', now())
            ->where('is_used', 0)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.']);
        }

        DB::table('otp_verifications')->where('id', $record->id)->update(['is_used' => 1]);

        $fullName = trim($pending['first_name'] . ' ' . $pending['middle_name'] . ' ' . $pending['last_name']);

        DB::table('users')->insert([
            'first_name'    => $pending['first_name'],
            'middle_name'   => $pending['middle_name'],
            'last_name'     => $pending['last_name'],
            'name'          => $fullName,
            'email'         => $pending['email'],
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
        $email   = trim($request->input('email'));
        $pending = Session::get('pending_register');

        if (!$pending || $pending['email'] !== $email) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please register again.']);
        }

        $otp       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        DB::table('otp_verifications')->where('email', $email)->where('type', 'register')->delete();
        DB::table('otp_verifications')->insert([
            'email'      => $email,
            'otp'        => $otp,
            'type'       => 'register',
            'expires_at' => $expiresAt,
        ]);

        Mail::to($email)->send(new \App\Mail\OtpMail($pending['first_name'], $otp));

        return response()->json(['success' => true]);
    }
}