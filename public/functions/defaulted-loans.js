// public/functions/defaulted-loans.js
// Used by: resources/views/admin/defaulted-loans.blade.php
// Handles search + days-overdue filter + rows-per-page pagination.
// Sort is server-side (page reload via the select's onchange in the blade).

let dlCurrentPage = 1;
let dlRowsPerPage = 10;

function dlGetMatchingRows() {
    const searchInput = document.getElementById('dlSearchInput');
    const query = (searchInput?.value || '').trim().toLowerCase();

    const daysFilter = document.getElementById('dlDaysFilter')?.value || 'all';

    const allRows = Array.from(
        document.querySelectorAll('#dlTableWrap table tbody tr[data-search]')
    );

    return allRows.filter(row => {
        const matchesSearch = !query || row.dataset.search.includes(query);

        const daysLate = parseInt(row.dataset.daysLate, 10) || 0;
        let matchesDays = true;
        if (daysFilter === '90') matchesDays = daysLate >= 90 && daysLate <= 99;
        if (daysFilter === '100') matchesDays = daysLate >= 100;

        return matchesSearch && matchesDays;
    });
}

function dlFilterRows() {
    dlCurrentPage = 1;
    dlRenderPage();
}

function dlChangeRowsPerPage(select) {
    dlRowsPerPage = parseInt(select.value, 10) || 10;
    dlCurrentPage = 1;
    dlRenderPage();
}

function dlGoToPage(direction) {
    dlCurrentPage += direction;
    dlRenderPage();
}

function dlRenderPage() {
    const allRows = Array.from(
        document.querySelectorAll('#dlTableWrap table tbody tr[data-search]')
    );
    const matchingRows = dlGetMatchingRows();

    allRows.forEach(row => row.style.display = 'none');

    const totalRows = matchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / dlRowsPerPage));

    if (dlCurrentPage > totalPages) dlCurrentPage = totalPages;
    if (dlCurrentPage < 1) dlCurrentPage = 1;

    const start = (dlCurrentPage - 1) * dlRowsPerPage;
    const end = start + dlRowsPerPage;
    matchingRows.slice(start, end).forEach(row => row.style.display = '');

    const noResults = document.getElementById('dlNoResults');
    const tableWrap = document.getElementById('dlTableWrap');
    if (noResults && tableWrap) {
        noResults.style.display = totalRows === 0 ? 'block' : 'none';
        tableWrap.style.display = totalRows === 0 ? 'none' : '';
    }

    dlUpdatePaginationControls(totalRows, totalPages, start, end);
}

function dlUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('dlPaginationInfo');
    const pageIndicator = document.getElementById('dlPageIndicator');
    const prevBtn = document.getElementById('dlPrevPage');
    const nextBtn = document.getElementById('dlNextPage');
    const paginationBar = document.getElementById('dlPaginationBar');

    if (infoEl) {
        if (totalRows === 0) {
            infoEl.textContent = 'Showing 0 of 0 entries';
        } else {
            const shownStart = start + 1;
            const shownEnd = Math.min(end, totalRows);
            infoEl.textContent = `Showing ${shownStart}–${shownEnd} of ${totalRows} entries`;
        }
    }

    if (pageIndicator) pageIndicator.textContent = `Page ${dlCurrentPage} of ${totalPages}`;
    if (prevBtn) prevBtn.disabled = dlCurrentPage <= 1;
    if (nextBtn) nextBtn.disabled = dlCurrentPage >= totalPages;

    if (paginationBar) {
        paginationBar.style.display = totalRows === 0 ? 'none' : 'flex';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('dlTableWrap')) {
        dlRenderPage();
    }

    // "Filters" button is a placeholder for now — no advanced filter
    // panel wired up yet. Wire this up once the filter panel UI exists.
    const filtersBtn = document.getElementById('dlFiltersBtn');
    if (filtersBtn) {
        filtersBtn.addEventListener('click', function () {
            console.log('[defaulted-loans.js] Filters button clicked — no panel wired up yet.');
        });
    }
});

// ========================================
// CLICKABLE ROWS — buong <tr> na may data-href ang
// mag-a-navigate papunta sa detail page, maliban kung
// ang na-click ay isang link/button (para hindi
// mag-double-navigate sa "View Details" button).
// Event delegation dahil pinapalitan ng search/filter/
// pagination ang visibility ng rows nang dynamic.
// ========================================
document.addEventListener('click', function (e) {
    var row = e.target.closest('tr[data-href]');
    if (!row) return;

    if (e.target.closest('a, button, input, select, textarea')) return;

    window.location.href = row.dataset.href;
});