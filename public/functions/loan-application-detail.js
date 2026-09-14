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
        confirmBtn.className = 'btn-decision btn-decision-black';
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
});