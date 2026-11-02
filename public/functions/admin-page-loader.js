/* Admin: loader kapag lilipat ng page (sidebar, submenu, See all notifications, notification items). */
(function () {
    'use strict';

    // ---------- Style ng loader (nilalagay ng JS, walang kailangang CSS file) ----------
    var css = ''
        + '.adm-page-loader{position:fixed;inset:0;z-index:2000;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.7);}'
        + '.adm-page-spinner{width:32px;height:32px;border:3px solid #e2e8f0;border-top-color:#2563eb;border-radius:50%;animation:adm-page-spin 0.7s linear infinite;}'
        + '@keyframes adm-page-spin{to{transform:rotate(360deg);}}'
        + 'html.app-skin-dark .adm-page-loader{background:rgba(20,20,20,0.7);}'
        + 'html.app-skin-dark .adm-page-spinner{border-color:#262626;border-top-color:#60a5fa;}';

    var styleEl = document.createElement('style');
    styleEl.textContent = css;
    document.head.appendChild(styleEl);

    // ---------- Loader ----------
    function showPageLoader() {
        if (document.querySelector('.adm-page-loader')) return;

        var overlay = document.createElement('div');
        overlay.className = 'adm-page-loader';

        var spinner = document.createElement('span');
        spinner.className = 'adm-page-spinner';
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-label', 'Loading');

        overlay.appendChild(spinner);
        document.body.appendChild(overlay);

        // Pang-safety: kung hindi natuloy ang paglipat, alisin pagkatapos ng 10 segundo
        setTimeout(hidePageLoader, 10000);
    }

    function hidePageLoader() {
        document.querySelectorAll('.adm-page-loader').forEach(function (el) {
            el.remove();
        });
    }

    // Kapag bumalik gamit ang Back button, alisin ang naiwang loader
    window.addEventListener('pageshow', hidePageLoader);

    // Mga link na lilipat ng page (kasama ang submenu)
    var SELECTOR = '.nxl-navigation a[href], .nxl-notif-dropdown-foot a[href], a.nxl-notif-item[href]';

    // true = capture phase, para mauna ito kahit may stopPropagation ang ibang script
    document.addEventListener('click', function (e) {
        var link = e.target.closest ? e.target.closest(SELECTOR) : null;
        if (!link) return;

        // Bagong tab o may modifier key
        if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        if (link.target === '_blank') return;

        var href = link.getAttribute('href') || '';

        // Dropdown toggle at walang pupuntahan
        if (href === '' || href === '#' || href.indexOf('javascript:') === 0) return;
        if (link.hasAttribute('data-bs-toggle')) return;

        // Logout
        if (link.hasAttribute('onclick') || href.toLowerCase().indexOf('logout') !== -1) return;

        // Ibang website
        if (link.origin !== window.location.origin) return;

        // Parehong page lang (walang lilipatan)
        if (link.href === window.location.href) return;

        showPageLoader();
    }, true);
})();