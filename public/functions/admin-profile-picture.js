document.addEventListener('DOMContentLoaded', function () {
    var trigger   = document.getElementById('nxlAvatarUploadTrigger');
    var fileInput = document.getElementById('nxlAvatarFileInput');
    var removeBtn = document.getElementById('nxlAvatarRemoveBtn');
    if (!trigger || !fileInput) return;

    var csrfMeta   = document.querySelector('meta[name="csrf-token"]');
    var csrfToken  = csrfMeta ? csrfMeta.getAttribute('content') : '';
    var uploadUrl  = "{{ route('admin.account-settings.profile-picture') }}";
    var removeUrl  = "{{ route('admin.account-settings.profile-picture.remove') }}";

    trigger.addEventListener('click', function () {
        fileInput.click();
    });

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

        var formData = new FormData();
        formData.append('profile_picture', file);

        trigger.disabled = true;

        fetch(uploadUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data || !data.success) {
                alert('Upload failed. Please try again.');
                return;
            }
            document.querySelectorAll('.js-admin-avatar').forEach(function (el) {
                el.innerHTML = '<img src="' + data.profile_picture_url + '" alt="Profile picture">';
            });
            if (removeBtn) removeBtn.style.display = '';
        })
        .catch(function (err) {
            console.error('[admin-profile-picture.js] upload error:', err);
            alert('Upload failed. Please try again.');
        })
        .finally(function () {
            trigger.disabled = false;
            fileInput.value = '';
        });
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            if (!confirm('Remove your profile picture?')) return;

            fetch(removeUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.success) {
                    alert('Failed to remove photo.');
                    return;
                }
                var initials = document.querySelector('.nxl-admin-dropdown-head .nxl-admin-name')
                    ? document.querySelectorAll('.js-admin-avatar')[0].dataset.initials
                    : '';
                document.querySelectorAll('.js-admin-avatar').forEach(function (el) {
                    el.textContent = el.getAttribute('data-initials') || '';
                });
                removeBtn.style.display = 'none';
            })
            .catch(function (err) {
                console.error('[admin-profile-picture.js] remove error:', err);
                alert('Failed to remove photo.');
            });
        });
    }
});