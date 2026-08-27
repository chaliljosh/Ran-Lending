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
    const successModalEl = document.getElementById('opdSuccessModal');
    const successTitleEl = document.getElementById('opdSuccessTitle');
    const successDescEl = document.getElementById('opdSuccessDesc');

    function showSuccess(title, desc) {
        successTitleEl.textContent = title;
        successDescEl.textContent = desc;
        bootstrap.Modal.getOrCreateInstance(successModalEl).show();
    }

    document.getElementById('opdConfirmPaidBtn').addEventListener('click', function () {
        if (!markPaidUrl) return;

        fetch(markPaidUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                bootstrap.Modal.getInstance(confirmPaidModalEl).hide();
                if (data.success) {
                    showSuccess('Payment Marked as Paid', 'The overdue payment has been successfully recorded as paid.');
                }
            })
            .catch(function () {
                bootstrap.Modal.getInstance(confirmPaidModalEl).hide();
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
                    showSuccess('Penalty Waived', 'The penalty has been successfully waived.');
                }
            })
            .catch(function () {
                bootstrap.Modal.getInstance(waiveModalEl).hide();
            });
    });

    // Reload once the success modal is dismissed, so the page
    // reflects the updated payment/penalty status.
    successModalEl.addEventListener('hidden.bs.modal', function () {
        window.location.reload();
    });
    document.getElementById('opdSuccessOkBtn').addEventListener('click', function () {
        bootstrap.Modal.getInstance(successModalEl).hide();
    });
})();