(function () {
    const searchInput = document.getElementById('dlSearchInput');
    const daysFilter = document.getElementById('dlDaysFilter');
    const sortSelect = document.getElementById('dlSortSelect');
    const rowsPerPageSelect = document.getElementById('dlRowsPerPage');
    const tbody = document.getElementById('dlTableBody');
    const tableWrap = document.getElementById('dlTableWrap');
    const emptyState = document.getElementById('dlEmptyState');
    const footer = document.getElementById('dlFooter');
    const showingText = document.getElementById('dlShowingText');
    const pagination = document.getElementById('dlPagination');

    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            window.location.href = sortSelect.dataset.baseUrl + '?sort=' + encodeURIComponent(sortSelect.value);
        });
    }

    if (!tbody) return;

    const allRows = Array.from(tbody.querySelectorAll('tr.dl-tr'));
    let currentPage = 1;

    function getFilteredRows() {
        const term = (searchInput.value || '').trim().toLowerCase();
        const range = daysFilter.value;

        return allRows.filter(function (row) {
            const matchesSearch = !term || row.dataset.search.includes(term);
            const days = parseInt(row.dataset.days, 10) || 0;

            let matchesDays = true;
            if (range === '90') matchesDays = days >= 90 && days <= 99;
            if (range === '100') matchesDays = days >= 100;

            return matchesSearch && matchesDays;
        });
    }

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

    function renderPagination(totalPages) {
        let html = '';

        html += '<button type="button" class="dl-page-btn" data-page="' + (currentPage - 1) + '"'
            + (currentPage === 1 ? ' disabled' : '') + ' aria-label="Previous page">&#8249;</button>';

        getPageList(currentPage, totalPages).forEach(function (p) {
            if (p === '...') {
                html += '<span class="dl-page-dots">...</span>';
            } else {
                html += '<button type="button" class="dl-page-btn' + (p === currentPage ? ' is-active' : '')
                    + '" data-page="' + p + '">' + p + '</button>';
            }
        });

        html += '<button type="button" class="dl-page-btn" data-page="' + (currentPage + 1) + '"'
            + (currentPage === totalPages ? ' disabled' : '') + ' aria-label="Next page">&#8250;</button>';

        pagination.innerHTML = html;
    }

    function render() {
        const rows = getFilteredRows();
        const rowsPerPage = parseInt(rowsPerPageSelect.value, 10) || 10;
        const total = rows.length;
        const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = Math.min(startIdx + rowsPerPage, total);

        allRows.forEach(function (row) { row.style.display = 'none'; });

        rows.slice(startIdx, endIdx).forEach(function (row) {
            row.style.display = '';
        });

        emptyState.style.display = total === 0 ? 'block' : 'none';
        tableWrap.style.display = total === 0 ? 'none' : '';
        footer.style.display = total === 0 ? 'none' : 'grid';

        showingText.innerHTML = total === 0
            ? 'Showing <strong>0</strong> of <strong>0</strong>'
            : 'Showing <strong>' + (startIdx + 1) + '\u2013' + endIdx + '</strong> of <strong>' + total + '</strong>';

        renderPagination(totalPages);
    }

    searchInput.addEventListener('input', function () { currentPage = 1; render(); });
    daysFilter.addEventListener('change', function () { currentPage = 1; render(); });
    rowsPerPageSelect.addEventListener('change', function () { currentPage = 1; render(); });

    pagination.addEventListener('click', function (e) {
        const btn = e.target.closest('.dl-page-btn');
        if (!btn || btn.disabled) return;
        currentPage = parseInt(btn.dataset.page, 10);
        render();
    });

    function openRowDetail(row) {
        const href = row.getAttribute('data-href');
        if (href) window.location.href = href;
    }

    allRows.forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button')) return;
            if (window.getSelection && window.getSelection().toString()) return;
            openRowDetail(row);
        });
        row.addEventListener('keydown', function (e) {
            if (e.target !== row) return;
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openRowDetail(row);
            }
        });
        row.setAttribute('tabindex', '0');
    });

    render();
})();