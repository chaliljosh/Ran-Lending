@extends('admin.layout')

@section('title', 'Overdue Payment Detail')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-details.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/overdue-payment-detail.css') }}?v={{ time() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/avatar-placeholder.css') }}">
@endsection

@section('content')
    @php
        $loanCode = 'LN-' . strtoupper(substr($loanUuid, 0, 8));
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Overdue Payment Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                <li class="breadcrumb-item">{{ $loanCode }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('admin.overdue-payments') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="main-content">
        @if (session('success'))
            <div class="alert alert-success mb-3">{{ session('success') }}</div>
        @endif

        @php
            $fullName = trim(($app->first_name ?? '') . ' ' . ($app->last_name ?? ''));
        @endphp

        <div class="opd-content">

            <div class="section-card opd-borrower-header mb-3">
                <div class="borrower-avatar js-borrower-avatar"
                    data-initials="{{ strtoupper(substr($app->first_name ?? '', 0, 1) . substr($app->last_name ?? '', 0, 1)) }}">
                    @if ($borrowerProfilePicture)
                        <img src="{{ $borrowerProfilePicture }}" alt="{{ $fullName }}" class="borrower-avatar-img">
                    @else
                        {{ strtoupper(substr($app->first_name ?? '', 0, 1) . substr($app->last_name ?? '', 0, 1)) }}
                    @endif
                </div>
                <div class="opd-borrower-right">
                    <h6 class="fw-bold mb-1">{{ $fullName !== '' ? $fullName : '—' }}</h6>
                    <p class="opd-borrower-email mb-0"><strong>Email:</strong> {{ $app->email }}</p>
                    <div class="opd-borrower-meta">
                        <span class="badge badge-rejected px-3 py-2">Overdue</span>
                        <span class="opd-days-overdue-text">{{ $daysLate }} day{{ $daysLate == 1 ? '' : 's' }}
                            overdue</span>
                    </div>
                </div>
            </div>

            <div class="opd-card opd-overdue-card">
                <div class="opd-card-title"> Overdue Payment</div>

                @if ($oldestOverdue)
                    <div class="opd-overdue-strip">
                        <div class="opd-field">
                            <div class="opd-field-label">Installment</div>
                            <div class="opd-field-value">#{{ $oldestOverdue->installment_no }}</div>
                        </div>
                        <div class="opd-field">
                            <div class="opd-field-label">Due Date</div>
                            <div class="opd-field-value">
                                {{ \Carbon\Carbon::parse($oldestOverdue->due_date)->format('M d, Y') }}</div>
                        </div>
                        <div class="opd-field">
                            <div class="opd-field-label">Days Overdue</div>
                            <div class="opd-field-value is-danger">{{ $daysLate }} days</div>
                        </div>
                        <div class="opd-field">
                            <div class="opd-field-label">Penalty</div>
                            <div class="opd-field-value is-orange">₱{{ number_format($currentPenalty, 2) }}</div>
                        </div>
                        <div class="opd-field">
                            <div class="opd-field-label">Amount Due</div>
                            <div class="opd-field-value">₱{{ number_format($amountDue, 2) }}</div>
                        </div>
                    </div>

                    <div class="opd-overdue-footer">
                        <div class="opd-outstanding-inline">
                            <span class="opd-outstanding-label">Total Outstanding</span>
                            <span class="opd-outstanding-value">₱{{ number_format($totalOutstanding, 2) }}</span>
                        </div>

                        <div class="opd-actions">
                            @if ($borrowerReceipt)
                                <button type="button" class="opd-btn opd-btn-view" data-bs-toggle="modal"
                                    data-bs-target="#opdBorrowerReceiptModal">
                                    <i class="feather-file-text"></i> View Receipt
                                </button>
                            @endif
                            <button type="button" class="opd-btn opd-btn-waive" data-bs-toggle="modal"
                                data-bs-target="#opdPartialModal">
                                Record Partial Payment
                            </button>
                            <button type="button" class="opd-btn opd-btn-mark-paid" data-bs-toggle="modal"
                                data-bs-target="#opdConfirmPaidModal">
                                 Mark as Paid
                            </button>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0" style="font-size:0.85rem;">No overdue installment found for this loan.</p>
                @endif
            </div>

            <div class="opd-card">
                <div class="opd-card-title">Payment Schedule</div>
                <div class="opd-table-scroll">
                    <table class="opd-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Due Date</th>
                                <th>Amount Due</th>
                                <th>Paid On</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paymentSchedule as $s)
                                @php
                                    $isPaid = !is_null($s->paid_at);
                                    $paidSoFar = (float) $s->amount_paid;
                                    $isPartial = !$isPaid && $paidSoFar > 0;
                                    $isOverdue = !$isPaid && \Carbon\Carbon::parse($s->due_date)->isPast();
                                    $badgeClass = $isPaid
                                        ? 'opd-badge-paid'
                                        : ($isOverdue
                                            ? 'opd-badge-overdue'
                                            : ($isPartial ? 'opd-badge-partial' : 'opd-badge-upcoming'));
                                    $badgeLabel = $isPaid
                                        ? 'Paid'
                                        : ($isOverdue
                                            ? ($isPartial ? 'Partial · Overdue' : 'Overdue')
                                            : ($isPartial ? 'Partial' : 'Upcoming'));
                                    $isCurrentRow = $oldestOverdue && $s->id === $oldestOverdue->id;
                                @endphp
                                <tr class="{{ $isCurrentRow ? 'is-current-overdue' : '' }}">
                                    <td data-label="#">{{ $s->installment_no }}</td>
                                    <td data-label="Due Date">{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}
                                    </td>
                                    <td data-label="Amount Due">
                                        <div>
                                            ₱{{ number_format($s->amount_due, 2) }}
                                            @if ($isPartial)
                                                <span class="opd-cell-sub">₱{{ number_format($paidSoFar, 2) }} paid ·
                                                    ₱{{ number_format($s->amount_due - $paidSoFar, 2) }} left</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td data-label="Paid On">
                                        {{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                                    <td data-label="Status"><span
                                            class="opd-badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="opd-card opd-summary-card">
                <div class="opd-card-title">Loan Summary</div>
                <div class="opd-summary-grid">
                    <div class="opd-summary-row">
                        <span class="opd-summary-label">Application ID</span>
                        <span class="opd-summary-value">{{ $loanCode }}</span>
                    </div>
                    <div class="opd-summary-row">
                        <span class="opd-summary-label">Loan Amount</span>
                        <span class="opd-summary-value">₱{{ number_format($app->loan_amount, 2) }}</span>
                    </div>
                    <div class="opd-summary-row">
                        <span class="opd-summary-label">Term</span>
                        <span class="opd-summary-value">{{ $app->loan_term }} months</span>
                    </div>
                    <div class="opd-summary-row">
                        <span class="opd-summary-label">Total Interest</span>
                        <span class="opd-summary-value">₱{{ number_format($app->total_interest ?? 0, 2) }}</span>
                    </div>
                    <div class="opd-summary-row">
                        <span class="opd-summary-label">Total to Pay</span>
                        <span class="opd-summary-value">₱{{ number_format($app->total_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="opd-summary-row">
                        <span class="opd-summary-label">Monthly Payment</span>
                        <span class="opd-summary-value">₱{{ number_format($app->monthly_payment ?? 0, 2) }}</span>
                    </div>
                    <div class="opd-summary-row">
                        <span class="opd-summary-label">Purpose</span>
                        <span class="opd-summary-value">{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}</span>
                    </div>
                    <div class="opd-summary-row">
                        <span class="opd-summary-label">Applied</span>
                        <span class="opd-summary-value">{{ date('M d, Y', strtotime($app->applied_at)) }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('modals')
    <div id="opd-config"
        data-mark-paid-url="{{ $oldestOverdue ? route('admin.installment-mark-paid', $oldestOverdue->id) : '' }}"
        data-waive-url="{{ $penaltyId ? route('admin.penalty-waive', $penaltyId) : '' }}"
        data-csrf="{{ csrf_token() }}" style="display:none;"></div>

    <div class="modal fade opd-modal" id="opdConfirmPaidModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="opd-modal-title">Mark Payment as Paid?</div>
                    <div class="opd-modal-desc">Please review the details before confirming.</div>

                    @if ($oldestOverdue)
                        <div class="opd-confirm-grid">
                            <div class="opd-confirm-cell">
                                <div class="opd-confirm-label">Borrower</div>
                                <div class="opd-confirm-value">{{ $fullName !== '' ? $fullName : '—' }}</div>
                            </div>
                            <div class="opd-confirm-cell">
                                <div class="opd-confirm-label">Loan</div>
                                <div class="opd-confirm-value">{{ $loanCode }}</div>
                            </div>
                            <div class="opd-confirm-cell">
                                <div class="opd-confirm-label">Installment</div>
                                <div class="opd-confirm-value">#{{ $oldestOverdue->installment_no }}</div>
                            </div>
                            <div class="opd-confirm-cell">
                                <div class="opd-confirm-label">Due Date</div>
                                <div class="opd-confirm-value">
                                    {{ \Carbon\Carbon::parse($oldestOverdue->due_date)->format('M d, Y') }}
                                    <span class="opd-confirm-sub is-danger">{{ $daysLate }}
                                        day{{ $daysLate == 1 ? '' : 's' }} overdue</span>
                                </div>
                            </div>
                        </div>

                        <div class="opd-breakdown">
                            <div class="opd-breakdown-row">
                                <span>Amount Due</span>
                                <span class="opd-breakdown-amount">₱{{ number_format($amountDue, 2) }}</span>
                            </div>
                            <div class="opd-breakdown-row">
                                <span>
                                    Penalty
                                    @if ($currentPenalty > 0)
                                        <small>
                                            {{ intdiv($daysLate, config('penalty.late_fee_period_days')) }}
                                            × ₱{{ number_format(config('penalty.late_fee_amount'), 0) }}
                                            (₱{{ number_format(config('penalty.late_fee_amount'), 0) }} every
                                            {{ config('penalty.late_fee_period_days') }} days late)
                                        </small>
                                    @endif
                                </span>
                                <span class="opd-breakdown-amount {{ $currentPenalty > 0 ? 'is-orange' : '' }}">
                                    ₱{{ number_format($currentPenalty, 2) }}
                                </span>
                            </div>
                            <div class="opd-breakdown-total">
                                <span class="opd-confirm-total-label">Total Outstanding</span>
                                <span class="opd-confirm-total-value">₱{{ number_format($totalOutstanding, 2) }}</span>
                            </div>
                        </div>

                        <div class="ols-field">
                            <label for="opdPaidDateDisplay" class="ols-field-label">Date Paid <span class="ols-req">*</span></label>
                            <div class="ols-date-wrap">
                                <input type="text" id="opdPaidDateDisplay" class="ols-input ols-date-input"
                                    placeholder="MM/DD/YYYY" readonly autocomplete="off">
                                <i class="feather-calendar ols-date-icon"></i>
                            </div>
                            <input type="hidden" id="opdPaidDate">
                        </div>

                        @if ($borrowerReceipt)
                            <div class="ols-field" id="opdUsingBorrowerReceipt" style="display:none;">
                                <label class="ols-field-label">Receipt</label>
                                <div class="ols-preview">
                                    <img id="opdUsingBorrowerThumb" alt="Borrower receipt">
                                    <div class="ols-preview-meta">
                                        <span>Receipt from the borrower</span>
                                        <span>Will be saved as the payment receipt</span>
                                    </div>
                                    <button type="button" class="ols-preview-remove" id="opdUseNoReceiptBtn"
                                        aria-label="Remove borrower receipt" title="Remove">
                                        <i class="feather-x"></i>
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div id="opdPaidError" class="ols-form-error d-none"></div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="opd-btn opd-btn-waive" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="opd-btn opd-btn-mark-paid" id="opdConfirmPaidBtn">
                        <span id="opdConfirmPaidText">Mark as Paid</span>
                        <span id="opdConfirmPaidSpinner" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade opd-modal" id="opdPartialModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="opd-modal-title">Record Partial Payment</div>
                    <div class="opd-modal-desc">Use this when the borrower paid less than the amount due.</div>

                    @if ($oldestOverdue)
                        <div class="opd-breakdown" style="margin-top:18px;">
                            <div class="opd-breakdown-row">
                                <span>Installment</span>
                                <span class="opd-breakdown-amount">#{{ $oldestOverdue->installment_no }}</span>
                            </div>
                            <div class="opd-breakdown-row">
                                <span>
                                    Penalty
                                    <small>Not included. It stays unpaid until the installment is fully paid.</small>
                                </span>
                                <span class="opd-breakdown-amount {{ $currentPenalty > 0 ? 'is-orange' : '' }}">
                                    ₱{{ number_format($currentPenalty, 2) }}
                                </span>
                            </div>
                            <div class="opd-breakdown-total">
                                <span class="opd-confirm-total-label">Installment Balance</span>
                                <span class="opd-confirm-total-value">₱{{ number_format($amountDue, 2) }}</span>
                            </div>
                        </div>

                        <div class="ols-field">
                            <label for="opdPartialDateDisplay" class="ols-field-label">Date Paid <span class="ols-req">*</span></label>
                            <div class="ols-date-wrap">
                                <input type="text" id="opdPartialDateDisplay" class="ols-input ols-date-input"
                                    placeholder="MM/DD/YYYY" readonly autocomplete="off">
                                <i class="feather-calendar ols-date-icon"></i>
                            </div>
                            <input type="hidden" id="opdPartialDate">
                        </div>

                        <div class="ols-field">
                            <label for="opdPartialAmount" class="ols-field-label">Amount Received <span class="ols-req">*</span></label>
                            <input type="number" id="opdPartialAmount" class="ols-input" min="0.01" step="0.01"
                                placeholder="0.00"
                                data-balance="{{ number_format($amountDue, 2, '.', '') }}">
                            <div class="ols-field-hint" id="opdPartialHint"></div>
                        </div>

                        @if ($borrowerReceipt)
                            <div class="ols-field" id="opdPUsingBorrowerReceipt" style="display:none;">
                                <label class="ols-field-label">Receipt</label>
                                <div class="ols-preview">
                                    <img id="opdPUsingBorrowerThumb" alt="Borrower receipt">
                                    <div class="ols-preview-meta">
                                        <span>Receipt from the borrower</span>
                                        <span>Will be saved as the payment receipt</span>
                                    </div>
                                    <button type="button" class="ols-preview-remove" id="opdPUseNoReceiptBtn"
                                        aria-label="Remove borrower receipt" title="Remove">
                                        <i class="feather-x"></i>
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div id="opdPartialError" class="ols-form-error d-none"></div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="opd-btn opd-btn-waive" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="opd-btn opd-btn-mark-paid" id="opdPartialBtn">
                        <span id="opdPartialBtnText">Record Partial Payment</span>
                        <span id="opdPartialSpinner" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($borrowerReceipt && $oldestOverdue)
        <div class="modal fade opd-modal" id="opdBorrowerReceiptModal" tabindex="-1" aria-labelledby="opdBrTitle"
            aria-hidden="true"
            data-file-url="{{ route('admin.borrower-receipt-file', $borrowerReceipt->receipt_id) }}">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h6 class="modal-title" id="opdBrTitle">Borrower Receipt</h6>
                            <div class="opd-br-sub">Installment #{{ $oldestOverdue->installment_no }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body opd-br-body">
                        <div class="opd-br-preview">
                            <div class="opd-br-state" id="opdBrLoading">Loading receipt...</div>
                            <div class="opd-br-state" id="opdBrError" style="display:none;">Unable to load the receipt.</div>
                            <a href="#" target="_blank" rel="noopener" id="opdBrImgLink" title="Open full size"
                                style="display:none;">
                                <img id="opdBrImg" alt="Receipt uploaded by the borrower">
                            </a>
                            <div class="opd-br-hint" id="opdBrHint" style="display:none;">Click the image to open it full size</div>
                        </div>

                        <div class="opd-br-details">
                            <div class="opd-br-amount-label">Total Outstanding</div>
                            <div class="opd-br-amount">₱{{ number_format($totalOutstanding, 2) }}</div>

                            <dl class="opd-br-list">
                                <div>
                                    <dt>Amount Due</dt>
                                    <dd>₱{{ number_format($amountDue, 2) }}</dd>
                                </div>
                                <div>
                                    <dt>Penalty</dt>
                                    <dd class="{{ $currentPenalty > 0 ? 'is-orange' : '' }}">
                                        ₱{{ number_format($currentPenalty, 2) }}</dd>
                                </div>
                                <div>
                                    <dt>Due Date</dt>
                                    <dd>{{ \Carbon\Carbon::parse($oldestOverdue->due_date)->format('F j, Y') }}</dd>
                                </div>
                                <div>
                                    <dt>Submitted</dt>
                                    <dd>{{ !empty($borrowerReceipt->submitted_at) ? \Carbon\Carbon::parse($borrowerReceipt->submitted_at)->format('F j, Y \a\t g:i A') : '—' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="opd-btn opd-btn-waive" data-bs-dismiss="modal">Close</button>
                        <a href="{{ route('admin.borrower-receipt-file', ['id' => $borrowerReceipt->receipt_id, 'download' => 1]) }}"
                            class="opd-btn opd-btn-view">
                            <i class="feather-download"></i> Download
                        </a>
                        <button type="button" class="opd-btn opd-btn-waive" id="opdBrPartialBtn">Record Partial</button>
                        <button type="button" class="opd-btn opd-btn-mark-paid" id="opdBrRecordBtn">Record Payment</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade opd-modal" id="opdWaiveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="opd-modal-icon is-confirm"><i class="feather-slash"></i></div>
                    <div class="opd-modal-title">Waive Penalty</div>
                    <div class="opd-modal-desc">Please provide a reason for waiving this penalty.</div>
                    <textarea id="opdWaiveReason" class="opd-modal-textarea" rows="3" placeholder="Reason for waiving..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="opd-btn opd-btn-waive" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="opd-btn opd-btn-mark-paid" id="opdConfirmWaiveBtn">
                       Confirm Waive
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@section('page-scripts')
    <script src="{{ asset('functions/overdue-payment-detail.js') }}?v={{ time() }}"></script>
@endsection