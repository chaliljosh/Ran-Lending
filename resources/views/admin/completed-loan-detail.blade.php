<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Completed Loan Detail - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    {{-- Reused lang yung existing loan-details.css (section-card, ld-field,
         badges, ld-schedule-table, atbp.) — walang binagong rule dito,
         layout overrides lang ang nasa bagong completed-loan-detail.css. --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-details.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/borrower-performance.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/completed-loan-detail.css') }}">
</head>
<body>
    <nav class="nxl-navigation">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="{{ route('admin.dashboard') }}" class="b-brand"><h3 class="fst-italic">Ran Serenity</h3></a>
            </div>
            <div class="navbar-content">
                <ul class="nxl-navbar">
                    <li class="nxl-item">
                        <a href="{{ route('admin.dashboard') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-airplay"></i></span>
                            <span class="nxl-mtext">Dashboard</span>
                        </a>
                    </li>
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
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification') }}">Pending</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'approved']) }}">Approved</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'rejected']) }}">Rejected</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'on_hold']) }}">On Hold</a></li>
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
                    <li class="nxl-item nxl-hasmenu nxl-trigger active">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                            <span class="nxl-mtext">Active Loans</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                            <li class="nxl-item" data-current="true"><a class="nxl-link" href="{{ route('admin.completed-loans') }}">Completed Loans</a></li>
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

    <header class="nxl-header">
        <div class="header-wrapper">
            <div class="header-left d-flex align-items-center gap-4">
                <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                    <div class="hamburger hamburger--arrowturn"><div class="hamburger-box"><div class="hamburger-inner"></div></div></div>
                </a>
                <div class="nxl-navigation-toggle">
                    <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                    <a href="javascript:void(0);" id="menu-expend-button" style="display:none"><i class="feather-arrow-right"></i></a>
                </div>
            </div>
            <div class="header-right ms-auto">
                <div class="nxl-h-item dark-light-theme">
                    <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button"><i class="feather-moon"></i></a>
                    <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display:none"><i class="feather-sun"></i></a>
                </div>
            </div>
        </div>
    </header>

    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-left d-flex align-items-center">
                    <div class="page-header-title"><h5 class="m-b-10">Completed Loan Detail</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.completed-loans') }}">Completed Loans</a></li>
                        <li class="breadcrumb-item">#{{ $applicationId }}</li>
                    </ul>
                </div>
                <div class="page-header-right ms-auto">
                    <a href="{{ route('admin.completed-loans') }}" class="btn btn-light">Back</a>
                </div>
            </div>

            <div class="main-content">
                <div class="cld-content">

                    <!-- Borrower Header -->
                    <div class="section-card cld-borrower-header mb-3">
                        <div class="borrower-avatar">
                            {{ strtoupper(substr($app->first_name ?? '', 0, 1) . substr($app->last_name ?? '', 0, 1)) }}
                        </div>
                        <div class="cld-borrower-right">
                            <h6 class="fw-bold mb-1">
                                {{ $app->last_name }}, {{ $app->first_name }} {{ $app->middle_name }}{{ ($app->suffix ?? null) ? ', ' . $app->suffix : '' }}
                            </h6>
                            <p class="text-muted mb-0" style="font-size:0.83rem;">{{ $app->email }}</p>
                            <div class="cld-borrower-meta">
                                <span class="badge badge-approved px-3 py-2">Completed</span>
                                @if($completedAt)
                                <span class="cld-completed-date">Fully paid on {{ \Carbon\Carbon::parse($completedAt)->format('M d, Y') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ============ BORROWER PERFORMANCE ============ --}}
                    <div class="bp-card mb-3">
                        <p class="bp-title"><i class="feather-trending-up me-2"></i>Borrower Performance</p>

                        <div class="bp-status-row">
                            <span class="bp-status-pill is-{{ $borrowerPerformance['rating'] }}">
                                <i class="bi {{
                                    $borrowerPerformance['rating'] === 'excellent' ? 'bi-check-circle-fill' :
                                    ($borrowerPerformance['rating'] === 'good' ? 'bi-check-circle-fill' :
                                    ($borrowerPerformance['rating'] === 'fair' ? 'bi-exclamation-circle-fill' :
                                    'bi-x-circle-fill'))
                                }}"></i>
                                {{ $borrowerPerformance['label'] }}
                            </span>
                            <p class="bp-description">{{ $borrowerPerformance['description'] }}</p>
                        </div>

                        <div class="bp-stats-row">
                            <div class="bp-stat">
                                <div class="bp-stat-value">{{ $borrowerPerformance['total_payments'] }}</div>
                                <div class="bp-stat-label">Payments</div>
                            </div>
                            <div class="bp-stat-divider"></div>
                            <div class="bp-stat">
                                <div class="bp-stat-value {{ $borrowerPerformance['late_payments'] > 0 ? 'is-warning-value' : '' }}">
                                    {{ $borrowerPerformance['late_payments'] }}
                                </div>
                                <div class="bp-stat-label">Late Payments</div>
                            </div>
                            <div class="bp-stat-divider"></div>
                            <div class="bp-stat">
                                <div class="bp-stat-value {{ $borrowerPerformance['total_penalties'] > 0 ? 'is-warning-value' : '' }}">
                                    ₱{{ number_format($borrowerPerformance['total_penalties'], 2) }}
                                </div>
                                <div class="bp-stat-label">Penalties</div>
                            </div>
                        </div>
                    </div>

                    <!-- Loan Summary — two-column label/value grid -->
                    <div class="section-card mb-3">
                        <p class="section-title"><i class="feather-file-text me-2"></i>Loan Summary</p>
                        <div class="cld-summary-grid">
                            <div class="cld-summary-item">
                                <span class="info-label">Application ID</span>
                                <span class="info-value">#{{ $app->application_id }}</span>
                            </div>
                            <div class="cld-summary-item">
                                <span class="info-label">Loan Amount</span>
                                <span class="ld-value-pill ld-pill-primary">₱{{ number_format($app->loan_amount, 2) }}</span>
                            </div>
                            <div class="cld-summary-item">
                                <span class="info-label">Term</span>
                                <span class="info-value">{{ $app->loan_term }} months</span>
                            </div>
                            <div class="cld-summary-item">
                                <span class="info-label">Interest Rate</span>
                                <span class="info-value">5% / month</span>
                            </div>
                            <div class="cld-summary-item">
                                <span class="info-label">Total Interest</span>
                                <span class="ld-value-pill ld-pill-warning">₱{{ number_format($app->total_interest ?? 0, 2) }}</span>
                            </div>
                            <div class="cld-summary-item">
                                <span class="info-label">Total Repaid</span>
                                <span class="ld-value-pill ld-pill-success">₱{{ number_format($amountCollected, 2) }}</span>
                            </div>
                            <div class="cld-summary-item">
                                <span class="info-label">Purpose</span>
                                <span class="info-value">{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}</span>
                            </div>
                            <div class="cld-summary-item">
                                <span class="info-label">Applied</span>
                                <span class="info-value">{{ date('M d, Y', strtotime($app->applied_at)) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Schedule -->
                    <div class="section-card">
                        <p class="section-title"><i class="feather-calendar me-2"></i>Payment Schedule</p>
                        <div class="table-responsive">
                            <table class="table table-sm ld-schedule-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Due Date</th>
                                        <th>Amount Due</th>
                                        <th>Amount Paid</th>
                                        <th>Paid On</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentSchedule as $s)
                                    @php
                                        $isPaid = !is_null($s->paid_at);
                                        $scheduleBadge = $isPaid ? 'ld-schedule-paid' : 'ld-schedule-pending';
                                        $scheduleLabel = $isPaid ? 'Paid' : 'Pending';
                                    @endphp
                                    <tr>
                                        <td>{{ $s->installment_no }}</td>
                                        <td>{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}</td>
                                        <td>₱{{ number_format($s->amount_due, 2) }}</td>
                                        <td>₱{{ number_format($s->amount_paid, 2) }}</td>
                                        <td>{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                                        <td><span class="ld-schedule-badge {{ $scheduleBadge }}">{{ $scheduleLabel }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <footer class="footer">
            <p class="text-center">&copy; {{ date('Y') }} Ran Serenity Lending System</p>
        </footer>
    </main>

    <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
</body>
</html>