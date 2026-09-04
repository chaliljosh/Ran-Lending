@extends('borrower.layout')

@section('title', 'Loan Details')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/loan-details.css') }}?v={{ time() }}">
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">View Loan Details</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Loan Details</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

        @if(!$app)

            {{-- No loan application yet --}}
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="bwl-ld-empty">
                        <div class="bwl-ld-empty-icon">
                            <i class="feather-file-text"></i>
                        </div>
                        <h5 class="fw-bold mb-2">No Loan Application Yet</h5>
                        <p class="text-muted mb-4">You haven't submitted a loan application. Apply now to get started.</p>
                        <a href="{{ route('borrower.apply-loan') }}" class="btn btn-primary px-4">
                            <i class="feather-file-plus me-2"></i>Apply for a Loan
                        </a>
                    </div>
                </div>
            </div>

        @else

            {{-- ============ LOAN SUMMARY ============ --}}
            <div class="bwl-ld-section">
                <div class="bwl-ld-section__head">
                    <div class="bwl-ld-section__head-text">
                        <span class="bwl-ld-eyebrow">{{ $loanId }}</span>
                        <h5 class="mb-0">Loan Summary</h5>
                    </div>
                    <span class="bwl-ld-status {{ $status['badge'] }}">{{ $status['label'] }}</span>
                </div>

                <div class="bwl-ld-stat-grid">
                    <div class="bwl-ld-stat-card">
                        <span class="bwl-ld-stat-label">Loan Amount</span>
                        <div class="bwl-ld-stat-value">₱{{ number_format($app->loan_amount, 2) }}</div>
                    </div>
                    <div class="bwl-ld-stat-card">
                        <span class="bwl-ld-stat-label">Loan Term</span>
                        <div class="bwl-ld-stat-value">{{ (int) $app->loan_term }} Months</div>
                    </div>
                    <div class="bwl-ld-stat-card">
                        <span class="bwl-ld-stat-label">Interest Rate</span>
                        <div class="bwl-ld-stat-value">{{ number_format($app->interest_rate, 2) }}% Monthly</div>
                    </div>
                    <div class="bwl-ld-stat-card">
                        <span class="bwl-ld-stat-label">Application Date</span>
                        <div class="bwl-ld-stat-value bwl-ld-stat-value--sm">{{ \Carbon\Carbon::parse($app->applied_at)->format('F j, Y') }}</div>
                    </div>
                </div>
            </div>

            {{-- ============ PAYMENT SCHEDULE + LOAN INFORMATION ============ --}}
            <div class="bwl-ld-row">

                <div class="bwl-ld-card">
                    <div class="bwl-ld-card-header">
                        <h6>Payment Schedule</h6>
                    </div>
                    <div class="bwl-ld-card-body">
                        @if($app->status !== 'approved' && $app->status !== 'active')
                            <p class="bwl-ld-note">
                                <i class="feather-info me-1"></i>
                                This is an estimated schedule based on your requested amount and term. It will be finalized once your application is approved.
                            </p>
                        @endif
                        <div class="table-responsive">
                            <table class="table bwl-ld-table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Due Date</th>
                                        <th>Amount Due</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentSchedule as $row)
                                        <tr>
                                            <td>Month {{ $row['month'] }}</td>
                                            <td>{{ $row['due']->format('F j, Y') }}</td>
                                            <td>₱{{ number_format($row['amount'], 2) }}</td>
                                            <td>
                                                @if($row['status'] === 'paid')
                                                    <span class="bwl-ld-status bg-success">Paid</span>
                                                @elseif($row['status'] === 'overdue')
                                                    <span class="bwl-ld-status bg-danger">Overdue</span>
                                                @else
                                                    <span class="bwl-ld-status bg-secondary">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bwl-ld-card">
                    <div class="bwl-ld-card-header">
                        <h6>Loan Information</h6>
                    </div>
                    <div class="bwl-ld-card-body">
                        <div class="bwl-ld-info-row">
                            <span class="bwl-ld-info-label">Total Amount to Pay</span>
                            <div class="bwl-ld-info-value is-danger">₱{{ number_format($app->total_amount, 2) }}</div>
                            <div class="bwl-ld-info-sub">(Principal + Interest)</div>
                        </div>
                        <div class="bwl-ld-info-row">
                            <span class="bwl-ld-info-label">Monthly Payment</span>
                            <div class="bwl-ld-info-value bwl-ld-info-value--sm">₱{{ number_format($app->monthly_payment, 2) }}</div>
                            <div class="bwl-ld-info-sub">Due every 15th of the month</div>
                        </div>
                        <div class="bwl-ld-info-row">
                            <span class="bwl-ld-info-label">Remaining Balance</span>
                            <div class="bwl-ld-info-value bwl-ld-info-value--sm">₱{{ number_format($remainingBalance, 2) }}</div>
                            <div class="bwl-ld-info-sub">
                                @if($totalCount > 0)
                                    ₱{{ number_format($amountCollected, 2) }} paid so far
                                @else
                                    No payments recorded yet
                                @endif
                            </div>
                        </div>

                        @if($totalCount > 0)
                            <div class="bwl-ld-info-row">
                                <span class="bwl-ld-info-label">Payments Made</span>
                                <div class="bwl-ld-info-value bwl-ld-info-value--sm">{{ $paidCount }} / {{ $totalCount }}</div>
                                <div class="bwl-ld-info-sub">
                                    @if($paidCount === $totalCount)
                                        Loan fully paid off!
                                    @else
                                        You are all set! Keep it up.
                                    @endif
                                </div>
                            </div>

                            <div class="bwl-ld-info-row">
                                <span class="bwl-ld-info-label">Next Payment Due</span>
                                @if($nextUnpaid)
                                    @php
                                        $dueCarbon = \Carbon\Carbon::parse($nextUnpaid->due_date);
                                        $daysDiff = now()->startOfDay()->diffInDays($dueCarbon->copy()->startOfDay(), false);
                                    @endphp
                                    <div class="bwl-ld-info-value bwl-ld-info-value--sm">{{ $dueCarbon->format('F j, Y') }}</div>
                                    <div class="bwl-ld-info-sub {{ $daysDiff < 0 ? 'is-danger' : '' }}">
                                        {{ $daysDiff < 0
                                            ? abs($daysDiff) . ' ' . (abs($daysDiff) == 1 ? 'day' : 'days') . ' overdue'
                                            : $daysDiff . ' ' . ($daysDiff == 1 ? 'day' : 'days') . ' remaining' }}
                                    </div>
                                @else
                                    <div class="bwl-ld-info-value bwl-ld-info-value--sm">—</div>
                                    <div class="bwl-ld-info-sub">Loan fully paid</div>
                                @endif
                            </div>
                        @endif

                        
                    </div>
                </div>

            </div>

            {{-- ============ APPLICATION TIMELINE ============ --}}
            <div class="bwl-ld-card">
                <div class="bwl-ld-card-header">
                    <h6>Application Timeline</h6>
                </div>
                <div class="bwl-ld-timeline-list">
                    <div class="bwl-ld-timeline-item is-done">
                        <span class="bwl-ld-timeline-dot"><i class="feather-check"></i></span>
                        <div class="bwl-ld-timeline-body">
                            <div class="bwl-ld-timeline-title">Application Submitted</div>
                            <div class="bwl-ld-timeline-date">{{ \Carbon\Carbon::parse($app->applied_at)->format('F j, Y g:i A') }}</div>
                        </div>
                    </div>
                    <div class="bwl-ld-timeline-item {{ in_array($app->status, ['approved', 'active', 'completed']) ? 'is-done' : 'is-upcoming' }}">
                        <span class="bwl-ld-timeline-dot"><i class="{{ $status['icon'] }}"></i></span>
                        <div class="bwl-ld-timeline-body">
                            <div class="bwl-ld-timeline-title">Current Status: {{ $status['label'] }}</div>
                            <div class="bwl-ld-timeline-date">
                                @if($app->status === 'pending')
                                    Your application is being reviewed by our admins.
                                @elseif($app->status === 'approved' || $app->status === 'active')
                                    Your loan has been approved.
                                @elseif($app->status === 'rejected')
                                    Your application was not approved.
                                @elseif($app->status === 'on_hold')
                                    Your application is on hold pending additional review.
                                @elseif($app->status === 'completed')
                                    Your loan has been fully paid off.
                                @elseif($app->status === 'defaulted')
                                    Your loan has been marked as defaulted.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @endif

    </div>

@endsection