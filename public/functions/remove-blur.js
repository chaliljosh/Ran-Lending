

document.addEventListener('DOMContentLoaded', function () {

    var BLUR_TARGET_SELECTORS = [
        'body',
        '.nxl-container',
        '.nxl-navigation',
        '.nxl-header',
        '.navbar-wrapper',
        '.header-wrapper',
        '.nxl-content',
        'main',
        '.footer',
        '.page-header',
        '.main-content',
    ];

    function getBlurTargets() {
        var els = [];
        BLUR_TARGET_SELECTORS.forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                els.push(el);
            });
        });
        return els;
    }

    function stripBlurFromPage() {
        getBlurTargets().forEach(function (el) {
            var computed = window.getComputedStyle(el);
            if (computed.filter && computed.filter !== 'none' && computed.filter.indexOf('blur') !== -1) {
                el.setAttribute('data-blur-override', 'true');
                el.style.setProperty('filter', 'none', 'important');
                el.style.setProperty('-webkit-filter', 'none', 'important');
            }
        });
    }

    function restoreBlurOverrides() {
        document.querySelectorAll('[data-blur-override="true"]').forEach(function (el) {
            el.style.removeProperty('filter');
            el.style.removeProperty('-webkit-filter');
            el.removeAttribute('data-blur-override');
        });
    }

    // Detaches each .ld-sticky-col from the DOM and immediately
    // reinserts it at the same position. This forces a full
    // re-render of everything inside it, which reliably fixes
    // Chrome's stale-paint bug on position:sticky elements after
    // a Bootstrap modal closes. Reattaching the same node (not a
    // clone) preserves Bootstrap's own event delegation, so
    // data-bs-toggle="modal" buttons keep working.
    function forceStickyRerender() {
        document.querySelectorAll('.ld-sticky-col').forEach(function (el) {
            var parent = el.parentNode;
            var next = el.nextSibling;
            if (!parent) return;

            parent.removeChild(el);
            // Force a reflow so the removal actually takes effect
            // before we reinsert — otherwise the browser may batch
            // both operations together and skip the repaint.
            void parent.offsetHeight;

            if (next) {
                parent.insertBefore(el, next);
            } else {
                parent.appendChild(el);
            }
        });
    }

    // Bootstrap modal events bubble on the modal element itself.
    document.addEventListener('show.bs.modal', function () {
        // Run immediately and once more after the open class settles,
        // in case the theme applies blur via a class added slightly
        // after show.bs.modal fires.
        stripBlurFromPage();
        requestAnimationFrame(stripBlurFromPage);
    });

    document.addEventListener('shown.bs.modal', stripBlurFromPage);

    document.addEventListener('hidden.bs.modal', function () {
        // Only restore once ALL modals are closed (walang natitirang
        // .modal.show sa page — importante ito dahil sa mga stacked
        // modal flows mo: settings -> crop -> settings, atbp.)
        if (!document.querySelector('.modal.show')) {
            restoreBlurOverrides();
            forceStickyRerender();
        }
    });
});