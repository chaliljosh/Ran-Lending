document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('olsScheduleTable');
    if (!table) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const confirmModalEl = document.getElementById('olsMarkPaidConfirmModal');
    const confirmBtn = document.getElementById('olsConfirmMarkPaidBtn');
    const confirmInstallmentNo = document.getElementById('olsConfirmInstallmentNo');
    const confirmAmount = document.getElementById('olsConfirmAmount');

    // ---- Payment form fields ----
    const paidDateInput = document.getElementById('olsPaidDate');
    const receiptInput = document.getElementById('olsReceiptInput');
    const dropzone = document.getElementById('olsDropzone');
    const previewWrap = document.getElementById('olsReceiptPreview');
    const previewImg = document.getElementById('olsReceiptPreviewImg');
    const fileNameEl = document.getElementById('olsReceiptFileName');
    const fileSizeEl = document.getElementById('olsReceiptFileSize');
    const removeReceiptBtn = document.getElementById('olsReceiptRemove');
    const errorEl = document.getElementById('olsMarkPaidError');

    const MAX_RECEIPT_BYTES = 5 * 1024 * 1024; // 5MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png'];

    let pendingScheduleId = null;
    let pendingBtn = null;
    let selectedFile = null;
    let previewUrl = null;

    function todayLocal() {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 10);
    }

    function showError(msg) {
        errorEl.textContent = msg || '';
        errorEl.style.display = msg ? '' : 'none';
    }

    function formatSize(bytes) {
        return bytes >= 1048576
            ? (bytes / 1048576).toFixed(1) + ' MB'
            : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function clearReceipt() {
        if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
        selectedFile = null;
        receiptInput.value = '';
        previewImg.removeAttribute('src');
        previewWrap.style.display = 'none';
        dropzone.style.display = '';
    }

    function resetForm() {
        clearReceipt();
        showError('');
        const today = todayLocal();
        paidDateInput.max = today;
        paidDateInput.value = today;
    }

    receiptInput.addEventListener('change', function () {
        const file = receiptInput.files[0];
        clearReceipt();
        showError('');
        if (!file) return;

        if (!ALLOWED_TYPES.includes(file.type)) {
            showError('Only JPG or PNG images are allowed.');
            return;
        }
        if (file.size > MAX_RECEIPT_BYTES) {
            showError('File is too large. Maximum size is 5MB.');
            return;
        }

        selectedFile = file;
        previewUrl = URL.createObjectURL(file);
        previewImg.src = previewUrl;
        fileNameEl.textContent = file.name;
        fileSizeEl.textContent = formatSize(file.size);
        dropzone.style.display = 'none';
        previewWrap.style.display = '';
    });

    removeReceiptBtn.addEventListener('click', function () {
        clearReceipt();
        showError('');
    });

    // ---- Step 1: click "Record Payment" -> open confirm modal ----
    table.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-record-payment');
        if (!btn) return;

        pendingScheduleId = btn.dataset.scheduleId;
        pendingBtn = btn;
        confirmInstallmentNo.textContent = '#' + btn.dataset.installmentNo;
        confirmAmount.textContent = btn.dataset.amountDue;
        resetForm();

        const modal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
        modal.show();
    });

    // ---- View Payment Details ----
    const detailsModalEl = document.getElementById('olsPaymentDetailsModal');
    if (detailsModalEl) {
        const el = id => document.getElementById(id);
        const dImg = el('olsPdReceiptImg');
        const dEmpty = el('olsPdReceiptEmpty');
        const dFull = el('olsPdFullLink');
        const dStatus = el('olsPdStatus');
        const STATUS = {
            paid:     ['is-paid', 'Paid'],
            overdue:  ['is-overdue', 'Overdue'],
            upcoming: ['is-upcoming', 'Upcoming'],
        };

        // Show "View Full Receipt" only when the image is scaled down to fit
        function updateFullLink() {
            if (dImg.style.display === 'none') { dFull.style.display = 'none'; return; }
            const scaled = dImg.naturalWidth > dImg.clientWidth + 1 || dImg.naturalHeight > dImg.clientHeight + 1;
            dFull.style.display = scaled ? '' : 'none';
        }

        table.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-view-payment-details');
            if (!btn) return;
            const d = btn.dataset;

            el('olsPdInstallment').textContent = '#' + d.installmentNo;
            el('olsPdDue').textContent = d.due || '—';
            el('olsPdPaid').textContent = d.paid || '—';
            el('olsPdAmount').textContent = d.amount ? '₱' + d.amount : '—';
            el('olsPdRecorded').textContent = d.recorded || '—';

            const [cls, label] = STATUS[d.status] || STATUS.upcoming;
            dStatus.className = 'ols-status-pill ' + cls;
            dStatus.textContent = label;

            dFull.style.display = 'none';
            if (d.receiptUrl) {
                dFull.href = d.receiptUrl;
                dEmpty.style.display = 'none';
                dImg.style.display = '';
                dImg.onload = updateFullLink;
                dImg.onerror = function () {
                    dImg.style.display = 'none';
                    dEmpty.textContent = 'Unable to load the receipt.';
                    dEmpty.style.display = '';
                };
                dImg.src = d.receiptUrl;
            } else {
                dImg.removeAttribute('src');
                dImg.style.display = 'none';
                dEmpty.textContent = 'No receipt uploaded.';
                dEmpty.style.display = '';
            }

            bootstrap.Modal.getOrCreateInstance(detailsModalEl).show();
        });

        // The modal isn't laid out until it's shown, so re-check then
        detailsModalEl.addEventListener('shown.bs.modal', updateFullLink);
        detailsModalEl.addEventListener('hidden.bs.modal', function () {
            dImg.onload = null;
            dImg.onerror = null;
            dImg.removeAttribute('src');
        });
    }

    // ---- Toast shown after a page reload (used by Void Payment) ----
    const flashToast = sessionStorage.getItem('olsFlashToast');
    if (flashToast) {
        sessionStorage.removeItem('olsFlashToast');
        if (typeof window.showAdminToast === 'function') {
            window.showAdminToast('success', 'Payment Voided', flashToast);
        }
    }

    // ---- Void Payment ----
    const voidModalEl = document.getElementById('olsVoidModal');
    if (voidModalEl) {
        const voidBtn = document.getElementById('olsConfirmVoidBtn');
        const voidReason = document.getElementById('olsVoidReason');
        const voidError = document.getElementById('olsVoidError');
        let voidScheduleId = null;

        function showVoidError(msg) {
            voidError.textContent = msg || '';
            voidError.style.display = msg ? '' : 'none';
        }

        table.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-void-payment');
            if (!btn) return;

            voidScheduleId = btn.dataset.scheduleId;
            document.getElementById('olsVoidInstallmentNo').textContent = '#' + btn.dataset.installmentNo;
            document.getElementById('olsVoidAmount').textContent = btn.dataset.amount;
            voidReason.value = '';
            showVoidError('');
            bootstrap.Modal.getOrCreateInstance(voidModalEl).show();
        });

        voidBtn.addEventListener('click', function () {
            if (!voidScheduleId) return;

            const reason = voidReason.value.trim();
            if (reason.length < 5) { showVoidError('Please enter a reason (at least 5 characters).'); return; }
            showVoidError('');

            voidBtn.disabled = true;
            voidBtn.classList.add('is-loading');
            if (!voidBtn.querySelector('.spin-icon')) {
                const spinner = document.createElement('i');
                spinner.className = 'feather-loader spin-icon';
                voidBtn.insertBefore(spinner, voidBtn.firstChild);
            }

            fetch(`/admin/installments/${voidScheduleId}/void`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ reason }),
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                    showVoidError(firstError || data.message || 'Failed to void the payment.');
                    return;
                }
                // Reload so the stats, progress, next due, and row status are all recomputed by the server
                sessionStorage.setItem('olsFlashToast', data.message || 'Payment voided successfully.');
                window.location.reload();
            })
            .catch(err => {
                console.error('[ongoing-loan-detail.js] void error:', err);
                showVoidError('Something went wrong. Please try again.');
            })
            .finally(() => {
                voidBtn.disabled = false;
                voidBtn.classList.remove('is-loading');
                const spinner = voidBtn.querySelector('.spin-icon');
                if (spinner) spinner.remove();
            });
        });
    }

    // ---- Step 2: confirm -> fire the actual request ----
    confirmBtn.addEventListener('click', function () {
        if (!pendingScheduleId || !pendingBtn) return;

        // Client-side validation (server pa rin ang final na authority)
        showError('');
        const paidDate = paidDateInput.value;
        if (!paidDate) { showError('Please select the date the payment was made.'); return; }
        if (paidDate > todayLocal()) { showError('The payment date cannot be in the future.'); return; }
        if (!selectedFile) { showError('Please attach the receipt.'); return; }

        const scheduleId = pendingScheduleId;
        const btn = pendingBtn;
        const row = table.querySelector(`tr[data-schedule-row="${scheduleId}"]`);

        const formData = new FormData();
        formData.append('paid_date', paidDate);
        formData.append('receipt', selectedFile);
        formData.append('require_proof', '1');

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
                // Huwag mag-set ng Content-Type — ang browser ang bahala (multipart boundary)
            },
            body: formData,
        })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                showError(firstError || data.message || 'Failed to mark as paid.');
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

                // Replace "Record Payment" with the "Recorded" note, keep the kebab dropdown
                const actionsCell = row.querySelector('[data-field="action"] .ols-actions-cell');
                const kebab = actionsCell.querySelector('.ols-kebab-wrap');
                actionsCell.innerHTML = '<span class="sched-paid-note">Recorded</span>';
                actionsCell.appendChild(kebab);

                // Keep the "View Payment Details" modal data in sync with the newly recorded payment
                const detailsBtn = row.querySelector('.js-view-payment-details');
                if (detailsBtn) {
                    const longDate = dt => dt.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                    const [y, m, dd] = paidDate.split('-').map(Number);
                    detailsBtn.dataset.status = 'paid';
                    detailsBtn.dataset.amount = data.amount_paid_display;
                    detailsBtn.dataset.paid = longDate(new Date(y, m - 1, dd));
                    detailsBtn.dataset.recorded = longDate(new Date());
                    detailsBtn.dataset.receiptUrl = data.receipt_url || '';
                }

                // Reveal "Void Payment" for the newly recorded payment
                const voidItem = row.querySelector('.js-void-item');
                if (voidItem) {
                    voidItem.classList.remove('d-none');
                    const voidTrigger = voidItem.querySelector('.js-void-payment');
                    if (voidTrigger) voidTrigger.dataset.amount = data.amount_paid_display;
                }
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

            if (typeof window.showAdminToast === 'function') {
                window.showAdminToast('success', 'Payment Recorded', 'Payment recorded successfully.');
            }
        })
        .catch(err => {
            console.error('[ongoing-loan-detail.js] mark-paid error:', err);
            showError('Something went wrong. Please try again.');
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