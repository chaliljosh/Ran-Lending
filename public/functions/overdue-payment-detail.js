(function () {
    const config = document.getElementById('opd-config');
    if (!config) return;

    const csrfToken = config.dataset.csrf;
    const markPaidUrl = config.dataset.markPaidUrl || null;
    const waivePenaltyUrl = config.dataset.waiveUrl || null;

    const confirmPaidModalEl = document.getElementById('opdConfirmPaidModal');
    const partialModalEl = document.getElementById('opdPartialModal');
    const waiveModalEl = document.getElementById('opdWaiveModal');

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

    const brModalEl = document.getElementById('opdBorrowerReceiptModal');
    const brLoading = document.getElementById('opdBrLoading');
    const brError = document.getElementById('opdBrError');
    const brImg = document.getElementById('opdBrImg');
    const brImgLink = document.getElementById('opdBrImgLink');
    const brHint = document.getElementById('opdBrHint');
    const brRecordBtn = document.getElementById('opdBrRecordBtn');
    const brPartialBtn = document.getElementById('opdBrPartialBtn');
    const brFileUrl = brModalEl ? (brModalEl.dataset.fileUrl || '') : '';

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

    function partialBalance() {
        return partialEls.amount ? parseFloat(partialEls.amount.dataset.balance || '0') : 0;
    }

    function refreshPartialHint() {
        if (!partialEls.amount || !partialEls.hint) return;
        const balance = partialBalance();
        const value = parseFloat(partialEls.amount.value);

        if (!value || value <= 0) {
            partialEls.hint.textContent = 'Enter the amount the borrower paid.';
            return;
        }
        if (value >= balance) {
            partialEls.hint.textContent = 'This covers the full balance. Use Mark as Paid instead.';
            return;
        }
        partialEls.hint.textContent = '\u20B1' + formatPeso(balance - value) + ' will remain due after this payment.';
    }

    function resetFull() {
        clearError(fullEls.error);
        if (fullPicker) {
            const today = todayLocal();
            fullPicker.close();
            fullPicker.setRange('', today);
            fullPicker.setValue(today);
        }
        fullUsesReceipt = applyReceipt(fullEls.receiptWrap, fullEls.receiptThumb, pendingTarget === 'full');
        if (pendingTarget === 'full') pendingTarget = null;
    }

    function resetPartial() {
        clearError(partialEls.error);
        if (partialPicker) {
            const today = todayLocal();
            partialPicker.close();
            partialPicker.setRange('', today);
            partialPicker.setValue(today);
        }
        if (partialEls.amount) {
            partialEls.amount.value = '';
        }
        refreshPartialHint();
        partialUsesReceipt = applyReceipt(partialEls.receiptWrap, partialEls.receiptThumb, pendingTarget === 'partial');
        if (pendingTarget === 'partial') pendingTarget = null;
    }

    if (confirmPaidModalEl) {
        confirmPaidModalEl.addEventListener('show.bs.modal', resetFull);
        confirmPaidModalEl.addEventListener('hidden.bs.modal', resetFull);
    }

    if (partialModalEl) {
        partialModalEl.addEventListener('show.bs.modal', resetPartial);
        partialModalEl.addEventListener('hidden.bs.modal', resetPartial);
    }

    if (partialEls.amount) {
        partialEls.amount.addEventListener('input', refreshPartialHint);
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
        if (!modalEl) return;
        brModalEl.addEventListener('hidden.bs.modal', function openNext() {
            brModalEl.removeEventListener('hidden.bs.modal', openNext);
            pendingTarget = target;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
        bootstrap.Modal.getInstance(brModalEl).hide();
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
        if (value >= partialBalance()) {
            return 'A partial payment must be less than the installment balance. Use Mark as Paid for a full payment.';
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

        opts.els.btn.disabled = true;
        opts.els.text.classList.add('d-none');
        opts.els.spinner.classList.remove('d-none');

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
                opts.els.btn.disabled = false;
                opts.els.text.classList.remove('d-none');
                opts.els.spinner.classList.add('d-none');
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