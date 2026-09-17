<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    /**
     * I-normalize ang email bago ito gamitin sa anumang lookup, OTP
     * record, o session storage. Tinatanggal ang "+alias" na bahagi ng
     * local part, at para sa Gmail/Googlemail, tinatanggal din ang mga
     * tuldok — parehong loophole na pwedeng gamitin para mag-request ng
     * paulit-ulit na password reset OTP gamit ang "magkaibang" email
     * pero parehong totoong inbox. Kopya ito ng normalizeEmail() sa
     * AuthController — pareho dapat lagi ang dalawa.
     */
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

        // I-store yung HASHED na version ng OTP — yung raw $otp ay
        // ipinapadala lang sa email, hindi na ito naka-store kahit saan.
        OtpVerification::create([
            'email'      => $user->email,
            'otp'        => Hash::make($otp),
            'type'       => 'forgot_password',
            'is_used'    => false,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new OtpMail($user->name, $otp));
        $request->session()->put('reset_email', $user->email);

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

            // Kunin muna yung record base sa email/type/expiry/is_used lang —
            // hindi na natin pwedeng gamitin ang WHERE otp = $data['otp']
            // dahil hashed na siya sa DB. I-compare gamit Hash::check().
            $record = OtpVerification::where('email', $email)
                ->where('type', 'forgot_password')
                ->where('is_used', false)
                ->where('expires_at', '>=', now())
                ->latest('id')
                ->first();

            if (!$record || !Hash::check($data['otp'], $record->otp)) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.']);
            }

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

            $request->session()->forget('reset_email');

            return response()->json(['success' => true, 'message' => 'Password reset successful.']);
        }

        return response()->json(['success' => false, 'message' => 'Invalid request.']);
    }
}