// public/functions/logout-modal.js
// Used by: admin nav "Logout" link (onclick="confirmLogout()")
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
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

     SessionSync.broadcastLogout();

    fetch('/api/logout', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(() => {
        window.location.href = '/login';
    })
    .catch(() => {
        window.location.href = '/login';
    });
}