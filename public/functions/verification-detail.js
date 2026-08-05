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
        confirmBtn.innerHTML = ' Yes, Approve';
    } else {
        icon.className = 'feather-x-circle text-danger';
        text.textContent = "Reject this borrower's personal details?";
        confirmBtn.className = 'btn btn-danger flex-fill';
        confirmBtn.innerHTML = ' Yes, Reject';
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