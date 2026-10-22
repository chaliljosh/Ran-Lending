// ============================================================
// PENALTY TRACKING PAGE — JavaScript
// Ginagamit sa: penalty-tracking.blade.php
//
// View-only na ang page na ito. May 3 na parte:
//   1. Search / Filter / Sort / Pagination ng table
//   2. Clickable rows (papunta sa detail page)
// (Wala nang Mark as Paid at Waive dito. Nasa Overdue Payment page na.)
// ============================================================

(function () {
    // ---- Mga elemento na madalas gamitin, kinuha lang minsan ----
    const searchInput = document.getElementById('ptSearchInput');
    const monthFilter = document.getElementById('ptMonthFilter');
    const statusFilter = document.getElementById('ptStatusFilter');
    const sortSelect = document.getElementById('ptSortSelect');
    const rowsPerPageSelect = document.getElementById('ptRowsPerPage');
    const tbody = document.getElementById('ptTableBody');
    const emptyState = document.getElementById('ptEmptyState');
    const showingText = document.getElementById('ptShowingText');
    const pagination = document.getElementById('ptPagination');

    // Isang row kada loan (yung mga nakatagong installment rows ay hiwalay)
    const allRows = Array.from(tbody.querySelectorAll('tr.pt-tr'));
    let currentPage = 1;

    /* ============================================================
       1. SEARCH / FILTER / SORT / PAGINATE
    ============================================================ */

    function getFilteredSortedRows() {
        const term = (searchInput.value || '').trim().toLowerCase();
        const month = monthFilter.value;
        const status = statusFilter.value;
        const sortBy = sortSelect.value;

        let rows = allRows.filter(function (row) {
            const matchesSearch = !term || row.dataset.borrower.includes(term);
            // may listahan ng buwan ang row (hal. "7,8,9"), kaya hinahanap lang kung nandun
            const matchesMonth = !month || row.dataset.months.split(',').includes(month);
            const matchesStatus = !status || row.dataset.status === status;
            return matchesSearch && matchesMonth && matchesStatus;
        });

        rows.sort(function (a, b) {
            if (sortBy === 'days_desc') return parseInt(b.dataset.days) - parseInt(a.dataset.days);
            if (sortBy === 'days_asc') return parseInt(a.dataset.days) - parseInt(b.dataset.days);
            if (sortBy === 'penalty_desc') return parseFloat(b.dataset.penalty) - parseFloat(a.dataset.penalty);
            if (sortBy === 'penalty_asc') return parseFloat(a.dataset.penalty) - parseFloat(b.dataset.penalty);
            return 0;
        });

        return rows;
    }

    // Gumagawa ng "Previous / 1 2 3 / Next" na buttons sa ilalim ng table
    function renderPagination(totalPages) {
        pagination.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'pt-page-btn';
        prevBtn.textContent = 'Previous';
        prevBtn.disabled = currentPage <= 1;
        prevBtn.addEventListener('click', function () { currentPage--; render(); });
        pagination.appendChild(prevBtn);

        const maxButtons = 5;
        let start = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let end = Math.min(totalPages, start + maxButtons - 1);
        start = Math.max(1, end - maxButtons + 1);

        for (let i = start; i <= end; i++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pt-page-btn' + (i === currentPage ? ' is-active' : '');
            btn.textContent = i;
            btn.addEventListener('click', function () { currentPage = i; render(); });
            pagination.appendChild(btn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'pt-page-btn';
        nextBtn.textContent = 'Next';
        nextBtn.disabled = currentPage >= totalPages || totalPages === 0;
        nextBtn.addEventListener('click', function () { currentPage++; render(); });
        pagination.appendChild(nextBtn);
    }

    // Ang pangunahing function: nagpapakita/nagtatago ng tamang mga row bawat page
    function render() {
        const rows = getFilteredSortedRows();
        const rowsPerPage = parseInt(rowsPerPageSelect.value, 10);
        const total = rows.length;
        const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = Math.min(startIdx + rowsPerPage, total);
        const pageRows = rows.slice(startIdx, endIdx);

        // Itago muna lahat
        allRows.forEach(function (row) { row.style.display = 'none'; });

        // Ipakita lang yung nasa kasalukuyang page
        pageRows.forEach(function (row) {
            row.style.display = '';
            tbody.appendChild(row);
        });

        emptyState.style.display = total === 0 ? '' : 'none';
        tbody.parentElement.style.display = total === 0 ? 'none' : '';

        showingText.textContent = total === 0
            ? 'Showing 0–0 of 0 records'
            : 'Showing ' + (startIdx + 1) + '–' + endIdx + ' of ' + total + ' records';

        renderPagination(totalPages);
    }

    // ---- I-connect lahat ng filter/sort controls sa render() ----
    [searchInput, monthFilter, statusFilter, sortSelect].forEach(function (el) {
        el.addEventListener('input', function () { currentPage = 1; render(); });
        el.addEventListener('change', function () { currentPage = 1; render(); });
    });
    rowsPerPageSelect.addEventListener('change', function () { currentPage = 1; render(); });

    /* ============================================================
       2. CLICKABLE ROWS
       Pag na-click ang row, pupunta sa Penalty Detail page (data-href).
       Hindi gagana kung ang na-click ay ang Actions column
       (may sarili siyang View Details link), o kung may
       hinihighlight na text.
    ============================================================ */

    function openRowDetail(row) {
        const href = row.getAttribute('data-href');
        if (href) window.location.href = href;
    }

    allRows.forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.pt-cell-actions')) return;
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