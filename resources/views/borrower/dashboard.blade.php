@extends('borrower.layout')

@section('title', 'Dashboard')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-onboarding.css') }}">
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Dashboard</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Dashboard</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

@if ($detailsStatus === 'incomplete')
    {{-- Bagong onboarding card — nasa borrower/partials/onboarding.blade.php --}}
    @include('borrower.partials.onboarding')

@elseif ($isOnHold)
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <section class="bwl-hold" aria-labelledby="bwlHoldTitle">

                <span class="bwl-hold-chip">
                    <span class="bwl-hold-chip-dot" aria-hidden="true"></span>
                    On hold
                </span>

                <h5 class="bwl-hold-title" id="bwlHoldTitle">Your submission needs an update</h5>
                <p class="bwl-hold-lede">
                    {{ $approvedCount }} of 3 reviewers approved it.
                    {{ $rejectedCount === 1 ? 'One asked' : $rejectedCount . ' asked' }} for changes.
                    Fix the points below and resubmit to continue.
                </p>

                {{-- Admin decisions: unang makikita ng user --}}
                <div class="bwl-hold-tally" role="group"
                     aria-label="{{ $approvedCount }} approved, {{ $rejectedCount }} rejected, out of 3 reviewers">
                    <div class="bwl-hold-tally-item is-approved">
                        <span class="bwl-hold-tally-num">{{ $approvedCount }}</span>
                        <span class="bwl-hold-tally-label">Approved</span>
                    </div>
                    <div class="bwl-hold-tally-item is-rejected">
                        <span class="bwl-hold-tally-num">{{ $rejectedCount }}</span>
                        <span class="bwl-hold-tally-label">Rejected</span>
                    </div>
                </div>

                {{-- Requested changes: focal point ng card --}}
                <div class="bwl-hold-fix">
                    <div class="bwl-hold-fix-head">
                        <h6 class="bwl-hold-fix-title">Requested changes</h6>
                        @if ($rejectionRemarks->isNotEmpty())
                            <span class="bwl-hold-fix-count">
                                {{ $rejectionRemarks->count() }}
                                {{ \Illuminate\Support\Str::plural('item', $rejectionRemarks->count()) }}
                            </span>
                        @endif
                    </div>

                    @if ($rejectionRemarks->isNotEmpty())
                        <ol class="bwl-hold-list">
                            @foreach ($rejectionRemarks as $remark)
                                <li class="bwl-hold-item">
                                    <span class="bwl-hold-item-num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="bwl-hold-item-text">{{ $remark }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="bwl-hold-empty">
                            The reviewer didn't leave a note. Double-check your ID photos and personal details,
                            then resubmit.
                        </p>
                    @endif
                </div>

                <div class="bwl-hold-actions">
                    <a href="{{ route('borrower.personal-details') }}" class="bwl-hold-btn">Update my details</a>
                    <p class="bwl-hold-note">Resubmitting sends it back to all 3 reviewers.</p>
                </div>

            </section>
        </div>
    </div>
@elseif ($detailsStatus === 'rejected')
    {{-- Explicit terminal rejection — same "bwl-hold" component as on_hold above,
         red variant via bwl-hold--rejected / bwl-hold-chip--rejected modifiers.
         No approved/rejected tally here since this is a clean 3-reject outcome,
         not a mixed on_hold decision. --}}
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <section class="bwl-hold bwl-hold--rejected" aria-labelledby="bwlRejectedTitle">

                <span class="bwl-hold-chip bwl-hold-chip--rejected">
                    <span class="bwl-hold-chip-dot" aria-hidden="true"></span>
                    Rejected
                </span>

                <h5 class="bwl-hold-title" id="bwlRejectedTitle">Your submission was not approved</h5>
                <p class="bwl-hold-lede">
                    Your personal details were reviewed and rejected. Fix the points below and resubmit for
                    another review.
                </p>

                <div class="bwl-hold-fix">
                    <div class="bwl-hold-fix-head">
                        <h6 class="bwl-hold-fix-title">Requested changes</h6>
                        @if ($rejectionRemarks->isNotEmpty())
                            <span class="bwl-hold-fix-count">
                                {{ $rejectionRemarks->count() }}
                                {{ \Illuminate\Support\Str::plural('item', $rejectionRemarks->count()) }}
                            </span>
                        @endif
                    </div>

                    @if ($rejectionRemarks->isNotEmpty())
                        <ol class="bwl-hold-list">
                            @foreach ($rejectionRemarks as $remark)
                                <li class="bwl-hold-item">
                                    <span class="bwl-hold-item-num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="bwl-hold-item-text">{{ $remark }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="bwl-hold-empty">
                            The reviewer didn't leave a note. Double-check your ID photos and personal details,
                            then resubmit.
                        </p>
                    @endif
                </div>

                <div class="bwl-hold-actions">
                    <a href="{{ route('borrower.personal-details') }}" class="bwl-hold-btn bwl-hold-btn--rejected">Update my details</a>
                    <p class="bwl-hold-note">Resubmitting sends it back to all 3 reviewers.</p>
                </div>

            </section>
        </div>
    </div>
