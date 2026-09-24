
function buildPageList(current, total) {
    const delta = 1; // ilang numero sa magkabilang gilid ng current page
    const range = [];
    const withDots = [];
    let last = null;

    range.push(1);
    for (let i = current - delta; i <= current + delta; i++) {
        if (i > 1 && i < total) range.push(i);
    }
    if (total > 1) range.push(total);

    for (const i of range) {
        if (last !== null) {
            if (i - last === 2) {
                withDots.push(last + 1);
            } else if (i - last > 2) {
                withDots.push('...');
            }
        }
        withDots.push(i);
        last = i;
    }

    return withDots;
}

function renderPageButtons(containerId, currentPage, totalPages, onPageChange) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '';

    if (totalPages <= 0) return;

    // ---- Prev arrow ----
    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'btn-page';
    prevBtn.innerHTML = '<i class="feather-chevron-left"></i>';
    prevBtn.disabled = currentPage <= 1;
    prevBtn.addEventListener('click', function () {
        if (currentPage > 1) onPageChange(currentPage - 1);
    });
    container.appendChild(prevBtn);

    // ---- Numbered pages + ellipsis ----
    const pages = buildPageList(currentPage, totalPages);
    pages.forEach(function (p) {
        if (p === '...') {
            const dots = document.createElement('span');
            dots.className = 'rv-page-ellipsis';
            dots.textContent = '....';
            container.appendChild(dots);
            return;
        }

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rv-page-num' + (p === currentPage ? ' active' : '');
        btn.textContent = p;
        btn.addEventListener('click', function () {
            if (p !== currentPage) onPageChange(p);
        });
        container.appendChild(btn);
    });

    // ---- Next arrow ----
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'btn-page';
    nextBtn.innerHTML = '<i class="feather-chevron-right"></i>';
    nextBtn.disabled = currentPage >= totalPages;
    nextBtn.addEventListener('click', function () {
        if (currentPage < totalPages) onPageChange(currentPage + 1);
    });
    container.appendChild(nextBtn);
}

// ========================================
// PENDING TAB — search + status/attention filter + sort + rows-per-page
// + numbered pagination. Table rows (<tr data-search data-status
// data-waiting data-applied data-new data-attention>) inside
// #pendingDetailsTable. Pattern matched 1:1 to loan-applications.js.
// ========================================
let pendingMatchingRows = [];
let pendingCurrentPage = 1;
let pendingRowsPerPage = 10;

function filterPendingTable() {
    const searchInput = document.getElementById('pendingSearchInput');
    const statusFilter = document.getElementById('pendingStatusFilter');
    const sortSelect = document.getElementById('pendingSortSelect');
    const table = document.getElementById('pendingDetailsTable');
    if (!table) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const filterValue = statusFilter?.value || 'all';
    const sortKey = sortSelect?.value || 'applied_desc';

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const allRows = Array.from(tbody.querySelectorAll('tr[data-search]'));

    // Re-sort ALL rows (not just the visible ones) so the order stays
    // correct if the user clears their search/filter afterward.
    allRows.sort(function (a, b) {
        switch (sortKey) {
            case 'applied_asc':
                return new Date(a.dataset.applied) - new Date(b.dataset.applied);
            case 'waiting_desc':
                return parseInt(b.dataset.waiting, 10) - parseInt(a.dataset.waiting, 10);
            case 'applied_desc':
            default:
                return new Date(b.dataset.applied) - new Date(a.dataset.applied);
        }
    });

    allRows.forEach(function (row) {
        tbody.appendChild(row);
    });

    pendingMatchingRows = allRows.filter(function (row) {
        const matchesSearch = !query || row.dataset.search.includes(query);

        let matchesFilter = true;
        if (filterValue === 'new') {
            matchesFilter = row.dataset.new === '1';
        } else if (filterValue === 'attention') {
            matchesFilter = row.dataset.attention === '1';
        } else if (filterValue === 'on_hold') {
            matchesFilter = row.dataset.status === 'on_hold';
        }
        // 'all' -> matchesFilter stays true

        return matchesSearch && matchesFilter;
    });

    allRows.forEach(function (row) {
        row.style.display = 'none';
    });

    pendingCurrentPage = 1;
    pendingRenderPage();
}

function pendingChangeRowsPerPage(select) {
    pendingRowsPerPage = parseInt(select.value, 10) || 10;
    pendingCurrentPage = 1;
    pendingRenderPage();
}

function pendingGoToPage(page) {
    pendingCurrentPage = page;
    pendingRenderPage();
}

