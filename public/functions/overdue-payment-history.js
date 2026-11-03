/**
 * Admin > Overdue Payment Detail > Payment Details (+ receipt viewer)
 * Gayang-gaya ng borrower flow (functions/payment-details.js).
 * I-save bilang: public/functions/overdue-payment-history.js
 */
document.addEventListener('DOMContentLoaded', function () {
    const detailsEl = document.getElementById('opdPayDetailsModal');
    const viewerEl = document.getElementById('opdPayViewerModal');
    if (!detailsEl || !viewerEl) return;

    const $ = id => document.getElementById(id);
    const list = $('opdPdList');

    const vImg = $('opdPdViewerImg');
    const vLoading = $('opdPdViewerLoading');
    const vError = $('opdPdViewerError');
    const vSub = $('opdPdViewerSub');
    const vDownload = $('opdPdViewerDownload');
    const vPenaltyRow = $('opdPdVPenaltyWrap');

    let ctx = {};                 // info ng installment na binubuksan
    let returnToDetails = false;  // galing sa listahan ang viewer?

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function withDownload(url) {
        return url + (url.indexOf('?') === -1 ? '?' : '&') + 'download=1';
    }

    function peso(v) {
        return v ? '\u20B1' + v : '\u2014';
    }

    function hasPenalty(p) {
        return p.penalty && parseFloat(String(p.penalty).replace(/,/g, '')) > 0;
    }

    function setStatus(el, isPaid) {
        el.textContent = isPaid ? 'Paid' : 'Partially paid';
        el.className = isPaid ? 'is-paid' : 'is-pending';
    }

    function resetViewer() {
        vImg.onload = null;
        vImg.onerror = null;
        vImg.removeAttribute('src');
        vImg.hidden = true;
        vError.hidden = true;
        vLoading.hidden = false;
    }

    function fillViewerInfo(p) {
        vSub.textContent = 'Installment #' + ctx.month;
        $('opdPdVDue').textContent = ctx.due || '\u2014';
        $('opdPdVPaidOn').textContent = p.date || '\u2014';
        $('opdPdVInstallment').textContent = peso(ctx.amount);
        $('opdPdVPaid').textContent = peso(p.amount);
        $('opdPdVPenalty').textContent = peso(p.penalty || '0.00');
        $('opdPdVKind').textContent = p.kind === 'partial' ? 'Partial' : 'Full';
        setStatus($('opdPdVStatus'), ctx.status === 'paid');
    }

    function openViewer(p, backToDetails) {
        resetViewer();
        returnToDetails = !!backToDetails;
        fillViewerInfo(p);

        if (vDownload) vDownload.href = withDownload(p.url);

        vImg.onload = function () { vLoading.hidden = true; vImg.hidden = false; };
        vImg.onerror = function () { vLoading.hidden = true; vError.hidden = false; };
        vImg.src = p.url;

        bootstrap.Modal.getOrCreateInstance(viewerEl).show();
    }

    // Click sa "Details" button sa Payment Schedule
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-opd-payments]');
        if (!btn) return;
        const d = btn.dataset;

        ctx = { month: d.month, due: d.due, paid: d.paid, amount: d.amount, status: d.status };

        let payments = [];
        try { payments = JSON.parse(d.payments || '[]'); } catch (err) {}

        const isPaid = d.status === 'paid';

        // Isang full payment lang na may resibo: diretso sa resibo.
        if (isPaid && payments.length === 1 && payments[0].url) {
            openViewer(payments[0], false);
            return;
        }

        $('opdPdMonth').textContent = 'Installment #' + d.month;
        $('opdPdDue').textContent = d.due || '\u2014';
        $('opdPdPaidOn').textContent = d.paid || '\u2014';
        $('opdPdAmount').textContent = peso(d.amount);
        setStatus($('opdPdStatus'), isPaid);

        if (payments.length) {
            list.innerHTML = payments.map(function (p, i) {
                const kind = p.kind === 'partial' ? 'Partial' : 'Full';
                const penalty = hasPenalty(p)
                    ? '<span class="opd-pd-penalty">incl. &#8369;' + escapeHtml(p.penalty) + ' penalty</span>'
                    : '';
                const link = p.url
                    ? '<button type="button" class="opd-pd-link js-opd-view" data-index="' + i + '">View receipt</button>'
                    : '<span class="opd-pd-none">No receipt</span>';
                return '<div class="opd-pd-row">' +
                    '<div class="opd-pd-row-main">' +
                    '<span class="opd-pd-amount">&#8369;' + escapeHtml(p.amount) +
                    '<span class="opd-pd-kind is-' + escapeHtml(p.kind) + '">' + kind + '</span></span>' +
                    '<span class="opd-pd-date">' + escapeHtml(p.date) + '</span>' +
                    penalty +
                    '</div>' + link + '</div>';
            }).join('');
            list.hidden = false;
            $('opdPdEmpty').hidden = true;
        } else {
            list.innerHTML = '';
            list.hidden = true;
            $('opdPdEmpty').hidden = false;
        }

        list._payments = payments;
        bootstrap.Modal.getOrCreateInstance(detailsEl).show();
    });

    // View receipt (galing sa listahan): isara ang details, buksan ang viewer.
    list.addEventListener('click', function (e) {
        const link = e.target.closest('.js-opd-view');
        if (!link) return;

        const p = (list._payments || [])[parseInt(link.dataset.index, 10)];
        if (!p || !p.url) return;

        detailsEl.addEventListener('hidden.bs.modal', function () {
            openViewer(p, true);
        }, { once: true });

        bootstrap.Modal.getOrCreateInstance(detailsEl).hide();
    });

    // Pagsara ng viewer: balik sa Payment Details kung galing doon.
    viewerEl.addEventListener('hidden.bs.modal', function () {
        resetViewer();
        if (returnToDetails) {
            returnToDetails = false;
            bootstrap.Modal.getOrCreateInstance(detailsEl).show();
        }
    });
});