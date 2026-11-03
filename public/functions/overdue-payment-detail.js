(function () {
    const config = document.getElementById('opd-config');
    if (!config) return;

    const csrfToken = config.dataset.csrf;
    const markPaidUrl = config.dataset.markPaidUrl || null;
    const waivePenaltyUrl = config.dataset.waiveUrl || null;
    const scheduleId = config.dataset.scheduleId || '';
    const rejectUrl = scheduleId ? '/admin/installments/' + scheduleId + '/reject-receipt' : null;

    const confirmPaidModalEl = document.getElementById('opdConfirmPaidModal');
    const partialModalEl = document.getElementById('opdPartialModal');
    const waiveModalEl = document.getElementById('opdWaiveModal');
    const rejectModalEl = document.getElementById('opdRejectModal');

    const fullEls = {
        btn: document.getElementById('opdConfirmPaidBtn'),
        text: document.getElementById('opdConfirmPaidText'),
        spinner: document.getElementById('opdConfirmPaidSpinner'),
        error: document.getElementById('opdPaidError'),
        dateInput: document.getElementById('opdPaidDate'),
        dateDisplay: document.getElementById('opdPaidDateDisplay'),
        receiptWrap: document.getElementById('opdUsingBorrowerReceipt'),
        receiptThumb: document.getElementById('opdUsingBorrowerThumb'),
        receiptRemove: document.getElementById('opdUseNoReceiptBtn'),
    };

    const partialEls = {
        btn: document.getElementById('opdPartialBtn'),
        text: document.getElementById('opdPartialBtnText'),
        spinner: document.getElementById('opdPartialSpinner'),
        error: document.getElementById('opdPartialError'),
        dateInput: document.getElementById('opdPartialDate'),
        dateDisplay: document.getElementById('opdPartialDateDisplay'),
        amount: document.getElementById('opdPartialAmount'),
        hint: document.getElementById('opdPartialHint'),
        receiptWrap: document.getElementById('opdPUsingBorrowerReceipt'),
        receiptThumb: document.getElementById('opdPUsingBorrowerThumb'),
        receiptRemove: document.getElementById('opdPUseNoReceiptBtn'),
    };

    const rejectEls = {
        btn: document.getElementById('opdRejectConfirmBtn'),
        text: document.getElementById('opdRejectConfirmText'),
        spinner: document.getElementById('opdRejectSpinner'),
        error: document.getElementById('opdRejectError'),
        reason: document.getElementById('opdRejectReason'),
    };

    const brModalEl = document.getElementById('opdBorrowerReceiptModal');
    const brLoading = document.getElementById('opdBrLoading');
    const brError = document.getElementById('opdBrError');
    const brImg = document.getElementById('opdBrImg');
    const brImgLink = document.getElementById('opdBrImgLink');
    const brHint = document.getElementById('opdBrHint');
    const brRecordBtn = document.getElementById('opdBrRecordBtn');
    const brPartialBtn = document.getElementById('opdBrPartialBtn');
    const brRejectBtn = document.getElementById('opdBrRejectBtn');
    const brFileUrl = brModalEl ? (brModalEl.dataset.fileUrl || '') : '';
    const brPaidYmd = brModalEl ? (brModalEl.dataset.paidYmd || '') : '';

    let pendingTarget = null;
    let fullUsesReceipt = false;
    let partialUsesReceipt = false;

    function showSuccessThenReload(title, desc) {
        if (window.showAdminToast) {
            showAdminToast('success', title, desc);
        }
        setTimeout(function () {
            window.location.reload();
        }, 3500);
    }

    function showError(el, message) {
        if (!el) return;
        el.textContent = message;
        el.classList.remove('d-none');
    }

    function clearError(el) {
        if (!el) return;
        el.textContent = '';
        el.classList.add('d-none');
    }

    function todayLocal() {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 10);
    }

    function formatPeso(value) {
        return value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setLoading(els, loading) {
        els.btn.disabled = loading;
        els.text.classList.toggle('d-none', loading);
        els.spinner.classList.toggle('d-none', !loading);
    }

    function createDatePicker(displayEl, hiddenEl, hostEl) {
        const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        const WEEKDAYS = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

        const pad = n => String(n).padStart(2, '0');
        const toYmd = (y, m, d) => y + '-' + pad(m + 1) + '-' + pad(d);
        const parseYmd = str => {
            const parts = str.split('-').map(Number);
            return { y: parts[0], m: parts[1] - 1, d: parts[2] };
        };

        let minYmd = '';
        let maxYmd = '';
        let view = 'days';
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
                top = Math.max(8, r.top - h - 6);
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

        document.addEventListener('mousedown', function (e) {
            if (!isOpen) return;
            if (pop.contains(e.target) || displayEl.parentElement.contains(e.target)) return;
            close();
        });

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

    const fullPicker = (confirmPaidModalEl && fullEls.dateDisplay && fullEls.dateInput)
        ? createDatePicker(fullEls.dateDisplay, fullEls.dateInput, confirmPaidModalEl)
        : null;

    const partialPicker = (partialModalEl && partialEls.dateDisplay && partialEls.dateInput)
        ? createDatePicker(partialEls.dateDisplay, partialEls.dateInput, partialModalEl)
        : null;

    function initialDate(fromReceipt) {
        const today = todayLocal();
        if (fromReceipt && brPaidYmd && brPaidYmd <= today) {
            return brPaidYmd;
        }
        return today;
    }

    function applyReceipt(wrap, thumb, on) {
        const active = !!on && !!wrap;
        if (wrap) {
            wrap.style.display = active ? '' : 'none';
            if (active && thumb) {
                thumb.src = brFileUrl;
            }
        }
        return active;
    }

    /* ------------------------------------------------------------
     | Partial payment amounts
     | total outstanding = installment balance + penalty
     | max partial       = total - 0.01 (full amount => Record Payment)
     |------------------------------------------------------------*/
    function partialInstallment() {
        return partialEls.amount ? parseFloat(partialEls.amount.dataset.installment || '0') : 0;
    }

    function partialPenalty() {
        return partialEls.amount ? parseFloat(partialEls.amount.dataset.penalty || '0') : 0;
    }

    function partialTotal() {
        return Math.round((partialInstallment() + partialPenalty()) * 100) / 100;
    }

    function partialMax() {
        return Math.max(0, Math.round((partialTotal() - 0.01) * 100) / 100);
    }

    function clampPartialAmount() {
        const input = partialEls.amount;
        if (!input || input.value === '') return false;

        let raw = input.value;
        const dot = raw.indexOf('.');
        if (dot !== -1 && raw.length - dot - 1 > 2) {
            raw = raw.slice(0, dot + 3);
        }

        const value = parseFloat(raw);
        let capped = false;

        if (!isFinite(value) || value < 0) {
            raw = '';
        } else if (value > partialMax()) {
            raw = partialMax().toFixed(2);
            capped = true;
        }

        if (raw !== input.value) {
            input.value = raw;
        }
        return capped;
    }

    function refreshPartialHint(capped) {
        if (!partialEls.amount || !partialEls.hint) return;
        const max = partialMax();
        const value = parseFloat(partialEls.amount.value);

        if (capped) {
            partialEls.hint.textContent = 'The most you can record here is \u20B1' + formatPeso(max) + '. For the full amount, use Record Payment.';
            return;
        }
        if (!value || value <= 0) {
            partialEls.hint.textContent = 'Enter the amount shown on the receipt (up to \u20B1' + formatPeso(max) + ').';
            return;
        }

        // Installment muna, penalty ang sumunod
        const toInstallment = Math.min(value, partialInstallment());
        const toPenalty = Math.max(0, Math.round((value - toInstallment) * 100) / 100);
        const remaining = Math.max(0, Math.round((partialTotal() - value) * 100) / 100);

        let text = '\u20B1' + formatPeso(toInstallment) + ' goes to the installment';
        if (toPenalty > 0) {
            text += ' and \u20B1' + formatPeso(toPenalty) + ' to the penalty';
        }
        text += '. \u20B1' + formatPeso(remaining) + ' will remain outstanding.';
        partialEls.hint.textContent = text;
    }

    function resetFull() {
        clearError(fullEls.error);
        const fromReceipt = pendingTarget === 'full';
        if (fullPicker) {
            fullPicker.close();
            fullPicker.setRange('', todayLocal());
            fullPicker.setValue(initialDate(fromReceipt));
        }
        fullUsesReceipt = applyReceipt(fullEls.receiptWrap, fullEls.receiptThumb, fromReceipt);
        if (fromReceipt) pendingTarget = null;
    }

    function resetPartial() {
        clearError(partialEls.error);
        const fromReceipt = pendingTarget === 'partial';
        if (partialPicker) {
            partialPicker.close();
            partialPicker.setRange('', todayLocal());
            partialPicker.setValue(initialDate(fromReceipt));
        }
        if (partialEls.amount) {
            partialEls.amount.value = '';
        }
        refreshPartialHint(false);
        partialUsesReceipt = applyReceipt(partialEls.receiptWrap, partialEls.receiptThumb, fromReceipt);
        if (fromReceipt) pendingTarget = null;
    }

    function resetReject() {
        clearError(rejectEls.error);
        if (rejectEls.reason) {
            rejectEls.reason.value = '';
        }
        if (pendingTarget === 'reject') pendingTarget = null;
    }

    if (confirmPaidModalEl) {
        confirmPaidModalEl.addEventListener('show.bs.modal', resetFull);
        confirmPaidModalEl.addEventListener('hidden.bs.modal', resetFull);
    }

    if (partialModalEl) {
        partialModalEl.addEventListener('show.bs.modal', resetPartial);
        partialModalEl.addEventListener('hidden.bs.modal', resetPartial);
    }

    if (rejectModalEl) {
        rejectModalEl.addEventListener('show.bs.modal', resetReject);
    }

    if (partialEls.amount) {
        partialEls.amount.addEventListener('keydown', function (e) {
            if (['e', 'E', '+', '-'].indexOf(e.key) !== -1) {
                e.preventDefault();
            }
        });
        partialEls.amount.addEventListener('input', function () {
            refreshPartialHint(clampPartialAmount());
        });
    }

    if (fullEls.receiptRemove) {
        fullEls.receiptRemove.addEventListener('click', function () {
            fullUsesReceipt = applyReceipt(fullEls.receiptWrap, fullEls.receiptThumb, false);
        });
    }

    if (partialEls.receiptRemove) {
        partialEls.receiptRemove.addEventListener('click', function () {
            partialUsesReceipt = applyReceipt(partialEls.receiptWrap, partialEls.receiptThumb, false);
        });
    }

    function openAfterReceipt(target, modalEl) {
        if (!modalEl || !brModalEl) return;
        brModalEl.addEventListener('hidden.bs.modal', function openNext() {
            brModalEl.removeEventListener('hidden.bs.modal', openNext);
            pendingTarget = target;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
        bootstrap.Modal.getOrCreateInstance(brModalEl).hide();
    }

    if (brModalEl && brImg) {
        brModalEl.addEventListener('show.bs.modal', function () {
            brLoading.style.display = '';
            brError.style.display = 'none';
            brImgLink.style.display = 'none';
            brHint.style.display = 'none';

            brImg.onload = function () {
                brLoading.style.display = 'none';
                brImgLink.style.display = '';
                brHint.style.display = '';
            };
            brImg.onerror = function () {
                brLoading.style.display = 'none';
                brError.style.display = '';
            };

            brImgLink.href = brFileUrl;
            brImg.src = brFileUrl;
        });

        if (brRecordBtn) {
            brRecordBtn.addEventListener('click', function () {
                openAfterReceipt('full', confirmPaidModalEl);
            });
        }

        if (brPartialBtn) {
            brPartialBtn.addEventListener('click', function () {
                openAfterReceipt('partial', partialModalEl);
            });
        }

        if (brRejectBtn) {
            brRejectBtn.addEventListener('click', function () {
                openAfterReceipt('reject', rejectModalEl);
            });
        }
    }

    function validateFull() {
        if (!fullEls.dateInput || !fullEls.dateInput.value) {
            return 'Please select the date the payment was made.';
        }
        if (fullEls.dateInput.value > todayLocal()) {
            return 'The payment date cannot be in the future.';
        }
        return '';
    }

    function validatePartial() {
        if (!partialEls.dateInput || !partialEls.dateInput.value) {
            return 'Please select the date the payment was made.';
        }
        if (partialEls.dateInput.value > todayLocal()) {
            return 'The payment date cannot be in the future.';
        }
        const value = parseFloat(partialEls.amount ? partialEls.amount.value : '');
        if (!value || value <= 0) {
            return 'Please enter the amount received.';
        }
        if (value > partialMax()) {
            return 'A partial payment cannot be more than \u20B1' + formatPeso(partialMax()) + '. Use Record Payment for the full amount.';
        }
        return '';
    }

    function submitPayment(opts) {
        if (!markPaidUrl) return;

        clearError(opts.els.error);

        const errorMessage = opts.validate();
        if (errorMessage) {
            showError(opts.els.error, errorMessage);
            return;
        }

        setLoading(opts.els, true);

        const formData = new FormData();
        formData.append('paid_date', opts.els.dateInput.value);
        if (opts.amount) {
            formData.append('amount', opts.amount());
        }
        if (opts.useReceipt()) {
            formData.append('use_borrower_receipt', '1');
        }

        fetch(markPaidUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { status: res.status, data: data };
                });
            })
            .then(function (result) {
                const data = result.data;

                if (data.success) {
                    bootstrap.Modal.getInstance(opts.modalEl).hide();
                    showSuccessThenReload(opts.successTitle, opts.successDesc);
                    return;
                }

                if (result.status === 422 && data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    showError(opts.els.error, data.errors[firstKey][0]);
                    return;
                }

                showError(opts.els.error, data.message || 'Please try again.');
            })
            .catch(function () {
                showError(opts.els.error, 'Something went wrong. Please try again.');
            })
            .finally(function () {
                setLoading(opts.els, false);
            });
    }

    if (fullEls.btn) {
        fullEls.btn.addEventListener('click', function () {
            submitPayment({
                els: fullEls,
                modalEl: confirmPaidModalEl,
                validate: validateFull,
                amount: null,
                useReceipt: function () { return fullUsesReceipt; },
                successTitle: 'Payment Marked as Paid',
                successDesc: 'The overdue payment has been successfully recorded as paid.',
            });
        });
    }

    if (partialEls.btn) {
        partialEls.btn.addEventListener('click', function () {
            submitPayment({
                els: partialEls,
                modalEl: partialModalEl,
                validate: validatePartial,
                amount: function () { return partialEls.amount.value; },
                useReceipt: function () { return partialUsesReceipt; },
                successTitle: 'Partial Payment Recorded',
                successDesc: 'The partial payment was recorded. The remaining balance is still due.',
            });
        });
    }

    if (rejectEls.btn) {
        rejectEls.btn.addEventListener('click', function () {
            if (!rejectUrl) return;

            clearError(rejectEls.error);

            const reason = rejectEls.reason.value.trim();
            if (reason.length < 5) {
                showError(rejectEls.error, 'Please enter a reason (at least 5 characters).');
                return;
            }

            setLoading(rejectEls, true);

            fetch(rejectUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ remarks: reason }),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { status: res.status, data: data };
                    });
                })
                .then(function (result) {
                    const data = result.data;

                    if (data.success) {
                        bootstrap.Modal.getInstance(rejectModalEl).hide();
                        showSuccessThenReload('Receipt Rejected', data.message || 'The borrower was notified and can upload a new receipt.');
                        return;
                    }

                    if (result.status === 422 && data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        showError(rejectEls.error, data.errors[firstKey][0]);
                        return;
                    }

                    showError(rejectEls.error, data.message || 'Failed to reject the receipt.');
                })
                .catch(function () {
                    showError(rejectEls.error, 'Something went wrong. Please try again.');
                })
                .finally(function () {
                    setLoading(rejectEls, false);
                });
        });
    }

    const waiveBtn = document.getElementById('opdConfirmWaiveBtn');
    if (waiveBtn) {
        waiveBtn.addEventListener('click', function () {
            if (!waivePenaltyUrl) return;

            const reason = document.getElementById('opdWaiveReason').value.trim();
            if (!reason) {
                document.getElementById('opdWaiveReason').focus();
                return;
            }

            fetch(waivePenaltyUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ waived_reason: reason }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    bootstrap.Modal.getInstance(waiveModalEl).hide();
                    if (data.success) {
                        showSuccessThenReload('Penalty Waived', 'The penalty has been successfully waived.');
                    } else if (window.showAdminToast) {
                        showAdminToast('error', 'Failed', data.message || 'Please try again.');
                    }
                })
                .catch(function () {
                    bootstrap.Modal.getInstance(waiveModalEl).hide();
                    if (window.showAdminToast) {
                        showAdminToast('error', 'Something went wrong', 'Please try again.');
                    }
                });
        });
    }
})();