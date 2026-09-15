@extends('admin.layout')

@section('title', 'Active Borrower Detail')

@section('page-styles')
    {{-- Reuses section-card / info-label / table-loan-summary / ld-schedule-*
         / borrower-avatar / badges / sidebar-highlight / dark-mode / footer
         rules already defined here — nothing below duplicates them. --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-details.css') }}">
    {{-- Standalone, page-specific additions only (top summary card,
         horizontal loan summary grid, standing badge, progress bar) --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/active-borrower-detail.css') }}">
@endsection

@section('content')

    @php
        $totalToPay = $app->total_amount ?? 0;
        $totalPaidAmount = $paymentSchedule->sum('amount_paid');
        $remainingBalance = max(0, $totalToPay - $totalPaidAmount);

        $nextPayment = $paymentSchedule->first(fn($s) => is_null($s->paid_at));
        $nextIsOverdue = $nextPayment && \Carbon\Carbon::parse($nextPayment->due_date)->isPast();
        $dueInDays = $nextPayment ? \Carbon\Carbon::parse($nextPayment->due_date)->diffInDays(now()) : null;
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Active Borrower Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.active-borrowers') }}">Borrowers</a></li>
                <li class="breadcrumb-item">{{ $app->first_name }} {{ $app->last_name }}</li>
                <li class="breadcrumb-item">Loan Details</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('admin.active-borrowers') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="main-content">

        {{-- ============================================
             TOP SUMMARY CARD
             Left: avatar + name + Application ID/Email + badges
             Right: stat row (Total to Pay / Total Paid / Remaining / Due In),
             evenly spaced across the full remaining width.
        ============================================ --}}
        <div class="abd-summary-card">
            <div class="abd-summary-left">
                <div class="borrower-avatar abd-summary-avatar js-borrower-avatar">
                    @if (!empty($borrowerProfilePicture))
                        <img src="{{ $borrowerProfilePicture }}" alt="{{ $app->first_name }} {{ $app->last_name }}">
                    @else
                        {{ strtoupper(substr($app->first_name ?? '', 0, 1) . substr($app->last_name ?? '', 0, 1)) }}
                    @endif
                </div>
                <div class="abd-summary-info">
                    <h5>{{ $app->first_name }} {{ $app->last_name }}</h5>
                    <div class="abd-summary-meta">
                        <span><strong>Application ID:</strong> #{{ $app->application_id }}</span>
                        <span><strong>Email:</strong> {{ $app->email }}</span>
                    </div>
                    <div class="abd-summary-badges">
                        <span class="badge badge-approved px-3 py-2">Active</span>
                        <span class="abd-standing is-{{ $standing }}">{{ $standingLabel }}</span>
                    </div>
                </div>
            </div>
            <div class="abd-summary-right">
                <div class="abd-summary-stat">
                    <span class="abd-summary-stat-value">₱{{ number_format($totalToPay, 2) }}</span>
                    <span class="abd-summary-stat-label">Total to Pay</span>
                </div>
                <div class="abd-summary-stat">
                    <span class="abd-summary-stat-value">₱{{ number_format($totalPaidAmount, 2) }}</span>
                    <span class="abd-summary-stat-label">Total Paid</span>
                </div>
                <div class="abd-summary-stat">
                    <span class="abd-summary-stat-value">₱{{ number_format($remainingBalance, 2) }}</span>
                    <span class="abd-summary-stat-label">Remaining Balance</span>
                </div>
                <div class="abd-summary-stat">
                    <span class="abd-summary-stat-value">
                        @if (!$nextPayment)
                            —
                        @elseif($nextIsOverdue)
                            Overdue
                        @else
                            {{ $dueInDays }} {{ $dueInDays == 1 ? 'day' : 'days' }}
                        @endif
                    </span>
                    <span class="abd-summary-stat-label">Due In</span>
                </div>
            </div>
        </div>

        {{-- ============================================
             LOAN SUMMARY — horizontal info grid,
             matched to the Personal Information layout style.
        ============================================ --}}
        <div class="section-card mb-3">
            <p class="section-title">Loan Summary</p>
            <div class="abd-info-row">
                <div class="abd-info-item">
                    <label>Application ID</label>
                    <span>#{{ $app->application_id }}</span>
                </div>
                <div class="abd-info-item">
                    <label>Loan Amount</label>
                    <span>₱{{ number_format($app->loan_amount, 2) }}</span>
                </div>
                <div class="abd-info-item">
                    <label>Term</label>
                    <span>{{ $app->loan_term }} months</span>
                </div>
                <div class="abd-info-item">
                    <label>Interest Rate</label>
                    <span>5% / month</span>
                </div>
                <div class="abd-info-item">
                    <label>Total Interest</label>
                    <span>₱{{ number_format($app->total_interest ?? 0, 2) }}</span>
                </div>
                <div class="abd-info-item">
                    <label>Total to Pay</label>
                    <span>₱{{ number_format($totalToPay, 2) }}</span>
                </div>
                <div class="abd-info-item abd-info-highlight">
                    <label>Monthly Payment</label>
                    <span>₱{{ number_format($app->monthly_payment ?? 0, 2) }}</span>
                </div>
                <div class="abd-info-item">
                    <label>Purpose</label>
                    <span>{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}</span>
                </div>
                <div class="abd-info-item">
                    <label>Applied On</label>
                    <span>{{ date('M d, Y', strtotime($app->applied_at)) }}</span>
                </div>
            </div>
        </div>

        {{-- PAYMENT SCHEDULE --}}
        <div class="section-card abd-schedule-card mb-3">
            <p class="section-title">Payment Schedule</p>
            <div class="table-responsive">
                <table class="table table-sm ld-schedule-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Amount Due</th>
                            <th>Amount Paid</th>
                            <th>Paid On</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentSchedule as $s)
                            @php
                                $isOverdue = is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
                                $isPaid = !is_null($s->paid_at);
                                $scheduleBadge = $isPaid
                                    ? 'ld-schedule-paid'
                                    : ($isOverdue
                                        ? 'ld-schedule-late'
                                        : 'ld-schedule-pending');
                                $scheduleLabel = $isPaid ? 'Paid' : ($isOverdue ? 'Overdue' : 'Pending');
                            @endphp
                            <tr>
                                <td>{{ $s->installment_no }}</td>
                                <td>{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}</td>
                                <td>₱{{ number_format($s->amount_due, 2) }}</td>
                                <td>₱{{ number_format($s->amount_paid, 2) }}</td>
                                <td>{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                                <td><span class="ld-schedule-badge {{ $scheduleBadge }}">{{ $scheduleLabel }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No payment schedule generated yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAYMENT PROGRESS --}}
        <div class="section-card">
            <div class="abd-progress-head">
                <span class="abd-progress-label">Payment Progress</span>
                <span class="abd-progress-count">{{ $paidCount }} of {{ $totalCount }} payments completed</span>
            </div>
            <div class="abd-progress-track">
                <div class="abd-progress-fill {{ $progressPct >= 100 ? 'is-complete' : '' }}"
                    style="width: {{ $progressPct }}%;"></div>
            </div>

            @if ($nextPayment)
                <div class="abd-next-payment">
                    <div>
                        <div class="abd-next-payment-label">Next Payment</div>
                        <div class="abd-next-payment-date">
                            {{ \Carbon\Carbon::parse($nextPayment->due_date)->format('M d, Y') }}</div>
                    </div>
                    <div class="abd-next-payment-amount">₱{{ number_format($nextPayment->amount_due, 2) }}</div>
                    <span class="ld-schedule-badge {{ $nextIsOverdue ? 'ld-schedule-late' : 'ld-schedule-pending' }}">
                        {{ $nextIsOverdue ? 'Overdue' : 'Pending' }}
                    </span>
                </div>
            @endif
        </div>

    </div>

@endsection
