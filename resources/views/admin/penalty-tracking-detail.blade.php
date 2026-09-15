@extends('admin.layout')

@section('title', 'Penalty Details')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/penalty-tracking-detail.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@endsection

@section('content')

    @php
        $status = $p->status ?? 'unpaid';

        if ($status === 'paid') {
            $statusClass = 'is-paid';
            $statusIconClass = 'is-green';
            $statusValueClass = 'is-green';
        } elseif ($status === 'waived') {
            $statusClass = 'is-waived';
            $statusIconClass = 'is-gray';
            $statusValueClass = 'is-gray';
        } else {
            $statusClass = 'is-unpaid';
            $statusIconClass = 'is-red';
            $statusValueClass = 'is-red';
        }

        $totalPoints = !empty($p->breakdown) ? collect($p->breakdown)->sum('points') : $p->late_fee_periods ?? 0;
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Penalty Tracking Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.penalty-tracking') }}">Penalty Tracking</a></li>
                <li class="breadcrumb-item">Details</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('admin.penalty-tracking') }}" class="pd-back-btn">Back</a>
        </div>
    </div>

    <div class="main-content">

        {{-- ── UNIFIED HEADER CARD (identity + penalty stat row) ── --}}
        <div class="pd-header-card mb-4">
            <div class="pd-header-block-left">
                <div class="pd-avatar js-borrower-avatar">
                    @if (!empty($borrowerProfilePicture))
                        <img src="{{ $borrowerProfilePicture }}" alt="{{ $p->first_name }} {{ $p->last_name }}">
                    @else
                        {{ strtoupper(substr($p->first_name ?? 'U', 0, 1) . substr($p->last_name ?? '', 0, 1)) }}
                    @endif
                </div>
                <div>
                    <div class="pd-name-row">
                        <span class="pd-borrower-name">{{ $p->first_name ?? '—' }} {{ $p->last_name ?? '' }}</span>
                        <span class="pd-status-badge pd-badge-{{ $status }}">
                            @if ($status === 'paid')
                                <i class="bi bi-check-circle-fill me-1"></i>
                            @elseif($status === 'waived')
                                <i class="bi bi-slash-circle-fill me-1"></i>
                            @else
                                <i class="bi bi-exclamation-circle-fill me-1"></i>
                            @endif
                            {{ ucfirst($status) }}
                        </span>
                    </div>
                    <div class="pd-borrower-sub">
                        Loan ID:
                        {{ $p->loan_id_display ?? 'LN-' . str_pad($p->application_id ?? 0, 5, '0', STR_PAD_LEFT) }}
                    </div>
                    <div class="pd-meta-row">
                        <span><i class="bi bi-tag"></i>Installment: #{{ $p->installment_no }}</span>
                        <span><i class="bi bi-calendar3"></i>Due Date:
                            {{ \Carbon\Carbon::parse($p->due_date)->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="pd-header-block-right">
                <div class="pd-stat-item">
                    <div class="pd-stat-value">{{ $p->days_late }}</div>
                    <div class="pd-stat-label">Days Late</div>
                </div>
                <div class="pd-stat-item">
                    <div class="pd-stat-value">{{ $p->late_fee_periods }}</div>
                    <div class="pd-stat-label">{{ Str::plural('Point', $p->late_fee_periods) }}</div>
                </div>
                <div class="pd-stat-item">
                    <div class="pd-stat-value">₱{{ number_format($p->rate ?? 0, 2) }}</div>
                    <div class="pd-stat-label">Per Point</div>
                </div>
                <div class="pd-stat-item">
                    <div class="pd-stat-value is-danger">₱{{ number_format($p->penalty_amount, 2) }}</div>
                    <div class="pd-stat-label">Total Penalty</div>
                </div>
            </div>
        </div>

        {{-- ── PENALTY BREAKDOWN ── --}}
        @if (!empty($p->breakdown))
            <div class="pd-card mb-4">
                <div class="pd-card-header">
                    <div class="pd-card-title">Penalty Breakdown</div>
                </div>
                <div class="pd-card-body p-0">
                    <div class="pd-table-scroll">
                        <table class="pd-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date Applied</th>
                                    <th>Reason</th>
                                    <th>Days Overdue</th>
                                    <th>Points</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($p->breakdown as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                                        <td>{{ ucfirst($row['reason']) }}</td>
                                        <td>{{ $row['days_overdue'] }} days</td>
                                        <td><span class="pd-point-badge">+{{ $row['points'] }}</span></td>
                                        <td class="text-end fw-600">₱{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="fw-700">Total Penalty</td>
                                    <td class="fw-700">{{ $totalPoints }} {{ Str::plural('point', $totalPoints) }}</td>
                                    <td class="text-end fw-700" colspan="2">₱{{ number_format($p->penalty_amount, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── PENALTY STATUS ── --}}
        <div class="pd-card mb-4">
            <div class="pd-card-header">

                <div class="pd-card-title">Penalty Status</div>
            </div>
            <div class="pd-card-body">
                <div class="pd-status-grid {{ $status === 'unpaid' ? 'is-single' : '' }}">
                    <div class="pd-status-box">
                        <div class="pd-status-box-icon {{ $statusIconClass }}">
                            @if ($status === 'paid')
                                <i class="bi bi-check-lg"></i>
                            @elseif($status === 'waived')
                                <i class="bi bi-slash-circle"></i>
                            @else
                                <i class="bi bi-exclamation-triangle"></i>
                            @endif
                        </div>
                        <div>
                            <div class="pd-status-box-label">Status</div>
                            <div class="pd-status-box-value {{ $statusValueClass }}">
                                @if ($status === 'paid')
                                    ✓ Paid
                                @elseif($status === 'waived')
                                    Waived
                                @else
                                    Unpaid
                                @endif
                            </div>
                            <div class="pd-status-box-desc">
                                @if ($status === 'paid')
                                    Penalty has been settled.
                                @elseif($status === 'waived')
                                    This penalty was waived and no longer needs payment.
                                @else
                                    Payment is still pending for this penalty.
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($status === 'paid')
                        <div class="pd-status-box">
                            <div class="pd-status-box-icon is-blue"><i class="bi bi-calendar-check"></i></div>
                            <div>
                                <div class="pd-status-box-label">Resolved Date</div>
                                <div class="pd-status-box-value">
                                    {{ \Carbon\Carbon::parse($p->updated_at)->format('M d, Y') }}</div>
                                <div class="pd-status-box-desc">Date when the penalty was fully paid.</div>
                            </div>
                        </div>
                    @elseif($status === 'waived')
                        <div class="pd-status-box">
                            <div class="pd-status-box-icon is-blue"><i class="bi bi-calendar-check"></i></div>
                            <div>
                                <div class="pd-status-box-label">Waived Date</div>
                                <div class="pd-status-box-value">
                                    {{ \Carbon\Carbon::parse($p->updated_at)->format('M d, Y') }}</div>
                                <div class="pd-status-box-desc">Waived by {{ $p->waived_by_name ?? '—' }}.</div>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($status === 'waived' && !empty($p->waived_reason))
                    <div class="pd-status-box mt-3">
                        <div class="pd-status-box-icon is-gray"><i class="bi bi-chat-left-text"></i></div>
                        <div>
                            <div class="pd-status-box-label">Reason for Waiver</div>
                            <div class="pd-info-value pd-waived-reason">{{ $p->waived_reason }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Action Buttons (unpaid only) --}}
        @if ($status === 'unpaid')
            <div class="pd-action-row mb-4">
                <button type="button" class="pd-btn-paid" data-bs-toggle="modal" data-bs-target="#paidConfirmModal">
                     Mark as Paid
                </button>
                <button type="button" class="pd-btn-waive" data-bs-toggle="modal" data-bs-target="#waiveModal">
                    Waive Penalty
                </button>
            </div>
        @endif

    </div>

@endsection

@push('modals')
    @if ($status === 'unpaid')
        <div class="modal fade pt-modal" id="paidConfirmModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header justify-content-end">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="pt-modal-icon is-confirm"><i class="bi bi-question-circle"></i></div>
                        <div class="pt-modal-title">Mark as Paid?</div>
                        <div class="pt-modal-subtitle">Are you sure you want to mark this penalty as paid?</div>
                        <div class="pt-info-card">
                            <div class="pt-info-item">
                                <div class="pt-info-label">Borrower</div>
                                <div class="pt-info-value">{{ $p->first_name ?? '—' }} {{ $p->last_name ?? '' }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Installment</div>
                                <div class="pt-info-value">#{{ $p->installment_no }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Amount Due</div>
                                <div class="pt-info-value">₱{{ number_format($p->amount_due, 2) }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Late Fee</div>
                                <div class="pt-info-value">₱{{ number_format($p->penalty_amount, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="pt-btn-cancel" data-bs-dismiss="modal">No, Cancel</button>
                        <button type="button" class="pt-btn-confirm-paid"
                            data-mark-paid-url="{{ route('admin.penalty-mark-paid', $p->id) }}"
                            data-redirect="{{ route('admin.penalty-tracking') }}">
                            <i class="bi bi-check-lg"></i> Yes, Mark as Paid
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade pt-modal" id="waiveModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.penalty-waive', $p->id) }}" class="pt-waive-form"
                        data-redirect="{{ route('admin.penalty-tracking') }}">
                        @csrf
                        <div class="modal-header justify-content-end">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="pt-modal-icon"><i class="bi bi-exclamation-triangle"></i></div>
                            <div class="pt-modal-title">Waive Penalty</div>
                            <div class="pt-modal-subtitle">This action will permanently waive the selected penalty.</div>
                            <div class="pt-info-card">
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Borrower</div>
                                    <div class="pt-info-value">{{ $p->first_name ?? '—' }} {{ $p->last_name ?? '' }}
                                    </div>
                                </div>
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Late Fee</div>
                                    <div class="pt-info-value">₱{{ number_format($p->penalty_amount, 2) }}</div>
                                </div>
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Days Late</div>
                                    <div class="pt-info-value">{{ $p->days_late }} Days</div>
                                </div>
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Installment</div>
                                    <div class="pt-info-value">#{{ $p->installment_no }}</div>
                                </div>
                            </div>
                            <label class="pt-form-label mt-3">Reason for Waiver <span class="req">*</span></label>
                            <textarea name="waived_reason" class="pt-textarea" rows="4"
                                placeholder="Enter the reason for waiving this penalty..." required></textarea>
                            <div class="pt-warning-line">
                                <i class="bi bi-exclamation-triangle-fill"></i> This action cannot be undone.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="pt-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="pt-btn-confirm-waive">
                                <i class="bi bi-check-lg"></i> Confirm Waive
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endpush

@section('page-scripts')
    <script src="{{ asset('functions/penalty-tracking-detail.js') }}"></script>
@endsection
