<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Login - RAN Lending</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="shortcut icon" type="image/png" href="{{ asset('assets/login/images/logos/seodashlogo.png') }}" />
  <link rel="stylesheet" href="{{ asset('assets/login/css/styles.min.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/login/css/login.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/otp-modal.css') }}" />
</head>
<body>
  <div class="page-wrapper d-flex flex-column min-vh-100" id="main-wrapper">

    <!-- Back to Home -->
    <a href="/" class="back-home-btn">
      <i class="bi bi-arrow-left"></i> Back to Home
    </a>

    <!-- Main content -->
    <div class="radial-gradient flex-grow-1 d-flex align-items-center justify-content-center">
      <div class="w-100">
        <div class="row justify-content-center w-100 m-0">
          <div class="col-md-8 col-lg-6 col-xxl-3">

            <div class="card mb-0 login-card">
              <div class="card-body p-4">
                <a href="{{ url('/') }}" class="text-nowrap logo-img text-center d-block py-2 w-100">
                  <img src="{{ asset('assets/login/images/logos/ranlogo.png') }}" class="img-fluid" style="max-width: 130px;" alt="RAN Logo">
                </a>
                <p class="text-center tagline">Your Best Lending Website</p>

                <div id="registeredAlert" class="alert alert-success d-none align-items-center gap-2" role="alert">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Account created successfully! Please sign in.</span>
                </div>

                <div id="errorAlert" class="alert alert-danger d-none align-items-center gap-2" role="alert">
                  <i class="bi bi-exclamation-circle-fill"></i>
                  <span id="errorMessage"></span>
                </div>

                <form id="loginForm">
                  <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                          placeholder="Enter your email" required>
                  </div>

                  <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="position-relative">
                      <input type="password" class="form-control" id="password" name="password"
                            placeholder="Enter your password" required style="padding-right: 45px;">
                      <button type="button" onclick="togglePw('password', this)" class="pw-toggle-btn">
                        <i class="bi bi-eye-slash"></i>
                      </button>
                    </div>
                  </div>

                  <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="rememberMe">
                      <label class="form-check-label text-dark" for="rememberMe">Remember me</label>
                    </div>
                    <a class="text-primary fw-bold" href="{{ route('forgot-password') }}">Forgot Password?</a>
                  </div>

                  <button type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-3" id="loginBtn">
                    <span id="loginBtnText">Sign In</span>
                    <span id="loginSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                  </button>

                  <div class="d-flex align-items-center justify-content-center">
                    <p class="fs-4 mb-0 fw-bold">New to RAN?</p>
                    <a class="text-primary fw-bold ms-2" href="{{ route('register') }}">Create an account</a>
                  </div>
                </form>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Shared OTP Modal — public/css/otp-modal.css + public/functions/otp-modal.js -->
 <!-- Shared OTP Modal — public/css/otp-modal.css + public/functions/otp-modal.js -->
<div class="otp-overlay" id="otpOverlay">
    <div class="otp-modal">
        <h5 class="otp-title">Enter Verification Code</h5>
        <p class="otp-subtitle">Please enter the verification code we just sent to</p>
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
            <span class="otp-btn-label">Confirm</span>
            <span class="otp-spinner"></span>
        </button>

        <div class="otp-resend" id="otpResendWrap"></div>

        <button type="button" class="otp-back-link" id="otpBackBtn">Back to Login</button>
    </div>
</div>

  <script src="{{ asset('assets/login/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/login/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <script>
     const params = new URLSearchParams(window.location.search);
    if (params.get('registered') === '1') {
      document.getElementById('registeredAlert').classList.remove('d-none');
      document.getElementById('registeredAlert').classList.add('d-flex');
    }
  </script>
  <script src="{{ asset('functions/otp-modal.js') }}"></script>
  <script src="{{ asset('functions/login.js') }}"></script>
</body>
</html>