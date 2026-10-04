// public/functions/overdue-payments.js
// Used by: resources/views/admin/overdue-payments.blade.php
//
// Handles search filtering, sort (most days late / newest / oldest), and
// rows-per-page pagination together. Sorting physically reorders the
// <tr> elements in the DOM (via tbody.appendChild), so pagination always
// re-reads rows in their CURRENT DOM order — that way Prev/Next pages
// stay correct no matter which sort is active.

let opCurrentPage = 1;
let opRowsPerPage = 10;

// Rows that match the current search box, in their current DOM order
// (i.e. respecting whatever sort was last applied).
function opGetMatchingRows() {
    const searchInput = document.getElementById('filterSearchInput');
    const query = (searchInput?.value || '').trim().toLowerCase();

    const allRows = Array.from(
        document.querySelectorAll('#loanTableWrap table tbody tr[data-search]')
    );

    return allRows.filter(row => !query || row.dataset.search.includes(query));
}

function filterOverdueLoans() {
    opCurrentPage = 1; // any new search resets back to page 1
    opRenderPage();
}

function sortOverdueLoans() {
    const order = document.getElementById('sortFilter').value;
    const tbody = document.querySelector('#loanTableWrap table tbody');
    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr[data-search]'));
    rows.sort(function (a, b) {
        if (order === 'most_late') {
            const aLate = parseInt(a.dataset.daysLate, 10) || 0;
            const bLate = parseInt(b.dataset.daysLate, 10) || 0;
            return bLate - aLate;
        }
        const aApplied = parseInt(a.dataset.applied, 10) || 0;
        const bApplied = parseInt(b.dataset.applied, 10) || 0;
        return order === 'oldest' ? aApplied - bApplied : bApplied - aApplied;
    });

    rows.forEach(function (row) {
        tbody.appendChild(row);
    });

    opCurrentPage = 1; // re-sorting also resets back to page 1
    opRenderPage();
}

function opChangeRowsPerPage(select) {
    opRowsPerPage = parseInt(select.value, 10) || 10;
    opCurrentPage = 1;
    opRenderPage();
}

function opGoToPage(direction) {
    opCurrentPage += direction;
    opRenderPage();
}

function opRenderPage() {
    const allRows = Array.from(
        document.querySelectorAll('#loanTableWrap table tbody tr[data-search]')
    );
    const matchingRows = opGetMatchingRows();

    // Hide everything first; only the current page's slice gets revealed.
    allRows.forEach(row => row.style.display = 'none');

    const totalRows = matchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / opRowsPerPage));

    if (opCurrentPage > totalPages) opCurrentPage = totalPages;
    if (opCurrentPage < 1) opCurrentPage = 1;

    const start = (opCurrentPage - 1) * opRowsPerPage;
    const end = start + opRowsPerPage;
    matchingRows.slice(start, end).forEach(row => row.style.display = '');

    // Reuse the existing "no results" / table-wrap toggle from the search feature.
    const noResults = document.getElementById('filteredNoResults');
    const tableWrap = document.getElementById('loanTableWrap');
    if (noResults && tableWrap) {
        noResults.style.display = totalRows === 0 ? 'block' : 'none';
        tableWrap.style.display = totalRows === 0 ? 'none' : '';
    }

    opUpdatePaginationControls(totalRows, totalPages, start, end);
}

function opUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('opPaginationInfo');
    const pageIndicator = document.getElementById('opPageIndicator');
    const prevBtn = document.getElementById('opPrevPage');
    const nextBtn = document.getElementById('opNextPage');
    const paginationWrap = document.getElementById('opPagination');

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
        pageIndicator.textContent = `Page ${opCurrentPage} of ${totalPages}`;
    }

    if (prevBtn) prevBtn.disabled = opCurrentPage <= 1;
    if (nextBtn) nextBtn.disabled = opCurrentPage >= totalPages;

    // No point showing the pagination bar if there are zero rows overall
    // (not just filtered out) — the "no overdue loans" empty state
    // already covers that case.
    if (paginationWrap) {
        paginationWrap.style.display = totalRows === 0 ? 'none' : 'flex';
    }
}

// Slice to page 1 (10 rows) as soon as the page loads.
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('loanTableWrap')) {
        opRenderPage();
    }
});

// ========================================
// CLICKABLE ROWS — buong <tr> na may data-href ang
// mag-a-navigate papunta sa detail page, maliban kung
// ang na-click ay isang link/button (para hindi
// mag-double-navigate sa "View Details" button).
// Event delegation dahil pinapalitan ng search/sort/
// pagination ang visibility ng rows nang dynamic.
// ========================================
document.addEventListener('click', function (e) {
    var row = e.target.closest('tr[data-href]');
    if (!row) return;

    if (e.target.closest('a, button, input, select, textarea')) return;

    window.location.href = row.dataset.href;
});