document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('rptFilterForm');
    if (!form) return;

    const monthSelect = form.querySelector('select[name="month"]');
    const yearSelect = form.querySelector('select[name="year"]');
    const exportBtn = form.querySelector('.rpt-export-btn');
    const page = document.querySelector('.rpt-page');

    // Para ma-ignore ang lumang request kapag mabilis magpalit ng month/year
    let requestCount = 0;

    // Nakaimbak dito ang mga na-load na report (key: "year-month")
    // Kapag binalikan ang same month, instant na, walang request
    const cache = {};
    const CACHE_SECONDS = 300; // 5 minuto

    // Pagination state ng Approved Applications table
    let pageSize = 10;
    let currentPage = 1;

    function buildUrl() {
        return form.getAttribute('action')
            + '?month=' + encodeURIComponent(monthSelect.value)
            + '&year=' + encodeURIComponent(yearSelect.value);
    }

    // Update ang Export PDF link para sa napiling month/year
    function updateExportLink() {
        if (!exportBtn) return;
        const base = exportBtn.getAttribute('href').split('?')[0];
        exportBtn.href = base
            + '?year=' + encodeURIComponent(yearSelect.value)
            + '&month=' + encodeURIComponent(monthSelect.value);
    }

    function showReport(html, url) {
        page.innerHTML = html;
        page.style.opacity = '1';
        updateExportLink();
        history.replaceState(null, '', url);

        // Bagong laman ng page, kaya balik sa page 1
        currentPage = 1;
        updateTable();
    }

    function loadReport() {
        const url = buildUrl();
        const key = yearSelect.value + '-' + monthSelect.value;
        const thisRequest = ++requestCount;

        // Kung nasa cache pa at hindi pa lumipas ang 5 minuto, gamitin na agad
        const saved = cache[key];
        if (saved && (Date.now() - saved.time) < CACHE_SECONDS * 1000) {
            showReport(saved.html, url);
            return;
        }

        // Kaunting fade habang naglo-load
        page.style.transition = 'opacity 0.15s';
        page.style.opacity = '0.5';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Request failed');
                return res.text();
            })
            .then(function (html) {
                // Kung may mas bago nang request, huwag na itong i-apply
                // (pero i-save pa rin sa cache para mabilis next time)
                cache[key] = { html: html, time: Date.now() };
                if (thisRequest !== requestCount) return;

                // Ang response ay laman na lang ng report (cards, chart, tables)
                showReport(html, url);
            })
            .catch(function (err) {
                console.error('[admin-reports.js] load error:', err);
                // Kung pumalya, bumalik sa normal na page refresh
                window.location.href = url;
            });
    }

    // ---------------------------------------------------------------
    // Approved Applications: search + pagination
    // ---------------------------------------------------------------

    // Gumagawa ng listahan ng page numbers, hal. [1, 2, '...', 4]
    function getPageList(current, total) {
        const pages = [];
        for (let i = 1; i <= total; i++) {
            const isEdge = i === 1 || i === total;
            const isNearCurrent = Math.abs(i - current) <= 1;
            if (isEdge || isNearCurrent) {
                pages.push(i);
            } else if (pages[pages.length - 1] !== '...') {
                pages.push('...');
            }
        }
        return pages;
    }

    function renderNav(nav, totalPages) {
        let html = '';

        html += '<button type="button" class="rpt-page-btn rpt-page-arrow" data-page="' + (currentPage - 1) + '"'
            + (currentPage === 1 ? ' disabled' : '') + ' aria-label="Previous page">&#8249;</button>';

        getPageList(currentPage, totalPages).forEach(function (p) {
            if (p === '...') {
                html += '<span class="rpt-page-dots">...</span>';
            } else {
                html += '<button type="button" class="rpt-page-btn' + (p === currentPage ? ' is-active' : '')
                    + '" data-page="' + p + '">' + p + '</button>';
            }
        });

        html += '<button type="button" class="rpt-page-btn rpt-page-arrow" data-page="' + (currentPage + 1) + '"'
            + (currentPage === totalPages ? ' disabled' : '') + ' aria-label="Next page">&#8250;</button>';

        nav.innerHTML = html;
    }

    // Ito ang nag-aayos ng table: search muna, tapos pagination
    function updateTable() {
        const allRows = page.querySelectorAll('.rpt-approved-row');
        const searchInput = page.querySelector('.rpt-search-input');
        const sizeSelect = page.querySelector('.rpt-page-size');
        const info = page.querySelector('.rpt-pager-info');
        const nav = page.querySelector('.rpt-pager-nav');
        const noMatch = page.querySelector('.rpt-no-match');

        if (sizeSelect) sizeSelect.value = String(pageSize);

        // 1) Hanapin ang mga row na tumutugma sa search
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const matches = [];
        allRows.forEach(function (row) {
            row.style.display = 'none';
            if (row.textContent.toLowerCase().includes(query)) {
                matches.push(row);
            }
        });

        // 2) Ayusin ang current page kung lumampas na
        const totalPages = Math.max(1, Math.ceil(matches.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        // 3) Ipakita lang ang rows ng current page
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        matches.slice(start, end).forEach(function (row) {
            row.style.display = '';
        });

        // 4) "No results" message
        if (noMatch) {
            noMatch.style.display = (allRows.length > 0 && matches.length === 0) ? '' : 'none';
        }

        // 5) "Showing 1-10 of 38" at page buttons
        if (info) {
            if (matches.length === 0) {
                info.innerHTML = 'Showing <strong>0</strong> of <strong>0</strong>';
            } else {
                const showingTo = Math.min(end, matches.length);
                info.innerHTML = 'Showing <strong>' + (start + 1) + '\u2013' + showingTo
                    + '</strong> of <strong>' + matches.length + '</strong>';
            }
        }
        // Laging ipinapakita ang page buttons, kahit isang page lang
        if (nav) {
            renderNav(nav, totalPages);
        }
    }

    monthSelect.addEventListener('change', loadReport);
    yearSelect.addEventListener('change', loadReport);

    // Iwas full page submit (hal. Enter key)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        loadReport();
    });

    // Search. Nasa 'page' nakalagay ang listener, kaya gumagana pa rin
    // kahit napalitan na ang laman ng page ng bagong month
    page.addEventListener('input', function (e) {
        if (!e.target.classList.contains('rpt-search-input')) return;
        currentPage = 1;
        updateTable();
    });

    // Rows per page
    page.addEventListener('change', function (e) {
        if (!e.target.classList.contains('rpt-page-size')) return;
        pageSize = parseInt(e.target.value, 10);
        currentPage = 1;
        updateTable();
    });

    // Page number / prev / next buttons
    page.addEventListener('click', function (e) {
        const btn = e.target.closest('.rpt-page-btn');
        if (!btn || btn.disabled) return;
        currentPage = parseInt(btn.dataset.page, 10);
        updateTable();
    });

    // Para sa unang load ng page
    updateTable();
});