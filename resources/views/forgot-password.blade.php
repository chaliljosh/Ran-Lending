<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Forgot Password - RAN Lending</title>

  <link rel="stylesheet" href="{{ asset('assets/login/css/styles.min.css') }}" />
  <link href="{{ asset('assets/landingpage/img/ranlogo1.jpg') }}" rel="icon">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/login/css/forgotpassword.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/login/css/reset-password-modal.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/admin/css/otp-modal.css') }}" />
</head>
<body data-login-url="{{ route('login') }}">

  <!-- Back to Home -->
  <div class="back-btn-bar">
    <a href="{{ url('/') }}" class="back-btn">
      <i class="bi bi-arrow-left"></i> Back to Home
    </a>
  </div>

  <div class="page-wrap">
    <div class="fp-card">

      <!-- Logo -->
      <div class="logo-wrap">
        <img src="{{ asset('assets/login/images/logos/ranlogo.png') }}" alt="RAN Logo">
      </div>

      <!-- Step 1: Email -->
      <div class="panel active" id="panel1">
        <h2>Forgot Password?</h2>
        <p class="desc">Enter the email address linked to your account and we'll send you a verification code.</p>
        <div id="error1" class="error-msg"></div>
        <div class="field">
          <label for="emailInput">Email Address</label>
          <div class="input-wrap">
            <i class="bi bi-envelope field-icon"></i>
            <input type="email" id="emailInput" placeholder="Enter your email">
          </div>
        </div>
        <button class="btn-submit" id="sendOtpBtn">
          <span class="btn-label">Send Verification Code <i class="bi bi-arrow-right"></i></span>
          <span class="btn-spinner"></span>
        </button>
        <a class="back-link" href="{{ route('login') }}">
          <i class="bi bi-arrow-left"></i> Back to Login
        </a>
      </div>

  
    </div>
  </div>

  <!-- Shared OTP Modal — public/css/otp-modal.css + public/functions/otp-modal.js -->
  <div class="otp-overlay" id="otpOverlay">
      <div class="otp-modal">
          <div class="otp-icon-circle">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                  <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
          </div>
          <h5 class="otp-title">Enter OTP Code</h5>
          <p class="otp-subtitle">We sent a 6-digit code to</p>
          <p class="otp-email" id="otpMaskedEmail"></p>

          <div class="otp-inputs" id="otpInputs">
              <input type="text" inputmode="numeric" maxlength="1">
              <input type="text" inputmode="numeric" maxlength="1">
              <input type="text" inputmode="numeric" maxlength="1">
              <input type="text" inputmode="numeric" maxlength="1">
              <input type="text" inputmode="numeric" maxlength="1">
              <input type="text" inputmode="numeric" maxlength="1">
          </div>

          <div class="otp-error" id="otpError"></div>

          <button type="button" class="otp-primary-btn" id="otpVerifyBtn">
              <span class="otp-btn-label">Verify Code</span>
              <span class="otp-spinner"></span>
          </button>

          <div class="otp-resend" id="otpResendWrap"></div>

          <button type="button" class="otp-back-link" id="otpBackBtn">Back</button>

      </div>
  </div>

  <!-- RESET PASSWORD MODAL — opens after OTP is verified -->
  <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rp-modal">

        <div class="modal-body">

          <div class="rp-header">
            <div class="rp-header-text">
              <h6 class="rp-title">Reset Password</h6>
            </div>
          </div>

  

          <div id="rpError" class="rp-alert rp-alert-danger d-none"></div>

          <div class="rp-field">
            <label for="rpNewPassword">New Password <span class="rp-required">*</span></label>
            <div class="rp-pw-wrap">
              <input type="password" id="rpNewPassword" class="rp-input" placeholder="Enter new password">
              <button type="button" class="rp-pw-toggle" data-target="rpNewPassword">
                <i class="bi bi-eye-slash"></i>
              </button>
            </div>
          </div>

          <div class="rp-field">
            <div class="rp-confirm-label-row">
              <label for="rpConfirmPassword" class="mb-0">Confirm New Password <span class="rp-required">*</span></label>
              <button type="button" class="rp-clear-btn" id="rpClearConfirmBtn">Clear</button>
            </div>
            <div class="rp-pw-wrap">
              <input type="password" id="rpConfirmPassword" class="rp-input" placeholder="Confirm new password">
              <button type="button" class="rp-pw-toggle" data-target="rpConfirmPassword">
                <i class="bi bi-eye-slash"></i>
              </button>
            </div>
          </div>

          <div class="rp-strength" id="rpStrength">
            <div class="rp-strength-bar">
              <span class="rp-strength-seg" data-seg="1"></span>
              <span class="rp-strength-seg" data-seg="2"></span>
              <span class="rp-strength-seg" data-seg="3"></span>
            </div>
            <p class="rp-strength-label" id="rpStrengthLabel">Password strength. Must contain;</p>
            <ul class="rp-checklist">
              <li id="rpReqUpper"><i class="bi bi-x-circle"></i> At least 1 uppercase</li>
              <li id="rpReqNumber"><i class="bi bi-x-circle"></i> At least 1 number</li>
              <li id="rpReqLength"><i class="bi bi-x-circle"></i> At least 8 characters</li>
            </ul>
          </div>

        </div>

        <div class="modal-footer rp-footer">
          <button type="button" class="btn rp-btn-discard" id="rpDiscardBtn">Cancel</button>
          <button type="button" class="btn rp-btn-submit" id="rpSubmitBtn">
            <span class="rp-btn-label">Reset Password</span>
            <span class="rp-btn-spinner"></span>
          </button>
        </div>

      </div>
    </div>
  </div>

  <script src="{{ asset('assets/login/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/login/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('functions/csrf.js') }}"></script>
  <script src="{{ asset('functions/otp-modal.js') }}"></script>
  <script src="{{ asset('functions/forgot-password.js') }}"></script>
</body>
</html>