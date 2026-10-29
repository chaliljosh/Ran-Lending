// public/functions/loan-application-detail.js
// Used by: resources/views/admin/loan-application-detail.blade.php

function decisionShowConfirm(action) {
    document.getElementById('decisionActionInput').value = action;

    document.getElementById('decisionStep1').style.display = 'none';
    document.getElementById('decisionStep2').style.display = 'block';
    document.getElementById('decisionFooterStep1').style.display = 'none';
    document.getElementById('decisionFooterStep2').style.display = 'flex';

    const headerTitle = document.getElementById('decisionModalLabel');
    const iconWrap = document.getElementById('decisionConfirmIconWrap');
    const icon = document.getElementById('decisionConfirmIcon');
    const text = document.getElementById('decisionConfirmText');
    const subtext = document.getElementById('decisionConfirmSubtext');
    const confirmBtn = document.getElementById('decisionConfirmBtn');

    if (action === 'approved') {
        headerTitle.textContent = 'Approve loan application';
        iconWrap.className = 'modal-icon-circle icon-success';
        icon.className = 'feather-check';
        text.textContent = 'Approve loan application?';
        subtext.textContent = 'Are you sure you want to approve this loan application? This action cannot be undone.';
        confirmBtn.className = 'btn-decision btn-decision-approve'; // green #477553
        confirmBtn.textContent = 'Approve';
    } else {
        headerTitle.textContent = 'Reject loan application';
        iconWrap.className = 'modal-icon-circle icon-danger';
        icon.className = 'feather-x';
        text.textContent = 'Reject loan application?';
        subtext.textContent = 'Are you sure you want to reject this loan application? This action cannot be undone.';
        confirmBtn.className = 'btn-decision btn-decision-black';
        confirmBtn.textContent = 'Reject';
    }
}

function decisionBackToStep1() {
    document.getElementById('decisionStep1').style.display = 'block';
    document.getElementById('decisionStep2').style.display = 'none';
    document.getElementById('decisionFooterStep1').style.display = 'flex';
    document.getElementById('decisionFooterStep2').style.display = 'none';
    document.getElementById('decisionActionInput').value = '';
    document.getElementById('decisionModalLabel').textContent = 'Review Loan Application';

    // Reset confirm button back to its default look
    const confirmBtn = document.getElementById('decisionConfirmBtn');
    if (confirmBtn) {
        confirmBtn.className = 'btn-decision btn-decision-black';
    }
}

/* ============================================================
   BORROWER INFO EDITING
   Per-card Edit / Cancel / Save. Save opens a modal that asks
   for a reason, then POSTs the changed fields. The server saves,
   logs and emails the borrower. After that we show a success
   modal, and when the admin closes it we reload the page so the
   yellow "edited" highlight comes from the server.
   ============================================================ */
