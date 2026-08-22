/* ========================================================
   ACTIVE BORROWERS — table search, sort, pagination
   Client-side lang; wala pang backend query params.
   ======================================================== */

(function () {
    let currentPage = 1;
    let rowsPerPage = 10;

    function getAllRows() {
        return Array.from(document.querySelectorAll('#borrowerTableWrap tbody tr[data-search]'));
    }

    function getVisibleRows() {
        // "visible" dito ay yung mga hindi na-filter out ng search (data-match !== 'false')
        return getAllRows().filter(function (row) {
            return row.dataset.match !== 'false';
        });
    }

    function filterActiveBorrowers() {
        const query = document.getElementById('filterSearchInput').value.trim().toLowerCase();
        const rows = getAllRows();

        rows.forEach(function (row) {
            const matches = row.dataset.search.includes(query);
            row.dataset.match = matches ? 'true' : 'false';
        });

        currentPage = 1;
        renderTable();
    }

    function sortActiveBorrowers() {
        const order = document.getElementById('sortFilter').value;
        const tbody = document.querySelector('#borrowerTableWrap table tbody');
        if (!tbody) return;

        const rows = getAllRows();
        rows.sort(function (a, b) {
            const aApplied = parseInt(a.dataset.applied, 10) || 0;
            const bApplied = parseInt(b.dataset.applied, 10) || 0;
            return order === 'oldest' ? aApplied - bApplied : bApplied - aApplied;
        });

        rows.forEach(function (row) {
            tbody.appendChild(row);
        });

        currentPage = 1;
        renderTable();
    }

    function changeRowsPerPage() {
        const select = document.getElementById('rowsPerPageSelect');
        rowsPerPage = select.value === 'all' ? Infinity : parseInt(select.value, 10);
        currentPage = 1;
        renderTable();
    }

    function goToPage(page) {
        currentPage = page;
        renderTable();
    }

    function renderTable() {
        const allRows = getAllRows();
        const visibleRows = getVisibleRows();
        const noResults = document.getElementById('filteredNoResults');
        const tableWrap = document.getElementById('borrowerTableWrap');
        const paginationBar = document.getElementById('rsPaginationBar');

        // hide lahat muna ng rows
        allRows.forEach(function (row) {
            row.style.display = 'none';
        });

        if (visibleRows.length === 0) {
            if (noResults) noResults.style.display = 'block';
            if (tableWrap) tableWrap.style.display = 'none';
            if (paginationBar) paginationBar.style.display = 'none';
            return;
        }

        if (noResults) noResults.style.display = 'none';
        if (tableWrap) tableWrap.style.display = '';

        const totalPages = Math.max(1, Math.ceil(visibleRows.length / rowsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;

        const start = rowsPerPage === Infinity ? 0 : (currentPage - 1) * rowsPerPage;
        const end = rowsPerPage === Infinity ? visibleRows.length : start + rowsPerPage;

        visibleRows.slice(start, end).forEach(function (row) {
            row.style.display = '';
        });

        renderPaginationBar(visibleRows.length, totalPages);
    }

    function renderPaginationBar(totalRows, totalPages) {
        const paginationBar = document.getElementById('rsPaginationBar');
        const infoEl = document.getElementById('rsPaginationInfo');
        const controlsEl = document.getElementById('rsPaginationControls');
        if (!paginationBar || !infoEl || !controlsEl) return;

        paginationBar.style.display = 'flex';

        const start = rowsPerPage === Infinity ? 1 : (currentPage - 1) * rowsPerPage + 1;
        const end = rowsPerPage === Infinity ? totalRows : Math.min(currentPage * rowsPerPage, totalRows);
        infoEl.innerHTML = 'Showing <strong>' + start + '&ndash;' + end + '</strong> of <strong>' + totalRows + '</strong> borrowers';

        controlsEl.innerHTML = '';

        if (totalPages <= 1) return;

        controlsEl.appendChild(makePageBtn('«', currentPage - 1, currentPage === 1, false));

        const pages = getPageList(currentPage, totalPages);
        pages.forEach(function (p) {
            if (p === '...') {
                const span = document.createElement('span');
                span.className = 'rs-page-ellipsis';
                span.textContent = '…';
                controlsEl.appendChild(span);
            } else {
                controlsEl.appendChild(makePageBtn(String(p), p, false, p === currentPage));
            }
        });

        controlsEl.appendChild(makePageBtn('»', currentPage + 1, currentPage === totalPages, false));
    }

    function makePageBtn(label, targetPage, disabled, isActive) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rs-page-btn' + (isActive ? ' active' : '');
        btn.textContent = label;
        btn.disabled = disabled;
        if (!disabled && !isActive) {
            btn.addEventListener('click', function () { goToPage(targetPage); });
        }
        return btn;
    }

    function getPageList(current, total) {
        const delta = 1;
        const range = [];
        const pages = [];
        let last = null;

        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
                range.push(i);
            }
        }

        range.forEach(function (i) {
            if (last !== null && i - last > 1) {
                pages.push('...');
            }
            pages.push(i);
            last = i;
        });

        return pages;
    }

    // init: markahan lahat ng rows na "match" bago mag-render
    document.addEventListener('DOMContentLoaded', function () {
        getAllRows().forEach(function (row) { row.dataset.match = 'true'; });
        renderTable();
    });

    window.filterActiveBorrowers = filterActiveBorrowers;
    window.sortActiveBorrowers = sortActiveBorrowers;
    window.changeRowsPerPage = changeRowsPerPage;
})();