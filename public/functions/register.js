/* ── Password rules ──
   Tinugma sa parehong 3-rule na policy na ginagamit sa borrower
   Account Settings > Change Password (uppercase, number, 8+ chars) —
   para consistent ang password requirements sa buong app. */
const RULES = {
  length: pw => pw.length >= 8,
  upper:  pw => /[A-Z]/.test(pw),
  number: pw => /[0-9]/.test(pw),
};

const RULE_LABELS = {
  length: 'at least 8 characters',
  upper:  'at least 1 uppercase letter',
  number: 'at least 1 number',
};

function validatePassword(pw) {
  return Object.entries(RULES)
    .filter(([key, test]) => !test(pw))
    .map(([key]) => RULE_LABELS[key]);
}

/* ── Email format check ──
   Simple regex — hindi perfect (walang regex na perfect for email),
   pero sapat na para hulihin yung mga obvious na mali gaya ng
   walang "@" o walang domain (hal. "crystallake241"). */
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/* ── Age check: 18 years old pataas lang ──
   Kinukumpara ang birthday sa petsang eksaktong 18 taon na ang nakalipas
   mula ngayon, para tama kahit hindi pa dumarating ang birthday ngayong taon. */
const MIN_AGE = 18;

function isAtLeastMinAge(year, month, day) {
  const today  = new Date();
  const cutoff = new Date(today.getFullYear() - MIN_AGE, today.getMonth(), today.getDate());
  const birth  = new Date(year, month - 1, day);
  return birth <= cutoff;
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
  const text  = document.getElementById('registerAlertText');
  text.textContent = msg;
  alert.classList.add('is-visible');
  clearTimeout(alert._hideTimer);
  alert._hideTimer = setTimeout(() => alert.classList.remove('is-visible'), 5000);
}

function hideError() {
  document.getElementById('registerAlert').classList.remove('is-visible');
}

function showSuccessModal() {
  document.getElementById('successModal').classList.add('show');
}

/* ── Password strength meter — live checklist + strength bar.
   Same pattern/logic as borrower Account Settings > Change Password
   (bwl-pw-strength), naka-scope lang dito papuntang plain, unprefixed
   classes dahil sa register page ito nag-iisa (bootstrap icons din
   ang gamit, hindi feather, kaya bi-check-circle/bi-x-circle). ── */
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
    icon.className = 'bi bi-check-circle';
  } else {
    el.classList.remove('is-met');
    icon.className = 'bi bi-x-circle';
  }
}

function updatePasswordStrengthUI() {
  const passwordInput = document.getElementById('password');
  const strengthWrap  = document.getElementById('pwStrength');
  if (!passwordInput || !strengthWrap) return;

  const pw     = passwordInput.value;
  const result = evaluatePasswordStrength(pw);

  setReqState('pwReqUpper', result.hasUpper);
  setReqState('pwReqNumber', result.hasNumber);
  setReqState('pwReqLength', result.hasLength);

  const segs  = strengthWrap.querySelectorAll('.pw-strength-seg');
  const label = document.getElementById('pwStrengthLabel');

  segs.forEach(seg => { seg.className = 'pw-strength-seg'; });

  if (pw.length === 0) {
    if (label) label.textContent = 'Password strength. Must contain;';
    return;
  }

  const level = result.metCount; // 0–3
  const levelClass = level <= 1 ? 'is-weak' : (level === 2 ? 'is-medium' : 'is-strong');
  const levelLabel = level <= 1 ? 'Weak password. Must contain;' : (level === 2 ? 'Medium password. Must contain;' : 'Strong password.');

  for (let i = 0; i < level; i++) {
    segs[i].classList.add(levelClass);
  }
  if (label) label.textContent = levelLabel;
}

/* ── Birthday dropdowns — Month / Day / Year ──
   Consistent na behavior sa lahat ng browsers (hindi native calendar
   widget), at pwedeng mag-type ng taon habang bukas ang dropdown para
   agad ma-jump doon nang walang scrolling. ── */
