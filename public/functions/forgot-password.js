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

function showError(panelNum, msg) {
    const id = 'error' + panelNum;
    const el = document.getElementById(id);
    if (el) {
        el.textContent = msg;
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 5000);
    }
}

/* ── Standard headers for every AJAX call to Laravel.
   Accept + X-Requested-With tell Laravel "this is an AJAX/JSON
   request" — without these, a 419 (CSRF) or 422 (validation) error
   comes back as a full HTML error page instead of JSON, which is
   what caused the "Unexpected token '<'" crash. ── */
function ajaxHeaders() {
    return {
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
}

document.addEventListener('DOMContentLoaded', function () {

    // ── STEP 1: Send OTP
    document.getElementById('sendOtpBtn').addEventListener('click', function () {
        const email = document.getElementById('emailInput').value.trim();
        if (!email) { showError(1, 'Please enter your email address.'); return; }

        const btn = this;
        btn.disabled = true;
        btn.classList.add('is-loading');

        const formData = new FormData();
        formData.append('email', email);

        fetch('/api/forgot-password/send-otp', {
            method: 'POST',
            credentials: 'same-origin',
            headers: ajaxHeaders(),
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.classList.remove('is-loading');

            if (data.success) {
                openForgotPasswordOtpModal(email);
            } else {
                showError(1, data.message);
            }
        })
        .catch(() => {
            showError(1, 'Something went wrong. Please try again.');
            btn.disabled = false;
            btn.classList.remove('is-loading');
        });
    });

    // ============================================================
    // RESET PASSWORD MODAL — opens after OTP is verified
    // ============================================================
    const resetModalEl   = document.getElementById('resetPasswordModal');
    const rpSubmitBtn     = document.getElementById('rpSubmitBtn');
    const rpDiscardBtn    = document.getElementById('rpDiscardBtn');
    const rpError         = document.getElementById('rpError');
    const rpNewPassword   = document.getElementById('rpNewPassword');
    const rpConfirmPassword = document.getElementById('rpConfirmPassword');

    // ---- Password strength meter (same pattern used elsewhere in the app) ----
    function evaluatePasswordStrength(pw) {
        const hasUpper  = /[A-Z]/.test(pw);
        const hasNumber = /[0-9]/.test(pw);
        const hasLength = pw.length >= 8;
        const metCount  = [hasUpper, hasNumber, hasLength].filter(Boolean).length;
        return { hasUpper, hasNumber, hasLength, metCount };
    }

    function setReqState(id, met) {
        const el = document.getElementById(id);
        if (!el) return;
        const icon = el.querySelector('i');
        if (met) {
            el.classList.add('is-met');
            icon.className = 'bi bi-check-circle-fill';
        } else {
            el.classList.remove('is-met');
            icon.className = 'bi bi-x-circle';
        }
    }

    function updateRpStrengthUI() {
        const strengthWrap = document.getElementById('rpStrength');
        if (!rpNewPassword || !strengthWrap) return;

        const pw = rpNewPassword.value;
        const result = evaluatePasswordStrength(pw);

        setReqState('rpReqUpper', result.hasUpper);
        setReqState('rpReqNumber', result.hasNumber);
        setReqState('rpReqLength', result.hasLength);

        const segs = strengthWrap.querySelectorAll('.rp-strength-seg');
        const label = document.getElementById('rpStrengthLabel');

        segs.forEach(seg => seg.className = 'rp-strength-seg');

        if (pw.length === 0) {
            if (label) label.textContent = 'Password strength. Must contain;';
            return;
        }

        const level = result.metCount;
        const levelClass = level <= 1 ? 'is-weak' : (level === 2 ? 'is-medium' : 'is-strong');
        const levelLabel = level <= 1 ? 'Weak password. Must contain;' : (level === 2 ? 'Medium password. Must contain;' : 'Strong password.');

        for (let i = 0; i < level; i++) segs[i].classList.add(levelClass);
        if (label) label.textContent = levelLabel;
    }

    if (rpNewPassword) rpNewPassword.addEventListener('input', updateRpStrengthUI);

    const rpClearConfirmBtn = document.getElementById('rpClearConfirmBtn');
    if (rpClearConfirmBtn) {
        rpClearConfirmBtn.addEventListener('click', function () {
            rpConfirmPassword.value = '';
        });
    }

    document.querySelectorAll('.rp-pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.dataset.target);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye-slash';
            }
        });
    });

    function resetRpForm() {
        rpError.classList.add('d-none');
        rpNewPassword.value = '';
        rpConfirmPassword.value = '';
        updateRpStrengthUI();
    }

    if (resetModalEl) {
        resetModalEl.addEventListener('show.bs.modal', resetRpForm);
    }

    // ---- Discard: close modal, go back to Step 1 ----
    if (rpDiscardBtn) {
        rpDiscardBtn.addEventListener('click', function () {
            const modal = bootstrap.Modal.getInstance(resetModalEl);
            if (modal) modal.hide();
            goStep(1);
        });
    }

    // ---- Submit: validate, call API, show toast, redirect to login ----
    if (rpSubmitBtn) {
        rpSubmitBtn.addEventListener('click', function () {
            rpError.classList.add('d-none');

            const password = rpNewPassword.value;
            const confirm  = rpConfirmPassword.value;

            if (!password || !confirm) {
                rpError.textContent = 'Please fill in both fields.';
                rpError.classList.remove('d-none');
                return;
            }

            const strength = evaluatePasswordStrength(password);
            if (!strength.hasUpper || !strength.hasNumber || !strength.hasLength) {
                rpError.textContent = 'Password must have at least 1 uppercase letter, 1 number, and be 8+ characters.';
                rpError.classList.remove('d-none');
                return;
            }

            if (password !== confirm) {
                rpError.textContent = 'Passwords do not match.';
                rpError.classList.remove('d-none');
                return;
            }

            rpSubmitBtn.disabled = true;
            rpSubmitBtn.classList.add('is-loading');

            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('password', password);
            formData.append('confirmPassword', confirm);

            fetch('/api/forgot-password/reset', {
                method: 'POST',
                credentials: 'same-origin',
                headers: ajaxHeaders(),
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(resetModalEl);
                    if (modal) modal.hide();

                    showToast('Password changed successfully!', 'success');

                    setTimeout(() => {
                        window.location.href = document.body.dataset.loginUrl || '/login';
                    }, 1800);
                } else {
                    rpError.textContent = data.message || 'Something went wrong. Please try again.';
                    rpError.classList.remove('d-none');
                }
            })
            .catch(() => {
                rpError.textContent = 'Something went wrong. Please check your connection.';
                rpError.classList.remove('d-none');
            })
            .finally(() => {
                rpSubmitBtn.disabled = false;
                rpSubmitBtn.classList.remove('is-loading');
            });
        });
    }
});

/* ── Opens the shared OtpModal, wired up to the forgot-password OTP endpoints.
   Kapag na-verify na, bubukas na yung Reset Password MODAL (hindi na panel3). ── */
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
                headers: ajaxHeaders(),
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
                headers: ajaxHeaders(),
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
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('resetPasswordModal'));
            modal.show();
        }
    });
}