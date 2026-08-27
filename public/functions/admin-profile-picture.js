document.addEventListener('DOMContentLoaded', function () {
    var trigger      = document.getElementById('nxlAvatarUploadTrigger');
    var triggerText  = document.getElementById('nxlAvatarUploadTriggerText'); // FIX: was missing
    var fileInput    = document.getElementById('nxlAvatarFileInput');
    var removeBtn    = document.getElementById('nxlAvatarRemoveBtn');
    if (!trigger || !fileInput) return;

    var csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var uploadUrl = trigger.dataset.uploadUrl;
    var removeUrl = removeBtn ? removeBtn.dataset.removeUrl : '';

    // ---- Crop modal elements ----
    var cropModalEl   = document.getElementById('nxlCropPhotoModal');
    var cropImage      = document.getElementById('nxlCropImage');
    var cropApplyBtn   = document.getElementById('nxlCropApplyBtn');
    var cropCancelBtn  = document.getElementById('nxlCropCancelBtn');
    var cropCloseX     = document.getElementById('nxlCropCloseX');
    var cropper = null;

    function openCropModal(dataUrl) {
        cropImage.src = dataUrl;

        var modal = bootstrap.Modal.getOrCreateInstance(cropModalEl);
        modal.show();

        cropModalEl.addEventListener('shown.bs.modal', function onShown() {
            cropModalEl.removeEventListener('shown.bs.modal', onShown);

            if (cropper) {
                cropper.destroy();
                cropper = null;
            }

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
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        var modal = bootstrap.Modal.getInstance(cropModalEl);
        if (modal) modal.hide();
        fileInput.value = '';
    }

    // ---- Step 1: click camera icon OR "Upload Profile Picture" text
    //      button -> open file picker. Both do the exact same thing. ----
    function openFilePicker() {
        fileInput.click();
    }
    trigger.addEventListener('click', openFilePicker);
    if (triggerText) triggerText.addEventListener('click', openFilePicker);

    fileInput.addEventListener('change', function () {
        var file = fileInput.files[0];
        if (!file) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            if (window.showAdminToast) {
                showAdminToast('error', 'Invalid File Type', 'Please upload a JPG, PNG, or WEBP image.');
            } else {
                alert('Please upload a JPG, PNG, or WEBP image.');
            }
            fileInput.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            if (window.showAdminToast) {
                showAdminToast('error', 'File Too Large', 'Image must be 2MB or smaller.');
            } else {
                alert('Image must be 2MB or smaller.');
            }
            fileInput.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            openCropModal(e.target.result);
        };
        reader.readAsDataURL(file);
    });

    if (cropApplyBtn) {
        cropApplyBtn.addEventListener('click', function () {
            if (!cropper) return;

            var canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingQuality: 'high',
            });

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

        cropApplyBtn.disabled = true;
        cropApplyBtn.textContent = 'Uploading...';
        trigger.disabled = true;
        if (triggerText) triggerText.disabled = true;

        fetch(uploadUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
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
                if (result.data && result.data.message) {
                    msg = result.data.message;
                } else if (result.data && result.data.errors && result.data.errors.profile_picture) {
                    msg = result.data.errors.profile_picture[0];
                } else if (result.status) {
                    msg += ' (HTTP ' + result.status + ')';
                }

                if (window.showAdminToast) {
                    showAdminToast('error', 'Upload Failed', msg);
                } else {
                    alert(msg);
                }
                console.error('[admin-profile-picture.js] upload failed:', result);
                return;
            }

            document.querySelectorAll('.js-admin-avatar').forEach(function (el) {
                el.innerHTML = '<img src="' + result.data.profile_picture_url + '" alt="Profile picture">';
            });
            if (removeBtn) removeBtn.style.display = '';

            closeCropModal();

            if (window.showAdminToast) {
                showAdminToast('success', 'Profile Picture Updated', 'Your new profile picture has been saved.');
            }
        })
        .catch(function (err) {
            console.error('[admin-profile-picture.js] upload error:', err);
            if (window.showAdminToast) {
                showAdminToast('error', 'Upload Failed', 'Something went wrong: ' + err.message);
            } else {
                alert('Upload failed: ' + err.message);
            }
        })
        .finally(function () {
            cropApplyBtn.disabled = false;
            cropApplyBtn.textContent = 'Apply';
            trigger.disabled = false;
            if (triggerText) triggerText.disabled = false;
        });
    }

    // ---- Remove photo — uses the confirmation modal instead of confirm() ----
    var removeConfirmModalEl = document.getElementById('nxlRemovePhotoConfirmModal');
    var confirmRemoveBtn = document.getElementById('nxlConfirmRemovePhotoBtn');

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            if (removeConfirmModalEl) {
                bootstrap.Modal.getOrCreateInstance(removeConfirmModalEl).show();
            } else if (confirm('Remove your profile picture?')) {
                doRemovePhoto();
            }
        });
    }

    if (confirmRemoveBtn) {
        confirmRemoveBtn.addEventListener('click', function () {
            doRemovePhoto();
        });
    }

    function doRemovePhoto() {
        fetch(removeUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            }).catch(function () {
                return { ok: false, status: res.status, data: null };
            });
        })
        .then(function (result) {
            if (removeConfirmModalEl) {
                var m = bootstrap.Modal.getInstance(removeConfirmModalEl);
                if (m) m.hide();
            }

            if (!result.ok || !result.data || !result.data.success) {
                var msg = 'Failed to remove photo.';
                if (result.data && result.data.message) {
                    msg = result.data.message;
                } else if (result.status) {
                    msg += ' (HTTP ' + result.status + ')';
                }
                if (window.showAdminToast) {
                    showAdminToast('error', 'Remove Failed', msg);
                } else {
                    alert(msg);
                }
                console.error('[admin-profile-picture.js] remove failed:', result);
                return;
            }
            document.querySelectorAll('.js-admin-avatar').forEach(function (el) {
                el.textContent = el.getAttribute('data-initials') || '';
            });
            if (removeBtn) removeBtn.style.display = 'none';

            if (window.showAdminToast) {
                showAdminToast('success', 'Photo Removed', 'Your profile picture has been removed.');
            }
        })
        .catch(function (err) {
            if (removeConfirmModalEl) {
                var m2 = bootstrap.Modal.getInstance(removeConfirmModalEl);
                if (m2) m2.hide();
            }
            console.error('[admin-profile-picture.js] remove error:', err);
            if (window.showAdminToast) {
                showAdminToast('error', 'Remove Failed', 'Something went wrong: ' + err.message);
            } else {
                alert('Failed to remove photo: ' + err.message);
            }
        });
    }
});