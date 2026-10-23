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

    // Napiling buwan (hal. "09" = September). Walang laman = All Months
    const month = document.getElementById('monthFilter')?.value || '';

    return allRows.filter(function (row) {
        const matchSearch = !query || row.dataset.search.includes(query);
        const matchMonth = !month || row.dataset.month === month;
        return matchSearch && matchMonth;
    });
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

// Tinatawag ng onclick ng page buttons (inline, gaya ng ibang click dito)
function opSetPage(page) {
    opCurrentPage = page;
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

// Gumagawa ng listahan ng page numbers, hal. [1, 2, '...', 4]
function opGetPageList(current, total) {
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

function opRenderPageButtons(nav, totalPages) {
    let html = '';

    html += '<button type="button" class="btn-page-num-v2" onclick="opSetPage(' + (opCurrentPage - 1) + ')"'
        + (opCurrentPage === 1 ? ' disabled' : '') + ' aria-label="Previous page">&#8249;</button>';

    opGetPageList(opCurrentPage, totalPages).forEach(function (p) {
        if (p === '...') {
            html += '<span class="page-dots-v2">...</span>';
        } else {
            html += '<button type="button" class="btn-page-num-v2' + (p === opCurrentPage ? ' is-active' : '')
                + '" onclick="opSetPage(' + p + ')">' + p + '</button>';
        }
    });

    html += '<button type="button" class="btn-page-num-v2" onclick="opSetPage(' + (opCurrentPage + 1) + ')"'
        + (opCurrentPage === totalPages ? ' disabled' : '') + ' aria-label="Next page">&#8250;</button>';

    nav.innerHTML = html;
}

function opUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('opPaginationInfo');
    const nav = document.getElementById('opPaginationNav');
    const paginationWrap = document.getElementById('opPagination');

    if (infoEl) {
        if (totalRows === 0) {
            infoEl.innerHTML = 'Showing <strong>0</strong> of <strong>0</strong>';
        } else {
            const shownStart = start + 1;
            const shownEnd = Math.min(end, totalRows);
            infoEl.innerHTML = 'Showing <strong>' + shownStart + '\u2013' + shownEnd
                + '</strong> of <strong>' + totalRows + '</strong>';
        }
    }

    if (nav) {
        opRenderPageButtons(nav, totalPages);
    }

    if (paginationWrap) {
        paginationWrap.style.display = totalRows === 0 ? 'none' : 'grid';
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