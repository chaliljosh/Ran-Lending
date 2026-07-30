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
    const confirmBtn = document.getElementById('decisionConfirmBtn');

    if (action === 'approved') {
        headerTitle.textContent = 'Approve loan application';
        iconWrap.className = 'modal-icon-circle icon-success';
        icon.className = 'feather-check';
        text.textContent = 'Are you sure you want to approve this loan application?';
        confirmBtn.className = 'btn btn-modal-primary btn-approve';
        confirmBtn.textContent = 'Approve';
    } else {
        headerTitle.textContent = 'Reject loan application';
        iconWrap.className = 'modal-icon-circle icon-danger';
        icon.className = 'feather-x';
        text.textContent = 'Are you sure you want to reject this loan application?';
        confirmBtn.className = 'btn btn-modal-primary btn-reject';
        confirmBtn.textContent = 'Reject';
    }
}

function decisionBackToStep1() {
    document.getElementById('decisionStep1').style.display = 'block';
    document.getElementById('decisionStep2').style.display = 'none';
    document.getElementById('decisionFooterStep1').style.display = 'flex';
    document.getElementById('decisionFooterStep2').style.display = 'none';
    document.getElementById('decisionActionInput').value = '';
    document.getElementById('decisionModalLabel').textContent = 'Make a decision';
}

document.addEventListener('DOMContentLoaded', function () {
    const decisionModalEl = document.getElementById('decisionModal');
    if (decisionModalEl) {
        decisionModalEl.addEventListener('hidden.bs.modal', decisionBackToStep1);
    }

    const successModalEl = document.getElementById('successModal');
    if (successModalEl) {
        const successModal = new bootstrap.Modal(successModalEl, { backdrop: 'static' });
        successModal.show();
        setTimeout(() => successModal.hide(), 2000);
    }
});