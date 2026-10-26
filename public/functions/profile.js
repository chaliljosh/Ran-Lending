// My Profile — per-section inline editing (AJAX save) + change password modal
// + Loan History modal.
// Expects window.PROFILE_CONFIG = { csrfToken, updateSectionUrl, changePasswordUrl } to be set inline before this file loads.

(function () {
    const config = window.PROFILE_CONFIG || {};

    // Snapshot ng original values, para maibalik pag Cancel.
    const originalValues = {};

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'profile-toast';
        toast.innerHTML = `<i class="feather-check-circle"></i>${message}`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2600);
    }

    window.toggleSectionEdit = function (section) {
        const fields = document.querySelectorAll(`.profile-editable[data-section="${section}"]`);
        fields.forEach(f => {
            originalValues[f.id] = f.value;
            f.disabled = false;
        });
        if (fields.length) fields[0].focus();

        document.getElementById(`editLink-${section}`).classList.add('d-none');
        document.getElementById(`sectionActions-${section}`).classList.remove('d-none');
    };

    window.cancelSectionEdit = function (section) {
        const fields = document.querySelectorAll(`.profile-editable[data-section="${section}"]`);
        fields.forEach(f => {
            if (originalValues[f.id] !== undefined) f.value = originalValues[f.id];
            f.disabled = true;
        });

        document.getElementById(`editLink-${section}`).classList.remove('d-none');
        document.getElementById(`sectionActions-${section}`).classList.add('d-none');
    };

    window.saveSection = function (section) {
        const saveBtn = document.getElementById(`saveBtn-${section}`);
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="save-pulse"></span>Saving...';

        const formData = new FormData();
        formData.append('section', section);
        formData.append('_token', config.csrfToken);

        document.querySelectorAll(`.profile-editable[data-section="${section}"]`).forEach(f => {
            formData.append(f.id, f.value);
        });

        fetch(config.updateSectionUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;

            if (data.success) {
                document.querySelectorAll(`.profile-editable[data-section="${section}"]`).forEach(f => { f.disabled = true; });
                document.getElementById(`editLink-${section}`).classList.remove('d-none');
                document.getElementById(`sectionActions-${section}`).classList.add('d-none');
                showToast('Changes saved successfully.');
            } else {
                alert(data.message || 'Something went wrong. Please try again.');
            }
        })
        .catch(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
            alert('Something went wrong. Please check your connection and try again.');
        });
    };

    window.cpToggle = function (inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'feather-eye-off';
        } else {
            input.type = 'password';
            icon.className = 'feather-eye';
        }
    };

    window.resetCpModal = function () {
        document.getElementById('cpCurrent').value = '';
        document.getElementById('cpNew').value = '';
        document.getElementById('cpConfirm').value = '';
        document.getElementById('cpError').classList.add('d-none');
        document.getElementById('cpSuccess').classList.add('d-none');
        document.getElementById('cpForm').classList.remove('d-none');
        document.getElementById('cpSaveBtn').classList.remove('d-none');
    };

    window.submitChangePassword = function () {
        const current = document.getElementById('cpCurrent').value;
        const newPass = document.getElementById('cpNew').value;
        const confirm = document.getElementById('cpConfirm').value;
        const errorBox = document.getElementById('cpError');

        errorBox.classList.add('d-none');

        if (!current || !newPass || !confirm) {
            errorBox.textContent = 'Please fill in all fields.';
            errorBox.classList.remove('d-none');
            return;
        }

        const saveBtn = document.getElementById('cpSaveBtn');
        const btnText = document.getElementById('cpBtnText');
        const spinner = document.getElementById('cpSpinner');
        saveBtn.disabled = true;
        btnText.textContent = 'Changing...';
        spinner.classList.remove('d-none');

        const formData = new FormData();
        formData.append('current_password', current);
        formData.append('new_password', newPass);
        formData.append('confirm_password', confirm);
        formData.append('_token', config.csrfToken);

        fetch(config.changePasswordUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            saveBtn.disabled = false;
            btnText.textContent = 'Change Password';
            spinner.classList.add('d-none');

            if (data.success) {
                document.getElementById('cpForm').classList.add('d-none');
                document.getElementById('cpSuccess').classList.remove('d-none');
                saveBtn.classList.add('d-none');
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'))?.hide();
                    resetCpModal();
                }, 1800);
            } else {
                errorBox.textContent = data.message || 'Something went wrong. Please try again.';
                errorBox.classList.remove('d-none');
            }
        })
        .catch(() => {
            saveBtn.disabled = false;
            btnText.textContent = 'Change Password';
            spinner.classList.add('d-none');
            errorBox.textContent = 'Something went wrong. Please check your connection and try again.';
            errorBox.classList.remove('d-none');
        });
    };
})();


// ══════════════════════════════════════════
// LOAN HISTORY — buksan ang modal pag na-click ang View o ang row
// (kailangan ng #profileLoanHistory at #ldLoanModal sa page)
// ══════════════════════════════════════════
(function () {
    const wrap = document.getElementById('profileLoanHistory');
    const modalEl = document.getElementById('ldLoanModal');
    if (!wrap || !modalEl) return;

    function setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text || '—';
    }

    function showIf(id, visible) {
        const el = document.getElementById(id);
        if (el) el.style.display = visible ? '' : 'none';
    }

    function openLoanModal(row) {
        const d = row.dataset;

        setText('ldLoanId', d.loanId);
        setText('ldLoanAmount', d.amount);
        setText('ldLoanTerm', d.term);
        setText('ldLoanApplied', d.applied);
        setText('ldLoanMonthly', d.monthly);
        setText('ldLoanTotal', d.total);

        showIf('ldLoanMonthlyWrap', !!d.monthly);
        showIf('ldLoanTotalWrap', !!d.total);
        showIf('ldLoanExtra', !!(d.monthly || d.total));

        const status = document.getElementById('ldLoanStatus');
        if (status) {
            status.className = 'bwl-ld-status ' + (d.statusClass || '');
            status.textContent = d.statusLabel || '';
        }

        const link = document.getElementById('ldLoanOpen');
        if (link) link.href = d.href || '#';

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if (d.href) {
            // Walang Bootstrap JS — buksan na lang ang page.
            window.location.href = d.href;
        }
    }

    wrap.addEventListener('click', function (e) {
        const row = e.target.closest('tr.bwl-ld-row-link');
        if (!row) return;

        // Huwag i-hijack ang click kung nagse-select ng text.
        const sel = window.getSelection();
        if (!e.target.closest('[data-ld-view-loan]') && sel && String(sel).length > 0) return;

        openLoanModal(row);
    });
})();