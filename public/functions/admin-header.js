document.addEventListener('DOMContentLoaded', function () {
    var headerDropdowns = Array.prototype.slice.call(
        document.querySelectorAll('.nxl-header-notif, .nxl-header-admin')
    );

    if (headerDropdowns.length === 0) {
        console.warn('[admin-header.js] No .nxl-header-notif / .nxl-header-admin elements found on this page.');
    }

    headerDropdowns.forEach(function (el) {
        var summary = el.querySelector('summary');
        if (!summary) return;

        summary.setAttribute('aria-expanded', el.hasAttribute('open'));

        summary.addEventListener('click', function () {
            headerDropdowns.forEach(function (other) {
                if (other !== el && other.hasAttribute('open')) {
                    other.removeAttribute('open');
                }
            });
        });

        el.addEventListener('toggle', function () {
            summary.setAttribute('aria-expanded', el.hasAttribute('open'));
        });
    });

    document.addEventListener('click', function (e) {
        headerDropdowns.forEach(function (el) {
            if (el.hasAttribute('open') && !el.contains(e.target)) {
                el.removeAttribute('open');
            }
        });
    });

    var notifTabs = document.getElementById('nxlNotifTabs');
    var notifList = document.getElementById('nxlNotifList');
    var notifEmptyUnread = document.getElementById('nxlNotifEmptyUnread');
    var activeNotifFilter = 'all';

    var refreshNotifEmpty = function () {
        if (!notifList || !notifEmptyUnread) return;
        var hasUnread = notifList.querySelector('.nxl-notif-item.is-unread');
        notifEmptyUnread.style.display = (activeNotifFilter === 'unread' && !hasUnread) ? 'block' : 'none';
    };

    if (notifTabs && notifList) {
        notifTabs.addEventListener('click', function (e) {
            var tab = e.target.closest('.nxl-notif-tab');
            if (!tab) return;
            activeNotifFilter = tab.getAttribute('data-filter');
            notifTabs.querySelectorAll('.nxl-notif-tab').forEach(function (t) {
                t.classList.toggle('is-active', t === tab);
            });
            notifList.classList.toggle('is-filter-unread', activeNotifFilter === 'unread');
            refreshNotifEmpty();
        });
    }

    var btn = document.getElementById('nxlMarkAllReadBtn');
    if (!btn) return;

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    if (!csrfToken) {
        console.error('[admin-header.js] No CSRF meta tag found — mark-all-read will fail.');
    }

    btn.addEventListener('click', function (e) {
        e.preventDefault();

        if (btn.disabled) return;

        var url = btn.getAttribute('data-url');
        if (!url) {
            console.error('[admin-header.js] No data-url on mark-all-read button.');
            return;
        }

        btn.disabled = true;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(function (res) {
            if (!res.ok) {
                console.error('[admin-header.js] mark-all-read failed with status', res.status, res.statusText);
            }
            return res.json();
        })
        .then(function (data) {
            if (!data || !data.success) {
                console.error('[admin-header.js] server did not return success:true');
                btn.disabled = false;
                return;
            }

            document.querySelectorAll('.nxl-notif-item.is-unread').forEach(function (item) {
                item.classList.remove('is-unread');
                var dot = item.querySelector('.nxl-notif-dot');
                if (dot) dot.remove();
            });

            var badge = document.querySelector('.nxl-notif-badge');
            if (badge) badge.remove();

            btn.style.display = 'none';
            refreshNotifEmpty();
        })
        .catch(function (err) {
            console.error('[admin-header.js] mark-all-read fetch error:', err);
            btn.disabled = false;
        });
    });
});