document.addEventListener('DOMContentLoaded', function () {
    var parents = document.querySelectorAll('.nxl-flat-parent');

    function setOpen(parent, open) {
        parent.classList.toggle('is-open', open);
        var trigger = parent.querySelector('.nxl-flat-toggle-trigger');
        if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    // Accordion: isa lang ang pwedeng bukas
    function toggle(parent) {
        var willOpen = !parent.classList.contains('is-open');

        parents.forEach(function (p) {
            if (p !== parent) setOpen(p, false);
        });

        setOpen(parent, willOpen);
    }

    // Sa page load: kung higit sa isa ang may is-open, isa lang ang iwan
    // (unahin yung may active na sublink)
    var openOnes = Array.prototype.filter.call(parents, function (p) {
        return p.classList.contains('is-open');
    });
    if (openOnes.length > 1) {
        var keep = openOnes.find(function (p) {
            return p.querySelector('.nxl-sublink.active');
        }) || openOnes[0];

        openOnes.forEach(function (p) {
            setOpen(p, p === keep);
        });
    }

    parents.forEach(function (parent) {
        var trigger = parent.querySelector('.nxl-flat-toggle-trigger');
        if (!trigger) return;

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            toggle(parent);
        });

        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggle(parent);
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