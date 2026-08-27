/* ============================================================
   admin-toast.js
   Global toast/notification helper. Call from anywhere:

       window.showAdminToast('success', 'Profile Picture Updated',
           'Your new profile picture has been saved.');

   Types: 'success', 'error', 'warning', 'info'
   Auto-dismisses after 5s; always closable via the X button.
============================================================ */

(function () {
    const ICONS = {
        success: 'feather-check-circle',
        error: 'feather-alert-circle',
        warning: 'feather-info',
        info: 'feather-info',
    };

    function getStack() {
        let stack = document.getElementById('nxlToastStack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'nxlToastStack';
            stack.className = 'nxl-toast-stack';
            document.body.appendChild(stack);
        }
        return stack;
    }

    function showAdminToast(type, title, desc, duration) {
        type = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
        duration = typeof duration === 'number' ? duration : 5000;

        const stack = getStack();

        const toast = document.createElement('div');
        toast.className = 'nxl-toast is-' + type;
        toast.innerHTML =
            '<div class="nxl-toast-icon"><i class="' + ICONS[type] + '"></i></div>' +
            '<div class="nxl-toast-body">' +
                '<div class="nxl-toast-title"></div>' +
                '<div class="nxl-toast-desc"></div>' +
            '</div>' +
            '<button type="button" class="nxl-toast-close" aria-label="Close"><i class="feather-x"></i></button>';

        toast.querySelector('.nxl-toast-title').textContent = title || '';
        toast.querySelector('.nxl-toast-desc').textContent = desc || '';

        stack.appendChild(toast);

        // Trigger enter animation on next frame
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                toast.classList.add('is-visible');
            });
        });

        let dismissTimer = null;

        function dismiss() {
            if (dismissTimer) clearTimeout(dismissTimer);
            toast.classList.remove('is-visible');
            toast.classList.add('is-leaving');
            toast.addEventListener('transitionend', function onEnd() {
                toast.removeEventListener('transitionend', onEnd);
                toast.remove();
            });
        }

        toast.querySelector('.nxl-toast-close').addEventListener('click', dismiss);

        if (duration > 0) {
            dismissTimer = setTimeout(dismiss, duration);
        }

        return { dismiss };
    }

    window.showAdminToast = showAdminToast;
})();