document.addEventListener('DOMContentLoaded', function () {
    var trigger      = document.getElementById('bwlSetChangePhotoBtn');
    var triggerText   = document.getElementById('bwlSetChangePhotoTextBtn');
    var triggerIcon   = document.getElementById('bwlSetChangePhotoIcon');
    var triggerSpinner = document.getElementById('bwlSetChangePhotoSpinner');
    var triggerLabel  = document.getElementById('bwlSetChangePhotoLabel');
    var fileInput     = document.getElementById('bwlSetFileInput');
    var removeBtn     = document.getElementById('bwlSetRemovePhotoBtn');
    if (!trigger || !fileInput) return;

    var csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var uploadUrl = trigger.dataset.uploadUrl;
    var removeUrl = removeBtn ? removeBtn.dataset.removeUrl : '';

    var cropModalEl   = document.getElementById('bwlSetCropModal');
    var cropImage      = document.getElementById('bwlSetCropImage');
    var cropApplyBtn   = document.getElementById('bwlSetCropApplyBtn');
    var cropApplyText   = document.getElementById('bwlSetCropApplyText');
    var cropApplySpinner = document.getElementById('bwlSetCropApplySpinner');
    var cropCancelBtn  = document.getElementById('bwlSetCropCancelBtn');
    var cropCloseX     = document.getElementById('bwlSetCropCloseX');
    var cropper = null;

    function openCropModal(dataUrl) {
        cropImage.src = dataUrl;
        var modal = bootstrap.Modal.getOrCreateInstance(cropModalEl);
        modal.show();

        cropModalEl.addEventListener('shown.bs.modal', function onShown() {
            cropModalEl.removeEventListener('shown.bs.modal', onShown);
            if (cropper) { cropper.destroy(); cropper = null; }
            cropper = new Cropper(cropImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                background: false,
                responsive: true,
                guides: false,
            });
        });
    }

    function closeCropModal() {
        if (cropper) { cropper.destroy(); cropper = null; }
        var modal = bootstrap.Modal.getInstance(cropModalEl);
        if (modal) modal.hide();
        fileInput.value = '';
    }

    function openFilePicker() { fileInput.click(); }
    if (trigger) trigger.addEventListener('click', openFilePicker);
    if (triggerText) triggerText.addEventListener('click', openFilePicker);

    fileInput.addEventListener('change', function () {
        var file = fileInput.files[0];
        if (!file) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            alert('Please upload a JPG, PNG, or WEBP image.');
            fileInput.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('Image must be 2MB or smaller.');
            fileInput.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) { openCropModal(e.target.result); };
        reader.readAsDataURL(file);
    });

    function setCropLoading(isLoading) {
        cropApplyBtn.disabled = isLoading;
        cropApplyText.classList.toggle('d-none', isLoading);
        cropApplySpinner.classList.toggle('d-none', !isLoading);
    }

    function setTriggerLoading(isLoading) {
        if (trigger) trigger.disabled = isLoading;
        if (triggerText) triggerText.disabled = isLoading;
        if (triggerIcon) triggerIcon.classList.toggle('d-none', isLoading);
        if (triggerSpinner) triggerSpinner.classList.toggle('d-none', !isLoading);
        if (triggerLabel) triggerLabel.classList.toggle('d-none', isLoading);
    }

    if (cropApplyBtn) {
        cropApplyBtn.addEventListener('click', function () {
            if (!cropper) return;
            var canvas = cropper.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high' });
            if (!canvas) return;
            canvas.toBlob(function (blob) {
                if (!blob) return;
                uploadCroppedImage(blob);
            }, 'image/jpeg', 0.92);
        });
    }

    if (cropCancelBtn) cropCancelBtn.addEventListener('click', closeCropModal);
    if (cropCloseX) cropCloseX.addEventListener('click', closeCropModal);

    function uploadCroppedImage(blob) {
        var formData = new FormData();
        formData.append('profile_picture', blob, 'profile.jpg');

        setCropLoading(true);
        setTriggerLoading(true);

        fetch(uploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
        .then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            }).catch(function () {
                return { ok: false, status: res.status, data: null };
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data || !result.data.success) {
                var msg = 'Upload failed. Please try again.';
                if (result.data && result.data.message) msg = result.data.message;
                else if (result.data && result.data.errors && result.data.errors.profile_picture) msg = result.data.errors.profile_picture[0];
                alert(msg);
                console.error('[borrower-profile-picture.js] upload failed:', result);
                return;
            }

            document.querySelectorAll('.js-borrower-avatar').forEach(function (el) {
                el.innerHTML = '<img src="' + result.data.profile_picture_url + '" alt="Profile picture">';
            });
            if (removeBtn) removeBtn.style.display = '';
            if (triggerLabel) triggerLabel.textContent = 'Change Profile Picture';
            closeCropModal();
        })
        .catch(function (err) {
            console.error('[borrower-profile-picture.js] upload error:', err);
            alert('Upload failed: ' + err.message);
        })
        .finally(function () {
            setCropLoading(false);
            setTriggerLoading(false);
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            var confirmModalEl = document.getElementById('bwlRemovePhotoConfirmModal');
            var confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
            confirmModal.show();
        });
    }

    var confirmRemoveBtn = document.getElementById('bwlConfirmRemovePhotoBtn');
    var confirmRemoveText = document.getElementById('bwlConfirmRemovePhotoText');
    var confirmRemoveSpinner = document.getElementById('bwlConfirmRemovePhotoSpinner');

    if (confirmRemoveBtn) {
        confirmRemoveBtn.addEventListener('click', function () {
            confirmRemoveBtn.disabled = true;
            confirmRemoveText.classList.add('d-none');
            confirmRemoveSpinner.classList.remove('d-none');

            fetch(removeUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                }).catch(function () {
                    return { ok: false, status: res.status, data: null };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.success) {
                    var msg = 'Failed to remove photo.';
                    if (result.data && result.data.message) msg = result.data.message;
                    alert(msg);
                    console.error('[borrower-profile-picture.js] remove failed:', result);
                    return;
                }
                document.querySelectorAll('.js-borrower-avatar').forEach(function (el) {
                    el.textContent = el.getAttribute('data-initials') || '';
                });
                removeBtn.style.display = 'none';
                if (triggerLabel) triggerLabel.textContent = 'Upload Profile Picture';

                var confirmModalEl = document.getElementById('bwlRemovePhotoConfirmModal');
                var confirmModal = bootstrap.Modal.getInstance(confirmModalEl);
                if (confirmModal) confirmModal.hide();
            })
            .catch(function (err) {
                console.error('[borrower-profile-picture.js] remove error:', err);
                alert('Failed to remove photo: ' + err.message);
            })
            .finally(function () {
                confirmRemoveBtn.disabled = false;
                confirmRemoveText.classList.remove('d-none');
                confirmRemoveSpinner.classList.add('d-none');
            });
        });
    }
});