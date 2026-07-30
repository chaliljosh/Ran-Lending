// public/functions/borrower-verification.js
// Used by: resources/views/admin/borrower-verification.blade.php

function showTab(tab, btn) {
    document.getElementById('tab-pending').style.display  = tab === 'pending'  ? 'block' : 'none';
    document.getElementById('tab-reviewed').style.display = tab === 'reviewed' ? 'block' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// Approve/Reject confirmation for each pending card (replaces native confirm())
let cardPendingForm = null;

function cardConfirmAction(btn, action) {
    cardPendingForm = btn.closest('form.decision-card-form');

    const icon = document.getElementById('cardConfirmIcon');
    const text = document.getElementById('cardConfirmText');
    const confirmBtn = document.getElementById('cardConfirmBtn');

    if (action === 'approved') {
        icon.className = 'feather-check-circle text-success';
        text.textContent = 'Approve this borrower?';
        confirmBtn.className = 'btn btn-success flex-fill';
        confirmBtn.innerHTML = ' Approve';
    } else {
        icon.className = 'feather-x-circle text-danger';
        text.textContent = 'Reject this borrower?';
        confirmBtn.className = 'btn btn-danger flex-fill';
        confirmBtn.innerHTML = 'Reject';
    }

    confirmBtn.onclick = function () {
        submitDecision(cardPendingForm, action);
    };

    const modal = new bootstrap.Modal(document.getElementById('cardConfirmModal'));
    modal.show();
}

// Submits the decision via fetch (no page reload), waits for BOTH the confirm
// modal to fully close AND the request to finish before opening the success
// modal — this is what fixes the "blurry / stacked backdrop" bug.
function submitDecision(form, action) {
    if (!form) return;

    let actionInput = form.querySelector('input[name="action"]');
    if (!actionInput) {
        actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        form.appendChild(actionInput);
    }
    actionInput.value = action;

    const confirmModalEl = document.getElementById('cardConfirmModal');
    const confirmModalInstance = bootstrap.Modal.getInstance(confirmModalEl);

    form.classList.add('is-submitting');

    let modalHidden = false;
    let fetchResult = null;

    function tryShowSuccess() {
        if (!modalHidden || fetchResult === null) return;

        if (fetchResult.success) {
            const successModal = new bootstrap.Modal(document.getElementById('successModal'), { backdrop: 'static' });
            successModal.show();
            setTimeout(() => {
                successModal.hide();
                location.reload();
            }, 1500);
        } else {
            form.classList.remove('is-submitting');
            alert('Something went wrong. Please try again.');
        }
    }

    if (confirmModalEl) {
        confirmModalEl.addEventListener('hidden.bs.modal', function onHidden() {
            confirmModalEl.removeEventListener('hidden.bs.modal', onHidden);
            modalHidden = true;
            tryShowSuccess();
        }, { once: true });
    }

    confirmModalInstance?.hide();

    const formData = new FormData(form);

    // IMPORTANT: use getAttribute('action'), NOT form.action.
    // form.action gets overridden by any <input name="action"> inside the
    // form (browser quirk — named form elements shadow form properties),
    // which was causing "[object HTMLInputElement]" to be sent as the URL.
    fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData,
    })
    .then(res => res.json())
    .then(data => {
        fetchResult = { success: !!data.success };
        tryShowSuccess();
    })
    .catch(() => {
        form.classList.remove('is-submitting');
        alert('Something went wrong. Please check your connection and try again.');
    });
}