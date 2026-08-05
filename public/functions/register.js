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

function showSuccessModal() {
  document.getElementById('successModal').classList.add('show');
}

/* ── Track the email currently being verified, so the OtpModal callbacks
   (which only know the OTP code, not the email) can send it along. ── */
let pendingRegisterEmail = '';

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
      const email            = document.getElementById('email').value.trim();
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
        signupBtn.disabled = false;
        signupBtn.innerHTML = 'Create Account';

        if (data.success) {
          pendingRegisterEmail = data.email;
          openRegisterOtpModal(data.email);
        } else {
          showError(data.message);
        }
      })
      .catch(() => {
        showError('Something went wrong. Please try again.');
        signupBtn.disabled = false;
        signupBtn.innerHTML = 'Create Account';
      });
    });
  }
});

/* ── Opens the shared OtpModal, wired up to the register OTP endpoints. ── */
function openRegisterOtpModal(email) {
  OtpModal.open({
    maskedEmail: email,
    onVerify: function (code) {
      const formData = new FormData();
      formData.append('email', pendingRegisterEmail);
      formData.append('otp',   code);

      return fetch('/api/register/verify-otp', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() },
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
      formData.append('email', pendingRegisterEmail);

      return fetch('/api/register/resend-otp', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() },
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
      showSuccessModal();
    }
  });
}