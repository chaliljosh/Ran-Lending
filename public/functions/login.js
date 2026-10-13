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

/* ── CROSS-TAB LOGIN SYNC ──
   Mirror of the logout broadcast in logout-modal.js, but the other
   way around: when this tab successfully logs in, any other open
   tab still sitting on /login gets redirected automatically instead
   of staying on the login form until the user manually refreshes. */
const LOGIN_BROADCAST_KEY = 'ran_login_broadcast';

function broadcastLogin(redirectUrl) {
    // Wrapped in try/catch: some browsers block localStorage in
    // certain contexts (private mode, etc.) — that should never
    // block the actual login redirect of THIS tab.
    try {
        localStorage.setItem(LOGIN_BROADCAST_KEY, JSON.stringify({
            redirect: redirectUrl,
            ts: Date.now(),
        }));
    } catch (err) {
        console.error('[login.js] cross-tab broadcast failed (ignoring):', err);
    }
}

// Fires in OTHER tabs when this key changes in localStorage.
// (The 'storage' event never fires in the tab that made the change,
// only in other tabs — same behavior the logout sync relies on.)
window.addEventListener('storage', function (e) {
    if (e.key === LOGIN_BROADCAST_KEY && e.newValue) {
        try {
            const data = JSON.parse(e.newValue);
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } catch (err) {
            console.error('[login.js] failed to parse login broadcast:', err);
        }
    }
});

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

    // Disables the button and swaps the "Sign In" label for the
    // spinner only. See the #loginBtn rules in login.css.
    function showLoading() {
        loginBtn.disabled = true;
        loginBtn.classList.add('is-loading');
        loginBtnText.classList.add('d-none');
        loginSpinner.classList.remove('d-none');
    }
    function hideLoading() {
        loginBtn.disabled = false;
        loginBtn.classList.remove('is-loading');
        loginBtnText.classList.remove('d-none');
        loginSpinner.classList.add('d-none');
    }

    // Kapag bumalik ang user via Back button (cached page), i-reset
    // ang button para hindi ito stuck sa loading.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) hideLoading();
    });

    /* Form Submit */
    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();

        // Iwas double-submit habang naglo-load pa
        if (loginBtn.disabled) return;

        hideError();

        const email    = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email)    { showError('Please enter your email address.'); return; }
        if (!password) { showError('Please enter your password.'); return; }

        showLoading();

        const formData = new FormData();
        formData.append('email',    email);
        formData.append('password', password);

        // CSRF token — gamit ang shared getCsrfToken() helper mula sa
        // csrf.js (kailangan itong naka-load na bago ito), kumukuha ng
        // XSRF-TOKEN cookie na Laravel na mismo ang nag-set.
        fetch('/api/login', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-XSRF-TOKEN': getCsrfToken() },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                hideLoading();
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
            // Success: sinadya na HINDI tinatawag ang hideLoading() dito,
            // para nakadisable pa rin ang button + spinner hanggang
            // matapos ang redirect.
            broadcastLogin(data.redirect);
            window.location.href = data.redirect;
        })
        .catch(() => {
            hideLoading();
            showError('Something went wrong. Please try again.');
        });
    });

    emailInput.addEventListener('input',    hideError);
    passwordInput.addEventListener('input', hideError);
});