@extends('admin.layout')

@section('title', 'Penalty Details')

@section('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/admin/css/penalty-tracking-detail.css') }}?v={{ time() }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
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

        {{-- ── LAHAT NG PENALTIES NG LOAN NA ITO ── --}}
        @if (!empty($loanPenalties) && $loanPenalties->count() > 1)
            <div class="pd-card mb-4">
                <div class="pd-card-header">
                    <div class="pd-card-title">All Penalties on this Loan</div>
                </div>
                <div class="pd-card-body p-0">
                    <div class="pd-table-scroll">
                        <table class="pd-table">
                            <thead>
                                <tr>
                                    <th>Installment</th>
                                    <th>Due Date</th>
                                    <th>Days Late</th>
                                    <th>Penalty</th>
                                    <th>Status</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($loanPenalties as $lp)
                                    <tr>
                                        <td>#{{ $lp->installment_no }}</td>
                                        <td>{{ \Carbon\Carbon::parse($lp->due_date)->format('M d, Y') }}</td>
                                        <td>{{ $lp->status === 'unpaid' ? (int) $lp->days_late . ' days' : '—' }}</td>
                                        <td class="fw-600">₱{{ number_format($lp->penalty_amount, 2) }}</td>
                                        <td><span class="pd-status-badge pd-badge-{{ $lp->status }}">{{ ucfirst($lp->status) }}</span></td>
                                        <td class="text-end">
                                            @if ($lp->id === $p->id)
                                                <span class="pd-borrower-sub">Viewing</span>
                                            @else
                                                <a href="{{ route('admin.penalty-tracking-detail', [$p->loan_uuid, $lp->installment_no]) }}" class="pd-back-btn">View</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── PENALTY STATUS (read-only, paid/waived lang) ── --}}
        @if ($status !== 'unpaid')
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
                                    Payment is still pending. It is settled on the Overdue Payment page.
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($status === 'paid')
                        <div class="pd-status-box">
                            <div class="pd-status-box-icon is-blue"><i class="bi bi-calendar-check"></i></div>
                            <div>
                                <div class="pd-status-box-label">Paid On</div>
                                <div class="pd-status-box-value">
                                    {{ \Carbon\Carbon::parse($p->updated_at)->format('M d, Y') }}</div>
                                <div class="pd-status-box-desc">Date when the penalty was fully paid.</div>
                            </div>
                        </div>
                    @elseif($status === 'waived')
                        <div class="pd-status-box">
                            <div class="pd-status-box-icon is-blue"><i class="bi bi-calendar-check"></i></div>
                            <div>
                                <div class="pd-status-box-label">Waived On</div>
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
        @endif

    </div>

@endsection

{{-- Wala nang JS at modals dito. View-only na ang page na ito. --}}