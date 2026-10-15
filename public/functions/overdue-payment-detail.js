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

    // Mga field ng "Mark as Paid" modal
    const paidDateInput = document.getElementById('opdPaidDate');
    const receiptInput = document.getElementById('opdReceipt');
    const paidError = document.getElementById('opdPaidError');

    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png'];

    function showSuccessThenReload(title, desc) {
        if (window.showAdminToast) {
            showAdminToast('success', title, desc);
        }
        setTimeout(function () {
            window.location.reload();
        }, 3500);
    }

    function showPaidError(message) {
        paidError.textContent = message;
        paidError.classList.remove('d-none');
    }

    function clearPaidError() {
        paidError.textContent = '';
        paidError.classList.add('d-none');
    }

    // Tingnan kung okay na ang date at receipt bago mag-send.
    // Return: error message, o '' kung okay na.
    function validatePaidForm() {
        if (!paidDateInput.value) {
            return 'Please select the date the payment was made.';
        }

        const file = receiptInput.files[0];
        if (!file) {
            return 'Please attach the receipt.';
        }
        if (ALLOWED_TYPES.indexOf(file.type) === -1) {
            return 'The receipt must be a JPG or PNG image.';
        }
        if (file.size > MAX_FILE_SIZE) {
            return 'The receipt may not be larger than 5MB.';
        }

        return '';
    }

    // Kapag binuksan ulit ang modal, linisin ang receipt at error
    confirmPaidModalEl.addEventListener('hidden.bs.modal', function () {
        receiptInput.value = '';
        clearPaidError();
    });

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

        // FormData ang gamit (hindi JSON) dahil may file na kasama.
        // Huwag maglagay ng 'Content-Type' header — ang browser na ang bahala.
        const formData = new FormData();
        formData.append('require_proof', '1');
        formData.append('paid_date', paidDateInput.value);
        formData.append('receipt', receiptInput.files[0]);

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
                // para hindi mawala ang piniling file ng admin.
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