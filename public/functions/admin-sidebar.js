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


(function () {
    var STORAGE_KEY = 'nxl-sidebar-mini';

    function getBtns() {
        return {
            mini: document.getElementById('menu-mini-button'),
            expand: document.getElementById('menu-expend-button')
        };
    }

    function applyMini(isMini) {
        document.documentElement.classList.toggle('minimenu', isMini);
        document.body.classList.toggle('sidebar-mini-mode', isMini);

        var b = getBtns();
        if (b.mini) b.mini.style.display = isMini ? 'none' : '';
        if (b.expand) b.expand.style.display = isMini ? '' : 'none';

        try { localStorage.setItem(STORAGE_KEY, isMini ? '1' : '0'); } catch (e) {}
    }

    function isMiniNow() {
        return document.body.classList.contains('sidebar-mini-mode');
    }

    // Capture phase sa document: nauuna ito sa theme's handler,
    // at pinipigilan natin itong makarating sa button.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('#menu-mini-button, #menu-expend-button');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        applyMini(!isMiniNow());
    }, true);

    function init() {
        var saved = null;
        try { saved = localStorage.getItem(STORAGE_KEY); } catch (e) {}
        applyMini(saved === '1');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();