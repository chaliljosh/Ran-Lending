@extends('admin.layout')

@section('title', 'Overdue Payment Detail')

@section('page-styles')
    {{-- Borrower header now reuses the shared section-card / borrower-avatar
         components from loan-details.css (same ones Completed Loan Detail
         uses), so that stylesheet needs to load here too. --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-details.css') }}">
    {{-- Standalone stylesheet for this page only — not shared with loan-details.css --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/overdue-payment-detail.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/avatar-placeholder.css') }}">
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Overdue Payment Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                <li class="breadcrumb-item">#{{ $applicationId }}</li>
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

            {{-- ============ 1. BORROWER HEADER ============ --}}
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

            {{-- ============ 2 & 3. OVERDUE PAYMENT SUMMARY + ACTIONS ============
                 Compact horizontal strip instead of a tall 2-column grid: one
                 row of small stat fields, a hairline divider, then a single
                 footer row with Total Outstanding (left) and the action
                 buttons (right). --}}
            <div class="opd-card opd-overdue-card">
                <div class="opd-card-title"><i class="feather-alert-triangle"></i> Overdue Payment</div>

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
                            <button type="button" class="opd-btn opd-btn-mark-paid" data-bs-toggle="modal"
                                data-bs-target="#opdConfirmPaidModal">
                                <i class="feather-check"></i> Mark as Paid
                            </button>
                            <button type="button" class="opd-btn opd-btn-waive" data-bs-toggle="modal"
                                data-bs-target="#opdWaiveModal" {{ !$penaltyId ? 'disabled' : '' }}>
                                <i class="feather-slash"></i> Waive Penalty
                            </button>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0" style="font-size:0.85rem;">No overdue installment found for this loan.</p>
                @endif
            </div>

            {{-- ============ 4. PAYMENT SCHEDULE ============ --}}
            <div class="opd-card">
                <div class="opd-card-title"><i class="feather-calendar"></i> Payment Schedule</div>
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
                                    $isOverdue = !$isPaid && \Carbon\Carbon::parse($s->due_date)->isPast();
                                    $badgeClass = $isPaid
                                        ? 'opd-badge-paid'
                                        : ($isOverdue
                                            ? 'opd-badge-overdue'
                                            : 'opd-badge-upcoming');
                                    $badgeLabel = $isPaid ? 'Paid' : ($isOverdue ? 'Overdue' : 'Upcoming');
                                    $isCurrentRow = $oldestOverdue && $s->id === $oldestOverdue->id;
                                @endphp
                                <tr class="{{ $isCurrentRow ? 'is-current-overdue' : '' }}">
                                    <td data-label="#">{{ $s->installment_no }}</td>
                                    <td data-label="Due Date">{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}
                                    </td>
                                    <td data-label="Amount Due">₱{{ number_format($s->amount_due, 2) }}</td>
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

            {{-- ============ 5. LOAN SUMMARY (compact, secondary) ============ --}}
            <div class="opd-card opd-summary-card">
                <div class="opd-card-title"><i class="feather-file-text"></i> Loan Summary</div>
                <div class="opd-summary-grid">
                    <div class="opd-summary-row">
                        <span class="opd-summary-label">Application ID</span>
                        <span class="opd-summary-value">#{{ $app->application_id }}</span>
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
                        <span class="opd-summary-label">Interest Rate</span>
                        <span class="opd-summary-value">5% / month</span>
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
    {{-- Config for overdue-payment-detail.js — reads URLs/CSRF from here
         instead of having them embedded as inline PHP-generated JS. --}}
    <div id="opd-config"
        data-mark-paid-url="{{ $oldestOverdue ? route('admin.installment-mark-paid', $oldestOverdue->id) : '' }}"
        data-waive-url="{{ $penaltyId ? route('admin.penalty-waive', $penaltyId) : '' }}"
        data-csrf="{{ csrf_token() }}" style="display:none;"></div>

    {{-- ============ CONFIRM: Mark as Paid ============ --}}
    <div class="modal fade opd-modal" id="opdConfirmPaidModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="opd-modal-title">Mark Payment as Paid?</div>
                    <div class="opd-modal-desc">Are you sure you want to mark this overdue payment as paid?</div>
                    <div class="modal-footer">

                        <button type="button" class="opd-btn opd-btn-waive" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="opd-btn opd-btn-mark-paid" id="opdConfirmPaidBtn">
                            Mark as Paid
                        </button>
                    </div>

                </div>

            </div>
        </div>
    </div>

    {{-- ============ WAIVE PENALTY ============ --}}
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
                        <i class="feather-check"></i> Confirm Waive
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ SUCCESS (shared by both actions) ============ --}}
    <div class="modal fade opd-modal" id="opdSuccessModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="opd-modal-icon is-success"><i class="feather-check-circle"></i></div>
                    <div class="opd-modal-title" id="opdSuccessTitle">Success</div>
                    <div class="opd-modal-desc" id="opdSuccessDesc"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="opd-btn opd-btn-mark-paid" id="opdSuccessOkBtn">OK</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@section('page-scripts')
    <script src="{{ asset('functions/overdue-payment-detail.js') }}"></script>
@endsection
