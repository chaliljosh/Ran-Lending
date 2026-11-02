// public/functions/row-loader.js
// Shared loader para sa mga table na may clickable row at "View / Review" button.
//
// Used by: ongoing-loans, waiting-for-release, loan-applications, overdue-payments
//
// Paano gamitin sa blade (ilagay bago ang page script):
//   <script src="{{ asset('functions/row-loader.js') }}"></script>
//
// Ginagawa nito:
//   - Kapag na-click ang row o ang button sa loob ng <tr data-href>, magiging
//     spinner + "Opening..." ang button, dim ang row, at "progress" ang cursor.
//   - Hindi na pwedeng mag-double click habang nagbubukas.
//   - Kapag pinindot ang Back at galing sa browser cache ang page, nare-reset.

(function () {
    var busy = false;
    var rowEl = null;
    var btnEl = null;
    var btnHtml = '';

    // Ang button sa loob ng row (View Details / Review / Review & Release)
    function findButton(row) {
        return row.querySelector('a.btn-view-v2, a.btn-light, a.pt-btn-view, a.btn-view-details');
    }

    function show(row, clickedLink) {
        if (!row) return;

        // Kapag may nagbubukas na at ibang row ang na-click:
        // - kapareho ng row: huwag nang gawin ulit
        // - ibang row: alisin ang loader sa luma at ilipat sa bago
        //   (ang huling na-click ang susundin ng browser)
        if (busy) {
            if (row === rowEl) return;
            reset();
        }

        busy = true;
        rowEl = row;

        // Dim ang row at pigilan ang double click
        row.style.opacity = '0.6';
        row.style.pointerEvents = 'none';
        document.body.style.cursor = 'progress';

        // Gawing spinner ang button
        var btn = clickedLink || findButton(row);
        if (btn) {
            btnEl = btn;
            btnHtml = btn.innerHTML;

            // Bilog na may border (hindi icon font), kaya gagana sa kahit anong page
            var spinner = document.createElement('span');
            spinner.style.display = 'inline-block';
            spinner.style.width = '12px';
            spinner.style.height = '12px';
            spinner.style.border = '2px solid currentColor';
            spinner.style.borderRightColor = 'transparent';
            spinner.style.borderRadius = '50%';
            spinner.style.verticalAlign = '-2px';
            spinner.animate(
                [{ transform: 'rotate(0deg)' }, { transform: 'rotate(360deg)' }],
                { duration: 700, iterations: Infinity }
            );

            btn.innerHTML = '';
            btn.appendChild(spinner);
            btn.appendChild(document.createTextNode(' Opening...'));
        }
    }

    function reset() {
        busy = false;
        document.body.style.cursor = '';

        if (rowEl) {
            rowEl.style.opacity = '';
            rowEl.style.pointerEvents = '';
            rowEl = null;
        }
        if (btnEl) {
            btnEl.innerHTML = btnHtml;
            btnEl = null;
        }
    }

    function isBusy() {
        return busy;
    }

    window.rowLoader = { show: show, reset: reset, isBusy: isBusy };

    // Back button galing sa browser cache
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) reset();
    });

    // Click mismo sa link/button sa loob ng row.
    // Hindi pinipigilan ang pag-navigate, loader lang ang idinadagdag.
    document.addEventListener('click', function (event) {
        var link = event.target.closest('tr[data-href] a');
        if (!link) return;

        // Walang loader kung bagong tab ang bubuksan
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0 || link.target === '_blank') return;

        show(link.closest('tr'), link);
    });
})();