// public/functions/assigned-payment-method.js
// Used by: resources/views/admin/partials/assigned-payment-method.blade.php
// (loaded from admin/ongoing-loan-detail.blade.php)
//
// Flow: Assign / Change button -> confirmation modal -> loader -> success modal.
// The logo dropdown itself lives in payment-account-dropdown.js.

(function () {
    const panel = document.getElementById('pmAssignPanel');
    const select = document.getElementById('pmAssignSelect');
    const btn = document.getElementById('pmAssignBtn');
    if (!panel || !select || !btn) return;

    const $ = (id) => document.getElementById(id);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const feedback = $('pmAssignFeedback');

    const confirmEl = $('pmAssignConfirmModal');
    const successEl = $('pmAssignSuccessModal');
    if (!confirmEl || !successEl || typeof bootstrap === 'undefined') return;

    const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmEl);
    const successModal = bootstrap.Modal.getOrCreateInstance(successEl);
    const confirmBtn = $('pmAssignConfirmBtn');
    const confirmError = $('pmAssignConfirmError');

    let currentId = panel.dataset.currentId || '';

    // ---------- helpers ----------
    // Everything is written with textContent, so account data can never inject HTML.
    function setText(id, text) {
        const el = $(id);
        if (el) el.textContent = text || '';
    }

    function setShown(id, shown) {
        const el = $(id);
        if (el) el.style.display = shown ? '' : 'none';
    }

    function setFeedback(message, ok) {
        if (!feedback) return;
        feedback.textContent = message || '';
        feedback.className = 'pm-assign-feedback' + (message ? (ok ? ' is-ok' : ' is-err') : '');
    }

    function refreshButton() {
        // Only enabled when the admin picked something different from what is assigned.
        btn.disabled = select.value === '' || select.value === currentId;
    }

    const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

    // Details of the account picked in the dropdown (from the custom dropdown, or the select as fallback)
    function selectedInfo() {
        const li = document.querySelector('#pmDdMenu .pm-dd-option[data-value="' + select.value + '"]');
        if (li && li.dataset.provider) {
            const logo = li.querySelector('.pm-logo');
            return {
                provider: li.dataset.provider,
                holder: li.dataset.holder || '',
                number: li.dataset.number || '',
                logoHtml: logo ? logo.innerHTML : '',
            };
        }
        const opt = select.options[select.selectedIndex];
        const parts = (opt ? opt.text : '').split(' \u2014 ');
        return { provider: parts[0] || '', holder: parts.slice(1).join(' \u2014 '), number: '', logoHtml: '' };
    }

    function setConfirmBusy(busy) {
        confirmBtn.disabled = busy;
        confirmBtn.innerHTML = busy ? '<span class="pm-spinner"></span>Assigning...' : 'Confirm';
        confirmEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (el) { el.disabled = busy; });
    }

    // Updates the "assigned" box on the left (the logo follows by itself, see payment-account-dropdown.js)
    function showMethod(m) {
        setText('pmdProvider', m.provider);
        setText('pmdName', m.account_name);
        setText('pmdNumber', m.account_number);

        setShown('pmdInactive', m.is_active === false);
        setShown('pmAssignedNone', false);
        setShown('pmAssignedBox', true);
        setShown('pmInactiveNote', false);
    }

    // ---------- 1) Assign / Change -> confirmation ----------
    select.addEventListener('change', function () {
        setFeedback('');
        refreshButton();
    });

    btn.addEventListener('click', function () {
        if (select.value === '') return;
        const info = selectedInfo();
        const hasCurrent = !!currentId;

        setText('pmAssignConfirmTitle', hasCurrent ? 'Change payment account?' : 'Assign payment account?');
        setText('pmAssignConfirmText', hasCurrent
            ? 'The borrower will send payments for this loan to the account below instead of the current one.'
            : 'The borrower will be told to send payments for this loan to the account below.');
        setText('pmAssignConfirmProvider', info.provider);
        setText('pmAssignConfirmHolder', info.holder);
        setText('pmAssignConfirmNumber', info.number);

        const logoBox = $('pmAssignConfirmLogo');
        if (logoBox) logoBox.innerHTML = info.logoHtml;

        if (confirmError) { confirmError.style.display = 'none'; confirmError.textContent = ''; }
        setConfirmBusy(false);
        confirmModal.show();
    });

    // ---------- 2) Confirm -> request ----------
    async function sendAssignment() {
        const res = await fetch(panel.dataset.assignUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ payment_method_id: select.value }),
        });

        let data = {};
        try { data = await res.json(); } catch (e) { /* non-JSON response */ }

        if (!res.ok || !data.success) {
            const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(firstError || data.message || 'Failed to assign the payment method.');
        }
        return data;
    }

    confirmBtn.addEventListener('click', async function () {
        const wasAssigned = !!currentId;
        const info = selectedInfo();

        if (confirmError) { confirmError.style.display = 'none'; confirmError.textContent = ''; }
        setConfirmBusy(true);

        try {
            // keep the loader visible for at least 600ms so it never just flashes
            const [data] = await Promise.all([sendAssignment(), sleep(600)]);
            const m = data.method || {};

            currentId = String(m.id !== undefined && m.id !== null ? m.id : select.value);
            panel.dataset.currentId = currentId;

            showMethod({
                provider: m.provider || info.provider,
                account_name: m.account_name || info.holder,
                account_number: m.account_number || info.number,
                is_active: m.is_active !== false,
            });

            btn.textContent = 'Change Assignment';
            const label = panel.querySelector('label[for="pmAssignSelect"]');
            if (label) label.textContent = 'Change the account the borrower sends to';
            setFeedback('');
            refreshButton();

            // success modal
            setText('pmAssignSuccessTitle', wasAssigned ? 'Payment Account Changed!' : 'Payment Account Assigned!');
            setText('pmAssignSuccessText', 'The borrower will now send payments for this loan to this account.');
            setText('pmAssignSuccessProvider', m.provider || info.provider);
            setText('pmAssignSuccessHolder', m.account_name || info.holder);
            setText('pmAssignSuccessNumber', m.account_number || info.number);

            confirmEl.addEventListener('hidden.bs.modal', function () { successModal.show(); }, { once: true });
            confirmModal.hide();
        } catch (e) {
            setConfirmBusy(false);
            if (confirmError) {
                confirmError.textContent = e.message;
                confirmError.style.display = 'block';
            }
        }
    });

    refreshButton();
})();