@extends('borrower.layout')

@section('title', 'Loan Details')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/loan-details.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/payment-receipt.css') }}?v={{ time() }}">
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
            <div class="bwl-ld-empty">
                <div class="bwl-ld-empty-top">
                    <div class="bwl-ld-empty-text-wrap">
                        <h5 class="bwl-ld-empty-title">No loan application yet</h5>
                        <p class="bwl-ld-empty-text">You haven't submitted a loan application. Apply now to get started — it only takes a few minutes.</p>

                        <a href="{{ route('borrower.apply-loan') }}" class="bwl-ld-btn bwl-ld-empty-cta">
                            <i class="feather-file-plus"></i>Apply for a Loan
                        </a>
                    </div>

                    <div class="bwl-ld-empty-art">
                        <img
                            class="bwl-ld-empty-illustration"
                            src="{{ asset('assets/borrower/images/dashboard-welcome-illustration.png') }}"
                            alt=""
                            loading="lazy"
                        >
                    </div>
                </div>

                <div class="bwl-ld-empty-steps">
                    <div class="bwl-ld-empty-step">
                        <span class="bwl-ld-empty-step-num">1</span>
                        <span class="bwl-ld-empty-step-label">Fill out your details</span>
                    </div>
                    <span class="bwl-ld-empty-step-line"></span>
                    <div class="bwl-ld-empty-step">
                        <span class="bwl-ld-empty-step-num">2</span>
                        <span class="bwl-ld-empty-step-label">We review your application</span>
                    </div>
                    <span class="bwl-ld-empty-step-line"></span>
                    <div class="bwl-ld-empty-step">
                        <span class="bwl-ld-empty-step-num">3</span>
                        <span class="bwl-ld-empty-step-label">Get notified of the decision</span>
                    </div>
                </div>
            </div>

        @else

            @php
                $isCompleted = $app->status === 'completed';

                // "Apply for a new loan" lang ang lalabas sa pinakabagong loan.
                $isLatest = !isset($loans) || $loans->isEmpty()
                    || $loans->first()->application_id == $app->application_id;
            @endphp

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
                <div class="bwl-ld-stat-grid bwl-ld-stat-grid--three">
                    <div class="bwl-ld-stat-card">
                        <span class="bwl-ld-stat-label">Loan Term</span>
                        <div class="bwl-ld-stat-value">{{ (int) $app->loan_term }} Months</div>
                    </div>
                    <div class="bwl-ld-stat-card">
                        <span class="bwl-ld-stat-label">Monthly Payment</span>
                        <div class="bwl-ld-stat-value">₱{{ number_format($app->monthly_payment, 2) }}</div>
                        <div class="bwl-ld-info-sub">Due monthly</div>
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

            {{-- ============ PAYMENT SCHEDULE ============ --}}
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
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paymentSchedule as $row)
                                    @php
                                        $receipt   = $row['receipt'] ?? null;
                                        $canUpload = !empty($row['can_upload']);
                                    @endphp
                                    <tr data-installment="{{ $row['month'] }}"
                                        data-amount="₱{{ number_format($row['amount'], 2) }}"
                                        data-due="{{ $row['due']->format('F j, Y') }}">
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
                                        <td class="bwl-ld-action-cell">
                                            @if(!empty($row['paid_at']))
                                                @if(!empty($row['official_receipt_url']))
                                                    @php
                                                        $c = $row['confirmation'] ?? null;
                                                        $summary = 'Received by ' . ($c['admin_name'] ?? 'admin')
                                                            . ' · ₱' . number_format($c['amount'] ?? $row['amount'], 2)
                                                            . (!empty($c['confirmed_on']) ? ' · ' . $c['confirmed_on']->format('F j, Y') : '');
                                                    @endphp
                                                    <button type="button" class="bwl-ld-view-btn" data-ld-view
                                                        data-title="Payment Receipt"
                                                        data-file-prefix="payment-receipt-month-"
                                                        data-src="{{ $row['official_receipt_url'] }}"
                                                        data-summary="{{ $summary }}">
                                                        <i class="feather-file-text"></i>View Receipt
                                                    </button>
                                                @else
                                                    <span class="bwl-ld-cell-muted">—</span>
                                                @endif

                                            @elseif($receipt && $receipt['status'] === 'submitted')
                                                <div class="bwl-ld-action-group">
                                                    <button type="button" class="bwl-ld-view-btn" data-ld-view
                                                        data-src="{{ route('borrower.payment-receipt.view', $receipt['id']) }}">
                                                        <i class="feather-file-text"></i>View Receipt
                                                    </button>
                                                    @if($canUpload)
                                                        <button type="button" class="bwl-ld-link-btn" data-ld-upload data-mode="replace">Replace</button>
                                                    @endif
                                                </div>

                                            @elseif($receipt && $receipt['status'] === 'rejected')
                                                <div class="bwl-ld-action-group">
                                                    <span class="bwl-ld-receipt-badge is-rejected">Rejected</span>
                                                    @if($canUpload)
                                                        <button type="button" class="bwl-ld-link-btn" data-ld-upload data-mode="again">Upload again</button>
                                                    @endif
                                                </div>
                                                @if(!empty($receipt['remarks']))
                                                    <div class="bwl-ld-receipt-remarks">{{ $receipt['remarks'] }}</div>
                                                @endif

                                            @elseif($canUpload)
                                                <button type="button" class="bwl-ld-upload-btn" data-ld-upload data-mode="upload">
                                                    <i class="feather-upload"></i>Upload Receipt
                                                </button>

                                            @else
                                                <span class="bwl-ld-cell-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ============ VIEW RECEIPT MODAL ============ --}}
            <div class="modal fade bwl-ld-modal" id="ldViewModal" tabindex="-1"
                aria-labelledby="ldViewTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="ldViewTitle">Receipt</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="bwl-ld-view-summary" id="ldViewSummary" hidden></div>

                            <div class="bwl-ld-view-stage">
                                <span class="spinner-border" id="ldViewLoading" role="status" aria-label="Loading receipt"></span>
                                <img id="ldViewImg" alt="Receipt" hidden>
                                <p class="bwl-ld-view-error" id="ldViewError" hidden>
                                    We couldn't load this receipt. Please try again.
                                </p>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="bwl-ld-btn-ghost" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="bwl-ld-btn" id="ldViewDownload">
                                <i class="feather-download"></i>Download
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ UPLOAD RECEIPT MODAL ============ --}}
            @if(!empty($canUploadReceipts))
                <div class="modal fade bwl-ld-modal" id="ldReceiptModal" tabindex="-1"
                    aria-labelledby="ldReceiptTitle" aria-hidden="true"
                    data-submit-url="{{ url('/api/borrower/payment-receipt') }}">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form id="ldReceiptForm" novalidate>
                                @csrf
                                <input type="hidden" name="application_id" value="{{ $app->application_id }}">
                                <input type="hidden" name="installment_no" id="ldReceiptInstallment" value="">

                                <div class="modal-header">
                                    <h5 class="modal-title" id="ldReceiptTitle">Upload Receipt</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="bwl-ld-modal-summary">
                                        <div>
                                            <span class="bwl-ld-info-label">Amount Due</span>
                                            <div class="bwl-ld-modal-summary-value" id="ldReceiptAmount">—</div>
                                        </div>
                                        <div>
                                            <span class="bwl-ld-info-label">Due Date</span>
                                            <div class="bwl-ld-modal-summary-value" id="ldReceiptDue">—</div>
                                        </div>
                                    </div>

                                    <div class="bwl-ld-field mb-0">
                                        <label for="ldReceiptFile">Receipt image</label>
                                        <div class="bwl-ld-dropzone" id="ldReceiptDrop">
                                            <input type="file" class="bwl-ld-dropzone__input" id="ldReceiptFile" name="receipt"
                                                accept="image/jpeg,image/png" title="Choose a receipt image">

                                            <span class="bwl-ld-dropzone__empty" id="ldReceiptDropEmpty">
                                                <i class="feather-image"></i>
                                                <span class="bwl-ld-dropzone__title">Choose a receipt photo</span>
                                                <span class="bwl-ld-dropzone__hint">or drag and drop it here · JPG or PNG, up to 5MB</span>
                                            </span>

                                            <span class="bwl-ld-dropzone__filled" id="ldReceiptDropFilled" hidden>
                                                <img id="ldReceiptPreviewImg" alt="Receipt preview">
                                                <span class="bwl-ld-dropzone__name" id="ldReceiptFileName"></span>
                                                <span class="bwl-ld-dropzone__change">Click to change</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="bwl-ld-modal-error" id="ldReceiptError" role="alert" hidden></div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="bwl-ld-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="bwl-ld-btn" id="ldReceiptSubmit" disabled>
                                        <span class="spinner-border spinner-border-sm" id="ldReceiptSpinner" hidden aria-hidden="true"></span>
                                        <span id="ldReceiptSubmitText">Submit Receipt</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

        @endif

    </div>

@endsection

@section('page-scripts')
    {{-- loan-details.js tinanggal na: tabs lang ang laman niya. --}}
    <script src="{{ asset('functions/payment-receipt.js') }}"></script>
@endsection