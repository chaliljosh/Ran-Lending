document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('defaultedScheduleTable');
    if (!table) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const confirmModalEl = document.getElementById('dlMarkPaidConfirmModal');
    const confirmBtn = document.getElementById('dlConfirmMarkPaidBtn');
    const confirmInstallmentNo = document.getElementById('dlConfirmInstallmentNo');
    const confirmAmount = document.getElementById('dlConfirmAmount');

    let pendingScheduleId = null;
    let pendingRowBtn = null;
    let pendingInstallmentNo = null;

    // ---- Step 1: click "Record Payment" -> open confirm modal ----
    table.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-record-payment');
        if (!btn) return;

        pendingScheduleId = btn.dataset.scheduleId;
        pendingRowBtn = btn;
        pendingInstallmentNo = btn.dataset.installmentNo;
        confirmInstallmentNo.textContent = '#' + btn.dataset.installmentNo;
        confirmAmount.textContent = btn.dataset.amountDue;

        const modal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
        modal.show();
    });

    // ---- Step 2: confirm -> fire the actual request ----
    confirmBtn.addEventListener('click', function () {
        if (!pendingScheduleId || !pendingRowBtn) return;

        const scheduleId = pendingScheduleId;
        const rowBtn = pendingRowBtn;
        const installmentNo = pendingInstallmentNo;
        const row = table.querySelector(`tr[data-schedule-row="${scheduleId}"]`);

        // ---- Loading state: spinner icon na lang, walang text ----
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="feather-loader spin-icon"></i>';
        rowBtn.disabled = true;
        rowBtn.innerHTML = '<i class="feather-loader spin-icon"></i>';

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
                window.showAdminToast('error', 'Failed to Mark as Paid', data.message || 'Please try again.');
                rowBtn.disabled = false;
                rowBtn.innerHTML = '<i class="feather-calendar"></i> Record Payment';
                return;
            }

            // ---- Update the specific row ----
            if (row) {
                row.querySelector('[data-field="amount_paid"]').textContent = '₱' + data.amount_paid_display;
                row.querySelector('[data-field="paid_on"]').textContent = data.paid_on_display;
                row.querySelector('[data-field="status"]').innerHTML =
                    '<span class="ols-status-pill is-paid"><i class="feather-check-circle"></i> Paid</span>';
                row.querySelector('[data-field="action"]').innerHTML =
                    '<span class="action-recorded-badge"><i class="feather-check-circle"></i> Recorded</span>';
            }

            // ---- Update installment count (summary, pill) ----
            document.getElementById('dlPaidCountSummary').textContent = data.paid_count;
            document.getElementById('dlTotalCountSummary').textContent = data.total_count;
            document.getElementById('dlPaidCountPill').textContent = data.paid_count;
            document.getElementById('dlTotalCountPill').textContent = data.total_count;

            // ---- Update stat cards ----
            document.getElementById('dlAmountRecoveredValue').textContent = data.amount_collected_display;

            if (typeof data.days_late !== 'undefined') {
                document.getElementById('dlDaysOverdueValue').textContent = data.days_late + ' days';
                document.getElementById('dlDaysOverdueMeta').textContent = data.days_late + ' days overdue';
            }
            if (typeof data.outstanding_amount_display !== 'undefined') {
                document.getElementById('dlOutstandingValue').textContent = data.outstanding_amount_display;
            }
            if (typeof data.unpaid_penalties_display !== 'undefined') {
                document.getElementById('dlUnpaidPenaltiesValue').textContent = data.unpaid_penalties_display;
            }

            document.getElementById('dlLastPaymentValue').textContent = data.paid_on_display;

            // ---- Close modal ----
            const modal = bootstrap.Modal.getInstance(confirmModalEl);
            if (modal) modal.hide();

            // ---- Success toast ----
            window.showAdminToast(
                'success',
                'Payment Recorded',
                'Installment #' + installmentNo + ' has been successfully marked as paid.'
            );
        })
        .catch(err => {
            console.error('[defaulted-loan-detail.js] mark-paid error:', err);
            window.showAdminToast('error', 'Something Went Wrong', 'Please try again.');
            rowBtn.disabled = false;
            rowBtn.innerHTML = '<i class="feather-calendar"></i> Record Payment';
        })
        .finally(() => {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="feather-check"></i> Yes, Mark as Paid';
            pendingScheduleId = null;
            pendingRowBtn = null;
            pendingInstallmentNo = null;
        });
    });
});