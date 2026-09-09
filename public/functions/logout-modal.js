// public/functions/logout-modal.js
// Used by: both admin and borrower nav "Logout" links (onclick="confirmLogout()")
// Endpoint: POST /api/logout (routes/web.php, name('logout'))
//
// Also handles CROSS-TAB logout sync: when one tab logs out, any other open
// tab on the same site (e.g. a dashboard tab that was just left open, never
// touched) shows a "You've been logged out" overlay instead of silently
// staying on a now-invalid session until the user clicks/refreshes something.

const LOGOUT_BROADCAST_KEY = 'ran_logout_broadcast';

// ---- Listener: fires in OTHER tabs when this key changes in localStorage.
// (Note: the 'storage' event never fires in the tab that made the change,
// only in other tabs — exactly the behavior we want here.)
window.addEventListener('storage', function (e) {
    if (e.key === LOGOUT_BROADCAST_KEY && e.newValue) {
        showLoggedOutOverlay();
    }
});

function showLoggedOutOverlay() {
    if (document.getElementById('loggedOutOverlay')) return; // already showing, avoid dupes

    const overlay = document.createElement('div');
    overlay.id = 'loggedOutOverlay';
    overlay.className = 'logged-out-overlay';
    overlay.innerHTML = `
        <div class="logged-out-card">
            <button type="button" class="logged-out-close-btn" id="loggedOutCloseBtn" aria-label="Close"></button>
            <h4>You've been logged out</h4>
            <p>Please log in again.</p>
            <a href="/login" class="logged-out-link">Log in</a>
        </div>
    `;
    document.body.appendChild(overlay);

    // Close just dismisses the notice — doesn't try to keep the stale
    // session alive, it only lets the user stop looking at it.
    document.getElementById('loggedOutCloseBtn').addEventListener('click', function () {
        overlay.remove();
    });
}

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

        <button type="button" class="logout-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="modal-body text-center">

            <h5 class="mt-3">
            Log out Account
            </h5>

            <p class="mt-2 text-muted">
                Are you sure you want to log out? You will need to log in again to access your account.
            </p>

        </div>

        <div class="modal-footer">

            <button
                type="button"
                class="btn logout-cancel-btn"
                data-bs-dismiss="modal">
                Cancel
            </button>

            <button
                type="button"
                class="btn logout-danger-btn"
                id="logoutConfirmBtn">
                Log out
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

    // Broadcast to any other open tabs so they lock out immediately too,
    // instead of staying on a stale dashboard until the user interacts
    // with them. Wrapped in try/catch: some browsers block localStorage
    // in certain contexts (private mode, etc.) — that should never block
    // the actual logout of THIS tab.
    try {
        localStorage.setItem(LOGOUT_BROADCAST_KEY, Date.now().toString());
    } catch (err) {
        console.error('[logout-modal.js] cross-tab broadcast failed (ignoring):', err);
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