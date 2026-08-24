/* ============================================================
   admin-header.js
   Handles:
   1. "Mark all as read" for the notification dropdown.
   2. Mutual exclusion between the header <details> dropdowns
      (notifications + admin profile) — clicking one summary
      force-closes any other open header dropdown FIRST, before
      the browser opens the clicked one.
   3. Click-outside-to-close for both dropdowns.
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    var headerDropdowns = Array.prototype.slice.call(
        document.querySelectorAll('.nxl-header-notif, .nxl-header-admin')
    );

    if (headerDropdowns.length === 0) {
        console.warn('[admin-header.js] No .nxl-header-notif / .nxl-header-admin elements found on this page.');
    }

    // 1 & 2. Mutual exclusion — listen on each <summary>'s click directly
    // (capturing phase, before the browser's native open/close toggle
    // runs) and force-close every OTHER open dropdown at that moment.
    headerDropdowns.forEach(function (el) {
        var summary = el.querySelector('summary');
        if (!summary) return;

        summary.addEventListener('click', function () {
            headerDropdowns.forEach(function (other) {
                if (other !== el && other.hasAttribute('open')) {
                    other.removeAttribute('open');
                }
            });
        });
    });

    // 3. Click outside any open header dropdown closes it.
    document.addEventListener('click', function (e) {
        headerDropdowns.forEach(function (el) {
            if (el.hasAttribute('open') && !el.contains(e.target)) {
                el.removeAttribute('open');
            }
        });
    });

    // 4. "Mark all as read"
    var btn = document.getElementById('nxlMarkAllReadBtn');
    if (!btn) return;

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    if (!csrfToken) {
        console.error('[admin-header.js] No CSRF meta tag found — mark-all-read will fail.');
    }

    btn.addEventListener('click', function (e) {
        e.preventDefault();

        fetch(btn.getAttribute('data-url'), {
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
            console.log('[admin-header.js] mark-all-read response:', data);
            if (!data || !data.success) {
                console.error('[admin-header.js] server did not return success:true');
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
        })
        .catch(function (err) {
            console.error('[admin-header.js] mark-all-read fetch error:', err);
        });
    });
});