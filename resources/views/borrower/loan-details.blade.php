@extends('borrower.layout')

@section('title', 'Loan Details')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/loan-details.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/payment-receipt.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/payment-details.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/payment-method.css') }}?v={{ time() }}">
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
                $awaitingRelease = !empty($awaitingRelease);
                $statusClass = $awaitingRelease ? 'is-awaiting' : $status['badge'];
                $isLatest = !isset($loans) || $loans->isEmpty()
                    || $loans->first()->application_id == $app->application_id;
            @endphp

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

            <div class="bwl-ld-section">
                <div class="bwl-ld-section__head">
                    <div class="bwl-ld-section__head-text">
                        <span class="bwl-ld-eyebrow">{{ $loanId }}</span>
                        <h5 class="mb-0">Loan Summary</h5>
                    </div>
                    <span class="bwl-ld-status {{ $statusClass }}">{{ $status['label'] }}</span>
                </div>

                <div class="bwl-ld-stat-grid">
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

            <div class="bwl-ld-card">
                <div class="bwl-ld-card-header">
                    <h6>Payment Schedule</h6>
                </div>
                <div class="bwl-ld-card-body">

                    @if($scheduleMessage)

                        <p class="bwl-ld-note mb-0">
                            <i class="feather-info me-1"></i>
                            {{ $scheduleMessage }}
                        </p>

                    @else

                        @if($isCompleted)
                            <p class="bwl-ld-note">
                                <i class="feather-check-circle me-1"></i>
                                All payments on this loan have been received. This is your final payment record.
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

                        {{-- Payment method na itinakda ng admin (read-only) --}}
                        @if(!empty($canUploadReceipts))
                            @include('borrower.partials.assigned-payment-method')
                        @endif

                        <div class="table-responsive">
                            <table class="table bwl-ld-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Due Date</th>
                                        <th>Amount Due</th>
                                        <th>Amount Paid</th>
                                        <th>Paid On</th>
                                        <th>Status</th>
                                        <th class="bwl-ld-th-action">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Ilang buwan lang ang pwedeng mag-upload nang sabay (unpaid na buwan, in order)
                                        $uploadWindow = 2;
                                        $unpaidSeen   = 0;
                                    @endphp
                                    @foreach($paymentSchedule as $row)
                                        @php
                                            $receipt     = $row['receipt'] ?? null;
                                            $rowPayments = $row['payments'] ?? [];
                                            $isRowPaid   = !empty($row['paid_at']);

                                            // Bilangin ang unpaid na buwan. Una at pangalawang unpaid lang ang may upload button (nakatago sa iba).
                                            // Ang na-reject na buwan ay laging bukas (re-upload) at hindi kumakain ng slot.
                                            $isRejected = !$isRowPaid && $receipt && $receipt['status'] === 'rejected';
                                            $inWindow = true;
                                            if (!$isRowPaid && !$isRejected) {
                                                $unpaidSeen++;
                                                $inWindow = $unpaidSeen <= $uploadWindow;
                                            }
                                            $baseCanUpload = !empty($row['can_upload']);
                                            $canUpload     = $baseCanUpload && $inWindow;

                                            // Amount Paid at balance (kung partial)
                                            $paidAmount = $isRowPaid
                                                ? (float) $row['amount']
                                                : collect($rowPayments)->sum(fn ($pay) => (float) data_get($pay, 'amount', data_get($pay, 'amount_paid', 0)));
                                            $balance = max(0, (float) $row['amount'] - $paidAmount);

                                            // Maliit na text sa ilalim ng Due Date
                                            $dueSubClass = '';
                                            if ($isRowPaid) {
                                                $dueSub = 'Paid';
                                            } else {
                                                $d = (int) now()->startOfDay()->diffInDays($row['due']->copy()->startOfDay(), false);
                                                if ($d < 0) {
                                                    $dueSub = abs($d) . ' ' . (abs($d) == 1 ? 'day' : 'days') . ' overdue';
                                                    $dueSubClass = 'is-danger';
                                                } elseif ($d === 0) {
                                                    $dueSub = 'Due today';
                                                } else {
                                                    $dueSub = 'in ' . $d . ' ' . ($d == 1 ? 'day' : 'days');
                                                }
                                            }

                                            $showView    = !$isRowPaid && $receipt && $receipt['status'] === 'submitted';
                                            $showUpload  = !$isRowPaid && !$showView && $canUpload;
                                            $showDetails = $isRowPaid || !empty($rowPayments);
                                            $uploadLabel = $isRejected ? 'Upload New Proof' : (!empty($rowPayments) ? 'Upload Balance' : 'Upload Receipt');
                                            $uploadMode  = $isRejected ? 'again' : 'upload';
                                        @endphp

                                        <tr class="{{ $isRejected ? 'is-rejected' : '' }}"
                                            data-installment="{{ $row['month'] }}"
                                            data-amount="₱{{ number_format($row['amount'], 2) }}"
                                            data-due="{{ $row['due']->format('F j, Y') }}">
                                            <td><span class="bwl-ld-num">{{ $row['month'] }}</span></td>
                                            <td>
                                                <div class="bwl-ld-main">{{ $row['due']->format('M j, Y') }}</div>
                                                <div class="bwl-ld-sub {{ $dueSubClass }}">{{ $dueSub }}</div>
                                            </td>
                                            <td><span class="bwl-ld-main">₱{{ number_format($row['amount'], 2) }}</span></td>
                                            <td>
                                                <div class="bwl-ld-main">₱{{ number_format($paidAmount, 2) }}</div>
                                                @if(!$isRowPaid && $paidAmount > 0)
                                                    <div class="bwl-ld-sub">Balance ₱{{ number_format($balance, 2) }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $isRowPaid ? $row['paid_at']->format('M j, Y') : '—' }}</td>
                                            <td>
                                                @if($row['status'] === 'paid')
                                                    <span class="bwl-ld-st is-paid">Paid</span>
                                                @elseif($row['status'] === 'overdue')
                                                    <span class="bwl-ld-st is-overdue">Overdue</span>
                                                @else
                                                    <span class="bwl-ld-st is-pending">Pending</span>
                                                @endif
                                            </td>
                                            <td class="bwl-ld-action-cell">
                                                @if($showUpload || $showView || $showDetails)
                                                    <div class="bwl-ld-action-group">

                                                        @if($isRowPaid)
                                                            <span class="bwl-ld-recorded">Recorded</span>
                                                        @endif

                                                        {{-- Main action: laging button, hindi nakatago sa menu --}}
                                                        @if($showUpload)
                                                            <button type="button"
                                                                class="{{ $isRejected ? 'bwl-ld-link-btn' : 'bwl-ld-upload-btn' }}"
                                                                data-ld-upload data-mode="{{ $uploadMode }}">
                                                                <i class="feather-upload"></i>{{ $uploadLabel }}
                                                            </button>
                                                        @endif

                                                        {{-- Iba pang actions: nasa dropdown --}}
                                                        @if($showView || $showDetails)
                                                            <div class="dropdown bwl-ld-menu">
                                                                <button type="button" class="bwl-ld-dots" data-bs-toggle="dropdown"
                                                                    aria-expanded="false" aria-label="More actions for month {{ $row['month'] }}">
                                                                    <i class="feather-chevron-down"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end bwl-ld-menu-list">
                                                                    @if($showView)
                                                                        <li>
                                                                            <button type="button" class="dropdown-item bwl-ld-menu-item" data-ld-view
                                                                                data-src="{{ route('borrower.payment-receipt.view', $receipt['id']) }}">
                                                                                <i class="feather-eye"></i>View Receipt
                                                                            </button>
                                                                        </li>
                                                                    @endif
                                                                    @if($showDetails)
                                                                        <li>
                                                                            <button type="button" class="dropdown-item bwl-ld-menu-item" data-ld-payments
                                                                                data-month="{{ $row['month'] }}"
                                                                                data-due="{{ $row['due']->format('F j, Y') }}"
                                                                                data-paid="{{ $isRowPaid ? $row['paid_at']->format('F j, Y') : '' }}"
                                                                                data-amount="{{ number_format($row['amount'], 2) }}"
                                                                                data-status="{{ $isRowPaid ? 'paid' : 'partial' }}"
                                                                                data-payments="{{ json_encode($rowPayments) }}">
                                                                                <i class="feather-file-text"></i>View Payment Details
                                                                            </button>
                                                                        </li>
                                                                    @endif
                                                                </ul>
                                                            </div>
                                                        @endif

                                                    </div>
                                                @else
                                                    <span class="bwl-ld-cell-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>

                                        @if($isRejected)
                                            {{-- Extra row: reason for rejection --}}
                                            <tr class="bwl-ld-reject-row">
                                                <td colspan="7">
                                                    <div class="bwl-ld-reject-panel">
                                                        <div class="bwl-ld-reject-file">
                                                            <span class="bwl-ld-reject-thumb"><i class="feather-image"></i></span>
                                                            <div>
                                                                <div class="bwl-ld-reject-file-title">Uploaded receipt</div>
                                                                <div class="bwl-ld-reject-file-sub">Previous submission</div>
                                                                <span class="bwl-ld-reject-tag">Rejected</span>
                                                            </div>
                                                        </div>
                                                        <div class="bwl-ld-reject-reason">
                                                            <div class="bwl-ld-reject-label">Reason for rejection</div>
                                                            <div class="bwl-ld-reject-title">
                                                                {{ !empty($receipt['remarks']) ? $receipt['remarks'] : 'Your receipt was rejected.' }}
                                                            </div>
                                                            <div class="bwl-ld-reject-hint">Please upload a clearer receipt showing the payment details.</div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    @endif

                </div>
            </div>

            <div class="modal fade bwl-pd-modal" id="ldPayDetailsModal" tabindex="-1"
                aria-labelledby="ldPdTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="ldPdTitle">Payment Details</h5>
                                <div class="bwl-pd-sub" id="ldPdMonth"></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <dl class="bwl-pd-grid">
                                <div><dt>Due Date</dt><dd id="ldPdDue"></dd></div>
                                <div><dt>Date Paid</dt><dd id="ldPdPaidOn"></dd></div>
                                <div><dt>Installment Amount</dt><dd id="ldPdAmount"></dd></div>
                                <div><dt>Status</dt><dd id="ldPdStatus"></dd></div>
                            </dl>

                            <div class="bwl-pd-section-title">Payments</div>
                            <div class="bwl-pd-list" id="ldPdList" hidden></div>
                            <div class="bwl-pd-empty" id="ldPdEmpty" hidden>No payment records yet.</div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="bwl-ld-btn-ghost" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade bwl-pd-modal" id="ldPayViewerModal" tabindex="-1"
                aria-labelledby="ldPdViewerTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="ldPdViewerTitle">Payment Receipt</h5>
                                <div class="bwl-pd-sub" id="ldPdViewerSub"></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <dl class="bwl-pd-grid">
                                <div><dt>Due Date</dt><dd id="ldPdVDue"></dd></div>
                                <div><dt>Date Paid</dt><dd id="ldPdVPaidOn"></dd></div>
                                <div><dt>Installment Amount</dt><dd id="ldPdVInstallment"></dd></div>
                                <div><dt>Amount Paid</dt><dd id="ldPdVPaid"></dd></div>
                                <div><dt>Payment Type</dt><dd id="ldPdVKind"></dd></div>
                                <div><dt>Status</dt><dd id="ldPdVStatus"></dd></div>
                            </dl>

                            <div class="bwl-pd-viewer-body">
                                <span class="spinner-border" id="ldPdViewerLoading" role="status" aria-label="Loading receipt"></span>
                                <img id="ldPdViewerImg" alt="Payment receipt" hidden>
                                <span class="bwl-pd-state" id="ldPdViewerError" hidden>We couldn't load this receipt. Please try again.</span>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="bwl-ld-btn-ghost" data-bs-dismiss="modal">Close</button>
                            <a href="#" class="bwl-ld-btn" id="ldPdViewerDownload">
                                <i class="feather-download"></i>Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>

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

                                    <div class="bwl-ld-field">
                                        <label for="ldReceiptPaidDateDisplay">Date paid</label>
                                        <div class="bwl-ld-date-wrap">
                                            <input type="text" id="ldReceiptPaidDateDisplay"
                                                class="form-control bwl-ld-date-input"
                                                placeholder="MM/DD/YYYY" readonly autocomplete="off">
                                            <i class="feather-calendar bwl-ld-date-icon"></i>
                                        </div>
                                        <input type="hidden" name="paid_date" id="ldReceiptPaidDate">
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
    <script src="{{ asset('functions/payment-receipt.js') }}"></script>
    <script src="{{ asset('functions/payment-detail.js') }}?v={{ time() }}"></script>
   <script src="{{ asset('functions/borrower-payment-method.js') }}"></script>
    <script>
        // Fixed ang position ng menu para hindi maputol ng .table-responsive
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof bootstrap === 'undefined') return;
            document.querySelectorAll('.bwl-ld-dots').forEach(function (el) {
                bootstrap.Dropdown.getOrCreateInstance(el, { popperConfig: { strategy: 'fixed' } });
            });
        });
    </script>
@endsection