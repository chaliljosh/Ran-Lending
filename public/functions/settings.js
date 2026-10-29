// Settings page — civil status/address (AJAX save), change email/phone (OTP),
// at change request para sa locked fields.
// Kailangan ng window.SETTINGS_CONFIG = { csrfToken, detailsUrl, sendOtpUrl, verifyOtpUrl, changeRequestUrl }

(function () {
    const cfg = window.SETTINGS_CONFIG || {};
    const $ = (id) => document.getElementById(id);

    // ---------- helpers ----------
    function toast(message) {
        const el = document.createElement('div');
        el.className = 'profile-toast';
        el.innerHTML = '<i class="feather-check-circle"></i>';
        el.appendChild(document.createTextNode(message));
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 2600);
    }

    async function post(url, body) {
        let fd = body;
        if (!(fd instanceof FormData)) {
            fd = new FormData();
            Object.keys(body || {}).forEach((k) => fd.append(k, body[k]));
        }
        fd.append('_token', cfg.csrfToken);

        let res;
        try {
            res = await fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd,
            });
        } catch (e) {
            return { success: false, message: 'Something went wrong. Please check your connection and try again.' };
        }

        let data = {};
        try { data = await res.json(); } catch (e) { /* hindi JSON */ }

        if (!res.ok) {
            data.success = false;
            if (!data.message) {
                data.message = res.status === 419 ? 'Your session expired. Please refresh the page.'
                    : res.status === 429 ? 'Too many attempts. Please wait a moment and try again.'
                    : 'Something went wrong. Please try again.';
            }
        }
        return data;
    }

    function showError(boxId, message) {
        const box = $(boxId);
        if (!box) return;
        box.textContent = message;
        box.classList.remove('d-none');
    }

    function hideError(boxId) {
        const box = $(boxId);
        if (box) box.classList.add('d-none');
    }

    function setBusy(btnId, textId, spinnerId, busy, busyText, idleText) {
        const btn = $(btnId);
        if (btn) btn.disabled = busy;
        if (textId && $(textId)) $(textId).textContent = busy ? busyText : idleText;
        if (spinnerId && $(spinnerId)) $(spinnerId).classList.toggle('d-none', !busy);
    }

    function modalFor(id) {
        return window.bootstrap && window.bootstrap.Modal
            ? window.bootstrap.Modal.getOrCreateInstance($(id))
            : null;
    }

    // ---------- 1) Civil status + address ----------
    const detailsForm = $('settingsDetailsForm');
    if (detailsForm) {
        detailsForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            hideError('detailsError');

            const btn = $('detailsSaveBtn');
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="save-pulse"></span>Saving...';

            const data = await post(cfg.detailsUrl, {
                civilStatus: $('civilStatus').value,
                province: $('province').value,
                city: $('city').value,
                barangay: $('barangay').value,
                zipcode: $('zipcode').value,
                homeAddress: $('homeAddress').value,
            });

            btn.disabled = false;
            btn.innerHTML = original;

            if (data.success) {
                toast(data.message || 'Changes saved successfully.');
            } else {
                showError('detailsError', data.message || 'Something went wrong. Please try again.');
            }
        });
    }

    // ---------- 2) Change email / phone (OTP) ----------
    const FIELDS = {
        email: {
            title: 'Change email',
            label: 'New email address',
            placeholder: 'name@example.com',
            inputmode: 'email',
            hint: 'We will send a 6-digit code to the new email address to confirm it.',
        },
        phone: {
            title: 'Change cellphone number',
            label: 'New cellphone number',
            placeholder: '+63 9XX XXX XXXX',
            inputmode: 'tel',
            hint: 'We will send a 6-digit code to your registered email to confirm the change.',
        },
    };

    const cc = { field: null, step: 1, value: '' };

    function ccSetStep(step) {
        cc.step = step;
        $('ccStep1').classList.toggle('d-none', step !== 1);
        $('ccStep2').classList.toggle('d-none', step !== 2);
        $('ccPrimaryText').textContent = step === 1 ? 'Send code' : 'Verify';
        hideError('ccError');
        if (step === 2) $('ccOtp').focus();
    }

    function ccOpen(field) {
        const f = FIELDS[field];
        if (!f) return;

        cc.field = field;
        cc.value = '';

        $('ccTitle').textContent = f.title;
        $('ccLabel').textContent = f.label;
        $('ccHint').textContent = f.hint;
        $('ccValue').value = '';
        $('ccValue').placeholder = f.placeholder;
        $('ccValue').setAttribute('inputmode', f.inputmode);
        $('ccOtp').value = '';
        ccSetStep(1);

        const m = modalFor('contactChangeModal');
        if (m) m.show();
    }

    // `overrideValue` ay para sa Resend (step 2): ang huling value na ipinadala.
    async function ccSendCode(overrideValue) {
        const value = (typeof overrideValue === 'string' ? overrideValue : $('ccValue').value).trim();
        if (!value) {
            showError('ccError', 'Please enter the new ' + (cc.field === 'email' ? 'email address.' : 'cellphone number.'));
            return false;
        }

        hideError('ccError');
        setBusy('ccPrimaryBtn', 'ccPrimaryText', 'ccSpinner', true, 'Sending...', 'Send code');

        const data = await post(cfg.sendOtpUrl, { field: cc.field, value: value });

        setBusy('ccPrimaryBtn', 'ccPrimaryText', 'ccSpinner', false, '', cc.step === 1 ? 'Send code' : 'Verify');

        if (!data.success) {
            showError('ccError', data.message || 'Could not send the code.');
            return false;
        }

        cc.value = value;
        $('ccSentTo').textContent = 'We sent a 6-digit code to ' + data.sent_to + '. It expires in 10 minutes.';
        $('ccOtp').value = '';
        ccSetStep(2);
        return true;
    }

    async function ccVerify() {
        const otp = $('ccOtp').value.trim();
        if (!/^\d{6}$/.test(otp)) {
            showError('ccError', 'Please enter the 6-digit code.');
            return;
        }

        hideError('ccError');
        setBusy('ccPrimaryBtn', 'ccPrimaryText', 'ccSpinner', true, 'Verifying...', 'Verify');

        const data = await post(cfg.verifyOtpUrl, { otp: otp });

        setBusy('ccPrimaryBtn', 'ccPrimaryText', 'ccSpinner', false, '', 'Verify');

        if (!data.success) {
            showError('ccError', data.message || 'Invalid or expired code.');
            return;
        }

        const target = data.field === 'email' ? $('contactEmailValue') : $('contactPhoneValue');
        if (target) target.textContent = data.value;

        const m = modalFor('contactChangeModal');
        if (m) m.hide();
        toast(data.message || 'Updated.');
    }

    document.querySelectorAll('[data-contact-change]').forEach((btn) => {
        btn.addEventListener('click', () => ccOpen(btn.dataset.contactChange));
    });

    if ($('ccPrimaryBtn')) {
        $('ccPrimaryBtn').addEventListener('click', () => (cc.step === 1 ? ccSendCode() : ccVerify()));
    }

    if ($('ccResend')) {
        $('ccResend').addEventListener('click', async () => {
            const ok = await ccSendCode(cc.value);
            if (ok) toast('A new code was sent.');
        });
    }

    // Enter key sa loob ng modal
    ['ccValue', 'ccOtp'].forEach((id) => {
        if (!$(id)) return;
        $(id).addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('ccPrimaryBtn').click();
            }
        });
    });

    // ---------- 3) Request a change (locked fields) ----------
    const crForm = $('changeRequestForm');
    if (crForm) {
        const fieldSelect = $('crField');
        const textInput = $('crValue');
        const incomeSelect = $('crIncome');
        const genderSelect = $('crGender');

        function crSyncInput() {
            const f = fieldSelect.value;

            textInput.classList.toggle('d-none', f === 'monthly_income' || f === 'gender');
            incomeSelect.classList.toggle('d-none', f !== 'monthly_income');
            genderSelect.classList.toggle('d-none', f !== 'gender');

            textInput.type = f === 'birthday' ? 'date' : 'text';
            textInput.placeholder = f === 'name' ? 'Full name as it appears on your ID' : '';

            $('crValueLabel').textContent = {
                name: 'Correct full name',
                birthday: 'Correct birthday',
                gender: 'Correct gender',
                citizenship: 'Correct citizenship',
                monthly_income: 'Correct income range',
            }[f] || 'Correct value';
        }

        fieldSelect.addEventListener('change', crSyncInput);
        crSyncInput();

        // Piliin agad ang unang field na walang pending request.
        const firstFree = Array.from(fieldSelect.options).find((o) => !o.disabled);
        if (firstFree) {
            fieldSelect.value = firstFree.value;
            crSyncInput();
        }

        crForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            hideError('crError');

            const f = fieldSelect.value;
            const newValue = f === 'monthly_income' ? incomeSelect.value
                : f === 'gender' ? genderSelect.value
                : textInput.value.trim();

            const file = $('crDocument').files[0];

            if (!newValue) return showError('crError', 'Please enter the correct value.');
            if (!$('crReason').value.trim()) return showError('crError', 'Please tell us why you need this change.');
            if (!file) return showError('crError', 'Please attach a supporting document.');
            if (file.size > 5 * 1024 * 1024) return showError('crError', 'The document must be less than 5MB.');

            const fd = new FormData();
            fd.append('field', f);
            fd.append('new_value', newValue);
            fd.append('reason', $('crReason').value.trim());
            fd.append('document', file);

            setBusy('crSubmitBtn', 'crSubmitText', 'crSpinner', true, 'Submitting...', 'Submit request');
            const data = await post(cfg.changeRequestUrl, fd);
            setBusy('crSubmitBtn', 'crSubmitText', 'crSpinner', false, '', 'Submit request');

            if (!data.success) {
                return showError('crError', data.message || 'Could not submit your request.');
            }

            const m = modalFor('changeRequestModal');
            if (m) m.hide();
            toast(data.message || 'Request submitted.');
            // I-refresh para lumabas ang "Pending" at ma-disable ang field na iyon.
            setTimeout(() => window.location.reload(), 1200);
        });
    }
})();