@elseif($detailsStatus === 'pending')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bwl-tracker-card">
                <p class="bwl-tracker-eyebrow">Personal Details</p>

                <div class="bwl-tracker-steps">
                    <div class="bwl-tracker-step is-done">
                        <span class="bwl-tracker-dot">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="bwl-tracker-label">Details Submitted</span>
                    </div>

                    <div class="bwl-tracker-line is-done"></div>

                    <div class="bwl-tracker-step is-current">
                        <span class="bwl-tracker-dot">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </span>
                        <span class="bwl-tracker-label">Admin Review</span>
                    </div>

                    <div class="bwl-tracker-line"></div>

                    <div class="bwl-tracker-step">
                        <span class="bwl-tracker-dot"></span>
                        <span class="bwl-tracker-label">Approved</span>
                    </div>
                </div>

                <div class="bwl-tracker-summary">
                    {{ $approvedCount }} / 3 admin approvals
                </div>

                <p class="bwl-tracker-message">
                    Your personal details have been submitted and are currently being reviewed by our admins.
                    You will be notified once your account is fully approved.
                </p>
            </div>
        </div>
    </div>
@elseif(!$app)
    @php
        $isRejectedLoan  = $latestLoan && $latestLoan->status === 'rejected';
        $isApprovedLoan  = $latestLoan && $latestLoan->status === 'approved';
        $isCompletedLoan = $latestLoan && $latestLoan->status === 'completed';
    @endphp
    <div class="row justify-content-center">
        <div class="col-lg-8">

            @if (!$latestLoan)
                <div class="bwl-tracker-card bwl-tracker-empty">
                    <p class="bwl-tracker-eyebrow">Loan Application</p>
                    <h5 class="bwl-tracker-empty-title">No Active Application</h5>
                    <p class="bwl-tracker-message">You haven't applied for a loan yet.</p>
                    <a href="{{ route('borrower.apply-loan') }}" class="bwl-tracker-link">
                        Apply for a Loan
                    </a>
                </div>
            @elseif ($isCompletedLoan)
                <div class="bwl-tracker-card">
                    <p class="bwl-tracker-eyebrow">Loan Application</p>

                    <div class="bwl-tracker-steps">
                        <div class="bwl-tracker-step is-done">
                            <span class="bwl-tracker-dot">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span class="bwl-tracker-label">Application Submitted</span>
                        </div>

                        <div class="bwl-tracker-line is-done"></div>

                        <div class="bwl-tracker-step is-done">
                            <span class="bwl-tracker-dot">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span class="bwl-tracker-label">Loan Activated</span>
                        </div>

                        <div class="bwl-tracker-line is-done"></div>

                        <div class="bwl-tracker-step is-done">
                            <span class="bwl-tracker-dot">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span class="bwl-tracker-label">Fully Paid</span>
                        </div>
                    </div>

                    <div class="bwl-tracker-summary">
                        Application #{{ $latestLoan->application_id }}
                        <span class="bwl-tracker-dot-sep">&middot;</span>
                        ₱{{ number_format($latestLoan->loan_amount, 2) }}
                        <span class="bwl-tracker-dot-sep">&middot;</span>
                        Submitted {{ \Carbon\Carbon::parse($latestLoan->applied_at)->format('M j, Y') }}
                    </div>

                    <p class="bwl-tracker-message">
                        Congratulations! Your loan has been fully repaid. You're now eligible to apply for a new loan.
                    </p>

                    <a href="{{ route('borrower.apply-loan') }}" class="bwl-tracker-link">
                        Apply for a New Loan
                    </a>
                </div>
            @else
                <div class="bwl-tracker-card">
                    <p class="bwl-tracker-eyebrow">Loan Application</p>

                    <div class="bwl-tracker-steps">
                        <div class="bwl-tracker-step is-done">
                            <span class="bwl-tracker-dot">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span class="bwl-tracker-label">Application Submitted</span>
                        </div>

                        <div class="bwl-tracker-line is-done"></div>

                        <div
                            class="bwl-tracker-step {{ $isRejectedLoan ? 'is-done' : ($isApprovedLoan ? 'is-done' : 'is-current') }}">
                            <span class="bwl-tracker-dot">
                                @if ($isRejectedLoan || $isApprovedLoan)
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                @else
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                @endif
                            </span>
                            <span class="bwl-tracker-label">Under Review</span>
                        </div>

                        <div
                            class="bwl-tracker-line {{ $isRejectedLoan ? 'is-rejected' : ($isApprovedLoan ? 'is-done' : '') }}">
                        </div>

                        <div
                            class="bwl-tracker-step {{ $isRejectedLoan ? 'is-rejected' : ($isApprovedLoan ? 'is-done' : '') }}">
                            <span class="bwl-tracker-dot">
                                @if ($isApprovedLoan)
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                @elseif ($isRejectedLoan)
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18">
                                        </line>
                                        <line x1="6" y1="6" x2="18" y2="18">
                                        </line>
                                    </svg>
                                @endif
                            </span>
                            <span class="bwl-tracker-label">
                                {{ $isRejectedLoan ? 'Rejected' : ($isApprovedLoan ? 'Approved' : 'Decision') }}
                            </span>
                        </div>
                    </div>

                    <div class="bwl-tracker-summary">
                        Application #{{ $latestLoan->application_id }}
                        <span class="bwl-tracker-dot-sep">&middot;</span>
                        ₱{{ number_format($latestLoan->loan_amount, 2) }}
                        <span class="bwl-tracker-dot-sep">&middot;</span>
                        Submitted {{ \Carbon\Carbon::parse($latestLoan->applied_at)->format('M j, Y') }}
                    </div>

                    <p class="bwl-tracker-message">
                        @if ($isRejectedLoan)
                            Your loan application was not approved. You may review your application details or apply
                            again when eligible.
                        @elseif ($isApprovedLoan)
                            Your application has been approved. Your loan will be activated shortly.
                        @else
                            Your application is currently being reviewed. We'll notify you once a decision has been
                            made.
                        @endif
                    </p>

                    @if ($isRejectedLoan)
                        <a href="{{ route('borrower.apply-loan') }}" class="bwl-tracker-link">
                            Apply Again </a>
                    @else
                        <a href="{{ route('borrower.loan-details') }}" class="bwl-tracker-link">
                            View Application Status
                        </a>
                    @endif
                </div>
            @endif

        </div>
    </div>
