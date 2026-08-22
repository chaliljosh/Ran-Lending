// public/functions/ongoing-loans.js
// Used by: resources/views/admin/ongoing-loans.blade.php
//
// Handles search filtering, sort (newest/oldest), and rows-per-page
// pagination together. Sorting physically reorders the <tr> elements in
// the DOM (via tbody.appendChild), so pagination always re-reads rows in
// their CURRENT DOM order — that way Prev/Next pages stay correct no
// matter which sort is active.

let olCurrentPage = 1;
let olRowsPerPage = 10;

// Rows that match the current search box, in their current DOM order
// (i.e. respecting whatever sort was last applied).
function olGetMatchingRows() {
    const searchInput = document.getElementById('filterSearchInput');
    const query = (searchInput?.value || '').trim().toLowerCase();

    const allRows = Array.from(
        document.querySelectorAll('#loanTableWrap table tbody tr[data-search]')
    );

    return allRows.filter(row => !query || row.dataset.search.includes(query));
}

function filterOngoingLoans() {
    olCurrentPage = 1; // any new search resets back to page 1
    olRenderPage();
}

function sortOngoingLoans() {
    const order = document.getElementById('sortFilter').value;
    const tbody = document.querySelector('#loanTableWrap table tbody');
    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr[data-search]'));
    rows.sort(function (a, b) {
        const aApplied = parseInt(a.dataset.applied, 10) || 0;
        const bApplied = parseInt(b.dataset.applied, 10) || 0;
        return order === 'oldest' ? aApplied - bApplied : bApplied - aApplied;
    });

    rows.forEach(function (row) {
        tbody.appendChild(row);
    });

    olCurrentPage = 1; // re-sorting also resets back to page 1
    olRenderPage();
}

function olChangeRowsPerPage(select) {
    olRowsPerPage = parseInt(select.value, 10) || 10;
    olCurrentPage = 1;
    olRenderPage();
}

function olGoToPage(direction) {
    olCurrentPage += direction;
    olRenderPage();
}

function olRenderPage() {
    const allRows = Array.from(
        document.querySelectorAll('#loanTableWrap table tbody tr[data-search]')
    );
    const matchingRows = olGetMatchingRows();

    // Hide everything first; only the current page's slice gets revealed.
    allRows.forEach(row => row.style.display = 'none');

    const totalRows = matchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / olRowsPerPage));

    if (olCurrentPage > totalPages) olCurrentPage = totalPages;
    if (olCurrentPage < 1) olCurrentPage = 1;

    const start = (olCurrentPage - 1) * olRowsPerPage;
    const end = start + olRowsPerPage;
    matchingRows.slice(start, end).forEach(row => row.style.display = '');

    // Reuse the existing "no results" / table-wrap toggle from the search feature.
    const noResults = document.getElementById('filteredNoResults');
    const tableWrap = document.getElementById('loanTableWrap');
    if (noResults && tableWrap) {
        noResults.style.display = totalRows === 0 ? 'block' : 'none';
        tableWrap.style.display = totalRows === 0 ? 'none' : '';
    }

    olUpdatePaginationControls(totalRows, totalPages, start, end);
}

function olUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('olPaginationInfo');
    const pageIndicator = document.getElementById('olPageIndicator');
    const prevBtn = document.getElementById('olPrevPage');
    const nextBtn = document.getElementById('olNextPage');
    const paginationWrap = document.getElementById('olPagination');

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
        pageIndicator.textContent = `Page ${olCurrentPage} of ${totalPages}`;
    }

    if (prevBtn) prevBtn.disabled = olCurrentPage <= 1;
    if (nextBtn) nextBtn.disabled = olCurrentPage >= totalPages;

    // No point showing the pagination bar if there are zero rows overall
    // (not just filtered out) — the "No ongoing loans yet" empty state
    // already covers that case.
    if (paginationWrap) {
        paginationWrap.style.display = totalRows === 0 ? 'none' : 'flex';
    }
}

// Slice to page 1 (10 rows) as soon as the page loads.
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('loanTableWrap')) {
        olRenderPage();
    }
});