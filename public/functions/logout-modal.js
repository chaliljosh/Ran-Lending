// public/functions/logout-modal.js
// Used by: both admin and borrower nav "Logout" links (onclick="confirmLogout()")
// Endpoint: POST /api/logout (routes/web.php, name('logout'))
//
// Builds the confirm modal dynamically so it works on any page that includes
// this script, without needing to paste modal HTML into every blade file.

function confirmLogout() {
    let modalEl = document.getElementById('logoutConfirmModal');

    if (!modalEl) {
        modalEl = document.createElement('div');
        modalEl.className = 'modal fade';
        modalEl.id = 'logoutConfirmModal';
        modalEl.tabIndex = -1;
        modalEl.setAttribute('aria-hidden', 'true');
      modalEl.innerHTML = `
<div class="modal-dialog modal-dialog-centered">
    <div class="modal-content logout-modal">

       

        <div class="modal-body text-center">

            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>

            <h5 class="mt-3">
                Are you sure you want to log out?
            </h5>

            <p class="mt-2 text-muted">
                You will need to sign in again to access your account.
            </p>

        </div>

        <div class="modal-footer">

            <button
                type="button"
                class="btn logout-cancel-btn flex-fill"
                data-bs-dismiss="modal">
                Cancel
            </button>

            <button
                type="button"
                class="btn logout-danger-btn flex-fill"
                id="logoutConfirmBtn">
                Logout
            </button>

        </div>

    </div>
</div>
        `;
        document.body.appendChild(modalEl);

        document.getElementById('logoutConfirmBtn').addEventListener('click', performLogout);
    }

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function performLogout() {
    const btn = document.getElementById('logoutConfirmBtn');

    // Guard against a double-click firing this twice while the first
    // request is still in flight.
    if (btn.disabled) return;

    const originalBtnHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

    // Broadcasting the logout to other open tabs is a nice-to-have, not a
    // requirement for logging THIS tab out. Previously this called
    // SessionSync.broadcastLogout() directly and unguarded — on any page
    // where that script isn't loaded (e.g. the borrower dashboard), this
    // threw an uncaught error RIGHT HERE, before fetch() ever ran. That's
    // why the button spun forever and never actually logged out: the
    // request was never sent at all. Guarding it means a missing/broken
    // SessionSync can never block the actual logout again.
    try {
        if (typeof SessionSync !== 'undefined' && typeof SessionSync.broadcastLogout === 'function') {
            SessionSync.broadcastLogout();
        }
    } catch (err) {
        console.error('[logout-modal.js] SessionSync.broadcastLogout failed (ignoring):', err);
    }

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    // Failsafe: whatever else happens, force the redirect after 5s so this
    // button can never get stuck spinning again, even if something we
    // haven't anticipated goes wrong with the request itself.
    let redirected = false;
    const goToLogin = () => {
        if (redirected) return;
        redirected = true;
        window.location.href = '/login';
    };
    const failsafeTimer = setTimeout(goToLogin, 5000);

    fetch('/api/logout', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(() => {
        clearTimeout(failsafeTimer);
        goToLogin();
    })
    .catch((err) => {
        console.error('[logout-modal.js] logout request failed:', err);
        clearTimeout(failsafeTimer);
        goToLogin();
    })
    .finally(() => {
        // Only matters if, for some reason, we're still on this page
        // (redirect blocked, etc.) — restore the button instead of
        // leaving it permanently disabled with a spinner.
        btn.disabled = false;
        btn.innerHTML = originalBtnHtml;
    });
}