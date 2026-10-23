(function () {
    const searchInput = document.getElementById('ptSearchInput');
    const monthFilter = document.getElementById('ptMonthFilter');
    const statusFilter = document.getElementById('ptStatusFilter');
    const sortSelect = document.getElementById('ptSortSelect');
    const rowsPerPageSelect = document.getElementById('ptRowsPerPage');
    const tbody = document.getElementById('ptTableBody');
    const tableWrap = document.getElementById('ptTableWrap');
    const emptyState = document.getElementById('ptEmptyState');
    const footer = document.getElementById('ptFooter');
    const showingText = document.getElementById('ptShowingText');
    const pagination = document.getElementById('ptPagination');

    if (!tbody) return;

    const allRows = Array.from(tbody.querySelectorAll('tr.pt-tr'));
    let currentPage = 1;

    function getFilteredSortedRows() {
        const term = (searchInput.value || '').trim().toLowerCase();
        const month = monthFilter.value;
        const status = statusFilter.value;
        const sortBy = sortSelect.value;

        const rows = allRows.filter(function (row) {
            const matchesSearch = !term || row.dataset.borrower.includes(term);
            const matchesMonth = !month || row.dataset.months.split(',').includes(month);
            const matchesStatus = !status || row.dataset.status === status;
            return matchesSearch && matchesMonth && matchesStatus;
        });

        rows.sort(function (a, b) {
            if (sortBy === 'days_desc') return parseInt(b.dataset.days, 10) - parseInt(a.dataset.days, 10);
            if (sortBy === 'days_asc') return parseInt(a.dataset.days, 10) - parseInt(b.dataset.days, 10);
            if (sortBy === 'penalty_desc') return parseFloat(b.dataset.penalty) - parseFloat(a.dataset.penalty);
            if (sortBy === 'penalty_asc') return parseFloat(a.dataset.penalty) - parseFloat(b.dataset.penalty);
            return 0;
        });

        return rows;
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

        html += '<button type="button" class="pt-page-btn" data-page="' + (currentPage - 1) + '"'
            + (currentPage === 1 ? ' disabled' : '') + ' aria-label="Previous page">&#8249;</button>';

        getPageList(currentPage, totalPages).forEach(function (p) {
            if (p === '...') {
                html += '<span class="pt-page-dots">...</span>';
            } else {
                html += '<button type="button" class="pt-page-btn' + (p === currentPage ? ' is-active' : '')
                    + '" data-page="' + p + '">' + p + '</button>';
            }
        });

        html += '<button type="button" class="pt-page-btn" data-page="' + (currentPage + 1) + '"'
            + (currentPage === totalPages ? ' disabled' : '') + ' aria-label="Next page">&#8250;</button>';

        pagination.innerHTML = html;
    }

    function render() {
        const rows = getFilteredSortedRows();
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
            tbody.appendChild(row);
        });

        emptyState.style.display = total === 0 ? 'block' : 'none';
        tableWrap.style.display = total === 0 ? 'none' : '';
        footer.style.display = total === 0 ? 'none' : 'grid';

        showingText.innerHTML = total === 0
            ? 'Showing <strong>0</strong> of <strong>0</strong>'
            : 'Showing <strong>' + (startIdx + 1) + '\u2013' + endIdx + '</strong> of <strong>' + total + '</strong>';

        renderPagination(totalPages);
    }

    [searchInput, monthFilter, statusFilter, sortSelect].forEach(function (el) {
        el.addEventListener('input', function () { currentPage = 1; render(); });
        el.addEventListener('change', function () { currentPage = 1; render(); });
    });
    rowsPerPageSelect.addEventListener('change', function () { currentPage = 1; render(); });

    pagination.addEventListener('click', function (e) {
        const btn = e.target.closest('.pt-page-btn');
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