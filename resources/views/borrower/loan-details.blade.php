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

            @php
                $isCompleted = $app->status === 'completed';

                // Loan History tab only appears when the controller passes $loans
                // (all of this borrower's applications). Without it, the page
                // behaves exactly like before — just with the completed-loan fixes.
                $hasHistory = isset($loans) && $loans->count() > 0;

                // True when we're looking at the borrower's most recent loan
                // (so "apply for a new loan" only shows on that one).
                $isLatest = !$hasHistory || $loans->first()->application_id == $app->application_id;

                // status => [badge class, label] for the history table
                $historyStatus = [
                    'pending'   => ['bg-warning',   'Pending'],
                    'on_hold'   => ['bg-danger',    'On Hold'],
                    'approved'  => ['bg-primary',   'Approved'],
                    'active'    => ['bg-primary',   'Active'],
                    'completed' => ['bg-success',   'Completed'],
                    'rejected'  => ['bg-secondary', 'Rejected'],
                    'defaulted' => ['bg-danger',    'Defaulted'],
                ];
            @endphp

            {{-- ============ TABS (only if there is history to show) ============ --}}
            @if($hasHistory)
                <div class="bwl-ld-tabs" role="tablist" aria-label="Loan views" data-ld-tabs>
                    <button type="button" class="bwl-ld-tab is-active" role="tab" id="ldTabDetails"
                        aria-selected="true" aria-controls="ldPanelDetails" data-ld-tab="details">
                        Loan Details
                    </button>
                    <button type="button" class="bwl-ld-tab" role="tab" id="ldTabHistory"
                        aria-selected="false" aria-controls="ldPanelHistory" tabindex="-1" data-ld-tab="history">
                        Loan History
                        <span class="bwl-ld-tab__count">{{ $loans->count() }}</span>
                    </button>
                </div>
            @endif

            {{-- ============ PANEL: LOAN DETAILS ============ --}}
            <div id="ldPanelDetails" role="tabpanel" aria-labelledby="ldTabDetails" data-ld-panel="details">

                {{-- ============ COMPLETED BANNER ============ --}}
                @if($isCompleted && $isLatest)
                    <div class="bwl-ld-banner">
                        <div>
                            <span class="bwl-ld-banner__title">Loan fully paid</span>
                            <p class="bwl-ld-banner__text">
                                You've completed every payment on this loan. Thank you! You may now apply for a new one.
                            </p>
                        </div>
                        <a href="{{ route('borrower.apply-loan') }}" class="bwl-ld-btn">Apply for a new loan</a>
                    </div>
                @endif

                {{-- ============ LOAN SUMMARY ============ --}}
                <div class="bwl-ld-section">
                    <div class="bwl-ld-section__head">
                        <div class="bwl-ld-section__head-text">
                            <span class="bwl-ld-eyebrow">{{ $loanId }}</span>
                            <h5 class="mb-0">Loan Summary</h5>
                        </div>
                        <span class="bwl-ld-status {{ $status['badge'] }}">{{ $status['label'] }}</span>
                    </div>

                    {{-- Row 1 — the loan's terms --}}
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

                    {{-- Row 2 — what it costs and where it stands --}}
                    <div class="bwl-ld-stat-grid bwl-ld-stat-grid--second">
                        <div class="bwl-ld-stat-card">
                            <span class="bwl-ld-stat-label">Total Amount to Pay</span>
                            <div class="bwl-ld-stat-value is-danger">₱{{ number_format($app->total_amount, 2) }}</div>
                            <div class="bwl-ld-info-sub">(Principal + Interest)</div>
                        </div>

                        <div class="bwl-ld-stat-card">
                            <span class="bwl-ld-stat-label">Monthly Payment</span>
                            <div class="bwl-ld-stat-value">₱{{ number_format($app->monthly_payment, 2) }}</div>
                            <div class="bwl-ld-info-sub">Due monthly</div>
                        </div>

                        <div class="bwl-ld-stat-card">
                            <span class="bwl-ld-stat-label">Remaining Balance</span>
                            <div class="bwl-ld-stat-value {{ $isCompleted ? 'is-success' : '' }}">
                                {{ $isCompleted ? 'Fully paid' : '₱' . number_format($remainingBalance, 2) }}
                            </div>
                            <div class="bwl-ld-info-sub">
                                @if($totalCount > 0)
                                    ₱{{ number_format($amountCollected, 2) }} {{ $isCompleted ? 'paid in total' : 'paid so far' }}
                                @else
                                    No payments recorded yet
                                @endif
                            </div>
                        </div>

                        @if($totalCount > 0)
                            <div class="bwl-ld-stat-card">
                                <span class="bwl-ld-stat-label">Next Payment Due</span>
                                @if($nextUnpaid)
                                    @php
                                        $dueCarbon = \Carbon\Carbon::parse($nextUnpaid->due_date);
                                        $daysDiff = now()->startOfDay()->diffInDays($dueCarbon->copy()->startOfDay(), false);
                                    @endphp
                                    <div class="bwl-ld-stat-value bwl-ld-stat-value--sm">{{ $dueCarbon->format('F j, Y') }}</div>
                                    <div class="bwl-ld-info-sub {{ $daysDiff < 0 ? 'is-danger' : '' }}">
                                        {{ $daysDiff < 0
                                            ? abs($daysDiff) . ' ' . (abs($daysDiff) == 1 ? 'day' : 'days') . ' overdue'
                                            : $daysDiff . ' ' . ($daysDiff == 1 ? 'day' : 'days') . ' remaining' }}
                                    </div>
                                @else
                                    <div class="bwl-ld-stat-value bwl-ld-stat-value--sm">—</div>
                                    <div class="bwl-ld-info-sub">Loan fully paid</div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ============ PAYMENT SCHEDULE (full width — handles 3 to 12 rows) ============ --}}
                <div class="bwl-ld-card">
                    <div class="bwl-ld-card-header">
                        <h6>Payment Schedule</h6>
                    </div>
                    <div class="bwl-ld-card-body">
                        @if($isCompleted)
                            <p class="bwl-ld-note">
                                <i class="feather-check-circle me-1"></i>
                                All payments on this loan have been received. This is your final payment record.
                            </p>
                        @elseif($app->status !== 'approved' && $app->status !== 'active')
                            <p class="bwl-ld-note">
                                <i class="feather-info me-1"></i>
                                This is an estimated schedule based on your requested amount and term. It will be finalized once your application is approved.
                            </p>
                        @endif

                        {{-- Progress first, so it's visible even when the table is long --}}
                        @if($totalCount > 0)
                            @php $progressPct = (int) round(($paidCount / $totalCount) * 100); @endphp
                            <div class="bwl-ld-progress">
                                <div class="bwl-ld-progress__head">
                                    <span class="bwl-ld-progress__label">Payment progress</span>
                                    <span class="bwl-ld-progress__value">{{ $paidCount }} of {{ $totalCount }} paid · {{ $progressPct }}%</span>
                                </div>
                                <div class="bwl-ld-progress__track" role="progressbar" aria-label="Payments completed"
                                    aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressPct }}">
                                    <span class="bwl-ld-progress__bar {{ $paidCount === $totalCount ? 'is-complete' : '' }}"
                                        style="width: {{ $progressPct }}%"></span>
                                </div>
                                <p class="bwl-ld-progress__note">
                                    @if($paidCount === $totalCount)
                                        Loan fully paid off!
                                    @elseif($isOverdue)
                                        You have an overdue payment. Please settle it as soon as possible.
                                    @else
                                        You are all set! Keep it up.
                                    @endif
                                </p>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table bwl-ld-table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Due Date</th>
                                        <th>Amount Due</th>
                                        <th>Status</th>
                                        <th>Date Paid</th>
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
                                            <td class="bwl-ld-cell-muted">
                                                {{ !empty($row['paid_at']) ? $row['paid_at']->format('F j, Y') : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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

            </div>{{-- /#ldPanelDetails --}}

            {{-- ============ PANEL: LOAN HISTORY ============ --}}
            @if($hasHistory)
                <div id="ldPanelHistory" role="tabpanel" aria-labelledby="ldTabHistory" data-ld-panel="history" hidden>
                    <div class="bwl-ld-card">
                        <div class="bwl-ld-card-header">
                            <h6>Loan History</h6>
                        </div>
                        <div class="bwl-ld-card-body">
                            <div class="table-responsive">
                                <table class="table bwl-ld-table">
                                    <thead>
                                        <tr>
                                            <th>Loan ID</th>
                                            <th>Amount</th>
                                            <th>Term</th>
                                            <th>Date Applied</th>
                                            <th>Status</th>
                                            <th class="text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($loans as $l)
                                            @php
                                                $ls = $historyStatus[$l->status]
                                                    ?? ['bg-secondary', ucfirst(str_replace('_', ' ', $l->status))];
                                                $isViewing = $l->application_id == $app->application_id;
                                            @endphp
                                            <tr class="bwl-ld-row-link"
                                                @if($isViewing)
                                                    data-ld-goto="details"
                                                @else
                                                    data-href="{{ route('borrower.loan-details', ['loan' => $l->application_id]) }}"
                                                @endif>
                                                <td>LN-{{ str_pad($l->application_id, 6, '0', STR_PAD_LEFT) }}</td>
                                                <td class="bwl-ld-cell-strong">₱{{ number_format($l->loan_amount, 2) }}</td>
                                                <td>{{ (int) $l->loan_term }} Months</td>
                                                <td>{{ \Carbon\Carbon::parse($l->applied_at)->format('M j, Y') }}</td>
                                                <td><span class="bwl-ld-status {{ $ls[0] }}">{{ $ls[1] }}</span></td>
                                                <td class="text-end">
                                                    @if($isViewing)
                                                        <span class="bwl-ld-viewing">Viewing</span>
                                                    @else
                                                        <a class="bwl-ld-link"
                                                            href="{{ route('borrower.loan-details', ['loan' => $l->application_id]) }}">View</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        @endif

    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/loan-details.js') }}"></script>
@endsection