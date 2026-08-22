// penalty-tracking-detail.js
// Handles Mark as Paid and Waive Penalty on the detail page

(function () {

    // Get CSRF token from meta tag
    var csrfToken = '';
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        csrfToken = csrfMeta.getAttribute('content');
    }

    // ─────────────────────────────────────────
    // MARK AS PAID
    // ─────────────────────────────────────────
    document.addEventListener('click', function (e) {

        // Check if clicked button is the "Yes, Mark as Paid" button
        var btn = e.target.closest('.pt-btn-confirm-paid[data-mark-paid-url]');
        if (!btn) return;

        var url      = btn.getAttribute('data-mark-paid-url');
        var redirect = btn.getAttribute('data-redirect');

        // Disable button while processing
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

        // Send AJAX request
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN'     : csrfToken,
                'X-Requested-With' : 'XMLHttpRequest',
                'Accept'           : 'application/json',
            },
        })
        .then(function (res) {
            return res.json();
        })
        .then(function (data) {
            if (data && data.success) {
                // Go back to penalty tracking list
                window.location.href = redirect;
            } else {
                // Re-enable button on failure
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Yes, Mark as Paid';
                alert('Hindi na-mark as paid. Subukan ulit.');
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Yes, Mark as Paid';
            alert('May error. Subukan ulit.');
        });

    });

    // ─────────────────────────────────────────
    // WAIVE PENALTY
    // ─────────────────────────────────────────
    var waiveForm = document.querySelector('.pt-waive-form');

    if (waiveForm) {

        waiveForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var redirect  = waiveForm.getAttribute('data-redirect');
            var submitBtn = waiveForm.querySelector('.pt-btn-confirm-waive');

            // Disable submit button while processing
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
            }

            // Send AJAX request with form data (includes waived_reason)
            fetch(waiveForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN'     : csrfToken,
                    'X-Requested-With' : 'XMLHttpRequest',
                    'Accept'           : 'application/json',
                },
                body: new FormData(waiveForm),
            })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (data && data.success) {
                    // Go back to penalty tracking list
                    window.location.href = redirect;
                } else {
                    // Re-enable button on failure
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Confirm Waive';
                    }
                    alert('Hindi na-waive. Subukan ulit.');
                }
            })
            .catch(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Confirm Waive';
                }
                alert('May error. Subukan ulit.');
            });

        });
    }

})();