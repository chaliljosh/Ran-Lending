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
    const errorEl = document.getElementById('olsMarkPaidError');

    // Gamit pa rin sa Send Proof modal
    const MAX_RECEIPT_BYTES = 5 * 1024 * 1024; // 5MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png'];

    let pendingScheduleId = null;
    let pendingBtn = null;

    // ---- "Use borrower's receipt" mode (galing sa View Receipt modal) ----
    const borrowerReceiptField = document.getElementById('olsUsingBorrowerReceipt');
    const borrowerReceiptThumb = document.getElementById('olsUsingBorrowerThumb');
    const useOwnReceiptBtn = document.getElementById('olsUseOwnReceiptBtn');
    let useBorrowerReceipt = false;

    function setBorrowerReceiptMode(url) {
        useBorrowerReceipt = !!url;
        borrowerReceiptField.style.display = useBorrowerReceipt ? '' : 'none';
        if (useBorrowerReceipt) {
            borrowerReceiptThumb.src = url;
        } else {
            borrowerReceiptThumb.removeAttribute('src');
        }
    }

    // X -> alisin ang receipt ng borrower (walang receipt na isasama)
    useOwnReceiptBtn.addEventListener('click', function () {
        setBorrowerReceiptMode(null);
        showError('');
    });

    function todayLocal() {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 10);
    }

    // ============================================================
    // Custom date picker (calendar + year grid)
    // displayEl = nakikitang text field (MM/DD/YYYY)
    // hiddenEl  = tunay na value (YYYY-MM-DD)
    // hostEl    = modal element kung saan ilalagay ang popup
    // ============================================================
    function createDatePicker(displayEl, hiddenEl, hostEl) {
        const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        const WEEKDAYS = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

        const pad = n => String(n).padStart(2, '0');
        const toYmd = (y, m, d) => y + '-' + pad(m + 1) + '-' + pad(d); // m = 0-based
        const parseYmd = str => {
            const parts = str.split('-').map(Number);
            return { y: parts[0], m: parts[1] - 1, d: parts[2] };
        };

        let minYmd = '';
        let maxYmd = '';
        let view = 'days'; // 'days' o 'years'
        let viewYear = 0;
        let viewMonth = 0;
        let isOpen = false;

        const pop = document.createElement('div');
        pop.className = 'ols-dp';
        pop.style.display = 'none';
        pop.setAttribute('role', 'dialog');
        hostEl.appendChild(pop);

        function setValue(ymd) {
            hiddenEl.value = ymd || '';
            if (ymd) {
                const p = parseYmd(ymd);
                displayEl.value = pad(p.m + 1) + '/' + pad(p.d) + '/' + p.y;
            } else {
                displayEl.value = '';
            }
        }

        function setRange(min, max) {
            minYmd = min || '';
            maxYmd = max || '';
        }

        function isDisabled(ymd) {
            return (minYmd && ymd < minYmd) || (maxYmd && ymd > maxYmd);
        }

        function monthIndex(ymd) {
            const p = parseYmd(ymd);
            return p.y * 12 + p.m;
        }

        function headerHtml(showNav) {
            const prevOff = minYmd && (viewYear * 12 + viewMonth) <= monthIndex(minYmd);
            const nextOff = maxYmd && (viewYear * 12 + viewMonth) >= monthIndex(maxYmd);
            const caret = view === 'years' ? 'feather-chevron-up' : 'feather-chevron-down';
            let html = '<div class="ols-dp-head">' +
                '<button type="button" class="ols-dp-title" data-act="toggle-view">' +
                MONTHS[viewMonth] + ' ' + viewYear + ' <i class="' + caret + '"></i></button>';
            if (showNav) {
                html += '<div class="ols-dp-nav">' +
                    '<button type="button" class="ols-dp-navbtn" data-act="prev" aria-label="Previous month"' + (prevOff ? ' disabled' : '') + '><i class="feather-chevron-left"></i></button>' +
                    '<button type="button" class="ols-dp-navbtn" data-act="next" aria-label="Next month"' + (nextOff ? ' disabled' : '') + '><i class="feather-chevron-right"></i></button>' +
                    '</div>';
            }
            return html + '</div>';
        }

        function render() {
            if (view === 'years') {
                renderYears();
            } else {
                renderDays();
            }
            if (isOpen) position();
        }

        function renderDays() {
            const firstWeekday = new Date(viewYear, viewMonth, 1).getDay();
            const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
            const selected = hiddenEl.value;
            const today = todayLocal();

            let html = headerHtml(true);
            html += '<div class="ols-dp-weekdays">' + WEEKDAYS.map(w => '<span>' + w + '</span>').join('') + '</div>';
            html += '<div class="ols-dp-grid">';
            for (let i = 0; i < firstWeekday; i++) html += '<span></span>';
            for (let d = 1; d <= daysInMonth; d++) {
                const ymd = toYmd(viewYear, viewMonth, d);
                let cls = 'ols-dp-day';
                if (ymd === today) cls += ' is-today';
                if (ymd === selected) cls += ' is-selected';
                html += '<button type="button" class="' + cls + '" data-ymd="' + ymd + '"' +
                    (isDisabled(ymd) ? ' disabled' : '') + '>' + d + '</button>';
            }
            html += '</div>';
            pop.innerHTML = html;
        }

        function renderYears() {
            const thisYear = new Date().getFullYear();
            const minY = minYmd ? parseYmd(minYmd).y : thisYear - 10;
            const maxY = maxYmd ? parseYmd(maxYmd).y : thisYear + 20;

            let html = headerHtml(false) + '<div class="ols-dp-years">';
            for (let y = minY; y <= maxY; y++) {
                html += '<button type="button" class="ols-dp-year' + (y === viewYear ? ' is-selected' : '') +
                    '" data-year="' + y + '">' + y + '</button>';
            }
            html += '</div>';
            pop.innerHTML = html;

            // I-scroll ang listahan para nasa gitna ang napiling taon
            const grid = pop.querySelector('.ols-dp-years');
            const current = pop.querySelector('.ols-dp-year.is-selected');
            if (grid && current) {
                grid.scrollTop = current.offsetTop - (grid.clientHeight / 2) + (current.clientHeight / 2);
            }
        }

        function position() {
            const r = displayEl.getBoundingClientRect();
            const w = pop.offsetWidth;
            const h = pop.offsetHeight;

            let top = r.bottom + 6;
            if (top + h > window.innerHeight - 8) {
                top = Math.max(8, r.top - h - 6); // kapag kulang ang espasyo sa baba, sa taas ilalabas
            }
            const left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));

            pop.style.top = top + 'px';
            pop.style.left = left + 'px';
        }

        function open() {
            if (isOpen) return;
            const base = hiddenEl.value || todayLocal();
            let p = parseYmd(base);
            if (isDisabled(base)) {
                p = parseYmd(maxYmd && base > maxYmd ? maxYmd : minYmd);
            }
            viewYear = p.y;
            viewMonth = p.m;
            view = 'days';
            isOpen = true;
            pop.style.display = 'block';
            render();
        }

        function close() {
            if (!isOpen) return;
            isOpen = false;
            pop.style.display = 'none';
        }

        function shiftMonth(delta) {
            const idx = viewYear * 12 + viewMonth + delta;
            viewYear = Math.floor(idx / 12);
            viewMonth = idx % 12;
            render();
        }

        function pickYear(year) {
            viewYear = year;
            // Siguraduhing nasa loob ng pinapayagang buwan
            if (minYmd && (viewYear * 12 + viewMonth) < monthIndex(minYmd)) viewMonth = parseYmd(minYmd).m;
            if (maxYmd && (viewYear * 12 + viewMonth) > monthIndex(maxYmd)) viewMonth = parseYmd(maxYmd).m;
            view = 'days';
            render();
        }

        pop.addEventListener('click', function (e) {
            const actBtn = e.target.closest('[data-act]');
            if (actBtn) {
                const act = actBtn.dataset.act;
                if (act === 'toggle-view') { view = view === 'days' ? 'years' : 'days'; render(); }
                if (act === 'prev') shiftMonth(-1);
                if (act === 'next') shiftMonth(1);
                return;
            }
            const dayBtn = e.target.closest('.ols-dp-day');
            if (dayBtn && !dayBtn.disabled) {
                setValue(dayBtn.dataset.ymd);
                close();
                displayEl.focus();
                return;
            }
            const yearBtn = e.target.closest('.ols-dp-year');
            if (yearBtn) pickYear(Number(yearBtn.dataset.year));
        });

        displayEl.addEventListener('click', function () { isOpen ? close() : open(); });
        displayEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                open();
            }
        });

        // Click sa labas -> isara
        document.addEventListener('mousedown', function (e) {
            if (!isOpen) return;
            if (pop.contains(e.target) || displayEl.parentElement.contains(e.target)) return;
            close();
        });

        // Escape -> isara lang ang calendar (hindi ang buong modal)
        hostEl.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen) {
                e.stopImmediatePropagation();
                e.preventDefault();
                close();
            }
        }, true);

        hostEl.addEventListener('hide.bs.modal', close);
        hostEl.addEventListener('scroll', close);
        window.addEventListener('resize', close);

        return { setValue: setValue, setRange: setRange, close: close };
    }

    const datePicker = createDatePicker(
        document.getElementById('olsPaidDateDisplay'),
        paidDateInput,
        confirmModalEl
    );

    function showError(msg) {
        errorEl.textContent = msg || '';
        errorEl.style.display = msg ? '' : 'none';
    }

    // Gamit sa Send Proof modal
    function formatSize(bytes) {
        return bytes >= 1048576
            ? (bytes / 1048576).toFixed(1) + ' MB'
            : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function resetForm() {
        showError('');
        setBorrowerReceiptMode(null);
        const today = todayLocal();
        // Hindi puwede sa hinaharap (walang minimum na petsa)
        datePicker.close();
        datePicker.setRange('', today);
        datePicker.setValue(today);
    }

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
            upcoming: ['is-upcoming', 'Pending'],
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

    // ---- View Borrower Receipt (receipt na in-upload ng borrower) ----
    const brModalEl = document.getElementById('olsBorrowerReceiptModal');
    if (brModalEl) {
        const br = id => document.getElementById(id);
        const brImg = br('olsBrImg');
        const brImgLink = br('olsBrImgLink');
        const brHint = br('olsBrHint');
        const brLoading = br('olsBrLoading');
        const brError = br('olsBrError');
        const brDownload = br('olsBrDownload');
        const brRecordBtn = br('olsBrRecordBtn');
        let brScheduleId = null;
        let brFileUrl = null;

        function resetBorrowerReceipt() {
            brImg.onload = null;
            brImg.onerror = null;
            brImg.removeAttribute('src');
            brImgLink.style.display = 'none';
            brHint.style.display = 'none';
            brError.style.display = 'none';
            brLoading.style.display = '';
        }

        table.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-view-borrower-receipt');
            if (!btn) return;
            const d = btn.dataset;

            brScheduleId = d.scheduleId;
            brFileUrl = d.fileUrl;
            br('olsBrInstallment').textContent = '#' + d.installmentNo;
            br('olsBrAmount').textContent = d.amountDue || '0.00';
            br('olsBrDue').textContent = d.due || '—';
            br('olsBrSubmitted').textContent = d.submitted || '—';
            brDownload.href = d.downloadUrl || '#';
            brImgLink.href = d.fileUrl || '#';

            resetBorrowerReceipt();

            // Set handlers BEFORE src para hindi ma-miss ang event
            brImg.onload = function () {
                brLoading.style.display = 'none';
                brImgLink.style.display = '';
                brHint.style.display = '';
            };
            brImg.onerror = function () {
                brLoading.style.display = 'none';
                brError.style.display = '';
            };
            brImg.src = d.fileUrl;

            bootstrap.Modal.getOrCreateInstance(brModalEl).show();
        });

        // "Record Payment" sa loob ng receipt modal -> isara muna, tapos buksan ang existing Record Payment modal
        brRecordBtn.addEventListener('click', function () {
            const rowBtn = table.querySelector(`.btn-record-payment[data-schedule-id="${brScheduleId}"]`);
            if (!rowBtn) return;

            const fileUrl = brFileUrl;
            brModalEl.addEventListener('hidden.bs.modal', function () {
                rowBtn.click();                    // bubuksan ang Mark as Paid modal (nire-reset nito ang form)
                setBorrowerReceiptMode(fileUrl);   // tapos gagamitin na ang receipt ng borrower
            }, { once: true });

            bootstrap.Modal.getOrCreateInstance(brModalEl).hide();
        });

        brModalEl.addEventListener('hidden.bs.modal', resetBorrowerReceipt);
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

    // ---- Send Proof of Release ----
    // ISANG BESES lang per loan: proof na na-release na ang loan sa borrower -> ie-email sa borrower.
    // Ang button ay nasa page header (hindi na sa row ng installment).
    const proofModalEl = document.getElementById('olsSendProofModal');
    const proofOpenBtn = document.getElementById('olsSendProofBtn');
    if (proofModalEl && proofOpenBtn) {
        const proofInput = document.getElementById('olsProofInput');
        const proofDropzone = document.getElementById('olsProofDropzone');
        const proofPreview = document.getElementById('olsProofPreview');
        const proofPreviewImg = document.getElementById('olsProofPreviewImg');
        const proofFileName = document.getElementById('olsProofFileName');
        const proofFileSize = document.getElementById('olsProofFileSize');
        const proofRemoveBtn = document.getElementById('olsProofRemove');
        const proofErrorEl = document.getElementById('olsProofError');
        const proofSendBtn = document.getElementById('olsProofSendBtn');

        const proofApplicationId = proofOpenBtn.dataset.applicationId;
        let proofFile = null;
        let proofPreviewUrl = null;

        function showProofError(msg) {
            proofErrorEl.textContent = msg || '';
            proofErrorEl.style.display = msg ? '' : 'none';
        }

        function clearProof() {
            if (proofPreviewUrl) { URL.revokeObjectURL(proofPreviewUrl); proofPreviewUrl = null; }
            proofFile = null;
            proofInput.value = '';
            proofPreviewImg.removeAttribute('src');
            proofPreview.style.display = 'none';
            proofDropzone.style.display = '';
        }

        proofInput.addEventListener('change', function () {
            const file = proofInput.files[0];
            clearProof();
            showProofError('');
            if (!file) return;

            if (!ALLOWED_TYPES.includes(file.type)) {
                showProofError('Only JPG or PNG images are allowed.');
                return;
            }
            if (file.size > MAX_RECEIPT_BYTES) {
                showProofError('File is too large. Maximum size is 5MB.');
                return;
            }

            proofFile = file;
            proofPreviewUrl = URL.createObjectURL(file);
            proofPreviewImg.src = proofPreviewUrl;
            proofFileName.textContent = file.name;
            proofFileSize.textContent = formatSize(file.size);
            proofDropzone.style.display = 'none';
            proofPreview.style.display = '';
        });

        proofRemoveBtn.addEventListener('click', function () {
            clearProof();
            showProofError('');
        });

        // Buksan ang modal mula sa "Send Proof of Release" button sa header
        proofOpenBtn.addEventListener('click', function () {
            document.getElementById('olsProofAmount').textContent = proofOpenBtn.dataset.amount;
            document.getElementById('olsProofBorrowerName').textContent = proofOpenBtn.dataset.borrowerName;
            document.getElementById('olsProofBorrowerEmail').textContent = proofOpenBtn.dataset.borrowerEmail;
            clearProof();
            showProofError('');
            bootstrap.Modal.getOrCreateInstance(proofModalEl).show();
        });

        proofModalEl.addEventListener('hidden.bs.modal', function () {
            clearProof();
            showProofError('');
        });

        proofSendBtn.addEventListener('click', function () {
            if (!proofApplicationId) return;
            showProofError('');
            if (!proofFile) { showProofError('Please attach the proof.'); return; }

            const formData = new FormData();
            formData.append('proof', proofFile);

            proofSendBtn.disabled = true;
            proofSendBtn.classList.add('is-loading');
            if (!proofSendBtn.querySelector('.spin-icon')) {
                const spinner = document.createElement('i');
                spinner.className = 'feather-loader spin-icon';
                proofSendBtn.insertBefore(spinner, proofSendBtn.firstChild);
            }

            fetch(`/admin/loans/${proofApplicationId}/send-proof`, {
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
                    showProofError(firstError || data.message || 'Failed to send the proof.');
                    return;
                }

                const modal = bootstrap.Modal.getInstance(proofModalEl);
                if (modal) modal.hide();

                if (typeof window.showAdminToast === 'function') {
                    window.showAdminToast('success', 'Proof Sent', data.message || 'Proof sent to the borrower.');
                }
            })
            .catch(err => {
                console.error('[ongoing-loan-detail.js] send-proof error:', err);
                showProofError('Something went wrong. Please try again.');
            })
            .finally(() => {
                proofSendBtn.disabled = false;
                proofSendBtn.classList.remove('is-loading');
                const spinner = proofSendBtn.querySelector('.spin-icon');
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

        const scheduleId = pendingScheduleId;
        const btn = pendingBtn;
        const row = table.querySelector(`tr[data-schedule-row="${scheduleId}"]`);

        const formData = new FormData();
        formData.append('paid_date', paidDate);
        // Receipt ng borrower lang ang puwedeng isama (galing sa View Receipt)
        if (useBorrowerReceipt) {
            formData.append('use_borrower_receipt', '1');
        }

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
                // Pinapalitan din nito ang "Receipt submitted" flag sa status cell
                row.querySelector('[data-field="status"]').innerHTML =
                    '<span class="ols-status-pill is-paid"><i class="feather-check-circle"></i> Paid</span>';

                // Replace "Record Payment" (at "View Receipt") with the "Recorded" note, keep the kebab dropdown
                const actionsCell = row.querySelector('[data-field="action"] .ols-actions-cell');
                const kebab = actionsCell.querySelector('.ols-kebab-wrap');
                actionsCell.innerHTML = '<span class="sched-paid-note">Recorded</span>';
                actionsCell.appendChild(kebab);
                kebab.classList.remove('d-none'); // lalabas na ang View Payment Details / Void

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