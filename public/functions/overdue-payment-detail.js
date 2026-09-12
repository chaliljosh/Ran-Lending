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

    // FIX: success now shows as a toast (auto-dismisses) instead of a
    // modal that requires clicking OK. Page still needs to reload
    // afterward so the payment/penalty status reflects the change —
    // delayed so the person actually sees the toast first.
    function showSuccessThenReload(title, desc) {
        if (window.showAdminToast) {
            showAdminToast('success', title, desc);
        }
        setTimeout(function () {
            window.location.reload();
        }, 3500);
    }

    confirmPaidBtn.addEventListener('click', function () {
        if (!markPaidUrl) return;

        // FIX: loading state — spinner icon only, button disabled,
        // while waiting for the server response.
        confirmPaidBtn.disabled = true;
        confirmPaidText.classList.add('d-none');
        confirmPaidSpinner.classList.remove('d-none');

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
                    showSuccessThenReload('Payment Marked as Paid', 'The overdue payment has been successfully recorded as paid.');
                } else if (window.showAdminToast) {
                    showAdminToast('error', 'Failed', data.message || 'Please try again.');
                }
            })
            .catch(function () {
                bootstrap.Modal.getInstance(confirmPaidModalEl).hide();
                if (window.showAdminToast) {
                    showAdminToast('error', 'Something went wrong', 'Please try again.');
                }
            })
            .finally(function () {
                // Reset loading state. Needed even on success since the
                // modal instance/button persists in the DOM until the
                // page reload fires a moment later.
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