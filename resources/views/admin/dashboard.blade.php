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
                    <li class="nxl-item">
                        <a href="{{ route('admin.borrower-verification') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Borrower Verification</span>
                            @if($pendingVerificationCount > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingVerificationCount }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-file-text"></i></span>
                            <span class="nxl-mtext">Loan Applications</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications') }}">Pending Applications</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">Approved</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">Rejected</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">On Hold</a></li>
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

                    <div class="col-lg-8">
                        <div class="card stretch stretch-full">
                            <div class="card-header">
                                <h5 class="card-title">Recent Loan Applications</h5>
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
                            <div class="card-header"><h5 class="card-title">Quick Actions</h5></div>
                            <div class="card-body">
                                <div class="d-flex flex-column gap-3">
                                    @if($pendingVerificationCount > 0)
                                        <a href="{{ route('admin.borrower-verification') }}" class="btn btn-light-brand w-100 justify-content-start">
                                            <i class="feather-user-check me-2"></i>
                                            Review Borrower Verifications
                                            <span class="badge bg-danger ms-auto">{{ $pendingVerificationCount }}</span>
                                        </a>
                                    @endif
                                    <a href="#" class="btn btn-light-brand w-100 justify-content-start">
                                        <i class="feather-file-text me-2"></i>
                                        Review Pending Applications
                                        <span class="badge bg-warning ms-auto">{{ $pendingApplications }}</span>
                                    </a>
                                    <a href="#" class="btn btn-light-brand w-100 justify-content-start">
                                        <i class="feather-users me-2"></i>
                                        Manage Borrowers
                                    </a>
                                    <a href="#" class="btn btn-light-brand w-100 justify-content-start">
                                        <i class="feather-dollar-sign me-2"></i>
                                        View Active Loans
                                    </a>
                                </div>
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