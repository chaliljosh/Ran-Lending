// Header bell — "Mark all as read" button
// Lives on: borrower.layout (present on every borrower page)
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('bwlNotifMarkAllRead');
    if (!btn) return;

    const markAllReadUrl = btn.dataset.url;
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : null;

    btn.addEventListener('click', function (e) {
        e.preventDefault();

        fetch(markAllReadUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
        .then(res => {
            if (!res.ok) throw new Error('Mark all as read failed: ' + res.status);
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                console.error('Mark all as read did not succeed:', data);
                return;
            }

            // ---- Update the UI in place, walang page reload ----

            // 1. Alisin ang "unread" look sa bawat notification item
            //    (dating dot + highlighted background).
            document.querySelectorAll('.bwl-notif-item.is-unread').forEach(function (item) {
                item.classList.remove('is-unread');
            });

            // 2. Itago ang red badge sa ibabaw ng bell icon.
            const badge = document.querySelector('.bwl-notif-badge');
            if (badge) badge.remove();

            // 3. Itago mismo ang "Mark all as read" button, dahil
            //    wala nang unread na natitira.
            btn.remove();
        })
        .catch(err => {
            console.error(err);
        });
    });
});