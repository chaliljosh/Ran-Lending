// public/functions/verification-detail.js
// Used by: resources/views/admin/verification-detail.blade.php

function decisionShowConfirm(action) {
    document.getElementById('decisionActionInput').value = action;

    document.getElementById('decisionStep1').style.display = 'none';
    document.getElementById('decisionStep2').style.display = 'block';
    document.getElementById('decisionFooterStep1').style.display = 'none';
    document.getElementById('decisionFooterStep2').style.display = 'flex';

    const icon = document.getElementById('decisionConfirmIcon');
    const text = document.getElementById('decisionConfirmText');
    const confirmBtn = document.getElementById('decisionConfirmBtn');

    if (action === 'approved') {
        icon.className = 'feather-check-circle text-success';
        text.textContent = "Approve this borrower's personal details?";
        confirmBtn.className = 'btn btn-success flex-fill';
        confirmBtn.innerHTML = '<i class="feather-check me-1"></i> Yes, Approve';
    } else {
        icon.className = 'feather-x-circle text-danger';
        text.textContent = "Reject this borrower's personal details?";
        confirmBtn.className = 'btn btn-danger flex-fill';
        confirmBtn.innerHTML = '<i class="feather-x me-1"></i> Yes, Reject';
    }
}

function decisionBackToStep1() {
    document.getElementById('decisionStep1').style.display = 'block';
    document.getElementById('decisionStep2').style.display = 'none';
    document.getElementById('decisionFooterStep1').style.display = 'flex';
    document.getElementById('decisionFooterStep2').style.display = 'none';
    document.getElementById('decisionActionInput').value = '';
}

document.addEventListener('DOMContentLoaded', function () {
    // Reset back to step 1 every time the modal is opened or closed
    const decisionModalEl = document.getElementById('decisionModal');
    if (decisionModalEl) {
        decisionModalEl.addEventListener('hidden.bs.modal', decisionBackToStep1);
    }

    // Auto-show the success modal (if a decision was just saved/changed), auto-closes after 2s
    const successModalEl = document.getElementById('successModal');
    if (successModalEl) {
        const successModal = new bootstrap.Modal(successModalEl, { backdrop: 'static' });
        successModal.show();
        setTimeout(() => successModal.hide(), 2000);
    }
}); 