function populateBirthdaySelects() {
  const yearSelect  = document.getElementById('birthYear');
  const monthSelect = document.getElementById('birthMonth');
  const daySelect   = document.getElementById('birthDay');
  if (!yearSelect || !monthSelect || !daySelect) return;

  const currentYear = new Date().getFullYear();
  const maxYear = currentYear;
  const minYear = currentYear - 100;

  for (let y = maxYear; y >= minYear; y--) {
    const opt = document.createElement('option');
    opt.value = y;
    opt.textContent = y;
    yearSelect.appendChild(opt);
  }

  // I-populate/i-refresh ang Day options base sa napiling Month/Year,
  // para tama ang bilang ng araw (leap years, 30 vs 31, atbp).
  function refreshDays() {
    const month = parseInt(monthSelect.value, 10);
    const year  = parseInt(yearSelect.value, 10) || currentYear;
    const daysInMonth = month ? new Date(year, month, 0).getDate() : 31;
    const prevValue = daySelect.value;

    daySelect.innerHTML = '<option value="">Day</option>';
    for (let d = 1; d <= daysInMonth; d++) {
      const opt = document.createElement('option');
      opt.value = d;
      opt.textContent = d;
      daySelect.appendChild(opt);
    }

    if (prevValue && parseInt(prevValue, 10) <= daysInMonth) {
      daySelect.value = prevValue;
    }
  }

  monthSelect.addEventListener('change', refreshDays);
  yearSelect.addEventListener('change', refreshDays);
  refreshDays();
}

/* ── Track the email currently being verified, so the OtpModal callbacks
   (which only know the OTP code, not the email) can send it along. ── */
let pendingRegisterEmail = '';

/* ── Main ── */
document.addEventListener('DOMContentLoaded', function () {

  // ── Birthday dropdowns setup
  populateBirthdaySelects();

  // ── Live password strength meter
  const passwordInputEl = document.getElementById('password');
  if (passwordInputEl) {
    passwordInputEl.addEventListener('input', updatePasswordStrengthUI);
    updatePasswordStrengthUI();
  }

  // ── SIGN UP
  const signupBtn = document.getElementById('signupBtn');

  // Loading state: naka-disable ang button at spinner lang ang makikita.
  // Ang `is-loading` class ang nagtatago ng label at nagpapakita ng
  // spinner (see .btn-signup rules sa register.css).
  function showLoading() {
    signupBtn.disabled = true;
    signupBtn.classList.add('is-loading');
  }
  function hideLoading() {
    signupBtn.disabled = false;
    signupBtn.classList.remove('is-loading');
  }

  if (signupBtn) {
    signupBtn.addEventListener('click', function () {
      // Iwas double-click habang naglo-load pa
      if (signupBtn.disabled) return;

      hideError();

      const lastName        = document.getElementById('lastName').value.trim();
      const firstName       = document.getElementById('firstName').value.trim();
      const middleName      = document.getElementById('middleName').value.trim();
      const email            = document.getElementById('email').value.trim();
      const password        = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirmPassword').value;

      const birthMonth = document.getElementById('birthMonth').value;
      const birthDay   = document.getElementById('birthDay').value;
      const birthYear  = document.getElementById('birthYear').value;

      if (!lastName || !firstName || !email || !password || !confirmPassword) {
        showError('Please fill in all required fields.');
        return;
      }

      // ✅ Specific na email format check, bago pa man mag-fetch
      if (!isValidEmail(email)) {
        showError('Please enter a valid email address (e.g. name@example.com).');
        document.getElementById('email').focus();
        return;
      }

      if (!birthMonth || !birthDay || !birthYear) {
        showError('Please select your complete birthday.');
        return;
      }

      // ✅ 18 years old pataas lang
      if (!isAtLeastMinAge(parseInt(birthYear, 10), parseInt(birthMonth, 10), parseInt(birthDay, 10))) {
        showError('You must be 18 years old or above to register.');
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

      showLoading();

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
      .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, data })))
      .then(({ ok, data }) => {
        hideLoading();

        if (data.success) {
          pendingRegisterEmail = data.email;
          openRegisterOtpModal(data.email);
          return;
        }

        // ✅ Kung Laravel validation error format ang sumagot
        // (hal. { message: "...", errors: { email: [...] } }),
        // kunin yung unang specific message imbes na generic text.
        if (data.errors) {
          const firstField = Object.keys(data.errors)[0];
          const firstMessage = data.errors[firstField][0];
          showError(firstMessage);
        } else {
          showError(data.message || 'Registration failed. Please try again.');
        }
      })
      .catch(() => {
        showError('Unable to reach the server. Please check your connection and try again.');
        hideLoading();
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