// public/functions/loan-applications.js
// Used by: resources/views/admin/loan-applications.blade.php
//
// Mirrors borrower-verification.js exactly: numbered pagination
// (1 2 3 ... N) with prev/next chevrons, rendered dynamically into
// #pendingPaginationControls / #loanReviewedPaginationControls /
// #filteredPaginationControls. The "Showing [rpp] of N results" text
// lives in each toolbar's #pendingPaginationInfo / etc. span — NOT in
// the pagination bar — so the pagination bar only ever contains the
// centered numbered controls (same structure as Borrower Verification).

// ========================================
// SHARED — generic numbered-pagination renderer
// ========================================
function buildPageList(current, total) {
    const delta = 1;
    const range = [];
    const withDots = [];
    let last = null;

    range.push(1);
    for (let i = current - delta; i <= current + delta; i++) {
        if (i > 1 && i < total) range.push(i);
    }
    if (total > 1) range.push(total);

    for (const i of range) {
        if (last !== null) {
            if (i - last === 2) {
                withDots.push(last + 1);
            } else if (i - last > 2) {
                withDots.push('...');
            }
        }
        withDots.push(i);
        last = i;
    }

    return withDots;
}

function renderPageButtons(containerId, currentPage, totalPages, onPageChange) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '';

    if (totalPages <= 0) return;

    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'btn-page';
    prevBtn.innerHTML = '<i class="feather-chevron-left"></i>';
    prevBtn.disabled = currentPage <= 1;
    prevBtn.addEventListener('click', function () {
        if (currentPage > 1) onPageChange(currentPage - 1);
    });
    container.appendChild(prevBtn);

    const pages = buildPageList(currentPage, totalPages);
    pages.forEach(function (p) {
        if (p === '...') {
            const dots = document.createElement('span');
            dots.className = 'rv-page-ellipsis';
            dots.textContent = '....';
            container.appendChild(dots);
            return;
        }

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rv-page-num' + (p === currentPage ? ' active' : '');
        btn.textContent = p;
        btn.addEventListener('click', function () {
            if (p !== currentPage) onPageChange(p);
        });
        container.appendChild(btn);
    });

    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'btn-page';
    nextBtn.innerHTML = '<i class="feather-chevron-right"></i>';
    nextBtn.disabled = currentPage >= totalPages;
    nextBtn.addEventListener('click', function () {
        if (currentPage < totalPages) onPageChange(currentPage + 1);
    });
    container.appendChild(nextBtn);
}

// ========================================
// PENDING TAB — search + rows-per-page + numbered pagination
// Table rows (<tr data-search>) inside #loanPendingList.
// ========================================
let pendingMatchingRows = [];
let pendingCurrentPage = 1;
let pendingRowsPerPage = 10;

function filterLoanPending() {
    const searchInput = document.getElementById('pendingSearchInput');
    const table = document.getElementById('pendingLoanTable');
    if (!table) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const allRows = Array.from(document.querySelectorAll('#loanPendingList tr[data-search]'));

    pendingMatchingRows = allRows.filter(row => !query || row.dataset.search.includes(query));

    allRows.forEach(row => row.style.display = 'none');

    pendingCurrentPage = 1;
    pendingRenderPage();
}

function pendingChangeRowsPerPage(select) {
    pendingRowsPerPage = parseInt(select.value, 10) || 10;
    pendingCurrentPage = 1;
    pendingRenderPage();
}

function pendingGoToPage(page) {
    pendingCurrentPage = page;
    pendingRenderPage();
}

function pendingRenderPage() {
    const table = document.getElementById('pendingLoanTable');
    const emptyState = document.getElementById('pendingEmptyState');
    const totalRows = pendingMatchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / pendingRowsPerPage));

    if (pendingCurrentPage > totalPages) pendingCurrentPage = totalPages;
    if (pendingCurrentPage < 1) pendingCurrentPage = 1;

    pendingMatchingRows.forEach(row => row.style.display = 'none');

    const start = (pendingCurrentPage - 1) * pendingRowsPerPage;
    const end = start + pendingRowsPerPage;
    pendingMatchingRows.slice(start, end).forEach(row => row.style.display = '');

    if (emptyState) emptyState.style.display = totalRows === 0 ? 'block' : 'none';
    if (table) table.style.display = totalRows === 0 ? 'none' : '';

    const infoEl = document.getElementById('pendingPaginationInfo');
    if (infoEl) {
        infoEl.textContent = `of ${totalRows} result${totalRows === 1 ? '' : 's'}`;
    }

    renderPageButtons('pendingPaginationControls', pendingCurrentPage, totalPages, pendingGoToPage);

    const paginationWrap = document.getElementById('pendingPagination');
    if (paginationWrap) {
        const hasActiveFilter = document.getElementById('pendingSearchInput')?.value;
        paginationWrap.style.display = (totalRows === 0 && !hasActiveFilter) ? 'none' : 'flex';
    }
}


