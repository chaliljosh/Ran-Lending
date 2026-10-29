document.addEventListener('DOMContentLoaded', function () {
    const detailsEl = document.getElementById('ldPayDetailsModal');
    const viewerEl = document.getElementById('ldPayViewerModal');
    if (!detailsEl || !viewerEl) return;

    const $ = id => document.getElementById(id);
    const list = $('ldPdList');

    const vImg = $('ldPdViewerImg');
    const vLoading = $('ldPdViewerLoading');
    const vError = $('ldPdViewerError');
    const vSub = $('ldPdViewerSub');
    const vDownload = $('ldPdViewerDownload');

    // Info ng installment na kasalukuyang binubuksan (month, due, amount, status)
    let ctx = {};

    // true kapag galing sa listahan ang viewer (makabalik sa Payment Details);
    // false kapag diretso sa resibo (isang bayad lang).
    let returnToDetails = false;

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function withDownload(url) {
        return url + (url.indexOf('?') === -1 ? '?' : '&') + 'download=1';
    }

    function resetViewer() {
        vImg.onload = null;
        vImg.onerror = null;
        vImg.removeAttribute('src');
        vImg.hidden = true;
        vError.hidden = true;
        vLoading.hidden = false;
    }

    // Punan ang info sa itaas ng resibo
    function fillViewerInfo(p) {
        const isPaid = ctx.status === 'paid';
        const kind = p.kind === 'partial' ? 'Partial' : 'Full';

        vSub.textContent = 'Month ' + ctx.month;
        $('ldPdVDue').textContent = ctx.due || '—';
        $('ldPdVPaidOn').textContent = p.date || '—';
        $('ldPdVInstallment').textContent = ctx.amount ? '₱' + ctx.amount : '—';
        $('ldPdVPaid').textContent = p.amount ? '₱' + p.amount : '—';
        $('ldPdVKind').textContent = kind;

        const status = $('ldPdVStatus');
        status.textContent = isPaid ? 'Paid' : 'Partially paid';
        status.className = isPaid ? 'is-paid' : 'is-pending';
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

    // Click sa "Payment Details"
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-ld-payments]');
        if (!btn) return;
        const d = btn.dataset;

        ctx = {
            month: d.month,
            due: d.due,
            paid: d.paid,
            amount: d.amount,
            status: d.status
        };

        let payments = [];
        try { payments = JSON.parse(d.payments || '[]'); } catch (err) {}

        const isPaid = d.status === 'paid';

        // Isang bayad lang (full) at may resibo: diretso na sa resibo.
        if (isPaid && payments.length === 1 && payments[0].url) {
            openViewer(payments[0], false);
            return;
        }

        // Kung hindi: ipakita ang Payment Details (listahan ng mga bayad)
        $('ldPdMonth').textContent = 'Month ' + d.month;
        $('ldPdDue').textContent = d.due || '—';
        $('ldPdPaidOn').textContent = d.paid || '—';
        $('ldPdAmount').textContent = d.amount ? '₱' + d.amount : '—';

        const status = $('ldPdStatus');
        status.textContent = isPaid ? 'Paid' : 'Partially paid';
        status.className = isPaid ? 'is-paid' : 'is-pending';

        if (payments.length) {
            list.innerHTML = payments.map(function (p, i) {
                const kind = p.kind === 'partial' ? 'Partial' : 'Full';
                const link = p.url
                    ? '<button type="button" class="bwl-pd-link js-ldp-view" data-index="' + i + '">View receipt</button>'
                    : '<span class="bwl-pd-none">No receipt</span>';
                return '<div class="bwl-pd-row">' +
                    '<div class="bwl-pd-row-main">' +
                    '<span class="bwl-pd-amount">&#8369;' + escapeHtml(p.amount) +
                    '<span class="bwl-pd-kind is-' + escapeHtml(p.kind) + '">' + kind + '</span></span>' +
                    '<span class="bwl-pd-date">' + escapeHtml(p.date) + '</span>' +
                    '</div>' + link + '</div>';
            }).join('');
            list.hidden = false;
            $('ldPdEmpty').hidden = true;
        } else {
            list.innerHTML = '';
            list.hidden = true;
            $('ldPdEmpty').hidden = false;
        }

        // Itabi ang listahan para sa View receipt
        list._payments = payments;

        bootstrap.Modal.getOrCreateInstance(detailsEl).show();
    });

    // View receipt (galing sa listahan): isara ang details, buksan ang viewer.
    // Pagsara ng viewer, balik sa details.
    list.addEventListener('click', function (e) {
        const link = e.target.closest('.js-ldp-view');
        if (!link) return;

        const p = (list._payments || [])[parseInt(link.dataset.index, 10)];
        if (!p || !p.url) return;

        detailsEl.addEventListener('hidden.bs.modal', function () {
            openViewer(p, true);
        }, { once: true });

        bootstrap.Modal.getOrCreateInstance(detailsEl).hide();
    });

    viewerEl.addEventListener('hidden.bs.modal', function () {
        resetViewer();
        if (returnToDetails) {
            returnToDetails = false;
            bootstrap.Modal.getOrCreateInstance(detailsEl).show();
        }
    });
});