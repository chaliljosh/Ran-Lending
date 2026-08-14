<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Active Borrower Detail - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    {{-- Reuses section-card / info-label / table-loan-summary / ld-schedule-*
         / borrower-avatar / badges / sidebar-highlight / dark-mode / footer
         rules already defined here — nothing below duplicates them. --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-details.css') }}">
    {{-- Standalone, page-specific additions only (standing badge + progress bar) --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/active-borrower-detail.css') }}">
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
                    <li class="nxl-item nxl-hasmenu nxl-trigger active">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Borrowers</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                            <li class="nxl-item" data-current="true"><a class="nxl-link" href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
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
                    <div class="page-header-title"><h5 class="m-b-10">Active Borrower Detail</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
                        <li class="breadcrumb-item">#{{ $applicationId }}</li>
                    </ul>
                </div>
                <div class="page-header-right ms-auto">
                    <a href="{{ route('admin.active-borrowers') }}" class="btn btn-light">Back</a>
                </div>
            </div>

            <div class="main-content">
                <div class="row">

                    <!-- LEFT COLUMN: Borrower Profile + Loan Summary -->
                    <div class="col-lg-4 ld-left-col">
                        <div class="ld-sticky-col">

                            <!-- 1. BORROWER PROFILE -->
                            <div class="section-card abd-profile-card mb-3">
                                <div class="abd-profile-row">
                                    <div class="borrower-avatar abd-profile-avatar">
                                        {{ strtoupper(substr($app->first_name ?? '', 0, 1) . substr($app->last_name ?? '', 0, 1)) }}
                                    </div>
                                    <div class="abd-profile-info">
                                        <div class="abd-profile-name">{{ $app->first_name }} {{ $app->last_name }}</div>
                                        <div class="abd-profile-email">{{ $app->email }}</div>
                                        <div class="abd-profile-badges">
                                            <span class="badge badge-approved px-3 py-2">Active</span>
                                            <span class="abd-standing is-{{ $standing }}">{{ $standingLabel }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. LOAN SUMMARY (compact) -->
                            <div class="section-card">
                                <p class="section-title"><i class="feather-file-text me-2"></i>Loan Summary</p>
                                <table class="table table-sm table-borderless table-loan-summary mb-0">
                                    <tr><td class="info-label">Application ID</td><td class="text-end info-value">#{{ $app->application_id }}</td></tr>
                                    <tr><td class="info-label">Loan Amount</td><td class="text-end"><span class="ld-value-pill  abd-pill-emphasis">₱{{ number_format($app->loan_amount, 2) }}</span></td></tr>
                                    <tr><td class="info-label">Term</td><td class="text-end info-value">{{ $app->loan_term }} months</td></tr>
                                    <tr><td class="info-label">Interest Rate</td><td class="text-end info-value">5% / month</td></tr>
                                    <tr><td class="info-label">Total Interest</td><td class="text-end"><span class="ld-value-pill">₱{{ number_format($app->total_interest ?? 0, 2) }}</span></td></tr>
                                    <tr><td class="info-label">Total to Pay</td><td class="text-end"><span class="ld-value-pill abd-pill-emphasis">₱{{ number_format($app->total_amount ?? 0, 2) }}</span></td></tr>
                                    <tr><td class="info-label">Monthly Payment</td><td class="text-end"><span class="ld-value-pill  abd-pill-emphasis">₱{{ number_format($app->monthly_payment ?? 0, 2) }}</span></td></tr>
                                    <tr><td class="info-label">Purpose</td><td class="text-end info-value">{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}</td></tr>
                                    <tr><td class="info-label">Applied</td><td class="text-end info-value">{{ date('M d, Y', strtotime($app->applied_at)) }}</td></tr>
                                </table>
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Payment Progress + Payment Schedule -->
                    <div class="col-lg-8">

                        <!-- 3. PAYMENT PROGRESS -->
                        <div class="section-card mb-3">
                            <div class="abd-progress-head">
                                <span class="abd-progress-label">Payment Progress</span>
                                <span class="abd-progress-count">{{ $paidCount }} of {{ $totalCount }} payments completed</span>
                            </div>
                            <div class="abd-progress-track">
                                <div class="abd-progress-fill {{ $progressPct >= 100 ? 'is-complete' : '' }}" style="width: {{ $progressPct }}%;"></div>
                            </div>

                            @php
                                $nextPayment = $paymentSchedule->first(fn($s) => is_null($s->paid_at));
                                $nextIsOverdue = $nextPayment && \Carbon\Carbon::parse($nextPayment->due_date)->isPast();
                            @endphp
                            @if($nextPayment)
                                <div class="abd-next-payment">
                                    <div>
                                        <div class="abd-next-payment-label">Next Payment</div>
                                        <div class="abd-next-payment-date">{{ \Carbon\Carbon::parse($nextPayment->due_date)->format('M d, Y') }}</div>
                                    </div>
                                    <div class="abd-next-payment-amount">₱{{ number_format($nextPayment->amount_due, 2) }}</div>
                                    <span class="ld-schedule-badge {{ $nextIsOverdue ? 'ld-schedule-late' : 'ld-schedule-pending' }}">
                                        {{ $nextIsOverdue ? 'Overdue' : 'Pending' }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- 4. PAYMENT SCHEDULE -->
                        <div class="section-card abd-schedule-card">
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
                                        @forelse($paymentSchedule as $s)
                                        @php
                                            $isOverdue = is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
                                            $isPaid = !is_null($s->paid_at);
                                            $scheduleBadge = $isPaid ? 'ld-schedule-paid' : ($isOverdue ? 'ld-schedule-late' : 'ld-schedule-pending');
                                            $scheduleLabel = $isPaid ? 'Paid' : ($isOverdue ? 'Overdue' : 'Pending');
                                        @endphp
                                        <tr>
                                            <td>{{ $s->installment_no }}</td>
                                            <td>{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}</td>
                                            <td>₱{{ number_format($s->amount_due, 2) }}</td>
                                            <td>₱{{ number_format($s->amount_paid, 2) }}</td>
                                            <td>{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                                            <td><span class="ld-schedule-badge {{ $scheduleBadge }}">{{ $scheduleLabel }}</span></td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No payment schedule generated yet.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
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