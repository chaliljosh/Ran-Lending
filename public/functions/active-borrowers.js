let abCurrentPage = 1;
let abRowsPerPage = 10;

function abGetAllRows() {
    return Array.from(document.querySelectorAll('#borrowerTableWrap table tbody tr[data-search]'));
}

function abGetMatchingRows() {
    const query = (document.getElementById('filterSearchInput')?.value || '').trim().toLowerCase();
    const month = document.getElementById('monthFilter')?.value || '';

    return abGetAllRows().filter(function (row) {
        const matchSearch = !query || row.dataset.search.includes(query);
        const matchMonth = !month || row.dataset.month === month;
        return matchSearch && matchMonth;
    });
}

function filterActiveBorrowers() {
    abCurrentPage = 1;
    abRenderPage();
}

function sortActiveBorrowers() {
    const order = document.getElementById('sortFilter').value;
    const tbody = document.querySelector('#borrowerTableWrap table tbody');
    if (!tbody) return;

    const rows = abGetAllRows();
    rows.sort(function (a, b) {
        const aApplied = parseInt(a.dataset.applied, 10) || 0;
        const bApplied = parseInt(b.dataset.applied, 10) || 0;
        return order === 'oldest' ? aApplied - bApplied : bApplied - aApplied;
    });

    rows.forEach(function (row) {
        tbody.appendChild(row);
    });

    abCurrentPage = 1;
    abRenderPage();
}

function abChangeRowsPerPage(select) {
    abRowsPerPage = parseInt(select.value, 10) || 10;
    abCurrentPage = 1;
    abRenderPage();
}

function abRenderPage() {
    const allRows = abGetAllRows();
    const matchingRows = abGetMatchingRows();

    allRows.forEach(row => row.style.display = 'none');

    const totalRows = matchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / abRowsPerPage));

    if (abCurrentPage > totalPages) abCurrentPage = totalPages;
    if (abCurrentPage < 1) abCurrentPage = 1;

    const start = (abCurrentPage - 1) * abRowsPerPage;
    const end = start + abRowsPerPage;
    matchingRows.slice(start, end).forEach(row => row.style.display = '');

    const noResults = document.getElementById('filteredNoResults');
    const tableWrap = document.getElementById('borrowerTableWrap');
    if (noResults && tableWrap) {
        noResults.style.display = totalRows === 0 ? 'block' : 'none';
        tableWrap.style.display = totalRows === 0 ? 'none' : '';
    }

    abUpdatePaginationControls(totalRows, totalPages, start, end);
}

function abGetPageList(current, total) {
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

function abRenderPageButtons(nav, totalPages) {
    let html = '';

    html += '<button type="button" class="btn-page-num-v2" data-page="' + (abCurrentPage - 1) + '"'
        + (abCurrentPage === 1 ? ' disabled' : '') + ' aria-label="Previous page">&#8249;</button>';

    abGetPageList(abCurrentPage, totalPages).forEach(function (p) {
        if (p === '...') {
            html += '<span class="page-dots-v2">...</span>';
        } else {
            html += '<button type="button" class="btn-page-num-v2' + (p === abCurrentPage ? ' is-active' : '')
                + '" data-page="' + p + '">' + p + '</button>';
        }
    });

    html += '<button type="button" class="btn-page-num-v2" data-page="' + (abCurrentPage + 1) + '"'
        + (abCurrentPage === totalPages ? ' disabled' : '') + ' aria-label="Next page">&#8250;</button>';

    nav.innerHTML = html;
}

function abUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('abPaginationInfo');
    const nav = document.getElementById('abPaginationNav');
    const paginationWrap = document.getElementById('abPagination');

    if (infoEl) {
        if (totalRows === 0) {
            infoEl.innerHTML = 'Showing <strong>0</strong> of <strong>0</strong>';
        } else {
            infoEl.innerHTML = 'Showing <strong>' + (start + 1) + '\u2013' + Math.min(end, totalRows)
                + '</strong> of <strong>' + totalRows + '</strong>';
        }
    }

    if (nav) abRenderPageButtons(nav, totalPages);

    if (paginationWrap) {
        paginationWrap.style.display = totalRows === 0 ? 'none' : 'grid';
    }
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('#abPaginationNav .btn-page-num-v2');
    if (!btn || btn.disabled) return;

    abCurrentPage = parseInt(btn.dataset.page, 10);
    abRenderPage();
});

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('borrowerTableWrap')) {
        abRenderPage();
    }
});

document.addEventListener('click', function (e) {
    const row = e.target.closest('tr[data-href]');
    if (!row) return;
    if (e.target.closest('a, button, input, select, textarea')) return;
    if (window.getSelection && window.getSelection().toString()) return;
    window.location.href = row.dataset.href;
});