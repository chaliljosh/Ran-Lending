document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('olsScheduleTable');
    if (!table) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const confirmModalEl = document.getElementById('olsMarkPaidConfirmModal');
    const confirmBtn = document.getElementById('olsConfirmMarkPaidBtn');
    const confirmInstallmentNo = document.getElementById('olsConfirmInstallmentNo');
    const confirmAmount = document.getElementById('olsConfirmAmount');

    const paidDateInput = document.getElementById('olsPaidDate');
    const errorEl = document.getElementById('olsMarkPaidError');

    const MAX_RECEIPT_BYTES = 5 * 1024 * 1024;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png'];

    let pendingScheduleId = null;
    let pendingBtn = null;

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

    useOwnReceiptBtn.addEventListener('click', function () {
        setBorrowerReceiptMode(null);
        showError('');
    });

    function todayLocal() {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 10);
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

    const datePicker = createDatePicker(
        document.getElementById('olsPaidDateDisplay'),
        paidDateInput,
        confirmModalEl
    );

    function showError(msg) {
        errorEl.textContent = msg || '';
        errorEl.style.display = msg ? '' : 'none';
    }

    function formatSize(bytes) {
        return bytes >= 1048576
            ? (bytes / 1048576).toFixed(1) + ' MB'
            : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function resetForm() {
        showError('');
        setBorrowerReceiptMode(null);
        const today = todayLocal();
        datePicker.close();
        datePicker.setRange('', today);
        datePicker.setValue(today);
    }

    function setBtnLoading(btn, loading) {
        btn.disabled = loading;
        btn.classList.toggle('is-loading', loading);
        const spinner = btn.querySelector('.spin-icon');
        if (loading && !spinner) {
            const s = document.createElement('i');
            s.className = 'feather-loader spin-icon';
            btn.insertBefore(s, btn.firstChild);
        } else if (!loading && spinner) {
            spinner.remove();
        }
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body || {}),
        }).then(res => res.json().then(data => ({ ok: res.ok, data: data })));
    }

    function firstMessage(data, fallback) {
        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        return firstError || data.message || fallback;
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function flashAndReload(title, message) {
        sessionStorage.setItem('olsFlashToast', JSON.stringify({ title: title, message: message }));
        window.location.reload();
    }

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

        detailsModalEl.addEventListener('shown.bs.modal', updateFullLink);
        detailsModalEl.addEventListener('hidden.bs.modal', function () {
            dImg.onload = null;
            dImg.onerror = null;
            dImg.removeAttribute('src');
        });
    }

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

        brRecordBtn.addEventListener('click', function () {
            const rowBtn = table.querySelector(`.btn-record-payment[data-schedule-id="${brScheduleId}"]`);
            if (!rowBtn) return;

            const fileUrl = brFileUrl;
            brModalEl.addEventListener('hidden.bs.modal', function () {
                rowBtn.click();
                setBorrowerReceiptMode(fileUrl);
            }, { once: true });

            bootstrap.Modal.getOrCreateInstance(brModalEl).hide();
        });

        brModalEl.addEventListener('hidden.bs.modal', resetBorrowerReceipt);
    }

    const flashRaw = sessionStorage.getItem('olsFlashToast');
    if (flashRaw) {
        sessionStorage.removeItem('olsFlashToast');
        let flash = null;
        try { flash = JSON.parse(flashRaw); } catch (e) {}
        if (flash && typeof window.showAdminToast === 'function') {
            window.showAdminToast('success', flash.title, flash.message);
        }
    }

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
            setBtnLoading(voidBtn, true);

            postJson('/admin/installments/' + voidScheduleId + '/void-request', { reason: reason })
                .then(function (res) {
                    if (!res.ok || !res.data.success) {
                        showVoidError(firstMessage(res.data, 'Failed to submit the void request.'));
                        setBtnLoading(voidBtn, false);
                        return;
                    }
                    flashAndReload('Void Requested', res.data.message);
                })
                .catch(function (err) {
                    console.error('[ongoing-loan-detail.js] void request error:', err);
                    showVoidError('Something went wrong. Please try again.');
                    setBtnLoading(voidBtn, false);
                });
        });
    }

    const voteModalEl = document.getElementById('olsVoteVoidModal');
    if (voteModalEl) {
        const $ = function (id) { return document.getElementById(id); };
        const approveBtn = $('olsVvApprove');
        const rejectBtn = $('olsVvReject');
        const cancelBtn = $('olsVvCancel');
        const remarksField = $('olsVvRemarksField');
        const remarksInput = $('olsVvRemarks');
        const noteEl = $('olsVvNote');
        const errEl = $('olsVvError');
        let voteRequestId = null;

        function showVoteError(msg) {
            errEl.textContent = msg || '';
            errEl.style.display = msg ? '' : 'none';
        }

        function renderVotes(votes) {
            $('olsVvVotes').innerHTML = votes.length
                ? votes.map(function (v) {
                    const who = escapeHtml(v.name) + (v.me ? ' (You)' : '');
                    const remarks = v.remarks ? '<div class="ols-vv-vote-remarks">' + escapeHtml(v.remarks) + '</div>' : '';
                    return '<div class="ols-vv-vote">' +
                        '<div class="ols-vv-vote-main"><span class="ols-vv-vote-name">' + who + '</span>' + remarks +
                        '<span class="ols-vv-vote-time">' + escapeHtml(v.time) + '</span></div>' +
                        '<span class="ols-vv-vote-state is-' + escapeHtml(v.decision) + '">' + escapeHtml(v.decision) + '</span></div>';
                }).join('')
                : '<div class="ols-vv-empty">No votes yet.</div>';
        }

        table.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-view-void-request');
            if (!btn) return;
            const d = btn.dataset;

            let votes = [];
            try { votes = JSON.parse(d.votes || '[]'); } catch (err) {}

            const isRequester = d.isRequester === '1';
            const hasVoted = votes.some(function (v) { return v.me; });

            voteRequestId = d.requestId;
            $('olsVvInstallment').textContent = '#' + d.installmentNo;
            $('olsVvAmount').textContent = d.amount;
            $('olsVvRequester').textContent = d.requester;
            $('olsVvDate').textContent = d.requestedAt;
            $('olsVvReason').textContent = d.reason;
            $('olsVvCount').textContent = d.approvals;
            renderVotes(votes);

            remarksInput.value = '';
            showVoteError('');
            remarksField.style.display = hasVoted ? 'none' : '';
            approveBtn.style.display = hasVoted ? 'none' : '';
            rejectBtn.style.display = hasVoted ? 'none' : '';
            cancelBtn.style.display = isRequester ? '' : 'none';

            const note = isRequester
                ? 'You requested this void. You can cancel it while it is still pending.'
                : (hasVoted ? 'You already voted on this request.' : '');
            noteEl.textContent = note;
            noteEl.style.display = note ? '' : 'none';

            bootstrap.Modal.getOrCreateInstance(voteModalEl).show();
        });

        function submitVote(decision, btn, successTitle) {
            if (!voteRequestId) return;
            const remarks = remarksInput.value.trim();
            if (decision === 'rejected' && remarks.length < 3) {
                showVoteError('Please enter a remark when rejecting.');
                return;
            }
            showVoteError('');
            setBtnLoading(btn, true);

            postJson('/admin/void-requests/' + voteRequestId + '/vote', { decision: decision, remarks: remarks })
                .then(function (res) {
                    if (!res.ok || !res.data.success) {
                        showVoteError(firstMessage(res.data, 'Failed to record your vote.'));
                        setBtnLoading(btn, false);
                        return;
                    }
                    flashAndReload(successTitle, res.data.message);
                })
                .catch(function (err) {
                    console.error('[ongoing-loan-detail.js] vote error:', err);
                    showVoteError('Something went wrong. Please try again.');
                    setBtnLoading(btn, false);
                });
        }

        approveBtn.addEventListener('click', function () { submitVote('approved', approveBtn, 'Vote Recorded'); });
        rejectBtn.addEventListener('click', function () { submitVote('rejected', rejectBtn, 'Void Rejected'); });

        cancelBtn.addEventListener('click', function () {
            if (!voteRequestId) return;
            showVoteError('');
            setBtnLoading(cancelBtn, true);

            postJson('/admin/void-requests/' + voteRequestId + '/cancel', {})
                .then(function (res) {
                    if (!res.ok || !res.data.success) {
                        showVoteError(firstMessage(res.data, 'Failed to cancel the request.'));
                        setBtnLoading(cancelBtn, false);
                        return;
                    }
                    flashAndReload('Request Cancelled', res.data.message);
                })
                .catch(function (err) {
                    console.error('[ongoing-loan-detail.js] cancel void error:', err);
                    showVoteError('Something went wrong. Please try again.');
                    setBtnLoading(cancelBtn, false);
                });
        });
    }

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

            setBtnLoading(proofSendBtn, true);

            fetch(`/admin/loans/${proofApplicationId}/send-proof`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    showProofError(firstMessage(data, 'Failed to send the proof.'));
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
                setBtnLoading(proofSendBtn, false);
            });
        });
    }

    confirmBtn.addEventListener('click', function () {
        if (!pendingScheduleId || !pendingBtn) return;

        showError('');
        const paidDate = paidDateInput.value;
        if (!paidDate) { showError('Please select the date the payment was made.'); return; }
        if (paidDate > todayLocal()) { showError('The payment date cannot be in the future.'); return; }

        const scheduleId = pendingScheduleId;
        const row = table.querySelector(`tr[data-schedule-row="${scheduleId}"]`);

        const formData = new FormData();
        formData.append('paid_date', paidDate);
        if (useBorrowerReceipt) {
            formData.append('use_borrower_receipt', '1');
        }

        setBtnLoading(confirmBtn, true);

        fetch(`/admin/installments/${scheduleId}/mark-paid`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                showError(firstMessage(data, 'Failed to mark as paid.'));
                return;
            }

            if (row) {
                row.querySelector('[data-field="amount_paid"]').textContent = '₱' + data.amount_paid_display;
                row.querySelector('[data-field="progress_fill"]').style.width = '100%';
                row.querySelector('[data-field="progress_pct"]').textContent = '100%';
                row.querySelector('[data-field="paid_on"]').textContent = data.paid_on_display;
                row.querySelector('[data-field="due_sub"]').textContent = 'Paid';
                row.querySelector('[data-field="status"]').innerHTML =
                    '<span class="ols-status-pill is-paid">Paid</span>';

                const actionsCell = row.querySelector('[data-field="action"] .ols-actions-cell');
                const kebab = actionsCell.querySelector('.ols-kebab-wrap');
                actionsCell.innerHTML = '<span class="sched-paid-note">Recorded</span>';
                actionsCell.appendChild(kebab);
                kebab.classList.remove('d-none');

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
            setBtnLoading(confirmBtn, false);
            pendingScheduleId = null;
            pendingBtn = null;
        });
    });

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