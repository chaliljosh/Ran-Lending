/* ========================================
   ALL BORROWERS PAGE - JS
   Ginagamit ito sa: borrowers.blade.php
   File: public/js/all-borrowers.js

   May console.log na naka-add para sa debugging. Pag gumana na
   ang lahat, pwede mo nang tanggalin ang mga console.log/warn line
   (naka-mark ang mga ito ng "// DEBUG").
   ======================================== */

console.log('[all-borrowers.js] file loaded'); // DEBUG

// FIX: dati, static lang yung "25 selected" sa Blade at wala talagang
// nagse-save ng pinili mong rows-per-page kahit saan — kaya pag
// nag-reload ang page, babalik lang sa default markup (25). Ngayon,
// ise-save natin ang huling napiling value sa localStorage, at ito
// na ang gagamitin bilang default sa susunod na pag-load ng page.
const PER_PAGE_STORAGE_KEY = 'rs_all_borrowers_per_page';

function getSavedPerPage() {
    try {
        const saved = localStorage.getItem(PER_PAGE_STORAGE_KEY);
        return saved ? parseInt(saved, 10) : null;
    } catch (e) {
        console.warn('[all-borrowers.js] localStorage not available, per-page won\'t persist'); // DEBUG
        return null;
    }
}

function savePerPage(value) {
    try {
        localStorage.setItem(PER_PAGE_STORAGE_KEY, String(value));
    } catch (e) {
        // silent — private browsing / storage disabled, di naman kritikal
    }
}

// ── Client-side search + status filter ──
// FIX: dati ito ang tanging naghahatid sa "gaano ka-hidden" ng isang row
// gamit lang si row.style.display — pero ginagamit din pala yun ni
// paginate() para sa sarili niyang visibility. Dahil parehong
// nag-uupdate ng style.display, nasisira ang filter state kada
// pag-paginate. Ngayon, ang FILTER MATCH ay nakatago sa
// row.dataset.filterMatch ('true'/'false') — hiwalay talaga sa kung
// nakikita ba ang row sa kasalukuyang page. Si paginate() na lang ang
// bahala mag-set ng style.display base dito.
function filterBorrowers() {
    const query  = document.getElementById('filterSearchInput').value.trim().toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const rows   = document.querySelectorAll('tbody tr[data-search]');
    const noResults = document.getElementById('filteredNoResults');
    const tableWrap = document.getElementById('borrowerTableWrap');
    let visibleCount = 0;

    rows.forEach(function (row) {
        const matchesQuery  = row.dataset.search.includes(query);
        const matchesStatus = !status || row.dataset.status === status;
        const visible = matchesQuery && matchesStatus;
        row.dataset.filterMatch = visible ? 'true' : 'false';
        if (visible) visibleCount++;
    });

    if (noResults && tableWrap) {
        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        tableWrap.style.display = visibleCount === 0 ? 'none' : '';
    }

    if (window.refreshBorrowerPagination) {
        window.refreshBorrowerPagination();
    } else {
        console.warn('[all-borrowers.js] refreshBorrowerPagination not found — pagination block may have failed to init'); // DEBUG
    }
}

// ── Client-side sort ──
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

