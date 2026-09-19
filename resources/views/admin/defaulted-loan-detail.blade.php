@extends('admin.layout')

@section('title', 'Defaulted Loan Detail')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/avatar-placeholder.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/css/defaulted-loan-detail.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
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
            <a href="{{ route('admin.defaulted-loans') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="main-content">

        {{-- ============ BORROWER STRIP ============ --}}

        <div class="dlh-card">
            <div class="dlh-left">
                <div class="dlh-avatar js-borrower-avatar">
                    @if (!empty($borrowerProfilePicture))
                        <img src="{{ $borrowerProfilePicture }}" alt="{{ $app->first_name }} {{ $app->last_name }}">
                    @else
                        {{ $initials }}
                    @endif
                </div>
                <div class="dlh-info">
                    <div class="dlh-name-row">
                        <span class="dlh-name">{{ $app->first_name }} {{ $app->last_name }}</span>
                        <span class="badge badge-rejected dlh-status-badge">Defaulted</span>
                    </div>
                    <div class="dlh-meta-row">
                        <span>{{ $app->email }}</span>
                        <span class="dlh-meta-sep">&bull;</span>
                        <span><strong>Loan ID:</strong> LN-{{ str_pad($applicationId, 5, '0', STR_PAD_LEFT) }}</span>
                        <span class="dlh-meta-sep">&bull;</span>
                        <span class="dlh-overdue-text" id="dlDaysOverdueMeta">{{ $daysLate }} days overdue</span>
                    </div>
                </div>
            </div>

            <div class="dlh-stats">
                <div class="dlh-stat">
                    <div class="dlh-stat-value is-danger" id="dlDaysOverdueValue">{{ $daysLate }} days</div>
                    <div class="dlh-stat-label">Days Overdue</div>
                </div>
                <div class="dlh-stat">
                    <div class="dlh-stat-value">&#8369;<span id="dlOutstandingValue">{{ number_format($outstandingAmount, 2) }}</span></div>
                    <div class="dlh-stat-label">Outstanding Balance</div>
                </div>
                <div class="dlh-stat">
                    <div class="dlh-stat-value">&#8369;<span id="dlUnpaidPenaltiesValue">{{ number_format($unpaidPenalties, 2) }}</span></div>
                    <div class="dlh-stat-label">Unpaid Penalties</div>
                </div>
                <div class="dlh-stat">
                    <div class="dlh-stat-value is-success">&#8369;<span id="dlAmountRecoveredValue">{{ number_format($amountCollected, 2) }}</span></div>
                    <div class="dlh-stat-label">Amount Recovered</div>
                </div>
            </div>
        </div>

        {{-- ============ LOAN SUMMARY ============ --}}
        <div class="panel loan-summary-panel mb-3">
            <div class="panel-header">Loan Summary</div>
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

                    <div>
                        <div class="ols-sched-title">Payment Schedule</div>

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
   
     <main class="nxl-container"> 
============================================================ --}}
@push('modals')
    <div class="modal fade nxl-account-modal" id="dlMarkPaidConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="ols-confirm-title">Mark this payment as paid?</div>
                    <div class="ols-confirm-desc">
                        Installment <strong id="dlConfirmInstallmentNo">#1</strong> of
                        <strong>&#8369;<span id="dlConfirmAmount">0.00</span></strong>
                        will be recorded as paid today. 
                    </div>
                </div>
                <div class="modal-footer nxl-pw-modal-footer">
                    <button type="button" class="nxl-pw-btn-discard" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="nxl-pw-btn-apply dlh-btn-apply" id="dlConfirmMarkPaidBtn">
                     Mark as Paid
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@section('page-scripts')
    <script src="{{ asset('functions/defaulted-loan-detail.js') }}"></script>
@endsection