function pendingRenderPage() {
    const table = document.getElementById('pendingDetailsTable');
    const emptyState = document.getElementById('pendingEmptyState');
    const totalRows = pendingMatchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / pendingRowsPerPage));

    if (pendingCurrentPage > totalPages) pendingCurrentPage = totalPages;
    if (pendingCurrentPage < 1) pendingCurrentPage = 1;

    pendingMatchingRows.forEach(function (row) {
        row.style.display = 'none';
    });

    const start = (pendingCurrentPage - 1) * pendingRowsPerPage;
    const end = start + pendingRowsPerPage;
    pendingMatchingRows.slice(start, end).forEach(function (row) {
        row.style.display = '';
    });

    if (emptyState) emptyState.style.display = totalRows === 0 ? 'block' : 'none';
    if (table) table.style.display = totalRows === 0 ? 'none' : '';

    const infoEl = document.getElementById('pendingPaginationInfo');
    if (infoEl) {
        if (totalRows === 0) {
            infoEl.textContent = 'Showing 0 of 0 borrowers';
        } else {
            infoEl.textContent = 'Showing ' + (start + 1) + '\u2013' + Math.min(end, totalRows) + ' of ' + totalRows + ' borrowers';
        }
    }

    renderPageButtons('pendingPaginationControls', pendingCurrentPage, totalPages, pendingGoToPage);

    const paginationWrap = document.getElementById('pendingPagination');
    if (paginationWrap) {
        paginationWrap.style.display = totalRows === 0 ? 'none' : 'flex';
    }
}


// ========================================
// REVIEWED TAB — search + decision filter + rows-per-page + numbered pagination
// Table rows (<tr data-search data-decision>) inside #reviewedTable.
// ========================================
let reviewedMatchingRows = [];
let reviewedCurrentPage = 1;
let reviewedRowsPerPage = 10;

function filterReviewedTable() {
    const searchInput = document.getElementById('reviewedSearchInput');
    const decisionSelect = document.getElementById('reviewedDecisionFilter');
    const table = document.getElementById('reviewedTable');
    if (!table) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const decision = decisionSelect?.value || '';

    const allRows = Array.from(table.querySelectorAll('tbody tr[data-search]'));

    reviewedMatchingRows = allRows.filter(row => {
        const matchesSearch = !query || row.dataset.search.includes(query);
        const matchesDecision = !decision || row.dataset.decision === decision;
        return matchesSearch && matchesDecision;
    });

    allRows.forEach(row => row.style.display = 'none');

    reviewedCurrentPage = 1;
    reviewedRenderPage();
}

function reviewedChangeRowsPerPage(select) {
    reviewedRowsPerPage = parseInt(select.value, 10) || 10;
    reviewedCurrentPage = 1;
    reviewedRenderPage();
}

function reviewedGoToPage(page) {
    reviewedCurrentPage = page;
    reviewedRenderPage();
}

function reviewedRenderPage() {
    const emptyState = document.getElementById('reviewedEmptyState');
    const totalRows = reviewedMatchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / reviewedRowsPerPage));

    if (reviewedCurrentPage > totalPages) reviewedCurrentPage = totalPages;
    if (reviewedCurrentPage < 1) reviewedCurrentPage = 1;

    reviewedMatchingRows.forEach(row => row.style.display = 'none');

    const start = (reviewedCurrentPage - 1) * reviewedRowsPerPage;
    const end = start + reviewedRowsPerPage;
    reviewedMatchingRows.slice(start, end).forEach(row => row.style.display = '');

    if (emptyState) {
        emptyState.style.display = totalRows === 0 ? 'block' : 'none';
    }

    reviewedUpdatePaginationControls(totalRows, totalPages);
}

function reviewedUpdatePaginationControls(totalRows, totalPages) {
    const infoEl = document.getElementById('reviewedPaginationInfo');
    const paginationWrap = document.getElementById('reviewedPagination');

    if (infoEl) {
        infoEl.textContent = `of ${totalRows} result${totalRows === 1 ? '' : 's'}`;
    }

    renderPageButtons('reviewedPaginationControls', reviewedCurrentPage, totalPages, reviewedGoToPage);

    if (paginationWrap) {
        const hasActiveFilter = document.getElementById('reviewedSearchInput')?.value
            || document.getElementById('reviewedDecisionFilter')?.value;
        paginationWrap.style.display = (totalRows === 0 && !hasActiveFilter) ? 'none' : 'flex';
    }
}

// ========================================
// FILTERED VIEW (Approved / Rejected / On Hold) — search + rows-per-page
// + pagination. Table rows (<tr data-search>) inside #filteredDetailsTable.
// ========================================
let filteredMatchingRows = [];
let filteredCurrentPage = 1;
let filteredRowsPerPage = 10;

function filterDetailsTable() {
    const searchInput = document.getElementById('filteredSearchInput');
    const table = document.getElementById('filteredDetailsTable');
    if (!table) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const allRows = Array.from(table.querySelectorAll('tbody tr[data-search]'));

    filteredMatchingRows = allRows.filter(row => !query || row.dataset.search.includes(query));

    allRows.forEach(row => row.style.display = 'none');

    filteredCurrentPage = 1;
    filteredRenderPage();
}

function filteredChangeRowsPerPage(select) {
    filteredRowsPerPage = parseInt(select.value, 10) || 10;
    filteredCurrentPage = 1;
    filteredRenderPage();
}

