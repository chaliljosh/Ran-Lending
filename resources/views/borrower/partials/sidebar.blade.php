@push('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/layout-fixes.css') }}">
@endpush

<nav class="nxl-navigation">
    <div class="navbar-wrapper" style="position: relative;">
        <div class="m-header">
            <a href="{{ route('borrower.dashboard') }}" class="b-brand">
                <img src="{{ asset('assets/landingpage/img/ranlogo.png') }}" alt="Ran Serenity" class="b-brand-icon">
                <h3 class="fst-italic b-brand-text">Ran Serenity</h3>
            </a>
        </div>
        <div class="navbar-content">
            <ul class="nxl-navbar">
                <li class="nxl-nav-label" style="display:block; box-sizing:border-box; width:100%; margin:0; padding:16px 14px 8px; font-size:0.68rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#94A3B8; white-space:nowrap;">General</li>
                <li class="nxl-item {{ request()->routeIs('borrower.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('borrower.dashboard') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboard</span>
                    </a>
                </li>

                @if(!$isFullyApproved)
                <li class="nxl-item {{ request()->routeIs('borrower.personal-details') ? 'active' : '' }}">
                    <a href="{{ route('borrower.personal-details') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-user-check"></i></span>
                        <span class="nxl-mtext">Personal Details</span>
                    </a>
                </li>
                @endif

                @if($isFullyApproved)
                <li class="nxl-nav-label" style="display:block; box-sizing:border-box; width:100%; margin:0; padding:16px 14px 8px; font-size:0.68rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#94A3B8; white-space:nowrap;">Loan Management</li>

                {{-- Nakatago habang may ongoing loan (pending / approved / active / on hold) --}}
                @if(!($hasOngoingLoan ?? false))
                <li class="nxl-item {{ request()->routeIs('borrower.apply-loan') ? 'active' : '' }}">
                    <a href="{{ route('borrower.apply-loan') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-file-plus"></i></span>
                        <span class="nxl-mtext">Apply for a Loan</span>
                    </a>
                </li>
                @endif

                <li class="nxl-item {{ request()->routeIs('borrower.loan-details') ? 'active' : '' }}">
                    <a href="{{ route('borrower.loan-details') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-file-text"></i></span>
                        <span class="nxl-mtext">View Loan Details</span>
                    </a>
                </li>

                <li class="nxl-nav-divider"></li>

                <li class="nxl-item">
                    <a href="javascript:void(0);" onclick="confirmLogout()" class="nxl-link is-logout">
                        <span class="nxl-micon"><i class="feather-log-out"></i></span>
                        <span class="nxl-mtext">Logout</span>
                    </a>
                </li>
                @endif

            </ul>
        </div>
    </div>
</nav>