@else
    {{-- ============ FULL REDESIGNED DASHBOARD (may active loan) ============ --}}

    {{-- ============ TOP STAT WIDGETS ============ --}}

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
            @if ($nextUnpaid)
                @php
                    $dueCarbon = \Carbon\Carbon::parse($nextUnpaid->due_date);
                    $daysDiff = now()
                        ->startOfDay()
                        ->diffInDays($dueCarbon->copy()->startOfDay(), false);
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
                <div class="bwl-dash-widget-label">Total Amount Paid</div>
                <div class="bwl-dash-widget-icon"><i class="feather-check-circle"></i></div>
            </div>
            <div class="bwl-dash-widget-value">₱{{ number_format($amountPaid, 2) }}</div>
            <div class="bwl-dash-widget-sub">{{ $paidCount }} of {{ $totalCount }} payments</div>
            <div class="bwl-dash-progress-track">
                <div class="bwl-dash-progress-fill" style="width:{{ $progressPct }}%"></div>
            </div>
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
                    <div class="bwl-dash-progress-sub">{{ $paidCount }} of {{ $totalCount }} payments
                        completed</div>
                    <div class="bwl-dash-donut" style="--pct: {{ $progressPct }};">
                        <div class="bwl-dash-donut-inner">
                            <span class="bwl-dash-donut-pct">{{ $progressPct }}%</span>
                        </div>
                    </div>
                    <div class="bwl-dash-progress-sub">
                        @if ($activatedAt)
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
            <div class="bwl-dash-card-header">
                <h6>Application Timeline</h6>
            </div>
            <div class="bwl-dash-timeline-list">
                <div class="bwl-dash-timeline-item is-done">
                    <span class="bwl-dash-timeline-dot"></span>
                    <div class="bwl-dash-timeline-body">
                        <div class="bwl-dash-timeline-title">Application Submitted</div>
                        <div class="bwl-dash-timeline-date">
                            {{ \Carbon\Carbon::parse($app->applied_at)->format('F j, Y') }}</div>
                    </div>
                    <span class="bwl-dash-timeline-badge is-done">Completed</span>
                </div>

                @if ($approvedAt)
                    <div class="bwl-dash-timeline-item is-done">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Application Approved</div>
                            <div class="bwl-dash-timeline-date">
                                {{ \Carbon\Carbon::parse($approvedAt)->format('F j, Y') }}</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-done">Completed</span>
                    </div>
                @endif

                @if ($activatedAt)
                    <div class="bwl-dash-timeline-item is-done">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Loan Activated</div>
                            <div class="bwl-dash-timeline-date">
                                {{ \Carbon\Carbon::parse($activatedAt)->format('F j, Y') }}</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-done">Completed</span>
                    </div>
                @endif

                @if ($nextUnpaid)
                    <div class="bwl-dash-timeline-item is-upcoming">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Next Payment</div>
                            <div class="bwl-dash-timeline-date">
                                {{ \Carbon\Carbon::parse($nextUnpaid->due_date)->format('F j, Y') }}</div>
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
                            <div class="bwl-dash-payment-date">
                                Due {{ \Carbon\Carbon::parse($p->due_date)->format('M j, Y') }}
                                &middot; Paid {{ \Carbon\Carbon::parse($p->paid_at)->format('M j, Y') }}
                            </div>
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
            <script src="{{ asset('functions/borrower-dashboard.js') }}"></script>
        @endsection

    </div>
@endsection