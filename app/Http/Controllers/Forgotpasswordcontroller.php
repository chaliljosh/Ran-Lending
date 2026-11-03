<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Mail\OtpMail;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    // Ilang maling try bago i-lock ang OTP
    private const MAX_OTP_TRIES = 5;

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

    public function sendOtp(Request $request)
    {
        try {
            $data = $request->validate(['email' => 'required|email']);

            $email = $this->normalizeEmail($data['email']);

            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Email not found.']);
            }

            $otp = rand(100000, 999999);

            OtpVerification::create([
                'email'      => $user->email,
                'otp'        => Hash::make($otp),
                'type'       => 'forgot_password',
                'is_used'    => false,
                'expires_at' => now()->addMinutes(10),
            ]);

            // Bagong OTP = reset ang bilang ng maling try
            Cache::forget('fp_tries:' . $user->email);

            Mail::to($user->email)->send(new OtpMail($user->name, $otp));
            $request->session()->put('reset_email', $user->email);

            AuditLogger::log('auth.password_reset_requested', [
                'entity_type' => 'user',
                'entity_id'   => $user->user_id,
                'description' => 'Requested a password reset code',
                'actor'       => ['id' => $user->user_id, 'role' => $user->role, 'name' => $user->name],
            ]);

            return response()->json(['success' => true, 'message' => 'OTP sent to your email.']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function reset(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'verify_otp') {
            $data = $request->validate([
                'email' => 'required|email',
                'otp' => 'required|string',
            ]);

            $email = $this->normalizeEmail($data['email']);
            $triesKey = 'fp_tries:' . $email;

            // Sobra na ang maling try: kailangan mag-resend
            if ((int) Cache::get($triesKey, 0) >= self::MAX_OTP_TRIES) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many wrong attempts. Please request a new code.',
                ]);
            }

            $record = OtpVerification::where('email', $email)
                ->where('type', 'forgot_password')
                ->where('is_used', false)
                ->where('expires_at', '>=', now())
                ->latest('id')
                ->first();

            if (!$record || !Hash::check($data['otp'], $record->otp)) {
                Cache::put($triesKey, (int) Cache::get($triesKey, 0) + 1, now()->addMinutes(10));
                return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.']);
            }

            Cache::forget($triesKey);
            $record->update(['is_used' => true]);
            $request->session()->put('reset_email', $email);

            return response()->json(['success' => true, 'message' => 'OTP verified.']);
        }

        if ($action === 'reset_password') {
            $email = $request->session()->get('reset_email');
            if (!$email) {
                return response()->json(['success' => false, 'message' => 'Session expired. Please start over.']);
            }

            $request->merge(['password_confirmation' => $request->input('confirmPassword')]);
            $data = $request->validate(['password' => 'required|string|min:6|confirmed']);

            User::where('email', $email)->update([
                'password_hash' => Hash::make($data['password']),
            ]);

            $user = User::where('email', $email)->first();
            if ($user) {
                AuditLogger::log('auth.password_reset', [
                    'entity_type' => 'user',
                    'entity_id'   => $user->user_id,
                    'description' => 'Reset password using the email code',
                    'actor'       => ['id' => $user->user_id, 'role' => $user->role, 'name' => $user->name],
                ]);
            }

            $request->session()->forget('reset_email');

            return response()->json(['success' => true, 'message' => 'Password reset successful.']);
        }

        return response()->json(['success' => false, 'message' => 'Invalid request.']);
    }
}