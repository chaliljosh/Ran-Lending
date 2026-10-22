// public/functions/overdue-payment-detail.js
// Used by: resources/views/admin/overdue-payment-detail.blade.php
//
// Reads its URLs/CSRF token from the #opd-config element's data
// attributes (set by the blade) instead of having them embedded as
// inline PHP-generated JS values.

(function () {
    const config = document.getElementById('opd-config');
    if (!config) return;

    const csrfToken = config.dataset.csrf;
    const markPaidUrl = config.dataset.markPaidUrl || null;
    const waivePenaltyUrl = config.dataset.waiveUrl || null;

    const confirmPaidModalEl = document.getElementById('opdConfirmPaidModal');
    const waiveModalEl = document.getElementById('opdWaiveModal');

    const confirmPaidBtn = document.getElementById('opdConfirmPaidBtn');
    const confirmPaidText = document.getElementById('opdConfirmPaidText');
    const confirmPaidSpinner = document.getElementById('opdConfirmPaidSpinner');

    // ---- Mga field ng "Mark as Paid" modal (Date Paid lang) ----
    const paidDateInput = document.getElementById('opdPaidDate');            // hidden (Y-m-d)
    const paidDateDisplay = document.getElementById('opdPaidDateDisplay');   // nakikita (MM/DD/YYYY)
    const paidError = document.getElementById('opdPaidError');

    // ---- Receipt na in-upload ng borrower (wala ang mga ito kapag walang receipt) ----
    const brModalEl = document.getElementById('opdBorrowerReceiptModal');
    const brLoading = document.getElementById('opdBrLoading');
    const brError = document.getElementById('opdBrError');
    const brImg = document.getElementById('opdBrImg');
    const brImgLink = document.getElementById('opdBrImgLink');
    const brHint = document.getElementById('opdBrHint');
    const brRecordBtn = document.getElementById('opdBrRecordBtn');
    const brFileUrl = brModalEl ? (brModalEl.dataset.fileUrl || '') : '';

    const usingBorrowerWrap = document.getElementById('opdUsingBorrowerReceipt');
    const usingBorrowerThumb = document.getElementById('opdUsingBorrowerThumb');
    const removeBorrowerBtn = document.getElementById('opdUseNoReceiptBtn');

    // true kapag galing sa "View Receipt" > "Record Payment"
    // (gagamitin ang receipt ng borrower bilang receipt ng payment)
    let pendingBorrowerReceipt = false;
    let useBorrowerReceipt = false;

    function showSuccessThenReload(title, desc) {
        if (window.showAdminToast) {
            showAdminToast('success', title, desc);
        }
        setTimeout(function () {
            window.location.reload();
        }, 3500);
    }

    function showPaidError(message) {
        if (!paidError) return;
        paidError.textContent = message;
        paidError.classList.remove('d-none');
    }

    function clearPaidError() {
        if (!paidError) return;
        paidError.textContent = '';
        paidError.classList.add('d-none');
    }

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

    // Wala ang mga elementong ito kapag walang overdue installment
    const datePicker = (paidDateDisplay && paidDateInput)
        ? createDatePicker(paidDateDisplay, paidDateInput, confirmPaidModalEl)
        : null;

    // Ipakita o itago ang receipt ng borrower sa loob ng Mark as Paid modal
    function setReceiptMode(on) {
        useBorrowerReceipt = !!on && !!usingBorrowerWrap;
        if (usingBorrowerWrap) {
            usingBorrowerWrap.style.display = useBorrowerReceipt ? '' : 'none';
            if (useBorrowerReceipt && usingBorrowerThumb) {
                usingBorrowerThumb.src = brFileUrl;
            }
        }
    }

    function resetForm() {
        clearPaidError();
        if (datePicker) {
            const today = todayLocal();
            datePicker.close();
            datePicker.setRange('', today);   // hindi puwede sa hinaharap
            datePicker.setValue(today);
        }
        // Ginagamit lang ang receipt ng borrower kapag galing sa "Record Payment"
        // ng View Receipt modal. Sa direktang "Mark as Paid", walang receipt.
        setReceiptMode(pendingBorrowerReceipt);
        pendingBorrowerReceipt = false;
    }

    // Tuwing bubuksan o isasara ang modal -> fresh na form (today ang default na date)
    confirmPaidModalEl.addEventListener('show.bs.modal', resetForm);
    confirmPaidModalEl.addEventListener('hidden.bs.modal', resetForm);

    // X button: ire-record ang payment nang walang receipt
    if (removeBorrowerBtn) {
        removeBorrowerBtn.addEventListener('click', function () {
            setReceiptMode(false);
        });
    }

    // ============================================================
    // View Receipt modal (receipt na in-upload ng borrower)
    // ============================================================
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

        // Record Payment -> isara ang receipt modal, tapos buksan ang Mark as Paid
        // na kasama ang receipt ng borrower
        if (brRecordBtn) {
            brRecordBtn.addEventListener('click', function () {
                brModalEl.addEventListener('hidden.bs.modal', function openPaid() {
                    brModalEl.removeEventListener('hidden.bs.modal', openPaid);
                    pendingBorrowerReceipt = true;
                    bootstrap.Modal.getOrCreateInstance(confirmPaidModalEl).show();
                });
                bootstrap.Modal.getInstance(brModalEl).hide();
            });
        }
    }

    // Tingnan kung okay na ang date bago mag-send.
    // Return: error message, o '' kung okay na.
    function validatePaidForm() {
        if (!paidDateInput || !paidDateInput.value) {
            return 'Please select the date the payment was made.';
        }
        if (paidDateInput.value > todayLocal()) {
            return 'The payment date cannot be in the future.';
        }
        return '';
    }

    confirmPaidBtn.addEventListener('click', function () {
        if (!markPaidUrl) return;

        clearPaidError();

        const errorMessage = validatePaidForm();
        if (errorMessage) {
            showPaidError(errorMessage);
            return;
        }

        // Loading state — spinner lang, naka-disable ang button
        confirmPaidBtn.disabled = true;
        confirmPaidText.classList.add('d-none');
        confirmPaidSpinner.classList.remove('d-none');

        // Walang receipt upload ang admin. Date lang ang ipinapadala, at kung galing
        // sa View Receipt, ipinapaalam sa server na gamitin ang receipt ng borrower.
        const formData = new FormData();
        formData.append('paid_date', paidDateInput.value);
        if (useBorrowerReceipt) {
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
                    bootstrap.Modal.getInstance(confirmPaidModalEl).hide();
                    showSuccessThenReload('Payment Marked as Paid', 'The overdue payment has been successfully recorded as paid.');
                    return;
                }

                // 422 galing sa validation — ipakita sa loob ng modal
                if (result.status === 422 && data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    showPaidError(data.errors[firstKey][0]);
                    return;
                }

                showPaidError(data.message || 'Please try again.');
            })
            .catch(function () {
                showPaidError('Something went wrong. Please try again.');
            })
            .finally(function () {
                confirmPaidBtn.disabled = false;
                confirmPaidText.classList.remove('d-none');
                confirmPaidSpinner.classList.add('d-none');
            });
    });

    document.getElementById('opdConfirmWaiveBtn').addEventListener('click', function () {
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
})();