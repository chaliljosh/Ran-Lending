/* Borrower: loader kapag nag-click sa link na lilipat ng page
   (sidebar, profile dropdown, at recent payments sa dashboard). */
(function () {
    'use strict';

    // Mga link na lilipat ng page
    var SELECTOR = '.nxl-navigation a[href], .bwl-dropdown-menu a[href], a.bwl-dash-payment-link[href]';

    function showPageLoader() {
        if (document.querySelector('.bwl-nf-loader-page')) return;

        var overlay = document.createElement('div');
        overlay.className = 'bwl-nf-loader bwl-nf-loader-page';

        var spinner = document.createElement('span');
        spinner.className = 'bwl-nf-spinner';
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-label', 'Loading');

        overlay.appendChild(spinner);
        document.body.appendChild(overlay);

        // Pang-safety: kung hindi natuloy ang paglipat, alisin pagkatapos ng 10 segundo
        setTimeout(hidePageLoader, 10000);
    }

    function hidePageLoader() {
        document.querySelectorAll('.bwl-nf-loader-page').forEach(function (el) {
            el.remove();
        });
    }

    // Kapag bumalik gamit ang Back button, alisin ang naiwang loader
    window.addEventListener('pageshow', hidePageLoader);

    document.addEventListener('click', function (e) {
        var link = e.target.closest ? e.target.closest(SELECTOR) : null;
        if (!link) return;

        // Kung may ibang code na humarang (hal. logout modal), huwag mag-loader
        if (e.defaultPrevented) return;

        // Bagong tab o may modifier key
        if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        if (link.target === '_blank') return;

        var href = link.getAttribute('href') || '';

        // Dropdown/submenu toggle at walang pupuntahan
        if (href === '' || href === '#' || href.indexOf('javascript:') === 0) return;
        if (link.hasAttribute('data-bs-toggle')) return;

        // Logout
        if (link.hasAttribute('onclick') || href.toLowerCase().indexOf('logout') !== -1) return;

        // Ibang website
        if (link.origin !== window.location.origin) return;

        // Parehong page lang (walang lilipatan)
        if (link.href === window.location.href) return;

        showPageLoader();
    });
})();