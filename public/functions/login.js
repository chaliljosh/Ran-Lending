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

let pendingLoginEmail = '';

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
                pendingLoginEmail = email;
                openLoginOtpModal(data.masked_email);
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

/* ── Opens the shared OtpModal, wired up to the login OTP endpoints. ── */
function openLoginOtpModal(maskedEmail) {
    OtpModal.open({
        maskedEmail: maskedEmail,
        onVerify: function (code) {
            const formData = new FormData();
            formData.append('email', pendingLoginEmail);
            formData.append('otp',   code);

            return fetch('/api/login/verify-otp', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Invalid OTP.');
                    return data;
                });
        },
        onResend: function () {
            const formData = new FormData();
            formData.append('email', pendingLoginEmail);

            return fetch('/api/login/resend-otp', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Failed to resend OTP.');
                    return data;
                });
        },
        onSuccess: function (data) {
            window.location.href = data.redirect;
        }
    });
}