function filteredGoToPage(page) {
    filteredCurrentPage = page;
    filteredRenderPage();
}

function filteredRenderPage() {
    const emptyState = document.getElementById('filteredEmptyState');
    const totalRows = filteredMatchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / filteredRowsPerPage));

    if (filteredCurrentPage > totalPages) filteredCurrentPage = totalPages;
    if (filteredCurrentPage < 1) filteredCurrentPage = 1;

    filteredMatchingRows.forEach(row => row.style.display = 'none');

    const start = (filteredCurrentPage - 1) * filteredRowsPerPage;
    const end = start + filteredRowsPerPage;
    filteredMatchingRows.slice(start, end).forEach(row => row.style.display = '');

    if (emptyState) {
        emptyState.style.display = totalRows === 0 ? 'block' : 'none';
    }

    filteredUpdatePaginationControls(totalRows, totalPages);
}

function filteredUpdatePaginationControls(totalRows, totalPages) {
    const infoEl = document.getElementById('filteredPaginationInfo');
    const paginationWrap = document.getElementById('filteredPagination');

    if (infoEl) {
        infoEl.textContent = `of ${totalRows} result${totalRows === 1 ? '' : 's'}`;
    }

    renderPageButtons('filteredPaginationControls', filteredCurrentPage, totalPages, filteredGoToPage);

    if (paginationWrap) {
        const hasActiveFilter = document.getElementById('filteredSearchInput')?.value;
        paginationWrap.style.display = (totalRows === 0 && !hasActiveFilter) ? 'none' : 'flex';
    }
}

let cardPendingForm = null;

function cardConfirmAction(btn, action) {
    cardPendingForm = btn.closest('form.decision-card-form');

    const icon = document.getElementById('cardConfirmIcon');
    const text = document.getElementById('cardConfirmText');
    const confirmBtn = document.getElementById('cardConfirmBtn');

    if (action === 'approved') {
        icon.className = 'feather-check-circle text-success';
        text.textContent = 'Approve this borrower?';
        confirmBtn.className = 'btn btn-success flex-fill';
        confirmBtn.innerHTML = '<i class="feather-check me-1"></i> Yes, Approve';
    } else {
        icon.className = 'feather-x-circle text-danger';
        text.textContent = 'Reject this borrower?';
        confirmBtn.className = 'btn btn-danger flex-fill';
        confirmBtn.innerHTML = '<i class="feather-x me-1"></i> Yes, Reject';
    }

    confirmBtn.onclick = function () {
        submitDecision(cardPendingForm, action);
    };

    const modal = new bootstrap.Modal(document.getElementById('cardConfirmModal'));
    modal.show();
}

function resultModalIdFor(status) {
    if (status === 'approved') return 'finalApprovalModal';
    if (status === 'on_hold') return 'onHoldModal';
    return 'decisionSuccessModal';
}

function submitDecision(form, action) {
    if (!form) return;

    let actionInput = form.querySelector('input[name="action"]');
    if (!actionInput) {
        actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        form.appendChild(actionInput);
    }
    actionInput.value = action;

    const confirmModalEl = document.getElementById('cardConfirmModal');
    const confirmModalInstance = bootstrap.Modal.getInstance(confirmModalEl);

    form.classList.add('is-submitting');

    let modalHidden = false;
    let fetchResult = null;

    function tryShowSuccess() {
        if (!modalHidden || fetchResult === null) return;

        if (fetchResult.success) {
            const resultModalId = resultModalIdFor(fetchResult.status);
            const resultModalEl = document.getElementById(resultModalId);
            const resultModal = new bootstrap.Modal(resultModalEl, { backdrop: 'static' });

            resultModalEl.addEventListener('hidden.bs.modal', function onResultHidden() {
                resultModalEl.removeEventListener('hidden.bs.modal', onResultHidden);
                location.reload();
            }, { once: true });

            resultModal.show();
        } else {
            form.classList.remove('is-submitting');
            alert('Something went wrong. Please try again.');
        }
    }

    if (confirmModalEl) {
        confirmModalEl.addEventListener('hidden.bs.modal', function onHidden() {
            confirmModalEl.removeEventListener('hidden.bs.modal', onHidden);
            modalHidden = true;
            tryShowSuccess();
        }, { once: true });
    }

    confirmModalInstance?.hide();

    const formData = new FormData(form);

    fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData,
    })
    .then(res => res.json())
    .then(data => {
        fetchResult = { success: !!data.success, status: data.status };
        tryShowSuccess();
    })
    .catch(() => {
        form.classList.remove('is-submitting');
        alert('Something went wrong. Please check your connection and try again.');
    });
}

// Run pagination once on load for whichever table exists on the current
// view (pending / reviewed / filtered) so it's already sliced to page 1
// even before the user types anything in search/filter.
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('pendingDetailsTable')) {
        filterPendingTable();
    }
    if (document.getElementById('reviewedTable')) {
        filterReviewedTable();
    }
    if (document.getElementById('filteredDetailsTable')) {
        filterDetailsTable();
    }
});