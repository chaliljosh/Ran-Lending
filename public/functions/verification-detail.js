// public/functions/verification-detail.js
// Used by: resources/views/admin/verification-detail.blade.php

/**
 * Review Borrower Verification modal is a single step (Cancel / Reject /
 * Approve). Clicking Reject or Approve hides that modal and shows a small
 * styled confirmation modal (not the native browser confirm()) — Cancel
 * there brings the review modal back with remarks intact; the confirm
 * button submits the form.
 */
let pendingDecisionAction = null;

function submitDecision(action) {
    pendingDecisionAction = action;

    const label = action === 'approved' ? 'approve' : 'reject';
    document.getElementById('confirmDecisionText').textContent =
        `Are you sure you want to ${label} this borrower's verification?`;

    const okBtn = document.getElementById('confirmDecisionOkBtn');
    okBtn.textContent = action === 'approved' ? 'Yes, Approve' : 'Yes, Reject';
    okBtn.className = 'btn-decision ' + (action === 'approved' ? 'btn-decision-approve' : 'btn-decision-reject');

    const decisionModalEl = document.getElementById('decisionModal');
    const decisionModal = bootstrap.Modal.getInstance(decisionModalEl);

    decisionModalEl.addEventListener('hidden.bs.modal', function onHidden() {
        decisionModalEl.removeEventListener('hidden.bs.modal', onHidden);
        const confirmModal = new bootstrap.Modal(document.getElementById('decisionConfirmModal'));
        confirmModal.show();
    }, { once: true });

    decisionModal.hide();
}

document.addEventListener('DOMContentLoaded', function () {
    const confirmOkBtn = document.getElementById('confirmDecisionOkBtn');
    if (confirmOkBtn) {
        confirmOkBtn.addEventListener('click', function () {
            document.getElementById('decisionActionInput').value = pendingDecisionAction;
            document.getElementById('decisionForm').submit();
        });
    }

    const confirmCancelBtn = document.getElementById('confirmDecisionCancelBtn');
    if (confirmCancelBtn) {
        confirmCancelBtn.addEventListener('click', function () {
            const confirmModalEl = document.getElementById('decisionConfirmModal');
            const confirmModal = bootstrap.Modal.getInstance(confirmModalEl);

            confirmModalEl.addEventListener('hidden.bs.modal', function onHidden() {
                confirmModalEl.removeEventListener('hidden.bs.modal', onHidden);
                const decisionModal = new bootstrap.Modal(document.getElementById('decisionModal'));
                decisionModal.show();
            }, { once: true });

            confirmModal.hide();
        });
    }
});

/* ============================================
   VALID ID VIEWER — zoom + rotate + fullscreen
============================================ */
let idViewerScale = 1;
let idViewerRotation = 0;

function openIdViewer(imageUrl) {
    const overlay = document.getElementById('idViewerOverlay');
    const img = document.getElementById('idViewerImage');
    img.src = imageUrl;
    idViewerScale = 1;
    idViewerRotation = 0;
    img.style.transform = 'scale(1) rotate(0deg)';
    overlay.classList.add('show');
}

function closeIdViewer() {
    document.getElementById('idViewerOverlay').classList.remove('show');
}

function idViewerZoom(delta) {
    idViewerScale = Math.min(Math.max(idViewerScale + delta, 0.4), 3);
    applyIdViewerTransform();
}

function idViewerRotate() {
    idViewerRotation = (idViewerRotation + 90) % 360;
    applyIdViewerTransform();
}

function idViewerReset() {
    idViewerScale = 1;
    idViewerRotation = 0;
    applyIdViewerTransform();
}

function applyIdViewerTransform() {
    const img = document.getElementById('idViewerImage');
    img.style.transform = `scale(${idViewerScale}) rotate(${idViewerRotation}deg)`;
}

// close viewer by clicking the dark backdrop (but not the image/toolbar itself)
document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('idViewerOverlay');
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeIdViewer();
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeIdViewer();
    });
});