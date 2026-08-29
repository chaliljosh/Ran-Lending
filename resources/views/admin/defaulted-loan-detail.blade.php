@extends('admin.layout')

@section('title', 'Defaulted Loan Detail')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-detail.css') }}" />
@endsection

@section('content')

    @php
        $initials = strtoupper(substr($app->first_name ?? 'B', 0, 1) . substr($app->last_name ?? 'R', 0, 1));
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Defaulted Loan Detail</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.defaulted-loans') }}">Defaulted Loans</a></li>
                <li class="breadcrumb-item">Loan #{{ $applicationId }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('admin.defaulted-loans') }}" class="btn btn-light"><i class="feather-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="main-content">

        {{-- ============ BORROWER STRIP ============ --}}
        <div class="borrower-strip">
            <div class="bs-left">
                <div class="loan-avatar" style="background: linear-gradient(135deg, #dc2626, #f87171);">{{ $initials }}</div>
                <div class="bs-info">
                    <h5>{{ $app->first_name }} {{ $app->last_name }}</h5>
                    <div class="bs-meta">
                        <span>{{ $app->email }}</span>
                        <span class="bs-dot">&middot;</span>
                        <span>Loan ID: <strong>LN-{{ str_pad($applicationId, 5, '0', STR_PAD_LEFT) }}</strong></span>
                        <span class="bs-dot">&middot;</span>
                        <span id="dlDaysOverdueMeta">{{ $daysLate }} days overdue</span>
                    </div>
                </div>
            </div>
            <div class="bs-right">
                <span class="badge badge-rejected">Defaulted</span>
            </div>
        </div>

        {{-- ============ STAT CARDS ROW ============ --}}
        <div class="row old-stat-row">
            <div class="col-6 col-xl-3">
                <div class="card stretch stretch-full old-stat-card">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar-text avatar-md bg-soft-danger text-danger">
                                <i class="feather-alert-octagon"></i>
                            </div>
                            <div>
                                <div class="old-stat-label">Days Overdue</div>
                                <div class="old-stat-value" id="dlDaysOverdueValue">{{ $daysLate }} days</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card stretch stretch-full old-stat-card">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar-text avatar-md bg-soft-danger text-danger">
                                <i class="feather-dollar-sign"></i>
                            </div>
                            <div>
                                <div class="old-stat-label">Outstanding Balance</div>
                                <div class="old-stat-value">&#8369;<span id="dlOutstandingValue">{{ number_format($outstandingAmount, 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card stretch stretch-full old-stat-card">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar-text avatar-md bg-soft-warning text-warning">
                                <i class="feather-alert-triangle"></i>
                            </div>
                            <div>
                                <div class="old-stat-label">Unpaid Penalties</div>
                                <div class="old-stat-value">&#8369;<span id="dlUnpaidPenaltiesValue">{{ number_format($unpaidPenalties, 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card stretch stretch-full old-stat-card">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar-text avatar-md bg-soft-success text-success">
                                <i class="feather-check-circle"></i>
                            </div>
                            <div>
                                <div class="old-stat-label">Amount Recovered</div>
                                <div class="old-stat-value">&#8369;<span id="dlAmountRecoveredValue">{{ number_format($amountCollected, 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ LOAN SUMMARY ============ --}}
        <div class="panel loan-summary-panel mb-3">
            <div class="panel-header"><i class="feather-file-text me-2"></i>Loan Summary</div>
            <div class="panel-body">
                <div class="loan-info-grid">
                    <div class="info-item2">
                        <label>Original Loan Amount</label>
                        <span>&#8369;{{ number_format($app->loan_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Total to Pay</label>
                        <span>&#8369;{{ number_format($app->total_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Term</label>
                        <span>{{ $app->loan_term ?? '—' }} {{ $app->loan_term ? 'months' : '' }}</span>
                    </div>
                    <div class="info-item2 is-primary">
                        <label>Installments Paid</label>
                        <span><span id="dlPaidCountSummary">{{ $paidCount }}</span> / <span id="dlTotalCountSummary">{{ $totalCount }}</span></span>
                    </div>
                </div>
                <div class="loan-info-grid secondary">
                    <div class="info-item2">
                        <label>Purpose</label>
                        <span>{{ ucfirst(str_replace('_', ' ', $app->loan_purpose ?? '—')) }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Applied On</label>
                        <span>{{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Last Payment</label>
                        <span id="dlLastPaymentValue">{{ $lastPaymentAt ? \Carbon\Carbon::parse($lastPaymentAt)->format('M d, Y') : 'No payments made' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ PAYMENT SCHEDULE ============ --}}
        <div class="panel payment-schedule-panel">
            <div class="ols-sched-header">
                <div class="ols-sched-header-left">
                    <div class="ols-sched-icon" style="background:#fef2f2; color:#dc2626;"><i class="feather-calendar"></i></div>
                    <div>
                        <div class="ols-sched-title">Payment Schedule</div>
                        <div class="ols-sched-subtitle">Full payment history for this defaulted loan.</div>
                    </div>
                </div>
                <div class="ols-sched-count-pill">
                    <i class="feather-calendar"></i>
                    <span><strong id="dlPaidCountPill">{{ $paidCount }}</strong> of <strong id="dlTotalCountPill">{{ $totalCount }}</strong> installments paid</span>
                </div>
            </div>

            @if($paymentSchedule->count() > 0)
            <div class="table-responsive">
                <table class="ols-sched-table" id="defaultedScheduleTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Amount Due</th>
                            <th>Amount Paid</th>
                            <th>Paid On</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentSchedule as $s)
                        @php
                            $isPaid = !is_null($s->paid_at);
                            $isOverdue = !$isPaid && \Carbon\Carbon::parse($s->due_date)->isPast();
                        @endphp
                        <tr data-schedule-row="{{ $s->id }}">
                            <td><span class="ols-row-num">{{ $s->installment_no }}</span></td>
                            <td><div class="ols-due-date">{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}</div></td>
                            <td class="ols-amount-due">&#8369;{{ number_format($s->amount_due, 2) }}</td>
                            <td class="ols-amount-paid-cell">
                                <div class="ols-amount-paid-value" data-field="amount_paid">&#8369;{{ number_format($s->amount_paid, 2) }}</div>
                            </td>
                            <td data-field="paid_on">{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                            <td data-field="status">
                                @if($isPaid)
                                    <span class="ols-status-pill is-paid"><i class="feather-check-circle"></i> Paid</span>
                                @elseif($isOverdue)
                                    <span class="ols-status-pill is-overdue"><i class="feather-alert-circle"></i> Overdue</span>
                                @else
                                    <span class="ols-status-pill is-upcoming"><i class="feather-clock"></i> Upcoming</span>
                                @endif
                            </td>
                            <td class="text-end" data-field="action">
                                @if(!$isPaid)
                                    <button type="button" class="btn-record-payment"
                                            data-schedule-id="{{ $s->id }}"
                                            data-installment-no="{{ $s->installment_no }}"
                                            data-amount-due="{{ number_format($s->amount_due, 2) }}">
                                        <i class="feather-calendar"></i> Record Payment
                                    </button>
                                @else
                                    <span class="action-recorded-badge"><i class="feather-check-circle"></i> Recorded</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ols-sched-footnote">
                <i class="feather-alert-octagon"></i>
                <span>This loan has exceeded {{ $defaultDaysLate }} days overdue and is classified as defaulted.</span>
            </div>

            @else
            <div class="panel-body">
                <p class="text-muted empty-note">No payment schedule found for this loan.</p>
            </div>
            @endif
        </div>

    </div>

@endsection

{{-- ============================================================
     RECORD PAYMENT CONFIRMATION MODAL
     Pushed into the layout's @stack('modals') slot — same reason
     as the one on Ongoing Loan Detail: rendered OUTSIDE
     <main class="nxl-container"> so it doesn't get caught by the
     blur filter theme.min.css applies to .nxl-container while a
     Bootstrap modal is open.
============================================================ --}}
@push('modals')
    <div class="modal fade nxl-account-modal" id="dlMarkPaidConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="ols-confirm-icon"><i class="feather-help-circle"></i></div>
                    <div class="ols-confirm-title">Mark this payment as paid?</div>
                    <div class="ols-confirm-desc">
                        Installment <strong id="dlConfirmInstallmentNo">#1</strong> of
                        <strong>&#8369;<span id="dlConfirmAmount">0.00</span></strong>
                        will be recorded as paid today. This cannot be undone.
                    </div>
                </div>
                <div class="modal-footer nxl-pw-modal-footer">
                    <button type="button" class="nxl-pw-btn-discard" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="nxl-pw-btn-apply" id="dlConfirmMarkPaidBtn">
                        <i class="feather-check"></i> Yes, Mark as Paid
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@section('page-scripts')
    <script src="{{ asset('functions/defaulted-loan-detail.js') }}"></script>
@endsection