let clCurrentPage = 1;
let clRowsPerPage = 10;

function clGetMatchingRows() {
    const searchInput = document.getElementById('filterSearchInput');
    const query = (searchInput?.value || '').trim().toLowerCase();
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

function filterCompletedLoans() {
    clCurrentPage = 1;
    clRenderPage();
}

function sortCompletedLoans() {
    const order = document.getElementById('sortFilter').value;
    const tbody = document.getElementById('loanTableBody');
    if (!tbody) return;

    const rules = {
        newest: ['applied', -1],
        oldest: ['applied', 1],
        completed_newest: ['completed', -1],
        completed_oldest: ['completed', 1]
    };
    const rule = rules[order] || rules.newest;

    const rows = Array.from(tbody.querySelectorAll('tr[data-search]'));
    rows.sort(function (a, b) {
        const aVal = parseInt(a.dataset[rule[0]], 10) || 0;
        const bVal = parseInt(b.dataset[rule[0]], 10) || 0;
        return (aVal - bVal) * rule[1];
    });
    rows.forEach(function (row) {
        tbody.appendChild(row);
    });

    clCurrentPage = 1;
    clRenderPage();
}

function clChangeRowsPerPage(select) {
    clRowsPerPage = parseInt(select.value, 10) || 10;
    clCurrentPage = 1;
    clRenderPage();
}

function clRenderPage() {
    const allRows = Array.from(
        document.querySelectorAll('#loanTableWrap table tbody tr[data-search]')
    );
    const matchingRows = clGetMatchingRows();

    allRows.forEach(row => row.style.display = 'none');

    const totalRows = matchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / clRowsPerPage));

    if (clCurrentPage > totalPages) clCurrentPage = totalPages;
    if (clCurrentPage < 1) clCurrentPage = 1;

    const start = (clCurrentPage - 1) * clRowsPerPage;
    const end = start + clRowsPerPage;
    matchingRows.slice(start, end).forEach(row => row.style.display = '');

    const noResults = document.getElementById('filteredNoResults');
    const tableWrap = document.getElementById('loanTableWrap');
    if (noResults && tableWrap) {
        noResults.style.display = totalRows === 0 ? 'block' : 'none';
        tableWrap.style.display = totalRows === 0 ? 'none' : '';
    }

    clUpdatePaginationControls(totalRows, totalPages, start, end);
}

function clGetPageList(current, total) {
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

function clRenderPageButtons(nav, totalPages) {
    let html = '';

    html += '<button type="button" class="btn-page-num-v2" data-page="' + (clCurrentPage - 1) + '"'
        + (clCurrentPage === 1 ? ' disabled' : '') + ' aria-label="Previous page">&#8249;</button>';

    clGetPageList(clCurrentPage, totalPages).forEach(function (p) {
        if (p === '...') {
            html += '<span class="page-dots-v2">...</span>';
        } else {
            html += '<button type="button" class="btn-page-num-v2' + (p === clCurrentPage ? ' is-active' : '')
                + '" data-page="' + p + '">' + p + '</button>';
        }
    });

    html += '<button type="button" class="btn-page-num-v2" data-page="' + (clCurrentPage + 1) + '"'
        + (clCurrentPage === totalPages ? ' disabled' : '') + ' aria-label="Next page">&#8250;</button>';

    nav.innerHTML = html;
}

function clUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('clPaginationInfo');
    const nav = document.getElementById('clPaginationNav');
    const paginationWrap = document.getElementById('clPagination');

    if (infoEl) {
        if (totalRows === 0) {
            infoEl.innerHTML = 'Showing <strong>0</strong> of <strong>0</strong>';
        } else {
            infoEl.innerHTML = 'Showing <strong>' + (start + 1) + '\u2013' + Math.min(end, totalRows)
                + '</strong> of <strong>' + totalRows + '</strong>';
        }
    }

    if (nav) clRenderPageButtons(nav, totalPages);

    if (paginationWrap) {
        paginationWrap.style.display = totalRows === 0 ? 'none' : 'grid';
    }
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('#clPaginationNav .btn-page-num-v2');
    if (!btn || btn.disabled) return;

    clCurrentPage = parseInt(btn.dataset.page, 10);
    clRenderPage();
});

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('loanTableWrap')) {
        clRenderPage();
    }
});

document.addEventListener('click', function (e) {
    var row = e.target.closest('tr[data-href]');
    if (!row) return;

    if (e.target.closest('a, button, input, select, textarea')) return;

    window.location.href = row.dataset.href;
});