// public/functions/overdue-payments.js
// Used by: resources/views/admin/overdue-payments.blade.php
//
// Handles search filtering, sorting (dropdown AT click sa column header),
// rows-per-page pagination, at clickable rows.
// Ang mga click ay naka-inline onclick sa blade (hindi document listener),
// para hindi ma-block ng ibang scripts sa layout.

let opCurrentPage = 1;
let opRowsPerPage = 10;

// Current sort state. Default: Most Days Late.
let opSortKey = 'daysLate';
let opSortDir = 'desc';

// Rows na tumutugma sa search box, sa current DOM order.
function opGetMatchingRows() {
    const searchInput = document.getElementById('filterSearchInput');
    const query = (searchInput?.value || '').trim().toLowerCase();

    const allRows = Array.from(
        document.querySelectorAll('#loanTableWrap table tbody tr[data-search]')
    );

    return allRows.filter(row => !query || row.dataset.search.includes(query));
}

function filterOverdueLoans() {
    opCurrentPage = 1;
    opRenderPage();
}

// Kunin ang value ng row para sa sorting.
// Text ang 'name', number ang lahat ng iba.
function opGetSortValue(row, key) {
    if (key === 'name') {
        return (row.dataset.name || '').toLowerCase();
    }
    return parseFloat(row.dataset[key]) || 0;
}

// Ang mismong pag-sort ng rows sa tbody.
function opSortRows() {
    const tbody = document.querySelector('#loanTableWrap table tbody');
    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr[data-search]'));

    rows.sort(function (a, b) {
        const aVal = opGetSortValue(a, opSortKey);
        const bVal = opGetSortValue(b, opSortKey);

        let result = 0;
        if (aVal < bVal) result = -1;
        if (aVal > bVal) result = 1;

        return opSortDir === 'asc' ? result : -result;
    });

    rows.forEach(function (row) {
        tbody.appendChild(row);
    });

    opCurrentPage = 1;
    opRenderPage();
}

// Dropdown sort (Most Days Late / Newest / Oldest)
function sortOverdueLoans() {
    const order = document.getElementById('sortFilter').value;

    if (order === 'most_late') {
        opSortKey = 'daysLate';
        opSortDir = 'desc';
    } else if (order === 'oldest') {
        opSortKey = 'applied';
        opSortDir = 'asc';
    } else {
        opSortKey = 'applied';
        opSortDir = 'desc';
    }

    opSortRows();
    opUpdateHeaderArrows();
}

// Click sa column header (tinatawag ng onclick sa <th>)
function opSortByColumn(key) {
    if (opSortKey === key) {
        // Same column: i-toggle ang direction
        opSortDir = opSortDir === 'asc' ? 'desc' : 'asc';
    } else {
        // Bagong column: ascending muna
        opSortKey = key;
        opSortDir = 'asc';
    }

    opSortRows();
    opUpdateHeaderArrows();
    opSyncDropdown();
}

// Ipakita ang arrow (▲/▼) sa active na column
function opUpdateHeaderArrows() {
    const headers = document.querySelectorAll('#loanTableWrap th[data-sort]');

    headers.forEach(function (th) {
        th.classList.remove('sort-asc', 'sort-desc');

        if (th.dataset.sort === opSortKey) {
            th.classList.add(opSortDir === 'asc' ? 'sort-asc' : 'sort-desc');
        }
    });
}

// Kung hindi tugma sa dropdown options ang sort, i-blank ang dropdown
function opSyncDropdown() {
    const select = document.getElementById('sortFilter');
    if (!select) return;

    if (opSortKey === 'daysLate' && opSortDir === 'desc') {
        select.value = 'most_late';
    } else {
        select.selectedIndex = -1;
    }
}

// Click sa row (tinatawag ng onclick sa <tr>)
// Pupunta sa detail page, maliban kung link/button ang na-click.
function opGoToRow(event, row) {
    if (event.target.closest('a, button, input, select, textarea')) return;

    const url = row.dataset.href;
    if (url) {
        window.location.href = url;
    }
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

    // Itago muna lahat; yung nasa current page lang ang ipapakita.
    allRows.forEach(row => row.style.display = 'none');

    const totalRows = matchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / opRowsPerPage));

    if (opCurrentPage > totalPages) opCurrentPage = totalPages;
    if (opCurrentPage < 1) opCurrentPage = 1;

    const start = (opCurrentPage - 1) * opRowsPerPage;
    const end = start + opRowsPerPage;
    matchingRows.slice(start, end).forEach(row => row.style.display = '');

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

    if (paginationWrap) {
        paginationWrap.style.display = totalRows === 0 ? 'none' : 'flex';
    }
}

// Pag-load ng page: i-sort ayon sa default (Most Days Late) tapos page 1.
function opInit() {
    if (document.getElementById('loanTableWrap')) {
        opSortRows();
        opUpdateHeaderArrows();
    }
}

// Tatakbo agad kung tapos na mag-load ang page, kung hindi hintayin muna.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', opInit);
} else {
    opInit();
}