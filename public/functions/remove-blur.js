/* ============================================================
   admin-remove-blur.js
   Force-strips any filter:blur() applied by theme.min.css to
   page content while a Bootstrap modal is open. Runs a scan on
   modal show/hide instead of guessing the theme's selector.
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    function stripBlurFromPage() {
        var all = document.body.querySelectorAll('*');
        all.forEach(function (el) {
            // Skip elements inside the modal itself — huwag apektuhan
            // yung mismong modal content (baka may sarili itong blur
            // effects na sinasadya, e.g. crop preview).
            if (el.closest('.modal')) return;

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
        }
    });
});