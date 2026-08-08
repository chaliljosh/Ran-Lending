(function () {
    const searchInput = document.getElementById('ptSearchInput');
    const monthFilter = document.getElementById('ptMonthFilter');
    const statusFilter = document.getElementById('ptStatusFilter');
    const sortSelect = document.getElementById('ptSortSelect');
    const rowsPerPageSelect = document.getElementById('ptRowsPerPage');
    const tbody = document.getElementById('ptTableBody');
    const emptyState = document.getElementById('ptEmptyState');
    const showingText = document.getElementById('ptShowingText');
    const pagination = document.getElementById('ptPagination');
    const exportBtn = document.getElementById('ptExportBtn');
    const refreshBtn = document.getElementById('ptRefreshBtn');

    const successModalEl = document.getElementById('successModal');
    const successModalMsgEl = document.getElementById('successModalMessage');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    const allRows = Array.from(tbody.querySelectorAll('tr.pt-tr'));
    let currentPage = 1;

    /* ============================================================
       SEARCH / FILTER / SORT / PAGINATE
    ============================================================ */

    function getFilteredSortedRows() {
        const term = (searchInput.value || '').trim().toLowerCase();
        const month = monthFilter.value;
        const status = statusFilter.value;
        const sortBy = sortSelect.value;

        let rows = allRows.filter(function (row) {
            const matchesSearch = !term || row.dataset.borrower.includes(term);
            const matchesMonth = !month || row.dataset.month === month;
            const matchesStatus = !status || row.dataset.status === status;
            return matchesSearch && matchesMonth && matchesStatus;
        });

        rows.sort(function (a, b) {
            if (sortBy === 'days_desc') return parseInt(b.dataset.days) - parseInt(a.dataset.days);
            if (sortBy === 'days_asc') return parseInt(a.dataset.days) - parseInt(b.dataset.days);
            if (sortBy === 'amount_desc') return parseFloat(b.dataset.amount) - parseFloat(a.dataset.amount);
            if (sortBy === 'amount_asc') return parseFloat(a.dataset.amount) - parseFloat(b.dataset.amount);
            if (sortBy === 'due_desc') return new Date(b.dataset.due) - new Date(a.dataset.due);
            if (sortBy === 'due_asc') return new Date(a.dataset.due) - new Date(b.dataset.due);
            return 0;
        });

        return rows;
    }

    function renderPagination(totalPages) {
        pagination.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'pt-page-btn';
        prevBtn.textContent = 'Previous';
        prevBtn.disabled = currentPage <= 1;
        prevBtn.addEventListener('click', function () { currentPage--; render(); });
        pagination.appendChild(prevBtn);

        const maxButtons = 5;
        let start = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let end = Math.min(totalPages, start + maxButtons - 1);
        start = Math.max(1, end - maxButtons + 1);

        for (let i = start; i <= end; i++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pt-page-btn' + (i === currentPage ? ' is-active' : '');
            btn.textContent = i;
            btn.addEventListener('click', function () { currentPage = i; render(); });
            pagination.appendChild(btn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'pt-page-btn';
        nextBtn.textContent = 'Next';
        nextBtn.disabled = currentPage >= totalPages || totalPages === 0;
        nextBtn.addEventListener('click', function () { currentPage++; render(); });
        pagination.appendChild(nextBtn);
    }

    function render() {
        const rows = getFilteredSortedRows();
        const rowsPerPage = parseInt(rowsPerPageSelect.value, 10);
        const total = rows.length;
        const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = Math.min(startIdx + rowsPerPage, total);
        const pageRows = rows.slice(startIdx, endIdx);

        allRows.forEach(function (row) { row.style.display = 'none'; });
        pageRows.forEach(function (row) {
            row.style.display = '';
            tbody.appendChild(row);
        });

        emptyState.style.display = total === 0 ? '' : 'none';
        tbody.parentElement.style.display = total === 0 ? 'none' : '';

        showingText.textContent = total === 0
            ? 'Showing 0–0 of 0 records'
            : 'Showing ' + (startIdx + 1) + '–' + endIdx + ' of ' + total + ' records';

        renderPagination(totalPages);
    }

    function exportCsv() {
        const rows = getFilteredSortedRows();
        const header = ['Borrower', 'Installment', 'Due Date', 'Days Late', 'Amount Due', 'Late Fee', 'Status'];
        const lines = [header.join(',')];

        rows.forEach(function (row) {
            const name = row.querySelector('.pt-borrower-name').textContent.trim();
            const installment = row.querySelector('.pt-cell-installment').textContent.trim();
            const due = row.querySelector('.pt-due-primary').textContent.trim();
            const days = row.dataset.days;
            const amount = row.querySelector('.pt-amount-value').textContent.trim();
            const fee = row.querySelector('.pt-amount-fee').textContent.replace('Fee ', '').trim();
            const status = row.dataset.status;
            lines.push([name, installment, due, days, amount, fee, status].map(function (v) {
                return '"' + String(v).replace(/"/g, '""') + '"';
            }).join(','));
        });

        const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'penalty-tracking-export.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    [searchInput, monthFilter, statusFilter, sortSelect].forEach(function (el) {
        el.addEventListener('input', function () { currentPage = 1; render(); });
        el.addEventListener('change', function () { currentPage = 1; render(); });
    });
    rowsPerPageSelect.addEventListener('change', function () { currentPage = 1; render(); });
    exportBtn.addEventListener('click', exportCsv);
    refreshBtn.addEventListener('click', function () { window.location.reload(); });

    // Whole-row click opens the View Details modal — except clicks
    // landing on the actions cell (View/Paid/Waive buttons).
    allRows.forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.pt-cell-actions')) return;
            const targetSel = row.getAttribute('data-view-target');
            const modalEl = document.querySelector(targetSel);
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
        row.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                row.click();
            }
        });
        row.setAttribute('tabindex', '0');
    });

    render();

    /* ============================================================
       SUCCESS MODAL HELPER
    ============================================================ */

    function showSuccessModal(message) {
        if (!successModalEl || !window.bootstrap) return;
        if (successModalMsgEl) successModalMsgEl.textContent = message;
        const modal = bootstrap.Modal.getOrCreateInstance(successModalEl);
        modal.show();
        setTimeout(function () { modal.hide(); }, 2500);
    }

    if (successModalMsgEl && successModalMsgEl.textContent.trim() !== '') {
        showSuccessModal(successModalMsgEl.textContent.trim());
    }

    /* ============================================================
       ROW UPDATE HELPERS (after Mark Paid / Waive succeeds)
    ============================================================ */

    function removeRowActionButtons(row) {
        const group = row.querySelector('.pt-actions-group');
        if (!group) return;
        const paidBtn = group.querySelector('.pt-action-btn.is-paid');
        const waiveBtn = group.querySelector('.pt-action-btn.is-waive');
        if (paidBtn) paidBtn.remove();
        if (waiveBtn) waiveBtn.remove();
    }

    function updateRowToPaid(row) {
        row.dataset.status = 'paid';

        const pill = row.querySelector('.pt-cell-days .pt-pill');
        if (pill) {
            pill.className = 'pt-pill is-green';
            pill.textContent = 'Paid';
        }

        const badge = row.querySelector('.pt-status-badge');
        if (badge) {
            badge.className = 'pt-status-badge is-paid';
            badge.textContent = 'PAID';
        }

        removeRowActionButtons(row);
    }

    function updateRowToWaived(row, waivedReason, penaltyId) {
        row.dataset.status = 'waived';

        const pill = row.querySelector('.pt-cell-days .pt-pill');
        if (pill) {
            pill.className = 'pt-pill is-gray';
            pill.textContent = 'Waived';
        }

        const badge = row.querySelector('.pt-status-badge');
        if (badge) {
            badge.className = 'pt-status-badge is-waived';
            badge.textContent = 'WAIVED';
        }

        removeRowActionButtons(row);

        const targetSel = row.getAttribute('data-view-target');
        const viewModal = targetSel ? document.querySelector(targetSel) : null;
        if (viewModal) {
            const infoCard = viewModal.querySelector('.pt-info-card');
            const statusValue = infoCard ? infoCard.querySelector('.pt-info-value') : null;
            if (statusValue) statusValue.textContent = 'Waived';

            let waivedItem = viewModal.querySelector('.pt-info-item.is-waived-reason');
            if (!waivedItem && infoCard) {
                waivedItem = document.createElement('div');
                waivedItem.className = 'pt-info-item is-full is-waived-reason';
                waivedItem.innerHTML = '<div class="pt-info-label">Waived Reason</div><div class="pt-info-value"></div>';
                infoCard.appendChild(waivedItem);
            }
            if (waivedItem) {
                waivedItem.querySelector('.pt-info-value').textContent = waivedReason || '';
            }
        }

        // Wala nang dahilan para buksan pa ang paid-confirm / waive modal ulit.
        const paidConfirmModalEl = document.getElementById('paidConfirmModal' + penaltyId);
        if (paidConfirmModalEl) paidConfirmModalEl.remove();

        const waiveModalEl = document.getElementById('waiveModal' + penaltyId);
        if (waiveModalEl) waiveModalEl.remove();
    }

    /* ============================================================
       MARK PAID — click ng "Paid" button sa row lang nagbubukas ng
       confirm modal (data-bs-toggle na sa Blade). Yung actual AJAX
       call ay nangyayari lang pag na-click yung "Yes, Mark as Paid"
       button sa loob ng confirm modal.
    ============================================================ */

    document.addEventListener('click', function (e) {
        const confirmBtn = e.target.closest('.pt-btn-confirm-paid');
        if (!confirmBtn) return;

        const penaltyId = confirmBtn.getAttribute('data-penalty-id');
        const url = confirmBtn.getAttribute('data-mark-paid-url');
        const row = document.querySelector('tr.pt-tr[data-penalty-id="' + penaltyId + '"]');
        const paidConfirmModalEl = document.getElementById('paidConfirmModal' + penaltyId);

        if (!url || !row) return;

        confirmBtn.disabled = true;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                confirmBtn.disabled = false;

                if (data && data.success) {
                    if (paidConfirmModalEl && window.bootstrap) {
                        bootstrap.Modal.getOrCreateInstance(paidConfirmModalEl).hide();
                    }
                    updateRowToPaid(row);
                    showSuccessModal(data.message || 'Penalty marked as paid.');
                    render();
                } else {
                    alert('Hindi na-mark as paid. Subukan ulit.');
                }
            })
            .catch(function () {
                confirmBtn.disabled = false;
                alert('May error habang nagma-mark as paid. Subukan ulit.');
            });
    });

    /* ============================================================
       WAIVE PENALTY — AJAX, walang page reload
    ============================================================ */

    document.querySelectorAll('.pt-waive-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const penaltyId = form.getAttribute('data-penalty-id');
            const row = document.querySelector('tr.pt-tr[data-penalty-id="' + penaltyId + '"]');
            const submitBtn = form.querySelector('.pt-btn-confirm-waive');
            const waiveModalEl = document.getElementById('waiveModal' + penaltyId);

            if (submitBtn) submitBtn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (submitBtn) submitBtn.disabled = false;

                    if (data && data.success) {
                        if (waiveModalEl && window.bootstrap) {
                            bootstrap.Modal.getOrCreateInstance(waiveModalEl).hide();
                        }
                        if (row) {
                            updateRowToWaived(row, data.waived_reason, penaltyId);
                        }
                        showSuccessModal(data.message || 'Penalty waived.');
                        render();
                    } else {
                        alert('Hindi na-waive ang penalty. Subukan ulit.');
                    }
                })
                .catch(function () {
                    if (submitBtn) submitBtn.disabled = false;
                    alert('May error habang nagwa-waive. Subukan ulit.');
                });
        });
    });
})();