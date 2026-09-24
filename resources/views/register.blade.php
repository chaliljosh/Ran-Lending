<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ran Lending - Registration</title>
<link href="{{ asset('assets/landingpage/img/ranlogo1.jpg') }}" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/login/css/styles.min.css') }}" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/login/css/register.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/otp-modal.css') }}" />
</head>
<style>
  body {
    font-family: 'Inter', sans-serif;
  }
</style>
<body>

  <!-- Back to Home -->
  <a href="{{ route('login') }}" class="back-btn">
    <i class="bi bi-arrow-left"></i> Back to Home
  </a>

  <div class="page-wrap">
    <div class="register-card">

      <!-- RIGHT PANEL -->
      <div class="card-right">
        <div class="form-header">
          <h2>Create Account</h2>
          <p>Fill in your details to get started</p>
        </div>

        <!-- Error Alert -->
        <div id="registerAlert" style="display:none; background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:0.875rem;"></div>

        <div class="form-grid">

                    <div class="field">
            <label for="firstName">First Name</label>
            <div class="input-wrap">
              <i class="bi bi-person field-icon"></i>
              <!-- ✅ Auto capitalize first letter of each word -->
              <input type="text" id="firstName" placeholder="Enter first name"
                oninput="this.value = this.value.replace(/\b\w/g, c => c.toUpperCase())">
            </div>
          </div>

          <div class="field">
            <label for="middleName">Middle Initial</label>
            <div class="input-wrap">
              <i class="bi bi-person-badge field-icon"></i>
              <!-- ✅ Isang letra lang, auto-uppercase -->
              <input type="text" id="middleName" placeholder="M" maxlength="1"
                oninput="this.value = this.value.replace(/[^a-zA-Z]/g, '').toUpperCase()">
            </div>
          </div>

          <div class="field">
            <label for="lastName">Last Name</label>
            <div class="input-wrap">
              <i class="bi bi-person field-icon"></i>
              <!-- ✅ Auto capitalize first letter of each word -->
              <input type="text" id="lastName" placeholder="Enter last name"
                oninput="this.value = this.value.replace(/\b\w/g, c => c.toUpperCase())">
            </div>
          </div>
          <div class="field">
            <label for="email">Email Address</label>
            <div class="input-wrap">
              <i class="bi bi-envelope field-icon"></i>
              <input type="email" id="email" placeholder="Enter email address">
            </div>
          </div>

          <!-- ============================================================
               BIRTHDAY — 3 dropdowns (Month / Day / Year). Ginawa itong
               permanente (hindi na native <input type="date">) dahil
               hindi consistent ang year-navigation behavior ng native
               date picker sa iba't ibang browsers/OS — sa iba mabilis
               ang year-grid, sa iba naman listahan pa rin na kailangang
               i-scroll paitaas. Ang mga <select> dropdown ay:
               (a) consistent ang behavior sa lahat ng browsers, at
               (b) pwedeng mag-type ng taon (hal. "1980") habang bukas
                   ang dropdown para direktang mag-jump doon — walang
                   scrolling na kailangan.
          ============================================================ -->
          <div class="field col-span-2">
            <label>Birthday</label>
            <div class="birthday-row">
              <div class="input-wrap no-icon">
                <select id="birthMonth">
                  <option value="">Month</option>
                  <option value="1">January</option>
                  <option value="2">February</option>
                  <option value="3">March</option>
                  <option value="4">April</option>
                  <option value="5">May</option>
                  <option value="6">June</option>
                  <option value="7">July</option>
                  <option value="8">August</option>
                  <option value="9">September</option>
                  <option value="10">October</option>
                  <option value="11">November</option>
                  <option value="12">December</option>
                </select>
              </div>
              <div class="input-wrap no-icon">
                <select id="birthDay">
                  <option value="">Day</option>
                  {{-- Ang laman/bilang ng options dito ay dynamic --}}
                  {{-- ia-adjust ng JS base sa napiling Month/Year   --}}
                  {{-- (para tama ang Feb 28/29, 30 vs 31 araw).    --}}
                </select>
              </div>
              <div class="input-wrap no-icon">
                <select id="birthYear">
                  <option value="">Year</option>
                  {{-- Pinopopulate ng JS: currentYear pababa      --}}
                  {{-- hanggang currentYear-100.                   --}}
                </select>
              </div>
            </div>
          </div>

          <div class="field col-span-2">
            <label for="password">Password</label>
            <div class="input-wrap pw-wrap">
              <i class="bi bi-lock field-icon"></i>
              <input type="password" id="password" placeholder="Create a password">
              <button class="toggle-pw" type="button" onclick="togglePw('password', this)">
                <i class="bi bi-eye-slash"></i>
              </button>
            </div>
          </div>

          <div class="field col-span-2">
            <label for="confirmPassword">Confirm Password</label>
            <div class="input-wrap pw-wrap">
              <i class="bi bi-lock-fill field-icon"></i>
              <input type="password" id="confirmPassword" placeholder="Re-enter password">
              <button class="toggle-pw" type="button" onclick="togglePw('confirmPassword', this)">
                <i class="bi bi-eye-slash"></i>
              </button>
            </div>
          </div>

          <!-- ============================================================
               PASSWORD STRENGTH — nailipat papunta dito, sa IBABA ng
               Confirm Password field (dati nasa gitna, kaagad pagkatapos
               ng Password field lang, bago pa man ang Confirm Password).
          ============================================================ -->
          <div class="col-span-2">
            <div class="pw-strength" id="pwStrength">
              <div class="pw-strength-bar">
                <span class="pw-strength-seg" data-seg="1"></span>
                <span class="pw-strength-seg" data-seg="2"></span>
                <span class="pw-strength-seg" data-seg="3"></span>
              </div>
              <p class="pw-strength-label" id="pwStrengthLabel">Password strength. Must contain;</p>
              <ul class="pw-checklist">
                <li id="pwReqUpper"><i class="bi bi-x-circle"></i> At least 1 uppercase</li>
                <li id="pwReqNumber"><i class="bi bi-x-circle"></i> At least 1 number</li>
                <li id="pwReqLength"><i class="bi bi-x-circle"></i> At least 8 characters</li>
              </ul>
            </div>
          </div>

          <!-- ============================================================
               CONSENT CHECKBOX — kailangan bago maka-submit. Ang
               "agreeTerms" checkbox ay chine-check ng register.js bago
               i-enable ang Create Account button / bago i-submit ang
               form (see register.js).
          ============================================================ -->
          <div class="col-span-2">
            <label class="consent-check">
              <input type="checkbox" id="agreeTerms">
              <span>
                I agree to the
                <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Terms of Service</a>
                and
                <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>
              </span>
            </label>
          </div>

          <div class="col-span-2">
            <button class="btn-signup" id="signupBtn">
              Create Account
            </button>
          </div>

        </div>

        <p class="footer-text">
          Already have an account? <a href="{{ route('login') }}">Sign In</a>
        </p>

      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div class="modal-overlay" id="successModal">
    <div class="modal-box">
      <div class="modal-icon" style="background:#dcfce7;">
        <i class="bi bi-check-circle-fill" style="font-size:36px; color:#16a34a;"></i>
      </div>
      <h5>Account Created!</h5>
      <p>Your email has been verified and your account has been successfully created. You can now sign in.</p>
      <button class="modal-btn" onclick="window.location.href='{{ route('login') }}'">Go to Login</button>
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
          <h5 class="otp-title">Verify Your Email</h5>
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
              <span class="otp-btn-label">Verify</span>
              <span class="otp-spinner"></span>
          </button>

          <div class="otp-resend" id="otpResendWrap"></div>

          <button type="button" class="otp-back-link" id="otpBackBtn">Back</button>

          
      </div>
  </div>

  <script src="{{ asset('assets/login/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/login/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('functions/csrf.js') }}"></script>
  <script src="{{ asset('functions/otp-modal.js') }}"></script>
  <script src="{{ asset('functions/register.js') }}"></script>
</body>
</html>