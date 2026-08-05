/* ── Toast notification (para hindi na gamitin ang native alert()) ── */
function showToast(msg, type = 'success') {
    const existing = document.getElementById('ran-toast');
    if (existing) existing.remove();

    const colors = {
        success: { bg: '#f0fdf4', border: '#86efac', text: '#166534', icon: 'bi-check-circle-fill', iconColor: '#16a34a' },
        error:   { bg: '#fef2f2', border: '#fca5a5', text: '#991b1b', icon: 'bi-exclamation-circle-fill', iconColor: '#dc2626' },
        info:    { bg: '#eff6ff', border: '#93c5fd', text: '#1e40af', icon: 'bi-info-circle-fill', iconColor: '#2563eb' },
    };

    const c = colors[type] || colors.info;

    const toast = document.createElement('div');
    toast.id = 'ran-toast';
    toast.innerHTML = `
        <i class="bi ${c.icon}" style="color:${c.iconColor}; font-size:1rem; flex-shrink:0;"></i>
        <span>${msg}</span>
    `;
    Object.assign(toast.style, {
        position:      'fixed',
        top:           '24px',
        right:         '24px',
        display:       'flex',
        alignItems:    'center',
        gap:           '10px',
        padding:       '12px 18px',
        background:    c.bg,
        border:        `1px solid ${c.border}`,
        color:         c.text,
        borderRadius:  '10px',
        fontSize:      '0.875rem',
        fontWeight:    '500',
        boxShadow:     '0 4px 16px rgba(0,0,0,0.10)',
        zIndex:        '99999',
        maxWidth:      '320px',
        animation:     'toastIn 0.3s ease',
    });

    if (!document.getElementById('toast-style')) {
        const style = document.createElement('style');
        style.id = 'toast-style';
        style.textContent = `
            @keyframes toastIn  { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
            @keyframes toastOut { from { opacity:1; transform:translateY(0); } to { opacity:0; transform:translateY(-12px); } }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function goStep(n) {
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel' + n).classList.add('active');
}

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

function showError(panelNum, msg) {
    const id    = 'error' + panelNum;
    const el    = document.getElementById(id);
    if (el) {
        el.textContent = msg;
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 5000);
    }
}

document.addEventListener('DOMContentLoaded', function () {

    // ── STEP 1: Send OTP
    document.getElementById('sendOtpBtn').addEventListener('click', function () {
        const email = document.getElementById('emailInput').value.trim();
        if (!email) { showError(1, 'Please enter your email address.'); return; }

        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Sending...';

        const formData = new FormData();
        formData.append('email', email);

        fetch('/api/forgot-password/send-otp', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-XSRF-TOKEN': getCsrfToken() },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Send Verification Code';

            if (data.success) {
                openForgotPasswordOtpModal(email);
            } else {
                showError(1, data.message);
            }
        })
        .catch(() => {
            showError(1, 'Something went wrong. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Send Verification Code';
        });
    });

    // ── STEP 3: Reset Password
    document.getElementById('resetBtn').addEventListener('click', function () {
        const password        = document.getElementById('newPw').value;
        const confirmPassword = document.getElementById('confirmPw').value;

        if (!password || password.length < 6) {
            showError(3, 'Password must be at least 6 characters.');
            return;
        }
        if (password !== confirmPassword) {
            showError(3, 'Passwords do not match.');
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Resetting...';

        const formData = new FormData();
        formData.append('action', 'reset_password');
        formData.append('password', password);
        formData.append('confirmPassword', confirmPassword);

        fetch('/api/forgot-password/reset', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-XSRF-TOKEN': getCsrfToken() },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                goStep(4);
            } else {
                showError(3, data.message);
                btn.disabled = false;
                btn.textContent = 'Reset Password';
            }
        })
        .catch(() => {
            showError(3, 'Something went wrong.');
            btn.disabled = false;
            btn.textContent = 'Reset Password';
        });
    });
});

/* ── Opens the shared OtpModal, wired up to the forgot-password OTP endpoints.
   Kapag na-verify na, diretso na sa Panel 3 (bagong password) — wala nang
   panel2 na atskip pa. ── */
function openForgotPasswordOtpModal(email) {
    OtpModal.open({
        maskedEmail: email,
        onVerify: function (code) {
            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('email', email);
            formData.append('otp', code);

            return fetch('/api/forgot-password/reset', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-XSRF-TOKEN': getCsrfToken() },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Invalid or expired code.');
                return data;
            });
        },
        onResend: function () {
            const formData = new FormData();
            formData.append('email', email);

            return fetch('/api/forgot-password/send-otp', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-XSRF-TOKEN': getCsrfToken() },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Could not resend code.');
                return data;
            });
        },
        onSuccess: function () {
            OtpModal.close();
            goStep(3);
        }
    });
}