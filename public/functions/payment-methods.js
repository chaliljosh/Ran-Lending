// public/functions/payment-methods.js
// Used by: resources/views/admin/payment-methods.blade.php

(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const page = document.getElementById('pmPage');
    if (!page) return;

    const $ = (id) => document.getElementById(id);

    const formModal = bootstrap.Modal.getOrCreateInstance($('pmFormModal'));
    const toggleModal = bootstrap.Modal.getOrCreateInstance($('pmToggleModal'));
    const deleteModal = bootstrap.Modal.getOrCreateInstance($('pmDeleteModal'));
    const successModal = bootstrap.Modal.getOrCreateInstance($('pmSuccessModal'));

    const providerSelect = $('pmProvider');
    const providerOther = $('pmProviderOther');
    const numberInput = $('pmAccountNumber');
    const numberHint = $('pmNumberHint');

    // ---------- Bank / e-wallet logos ----------
    const logoBase = page.dataset.logoBase;
    const logoBox = $('pmProviderLogo');
    let logoMap = {};
    try { logoMap = JSON.parse(page.dataset.logoMap || '{}') || {}; } catch (e) { logoMap = {}; }

    const fields = {
        account_name: $('pmAccountName'),
    };

    let formUrl = null;
    let formIsEdit = false;
    let formActive = true;
    let toggleUrl = null;
    let deleteUrl = null;
    let toggleWasActive = false;
    let toggleLabel = '';
    let deleteLabel = '';

    // ---------- Account number rules per provider ----------
    const WALLETS = ['GCash', 'Maya'];
    const KNOWN = ['GCash', 'Maya', 'BPI', 'BDO', 'Metrobank', 'UnionBank', 'Landbank'];

    function rulesFor(provider) {
        if (WALLETS.indexOf(provider) !== -1) {
            return { min: 11, max: 11, prefix: '09', placeholder: '09171234567',
                     hint: provider + ' number: 11 digits, starts with 09.' };
        }
        if (provider === 'Other') {
            return { min: 6, max: 20, prefix: '', placeholder: 'Digits only',
                     hint: 'Digits only, 6 to 20 digits.' };
        }
        if (provider) {
            return { min: 10, max: 16, prefix: '', placeholder: 'Digits only',
                     hint: 'Digits only, 10 to 16 digits.' };
        }
        return null;
    }

    function currentProvider() {
        return providerSelect.value === 'Other' ? providerOther.value.trim() : providerSelect.value;
    }

    // Shows the logo of the selected provider (initials first, then the image once it loads)
    function updateLogo() {
        const name = currentProvider();
        logoBox.innerHTML = '';
        if (!name) {
            logoBox.innerHTML = '<i class="feather-credit-card"></i>';
            return;
        }

        const initials = document.createElement('span');
        initials.className = 'pm-logo-fallback';
        initials.textContent = name.slice(0, 2).toUpperCase();
        logoBox.appendChild(initials);

        const file = providerSelect.value !== 'Other' ? logoMap[providerSelect.value.toLowerCase()] : null;
        if (file) {
            const img = new Image();
            img.alt = name;
            img.onload = function () { logoBox.innerHTML = ''; logoBox.appendChild(img); };
            img.src = logoBase + '/' + file;
        }
    }

    function numberError() {
        const rules = rulesFor(providerSelect.value);
        if (!rules) return 'Select a bank / e-wallet first.';
        const n = numberInput.value;
        if (!n) return 'Enter the account number.';
        if (n.length < rules.min || n.length > rules.max) {
            return rules.min === rules.max
                ? 'Must be exactly ' + rules.min + ' digits (now ' + n.length + ').'
                : 'Must be ' + rules.min + ' to ' + rules.max + ' digits (now ' + n.length + ').';
        }
        if (rules.prefix && n.indexOf(rules.prefix) !== 0) {
            return 'Must start with ' + rules.prefix + '.';
        }
        return '';
    }

    function refreshNumberField(showErrors) {
        const rules = rulesFor(providerSelect.value);
        numberInput.disabled = !rules;
        numberInput.maxLength = rules ? rules.max : 20;
        numberInput.placeholder = rules ? rules.placeholder : 'Select a bank / e-wallet first';

        if (!rules) {
            numberHint.style.display = 'none';
            return;
        }

        const err = showErrors ? numberError() : '';
        numberHint.style.display = 'block';
        numberHint.textContent = err || rules.hint;
        numberHint.style.color = err ? '#b42318' : '';
    }

    providerSelect.addEventListener('change', function () {
        providerOther.style.display = providerSelect.value === 'Other' ? 'block' : 'none';
        if (providerSelect.value !== 'Other') providerOther.value = '';
        // trim digits that exceed the new limit
        const rules = rulesFor(providerSelect.value);
        if (rules && numberInput.value.length > rules.max) {
            numberInput.value = numberInput.value.slice(0, rules.max);
        }
        refreshNumberField(numberInput.value.length > 0);
        updateLogo();
    });

    providerOther.addEventListener('input', updateLogo);

    numberInput.addEventListener('input', function () {
        numberInput.value = numberInput.value.replace(/\D/g, '');
        refreshNumberField(true);
    });

    // ---------- helpers ----------
    async function post(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body || {}),
        });

        let data = {};
        try { data = await res.json(); } catch (e) { /* non-JSON response */ }

        if (!res.ok) {
            const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(firstError || data.message || 'Something went wrong. Please try again.');
        }
        return data;
    }

    function showError(el, message) {
        el.textContent = message;
        el.style.display = 'block';
    }

    function hideError(el) {
        el.style.display = 'none';
        el.textContent = '';
    }

    function setBusy(btn, busy, idleText, busyText) {
        btn.disabled = busy;
        btn.innerHTML = busy
            ? '<span class="pm-spinner"></span>' + (busyText || 'Saving...')
            : idleText;
    }

    const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

    // Re-fetches the page in the background and swaps only the accounts panel (no full reload).
    async function refreshList() {
        try {
            const res = await fetch(window.location.href, {
                headers: { 'Accept': 'text/html' },
                cache: 'no-store',
            });
            if (!res.ok) throw new Error('refresh failed');

            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const newPanel = doc.querySelector('#pmPage .pm-panel');
            const oldPanel = page.querySelector('.pm-panel');
            if (!newPanel || !oldPanel) throw new Error('panel missing');

            oldPanel.replaceWith(newPanel);
            bindEdit();
            bindToggle();
            bindDelete();
        } catch (e) {
            window.location.reload(); // safe fallback
        }
    }

    // Runs the request but keeps the loader visible for at least 600ms so it never just flashes.
    async function postWithLoader(url, body) {
        const [data] = await Promise.all([post(url, body), sleep(600)]);
        return data;
    }

    function lockForm(locked) {
        $('pmFormCancelBtn').disabled = locked;
        $('pmFormCloseBtn').disabled = locked;
        [providerSelect, providerOther, numberInput, fields.account_name].forEach(function (el) {
            if (locked) { el.dataset.wasDisabled = el.disabled ? '1' : '0'; el.disabled = true; }
            else { el.disabled = el.dataset.wasDisabled === '1'; }
        });
    }

    // Close the current modal, then open the success modal with the account details.
    // opts: { title, text, provider, holder, status: 'Active' | 'Inactive' | 'Deleted' }
    function showSuccess(fromModalEl, fromModal, opts) {
        $('pmSuccessTitle').textContent = opts.title;
        $('pmSuccessText').textContent = opts.text;
        $('pmSuccessProvider').textContent = opts.provider;
        $('pmSuccessHolder').textContent = opts.holder;

        const pill = $('pmSuccessStatus');
        pill.textContent = opts.status;
        pill.className = 'pm-pill ' + (opts.status === 'Active' ? 'is-active'
            : opts.status === 'Deleted' ? 'is-deleted' : 'is-inactive');

        fromModalEl.addEventListener('hidden.bs.modal', function () { successModal.show(); }, { once: true });
        fromModal.hide();
    }


    // ---------- Add / Edit ----------
    function setProvider(value) {
        const v = (value || '').trim();
        const match = KNOWN.find((k) => k.toLowerCase() === v.toLowerCase());
        if (match) {
            providerSelect.value = match;
            providerOther.value = '';
            providerOther.style.display = 'none';
        } else if (v) {
            providerSelect.value = 'Other';
            providerOther.value = v;
            providerOther.style.display = 'block';
        } else {
            providerSelect.value = '';
            providerOther.value = '';
            providerOther.style.display = 'none';
        }
    }

    function openForm({ title, url, values }) {
        $('pmFormTitle').textContent = title;
        formUrl = url;
        formIsEdit = !!values;
        formActive = !(values && values.active === false);

        Object.keys(fields).forEach((key) => {
            fields[key].value = (values && values[key]) || '';
        });
        setProvider(values && values.provider);
        updateLogo();
        numberInput.value = ((values && values.account_number) || '').replace(/\D/g, '');
        refreshNumberField(false);

        hideError($('pmFormError'));
        formModal.show();
    }

    $('pmAddBtn').addEventListener('click', function () {
        openForm({ title: 'Add Payment Method', url: page.dataset.storeUrl });
    });

    function bindEdit() {
    document.querySelectorAll('.js-pm-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openForm({
                title: 'Edit Payment Method',
                url: btn.dataset.updateUrl,
                values: {
                    provider: btn.dataset.provider,
                    account_name: btn.dataset.accountName,
                    account_number: btn.dataset.accountNumber,
                    active: !btn.closest('.pm-item').classList.contains('is-inactive'),
                },
            });
        });
    });
    }

    $('pmFormSaveBtn').addEventListener('click', async function () {
        const saveBtn = this;
        const errorEl = $('pmFormError');
        hideError(errorEl);

        const body = {
            provider: currentProvider(),
            // The DB column is still named account_label; it just mirrors the account holder and is never shown.
            account_label: fields.account_name.value.trim(),
            account_name: fields.account_name.value.trim(),
            account_number: numberInput.value,
        };

        if (!body.provider) {
            showError(errorEl, providerSelect.value === 'Other'
                ? 'Type the bank or e-wallet name.'
                : 'Select a bank or e-wallet.');
            return;
        }
        if (!body.account_name) {
            showError(errorEl, 'Please enter the account holder name.');
            return;
        }
        const numErr = numberError();
        if (numErr) {
            refreshNumberField(true);
            showError(errorEl, 'Account number: ' + numErr);
            return;
        }

        setBusy(saveBtn, true, 'Save', 'Saving...');
        lockForm(true);
        try {
            await postWithLoader(formUrl, body);
            await refreshList();
            showSuccess($('pmFormModal'), formModal, {
                title: formIsEdit ? 'Account Updated Successfully!' : 'Account Added Successfully!',
                text: formIsEdit
                    ? 'The payment account details have been updated.'
                    : 'The payment account has been added and is now available for assignment to borrowers.',
                provider: body.provider,
                holder: body.account_name,
                status: formActive ? 'Active' : 'Inactive',
            });
        } catch (e) {
            lockForm(false);
            showError(errorEl, e.message);
            setBusy(saveBtn, false, 'Save');
        }
    });

    // ---------- Activate / Deactivate ----------
    function bindToggle() {
    document.querySelectorAll('.js-pm-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const isActive = btn.dataset.active === '1';
            const loans = parseInt(btn.dataset.loans || '0', 10);
            toggleUrl = btn.dataset.toggleUrl;

            $('pmToggleTitle').textContent = isActive ? 'Deactivate payment method?' : 'Activate payment method?';

            let text;
            if (isActive) {
                text = btn.dataset.label + ' will no longer appear in the assignment dropdown.';
                if (loans > 0) {
                    text += ' The ' + loans + ' loan' + (loans === 1 ? '' : 's')
                        + ' already using it will keep showing it, and past receipts are not affected.';
                }
            } else {
                text = btn.dataset.label + ' will be available to assign to loans again.';
            }
            $('pmToggleText').textContent = text;
            toggleWasActive = isActive;
            toggleLabel = btn.dataset.label;

            hideError($('pmToggleError'));
            toggleModal.show();
        });
    });
    }

    $('pmToggleConfirmBtn').addEventListener('click', async function () {
        const confirmBtn = this;
        hideError($('pmToggleError'));

        setBusy(confirmBtn, true, 'Confirm', 'Please wait...');
        try {
            await postWithLoader(toggleUrl);
            await refreshList();
            const parts = toggleLabel.split(' — ');
            showSuccess($('pmToggleModal'), toggleModal, {
                title: toggleWasActive ? 'Account Deactivated' : 'Account Activated!',
                text: toggleWasActive
                    ? 'The payment account will no longer appear in the assignment dropdown.'
                    : 'The payment account is now available for assignment to borrowers again.',
                provider: parts[0],
                holder: parts.slice(1).join(' — '),
                status: toggleWasActive ? 'Inactive' : 'Active',
            });
        } catch (e) {
            showError($('pmToggleError'), e.message);
            setBusy(confirmBtn, false, 'Confirm');
        }
    });

    // ---------- Delete (only shown for accounts no loan is using) ----------
    function bindDelete() {
    document.querySelectorAll('.js-pm-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            deleteUrl = btn.dataset.deleteUrl;
            deleteLabel = btn.dataset.label;
            const loans = parseInt(btn.dataset.loans || '0', 10);
            let text = btn.dataset.label + ' will be removed permanently.';
            if (loans > 0) {
                text += ' The ' + loans + ' loan' + (loans === 1 ? '' : 's')
                    + ' using it will have no payment account until you assign another one,'
                    + ' and the borrower will see a notice in the meantime.';
            }
            text += ' Past receipts keep their own copy of the account details.';
            $('pmDeleteText').textContent = text;
            hideError($('pmDeleteError'));
            deleteModal.show();
        });
    });
    }

    $('pmDeleteConfirmBtn').addEventListener('click', async function () {
        const confirmBtn = this;
        hideError($('pmDeleteError'));

        setBusy(confirmBtn, true, 'Delete', 'Deleting...');
        try {
            await postWithLoader(deleteUrl);
            await refreshList();
            const dParts = deleteLabel.split(' — ');
            showSuccess($('pmDeleteModal'), deleteModal, {
                title: 'Account Deleted',
                text: 'The payment account has been removed. Past receipts keep their own copy of the details.',
                provider: dParts[0],
                holder: dParts.slice(1).join(' — '),
                status: 'Deleted',
            });
        } catch (e) {
            showError($('pmDeleteError'), e.message);
            setBusy(confirmBtn, false, 'Delete');
        }
    });
    bindEdit();
    bindToggle();
    bindDelete();
})();