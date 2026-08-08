<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ran Serenity || Dashboard</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-dashboard.css') }}" />
</head>
<body>
    <nav class="nxl-navigation">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="{{ route('admin.dashboard') }}" class="b-brand">
                    <h3 class="fst-italic">Ran Serenity</h3>
                </a>
            </div>
            <div class="navbar-content">
                <ul class="nxl-navbar">
                    <li class="nxl-item">
                        <a href="{{ route('admin.dashboard') }}" class="nxl-link active">
                            <span class="nxl-micon"><i class="feather-airplay"></i></span>
                            <span class="nxl-mtext">Dashboard</span>
                        </a>
                    </li>

                    <!-- Borrower Verification -->
                     <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Borrower Verification</span>
                            @if($pendingCount > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? 'pending') === 'pending' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification') }}">Pending</a>
                            </li>
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? '') === 'approved' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'approved']) }}">Approved</a>
                            </li>
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? '') === 'rejected' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'rejected']) }}">Rejected</a>
                            </li>
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? '') === 'on_hold' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'on_hold']) }}">On Hold</a>
                            </li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-file-text"></i></span>
                            <span class="nxl-mtext">Loan Applications</span>
                            @if(($pendingLoanCount ?? 0) > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingLoanCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications') }}">Pending Applications</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'approved']) }}">Approved</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'rejected']) }}">Rejected</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'on_hold']) }}">On Hold</a></li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Borrowers</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.penalty-tracking') }}">Penalty Tracking</a></li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu"> 
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                            <span class="nxl-mtext">Active Loans</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.completed-loans') }}">Completed Loans</a></li>
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

            {{-- ============ SIDEBAR BOTTOM — ADMIN PROFILE ============
                 Stays pinned under the scrollable menu via the flex-column
                 layout on .navbar-wrapper above. Dropup so it opens upward
                 (it's at the bottom of the screen). --}}
            <div class="nxl-sidebar-footer">
                <div class="dropdown dropup">
                    <button type="button" class="nxl-admin-trigger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="nxl-admin-avatar">{{ $adminInitials }}</div>
                        <div class="nxl-admin-info">
                            <div class="nxl-admin-name">{{ $adminName }}</div>
                            <div class="nxl-admin-role">{{ $adminRole }}</div>
                        </div>
                        <i class="feather-settings nxl-admin-menu-icon"></i>
                    </button>
                    <ul class="dropdown-menu nxl-admin-dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="feather-user me-2"></i> My Profile</a></li>
                        {{-- Activity Log / Account Settings only linked if those routes already exist in the app --}}
                        @if(\Illuminate\Support\Facades\Route::has('admin.account-settings'))
                            <li><a class="dropdown-item" href="{{ route('admin.account-settings') }}"><i class="feather-settings me-2"></i> Account Settings</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="confirmLogout()">
                                <i class="feather-log-out me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <header class="nxl-header">
        <div class="header-wrapper">
            <div class="header-left d-flex align-items-center gap-4">
                <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                    <div class="hamburger hamburger--arrowturn">
                        <div class="hamburger-box"><div class="hamburger-inner"></div></div>
                    </div>
                </a>
                <div class="nxl-navigation-toggle">
                    <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                    <a href="javascript:void(0);" id="menu-expend-button" style="display: none"><i class="feather-arrow-right"></i></a>
                </div>
            </div>
            <div class="header-right ms-auto">
                <div class="d-flex align-items-center">
                    <div class="nxl-h-item dark-light-theme">
                        <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button"><i class="feather-moon"></i></a>
                        <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display: none"><i class="feather-sun"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-left d-flex align-items-center">
                    <div class="page-header-title"><h5 class="m-b-10">Dashboard</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Dashboard</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">
                <div class="row">
                    {{-- ============ TOP: SUMMARY CARDS (unchanged) ============ --}}
                    <div class="col-xxl-3 col-md-6">
                        <div class="card stretch stretch-full">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-4">
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="avatar-text avatar-lg bg-soft-primary text-primary">
                                            <i class="feather-file-text"></i>
                                        </div>
                                        <div>
                                            <div class="fs-13 fw-semibold text-muted text-truncate-1-line">Total Applications</div>
                                            <h3 class="fw-bold text-dark mt-1">{{ $totalApplications }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-4 border-top">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="fs-12 text-muted">Pending Review</span>
                                        <span class="fs-12 fw-semibold text-dark">{{ $pendingApplications }}</span>
                                    </div>
                                    <div class="progress ht-3">
                                        @php
                                            $pendingPct = $totalApplications > 0 ? round(($pendingApplications / $totalApplications) * 100) : 0;
                                        @endphp
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pendingPct }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-3 col-md-6">
                        <div class="card stretch stretch-full">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-4">
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="avatar-text avatar-lg bg-soft-success text-success">
                                            <i class="feather-dollar-sign"></i>
                                        </div>
                                        <div>
                                            <div class="fs-13 fw-semibold text-muted text-truncate-1-line">Active Loans</div>
                                            <h3 class="fw-bold text-dark mt-1">{{ $activeLoans }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-4 border-top">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="fs-12 text-muted">Total Amount</span>
                                        <span class="fs-12 fw-semibold text-dark">&#8369;{{ number_format($totalActiveAmount, 2) }}</span>
                                    </div>
                                    <div class="progress ht-3">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-3 col-md-6">
                        <div class="card stretch stretch-full">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-4">
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="avatar-text avatar-lg bg-soft-warning text-warning">
                                            <i class="feather-users"></i>
                                        </div>
                                        <div>
                                            <div class="fs-13 fw-semibold text-muted text-truncate-1-line">Total Borrowers</div>
                                            <h3 class="fw-bold text-dark mt-1">{{ $totalBorrowers }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-4 border-top">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="fs-12 text-muted">Active Borrowers</span>
                                        <span class="fs-12 fw-semibold text-dark">{{ $activeBorrowers }}</span>
                                    </div>
                                    <div class="progress ht-3">
                                        @php
                                            $activePct = $totalBorrowers > 0 ? round(($activeBorrowers / $totalBorrowers) * 100) : 0;
                                        @endphp
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $activePct }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-3 col-md-6">
                        <div class="card stretch stretch-full">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-4">
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="avatar-text avatar-lg bg-soft-danger text-danger">
                                            <i class="feather-user-check"></i>
                                        </div>
                                        <div>
                                            <div class="fs-13 fw-semibold text-muted text-truncate-1-line">Pending Verification</div>
                                            <h3 class="fw-bold text-dark mt-1">{{ $pendingVerificationCount }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-4 border-top">
                                    <a href="{{ route('admin.borrower-verification') }}" class="fs-12 fw-semibold text-primary">
                                        Review now <i class="feather-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ MIDDLE: Recent Loan Applications | Attention Required ============ --}}
                    <div class="col-lg-8">
                        <div class="card stretch stretch-full">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="card-title">Recent Loan Applications</h5>
                                <a href="{{ route('admin.loan-applications') }}" class="fs-12 fw-semibold text-primary">
                                    View All <i class="feather-arrow-right"></i>
                                </a>
                            </div>
                            <div class="card-body custom-card-action p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr class="border-b">
                                                <th>Borrower</th>
                                                <th>Loan Amount</th>
                                                <th>Date Applied</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recentApplications as $app)
                                                <tr>
                                                    <td><span class="fw-bold text-dark d-block">{{ $app->borrower_name }}</span></td>
                                                    <td class="fw-bold text-dark">&#8369;{{ number_format($app->total_amount, 2) }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</td>
                                                    <td>
                                                        @php
                                                            $statusColors = [
                                                                'pending'  => 'warning',
                                                                'active'   => 'success',
                                                                'approved' => 'success',
                                                                'rejected' => 'danger',
                                                                'on_hold'  => 'secondary',
                                                            ];
                                                            $color = $statusColors[$app->status] ?? 'secondary';
                                                        @endphp
                                                        <span class="badge bg-soft-{{ $color }} text-{{ $color }}">{{ ucfirst($app->status) }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">No Pending applications.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card stretch stretch-full">
                            <div class="card-header">
                                <h5 class="card-title">Attention Required</h5>
                            </div>
                            <div class="card-body">
                                @foreach($attentionItems as $item)
                                    <div class="attn-item">
                                        <div class="attn-icon bg-soft-{{ $item['color'] }} text-{{ $item['color'] }}">
                                            <i class="{{ $item['icon'] }}"></i>
                                        </div>
                                        <div class="attn-text">
                                            <div class="attn-label">{{ $item['label'] }}</div>
                                            <div class="attn-count">{{ $item['count'] }} {{ $item['count'] == 1 ? 'item' : 'items' }}</div>
                                        </div>
                                        <a href="{{ $item['url'] }}" class="attn-action text-{{ $item['color'] }}">
                                            {{ $item['action'] }} <i class="feather-arrow-right"></i>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- ============ BOTTOM: Upcoming Payments | Recent Activity ============ --}}
                    <div class="col-lg-6">
                        <div class="card stretch stretch-full">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="card-title">Upcoming Payments</h5>
                            </div>
                            <div class="card-body">
                                @forelse($upcomingPayments as $payment)
                                    <div class="upcoming-row">
                                        <div>
                                            <div class="upcoming-name">{{ $payment->borrower_name }}</div>
                                            <div class="upcoming-due">{{ \Carbon\Carbon::parse($payment->due_date)->format('M d, Y') }}</div>
                                        </div>
                                        <div class="upcoming-amount">&#8369;{{ number_format($payment->amount_due, 2) }}</div>
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-4 fs-13">No upcoming payments</div>
                                @endforelse
                            </div>
                            @if($upcomingPayments->isNotEmpty())
                                <div class="card-footer text-center border-top py-3">
                                    <a href="{{ route('admin.ongoing-loans') }}" class="fs-12 fw-semibold text-primary">
                                        View Active Loans <i class="feather-arrow-right"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card stretch stretch-full">
                            <div class="card-header">
                                <h5 class="card-title">Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                @forelse($recentActivity as $activity)
                                    <div class="activity-row">
                                        <div class="activity-dot bg-soft-{{ $activity['color'] }} text-{{ $activity['color'] }}">
                                            <i class="{{ $activity['icon'] }}"></i>
                                        </div>
                                        <div>
                                            <div class="activity-text">{{ $activity['text'] }}</div>
                                            <div class="activity-meta">
                                                @if(!empty($activity['meta']))
                                                    {{ $activity['meta'] }} &middot;
                                                @endif
                                                {{ \Carbon\Carbon::parse($activity['ts'])->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-4 fs-13">No recent activity yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer">
            <p class="text-center"><span>&copy; {{ date('Y') }} Ran Serenity Lending System</span></p>
        </footer>
    </main>

    <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
</body>
</html>