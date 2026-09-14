    // public/functions/loan-applications.js
    // Used by: resources/views/admin/loan-applications.blade.php
    //
    // PENDING TAB: search box, status filter, and sort dropdown all
    // reorder/show-hide the existing <tr> rows, THEN paginate the
    // matching set (rows-per-page select + numbered pagination),
    // same pattern as the Reviewed/Filtered tabs below. Row numbers
    // (the "#" column) are assigned per visible page here since the
    // row order can change with search/filter/sort.
    //
    // REVIEWED / FILTERED tabs are untouched — still use the same
    // numbered pagination (1 2 3 ... N) as before.

    // ========================================
    // SHARED — generic numbered-pagination renderer
    // ========================================
    function buildPageList(current, total) {
        const delta = 1;
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

        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'btn-page';
        prevBtn.innerHTML = '<i class="feather-chevron-left"></i>';
        prevBtn.disabled = currentPage <= 1;
        prevBtn.addEventListener('click', function () {
            if (currentPage > 1) onPageChange(currentPage - 1);
        });
        container.appendChild(prevBtn);

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
    // PENDING TAB — search + status filter + sort + rows-per-page
    // + numbered pagination. Table rows (<tr data-search data-status
    // data-waiting data-amount data-applied>) inside #loanPendingList.
    // ========================================
    let pendingMatchingRows = [];
    let pendingCurrentPage = 1;
    let pendingRowsPerPage = 10;

    function filterLoanPending() {
        const searchInput = document.getElementById('pendingSearchInput');
        const statusFilter = document.getElementById('pendingStatusFilter');
        const sortSelect = document.getElementById('pendingSortSelect');
        const tbody = document.getElementById('loanPendingList');
        if (!tbody) return;

        const query = (searchInput?.value || '').trim().toLowerCase();
        const filterValue = statusFilter?.value || 'all';
        const sortKey = sortSelect?.value || 'applied_desc';

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
        const table = document.getElementById('pendingLoanTable');
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
        const pageRows = pendingMatchingRows.slice(start, end);

        pageRows.forEach(function (row, i) {
            row.style.display = '';
            const numCell = row.querySelector('.rt-num');
            if (numCell) numCell.textContent = start + i + 1;
        });

        if (emptyState) emptyState.style.display = totalRows === 0 ? 'block' : 'none';
        if (table) table.style.display = totalRows === 0 ? 'none' : '';

        const infoEl = document.getElementById('pendingPaginationInfo');
        if (infoEl) {
            if (totalRows === 0) {
                infoEl.textContent = 'Showing 0 of 0 applications';
            } else {
                infoEl.textContent = 'Showing ' + (start + 1) + '\u2013' + Math.min(end, totalRows) + ' of ' + totalRows + ' applications';
            }
        }

        const badge = document.getElementById('pendingCountBadge');
        if (badge) badge.textContent = totalRows + ' result' + (totalRows === 1 ? '' : 's');

        renderPageButtons('pendingPaginationControls', pendingCurrentPage, totalPages, pendingGoToPage);

        const paginationWrap = document.getElementById('pendingPagination');
        if (paginationWrap) {
            paginationWrap.style.display = totalRows === 0 ? 'none' : 'flex';
        }
    }


    // ========================================
    // REVIEWED TAB — search + decision filter + rows-per-page + numbered pagination
    // Table rows (<tr data-search data-decision>) inside #loanReviewedList.
    // ========================================
    let loanReviewedMatchingRows = [];
    let loanReviewedCurrentPage = 1;
    let loanReviewedRowsPerPage = 10;

    function filterLoanReviewed() {
        const searchInput = document.getElementById('loanReviewedSearchInput');
        const decisionSelect = document.getElementById('loanReviewedDecisionFilter');
        const table = document.getElementById('loanReviewedTable');
        if (!table) return;

        const query = (searchInput?.value || '').trim().toLowerCase();
        const decision = decisionSelect?.value || '';

        const allRows = Array.from(document.querySelectorAll('#loanReviewedList tr[data-search]'));

        loanReviewedMatchingRows = allRows.filter(row => {
            const matchesSearch = !query || row.dataset.search.includes(query);
            const matchesDecision = !decision || row.dataset.decision === decision;
            return matchesSearch && matchesDecision;
        });

        allRows.forEach(row => row.style.display = 'none');

        loanReviewedCurrentPage = 1;
        loanReviewedRenderPage();
    }

    function loanReviewedChangeRowsPerPage(select) {
        loanReviewedRowsPerPage = parseInt(select.value, 10) || 10;
        loanReviewedCurrentPage = 1;
        loanReviewedRenderPage();
    }

    function loanReviewedGoToPage(page) {
        loanReviewedCurrentPage = page;
        loanReviewedRenderPage();
    }

    function loanReviewedRenderPage() {
        const table = document.getElementById('loanReviewedTable');
        const emptyState = document.getElementById('loanReviewedEmptyState');
        const totalRows = loanReviewedMatchingRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / loanReviewedRowsPerPage));

        if (loanReviewedCurrentPage > totalPages) loanReviewedCurrentPage = totalPages;
        if (loanReviewedCurrentPage < 1) loanReviewedCurrentPage = 1;

        loanReviewedMatchingRows.forEach(row => row.style.display = 'none');

        const start = (loanReviewedCurrentPage - 1) * loanReviewedRowsPerPage;
        const end = start + loanReviewedRowsPerPage;
        loanReviewedMatchingRows.slice(start, end).forEach(row => row.style.display = '');

        if (emptyState) emptyState.style.display = totalRows === 0 ? 'block' : 'none';
        if (table) table.style.display = totalRows === 0 ? 'none' : '';

        const infoEl = document.getElementById('loanReviewedPaginationInfo');
        if (infoEl) {
            infoEl.textContent = `of ${totalRows} result${totalRows === 1 ? '' : 's'}`;
        }

        renderPageButtons('loanReviewedPaginationControls', loanReviewedCurrentPage, totalPages, loanReviewedGoToPage);

        const paginationWrap = document.getElementById('loanReviewedPagination');
        if (paginationWrap) {
            const hasActiveFilter = document.getElementById('loanReviewedSearchInput')?.value
                || document.getElementById('loanReviewedDecisionFilter')?.value;
            paginationWrap.style.display = (totalRows === 0 && !hasActiveFilter) ? 'none' : 'flex';
        }
    }


    // ========================================
    // FILTERED VIEW (Approved / Rejected / On Hold) —
    // search + rows-per-page + numbered pagination.
    // Table rows (<tr data-search>) inside #filteredLoanList.
    // ========================================
    let filteredMatchingRows = [];
    let filteredCurrentPage = 1;
    let filteredRowsPerPage = 10;

    function filterLoanFiltered() {
        const searchInput = document.getElementById('filteredSearchInput');
        const table = document.getElementById('filteredLoanTable');
        if (!table) return;

        const query = (searchInput?.value || '').trim().toLowerCase();
        const allRows = Array.from(document.querySelectorAll('#filteredLoanList tr[data-search]'));

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

    // ========================================
    // LOAN DECISION TOAST — shown after a successful decision submit
    // on the Loan Application Detail page's redirect back here.
    // Called from the Blade with the raw decisionStatus session value
    // (approved / rejected / on_hold / null).
    // ========================================
    function showLoanDecisionToast(status) {
        let title = 'Decision submitted';
        let message = 'Your decision has been recorded successfully. The application will remain under review until all three administrators have submitted their decisions.';
        let type = 'success';

        if (status === 'approved') {
            title = 'Application approved';
            message = 'The loan application has been approved by all administrators.';
            type = 'success';
        } else if (status === 'rejected') {
            title = 'Application rejected';
            message = 'The loan application has been rejected by all administrators.';
            type = 'error';
        } else if (status === 'on_hold') {
            title = 'Application on hold';
            message = 'The administrators have reached different decisions. The application has been placed on hold for further discussion.';
            type = 'warning';
        }

        if (window.showAdminToast) {
            window.showAdminToast(type, title, message);
        }
    }

    function filteredRenderPage() {
        const table = document.getElementById('filteredLoanTable');
        const emptyState = document.getElementById('filteredEmptyState');
        const totalRows = filteredMatchingRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / filteredRowsPerPage));

        if (filteredCurrentPage > totalPages) filteredCurrentPage = totalPages;
        if (filteredCurrentPage < 1) filteredCurrentPage = 1;

        filteredMatchingRows.forEach(row => row.style.display = 'none');

        const start = (filteredCurrentPage - 1) * filteredRowsPerPage;
        const end = start + filteredRowsPerPage;
        filteredMatchingRows.slice(start, end).forEach(row => row.style.display = '');

        if (emptyState) emptyState.style.display = totalRows === 0 ? 'block' : 'none';
        if (table) table.style.display = totalRows === 0 ? 'none' : '';

        const infoEl = document.getElementById('filteredPaginationInfo');
        if (infoEl) {
            infoEl.textContent = `of ${totalRows} result${totalRows === 1 ? '' : 's'}`;
        }

        renderPageButtons('filteredPaginationControls', filteredCurrentPage, totalPages, filteredGoToPage);

        const paginationWrap = document.getElementById('filteredPagination');
        if (paginationWrap) {
            const hasActiveFilter = document.getElementById('filteredSearchInput')?.value;
            paginationWrap.style.display = (totalRows === 0 && !hasActiveFilter) ? 'none' : 'flex';
        }
    }

    // Run once on load for whichever table exists on the current view
    // (pending / reviewed / filtered) so it's already sorted/filtered
    // and paginated correctly even before the user types anything.
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('pendingLoanTable')) {
            filterLoanPending();
        }
        if (document.getElementById('loanReviewedTable')) {
            filterLoanReviewed();
        }
        if (document.getElementById('filteredLoanTable')) {
            filterLoanFiltered();
        }
    });