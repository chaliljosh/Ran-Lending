// public/functions/assigned-payment-method.js
// Used by: resources/views/admin/partials/assigned-payment-method.blade.php
// (loaded from admin/ongoing-loan-detail.blade.php)

(function () {
    const panel = document.getElementById('pmAssignPanel');
    const select = document.getElementById('pmAssignSelect');
    const btn = document.getElementById('pmAssignBtn');
    if (!panel || !select || !btn) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const feedback = document.getElementById('pmAssignFeedback');
    let currentId = panel.dataset.currentId || '';

    function setFeedback(message, ok) {
        feedback.textContent = message || '';
        feedback.className = 'pm-assign-feedback' + (message ? (ok ? ' is-ok' : ' is-err') : '');
    }

    function refreshButton() {
        // Only enabled when the admin picked something different from what is assigned.
        btn.disabled = select.value === '' || select.value === currentId;
    }

    // Everything is written with textContent, so account data can never inject HTML.
    function showMethod(m) {
        document.getElementById('pmdProvider').textContent = m.provider || '';
        document.getElementById('pmdLabel').textContent = m.account_label || '';
        document.getElementById('pmdName').textContent = m.account_name || '';
        document.getElementById('pmdNumber').textContent = m.account_number || '';
        document.getElementById('pmdInstructions').textContent = m.instructions || '';

        document.getElementById('pmdInstructionsWrap').style.display = m.instructions ? '' : 'none';
        document.getElementById('pmdInactive').style.display = m.is_active ? 'none' : '';
        document.getElementById('pmAssignedNone').style.display = 'none';
        document.getElementById('pmAssignedBox').style.display = '';
    }

    select.addEventListener('change', function () {
        setFeedback('');
        refreshButton();
    });

    btn.addEventListener('click', async function () {
        const idleText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Saving...';
        setFeedback('');

        try {
            const res = await fetch(panel.dataset.assignUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ payment_method_id: select.value }),
            });

            let data = {};
            try { data = await res.json(); } catch (e) { /* non-JSON response */ }

            if (!res.ok || !data.success) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(firstError || data.message || 'Failed to assign the payment method.');
            }

            currentId = String(data.method.id);
            panel.dataset.currentId = currentId;
            showMethod(data.method);
            btn.textContent = 'Change Assignment';
            setFeedback(data.message, true);
        } catch (e) {
            btn.textContent = idleText;
            setFeedback(e.message, false);
        } finally {
            refreshButton();
        }
    });

    refreshButton();
})();