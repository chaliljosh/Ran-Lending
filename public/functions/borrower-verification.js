// public/functions/borrower-verification.js
// Used by: resources/views/admin/borrower-verification.blade.php

function showTab(tab, btn) {
    document.getElementById('tab-pending').style.display  = tab === 'pending'  ? 'block' : 'none';
    document.getElementById('tab-reviewed').style.display = tab === 'reviewed' ? 'block' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// ========================================
// PENDING TAB — search + status filter + pagination.
// Same pattern as the Reviewed tab below, just filtering on data-status
// (pending / on_hold) instead of data-decision.
// Table rows (<tr data-search data-status>) inside #pendingDetailsTable.
// ========================================
let pendingMatchingRows = [];
let pendingCurrentPage = 1;
let pendingRowsPerPage = 10;

function filterPendingTable() {
    const searchInput = document.getElementById('pendingSearchInput');
    const statusSelect = document.getElementById('pendingStatusFilter');
    const table = document.getElementById('pendingDetailsTable');
    if (!table) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const status = statusSelect?.value || '';

    const allRows = Array.from(table.querySelectorAll('tbody tr[data-search]'));

    pendingMatchingRows = allRows.filter(row => {
        const matchesSearch = !query || row.dataset.search.includes(query);
        const matchesStatus = !status || row.dataset.status === status;
        return matchesSearch && matchesStatus;
    });

    allRows.forEach(row => row.style.display = 'none');

    pendingCurrentPage = 1;
    pendingRenderPage();
}

function pendingChangeRowsPerPage(select) {
    pendingRowsPerPage = parseInt(select.value, 10) || 10;
    pendingCurrentPage = 1;
    pendingRenderPage();
}

function pendingGoToPage(direction) {
    pendingCurrentPage += direction;
    pendingRenderPage();
}

function pendingRenderPage() {
    const emptyState = document.getElementById('pendingEmptyState');
    const totalRows = pendingMatchingRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / pendingRowsPerPage));

    if (pendingCurrentPage > totalPages) pendingCurrentPage = totalPages;
    if (pendingCurrentPage < 1) pendingCurrentPage = 1;

    pendingMatchingRows.forEach(row => row.style.display = 'none');

    const start = (pendingCurrentPage - 1) * pendingRowsPerPage;
    const end = start + pendingRowsPerPage;
    pendingMatchingRows.slice(start, end).forEach(row => row.style.display = '');

    if (emptyState) {
        emptyState.style.display = totalRows === 0 ? 'block' : 'none';
    }

    pendingUpdatePaginationControls(totalRows, totalPages, start, end);
}

function pendingUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('pendingPaginationInfo');
    const pageIndicator = document.getElementById('pendingPageIndicator');
    const prevBtn = document.getElementById('pendingPrevPage');
    const nextBtn = document.getElementById('pendingNextPage');
    const paginationWrap = document.getElementById('pendingPagination');

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
        pageIndicator.textContent = `Page ${pendingCurrentPage} of ${totalPages}`;
    }

    if (prevBtn) prevBtn.disabled = pendingCurrentPage <= 1;
    if (nextBtn) nextBtn.disabled = pendingCurrentPage >= totalPages;

    if (paginationWrap) {
        const hasActiveFilter = document.getElementById('pendingSearchInput')?.value
            || document.getElementById('pendingStatusFilter')?.value;
        paginationWrap.style.display = (totalRows === 0 && !hasActiveFilter) ? 'none' : 'flex';
    }
}

// ========================================
// REVIEWED TAB — search + decision filter + pagination
// reviewedMatchingRows holds the current filtered set (before paging).
// filterReviewedTable() re-runs whenever search/decision changes and
// resets to page 1. reviewedRenderPage() just slices that set based on
// reviewedCurrentPage / reviewedRowsPerPage and updates the row display
// + the pagination controls (info text, Prev/Next disabled state).
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

function reviewedGoToPage(direction) {
    reviewedCurrentPage += direction;
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

    reviewedUpdatePaginationControls(totalRows, totalPages, start, end);
}

function reviewedUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('reviewedPaginationInfo');
    const pageIndicator = document.getElementById('reviewedPageIndicator');
    const prevBtn = document.getElementById('reviewedPrevPage');
    const nextBtn = document.getElementById('reviewedNextPage');
    const paginationWrap = document.getElementById('reviewedPagination');

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
        pageIndicator.textContent = `Page ${reviewedCurrentPage} of ${totalPages}`;
    }

    if (prevBtn) prevBtn.disabled = reviewedCurrentPage <= 1;
    if (nextBtn) nextBtn.disabled = reviewedCurrentPage >= totalPages;

    if (paginationWrap) {
        paginationWrap.style.display = totalRows === 0 && !document.getElementById('reviewedSearchInput')?.value && !document.getElementById('reviewedDecisionFilter')?.value
            ? 'none'
            : 'flex';
    }
}

// ========================================
// FILTERED VIEW (Approved / Rejected / On Hold) — search + rows-per-page
// + pagination. Same pattern as the Reviewed tab above, minus the
// decision filter (the whole table is already one decision, driven by
// which sidebar link/tab the admin is on).
// Table rows (<tr data-search>) inside #filteredDetailsTable.
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

function filteredGoToPage(direction) {
    filteredCurrentPage += direction;
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

    filteredUpdatePaginationControls(totalRows, totalPages, start, end);
}

function filteredUpdatePaginationControls(totalRows, totalPages, start, end) {
    const infoEl = document.getElementById('filteredPaginationInfo');
    const pageIndicator = document.getElementById('filteredPageIndicator');
    const prevBtn = document.getElementById('filteredPrevPage');
    const nextBtn = document.getElementById('filteredNextPage');
    const paginationWrap = document.getElementById('filteredPagination');

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
        pageIndicator.textContent = `Page ${filteredCurrentPage} of ${totalPages}`;
    }

    if (prevBtn) prevBtn.disabled = filteredCurrentPage <= 1;
    if (nextBtn) nextBtn.disabled = filteredCurrentPage >= totalPages;

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

// Run pagination once on load for all three tables so they're already
// sliced to page 1 even before the user types anything in search/filter.
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