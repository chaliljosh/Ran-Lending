/**
 * SessionSync
 * ------------------------------------------------------------------
 * Cross-tab logout synchronization gamit ang localStorage 'storage' event.
 *
 * Behavior depende sa klase ng page:
 *   - PROTECTED pages (admin/borrower dashboard) -> i-redirect papuntang
 *     login page, kasi hindi dapat makita ang content na 'to kapag
 *     naka-logout na.
 *   - PUBLIC pages (landing page) -> i-reload lang ang page mismo,
 *     kasi pwede naman tignan kahit guest (magpapalit lang ang navbar
 *     mula "user menu" papuntang "Sign in" button).
 *
 * Paano malalaman kung protected ang page: kung may
 * <meta name="requires-auth" content="true"> sa <head>, ilalagay lang
 * natin 'to sa admin/borrower layout, HINDI sa landing page.
 * ------------------------------------------------------------------
 */
(function (window) {
    'use strict';

    const STORAGE_KEY = 'ran-serenity-logout-event';

    function getLoginUrl() {
        const meta = document.querySelector('meta[name="login-route"]');
        return meta ? meta.content : '/login';
    }

    function isProtectedPage() {
        return !!document.querySelector('meta[name="requires-auth"]');
    }

    /**
     * Tawagin ito bago mag-submit/fetch ng actual logout request.
     */
    function broadcastLogout() {
        try {
            localStorage.setItem(STORAGE_KEY, Date.now().toString());
        } catch (e) {
            console.warn('SessionSync: hindi ma-broadcast ang logout event.', e);
        }
    }

    /**
     * Pag na-detect ang logout signal mula sa ibang tab:
     *   - protected page  -> redirect sa login
     *   - public page     -> reload lang, mananatili sa parehong page
     */
    function handleLogoutDetected() {
        if (isProtectedPage()) {
            window.location.href = getLoginUrl();
        } else {
            window.location.reload();
        }
    }

    function listenForLogout() {
        window.addEventListener('storage', function (event) {
            if (event.key === STORAGE_KEY && event.newValue) {
                handleLogoutDetected();
            }
        });
    }

    listenForLogout();

    window.SessionSync = {
        broadcastLogout: broadcastLogout
    };

})(window);