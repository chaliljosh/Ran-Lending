/* ── Toggle password visibility ── */
function togglePw(id, btn) {
    const inp  = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    } else {
        inp.type = 'password';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    }
}

document.addEventListener('DOMContentLoaded', function () {

    const loginForm     = document.getElementById('loginForm');
    const emailInput    = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const rememberMe    = document.getElementById('rememberMe');
    const errorAlert    = document.getElementById('errorAlert');
    const errorMessage  = document.getElementById('errorMessage');
    const loginBtn      = document.getElementById('loginBtn');
    const loginBtnText  = document.getElementById('loginBtnText');
    const loginSpinner  = document.getElementById('loginSpinner');

    const savedEmail = localStorage.getItem('rememberedEmail');
    if (savedEmail) {
        emailInput.value   = savedEmail;
        rememberMe.checked = true;
    }

    function showError(msg) {
        errorMessage.textContent = msg;
        errorAlert.classList.remove('d-none');
        setTimeout(() => errorAlert.classList.add('d-none'), 5000);
    }
    function hideError() { errorAlert.classList.add('d-none'); }

    function showLoading() {
        loginBtn.disabled        = true;
        loginBtnText.textContent = 'Signing In...';
        loginSpinner.classList.remove('d-none');
    }
    function hideLoading() {
        loginBtn.disabled        = false;
        loginBtnText.textContent = 'Sign In';
        loginSpinner.classList.add('d-none');
    }

    // OTP Modal lang natitira dito (para sa 2-step login verification).
    // Ang Forgot Password ay lumipat na sa sarili niyang page (forgot-password.html).
    document.body.insertAdjacentHTML('beforeend', `
        <!-- OTP Modal -->
        <div id="otpOverlay" style="display:none; position:fixed; inset:0; z-index:99999;
            background:rgba(0,0,0,0.45); backdrop-filter:blur(6px);
            align-items:center; justify-content:center; padding:16px;">
            <div style="background:#fff; border-radius:20px; width:100%; max-width:400px;
                padding:40px 32px 32px; box-shadow:0 20px 60px rgba(0,0,0,0.15);
                text-align:center; animation:modalSlideUp 0.3s ease;">
                <h5 style="color:#1e293b; font-weight:700; font-size:1.15rem; margin-bottom:6px;">2-Step Verification</h5>
                <p style="color:#64748b; font-size:0.83rem; margin-bottom:4px;">Enter the 6-digit code</p>
                <p id="otpEmailHint" style="color:#94a3b8; font-size:0.8rem; margin-bottom:24px;"></p>
                <div id="otpError" style="display:none; background:#fef2f2; border:1px solid #fecaca;
                    border-radius:8px; color:#dc2626; font-size:0.82rem;
                    padding:10px 14px; margin-bottom:14px; text-align:left;">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    <span id="otpErrorMsg"></span>
                </div>
                <div style="display:flex; gap:10px; margin-bottom:16px;">
                    <input id="otpInput" type="text" inputmode="numeric" maxlength="6"
                        placeholder="Enter 6-digit code"
                        style="flex:1; padding:11px 14px; background:#f8fafc;
                            border:1.5px solid #e2e8f0; border-radius:10px; color:#1e293b;
                            font-size:1rem; letter-spacing:4px; text-align:center; outline:none;"
                        onfocus="this.style.borderColor='#4a7cf7'; this.style.background='#fff';"
                        onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc';">
                    <button id="resendBtn" onclick="resendOTP()"
                        style="padding:11px 12px; border-radius:10px; border:1.5px solid #e2e8f0;
                            background:#f8fafc; color:#94a3b8; font-size:0.78rem;
                            cursor:not-allowed; white-space:nowrap; min-width:120px;" disabled>
                        Resend: <span id="resendTimer">60</span>s
                    </button>
                </div>
                <button id="otpSubmitBtn" onclick="submitOTP()"
                    style="width:100%; padding:12px; border-radius:10px; border:none;
                        background:linear-gradient(135deg,#4a7cf7,#6ea8fe); color:#fff;
                        font-size:0.95rem; font-weight:600; cursor:pointer; margin-bottom:14px;
                        box-shadow:0 4px 14px rgba(74,124,247,0.3);">
                    <span id="otpBtnText">Next</span>
                    <span id="otpSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
                <button onclick="closeOTPModal()"
                    style="background:none; border:none; color:#94a3b8; font-size:0.8rem; cursor:pointer;">
                    <i class="bi bi-arrow-left me-1"></i>Back to Login
                </button>
            </div>
        </div>

        <style>
            @keyframes modalSlideUp {
                from { transform: translateY(24px); opacity: 0; }
                to   { transform: translateY(0);    opacity: 1; }
            }
        </style>
    `);

    /* Form Submit */
    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        hideError();

        const email    = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email)    { showError('Please enter your email address.'); return; }
        if (!password) { showError('Please enter your password.'); return; }

        showLoading();

        const formData = new FormData();
        formData.append('email',    email);
        formData.append('password', password);

        fetch('/api/login', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            hideLoading();
            if (!data.success) {
                showError(data.message || 'Something went wrong. Please try again.');
                passwordInput.value = '';
                passwordInput.focus();
                return;
            }
            if (rememberMe.checked) {
                localStorage.setItem('rememberedEmail', email);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
            if (data.otp_required) {
                openOTPModal(email, data.masked_email);
            } else {
                window.location.href = data.redirect;
            }
        })
        .catch(() => {
            hideLoading();
            showError('Something went wrong. Please try again.');
        });
    });

    emailInput.addEventListener('input',    hideError);
    passwordInput.addEventListener('input', hideError);
});

