@extends('admin.layout')

@section('title', 'Active Borrower Detail')

@section('page-styles')
    {{-- Reuses section-card / info-label / table-loan-summary / ld-schedule-*
         / borrower-avatar / badges / sidebar-highlight / dark-mode / footer
         rules already defined here — nothing below duplicates them. --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-details.css') }}">
    {{-- Standalone, page-specific additions only (stat cards + standing badge + progress bar) --}}
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
            <div class="page-header-title"><h5 class="m-b-10">Active Borrower Detail</h5></div>
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

        {{-- ============ TOP ROW: Profile (left) + Stat Cards (right) ============ --}}
        <div class="row">

            <!-- LEFT COLUMN: Borrower Profile + Loan Summary -->
            <div class="col-lg-4 ld-left-col">
                <div class="ld-sticky-col">

                    <!-- 1. BORROWER PROFILE -->
                    <div class="section-card abd-profile-card mb-3">
                        <div class="abd-profile-row">
                            <div class="borrower-avatar abd-profile-avatar">
                                {{ strtoupper(substr($app->first_name ?? '', 0, 1) . substr($app->last_name ?? '', 0, 1)) }}
                            </div>
                            <div class="abd-profile-info">
                                <div class="abd-profile-name">{{ $app->first_name }} {{ $app->last_name }}</div>
                                <div class="abd-profile-email">{{ $app->email }}</div>
                                <div class="abd-profile-badges">
                                    <span class="badge badge-approved px-3 py-2">Active</span>
                                    <span class="abd-standing is-{{ $standing }}">{{ $standingLabel }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. LOAN SUMMARY (compact) -->
                    <div class="section-card">
                        <p class="section-title"><i class="feather-file-text me-2"></i>Loan Summary</p>
                        <table class="table table-sm table-borderless table-loan-summary mb-0">
                            <tr><td class="info-label">Application ID</td><td class="text-end info-value">#{{ $app->application_id }}</td></tr>
                            <tr><td class="info-label">Loan Amount</td><td class="text-end info-value">₱{{ number_format($app->loan_amount, 2) }}</td></tr>
                            <tr><td class="info-label">Term</td><td class="text-end info-value">{{ $app->loan_term }} months</td></tr>
                            <tr><td class="info-label">Interest Rate</td><td class="text-end info-value">5% / month</td></tr>
                            <tr><td class="info-label">Total Interest</td><td class="text-end info-value">₱{{ number_format($app->total_interest ?? 0, 2) }}</td></tr>
                            <tr><td class="info-label">Total to Pay</td><td class="text-end info-value">₱{{ number_format($totalToPay, 2) }}</td></tr>
                            <tr class="abd-summary-highlight">
                                <td class="info-label">Monthly Payment</td>
                                <td class="text-end"><span class="ld-value-pill abd-pill-emphasis">₱{{ number_format($app->monthly_payment ?? 0, 2) }}</span></td>
                            </tr>
                            <tr><td class="info-label">Purpose</td><td class="text-end info-value">{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}</td></tr>
                            <tr><td class="info-label">Applied On</td><td class="text-end info-value">{{ date('M d, Y', strtotime($app->applied_at)) }}</td></tr>
                        </table>
                    </div>

                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-lg-8">

                {{-- 3. STAT CARDS ROW: Total to Pay / Total Paid / Remaining Balance / Due In --}}
                <div class="row abd-stat-row">
                    <div class="col-6 col-xl-3">
                        <div class="card stretch stretch-full abd-stat-card">
                            <div class="card-body">
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="avatar-text avatar-md bg-soft-primary text-primary">
                                        <i class="feather-file-text"></i>
                                    </div>
                                    <div>
                                        <div class="abd-stat-label">Total to Pay</div>
                                        <div class="abd-stat-value">₱{{ number_format($totalToPay, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-xl-3">
                        <div class="card stretch stretch-full abd-stat-card">
                            <div class="card-body">
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="avatar-text avatar-md bg-soft-success text-success">
                                        <i class="feather-check-circle"></i>
                                    </div>
                                    <div>
                                        <div class="abd-stat-label">Total Paid</div>
                                        <div class="abd-stat-value">₱{{ number_format($totalPaidAmount, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-xl-3">
                        <div class="card stretch stretch-full abd-stat-card">
                            <div class="card-body">
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="avatar-text avatar-md bg-soft-warning text-warning">
                                        <i class="feather-repeat"></i>
                                    </div>
                                    <div>
                                        <div class="abd-stat-label">Remaining Balance</div>
                                        <div class="abd-stat-value">₱{{ number_format($remainingBalance, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-xl-3">
                        <div class="card stretch stretch-full abd-stat-card">
                            <div class="card-body">
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="avatar-text avatar-md bg-soft-purple text-purple">
                                        <i class="feather-calendar"></i>
                                    </div>
                                    <div>
                                        <div class="abd-stat-label">Due In</div>
                                        <div class="abd-stat-value">
                                            @if(!$nextPayment)
                                                —
                                            @elseif($nextIsOverdue)
                                                Overdue
                                            @else
                                                {{ $dueInDays }} {{ $dueInDays == 1 ? 'day' : 'days' }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. PAYMENT SCHEDULE --}}
                <div class="section-card abd-schedule-card mb-3">
                    <p class="section-title"><i class="feather-calendar me-2"></i>Payment Schedule</p>
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
                                    $scheduleBadge = $isPaid ? 'ld-schedule-paid' : ($isOverdue ? 'ld-schedule-late' : 'ld-schedule-pending');
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
                                    <td colspan="6" class="text-center text-muted py-4">No payment schedule generated yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 5. PAYMENT PROGRESS --}}
                <div class="section-card">
                    <div class="abd-progress-head">
                        <span class="abd-progress-label">Payment Progress</span>
                        <span class="abd-progress-count">{{ $paidCount }} of {{ $totalCount }} payments completed</span>
                    </div>
                    <div class="abd-progress-track">
                        <div class="abd-progress-fill {{ $progressPct >= 100 ? 'is-complete' : '' }}" style="width: {{ $progressPct }}%;"></div>
                    </div>

                    @if($nextPayment)
                        <div class="abd-next-payment">
                            <div>
                                <div class="abd-next-payment-label">Next Payment</div>
                                <div class="abd-next-payment-date">{{ \Carbon\Carbon::parse($nextPayment->due_date)->format('M d, Y') }}</div>
                            </div>
                            <div class="abd-next-payment-amount">₱{{ number_format($nextPayment->amount_due, 2) }}</div>
                            <span class="ld-schedule-badge {{ $nextIsOverdue ? 'ld-schedule-late' : 'ld-schedule-pending' }}">
                                {{ $nextIsOverdue ? 'Overdue' : 'Pending' }}
                            </span>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

@endsection