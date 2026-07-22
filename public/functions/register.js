/* ── Password rules ── */
const RULES = {
  length:  pw => pw.length >= 8,
  case:    pw => /[A-Z]/.test(pw) && /[a-z]/.test(pw),
  number:  pw => /[0-9]/.test(pw),
  special: pw => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pw),
};

const RULE_LABELS = {
  length:  'at least 8 characters',
  case:    'uppercase and lowercase letters',
  number:  'at least one number',
  special: 'at least one special character',
};

function validatePassword(pw) {
  return Object.entries(RULES)
    .filter(([key, test]) => !test(pw))
    .map(([key]) => RULE_LABELS[key]);
}

/* ── CSRF token (kailangan sa bawat POST request papunta sa Laravel) ── */
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

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

/* ── Toast notification (replaces all alert()) ── */
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

/* ── Form error alert ── */
function showError(msg) {
  const alert = document.getElementById('registerAlert');
  alert.textContent = msg;
  alert.style.display = 'block';
  setTimeout(() => alert.style.display = 'none', 5000);
}

function hideError() {
  document.getElementById('registerAlert').style.display = 'none';
}

/* ── OTP Modal ── */
function showOTPModal(email) {
  document.getElementById('otpEmail').value = email;
  document.getElementById('otpModal').classList.add('show');
}

function showSuccessModal() {
  document.getElementById('otpModal').classList.remove('show');
  document.getElementById('successModal').classList.add('show');
}

/* ── Cooldown timer for Resend button ── */
function startCooldown(seconds) {
  const btn = document.getElementById('resendOtpBtn');
  btn.disabled = true;

  let remaining = seconds;
  btn.textContent = `Resend OTP (${remaining}s)`;

  const timer = setInterval(() => {
    remaining--;
    btn.textContent = `Resend OTP (${remaining}s)`;
    if (remaining <= 0) {
      clearInterval(timer);
      btn.disabled = false;
      btn.textContent = 'Resend OTP';
    }
  }, 1000);
}

/* ── Main ── */
document.addEventListener('DOMContentLoaded', function () {

  // ── SIGN UP
  const signupBtn = document.getElementById('signupBtn');
  if (signupBtn) {
    signupBtn.addEventListener('click', function () {
      hideError();

      const lastName        = document.getElementById('lastName').value.trim();
      const firstName       = document.getElementById('firstName').value.trim();
      const middleName      = document.getElementById('middleName').value.trim();
      const email           = document.getElementById('email').value.trim();
      const password        = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirmPassword').value;
      const birthMonth      = document.getElementById('birthMonth').value;
      const birthDay        = document.getElementById('birthDay').value;
      const birthYear       = document.getElementById('birthYear').value;

      if (!lastName || !firstName || !email || !password || !confirmPassword) {
        showError('Please fill in all required fields.');
        return;
      }

      const failing = validatePassword(password);
      if (failing.length > 0) {
        showError('Password must include: ' + failing.join(', ') + '.');
        document.getElementById('password').focus();
        return;
      }

      if (password !== confirmPassword) {
        showError('Passwords do not match.');
        document.getElementById('confirmPassword').focus();
        return;
      }

      signupBtn.disabled = true;
      signupBtn.innerHTML = 'Sending OTP... <span class="spinner-border spinner-border-sm ms-1"></span>';

      const formData = new FormData();
      formData.append('lastName',        lastName);
      formData.append('firstName',       firstName);
      formData.append('middleName',      middleName);
      formData.append('email',           email);
      formData.append('password',        password);
      formData.append('confirmPassword', confirmPassword);
      formData.append('birthMonth',      birthMonth);
      formData.append('birthDay',        birthDay);
      formData.append('birthYear',       birthYear);

      fetch('/api/register', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showOTPModal(data.email);
        } else {
          showError(data.message);
        }
        signupBtn.disabled = false;
        signupBtn.innerHTML = 'Sign Up <i class="bi bi-arrow-right"></i>';
      })
      .catch(() => {
        showError('Something went wrong. Please try again.');
        signupBtn.disabled = false;
        signupBtn.innerHTML = 'Sign Up <i class="bi bi-arrow-right"></i>';
      });
    });
  }

  // ── VERIFY OTP
  const verifyOtpBtn = document.getElementById('verifyOtpBtn');
  if (verifyOtpBtn) {
    verifyOtpBtn.addEventListener('click', function () {
      const email = document.getElementById('otpEmail').value;
      const otp   = document.getElementById('otpInput').value.trim();

      if (!otp || otp.length !== 6) {
        showToast('Please enter the 6-digit OTP.', 'error');
        return;
      }

      verifyOtpBtn.disabled = true;
      verifyOtpBtn.textContent = 'Verifying...';

      const formData = new FormData();
      formData.append('email', email);
      formData.append('otp',   otp);

      fetch('/api/register/verify-otp', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showSuccessModal();
        } else {
          showToast(data.message, 'error');
          verifyOtpBtn.disabled = false;
          verifyOtpBtn.textContent = 'Verify';
        }
      })
      .catch(() => {
        showToast('Something went wrong.', 'error');
        verifyOtpBtn.disabled = false;
        verifyOtpBtn.textContent = 'Verify';
      });
    });
  }

  // ── RESEND OTP
  const resendOtpBtn = document.getElementById('resendOtpBtn');
  if (resendOtpBtn) {
    resendOtpBtn.addEventListener('click', function () {
      const email = document.getElementById('otpEmail').value;
      resendOtpBtn.textContent = 'Sending...';
      resendOtpBtn.disabled = true;

      const formData = new FormData();
      formData.append('email', email);

      fetch('/api/register/resend-otp', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('OTP resent successfully! Check your email.', 'success');
          startCooldown(120);
        } else {
          showToast(data.message, 'error');
          resendOtpBtn.disabled = false;
          resendOtpBtn.textContent = 'Resend OTP';
        }
      })
      .catch(() => {
        showToast('Something went wrong.', 'error');
        resendOtpBtn.disabled = false;
        resendOtpBtn.textContent = 'Resend OTP';
      });
    });
  }

});