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
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/login/css/styles.min.css') }}" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="{{ asset('assets/login/css/register.css') }}?v={{ filemtime(public_path('assets/login/css/register.css')) }}" />
  <link rel="stylesheet" href="{{ asset('css/otp-modal.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/login/css/register-terms-modal.css') }}?v={{ filemtime(public_path('assets/login/css/register-terms-modal.css')) }}" />
</head>
<body>

  <a href="{{ route('login') }}" class="back-btn">
    <i class="bi bi-arrow-left"></i> Back to Home
  </a>

  <div class="page-wrap">
    <div class="register-card">

      <div class="card-right">
        <div class="form-header">
          <h2>Create Account</h2>
          <p>Fill in your details to get started</p>
        </div>

        <div class="reg-progress" id="regProgress">
          <div class="reg-progress-step">
            <div class="reg-progress-circle">1</div>
            <div class="reg-progress-label">Account</div>
          </div>
          <div class="reg-progress-connector"></div>
          <div class="reg-progress-step">
            <div class="reg-progress-circle">2</div>
            <div class="reg-progress-label">Personal Info</div>
          </div>
          <div class="reg-progress-connector"></div>
          <div class="reg-progress-step">
            <div class="reg-progress-circle">3</div>
            <div class="reg-progress-label">Address</div>
          </div>
        </div>

        <div id="registerAlert" class="alert-banner">
          <i class="bi bi-exclamation-circle-fill alert-icon"></i>
          <span id="registerAlertText"></span>
        </div>

        <div id="regForm">

          <div class="form-grid reg-step">

            <div class="field col-span-2">
              <div class="name-row name-row-4">
                <div>
                  <label for="firstName">First Name<span class="required-asterisk">*</span></label>
                  <div class="input-wrap">
                    <i class="bi bi-person field-icon"></i>
                    <input type="text" id="firstName" placeholder="Enter first name"
                      oninput="this.value = this.value.replace(/\b\w/g, c => c.toUpperCase())">
                  </div>
                </div>
                <div>
                  {{-- Full middle name ang tina-type ng user. Initial lang ang ipinapadala sa system (tingnan ang register.js). --}}
                  <label for="middleName">Middle Name</label>
                  <div class="input-wrap">
                    <i class="bi bi-person-badge field-icon"></i>
                    <input type="text" id="middleName" placeholder="Enter middle name" maxlength="50" autocomplete="off"
                      oninput="this.value = this.value.replace(/[^A-Za-z\u00C0-\u024F\s'.-]/g, '').replace(/\b\w/g, c => c.toUpperCase())">
                  </div>
                </div>
                <div>
                  <label for="lastName">Last Name<span class="required-asterisk">*</span></label>
                  <div class="input-wrap">
                    <i class="bi bi-person field-icon"></i>
                    <input type="text" id="lastName" placeholder="Enter last name"
                      oninput="this.value = this.value.replace(/\b\w/g, c => c.toUpperCase())">
                  </div>
                </div>
                <div>
                  <label for="suffix">Suffix</label>
                  <div class="input-wrap no-icon">
                    <select id="suffix">
                      <option value="">None</option>
                      <option value="Jr.">Jr.</option>
                      <option value="Sr.">Sr.</option>
                      <option value="II">II</option>
                      <option value="III">III</option>
                      <option value="IV">IV</option>
                      <option value="V">V</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="field col-span-2">
              <label for="email">Email Address<span class="required-asterisk">*</span></label>
              <div class="input-wrap">
                <i class="bi bi-envelope field-icon"></i>
                <input type="email" id="email" placeholder="Enter email address">
              </div>
            </div>

            <div class="field col-span-2">
              <label>Birthday<span class="required-asterisk">*</span></label>
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
                  </select>
                </div>
                <div class="input-wrap no-icon">
                  <select id="birthYear">
                    <option value="">Year</option>
                  </select>
                </div>
              </div>
              <p class="field-hint">You must be 18 years old or above to register.</p>
            </div>

            <div class="field">
              <label for="password">Password<span class="required-asterisk">*</span></label>
              <div class="input-wrap pw-wrap">
                <i class="bi bi-lock field-icon"></i>
                <input type="password" id="password" placeholder="Create a password">
                <button class="toggle-pw" type="button" onclick="togglePw('password', this)">
                  <i class="bi bi-eye-slash"></i>
                </button>
              </div>
            </div>

            <div class="field">
              <label for="confirmPassword">Confirm Password<span class="required-asterisk">*</span></label>
              <div class="input-wrap pw-wrap">
                <i class="bi bi-lock-fill field-icon"></i>
                <input type="password" id="confirmPassword" placeholder="Re-enter password">
                <button class="toggle-pw" type="button" onclick="togglePw('confirmPassword', this)">
                  <i class="bi bi-eye-slash"></i>
                </button>
              </div>
            </div>

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

          </div>

          <div class="form-grid reg-step">

            <div class="field col-span-2">
              <div class="name-row">
                <div>
                  <label for="age">Age</label>
                  <div class="input-wrap no-icon">
                    <input type="text" id="age" placeholder="Auto-calculated" readonly>
                  </div>
                </div>
                <div>
                  <label for="gender">Gender<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <select id="gender">
                      <option value="">Select gender</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                    </select>
                  </div>
                </div>
                <div>
                  <label for="civilStatus">Civil Status<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <select id="civilStatus">
                      <option value="">Select status</option>
                      <option value="single">Single</option>
                      <option value="married">Married</option>
                      <option value="widowed">Widowed</option>
                      <option value="separated">Separated</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="field col-span-2">
              <div class="name-row name-row-2">
                <div>
                  <label for="citizenship">Citizenship<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <select id="citizenship">
                      <option value="">Select citizenship</option>
                    </select>
                  </div>
                </div>
                <div>
                  <label for="cellphone">Cellphone Number<span class="required-asterisk">*</span></label>
                  <div class="input-wrap">
                    <i class="bi bi-telephone field-icon"></i>
                    <input type="tel" id="cellphone" placeholder="+63 912 345 6789" maxlength="16">
                  </div>
                </div>
              </div>
            </div>

            <div class="field col-span-2">
              <div class="name-row">
                <div>
                  <label for="birthCountry">Birth Country<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <select id="birthCountry">
                      <option value="">Select Country</option>
                    </select>
                  </div>
                </div>
                <div>
                  <label for="birthProvince">Birth Province<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <select id="birthProvince" disabled>
                      <option value="">Select Province</option>
                    </select>
                    <input type="text" id="birthProvinceText" class="is-off" placeholder="Enter province / state"
                      oninput="this.value = this.value.replace(/\b\w/g, c => c.toUpperCase())">
                  </div>
                </div>
                <div>
                  <label for="birthCity">Birth City<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <select id="birthCity" disabled>
                      <option value="">Select City/Municipality</option>
                    </select>
                    <input type="text" id="birthCityText" class="is-off" placeholder="Enter city"
                      oninput="this.value = this.value.replace(/\b\w/g, c => c.toUpperCase())">
                  </div>
                </div>
              </div>
            </div>

          </div>

          <div class="form-grid reg-step">

            <div class="col-span-2 step-head">
              <h3>Current Address</h3>
              <p>Please provide your current residential address.</p>
            </div>

            <div class="field col-span-2">
              <div class="name-row">
                <div>
                  <label for="country">Country<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <input type="text" id="country" value="Philippines" readonly>
                  </div>
                </div>
                <div>
                  <label for="region">Region<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <select id="region">
                      <option value="">Select Region</option>
                    </select>
                  </div>
                </div>
                <div>
                  <label for="province">Province<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <select id="province" disabled>
                      <option value="">Select Province</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="field col-span-2">
              <div class="name-row">
                <div>
                  <label for="city">City/Municipality<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <select id="city" disabled>
                      <option value="">Select City/Municipality</option>
                    </select>
                  </div>
                </div>
                <div>
                  <label for="barangay">Barangay<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <select id="barangay" disabled>
                      <option value="">Select Barangay</option>
                    </select>
                  </div>
                </div>
                <div>
                  <label for="zipcode">Zipcode <span class="optional-tag">(Optional)</span></label>
                  <div class="input-wrap no-icon">
                    <input type="text" id="zipcode" placeholder="e.g. 2000" maxlength="4" inputmode="numeric">
                  </div>
                </div>
              </div>
            </div>

            <div class="field col-span-2">
              <label for="homeAddress">House No., Street, Subdivision / Sitio<span class="required-asterisk">*</span></label>
              <div class="input-wrap no-icon">
                <input type="text" id="homeAddress" maxlength="200" placeholder="e.g. Block 5 Lot 10, Rizal Street, Greenfield Subdivision">
              </div>
            </div>

            <div class="col-span-2 step-head">
              <h3>Permanent Address</h3>
              <p>Check the box below if your permanent address is the same as your current address.</p>
            </div>

            <div class="col-span-2">
              <label class="consent-check">
               <input type="checkbox" id="sameAsCurrent">
                <span>My permanent address is the same as my current address</span>
              </label>
            </div>

          <div class="col-span-2" id="permFields">
              <div class="form-grid">

                <div class="field col-span-2">
                  <div class="name-row">
                    <div>
                      <label for="permRegion">Region<span class="required-asterisk">*</span></label>
                      <div class="input-wrap no-icon">
                        <select id="permRegion">
                          <option value="">Select Region</option>
                        </select>
                      </div>
                    </div>
                    <div>
                      <label for="permProvince">Province<span class="required-asterisk">*</span></label>
                      <div class="input-wrap no-icon">
                        <select id="permProvince" disabled>
                          <option value="">Select Province</option>
                        </select>
                      </div>
                    </div>
                    <div>
                      <label for="permCity">City/Municipality<span class="required-asterisk">*</span></label>
                      <div class="input-wrap no-icon">
                        <select id="permCity" disabled>
                          <option value="">Select City/Municipality</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="field col-span-2">
                  <div class="name-row name-row-2">
                    <div>
                      <label for="permBarangay">Barangay<span class="required-asterisk">*</span></label>
                      <div class="input-wrap no-icon">
                        <select id="permBarangay" disabled>
                          <option value="">Select Barangay</option>
                        </select>
                      </div>
                    </div>
                    <div>
                      <label for="permZipcode">Zipcode <span class="optional-tag">(Optional)</span></label>
                      <div class="input-wrap no-icon">
                        <input type="text" id="permZipcode" placeholder="e.g. 2000" maxlength="4" inputmode="numeric">
                      </div>
                    </div>
                  </div>
                </div>

                <div class="field col-span-2">
                  <label for="permHomeAddress">House No., Street, Subdivision / Sitio<span class="required-asterisk">*</span></label>
                  <div class="input-wrap no-icon">
                    <input type="text" id="permHomeAddress" maxlength="200" placeholder="e.g. Block 5 Lot 10, Rizal Street, Greenfield Subdivision">
                  </div>
                </div>

              </div>
            </div>

          </div>

          <div class="reg-nav">
            <button type="button" class="btn-back" id="regBtnBack">Back</button>
            <button type="button" class="btn-next" id="regBtnNext">
              <span class="btn-label">Next</span>
              <span class="btn-spinner"></span>
            </button>
            <button type="button" class="btn-signup" id="signupBtn">
              <span class="btn-label">Create Account</span>
              <span class="btn-spinner"></span>
            </button>
          </div>

        </div>

        {{-- Terms & Privacy note: nakikita sa step 1, 2, at 3 --}}
        <p class="legal-note">
          By creating an account, you agree to our
          <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Terms of Service</a>
          and
          <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.
        </p>

        <p class="footer-text">
          Already have an account? <a href="{{ route('login') }}">Sign In</a>
        </p>

      </div>
    </div>
  </div>

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

  {{-- Terms & Privacy modal: lalabas pagpindot ng "Create Account" (pagkatapos pumasa ang validation) --}}
  <div class="tp-overlay" id="termsOverlay" aria-hidden="true">
    <div class="tp-modal" role="dialog" aria-modal="true" aria-labelledby="tpTitle">

      <div class="tp-head">
        <h5 id="tpTitle">Terms &amp; Privacy</h5>
        <p>Please read and accept before creating your account.</p>
      </div>

      <div class="tp-tabs" role="tablist">
        <button type="button" class="tp-tab is-active" data-tab="terms" role="tab">Terms of Service</button>
        <button type="button" class="tp-tab" data-tab="privacy" role="tab">Privacy Policy</button>
      </div>

      <div class="tp-body" id="tpBody">

        <div class="tp-panel is-active" data-panel="terms" role="tabpanel">


          <div class="tp-section">
            <span class="tp-num">01</span>
            <div>
              <h6>Eligibility &amp; Registration</h6>
              <p>You must be at least 18 years old and complete the registration form with accurate personal and address information. Your account is activated once you verify your email with the one-time code (OTP) we send you. After that, you can sign in and apply for a loan right away. You are responsible for keeping your information accurate and up to date.</p>
            </div>
          </div>

          <div class="tp-section">
            <span class="tp-num">02</span>
            <div>
              <h6>Loan Terms</h6>
              <p>Approved loans carry a fixed interest rate of 5% per month, applied over your chosen loan term. First-time borrowers can borrow up to ₱15,000, with a maximum loan term of 6 months. The total amount payable, including interest, is disclosed to you before you confirm your application. Monthly payments are due on the agreed schedule shown on your Loan Details page.</p>
            </div>
          </div>

          <div class="tp-section">
            <span class="tp-num">03</span>
            <div>
              <h6>Late Payments &amp; Penalties</h6>
              <p>Payments not received by their due date may incur penalty charges. Repeated or prolonged late payments may affect your eligibility for future loans.</p>
            </div>
          </div>

          <div class="tp-section">
            <span class="tp-num">04</span>
            <div>
              <h6>Changes to These Terms</h6>
              <p>These terms may be updated from time to time. Continued use of Ran Serenity after changes are posted constitutes acceptance of the updated terms.</p>
            </div>
          </div>
        </div>

        <div class="tp-panel" data-panel="privacy" role="tabpanel">
      

          <div class="tp-section">
            <span class="tp-num">01</span>
            <div>
              <h6>Information We Collect</h6>
              <p>We collect the personal information you provide when you register and when you apply for a loan, including your name, birthday, gender, civil status, citizenship, place of birth, contact number, email address, current and permanent address, valid ID and a selfie with your ID, employment details, monthly income, and the payment receipts you upload.</p>
            </div>
          </div>

          <div class="tp-section">
            <span class="tp-num">02</span>
            <div>
              <h6>How We Use Your Information</h6>
              <p>Your information is used to create and secure your account, send you one-time verification codes and account notifications by email, verify your identity, process and manage your loans, and record your payments. When you apply for a loan, your application is reviewed by authorized admin staff.</p>
            </div>
          </div>

          <div class="tp-section">
            <span class="tp-num">03</span>
            <div>
              <h6>How We Protect Your Information</h6>
              <p>Your data is stored securely and access is limited to authorized personnel involved in reviewing and managing borrower accounts and loan applications.</p>
            </div>
          </div>

          <div class="tp-section">
            <span class="tp-num">04</span>
            <div>
              <h6>Data Sharing</h6>
              <p>We do not sell or share your personal information with third parties outside of what is necessary to operate and administer your loan account.</p>
            </div>
          </div>

          <div class="tp-section">
            <span class="tp-num">05</span>
            <div>
              <h6>Questions</h6>
              <p>If you have questions about how your information is handled, please reach out through the Help &amp; FAQ page.</p>
            </div>
          </div>
        </div>

      </div>

      <div class="tp-foot">
        <label class="tp-check">
          <input type="checkbox" id="termsAgree">
          <span>I agree to the <strong>Terms of Service</strong> and <strong>Privacy Policy</strong></span>
        </label>

        <div class="tp-actions">
          <button type="button" class="tp-btn tp-btn--ghost" id="termsCancel">Cancel</button>
          <button type="button" class="tp-btn tp-btn--primary" id="termsAgreeBtn" disabled>I Agree &amp; Create Account</button>
        </div>
      </div>

    </div>
  </div>

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
  <script src="{{ asset('functions/register.js') }}?v={{ filemtime(public_path('functions/register.js')) }}"></script>
</body>
</html>