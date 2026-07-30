<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('admin.dashboard') }}" class="b-brand"><h3 class="fst-italic">Ran Serenity</h3></a>
        </div>
        <div class="navbar-content">
            <ul class="nxl-navbar">
                <li class="nxl-item">
                    <a href="{{ route('admin.dashboard') }}" class="nxl-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboard</span>
                    </a>
                </li>

                <li class="nxl-item">
                    <a href="{{ route('admin.borrower-verification') }}" class="nxl-link {{ request()->routeIs('admin.borrower-verification') || request()->routeIs('admin.verification-detail') ? 'active' : '' }}">
                        <span class="nxl-micon"><i class="feather-user-check"></i></span>
                        <span class="nxl-mtext">Borrower Verification</span>
                        @if(($pendingCount ?? 0) > 0)
                            <span class="badge bg-danger ms-auto">{{ $pendingCount }}</span>
                        @endif
                    </a>
                </li>

                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('admin.loan-applications') || request()->routeIs('admin.loan-application-detail') ? 'nxl-trigger active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-file-text"></i></span>
                        <span class="nxl-mtext">Loan Applications</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item">
                            <a class="nxl-link {{ request()->routeIs('admin.loan-applications') && request('status', 'pending') === 'pending' ? 'active' : '' }}"
                               href="{{ route('admin.loan-applications', ['status' => 'pending']) }}">
                                Pending Applications
                            </a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link {{ request('status') === 'approved' ? 'active' : '' }}"
                               href="{{ route('admin.loan-applications', ['status' => 'approved']) }}">
                                Approved
                            </a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link {{ request('status') === 'rejected' ? 'active' : '' }}"
                               href="{{ route('admin.loan-applications', ['status' => 'rejected']) }}">
                                Rejected
                            </a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link {{ request('status') === 'on_hold' ? 'active' : '' }}"
                               href="{{ route('admin.loan-applications', ['status' => 'on_hold']) }}">
                                On Hold
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-users"></i></span>
                        <span class="nxl-mtext">Borrowers</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item"><a class="nxl-link" href="#">All Borrowers</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="#">Active Borrowers</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="#">Penalty Tracking</a></li>
                    </ul>
                </li>

                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                        <span class="nxl-mtext">Active Loans</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item"><a class="nxl-link" href="#">Ongoing Loans</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="#">Overdue Payments</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="#">Completed Loans</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="#">Defaulted</a></li>
                    </ul>
                </li>

                <li class="nxl-item">
                    <a href="javascript:void(0);" onclick="confirmLogout()" class="nxl-link text-danger">
                        <span class="nxl-micon"><i class="feather-log-out"></i></span>
                        <span class="nxl-mtext">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>