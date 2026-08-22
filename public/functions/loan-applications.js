// Loan Applications — admin page behavior
// Handles: Pending/Reviewed tab switching, and search + rows-per-page +
// pagination for the Pending tab, the Reviewed tab, and the filtered
// Approved/Rejected/On Hold views.

// =========================================================
// TAB SWITCHING (Pending / Reviewed)
// =========================================================

function showLoanTab(tab, btn) {
    const pendingTab = document.getElementById('loantab-pending');
    const reviewedTab = document.getElementById('loantab-reviewed');

    if (pendingTab) pendingTab.style.display = (tab === 'pending') ? '' : 'none';
    if (reviewedTab) reviewedTab.style.display = (tab === 'reviewed') ? '' : 'none';

    document.querySelectorAll('.la-tabs .tab-btn').forEach(function (el) {
        el.classList.remove('active');
    });
    if (btn) btn.classList.add('active');
}


// =========================================================
// PENDING TAB — search filter + rows-per-page + pagination
// =========================================================
(function () {
    'use strict';

    let currentPage = 1;
    let rowsPerPage = 10;

    window.filterLoanPending = function () {
        currentPage = 1;
        renderPendingTable();
    };

    window.pendingChangeRowsPerPage = function (select) {
        rowsPerPage = parseInt(select.value, 10);
        currentPage = 1;
        renderPendingTable();
    };

    window.pendingGoToPage = function (direction) {
        currentPage += direction;
        renderPendingTable();
    };

    function renderPendingTable() {
        const table = document.getElementById('pendingLoanTable');
        if (!table) return;

        const searchInput = document.getElementById('pendingSearchInput');
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        const rows = Array.from(document.querySelectorAll('#loanPendingList tr'));

        const filtered = rows.filter(function (row) {
            const haystack = row.getAttribute('data-search') || '';
            return !query || haystack.includes(query);
        });

        const totalRows = filtered.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const pageRows = filtered.slice(start, end);

        rows.forEach(function (row) { row.style.display = 'none'; });
        pageRows.forEach(function (row) { row.style.display = ''; });

        const emptyState = document.getElementById('pendingEmptyState');
        const tableWrap = table;
        if (emptyState) emptyState.style.display = totalRows === 0 ? 'block' : 'none';
        tableWrap.style.display = totalRows === 0 ? 'none' : '';

        const infoEl = document.getElementById('pendingPaginationInfo');
        if (infoEl) {
            infoEl.textContent = totalRows === 0
                ? 'Showing 0 of 0'
                : 'Showing ' + (start + 1) + '\u2013' + Math.min(end, totalRows) + ' of ' + totalRows;
        }

        const pageIndicator = document.getElementById('pendingPageIndicator');
        if (pageIndicator) {
            pageIndicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        }

        const prevBtn = document.getElementById('pendingPrevPage');
        const nextBtn = document.getElementById('pendingNextPage');
        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('pendingLoanTable')) {
            renderPendingTable();
        }
    });
})();


// =========================================================
// REVIEWED TAB — search + decision filter + rows-per-page + pagination
// =========================================================
(function () {
    'use strict';

    let currentPage = 1;
    let rowsPerPage = 10;

    window.filterLoanReviewed = function () {
        currentPage = 1;
        renderReviewedTable();
    };

    window.loanReviewedChangeRowsPerPage = function (select) {
        rowsPerPage = parseInt(select.value, 10);
        currentPage = 1;
        renderReviewedTable();
    };

    window.loanReviewedGoToPage = function (direction) {
        currentPage += direction;
        renderReviewedTable();
    };

    function renderReviewedTable() {
        const table = document.getElementById('loanReviewedTable');
        if (!table) return;

        const searchInput = document.getElementById('loanReviewedSearchInput');
        const decisionFilter = document.getElementById('loanReviewedDecisionFilter');

        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const decision = decisionFilter ? decisionFilter.value : '';

        const rows = Array.from(document.querySelectorAll('#loanReviewedList tr'));

        const filtered = rows.filter(function (row) {
            const haystack = row.getAttribute('data-search') || '';
            const rowDecision = row.getAttribute('data-decision') || '';
            const matchesQuery = !query || haystack.includes(query);
            const matchesDecision = !decision || rowDecision === decision;
            return matchesQuery && matchesDecision;
        });

        const totalRows = filtered.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const pageRows = filtered.slice(start, end);

        rows.forEach(function (row) { row.style.display = 'none'; });
        pageRows.forEach(function (row) { row.style.display = ''; });

        const emptyState = document.getElementById('loanReviewedEmptyState');
        if (emptyState) emptyState.style.display = totalRows === 0 ? 'block' : 'none';
        table.style.display = totalRows === 0 ? 'none' : '';

        const infoEl = document.getElementById('loanReviewedPaginationInfo');
        if (infoEl) {
            infoEl.textContent = totalRows === 0
                ? 'Showing 0 of 0'
                : 'Showing ' + (start + 1) + '\u2013' + Math.min(end, totalRows) + ' of ' + totalRows;
        }

        const pageIndicator = document.getElementById('loanReviewedPageIndicator');
        if (pageIndicator) {
            pageIndicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        }

        const prevBtn = document.getElementById('loanReviewedPrevPage');
        const nextBtn = document.getElementById('loanReviewedNextPage');
        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('loanReviewedTable')) {
            renderReviewedTable();
        }
    });
})();


// =========================================================
// FILTERED VIEW (Approved / Rejected / On Hold) —
// search + rows-per-page + pagination
// =========================================================
(function () {
    'use strict';

    let currentPage = 1;
    let rowsPerPage = 10;

    window.filterLoanFiltered = function () {
        currentPage = 1;
        renderFilteredTable();
    };

    window.filteredChangeRowsPerPage = function (select) {
        rowsPerPage = parseInt(select.value, 10);
        currentPage = 1;
        renderFilteredTable();
    };

    window.filteredGoToPage = function (direction) {
        currentPage += direction;
        renderFilteredTable();
    };

    function renderFilteredTable() {
        const table = document.getElementById('filteredLoanTable');
        if (!table) return;

        const searchInput = document.getElementById('filteredSearchInput');
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        const rows = Array.from(document.querySelectorAll('#filteredLoanList tr'));

        const filtered = rows.filter(function (row) {
            const haystack = row.getAttribute('data-search') || '';
            return !query || haystack.includes(query);
        });

        const totalRows = filtered.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const pageRows = filtered.slice(start, end);

        rows.forEach(function (row) { row.style.display = 'none'; });
        pageRows.forEach(function (row) { row.style.display = ''; });

        const emptyState = document.getElementById('filteredEmptyState');
        if (emptyState) emptyState.style.display = totalRows === 0 ? 'block' : 'none';
        table.style.display = totalRows === 0 ? 'none' : '';

        const infoEl = document.getElementById('filteredPaginationInfo');
        if (infoEl) {
            infoEl.textContent = totalRows === 0
                ? 'Showing 0 of 0'
                : 'Showing ' + (start + 1) + '\u2013' + Math.min(end, totalRows) + ' of ' + totalRows;
        }

        const pageIndicator = document.getElementById('filteredPageIndicator');
        if (pageIndicator) {
            pageIndicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        }

        const prevBtn = document.getElementById('filteredPrevPage');
        const nextBtn = document.getElementById('filteredNextPage');
        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('filteredLoanTable')) {
            renderFilteredTable();
        }
    });
})();