// ========================================
// REVIEWED TAB — search + decision filter + rows-per-page + numbered pagination
// Table rows (<tr data-search data-decision>) inside #loanReviewedList.
// ========================================
let loanReviewedMatchingRows = [];
let loanReviewedCurrentPage = 1;
let loanReviewedRowsPerPage = 10;

function filterLoanReviewed() {
    const searchInput = document.getElementById('loanReviewedSearchInput');
    const decisionSelect = document.getElementById('loanReviewedDecisionFilter');
    const table = document.getElementById('loanReviewedTable');
    if (!table) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const decision = decisionSelect?.value || '';

    const allRows = Array.from(document.querySelectorAll('#loanReviewedList tr[data-search]'));

    loanReviewedMatchingRows = allRows.filter(row => {
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

function loanReviewedGoToPage(page) {
    loanReviewedCurrentPage = page;
    loanReviewedRenderPage();
}

function loanReviewedRenderPage() {
    const table = document.getElementById('loanReviewedTable');
    const emptyState = document.getElementById('loanReviewedEmptyState');
    const totalRows = loanReviewedMatchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / loanReviewedRowsPerPage));

    if (loanReviewedCurrentPage > totalPages) loanReviewedCurrentPage = totalPages;
    if (loanReviewedCurrentPage < 1) loanReviewedCurrentPage = 1;

    loanReviewedMatchingRows.forEach(row => row.style.display = 'none');

    const start = (loanReviewedCurrentPage - 1) * loanReviewedRowsPerPage;
    const end = start + loanReviewedRowsPerPage;
    loanReviewedMatchingRows.slice(start, end).forEach(row => row.style.display = '');

    if (emptyState) emptyState.style.display = totalRows === 0 ? 'block' : 'none';
    if (table) table.style.display = totalRows === 0 ? 'none' : '';

    const infoEl = document.getElementById('loanReviewedPaginationInfo');
    if (infoEl) {
        infoEl.textContent = `of ${totalRows} result${totalRows === 1 ? '' : 's'}`;
    }

    renderPageButtons('loanReviewedPaginationControls', loanReviewedCurrentPage, totalPages, loanReviewedGoToPage);

    const paginationWrap = document.getElementById('loanReviewedPagination');
    if (paginationWrap) {
        const hasActiveFilter = document.getElementById('loanReviewedSearchInput')?.value
            || document.getElementById('loanReviewedDecisionFilter')?.value;
        paginationWrap.style.display = (totalRows === 0 && !hasActiveFilter) ? 'none' : 'flex';
    }
}


// ========================================
// FILTERED VIEW (Approved / Rejected / On Hold) —
// search + rows-per-page + numbered pagination.
// Table rows (<tr data-search>) inside #filteredLoanList.
// ========================================
let filteredMatchingRows = [];
let filteredCurrentPage = 1;
let filteredRowsPerPage = 10;

function filterLoanFiltered() {
    const searchInput = document.getElementById('filteredSearchInput');
    const table = document.getElementById('filteredLoanTable');
    if (!table) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const allRows = Array.from(document.querySelectorAll('#filteredLoanList tr[data-search]'));

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

function filteredGoToPage(page) {
    filteredCurrentPage = page;
    filteredRenderPage();
}

function filteredRenderPage() {
    const table = document.getElementById('filteredLoanTable');
    const emptyState = document.getElementById('filteredEmptyState');
    const totalRows = filteredMatchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / filteredRowsPerPage));

    if (filteredCurrentPage > totalPages) filteredCurrentPage = totalPages;
    if (filteredCurrentPage < 1) filteredCurrentPage = 1;

    filteredMatchingRows.forEach(row => row.style.display = 'none');

    const start = (filteredCurrentPage - 1) * filteredRowsPerPage;
    const end = start + filteredRowsPerPage;
    filteredMatchingRows.slice(start, end).forEach(row => row.style.display = '');

    if (emptyState) emptyState.style.display = totalRows === 0 ? 'block' : 'none';
    if (table) table.style.display = totalRows === 0 ? 'none' : '';

    const infoEl = document.getElementById('filteredPaginationInfo');
    if (infoEl) {
        infoEl.textContent = `of ${totalRows} result${totalRows === 1 ? '' : 's'}`;
    }

    renderPageButtons('filteredPaginationControls', filteredCurrentPage, totalPages, filteredGoToPage);

    const paginationWrap = document.getElementById('filteredPagination');
    if (paginationWrap) {
        const hasActiveFilter = document.getElementById('filteredSearchInput')?.value;
        paginationWrap.style.display = (totalRows === 0 && !hasActiveFilter) ? 'none' : 'flex';
    }
}

// Run pagination once on load for whichever table exists on the current
// view (pending / reviewed / filtered) so it's already sliced to page 1
// even before the user types anything in search/filter.
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('pendingLoanTable')) {
        filterLoanPending();
    }
    if (document.getElementById('loanReviewedTable')) {
        filterLoanReviewed();
    }
    if (document.getElementById('filteredLoanTable')) {
        filterLoanFiltered();
    }
});