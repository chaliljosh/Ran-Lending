@extends('borrower.layout')

@section('title', 'Dashboard')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-dashboard.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Dashboard</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Dashboard</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

        @if($detailsStatus === 'incomplete')
        <div class="bwl-status-panel">
            <div class="bwl-status-head">
                <div class="bwl-status-icon is-blue"><i class="feather-user-check"></i></div>
                <div class="bwl-status-head-text">
                    <h5 class="bwl-status-title">Complete Your Profile</h5>
                    <p class="bwl-status-desc">Please fill up your personal details first before you can access the full dashboard and apply for a loan.</p>
                </div>
            </div>
            <div class="bwl-status-divider"></div>
            <a href="{{ route('borrower.personal-details') }}" class="bwl-status-btn is-primary">
                <i class="feather-edit me-2"></i>Fill Up Personal Details
            </a>
        </div>

        @elseif($detailsStatus === 'pending')
        <div class="bwl-status-panel">
            <div class="bwl-status-head">
                <div class="bwl-status-icon is-orange"><i class="feather-clock"></i></div>
                <div class="bwl-status-head-text">
                    <h5 class="bwl-status-title">Under Review</h5>
                    <p class="bwl-status-desc">Your personal details have been submitted and are currently being reviewed by our admins. Please wait for approval.</p>
                </div>
            </div>
            <div class="bwl-status-divider"></div>
            <div class="bwl-status-stat">
                <div class="bwl-status-stat-icon"><i class="feather-file-text"></i></div>
                <div>
                    <div class="bwl-status-stat-label">Admin Approvals</div>
                    <div class="bwl-status-stat-value">{{ $approvedCount }} / 3</div>
                </div>
            </div>
            <p class="bwl-status-footnote">You will be notified once your account is fully approved.</p>
        </div>

        @elseif($detailsStatus === 'rejected')
        <div class="bwl-status-panel is-danger">
            <div class="bwl-status-head">
                <div class="bwl-status-icon is-red"><i class="feather-x-circle"></i></div>
                <div class="bwl-status-head-text">
                    <h5 class="bwl-status-title">Application Rejected</h5>
                    <p class="bwl-status-desc">Unfortunately, your personal details were not approved. Please update your information and resubmit.</p>
                </div>
            </div>
            <div class="bwl-status-divider"></div>
            <a href="{{ route('borrower.personal-details') }}" class="bwl-status-btn is-danger">
                <i class="feather-edit me-2"></i>Update Personal Details
            </a>
        </div>

        @elseif(!$app)
        {{-- Fully approved, pero walang active loan schedule pa (walang
             application, o may application pero pending/on_hold/rejected/
             approved-but-not-activated-yet). Panatilihin simple. --}}
        <div class="row">
            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex gap-4 align-items-center mb-4">
                            <div class="avatar-text avatar-lg bg-gray-200"><i class="feather-file-text"></i></div>
                            <div><span class="fs-13 fw-semibold text-muted">Loan Application</span><h3>{{ $loanStatus['label'] }}</h3></div>
                        </div>
                        <div class="progress ht-3">
                            <div class="progress-bar {{ $loanStatus['bar'] }}" style="width:{{ $loanStatus['progress'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-9">
                <div class="card stretch stretch-full">
                    <div class="card-header"><h5 class="card-title">Application Timeline</h5></div>
                    <div class="card-body fs-13">
                        @if(!$latestLoan)
                            <p class="text-muted mb-0">You haven't applied for a loan yet.</p>
                        @else
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="feather-check-circle text-success me-2"></i>
                                    Application Submitted —
                                    <span class="text-muted">{{ \Carbon\Carbon::parse($latestLoan->applied_at)->format('F j, Y') }}</span>
                                </li>
                                <li class="mb-0">
                                    <i class="feather-info me-2"></i>
                                    Current Status: <strong>{{ $loanStatus['label'] }}</strong>
                                </li>
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @else
        {{-- ============ FULL REDESIGNED DASHBOARD (may active loan) ============ --}}

        {{-- ============ TOP STAT WIDGETS ============ --}}
        {{-- Icon ngayon nasa top-right ng bawat widget, kagaya ng
             reference screenshot (label left, icon right, value below). --}}
        <div class="bwl-dash-widgets">
            <div class="bwl-dash-widget">
                <div class="bwl-dash-widget-top">
                    <div class="bwl-dash-widget-label">Outstanding Balance</div>
                    <div class="bwl-dash-widget-icon"><i class="feather-credit-card"></i></div>
                </div>
                <div class="bwl-dash-widget-value">₱{{ number_format($outstandingAmount, 2) }}</div>
                <div class="bwl-dash-widget-sub">Loan #{{ str_pad($app->application_id, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="bwl-dash-progress-track">
                    <div class="bwl-dash-progress-fill" style="width:{{ $progressPct }}%"></div>
                </div>
            </div>

            <div class="bwl-dash-widget">
                <div class="bwl-dash-widget-top">
                    <div class="bwl-dash-widget-label">Next Payment</div>
                    <div class="bwl-dash-widget-icon"><i class="feather-calendar"></i></div>
                </div>
                <div class="bwl-dash-widget-value">
                    {{ $nextUnpaid ? '₱' . number_format($nextUnpaid->amount_due, 2) : '—' }}
                </div>
                @if($nextUnpaid)
                    @php
                        $dueCarbon = \Carbon\Carbon::parse($nextUnpaid->due_date);
                        $daysDiff = now()->startOfDay()->diffInDays($dueCarbon->copy()->startOfDay(), false);
                    @endphp
                    <div class="bwl-dash-widget-sub">Due on {{ $dueCarbon->format('M d, Y') }}</div>
                    <span class="bwl-dash-pill {{ $daysDiff < 0 ? 'is-danger' : 'is-info' }}">
                        {{ $daysDiff < 0 ? abs($daysDiff) . ' ' . (abs($daysDiff) == 1 ? 'day' : 'days') . ' overdue' : $daysDiff . ' ' . ($daysDiff == 1 ? 'day' : 'days') . ' remaining' }}
                    </span>
                @else
                    <div class="bwl-dash-widget-sub">Loan fully paid</div>
                @endif
            </div>

            <div class="bwl-dash-widget">
                <div class="bwl-dash-widget-top">
                    <div class="bwl-dash-widget-label">Loan Progress</div>
                    <div class="bwl-dash-widget-icon"><i class="feather-trending-up"></i></div>
                </div>
                <div class="bwl-dash-widget-value fs-16">{{ $paidCount }} of {{ $totalCount }} payments</div>
                <div class="bwl-dash-widget-sub">{{ $progressPct }}% completed</div>
            </div>

            <div class="bwl-dash-widget">
                <div class="bwl-dash-widget-top">
                    <div class="bwl-dash-widget-label">Payment Status</div>
                    <div class="bwl-dash-widget-icon {{ $isOverdue ? 'is-red' : 'is-green' }}">
                        <i class="feather-{{ $isOverdue ? 'alert-circle' : 'check-circle' }}"></i>
                    </div>
                </div>
                <div class="bwl-dash-widget-value {{ $isOverdue ? 'text-danger' : 'text-success' }}">
                    {{ $isOverdue ? 'Overdue' : 'Up to date' }}
                </div>
                <div class="bwl-dash-widget-sub">
                    {{ $isOverdue ? $daysOverdue . ' ' . ($daysOverdue == 1 ? 'day' : 'days') . ' overdue' : 'No overdue payment' }}
                </div>
            </div>
        </div>

        {{-- ============ CURRENT LOAN OVERVIEW + PAYMENT CALENDAR ============ --}}
        <div class="bwl-dash-row bwl-dash-row-cal">
            <div class="bwl-dash-card bwl-dash-overview">
                <div class="bwl-dash-card-header">
                    <h6>Current Loan Overview</h6>
                </div>
                <div class="bwl-dash-overview-body">
                    <div class="bwl-dash-overview-list">
                        <div class="bwl-dash-overview-row">
                            <span>Original Amount</span>
                            <strong>₱{{ number_format($app->loan_amount, 2) }}</strong>
                        </div>
                        <div class="bwl-dash-overview-row">
                            <span>Outstanding Balance</span>
                            <strong>₱{{ number_format($outstandingAmount, 2) }}</strong>
                        </div>
                        <div class="bwl-dash-overview-row">
                            <span>Monthly Payment</span>
                            <strong>₱{{ number_format($totalCount > 0 ? $app->total_amount / $totalCount : 0, 2) }}</strong>
                        </div>
                        <div class="bwl-dash-overview-row">
                            <span>Interest Rate</span>
                            <strong>{{ number_format($app->interest_rate, 0) }}% monthly</strong>
                        </div>
                        <div class="bwl-dash-overview-row">
                            <span>Loan Term</span>
                            <strong>{{ $app->loan_term }} months</strong>
                        </div>
                        <div class="bwl-dash-overview-row">
                            <span>Payments Made</span>
                            <strong>{{ $paidCount }} / {{ $totalCount }}</strong>
                        </div>
                        <div class="bwl-dash-overview-row">
                            <span>Next Due Date</span>
                            <strong>{{ $nextUnpaid ? \Carbon\Carbon::parse($nextUnpaid->due_date)->format('M d, Y') : '—' }}</strong>
                        </div>
                    </div>

                    <div class="bwl-dash-donut-wrap">
                        <div class="bwl-dash-progress-title">Loan Progress</div>
                        <div class="bwl-dash-progress-sub">{{ $paidCount }} of {{ $totalCount }} payments completed</div>
                        <div class="bwl-dash-donut" style="--pct: {{ $progressPct }};">
                            <div class="bwl-dash-donut-inner">
                                <span class="bwl-dash-donut-pct">{{ $progressPct }}%</span>
                            </div>
                        </div>
                        <div class="bwl-dash-progress-sub">
                            @if($activatedAt)
                                Started on {{ \Carbon\Carbon::parse($activatedAt)->format('M d, Y') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="bwl-dash-card bwl-dash-calendar" id="bwl-dash-calendar-card"
                 data-calendar-url="{{ route('borrower.dashboard.calendar') }}">
                <div class="bwl-dash-card-header">
                    <h6>Payment Calendar</h6>
                </div>
                <div id="bwl-cal-content">
                    @include('borrower.partials.payment-calendar')
                </div>
            </div>
        </div>

        {{-- ============ APPLICATION TIMELINE + RECENT PAYMENTS ============ --}}
        <div class="bwl-dash-row">
            <div class="bwl-dash-card bwl-dash-timeline">
                <div class="bwl-dash-card-header"><h6>Application Timeline</h6></div>
                <div class="bwl-dash-timeline-list">
                    <div class="bwl-dash-timeline-item is-done">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Application Submitted</div>
                            <div class="bwl-dash-timeline-date">{{ \Carbon\Carbon::parse($app->applied_at)->format('F j, Y') }}</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-done">Completed</span>
                    </div>

                    @if($approvedAt)
                    <div class="bwl-dash-timeline-item is-done">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Application Approved</div>
                            <div class="bwl-dash-timeline-date">{{ \Carbon\Carbon::parse($approvedAt)->format('F j, Y') }}</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-done">Completed</span>
                    </div>
                    @endif

                    @if($activatedAt)
                    <div class="bwl-dash-timeline-item is-done">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Loan Activated</div>
                            <div class="bwl-dash-timeline-date">{{ \Carbon\Carbon::parse($activatedAt)->format('F j, Y') }}</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-done">Completed</span>
                    </div>
                    @endif

                    @if($nextUnpaid)
                    <div class="bwl-dash-timeline-item is-upcoming">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Next Payment</div>
                            <div class="bwl-dash-timeline-date">{{ \Carbon\Carbon::parse($nextUnpaid->due_date)->format('F j, Y') }}</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-upcoming">Upcoming</span>
                    </div>
                    @else
                    <div class="bwl-dash-timeline-item is-done">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Loan Fully Paid</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-done">Completed</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="bwl-dash-card bwl-dash-payments">
                <div class="bwl-dash-card-header">
                    <h6>Recent Payments</h6>
                    <a href="{{ route('borrower.loan-details') }}">View All →</a>
                </div>
                <div class="bwl-dash-payment-list">
                    @forelse($recentPayments as $p)
                        <div class="bwl-dash-payment-item">
                            <div class="bwl-dash-payment-body">
                                <div class="bwl-dash-payment-title">Installment #{{ $p->installment_no }}</div>
                                <div class="bwl-dash-payment-date">{{ \Carbon\Carbon::parse($p->paid_at)->format('F j, Y') }}</div>
                            </div>
                            <span class="bwl-dash-payment-amount">₱{{ number_format($p->amount_paid, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0 p-3">No payments recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @endif

        @section('page-scripts')
         <script src="{{ asset('functions/borrower-dashboard-calendar.js') }}"></script>
            <script src="{{ asset('functions/borrower-header-notification.js') }}?v={{ time() }}"></script>
        @endsection

    </div>
@endsection