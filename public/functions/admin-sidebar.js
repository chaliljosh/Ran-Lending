document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.nxl-flat-toggle-trigger').forEach(function (trigger) {
        function toggle() {
            var parent = trigger.closest('.nxl-flat-parent');
            if (!parent) return;

            var isOpen = parent.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            toggle();
        });

        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggle();
            }
        });
    });
});