// ── Pagination ──
(function () {
    const STATE = {
        table: null,
        footer: null,
        perPageSelect: null,
        infoEl: null,
        controlsEl: null,
        currentPage: 1,
        perPage: 25,
    };

    function getAllRows() {
        return Array.from(STATE.table.querySelectorAll('tbody tr[data-search]'));
    }

    // FIX: ito na ang "totoong" filtered set — base sa
    // dataset.filterMatch (permanenteng filter state), HINDI na sa
    // style.display (na pabago-bago dahil sa pagination mismo).
    // Bago pa man tumakbo si filterBorrowers() (walang binigay na
    // value), default 'true' — kaya lahat ng rows ay kasama sa una.
    function getFilteredRows() {
        return getAllRows().filter((row) => row.dataset.filterMatch !== 'false');
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

        console.log('[all-borrowers.js] paginate()', { perPage, total, currentPage: STATE.currentPage, start, end }); // DEBUG

        // Una: itago lahat ng rows na hindi match sa filter (wala
        // silang kinalaman sa pagination — palaging hidden ito).
        allRows.forEach((row) => {
            if (!filtered.includes(row)) {
                row.style.display = 'none';
            }
        });

        // Pagkatapos: sa mga rows na match sa filter, ipakita lang
        // yung mga nasa saklaw ng kasalukuyang page.
        filtered.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });

        renderInfo(total, start, Math.min(end, total));
        renderControls(totalPages);
    }

    function renderInfo(total, start, end) {
        if (!STATE.infoEl) return;
        if (total === 0) {
            STATE.infoEl.innerHTML = 'No borrowers found';
            return;
        }
        STATE.infoEl.innerHTML =
            `Showing <strong>${start + 1}\u2013${end}</strong> of <strong>${total}</strong>`;
    }

    function renderControls(totalPages) {
        if (!STATE.controlsEl) return;
        const current = STATE.currentPage;
        STATE.controlsEl.innerHTML = '';

        STATE.controlsEl.appendChild(
            makeBtn('\u2039', 'prev', current === 1, () => goTo(current - 1))
        );

        getPageNumbers(current, totalPages).forEach((item) => {
            if (item === '...') {
                const span = document.createElement('span');
                span.className = 'page-ellipsis';
                span.textContent = '\u2026';
                STATE.controlsEl.appendChild(span);
            } else {
                const btn = makeBtn(String(item), 'page', false, () => goTo(item));
                if (item === current) btn.classList.add('active');
                STATE.controlsEl.appendChild(btn);
            }
        });

        STATE.controlsEl.appendChild(
            makeBtn('\u203A', 'next', current === totalPages, () => goTo(current + 1))
        );
    }

    function makeBtn(label, action, disabled, onClick) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'page-btn';
        btn.textContent = label;
        btn.dataset.action = action;
        btn.disabled = disabled;
        btn.addEventListener('click', onClick);
        return btn;
    }

    function getPageNumbers(current, total) {
        const pages = [];
        const delta = 1;
        for (let i = 1; i <= total; i++) {
            if (
                i === 1 ||
                i === total ||
                (i >= current - delta && i <= current + delta)
            ) {
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

        console.log('[all-borrowers.js] init — table found:', !!STATE.table, ' footer found:', !!STATE.footer); // DEBUG

        if (!STATE.table || !STATE.footer) {
            console.warn('[all-borrowers.js] STOPPED — table or footer not found in DOM. Check that #borrowerTableWrap and .table-footer exist.'); // DEBUG
            return;
        }

        STATE.perPageSelect = STATE.footer.querySelector('.per-page-select');
        STATE.infoEl = STATE.footer.querySelector('.table-footer-info');
        STATE.controlsEl = STATE.footer.querySelector('.pagination-controls');

        console.log('[all-borrowers.js] perPageSelect found:', !!STATE.perPageSelect); // DEBUG

        if (STATE.perPageSelect) {
            // FIX: i-apply muna ang naka-save na value (kung meron)
            // bago basahin ang select — para ito na ang gamitin
            // bilang panimulang perPage, hindi yung "25 selected"
            // na naka-hardcode sa Blade.
            const saved = getSavedPerPage();
            if (saved && [...STATE.perPageSelect.options].some((o) => parseInt(o.value, 10) === saved)) {
                STATE.perPageSelect.value = String(saved);
                console.log('[all-borrowers.js] restored saved per-page:', saved); // DEBUG
            }

            STATE.perPage = parseInt(STATE.perPageSelect.value, 10) || 25;

            STATE.perPageSelect.addEventListener('change', () => {
                console.log('[all-borrowers.js] per-page changed to', STATE.perPageSelect.value); // DEBUG
                STATE.perPage = parseInt(STATE.perPageSelect.value, 10) || 25;
                savePerPage(STATE.perPage);
                STATE.currentPage = 1;
                paginate();
            });
        } else {
            console.warn('[all-borrowers.js] .per-page-select NOT found inside .table-footer'); // DEBUG
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