// public/functions/loan-applications.js
// Used by: resources/views/admin/loan-applications.blade.php

function showLoanTab(tab, btn) {
    document.getElementById('loantab-pending').style.display  = tab === 'pending'  ? '' : 'none';
    document.getElementById('loantab-reviewed').style.display = tab === 'reviewed' ? '' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

let loanCardPendingForm = null;

function loanCardConfirmAction(btn, action) {
    loanCardPendingForm = btn.closest('form.decision-card-form');

    const icon = document.getElementById('loanCardConfirmIcon');
    const text = document.getElementById('loanCardConfirmText');
    const confirmBtn = document.getElementById('loanCardConfirmBtn');

    if (action === 'approved') {
        icon.className = 'feather-check-circle text-success';
        text.textContent = 'Approve this application?';
        confirmBtn.className = 'btn btn-success flex-fill';
        confirmBtn.innerHTML = ' Approve Application';
    } else {
        icon.className = 'feather-x-circle text-danger';
        text.textContent = 'Reject this application?';
        confirmBtn.className = 'btn btn-danger flex-fill';
        confirmBtn.innerHTML = ' Reject Application';
    }

    confirmBtn.onclick = function () {
        submitLoanDecision(loanCardPendingForm, action);
    };

    const modal = new bootstrap.Modal(document.getElementById('loanCardConfirmModal'));
    modal.show();
}

function submitLoanDecision(form, action) {
    if (!form) return;

    let actionInput = form.querySelector('input[name="action"]');
    if (!actionInput) {
        actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        form.appendChild(actionInput);
    }
    actionInput.value = action;

    const confirmModalEl = document.getElementById('loanCardConfirmModal');
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