function initBorrowerInfoEdit() {
    const modalEl = document.getElementById('editInfoModal');
    const cards = document.querySelectorAll('[data-edit-section]');
    if (!modalEl || !cards.length) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const saveUrl = modalEl.dataset.saveUrl;
    const titleEl = document.getElementById('editInfoTitle');
    const changesBox = document.getElementById('editInfoChanges');
    const reasonInput = document.getElementById('editInfoReason');
    const reasonCount = document.getElementById('editInfoReasonCount');
    const errorEl = document.getElementById('editInfoError');
    const confirmBtn = document.getElementById('editInfoConfirmBtn');
    const confirmLabel = confirmBtn.innerHTML;

    // Success modal (nasa Blade, sa baba ng #editInfoModal)
    const successEl = document.getElementById('editInfoSuccessModal');
    const successMsgEl = document.getElementById('editInfoSuccessMessage');

    let activeCard = null;
    let pendingChanges = [];
    let successMessage = null; // may laman lang kapag successful ang save

    function showModal() { bootstrap.Modal.getOrCreateInstance(modalEl).show(); }

    function showError(msg) {
        errorEl.textContent = msg || '';
        errorEl.style.display = msg ? '' : 'none';
    }

    function setLoading(loading) {
        confirmBtn.disabled = loading;
        confirmBtn.innerHTML = loading ? '<i class="feather-loader spin-icon"></i>' : confirmLabel;
    }

    function firstMessage(data, fallback) {
        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        return firstError || data.message || fallback;
    }

    function showSuccess(message) {
        // Kung wala ang success modal sa page, reload na lang
        if (!successEl || !successMsgEl) {
            window.location.reload();
            return;
        }

        successMsgEl.textContent = message || 'The changes were saved.';
        bootstrap.Modal.getOrCreateInstance(successEl).show();
    }

    function todayYmd() {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 10);
    }

    function prettyDate(ymd) {
        const p = ymd.split('-').map(Number);
        if (p.length !== 3 || p.some(isNaN)) return ymd;
        return new Date(p[0], p[1] - 1, p[2]).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function optionsOf(field) {
        let opts = {};
        try { opts = JSON.parse(field.dataset.options || '{}'); } catch (e) {}
        if (Array.isArray(opts)) {
            opts = Object.fromEntries(opts.map(function (v) { return [v, v]; }));
        }
        return opts;
    }

    function displayFor(field, value) {
        if (value === '') return '—';
        const type = field.dataset.type;
        if (type === 'date') return prettyDate(value);
        if (type === 'select') return optionsOf(field)[value] || value;
        return value;
    }

    function buildInput(field) {
        const type = field.dataset.type || 'text';
        const raw = field.dataset.raw || '';
        let input;

        if (type === 'select') {
            input = document.createElement('select');
            input.className = 'form-select ld-edit-input';

            const opts = optionsOf(field);
            const blank = document.createElement('option');
            blank.value = '';
            blank.textContent = 'Select...';
            input.appendChild(blank);

            Object.keys(opts).forEach(function (value) {
                const o = document.createElement('option');
                o.value = value;
                o.textContent = opts[value];
                input.appendChild(o);
            });

            // Keep a stored value that is not in the list, so nothing is lost
            if (raw && !(raw in opts)) {
                const o = document.createElement('option');
                o.value = raw;
                o.textContent = raw;
                input.appendChild(o);
            }
        } else {
            input = document.createElement('input');
            input.type = type;
            input.className = 'form-control ld-edit-input';
            if (field.dataset.max) input.maxLength = Number(field.dataset.max);
            if (type === 'date') input.max = todayYmd();
        }

        input.value = raw;
        return input;
    }

    function startEdit(card) {
        cards.forEach(function (c) {
            if (c !== card && c.classList.contains('is-editing')) cancelEdit(c);
        });

        card.querySelectorAll('.ld-field[data-edit]').forEach(function (field) {
            if (field.querySelector('.ld-edit-input')) return;

            const input = buildInput(field);
            const label = field.querySelector('.info-label');
            const value = field.querySelector('.info-value');
            if (value) value.style.display = 'none';

            if (label) {
                label.insertAdjacentElement('afterend', input);
            } else {
                field.appendChild(input);
            }
        });

        card.classList.add('is-editing');

        const firstInput = card.querySelector('.ld-edit-input');
        if (firstInput) firstInput.focus();
    }

    function cancelEdit(card) {
        card.querySelectorAll('.ld-edit-input').forEach(function (i) { i.remove(); });
        card.querySelectorAll('.ld-field .info-value').forEach(function (v) { v.style.display = ''; });
        card.classList.remove('is-editing');
    }

    function collectChanges(card) {
        const changes = [];
        let invalid = null;

        card.querySelectorAll('.ld-field[data-edit]').forEach(function (field) {
            const input = field.querySelector('.ld-edit-input');
            if (!input) return;

            input.classList.remove('is-invalid');

            const oldVal = (field.dataset.raw || '').trim();
            const newVal = input.value.trim();

            if (field.dataset.required === '1' && newVal === '') {
                input.classList.add('is-invalid');
                invalid = invalid || input;
                return;
            }
            if (newVal === oldVal) return;

            const labelEl = field.querySelector('.info-label');
            changes.push({
                key: field.dataset.edit,
                label: (labelEl ? labelEl.textContent : field.dataset.edit).trim(),
                newVal: newVal,
                oldDisplay: displayFor(field, oldVal),
                newDisplay: displayFor(field, newVal),
            });
        });

        return { changes: changes, invalid: invalid };
    }

    function renderChanges(changes) {
        changesBox.innerHTML = '';
        changes.forEach(function (c) {
            const row = document.createElement('div');
            row.className = 'ld-change-row';

            const label = document.createElement('span');
            label.className = 'ld-change-label';
            label.textContent = c.label;

            const oldEl = document.createElement('span');
            oldEl.className = 'ld-change-old';
            oldEl.textContent = c.oldDisplay;

            const arrow = document.createElement('span');
            arrow.className = 'ld-change-arrow';
            arrow.textContent = '→';

            const newEl = document.createElement('span');
            newEl.className = 'ld-change-new';
            newEl.textContent = c.newDisplay;

            row.appendChild(label);
            row.appendChild(oldEl);
            row.appendChild(arrow);
            row.appendChild(newEl);
            changesBox.appendChild(row);
        });
    }

    function onSave(card) {
        const result = collectChanges(card);

        if (result.invalid) {
            result.invalid.focus();
            return;
        }
        if (!result.changes.length) {
            cancelEdit(card); // nothing changed
            return;
        }

        activeCard = card;
        pendingChanges = result.changes;

        const sectionName = card.querySelector('.section-title span');
        titleEl.textContent = 'Save changes to ' + (sectionName ? sectionName.textContent : 'this section');
        renderChanges(result.changes);
        reasonInput.value = '';
        reasonCount.textContent = '0';
        showError('');
        setLoading(false);
        showModal();
    }

    // Edit / Cancel / Save buttons (event delegation)
    document.addEventListener('click', function (e) {
        const card = e.target.closest('[data-edit-section]');
        if (!card) return;

        if (e.target.closest('.js-edit-start')) startEdit(card);
        else if (e.target.closest('.js-edit-cancel')) cancelEdit(card);
        else if (e.target.closest('.js-edit-save')) onSave(card);
    });

    reasonInput.addEventListener('input', function () {
        reasonCount.textContent = reasonInput.value.length;
    });

    confirmBtn.addEventListener('click', function () {
        if (!activeCard || !pendingChanges.length) return;

        const reason = reasonInput.value.trim();
        if (reason.length < 5) {
            showError('Please enter a reason (at least 5 characters).');
            return;
        }

        showError('');
        setLoading(true);

        const fields = {};
        pendingChanges.forEach(function (c) { fields[c.key] = c.newVal; });

        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                section: activeCard.dataset.editSection,
                fields: fields,
                reason: reason,
            }),
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (res) {
                if (!res.ok || !res.data.success) {
                    showError(firstMessage(res.data, 'Failed to save the changes.'));
                    setLoading(false);
                    return;
                }

                // Isara muna ang reason modal. Kapag tapos na itong magsara
                // (hidden.bs.modal sa baba), saka ipapakita ang success modal.
                successMessage = res.data.message || 'The changes were saved.';
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            })
            .catch(function (err) {
                console.error('[loan-application-detail.js] save borrower info error:', err);
                showError('Something went wrong. Please try again.');
                setLoading(false);
            });
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        setLoading(false);
        showError('');

        // Successful ang save: ipakita na ang success modal
        if (successMessage !== null) {
            const message = successMessage;
            successMessage = null;
            showSuccess(message);
        }
    });

    // Kapag isinara ang success modal (Done, X, o click sa labas), i-reload ang
    // page para lumabas ang dilaw na highlight galing sa server.
    if (successEl) {
        successEl.addEventListener('hidden.bs.modal', function () {
            window.location.reload();
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const decisionModalEl = document.getElementById('decisionModal');
    if (decisionModalEl) {
        decisionModalEl.addEventListener('hidden.bs.modal', decisionBackToStep1);
    }

    // Live character counter for the remarks textarea.
    const remarksInput = document.getElementById('decisionRemarks');
    const remarksCount = document.getElementById('decisionRemarksCount');
    if (remarksInput && remarksCount) {
        remarksInput.addEventListener('input', function () {
            remarksCount.textContent = remarksInput.value.length;
        });
    }

    // ---- Loading state on submit: icon-only spinner, no text ----
    // Normal form submit (not AJAX) — the button stays in this
    // loading state until the page navigates away after the
    // server responds/redirects.
    const decisionForm = document.getElementById('decisionForm');
    if (decisionForm) {
        decisionForm.addEventListener('submit', function () {
            const confirmBtn = document.getElementById('decisionConfirmBtn');
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="feather-loader spin-icon"></i>';
            }
        });
    }

    // ---- Borrower info editing ----
    initBorrowerInfoEdit();
});