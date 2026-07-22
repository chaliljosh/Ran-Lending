function renderBorrowerNav(data) {
    const currentPage = window.location.pathname.split('/').pop() || 'dashboard';

    const isActive = (page) => currentPage === page ? 'active' : '';

    let links = `
        <li class="nxl-item">
            <a href="/borrower/dashboard" class="nxl-link ${isActive('dashboard')}">
                <span class="nxl-micon"><i class="feather-airplay"></i></span>
                <span class="nxl-mtext">Dashboard</span>
            </a>
        </li>
    `;

    if (!data.isFullyApproved) {
        links += `
            <li class="nxl-item">
                <a href="/borrower/personal-details" class="nxl-link ${isActive('personal-details')}">
                    <span class="nxl-micon"><i class="feather-user-check"></i></span>
                    <span class="nxl-mtext">Personal Details</span>
                </a>
            </li>
        `;
    }

    if (data.isFullyApproved) {
        links += `
            <li class="nxl-item">
                <a href="/borrower/apply-loan" class="nxl-link ${isActive('apply-loan')}">
                    <span class="nxl-micon"><i class="feather-file-plus"></i></span>
                    <span class="nxl-mtext">Apply for a Loan</span>
                </a>
            </li>
            <li class="nxl-item">
                <a href="/borrower/loan-details" class="nxl-link ${isActive('loan-details')}">
                    <span class="nxl-micon"><i class="feather-file-text"></i></span>
                    <span class="nxl-mtext">View Loan Details</span>
                </a>
            </li>
            <li class="nxl-item">
                <a href="/borrower/notifications" class="nxl-link ${isActive('notifications')}">
                    <span class="nxl-micon"><i class="feather-bell"></i></span>
                    <span class="nxl-mtext">Notifications</span>
                </a>
            </li>
            <li class="nxl-item">
                <a href="/borrower/profile" class="nxl-link ${isActive('profile')}">
                    <span class="nxl-micon"><i class="feather-user"></i></span>
                    <span class="nxl-mtext">My Profile</span>
                </a>
            </li>
            <li class="nxl-item">
                <a href="javascript:void(0);" onclick="confirmLogout()" class="nxl-link" style="color:#e74c3c;">
                    <span class="nxl-micon"><i class="feather-log-out" style="color:#e74c3c;"></i></span>
                    <span class="nxl-mtext">Logout</span>
                </a>
            </li>
        `;
    }

    document.getElementById('navContainer').innerHTML = `
        <nav class="nxl-navigation">
            <div class="navbar-wrapper" style="position: relative;">
                <div class="m-header">
                    <a href="/borrower/dashboard" class="b-brand">
                        <h3 class="fst-italic">Ran Serenity</h3>
                    </a>
                </div>
                <div class="navbar-content" style="padding-bottom: 80px;">
                    <ul class="nxl-navbar">${links}</ul>
                </div>
                <div style="padding:14px 16px;border-top:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:12px;position:absolute;bottom:0;left:0;right:0;background:inherit;">
                    <div style="width:38px;height:38px;border-radius:50%;background:#4a7cf7;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:0.85rem;flex-shrink:0;">
                        ${data.initials}
                    </div>
                    <div style="overflow:hidden;flex:1;">
                        <p style="margin:0;font-size:0.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${data.userName}</p>
                        <p style="margin:0;font-size:0.75rem;opacity:0.6;">Borrower</p>
                    </div>
                    <a href="javascript:void(0);" onclick="confirmLogout()" title="Logout" style="color:#e74c3c;font-size:1.1rem;flex-shrink:0;opacity:0.8;">
                        <i class="feather-log-out"></i>
                    </a>
                </div>
            </div>
        </nav>
    `;
}