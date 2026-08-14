<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ongoing Loan Detail - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-detail.css') }}" />
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
                            <li class="nxl-item" data-current="true"><a class="nxl-link" href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
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
                    <div class="page-header-title"><h5 class="m-b-10">Ongoing Loan Detail</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                        <li class="breadcrumb-item">Loan #{{ $applicationId }}</li>
                    </ul>
                </div>
                <div class="page-header-right ms-auto">
                    <a href="{{ route('admin.ongoing-loans') }}" class="btn btn-light"><i class="feather-arrow-left me-1"></i>Back</a>
                </div>
            </div>

            <div class="main-content">

                {{-- ============================================
                     BORROWER STRIP — identity only, flat style
                ============================================ --}}
                @php
                    $initials = strtoupper(substr($app->first_name ?? 'B', 0, 1) . substr($app->last_name ?? 'R', 0, 1));
                    $remainingCount = max(0, $totalCount - $paidCount);
                    $remainingAmount = max(0, ($app->total_amount ?? 0) - $amountCollected);
                    $nextDue = $paymentSchedule->firstWhere('paid_at', null);
                @endphp
                <div class="borrower-strip">
                    <div class="bs-left">
                        <div class="loan-avatar">{{ $initials }}</div>
                        <div class="bs-info">
                            <h5>{{ $app->first_name }} {{ $app->last_name }}</h5>
                            <div class="bs-meta">
                                <span>{{ $app->email }}</span>
                                <span class="bs-dot">&middot;</span>
                                <span>Loan ID: <strong>LN-{{ str_pad($applicationId, 5, '0', STR_PAD_LEFT) }}</strong></span>
                            </div>
                        </div>
                    </div>
                    <div class="bs-right">
                        <span class="badge badge-active">Active</span>
                    </div>
                </div>

                <div class="loan-detail-grid">

                    {{-- LEFT: Loan Summary --}}
                    <div class="panel loan-summary-panel">
                        <div class="panel-header"><i class="feather-file-text"></i>Loan Summary</div>
                        <div class="panel-body">

                            <div class="loan-info-grid">
                                <div class="info-item2">
                                    <label>Loan Amount</label>
                                    <span>&#8369;{{ number_format($app->loan_amount ?? 0, 2) }}</span>
                                </div>
                                <div class="info-item2">
                                    <label>Term</label>
                                    <span>{{ $app->loan_term ?? '—' }} {{ $app->loan_term ? 'months' : '' }}</span>
                                </div>
                                <div class="info-item2">
                                    <label>Interest Rate</label>
                                    <span>{{ $interestRate }}%</span>
                                </div>
                                <div class="info-item2">
                                    <label>Total Interest</label>
                                    <span>&#8369;{{ number_format($totalInterest, 2) }}</span>
                                </div>
                                <div class="info-item2 is-primary">
                                    <label>Total to Pay</label>
                                    <span>&#8369;{{ number_format($app->total_amount ?? 0, 2) }}</span>
                                </div>
                            </div>

                            <div class="loan-info-grid secondary">
                                <div class="info-item2">
                                    <label>Monthly Payment</label>
                                    <span>&#8369;{{ number_format($monthlyPayment, 2) }}</span>
                                </div>
                                <div class="info-item2">
                                    <label>Purpose</label>
                                    <span>{{ ucfirst(str_replace('_', ' ', $app->loan_purpose ?? '—')) }}</span>
                                </div>
                                <div class="info-item2">
                                    <label>Date Due Next</label>
                                    <span>{{ $nextDue ? \Carbon\Carbon::parse($nextDue->due_date)->format('F d, Y') : '—' }}</span>
                                </div>
                            </div>

                            <div class="lsc-progress">
                                <p class="lsc-progress-title">Payment Progress</p>
                                <div class="loan-info-grid progress-row">
                                    <div class="info-item2">
                                        <label>Installments Paid</label>
                                        <span>{{ $paidCount }}<span class="of-total">/{{ $totalCount }}</span></span>
                                    </div>
                                    <div class="info-item2">
                                        <label>Collected</label>
                                        <span>&#8369;{{ number_format($amountCollected, 2) }}</span>
                                    </div>
                                    <div class="info-item2">
                                        <label>Remaining</label>
                                        <span>&#8369;{{ number_format($remainingAmount, 2) }}</span>
                                    </div>
                                </div>
                                <div class="lsc-progress-bar">
                                    <div class="lsc-progress-fill" style="width: {{ $totalCount > 0 ? round(($paidCount / $totalCount) * 100) : 0 }}%;"></div>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- RIGHT: Admin Review Status --}}
                    <div class="panel loan-review-panel">
                        <div class="panel-header"><i class="feather-check-square"></i>Admin Review Status</div>
                        <div class="panel-body">
                            @if($allReviews->count() > 0)
                                @foreach($allReviews as $r)
                                @php $rInitials = strtoupper(substr($r->admin_name, 0, 2)); @endphp
                                <div class="review-item {{ $r->decision === 'approved' ? 'is-approved' : 'is-rejected' }}">
                                    <div class="review-avatar">{{ $rInitials }}</div>
                                    <div class="review-body">
                                        <span class="review-name">{{ $r->admin_name }}</span>
                                        @if($r->remarks)
                                            <span class="review-remark">{{ $r->remarks }}</span>
                                        @endif
                                        <span class="review-time">{{ \Carbon\Carbon::parse($r->reviewed_at)->format('M d, Y h:i A') }}</span>
                                    </div>
                                    <span class="badge {{ $r->decision === 'approved' ? 'badge-approved' : 'badge-rejected' }}">
                                        {{ ucfirst($r->decision) }}
                                    </span>
                                </div>
                                @endforeach
                            @else
                                <p class="text-muted empty-note">No admin decisions recorded for this loan yet.</p>
                            @endif

                            @if($comments->count() > 0)
                                <p class="comments-label">Comments</p>
                                @foreach($comments as $c)
                                @php $cInitials = strtoupper(substr($c->admin_name, 0, 2)); @endphp
                                <div class="review-item is-comment">
                                    <div class="review-avatar {{ $c->admin_id == $adminId ? 'mine' : '' }}">{{ $cInitials }}</div>
                                    <div class="review-body">
                                        <span class="review-name">{{ $c->admin_name }} {{ $c->admin_id == $adminId ? '(You)' : '' }}</span>
                                        <span class="review-remark">{{ $c->comment }}</span>
                                        <span class="review-time">{{ \Carbon\Carbon::parse($c->created_at)->format('M d, Y h:i A') }}</span>
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                </div>

                {{-- BELOW: Payment Schedule --}}
                <div class="panel payment-schedule-panel">
                    <div class="panel-header"><i class="feather-calendar"></i>Payment Schedule</div>
                    <div class="panel-body">
                        @if($paymentSchedule->count() > 0)
                        <div class="table-responsive">
                            <table class="loan-schedule-table">
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
                                        $isOverdue = !$isPaid && \Carbon\Carbon::parse($s->due_date)->isPast();
                                        $rowStatus = $isPaid ? 'paid' : ($isOverdue ? 'overdue' : 'upcoming');
                                    @endphp
                                    <tr>
                                        <td>{{ $s->installment_no }}</td>
                                        <td>{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}</td>
                                        <td>&#8369;{{ number_format($s->amount_due, 2) }}</td>
                                        <td>&#8369;{{ number_format($s->amount_paid, 2) }}</td>
                                        <td>{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                                        <td>
                                            @if($rowStatus === 'paid')
                                                <span class="sched-status is-paid">Paid</span>
                                            @elseif($rowStatus === 'overdue')
                                                <span class="sched-status is-overdue">Overdue</span>
                                            @else
                                                <span class="sched-status is-upcoming">Upcoming</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-muted empty-note">No payment schedule found for this loan.</p>
                        @endif
                    </div>
                    @if($paymentSchedule->count() > 0)
                    <div class="panel-footer justify-content-center">
                        <span><i class="feather-calendar"></i>{{ $paymentSchedule->count() }} of {{ $totalCount }} payments scheduled</span>
                    </div>
                    @endif
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