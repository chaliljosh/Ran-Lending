@extends('admin.layout')

@section('title', 'Completed Loan Detail')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/avatar-placeholder.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-detail.css') }}" />
    {{-- Standalone overrides for this page only — merges the borrower
         strip + 4 stat cards into one card, and applies the site's
         white-bg/border/shadow/typography language to the Loan Summary,
         Repayment Performance, and Payment Schedule panels without
         touching the shared ongoing-loan-detail.css file. --}}
     <link rel="stylesheet" href="{{ asset('assets/admin/css/completed-loan-detail.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
@endsection

@section('content')

    @php
        $ratingColors = [
            'excellent' => 'is-approved',
            'good'      => 'is-approved',
            'fair'      => 'is-onhold',
            'poor'      => 'is-rejected',
        ];
        $ratingBadgeClass = $ratingColors[$borrowerPerformance['rating']] ?? 'is-approved';
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Completed Loan Detail</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.completed-loans') }}">Completed Loans</a></li>
                <li class="breadcrumb-item">Loan #{{ $applicationId }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('admin.completed-loans') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="main-content cld-page">

        {{-- ============ BORROWER HEADER + STATS (merged) ============ --}}
        <div class="cld-summary-card mb-3">
            <div class="cld-summary-left">
                <div class="cld-avatar js-borrower-avatar" data-initials="">
                    @if($borrowerProfilePicture)
                        <img src="{{ $borrowerProfilePicture }}" alt="{{ $app->first_name }} {{ $app->last_name }}" class="cld-avatar-img">
                    @endif
                </div>
                <div class="cld-borrower-info">
                    <h5>{{ $app->first_name }} {{ $app->last_name }}</h5>
                    <div class="cld-borrower-meta">
                        <span>{{ $app->email }}</span>
                        <span class="cld-dot">&middot;</span>
                        <span>Loan ID: <strong>LN-{{ str_pad($applicationId, 5, '0', STR_PAD_LEFT) }}</strong></span>
                        @if($completedAt)
                            <span class="cld-dot">&middot;</span>
                            <span>Completed on <strong>{{ \Carbon\Carbon::parse($completedAt)->format('M d, Y') }}</strong></span>
                        @endif
                    </div>
                    <span class="badge badge-approved cld-completed-badge">Completed</span>
                </div>
            </div>
            <div class="cld-summary-right">
                <div class="cld-stat">
                    <span class="cld-stat-value">&#8369;{{ number_format($app->total_amount ?? 0, 2) }}</span>
                    <span class="cld-stat-label">Total Loan Amount</span>
                </div>
                <div class="cld-stat">
                    <span class="cld-stat-value">&#8369;{{ number_format($amountCollected, 2) }}</span>
                    <span class="cld-stat-label">Total Amount Repaid</span>
                </div>
                <div class="cld-stat">
                    <span class="cld-stat-value">&#8369;{{ number_format($totalInterest, 2) }}</span>
                    <span class="cld-stat-label">Total Interest Earned</span>
                </div>
                <div class="cld-stat">
                    <span class="cld-stat-value">{{ $paidCount }} / {{ $totalCount }}</span>
                    <span class="cld-stat-label">Installments Paid</span>
                </div>
            </div>
        </div>

        <div class="loan-detail-grid">

            {{-- ============ LOAN SUMMARY ============ --}}
            <div class="panel loan-summary-panel">
                <div class="panel-header">Loan Summary</div>
                <div class="panel-body">

                    <div class="loan-info-grid">
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
                            <label>Total Repaid</label>
                            <span>&#8369;{{ number_format($amountCollected, 2) }}</span>
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
                            <label>Applied On</label>
                            <span>{{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</span>
                        </div>
                    </div>

                    {{-- ============ PAYMENT PROGRESS ROW — fully completed ============ --}}
                    <div class="old-progress-row">
                        <div class="old-progress-main">
                            <p class="lsc-progress-title">Payment Progress</p>
                            <div class="old-progress-count">{{ $paidCount }} / {{ $totalCount }} completed</div>
                            <div class="lsc-progress-bar">
                                <div class="lsc-progress-fill" style="width: 100%;"></div>
                            </div>
                        </div>
                        <div class="old-next-due-box">
                            <span class="old-next-due-label">Status</span>
                            <span class="old-next-due-date">
                                <i class="feather-check-circle" style="color:#16a34a;"></i>
                                <span>Fully Paid</span>
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ============ BORROWER REPAYMENT PERFORMANCE ============ --}}
            <div class="panel loan-review-panel">
                <div class="panel-header">Repayment Performance</div>
                <div class="panel-body">

                    <div class="review-item {{ $ratingBadgeClass }}">
                        <div class="review-avatar {{ $ratingBadgeClass === 'is-rejected' ? '' : 'is-purple' }}">
                            <i class="feather-{{ $borrowerPerformance['rating'] === 'poor' ? 'alert-triangle' : 'thumbs-up' }}"></i>
                        </div>
                        <div class="review-body">
                            <span class="review-name">{{ $borrowerPerformance['label'] }}</span>
                            <span class="review-remark">{{ $borrowerPerformance['description'] }}</span>
                        </div>
                    </div>

                    <div class="loan-info-grid secondary" style="margin-top: 18px;">
                        <div class="info-item2">
                            <label>Total Payments</label>
                            <span>{{ $borrowerPerformance['total_payments'] }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Late Payments</label>
                            <span>{{ $borrowerPerformance['late_payments'] }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Total Penalties Incurred</label>
                            <span>&#8369;{{ number_format($borrowerPerformance['total_penalties'], 2) }}</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- ============ PAYMENT SCHEDULE (read-only, no Record Payment buttons) ============ --}}
        <div class="panel payment-schedule-panel">
            <div class="ols-sched-header">
                <div class="ols-sched-header-left">
                    <div>
                        <div class="ols-sched-title">Payment Schedule</div>
                    </div>
                </div>
                <div class="ols-sched-count-pill">
                    <span><strong>{{ $paidCount }}</strong> of <strong>{{ $totalCount }}</strong> installments paid</span>
                </div>
            </div>

            @if($paymentSchedule->count() > 0)
            <div class="table-responsive">
                <table class="ols-sched-table">
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
                            $wasLate = $s->paid_at && \Carbon\Carbon::parse($s->paid_at)->gt(\Carbon\Carbon::parse($s->due_date));
                        @endphp
                        <tr>
                            <td><span class="ols-row-num">{{ $s->installment_no }}</span></td>
                            <td>
                                <div class="ols-due-date">{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}</div>
                            </td>
                            <td class="ols-amount-due">&#8369;{{ number_format($s->amount_due, 2) }}</td>
                            <td class="ols-amount-paid-cell">
                                <div class="ols-amount-paid-value">&#8369;{{ number_format($s->amount_paid, 2) }}</div>
                            </td>
                            <td>{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                            <td>
                                @if($wasLate)
                                    <span class="ols-status-pill is-overdue"> Paid Late</span>
                                @else
                                    <span class="ols-status-pill is-paid"> Paid On Time</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


            @else
            <div class="panel-body">
                <p class="text-muted empty-note">No payment schedule found for this loan.</p>
            </div>
            @endif
        </div>

    </div>

@endsection