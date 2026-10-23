// public/functions/ongoing-loans.js
// Used by: resources/views/admin/ongoing-loans.blade.php
//
// Handles search + month filter, sorting (dropdown AT click sa column header),
// rows-per-page pagination, at clickable rows.
// Ang mga click ay naka-inline onclick sa blade (hindi document listener),
// para hindi ma-block ng ibang scripts sa layout.

let olCurrentPage = 1;
let olRowsPerPage = 10;

// Current sort state. Default: Newest First.
let olSortKey = 'applied';
let olSortDir = 'desc';

// Rows na tumutugma sa search box at month, sa current DOM order.
function olGetMatchingRows() {
    const searchInput = document.getElementById('filterSearchInput');
    const query = (searchInput?.value || '').trim().toLowerCase();

    // Napiling buwan (hal. "09" = September). Walang laman = All Months
    const month = document.getElementById('monthFilter')?.value || '';

    const allRows = Array.from(
        document.querySelectorAll('#loanTableWrap table tbody tr[data-search]')
    );

    return allRows.filter(function (row) {
        const matchSearch = !query || row.dataset.search.includes(query);
        const matchMonth = !month || row.dataset.month === month;
        return matchSearch && matchMonth;
    });
}

function filterOngoingLoans() {
    olCurrentPage = 1;
    olRenderPage();
}

// Kunin ang value ng row para sa sorting.
// Text ang 'name', number ang lahat ng iba.
function olGetSortValue(row, key) {
    if (key === 'name') {
        return (row.dataset.name || '').toLowerCase();
    }
    return parseFloat(row.dataset[key]) || 0;
}

// Ang mismong pag-sort ng rows sa tbody.
function olSortRows() {
    const tbody = document.querySelector('#loanTableWrap table tbody');
    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr[data-search]'));

    rows.sort(function (a, b) {
        const aVal = olGetSortValue(a, olSortKey);
        const bVal = olGetSortValue(b, olSortKey);

        let result = 0;
        if (aVal < bVal) result = -1;
        if (aVal > bVal) result = 1;

        return olSortDir === 'asc' ? result : -result;
    });

    rows.forEach(function (row) {
        tbody.appendChild(row);
    });

    olCurrentPage = 1;
    olRenderPage();
}

// Kunin ang sort state mula sa dropdown (Newest / Oldest)
function olReadDropdown() {
    const order = document.getElementById('sortFilter').value;

    olSortKey = 'applied';
    olSortDir = order === 'oldest' ? 'asc' : 'desc';
}

// Dropdown sort (Newest / Oldest)
function sortOngoingLoans() {
    olReadDropdown();
    olSortRows();
    olUpdateHeaderArrows();
}

// Click sa column header (tinatawag ng onclick sa <th>)
function olSortByColumn(key) {
    if (olSortKey === key) {
        // Same column: i-toggle ang direction
        olSortDir = olSortDir === 'asc' ? 'desc' : 'asc';
    } else {
        // Bagong column: ascending muna
        olSortKey = key;
        olSortDir = 'asc';
    }

    olSortRows();
    olUpdateHeaderArrows();
    olSyncDropdown();
}

// Ipakita ang arrow (▲/▼) sa active na column
function olUpdateHeaderArrows() {
    const headers = document.querySelectorAll('#loanTableWrap th[data-sort]');

    headers.forEach(function (th) {
        th.classList.remove('sort-asc', 'sort-desc');

        if (th.dataset.sort === olSortKey) {
            th.classList.add(olSortDir === 'asc' ? 'sort-asc' : 'sort-desc');
        }
    });
}

// Kung hindi tugma sa dropdown options ang sort, i-blank ang dropdown
function olSyncDropdown() {
    const select = document.getElementById('sortFilter');
    if (!select) return;

    if (olSortKey === 'applied') {
        select.value = olSortDir === 'asc' ? 'oldest' : 'newest';
    } else {
        select.selectedIndex = -1;
    }
}

// Click sa row (tinatawag ng onclick sa <tr>)
// Pupunta sa detail page, maliban kung link/button ang na-click.
function olGoToRow(event, row) {
    if (event.target.closest('a, button, input, select, textarea')) return;

    const url = row.dataset.href;
    if (url) {
        window.location.href = url;
    }
}

function olChangeRowsPerPage(select) {
    olRowsPerPage = parseInt(select.value, 10) || 10;
    olCurrentPage = 1;
    olRenderPage();
}

// Tinatawag ng onclick ng page buttons
function olSetPage(page) {
    olCurrentPage = page;
    olRenderPage();
}

function olRenderPage() {
    const allRows = Array.from(
        document.querySelectorAll('#loanTableWrap table tbody tr[data-search]')
    );
    const matchingRows = olGetMatchingRows();

    // Itago muna lahat; yung nasa current page lang ang ipapakita.
    allRows.forEach(row => row.style.display = 'none');

    const totalRows = matchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / olRowsPerPage));

    if (olCurrentPage > totalPages) olCurrentPage = totalPages;
    if (olCurrentPage < 1) olCurrentPage = 1;

    const start = (olCurrentPage - 1) * olRowsPerPage;
    const end = start + olRowsPerPage;
    matchingRows.slice(start, end).forEach(row => row.style.display = '');

    const noResults = document.getElementById('filteredNoResults');
    const tableWrap = document.getElementById('loanTableWrap');
    if (noResults && tableWrap) {
        noResults.style.display = totalRows === 0 ? 'block' : 'none';
        tableWrap.style.display = totalRows === 0 ? 'none' : '';
    }

    olUpdatePaginationControls(totalRows, totalPages, start, end);
}

// Gumagawa ng listahan ng page numbers, hal. [1, 2, '...', 4]
function olGetPageList(current, total) {
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

function olRenderPageButtons(nav, totalPages) {
    let html = '';

    html += '<button type="button" class="btn-page-num-v2" onclick="olSetPage(' + (olCurrentPage - 1) + ')"'
        + (olCurrentPage === 1 ? ' disabled' : '') + ' aria-label="Previous page">&#8249;</button>';

    olGetPageList(olCurrentPage, totalPages).forEach(function (p) {
        if (p === '...') {
            html += '<span class="page-dots-v2">...</span>';
        } else {
            html += '<button type="button" class="btn-page-num-v2' + (p === olCurrentPage ? ' is-active' : '')
                + '" onclick="olSetPage(' + p + ')">' + p + '</button>';
        }
    });

    html += '<button type="button" class="btn-page-num-v2" onclick="olSetPage(' + (olCurrentPage + 1) + ')"'
        + (olCurrentPage === totalPages ? ' disabled' : '') + ' aria-label="Next page">&#8250;</button>';

    nav.innerHTML = html;
}

function olUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('olPaginationInfo');
    const nav = document.getElementById('olPaginationNav');
    const paginationWrap = document.getElementById('olPagination');

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
        olRenderPageButtons(nav, totalPages);
    }

    if (paginationWrap) {
        paginationWrap.style.display = totalRows === 0 ? 'none' : 'grid';
    }
}

// Pag-load ng page: i-sort ayon sa dropdown (default Newest First) tapos page 1.
function olInit() {
    if (document.getElementById('loanTableWrap')) {
        olReadDropdown();
        olSortRows();
        olUpdateHeaderArrows();
    }
}

// Tatakbo agad kung tapos na mag-load ang page, kung hindi hintayin muna.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', olInit);
} else {
    olInit();
}