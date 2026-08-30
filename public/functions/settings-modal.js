document.addEventListener('DOMContentLoaded', function () {
    var settingsModalEl = document.getElementById('bwlSettingsModal');
    if (!settingsModalEl) return;

    var csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var UPLOAD_PICTURE_URL  = '/api/borrower/profile/upload-picture';
    var REMOVE_PICTURE_URL  = '/api/borrower/profile/remove-picture';
    var CHANGE_PASSWORD_URL = '/api/borrower/profile/change-password';

    // ============================================================
    // TOAST HELPER — lightweight, walang dependency sa admin-toast.js
    // (na Admin-only). Same visual language lang ng buong Account
    // Settings modal (navy/gray/green/danger).
    // ============================================================
    function showBwlToast(type, title, desc, duration) {
        type = (type === 'success' || type === 'error') ? type : 'success';
        duration = typeof duration === 'number' ? duration : 4000;

        var stack = document.getElementById('bwlToastStack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'bwlToastStack';
            stack.className = 'bwl-toast-stack';
            document.body.appendChild(stack);
        }

        var icon = type === 'success' ? 'feather-check-circle' : 'feather-alert-circle';
        var toast = document.createElement('div');
        toast.className = 'bwl-toast is-' + type;
        toast.innerHTML =
            '<div class="bwl-toast-icon"><i class="' + icon + '"></i></div>' +
            '<div class="bwl-toast-body">' +
                '<div class="bwl-toast-title"></div>' +
                '<div class="bwl-toast-desc"></div>' +
            '</div>' +
            '<button type="button" class="bwl-toast-close" aria-label="Close"><i class="feather-x"></i></button>';

        toast.querySelector('.bwl-toast-title').textContent = title || '';
        toast.querySelector('.bwl-toast-desc').textContent = desc || '';

        stack.appendChild(toast);

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                toast.classList.add('is-visible');
            });
        });

        var dismissTimer = null;
        function dismiss() {
            if (dismissTimer) clearTimeout(dismissTimer);
            toast.classList.remove('is-visible');
            toast.classList.add('is-leaving');
            toast.addEventListener('transitionend', function onEnd() {
                toast.removeEventListener('transitionend', onEnd);
                toast.remove();
            });
        }

        toast.querySelector('.bwl-toast-close').addEventListener('click', dismiss);
        if (duration > 0) dismissTimer = setTimeout(dismiss, duration);
    }

    // ============================================================
    // PROFILE PICTURE — CHANGE / CROP / UPLOAD / REMOVE
    // ============================================================
    var changePhotoBtn     = document.getElementById('bwlSetChangePhotoBtn');
    var changePhotoTextBtn = document.getElementById('bwlSetChangePhotoTextBtn');
    var removePhotoBtn     = document.getElementById('bwlSetRemovePhotoBtn');
    var fileInput          = document.getElementById('bwlSetFileInput');

    var cropModalEl     = document.getElementById('bwlSetCropModal');
    var cropImage        = document.getElementById('bwlSetCropImage');
    var cropApplyBtn     = document.getElementById('bwlSetCropApplyBtn');
    var cropApplyText    = document.getElementById('bwlSetCropApplyText');
    var cropApplySpinner = document.getElementById('bwlSetCropApplySpinner');
    var cropCancelBtn    = document.getElementById('bwlSetCropCancelBtn');
    var cropCloseX       = document.getElementById('bwlSetCropCloseX');
    var cropper = null;

    function openFilePicker() { fileInput.click(); }
    if (changePhotoBtn) changePhotoBtn.addEventListener('click', openFilePicker);
    if (changePhotoTextBtn) changePhotoTextBtn.addEventListener('click', openFilePicker);

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

    function openCropModal(dataUrl) {
        cropImage.src = dataUrl;
        var cropModal = bootstrap.Modal.getOrCreateInstance(cropModalEl);
        cropModal.show();

        cropModalEl.addEventListener('shown.bs.modal', function onShown() {
            cropModalEl.removeEventListener('shown.bs.modal', onShown);
            if (cropper) { cropper.destroy(); cropper = null; }
            cropper = new Cropper(cropImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.8,
                cropBoxMovable: false,
                cropBoxResizable: false,
                background: false,
                responsive: true,
                guides: false,
            });
        });
    }

    function closeCropModal() {
        if (cropper) { cropper.destroy(); cropper = null; }
        var cropModal = bootstrap.Modal.getInstance(cropModalEl);
        if (cropModal) cropModal.hide();
        fileInput.value = '';
    }

    if (cropCancelBtn) cropCancelBtn.addEventListener('click', closeCropModal);
    if (cropCloseX) cropCloseX.addEventListener('click', closeCropModal);

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

    function uploadCroppedImage(blob) {
        var formData = new FormData();
        formData.append('profile_picture', blob, 'profile.jpg');

        // ---- Loading state: spinner icon na lang, walang "Uploading..." text ----
        cropApplyBtn.disabled = true;
        cropApplyText.classList.add('d-none');
        cropApplySpinner.classList.remove('d-none');

        fetch(UPLOAD_PICTURE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data.success) {
                showBwlToast('error', 'Upload Failed', data.message || 'Please try again.');
                return;
            }
            document.querySelectorAll('.js-borrower-avatar').forEach(function (el) {
                el.innerHTML = '<img src="' + data.profile_picture_url + '" alt="Profile picture">';
            });
            if (removePhotoBtn) removePhotoBtn.style.display = '';
            var photoLabel = document.getElementById('bwlSetChangePhotoLabel');
            if (photoLabel) photoLabel.textContent = 'Change Profile Picture';
            closeCropModal();

            showBwlToast('success', 'Profile Picture Updated', 'Your new profile picture has been saved.');
        })
        .catch(function (err) {
            console.error('[borrower-account-settings-modal.js] upload error:', err);
            showBwlToast('error', 'Upload Failed', 'Please try again.');
        })
        .finally(function () {
            cropApplyBtn.disabled = false;
            cropApplyText.classList.remove('d-none');
            cropApplySpinner.classList.add('d-none');
        });
    }

    // ---- Remove photo: opens the confirm modal instead of native confirm() ----
    var removeConfirmModalEl  = document.getElementById('bwlRemovePhotoConfirmModal');
    var confirmRemoveBtn      = document.getElementById('bwlConfirmRemovePhotoBtn');
    var confirmRemoveText     = document.getElementById('bwlConfirmRemovePhotoText');
    var confirmRemoveSpinner  = document.getElementById('bwlConfirmRemovePhotoSpinner');

    if (removePhotoBtn && removeConfirmModalEl) {
        removePhotoBtn.addEventListener('click', function () {
            var modal = bootstrap.Modal.getOrCreateInstance(removeConfirmModalEl);
            modal.show();
        });
    }

    if (confirmRemoveBtn) {
        confirmRemoveBtn.addEventListener('click', function () {
            confirmRemoveBtn.disabled = true;
            confirmRemoveText.classList.add('d-none');
            confirmRemoveSpinner.classList.remove('d-none');

            fetch(REMOVE_PICTURE_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    showBwlToast('error', 'Failed to Remove Photo', data.message || 'Please try again.');
                    return;
                }
                document.querySelectorAll('.js-borrower-avatar').forEach(function (el) {
                    el.textContent = el.getAttribute('data-initials') || '';
                });
                removePhotoBtn.style.display = 'none';
                var photoLabel = document.getElementById('bwlSetChangePhotoLabel');
                if (photoLabel) photoLabel.textContent = 'Upload Profile Picture';

                var modal = bootstrap.Modal.getInstance(removeConfirmModalEl);
                if (modal) modal.hide();

                showBwlToast('success', 'Profile Picture Removed', 'Your avatar now shows your initials.');
            })
            .catch(function (err) {
                console.error('[borrower-account-settings-modal.js] remove error:', err);
                showBwlToast('error', 'Failed to Remove Photo', 'Please try again.');
            })
            .finally(function () {
                confirmRemoveBtn.disabled = false;
                confirmRemoveText.classList.remove('d-none');
                confirmRemoveSpinner.classList.add('d-none');
            });
        });
    }

    // ============================================================
    // CHANGE PASSWORD (now its own modal)
    // ============================================================
    var passwordModalEl = document.getElementById('bwlChangePasswordModal');
    var savePasswordBtn     = document.getElementById('bwlSetSavePasswordBtn');
    var savePasswordText    = document.getElementById('bwlSetSavePasswordText');
    var savePasswordSpinner = document.getElementById('bwlSetSavePasswordSpinner');
    var passwordError       = document.getElementById('bwlSetPasswordError');
    var passwordSuccess     = document.getElementById('bwlSetPasswordSuccess');

    if (passwordModalEl) {
        passwordModalEl.addEventListener('show.bs.modal', function () {
            passwordError.classList.add('d-none');
            passwordSuccess.classList.add('d-none');
            document.getElementById('bwlSetCurrentPassword').value = '';
            document.getElementById('bwlSetNewPassword').value = '';
            document.getElementById('bwlSetConfirmPassword').value = '';
        });
    }

    document.querySelectorAll('.bwl-acct-pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            var icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'feather-eye-off';
            } else {
                input.type = 'password';
                icon.className = 'feather-eye';
            }
        });
    });

    if (savePasswordBtn) {
        savePasswordBtn.addEventListener('click', function () {
            passwordError.classList.add('d-none');
            passwordSuccess.classList.add('d-none');

            var current = document.getElementById('bwlSetCurrentPassword').value;
            var newPass = document.getElementById('bwlSetNewPassword').value;
            var confirm = document.getElementById('bwlSetConfirmPassword').value;

            if (!current || !newPass || !confirm) {
                passwordError.textContent = 'Please fill in all fields.';
                passwordError.classList.remove('d-none');
                return;
            }
            if (newPass.length < 8) {
                passwordError.textContent = 'New password must be at least 8 characters.';
                passwordError.classList.remove('d-none');
                return;
            }
            if (newPass !== confirm) {
                passwordError.textContent = 'New passwords do not match.';
                passwordError.classList.remove('d-none');
                return;
            }

            savePasswordBtn.disabled = true;
            savePasswordText.textContent = 'Updating...';
            savePasswordSpinner.classList.remove('d-none');

            var formData = new FormData();
            formData.append('current_password', current);
            formData.append('new_password', newPass);
            formData.append('confirm_password', confirm);

            fetch(CHANGE_PASSWORD_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    passwordSuccess.classList.remove('d-none');
                    document.getElementById('bwlSetCurrentPassword').value = '';
                    document.getElementById('bwlSetNewPassword').value = '';
                    document.getElementById('bwlSetConfirmPassword').value = '';
                } else {
                    passwordError.textContent = data.message || 'Something went wrong. Please try again.';
                    passwordError.classList.remove('d-none');
                }
            })
            .catch(function () {
                passwordError.textContent = 'Something went wrong. Please check your connection.';
                passwordError.classList.remove('d-none');
            })
            .finally(function () {
                savePasswordBtn.disabled = false;
                savePasswordText.textContent = 'Update Password';
                savePasswordSpinner.classList.add('d-none');
            });
        });
    }
});