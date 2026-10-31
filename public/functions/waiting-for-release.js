document.addEventListener('DOMContentLoaded', function () {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var MAX_BYTES = 5 * 1024 * 1024;
    var ALLOWED = ['image/jpeg', 'image/png'];

    var modalEl = document.getElementById('olsSendProofModal');
    var openBtn = document.getElementById('olsSendProofBtn');
    if (!modalEl || !openBtn) return;

    var input = document.getElementById('olsProofInput');
    var dropzone = document.getElementById('olsProofDropzone');
    var preview = document.getElementById('olsProofPreview');
    var previewImg = document.getElementById('olsProofPreviewImg');
    var fileName = document.getElementById('olsProofFileName');
    var fileSize = document.getElementById('olsProofFileSize');
    var removeBtn = document.getElementById('olsProofRemove');
    var errEl = document.getElementById('olsProofError');
    var sendBtn = document.getElementById('olsProofSendBtn');

    var file = null;
    var previewUrl = null;

    function showError(msg) {
        errEl.textContent = msg || '';
        errEl.style.display = msg ? '' : 'none';
    }

    function formatSize(bytes) {
        return bytes >= 1048576
            ? (bytes / 1048576).toFixed(1) + ' MB'
            : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function setLoading(btn, loading) {
        btn.disabled = loading;
        btn.classList.toggle('is-loading', loading);
        var spinner = btn.querySelector('.spin-icon');
        if (loading && !spinner) {
            var s = document.createElement('i');
            s.className = 'feather-loader spin-icon';
            btn.insertBefore(s, btn.firstChild);
        } else if (!loading && spinner) {
            spinner.remove();
        }
    }

    function clearProof() {
        if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
        file = null;
        input.value = '';
        previewImg.removeAttribute('src');
        preview.style.display = 'none';
        dropzone.style.display = '';
    }

    input.addEventListener('change', function () {
        var f = input.files[0];
        clearProof();
        showError('');
        if (!f) return;

        if (ALLOWED.indexOf(f.type) === -1) {
            showError('Only JPG or PNG images are allowed.');
            return;
        }
        if (f.size > MAX_BYTES) {
            showError('File is too large. Maximum size is 5MB.');
            return;
        }

        file = f;
        previewUrl = URL.createObjectURL(f);
        previewImg.src = previewUrl;
        fileName.textContent = f.name;
        fileSize.textContent = formatSize(f.size);
        dropzone.style.display = 'none';
        preview.style.display = '';
    });

    removeBtn.addEventListener('click', function () {
        clearProof();
        showError('');
    });

    // Lightbox para sa full-size preview
    var lightbox = document.createElement('div');
    lightbox.className = 'olrm-lightbox';
    lightbox.innerHTML =
        '<button type="button" class="olrm-lightbox-close" aria-label="Close"><i class="feather-x"></i></button>' +
        '<img class="olrm-lightbox-img" alt="Proof of release">';
    document.body.appendChild(lightbox);
    var lightboxImg = lightbox.querySelector('.olrm-lightbox-img');

    function openLightbox() {
        if (!previewUrl) return;
        lightboxImg.src = previewUrl;
        lightbox.classList.add('is-open');
    }

    function closeLightbox() {
        lightbox.classList.remove('is-open');
        lightboxImg.removeAttribute('src');
    }

    preview.title = 'Click to view full size';
    preview.addEventListener('click', function (e) {
        if (e.target.closest('#olsProofRemove')) return;
        openLightbox();
    });
    lightbox.addEventListener('click', closeLightbox);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && lightbox.classList.contains('is-open')) {
            e.stopPropagation();
            e.preventDefault();
            closeLightbox();
        }
    }, true);

    openBtn.addEventListener('click', function () {
        document.getElementById('olsProofAmount').textContent = openBtn.dataset.amount;
        document.getElementById('olsProofBorrowerName').textContent = openBtn.dataset.borrowerName;
        document.getElementById('olsProofBorrowerEmail').textContent = openBtn.dataset.borrowerEmail;
        clearProof();
        showError('');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        closeLightbox();
        clearProof();
        showError('');
    });

    sendBtn.addEventListener('click', function () {
        showError('');
        if (!file) { showError('Please attach the proof.'); return; }

        var fd = new FormData();
        fd.append('proof', file);
        setLoading(sendBtn, true);

        fetch('/admin/loans/' + openBtn.dataset.applicationId + '/send-proof', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: fd
        })
        .then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        })
        .then(function (r) {
            if (!r.ok || !r.data.success) {
                var firstError = r.data.errors ? Object.values(r.data.errors)[0]?.[0] : null;
                showError(firstError || r.data.message || 'Failed to send the proof.');
                setLoading(sendBtn, false);
                return;
            }

            // Binabasa ito ng ongoing-loan-detail.js para ipakita ang "Loan Released" modal
            sessionStorage.setItem('olsReleasedModal', JSON.stringify({
                message: r.data.message ||
                    ('Release confirmation was emailed to ' + openBtn.dataset.borrowerEmail + '.')
            }));

            window.location.href = openBtn.dataset.redirectUrl;
        })
        .catch(function (err) {
            console.error('[waiting-for-release.js] send-proof error:', err);
            showError('Something went wrong. Please try again.');
            setLoading(sendBtn, false);
        });
    });
});