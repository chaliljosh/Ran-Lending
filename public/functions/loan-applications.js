// public/functions/loan-applications.js
// Used by: resources/views/admin/loan-applications.blade.php

function showLoanTab(tab, btn) {
    document.getElementById('loantab-pending').style.display  = tab === 'pending'  ? '' : 'none';
    document.getElementById('loantab-reviewed').style.display = tab === 'reviewed' ? '' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// ========================================
// REVIEWED TAB — search + decision filter + pagination
// Table rows (<tr data-search data-decision>) inside #loanReviewedList.
// loanReviewedMatchingCards holds the current filtered set (before paging).
// filterLoanReviewed() re-runs on every search/decision-filter change and
// resets back to page 1.
// ========================================
let loanReviewedMatchingCards = [];
let loanReviewedCurrentPage = 1;
let loanReviewedRowsPerPage = 10;

function filterLoanReviewed() {
    const searchInput = document.getElementById('loanReviewedSearchInput');
    const decisionSelect = document.getElementById('loanReviewedDecisionFilter');
    const list = document.getElementById('loanReviewedList');
    if (!list) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const decision = decisionSelect?.value || '';

    const allRows = Array.from(list.querySelectorAll('tr[data-search]'));

    loanReviewedMatchingCards = allRows.filter(row => {
        const matchesSearch = !query || row.dataset.search.includes(query);
        const matchesDecision = !decision || row.dataset.decision === decision;
        return matchesSearch && matchesDecision;
    });

    allRows.forEach(row => row.style.display = 'none');

    loanReviewedCurrentPage = 1;
    loanReviewedRenderPage();
}

function loanReviewedChangeRowsPerPage(select) {
    loanReviewedRowsPerPage = parseInt(select.value, 10) || 10;
    loanReviewedCurrentPage = 1;
    loanReviewedRenderPage();
}

function loanReviewedGoToPage(direction) {
    loanReviewedCurrentPage += direction;
    loanReviewedRenderPage();
}

function loanReviewedRenderPage() {
    const emptyState = document.getElementById('loanReviewedEmptyState');
    const totalCards = loanReviewedMatchingCards.length;
    const totalPages = Math.max(1, Math.ceil(totalCards / loanReviewedRowsPerPage));

    if (loanReviewedCurrentPage > totalPages) loanReviewedCurrentPage = totalPages;
    if (loanReviewedCurrentPage < 1) loanReviewedCurrentPage = 1;

    loanReviewedMatchingCards.forEach(row => row.style.display = 'none');

    const start = (loanReviewedCurrentPage - 1) * loanReviewedRowsPerPage;
    const end = start + loanReviewedRowsPerPage;
    loanReviewedMatchingCards.slice(start, end).forEach(row => row.style.display = '');

    if (emptyState) {
        emptyState.style.display = totalCards === 0 ? 'block' : 'none';
    }

    loanReviewedUpdatePaginationControls(totalCards, totalPages, start, end);
}

function loanReviewedUpdatePaginationControls(totalCards, totalPages, start, end) {
    const infoEl = document.getElementById('loanReviewedPaginationInfo');
    const pageIndicator = document.getElementById('loanReviewedPageIndicator');
    const prevBtn = document.getElementById('loanReviewedPrevPage');
    const nextBtn = document.getElementById('loanReviewedNextPage');
    const paginationWrap = document.getElementById('loanReviewedPagination');

    if (infoEl) {
        if (totalCards === 0) {
            infoEl.textContent = 'Showing 0 of 0';
        } else {
            const shownStart = start + 1;
            const shownEnd = Math.min(end, totalCards);
            infoEl.textContent = `Showing ${shownStart}–${shownEnd} of ${totalCards}`;
        }
    }

    if (pageIndicator) {
        pageIndicator.textContent = `Page ${loanReviewedCurrentPage} of ${totalPages}`;
    }

    if (prevBtn) prevBtn.disabled = loanReviewedCurrentPage <= 1;
    if (nextBtn) nextBtn.disabled = loanReviewedCurrentPage >= totalPages;

    if (paginationWrap) {
        const hasActiveFilter = document.getElementById('loanReviewedSearchInput')?.value
            || document.getElementById('loanReviewedDecisionFilter')?.value;
        paginationWrap.style.display = (totalCards === 0 && !hasActiveFilter) ? 'none' : 'flex';
    }
}

// ========================================
// FILTERED VIEW (Approved / Rejected / On Hold) — search + rows-per-page
// + pagination. Same pattern as the Reviewed tab above, just without a
// decision filter (the whole table is already one decision, driven by
// which sidebar link/tab the admin is on).
// Table rows (<tr data-search>) inside #filteredLoanList.
// ========================================
let filteredMatchingRows = [];
let filteredCurrentPage = 1;
let filteredRowsPerPage = 10;

function filterLoanFiltered() {
    const searchInput = document.getElementById('filteredSearchInput');
    const list = document.getElementById('filteredLoanList');
    if (!list) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const allRows = Array.from(list.querySelectorAll('tr[data-search]'));

    filteredMatchingRows = allRows.filter(row => !query || row.dataset.search.includes(query));

    allRows.forEach(row => row.style.display = 'none');

    filteredCurrentPage = 1;
    filteredRenderPage();
}

function filteredChangeRowsPerPage(select) {
    filteredRowsPerPage = parseInt(select.value, 10) || 10;
    filteredCurrentPage = 1;
    filteredRenderPage();
}

function filteredGoToPage(direction) {
    filteredCurrentPage += direction;
    filteredRenderPage();
}

function filteredRenderPage() {
    const emptyState = document.getElementById('filteredEmptyState');
    const totalRows = filteredMatchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / filteredRowsPerPage));

    if (filteredCurrentPage > totalPages) filteredCurrentPage = totalPages;
    if (filteredCurrentPage < 1) filteredCurrentPage = 1;

    filteredMatchingRows.forEach(row => row.style.display = 'none');

    const start = (filteredCurrentPage - 1) * filteredRowsPerPage;
    const end = start + filteredRowsPerPage;
    filteredMatchingRows.slice(start, end).forEach(row => row.style.display = '');

    if (emptyState) {
        emptyState.style.display = totalRows === 0 ? 'block' : 'none';
    }

    filteredUpdatePaginationControls(totalRows, totalPages, start, end);
}

function filteredUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('filteredPaginationInfo');
    const pageIndicator = document.getElementById('filteredPageIndicator');
    const prevBtn = document.getElementById('filteredPrevPage');
    const nextBtn = document.getElementById('filteredNextPage');
    const paginationWrap = document.getElementById('filteredPagination');

    if (infoEl) {
        if (totalRows === 0) {
            infoEl.textContent = 'Showing 0 of 0';
        } else {
            const shownStart = start + 1;
            const shownEnd = Math.min(end, totalRows);
            infoEl.textContent = `Showing ${shownStart}–${shownEnd} of ${totalRows}`;
        }
    }

    if (pageIndicator) {
        pageIndicator.textContent = `Page ${filteredCurrentPage} of ${totalPages}`;
    }

    if (prevBtn) prevBtn.disabled = filteredCurrentPage <= 1;
    if (nextBtn) nextBtn.disabled = filteredCurrentPage >= totalPages;

    if (paginationWrap) {
        const hasActiveFilter = document.getElementById('filteredSearchInput')?.value;
        paginationWrap.style.display = (totalRows === 0 && !hasActiveFilter) ? 'none' : 'flex';
    }
}

// Run pagination once on load for both tables so they're already sliced
// to page 1 even before the user types anything.
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('loanReviewedList')) {
        filterLoanReviewed();
    }
    if (document.getElementById('filteredLoanList')) {
        filterLoanFiltered();
    }
});