document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('nxlChangePasswordForm');
    if (!form) return;

    var alertBox = document.getElementById('nxlPasswordFormAlert');
    var submitBtn = document.getElementById('nxlPasswordSubmitBtn');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    var modalEl = document.getElementById('nxlChangePasswordModal');
    var successModalEl = document.getElementById('nxlPasswordSuccessModal');

    var newPasswordInput = document.getElementById('nxlNewPassword');
    var confirmPasswordInput = document.getElementById('nxlConfirmPassword');
    var clearBtn = document.getElementById('nxlPasswordClearBtn');
    var strengthBar = document.getElementById('nxlPwStrengthBar');
    var strengthLabel = document.getElementById('nxlPwStrengthLabel');

    function showAlert(message, type) {
        alertBox.textContent = message;
        alertBox.className = 'nxl-password-alert nxl-password-alert-' + type;
        alertBox.style.display = 'block';
    }

    // ---- Live password strength + requirement checklist ----
    function evaluatePassword(value) {
        var rules = {
            uppercase: /[A-Z]/.test(value),
            number: /[0-9]/.test(value),
            length: value.length >= 8,
        };

        Object.keys(rules).forEach(function (rule) {
            var item = form.querySelector('.nxl-pw-check-item[data-rule="' + rule + '"]');
            if (!item) return;

            var icon = item.querySelector('.nxl-pw-check-icon i');
            if (rules[rule]) {
                item.classList.add('is-met');
                icon.className = 'feather-check';
            } else {
                item.classList.remove('is-met');
                icon.className = 'feather-x';
            }
        });

        var metCount = Object.values(rules).filter(Boolean).length;

        strengthBar.classList.remove('is-weak', 'is-medium', 'is-strong');

        if (value.length === 0) {
            strengthLabel.textContent = 'Password must contain;';
        } else if (metCount <= 1) {
            strengthBar.classList.add('is-weak');
            strengthLabel.textContent = 'Weak password. Must contain;';
        } else if (metCount === 2) {
            strengthBar.classList.add('is-medium');
            strengthLabel.textContent = 'Medium password. Must contain;';
        } else {
            strengthBar.classList.add('is-strong');
            strengthLabel.textContent = 'Strong password.';
        }
    }

    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function () {
            evaluatePassword(newPasswordInput.value);
        });
    }

    // ---- Clear confirm-password field ----
    if (clearBtn && confirmPasswordInput) {
        clearBtn.addEventListener('click', function () {
            confirmPasswordInput.value = '';
            confirmPasswordInput.focus();
        });
    }

    // ---- Submit via AJAX ----
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        alertBox.style.display = 'none';

        var newPassword = form.querySelector('[name="new_password"]').value;
        var confirmPassword = form.querySelector('[name="new_password_confirmation"]').value;

        if (newPassword !== confirmPassword) {
            showAlert('New password and confirmation do not match.', 'error');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Updating...';

        var formData = new FormData(form);

        fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
        .then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            });
        })
        .then(function (result) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Apply Changes';

            if (result.ok && result.data && result.data.success) {
                form.reset();
                evaluatePassword('');

                // Update "Last changed" text using the EXACT string the
                // server just returned — no client-side date guessing,
                // no page refresh needed. We look this up fresh (not a
                // cached reference) since the Account Settings modal was
                // already closed/reopened by Bootstrap by this point.
                var lastChangedEl = document.getElementById('nxlPasswordLastChanged');
                if (lastChangedEl && result.data.password_changed_at_display) {
                    lastChangedEl.textContent = 'Last changed: ' + result.data.password_changed_at_display;
                }

                // Close the Change Password modal, then show the success modal.
                var changeModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                changeModalInstance.hide();

                modalEl.addEventListener('hidden.bs.modal', function onHidden() {
                    modalEl.removeEventListener('hidden.bs.modal', onHidden);
                    bootstrap.Modal.getOrCreateInstance(successModalEl).show();
                });

                return;
            }

            var message = 'Something went wrong. Please try again.';
            if (result.data) {
                if (result.data.errors) {
                    var firstField = Object.keys(result.data.errors)[0];
                    message = result.data.errors[firstField][0];
                } else if (result.data.message) {
                    message = result.data.message;
                }
            }
            showAlert(message, 'error');
        })
        .catch(function () {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Apply Changes';
            showAlert('Network error. Please check your connection and try again.', 'error');
        });
    });

    // ---- Show/hide password toggle ----
    form.addEventListener('click', function (e) {
        var btn = e.target.closest('.nxl-password-toggle-btn');
        if (!btn) return;

        var input = btn.previousElementSibling;
        if (!input || input.tagName !== 'INPUT') return;

        var icon = btn.querySelector('i');
        var isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        icon.className = isHidden ? 'feather-eye-off' : 'feather-eye';
        btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });

    // ---- Reset form + strength bar + password visibility tuwing nagsasara ang modal ----
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            form.reset();
            alertBox.style.display = 'none';
            evaluatePassword('');

            form.querySelectorAll('.nxl-password-field-wrap input').forEach(function (input) {
                input.type = 'password';
            });
            form.querySelectorAll('.nxl-password-toggle-btn i').forEach(function (icon) {
                icon.className = 'feather-eye';
            });
        });
    }
});