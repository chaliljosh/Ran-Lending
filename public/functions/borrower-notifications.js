/* Borrower notifications: tabs (All/Unread), filter ng type, pagination,
   Mark all as read, loader, at pagsara ng dropdown kapag nag-click sa labas. */
(function () {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // [1, '…', 4, 5, 6, '…', 12] — palagi may first at last page
    function pageList(total, cur) {
        if (total <= 7) {
            var all = [];
            for (var i = 1; i <= total; i++) all.push(i);
            return all;
        }
        var pages = [1];
        var s = Math.max(2, cur - 1);
        var e = Math.min(total - 1, cur + 1);
        if (s > 2) pages.push('…');
        for (var p = s; p <= e; p++) pages.push(p);
        if (e < total - 1) pages.push('…');
        pages.push(total);
        return pages;
    }

    // ---------- Loader element (para sa dropdown at page) ----------
    function createLoaderEl() {
        var overlay = document.createElement('div');
        overlay.className = 'bwl-nf-loader';
        overlay.setAttribute('data-nf-loader', '');
        overlay.hidden = true;

        var spinner = document.createElement('span');
        spinner.className = 'bwl-nf-spinner';
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-label', 'Loading');

        overlay.appendChild(spinner);
        return overlay;
    }

    // ---------- Full-screen loader (para sa "See all notifications") ----------
    function showPageLoader() {
        if (document.querySelector('.bwl-nf-loader-page')) return;

        var overlay = createLoaderEl();
        overlay.classList.add('bwl-nf-loader-page');
        overlay.hidden = false;
        document.body.appendChild(overlay);
    }

    function hidePageLoader() {
        document.querySelectorAll('.bwl-nf-loader-page').forEach(function (el) {
            el.remove();
        });
    }

    function setupRoot(root) {
        var tabs = root.querySelectorAll('[data-nf-tab]');
        var chips = root.querySelectorAll('[data-nf-cat]');
        var items = root.querySelectorAll('.bwl-nf-item');
        var emptyEl = root.querySelector('[data-nf-empty]');
        var markAll = root.querySelector('[data-nf-markall]');
        var pager = root.querySelector('[data-nf-pager]');
        var pagerInfo = root.querySelector('[data-nf-pager-info]');
        var pagerNav = root.querySelector('[data-nf-pager-nav]');
        var loader = root.querySelector('[data-nf-loader]');

        // Kung walang loader sa HTML (halimbawa sa dropdown), gumawa
        if (!loader) {
            loader = createLoaderEl();
            root.appendChild(loader);
        }

        // 0 = walang pagination (dropdown). Sa buong page, galing sa data-nf-page-size.
        var pageSize = parseInt(root.getAttribute('data-nf-page-size'), 10) || 0;
        var page = 1;
        var filter = 'all';   // all | unread
        var cat = 'all';      // all | loans | payments | account

        // ---------- Loader ----------
        function showLoader() {
            loader.hidden = false;
            root.setAttribute('aria-busy', 'true');
        }

        function hideLoader() {
            loader.hidden = true;
            root.removeAttribute('aria-busy');
        }

        // Ipakita muna ang loader sandali, tapos i-apply ang filter/page
        function applyWithLoader(scrollToTop) {
            showLoader();
            setTimeout(function () {
                apply();
                hideLoader();
                if (scrollToTop) {
                    root.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 300);
        }

        // Itago ang type na walang kahit isang notification
        var counts = {};
        items.forEach(function (item) {
            var c = item.getAttribute('data-cat') || 'account';
            counts[c] = (counts[c] || 0) + 1;
        });
        chips.forEach(function (chip) {
            var c = chip.getAttribute('data-nf-cat');
            if (c !== 'all' && !counts[c]) chip.hidden = true;
        });

        function makeBtn(label, targetPage, opts) {
            opts = opts || {};
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'bwl-nf-pg' + (opts.active ? ' is-active' : '');
            b.textContent = label;
            if (opts.aria) b.setAttribute('aria-label', opts.aria);
            if (opts.active) b.setAttribute('aria-current', 'page');
            if (opts.disabled) b.disabled = true;
            b.addEventListener('click', function () {
                page = targetPage;
                applyWithLoader(true);
            });
            return b;
        }

        function renderPager(matched, totalPages, start, end) {
            if (!pager) return;

            if (!pageSize || totalPages <= 1) {
                pager.hidden = true;
                return;
            }
            pager.hidden = false;

            pagerInfo.textContent = 'Showing ' + (start + 1) + '–' + Math.min(end, matched) + ' of ' + matched;

            pagerNav.textContent = '';
            pagerNav.appendChild(makeBtn('‹', page - 1, { disabled: page === 1, aria: 'Previous page' }));

            pageList(totalPages, page).forEach(function (p) {
                if (p === '…') {
                    var gap = document.createElement('span');
                    gap.className = 'bwl-nf-pg-gap';
                    gap.textContent = '…';
                    pagerNav.appendChild(gap);
                } else {
                    pagerNav.appendChild(makeBtn(String(p), p, { active: p === page, aria: 'Page ' + p }));
                }
            });

            pagerNav.appendChild(makeBtn('›', page + 1, { disabled: page === totalPages, aria: 'Next page' }));
        }

        function apply() {
            // Ilan ang tumutugma sa filters (bago ang pagination)
            var matched = 0;
            items.forEach(function (item) {
                var unread = item.getAttribute('data-read') === '0';
                var okRead = filter === 'all' || unread;
                var okCat = cat === 'all' || item.getAttribute('data-cat') === cat;
                if (okRead && okCat) matched++;
            });

            var totalPages = pageSize ? Math.max(1, Math.ceil(matched / pageSize)) : 1;
            if (page > totalPages) page = totalPages;

            var start = pageSize ? (page - 1) * pageSize : 0;
            var end = pageSize ? start + pageSize : matched;

            var idx = 0;
            items.forEach(function (item) {
                var unread = item.getAttribute('data-read') === '0';
                var okRead = filter === 'all' || unread;
                var okCat = cat === 'all' || item.getAttribute('data-cat') === cat;
                var match = okRead && okCat;

                item.hidden = !(match && idx >= start && idx < end);
                if (match) idx++;
            });

            tabs.forEach(function (tab) {
                var active = tab.getAttribute('data-nf-tab') === filter;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            chips.forEach(function (chip) {
                chip.classList.toggle('is-active', chip.getAttribute('data-nf-cat') === cat);
            });

            if (emptyEl) {
                emptyEl.hidden = matched > 0;
                if (filter === 'unread') {
                    emptyEl.textContent = "You're all caught up.";
                } else if (cat !== 'all') {
                    emptyEl.textContent = 'No notifications in this category.';
                } else {
                    emptyEl.textContent = 'No notifications yet.';
                }
            }

            renderPager(matched, totalPages, start, end);
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                filter = tab.getAttribute('data-nf-tab');
                page = 1;
                applyWithLoader(false);
            });
        });

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                cat = chip.getAttribute('data-nf-cat');
                page = 1;
                applyWithLoader(false);
            });
        });

        if (markAll) {
            markAll.addEventListener('click', function () {
                var url = markAll.getAttribute('data-url');
                if (!url) return;

                markAll.disabled = true;
                showLoader();

                // Para makita talaga ang loader kahit mabilis ang server
                var startedAt = Date.now();
                var minShow = 500;

                function finish(fn) {
                    var wait = Math.max(0, minShow - (Date.now() - startedAt));
                    setTimeout(function () {
                        fn();
                        hideLoader();
                    }, wait);
                }

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('Request failed');
                        return res.json();
                    })
                    .then(function () {
                        finish(function () {
                            // Lahat ng item sa DOM (dropdown at page) ay read na
                            document.querySelectorAll('.bwl-nf-item').forEach(function (item) {
                                item.classList.remove('is-unread');
                                item.setAttribute('data-read', '1');
                            });
                            document.querySelectorAll('.bwl-notif-badge').forEach(function (b) {
                                b.remove();
                            });
                            document.querySelectorAll('[data-nf-markall]').forEach(function (b) {
                                b.hidden = true;
                            });
                            apply();
                        });
                    })
                    .catch(function () {
                        finish(function () {
                            markAll.disabled = false;
                        });
                    });
            });
        }

        apply();
    }

    function init() {
        document.querySelectorAll('[data-nf-root]').forEach(setupRoot);

        // Isara ang dropdown kapag nag-click sa labas o pinindot ang Escape
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details.bwl-notif-bell[open]').forEach(function (d) {
                if (!d.contains(e.target)) d.removeAttribute('open');
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('details.bwl-notif-bell[open]').forEach(function (d) {
                d.removeAttribute('open');
            });
        });

        // Loader kapag na-click ang "See all notifications"
        document.addEventListener('click', function (e) {
            var link = e.target.closest ? e.target.closest('.bwl-nf-foot a') : null;
            if (!link) return;

            // Huwag mag-loader kung bubuksan sa bagong tab
            if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
            if (link.target === '_blank') return;

            showPageLoader();
        });
    }

    // Kapag bumalik gamit ang Back button, alisin ang naiwang loader
    window.addEventListener('pageshow', hidePageLoader);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();