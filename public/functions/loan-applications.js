// public/functions/loan-applications.js
// Used by: resources/views/admin/loan-applications.blade.php

function showLoanTab(tab, btn) {
    document.getElementById('loantab-pending').style.display  = tab === 'pending'  ? '' : 'none';
    document.getElementById('loantab-reviewed').style.display = tab === 'reviewed' ? '' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function filterApplications(query) {
    query = query.trim().toLowerCase();
    document.querySelectorAll('.app-card[data-search]').forEach(card => {
        const match = card.dataset.search.includes(query);
        card.style.display = match ? '' : 'none';
    });
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

// Picks which result modal to show based on the application's status
// after this decision was recorded:
//   approved -> finalApprovalModal (all 3 admins approved)
//   rejected -> finalRejectionModal (all 3 admins rejected)
//   on_hold  -> onHoldModal (mixed decisions among admins)
//   pending  -> decisionSuccessModal (generic "recorded" message)
function resultModalIdFor(status) {
    if (status === 'approved') return 'finalApprovalModal';
    if (status === 'rejected') return 'finalRejectionModal';
    if (status === 'on_hold') return 'onHoldModal';
    return 'decisionSuccessModal';
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
            const resultModalId = resultModalIdFor(fetchResult.status);
            const resultModalEl = document.getElementById(resultModalId);
            const resultModal = new bootstrap.Modal(resultModalEl, { backdrop: 'static' });

            resultModalEl.addEventListener('hidden.bs.modal', function onResultHidden() {
                resultModalEl.removeEventListener('hidden.bs.modal', onResultHidden);
                location.reload();
            }, { once: true });

            resultModal.show();
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
        fetchResult = { success: !!data.success, status: data.status };
        tryShowSuccess();
    })
    .catch(() => {
        form.classList.remove('is-submitting');
        alert('Something went wrong. Please check your connection and try again.');
    });
}