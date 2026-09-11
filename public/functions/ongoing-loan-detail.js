document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('olsScheduleTable');
    if (!table) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const confirmModalEl = document.getElementById('olsMarkPaidConfirmModal');
    const confirmBtn = document.getElementById('olsConfirmMarkPaidBtn');
    const confirmInstallmentNo = document.getElementById('olsConfirmInstallmentNo');
    const confirmAmount = document.getElementById('olsConfirmAmount');

    let pendingScheduleId = null;
    let pendingBtn = null;

    // ---- Step 1: click "Record Payment" -> open confirm modal ----
    table.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-record-payment');
        if (!btn) return;

        pendingScheduleId = btn.dataset.scheduleId;
        pendingBtn = btn;
        confirmInstallmentNo.textContent = '#' + btn.dataset.installmentNo;
        confirmAmount.textContent = btn.dataset.amountDue;

        const modal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
        modal.show();
    });

    // ---- Step 2: confirm -> fire the actual request ----
    confirmBtn.addEventListener('click', function () {
        if (!pendingScheduleId || !pendingBtn) return;

        const scheduleId = pendingScheduleId;
        const btn = pendingBtn;
        const row = table.querySelector(`tr[data-schedule-row="${scheduleId}"]`);

        // Loading state: icon only, no text — swap the check icon for a
        // spinning one and hide the label rather than changing the text.
        confirmBtn.disabled = true;
        confirmBtn.classList.add('is-loading');
        if (!confirmBtn.querySelector('.spin-icon')) {
            const spinner = document.createElement('i');
            spinner.className = 'feather-loader spin-icon';
            confirmBtn.insertBefore(spinner, confirmBtn.firstChild);
        }

        fetch(`/admin/installments/${scheduleId}/mark-paid`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                alert(data.message || 'Failed to mark as paid.');
                return;
            }

            if (row) {
                row.querySelector('[data-field="amount_paid"]').textContent = '₱' + data.amount_paid_display;
                row.querySelector('[data-field="progress_fill"]').style.width = '100%';
                row.querySelector('[data-field="progress_pct"]').textContent = '100%';
                row.querySelector('[data-field="paid_on"]').textContent = data.paid_on_display;
                row.querySelector('[data-field="due_sub"]').textContent = 'Paid';
                row.querySelector('[data-field="status"]').innerHTML =
                    '<span class="ols-status-pill is-paid"><i class="feather-check-circle"></i> Paid</span>';
                row.querySelector('[data-field="action"]').querySelector('.ols-kebab-wrap');
                const actionsCell = row.querySelector('[data-field="action"] .ols-actions-cell');
                const kebab = actionsCell.querySelector('.ols-kebab-wrap');
                actionsCell.innerHTML = '<span class="sched-paid-note">Recorded</span>';
                actionsCell.appendChild(kebab);
            }

            document.getElementById('olsPaidCount').textContent = data.paid_count;
            document.getElementById('olsTotalCount').textContent = data.total_count;
            document.getElementById('olsAmountPaidStat').textContent = data.amount_collected_display;
            document.getElementById('olsRemainingStat').textContent = data.remaining_display;
            document.getElementById('olsDueInStat').textContent = data.due_in_label;

            const fill = document.getElementById('olsProgressFill');
            if (fill) fill.style.width = data.progress_pct + '%';

            const nextDueBox = document.getElementById('olsNextDueBox');
            if (data.is_fully_paid) {
                if (nextDueBox) nextDueBox.style.display = 'none';
            } else {
                const nextDueDateEl = document.getElementById('olsNextDueDate');
                if (nextDueDateEl) nextDueDateEl.textContent = data.next_due_date_display;
                if (nextDueBox) nextDueBox.style.display = '';
            }

            const modal = bootstrap.Modal.getInstance(confirmModalEl);
            if (modal) modal.hide();

            // Toast confirmation, matching the pattern already used on
            // Loan Applications / Loan Application Detail.
            if (typeof window.showAdminToast === 'function') {
                window.showAdminToast('success', 'Payment Recorded', 'Payment recorded successfully.');
            }
        })
        .catch(err => {
            console.error('[ongoing-loan-detail.js] mark-paid error:', err);
            alert('Something went wrong. Please try again.');
        })
        .finally(() => {
            confirmBtn.disabled = false;
            confirmBtn.classList.remove('is-loading');
            const spinner = confirmBtn.querySelector('.spin-icon');
            if (spinner) spinner.remove();
            pendingScheduleId = null;
            pendingBtn = null;
        });
    });

    // ---- Simple client-side pagination (10 rows per page) ----
    const rows = Array.from(table.querySelectorAll('tbody tr[data-page-row]'));
    const rowsPerPage = 10;
    let currentPage = 1;
    const totalPages = Math.max(1, Math.ceil(rows.length / rowsPerPage));

    const pagination = document.getElementById('olsPagination');
    const pageIndicator = document.getElementById('olsPageIndicator');
    const prevBtn = document.getElementById('olsPrevPage');
    const nextBtn = document.getElementById('olsNextPage');

    function renderSchedulePage() {
        rows.forEach((row, i) => {
            const page = Math.floor(i / rowsPerPage) + 1;
            row.style.display = page === currentPage ? '' : 'none';
        });
        if (pageIndicator) pageIndicator.textContent = currentPage;
        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
    }

    if (rows.length > rowsPerPage && pagination) {
        pagination.style.display = 'flex';
        prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderSchedulePage(); } });
        nextBtn.addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; renderSchedulePage(); } });
        renderSchedulePage();
    }
});