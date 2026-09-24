function decisionShowConfirm(action) {
    // NEW: kapag reject, kailangan may laman ang remarks bago
    // pumunta sa confirmation step — dito papalabas si borrower
    // ng specific info kung ano dapat ayusin.
    if (action === 'rejected') {
        const remarksInput = document.getElementById('decisionRemarks');
        const errorEl = document.getElementById('decisionRemarksError');

        if (!remarksInput.value.trim()) {
            remarksInput.classList.add('is-invalid');
            if (errorEl) errorEl.style.display = 'block';
            remarksInput.focus();
            return;
        }

        remarksInput.classList.remove('is-invalid');
        if (errorEl) errorEl.style.display = 'none';
    }

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
        headerTitle.textContent = 'Approve borrower verification';
        iconWrap.className = 'modal-icon-circle icon-success';
        icon.className = 'feather-check';
        text.textContent = 'Approve this borrower?';
        subtext.textContent = "Are you sure you want to approve this borrower's verification? This action cannot be undone.";
        confirmBtn.className = 'btn-decision btn-decision-black';
        confirmBtn.textContent = 'Approve';
    } else {
        headerTitle.textContent = 'Reject borrower verification';
        iconWrap.className = 'modal-icon-circle icon-danger';
        icon.className = 'feather-x';
        text.textContent = 'Reject this borrower?';
        subtext.textContent = "Are you sure you want to reject this borrower's verification? This action cannot be undone.";
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
    document.getElementById('decisionModalLabel').textContent = 'Review Borrower Verification';
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

            // NEW: alisin ang error highlight sa sandaling magsimula
            // mag-type ulit ang admin.
            if (remarksInput.value.trim()) {
                remarksInput.classList.remove('is-invalid');
                const errorEl = document.getElementById('decisionRemarksError');
                if (errorEl) errorEl.style.display = 'none';
            }
        });
    }


    const decisionForm = document.getElementById('decisionForm');
    if (decisionForm) {
        decisionForm.addEventListener('submit', function () {
const confirmBtn = document.getElementById('decisionConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

    const cancelBtn = document.querySelector('#decisionFooterStep2 .btn-decision-cancel');
    if (cancelBtn) cancelBtn.disabled = true;
        });
    }
});

/* ============================================
   VALID ID VIEWER — zoom + rotate + fullscreen
   (unchanged)
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