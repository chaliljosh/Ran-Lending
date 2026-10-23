const PER_PAGE_STORAGE_KEY = 'rs_all_borrowers_per_page';

function getSavedPerPage() {
    try {
        const saved = localStorage.getItem(PER_PAGE_STORAGE_KEY);
        return saved ? parseInt(saved, 10) : null;
    } catch (e) {
        return null;
    }
}

function savePerPage(value) {
    try {
        localStorage.setItem(PER_PAGE_STORAGE_KEY, String(value));
    } catch (e) {}
}

function filterBorrowers() {
    const query = document.getElementById('filterSearchInput').value.trim().toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#borrowerTableWrap tbody tr[data-search]');

    rows.forEach(function (row) {
        const matchesQuery = row.dataset.search.includes(query);
        const matchesStatus = !status || row.dataset.status === status;
        row.dataset.filterMatch = matchesQuery && matchesStatus ? 'true' : 'false';
    });

    if (window.refreshBorrowerPagination) {
        window.refreshBorrowerPagination();
    }
}

function sortBorrowers() {
    const order = document.getElementById('sortFilter').value;
    const tbody = document.querySelector('#borrowerTableWrap table tbody');
    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr[data-search]'));
    rows.sort(function (a, b) {
        const aJoined = parseInt(a.dataset.joined, 10) || 0;
        const bJoined = parseInt(b.dataset.joined, 10) || 0;
        return order === 'oldest' ? aJoined - bJoined : bJoined - aJoined;
    });

    rows.forEach(function (row) {
        tbody.appendChild(row);
    });

    if (window.refreshBorrowerPagination) {
        window.refreshBorrowerPagination();
    }
}

document.addEventListener('click', function (e) {
    const row = e.target.closest('tr[data-href]');
    if (!row) return;
    if (e.target.closest('a, button, input, select')) return;
    if (window.getSelection && window.getSelection().toString()) return;
    window.location.href = row.dataset.href;
});

(function () {
    const STATE = {
        table: null,
        footer: null,
        perPageSelect: null,
        infoEl: null,
        controlsEl: null,
        currentPage: 1,
        perPage: 25
    };

    function getAllRows() {
        return Array.from(STATE.table.querySelectorAll('tbody tr[data-search]'));
    }

    function getFilteredRows() {
        return getAllRows().filter(function (row) {
            return row.dataset.filterMatch !== 'false';
        });
    }

    function paginate() {
        const allRows = getAllRows();
        const filtered = getFilteredRows();
        const total = filtered.length;
        const perPage = STATE.perPage;
        const totalPages = Math.max(1, Math.ceil(total / perPage));

        if (STATE.currentPage > totalPages) STATE.currentPage = totalPages;
        if (STATE.currentPage < 1) STATE.currentPage = 1;

        const start = (STATE.currentPage - 1) * perPage;
        const end = start + perPage;

        allRows.forEach(function (row) {
            if (!filtered.includes(row)) row.style.display = 'none';
        });

        filtered.forEach(function (row, index) {
            row.style.display = index >= start && index < end ? '' : 'none';
        });

        const noResults = document.getElementById('filteredNoResults');
        const tableWrap = document.getElementById('borrowerTableWrap');
        if (noResults && tableWrap) {
            noResults.style.display = total === 0 ? 'block' : 'none';
            tableWrap.style.display = total === 0 ? 'none' : '';
        }
        STATE.footer.style.display = total === 0 ? 'none' : 'grid';

        renderInfo(total, start, Math.min(end, total));
        renderControls(totalPages);
    }

    function renderInfo(total, start, end) {
        if (!STATE.infoEl) return;
        STATE.infoEl.innerHTML = total === 0
            ? 'Showing <strong>0</strong> of <strong>0</strong>'
            : 'Showing <strong>' + (start + 1) + '\u2013' + end + '</strong> of <strong>' + total + '</strong>';
    }

    function renderControls(totalPages) {
        if (!STATE.controlsEl) return;
        const current = STATE.currentPage;
        STATE.controlsEl.innerHTML = '';

        STATE.controlsEl.appendChild(
            makeBtn('\u2039', 'prev', current === 1, function () { goTo(current - 1); })
        );

        getPageNumbers(current, totalPages).forEach(function (item) {
            if (item === '...') {
                const span = document.createElement('span');
                span.className = 'page-ellipsis';
                span.textContent = '\u2026';
                STATE.controlsEl.appendChild(span);
            } else {
                const btn = makeBtn(String(item), 'page', false, function () { goTo(item); });
                if (item === current) btn.classList.add('active');
                STATE.controlsEl.appendChild(btn);
            }
        });

        STATE.controlsEl.appendChild(
            makeBtn('\u203A', 'next', current === totalPages, function () { goTo(current + 1); })
        );
    }

    function makeBtn(label, action, disabled, onClick) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'page-btn';
        btn.textContent = label;
        btn.dataset.action = action;
        btn.disabled = disabled;
        btn.setAttribute('aria-label', action === 'prev' ? 'Previous page' : action === 'next' ? 'Next page' : 'Page ' + label);
        btn.addEventListener('click', onClick);
        return btn;
    }

    function getPageNumbers(current, total) {
        const pages = [];
        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || Math.abs(i - current) <= 1) {
                pages.push(i);
            } else if (pages[pages.length - 1] !== '...') {
                pages.push('...');
            }
        }
        return pages;
    }

    function goTo(page) {
        STATE.currentPage = page;
        paginate();
    }

    function initBorrowerPagination() {
        STATE.table = document.querySelector('#borrowerTableWrap table');
        STATE.footer = document.querySelector('.table-footer');
        if (!STATE.table || !STATE.footer) return;

        STATE.perPageSelect = STATE.footer.querySelector('.per-page-select');
        STATE.infoEl = STATE.footer.querySelector('.table-footer-info');
        STATE.controlsEl = STATE.footer.querySelector('.pagination-controls');

        if (STATE.perPageSelect) {
            const saved = getSavedPerPage();
            if (saved && Array.from(STATE.perPageSelect.options).some(function (o) { return parseInt(o.value, 10) === saved; })) {
                STATE.perPageSelect.value = String(saved);
            }

            STATE.perPage = parseInt(STATE.perPageSelect.value, 10) || 25;

            STATE.perPageSelect.addEventListener('change', function () {
                STATE.perPage = parseInt(STATE.perPageSelect.value, 10) || 25;
                savePerPage(STATE.perPage);
                STATE.currentPage = 1;
                paginate();
            });
        }

        paginate();
    }

    window.refreshBorrowerPagination = function () {
        STATE.currentPage = 1;
        paginate();
    };

    window.initBorrowerPagination = initBorrowerPagination;

    document.addEventListener('DOMContentLoaded', initBorrowerPagination);
})();