/* OTP Modal (para sa 2-step login verification) */
let resendInterval = null;
let currentEmail   = '';

function openOTPModal(email, maskedEmail) {
    currentEmail = email;
    document.getElementById('otpEmailHint').innerHTML =
        `Your code was sent to <strong style="color:#475569;">${maskedEmail}</strong>`;
    document.getElementById('otpOverlay').style.display = 'flex';
    document.getElementById('otpInput').value = '';
    hideOTPError();
    startResendTimer();
    setTimeout(() => document.getElementById('otpInput').focus(), 100);
}

function closeOTPModal() {
    document.getElementById('otpOverlay').style.display = 'none';
    clearInterval(resendInterval);
}

function showOTPError(msg) {
    document.getElementById('otpErrorMsg').textContent = msg;
    document.getElementById('otpError').style.display  = 'block';
}
function hideOTPError() {
    document.getElementById('otpError').style.display = 'none';
}

function startResendTimer() {
    clearInterval(resendInterval);
    let seconds     = 60;
    const resendBtn = document.getElementById('resendBtn');
    resendBtn.disabled = true;
    resendBtn.style.cursor = 'not-allowed';
    resendBtn.style.color  = '#94a3b8';
    resendBtn.innerHTML = 'Resend: <span id="resendTimer">' + seconds + '</span>s';

    resendInterval = setInterval(() => {
        seconds--;
        const t = document.getElementById('resendTimer');
        if (t) t.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(resendInterval);
            resendBtn.innerHTML = 'Resend code';
            resendBtn.disabled  = false;
            resendBtn.style.cursor      = 'pointer';
            resendBtn.style.color       = '#4a7cf7';
            resendBtn.style.borderColor = '#bfdbfe';
            resendBtn.style.background  = '#eff6ff';
        }
    }, 1000);
}

function resendOTP() {
    const formData = new FormData();
    formData.append('email', currentEmail);
    fetch('/api/login/resend-otp', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) { hideOTPError(); startResendTimer(); }
            else showOTPError(data.message || 'Failed to resend OTP.');
        })
        .catch(() => showOTPError('Something went wrong.'));
}

function submitOTP() {
    const otp = document.getElementById('otpInput').value.trim();
    if (!otp || otp.length !== 6) { showOTPError('Please enter the 6-digit code.'); return; }

    hideOTPError();
    const btn     = document.getElementById('otpSubmitBtn');
    const btnText = document.getElementById('otpBtnText');
    const spinner = document.getElementById('otpSpinner');
    btn.disabled = true;
    btnText.textContent = 'Verifying...';
    spinner.classList.remove('d-none');

    const formData = new FormData();
    formData.append('email', currentEmail);
    formData.append('otp',   otp);

    fetch('/api/login/verify-otp', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                btn.disabled = false;
                btnText.textContent = 'Next';
                spinner.classList.add('d-none');
                showOTPError(data.message || 'Invalid OTP.');
                document.getElementById('otpInput').value = '';
                document.getElementById('otpInput').focus();
            }
        })
        .catch(() => {
            btn.disabled = false;
            btnText.textContent = 'Next';
            spinner.classList.add('d-none');
            showOTPError('Something went wrong.');
        });
}

document.addEventListener('keydown', function (e) {
    const overlay = document.getElementById('otpOverlay');
    if (e.key === 'Enter' && overlay && overlay.style.display === 'flex') submitOTP();
});