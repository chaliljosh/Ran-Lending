@extends('admin.layout')

@section('title', 'Ongoing Loan Detail')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-detail.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/avatar-placeholder.css') }}" />
@endsection

@section('content')

    @php
        $initials = strtoupper(substr($app->first_name ?? 'B', 0, 1) . substr($app->last_name ?? 'R', 0, 1));
        $remainingAmount = max(0, ($app->total_amount ?? 0) - $amountCollected);
        $nextDue = $paymentSchedule->firstWhere('paid_at', null);
        $nextIsOverdue = $nextDue && \Carbon\Carbon::parse($nextDue->due_date)->isPast();
        $dueInDays = $nextDue ? (int) now()->diffInDays(\Carbon\Carbon::parse($nextDue->due_date)) : null;
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Ongoing Loan Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                <li class="breadcrumb-item">Loan #{{ $applicationId }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('admin.ongoing-loans') }}" class="btn btn-light">
            Back</a>
        </div>
    </div>

    <div class="main-content">

        {{-- ============ MERGED HEADER CARD ============
             Borrower identity (left) + loan stats (right) in one
             card, styled to match Penalty Tracking Detail's header
             (white bg, thin border, soft shadow, plain stat items
             with vertical dividers instead of separate icon cards). --}}
        <div class="olh-card">
            <div class="olh-left">
                <div class="olh-avatar js-borrower-avatar">
                    @if (!empty($borrowerProfilePicture))
                        <img src="{{ $borrowerProfilePicture }}" alt="{{ $app->first_name }} {{ $app->last_name }}">
                    @endif
                </div>
                <div class="olh-info">
                    <div class="olh-name-row">
                        <span class="olh-name">{{ $app->first_name }} {{ $app->last_name }}</span>
                        <span class="badge badge-active olh-status-badge">Active</span>
                    </div>
                    <div class="olh-meta-row">
                        <span><strong>Loan ID:</strong> LN-{{ str_pad($applicationId, 5, '0', STR_PAD_LEFT) }}</span>
                        <span class="olh-meta-sep">&bull;</span>
                        <span><strong>Email:</strong> {{ $app->email }}</span>
                    </div>
                </div>
            </div>

            <div class="olh-stats">
                <div class="olh-stat">
                    <div class="olh-stat-value">&#8369;{{ number_format($app->total_amount ?? 0, 2) }}</div>
                    <div class="olh-stat-label">Total Loan Amount</div>
                </div>
                <div class="olh-stat">
                    <div class="olh-stat-value">&#8369;<span
                            id="olsAmountPaidStat">{{ number_format($amountCollected, 2) }}</span></div>
                    <div class="olh-stat-label">Amount Paid</div>
                </div>
                <div class="olh-stat">
                    <div class="olh-stat-value">&#8369;<span
                            id="olsRemainingStat">{{ number_format($remainingAmount, 2) }}</span></div>
                    <div class="olh-stat-label">Remaining Balance</div>
                </div>
                <div class="olh-stat">
                    <div class="olh-stat-value" id="olsDueInStat">
                        @if (!$nextDue)
                            &mdash;
                        @elseif($nextIsOverdue)
                            Overdue
                        @else
                            {{ $dueInDays }} {{ $dueInDays == 1 ? 'day' : 'days' }}
                        @endif
                    </div>
                    <div class="olh-stat-label">Due In</div>
                </div>
            </div>
        </div>

        <div class="loan-detail-grid">

            {{-- ============ LOAN SUMMARY ============ --}}
            <div class="panel loan-summary-panel">
                <div class="panel-header">Loan Summary</div>
                <div class="panel-body">

                    <div class="loan-info-grid">
                        <div class="info-item2">
                            <label>Term</label>
                            <span>{{ $app->loan_term ?? '—' }} {{ $app->loan_term ? 'months' : '' }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Interest Rate</label>
                            <span>{{ $interestRate }}%</span>
                        </div>
                        <div class="info-item2">
                            <label>Total Interest</label>
                            <span>&#8369;{{ number_format($totalInterest, 2) }}</span>
                        </div>
                        <div class="info-item2 is-primary">
                            <label>Total to Pay</label>
                            <span>&#8369;{{ number_format($app->total_amount ?? 0, 2) }}</span>
                        </div>
                    </div>

                    <div class="loan-info-grid secondary">
                        <div class="info-item2">
                            <label>Monthly Payment</label>
                            <span>&#8369;{{ number_format($monthlyPayment, 2) }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Purpose</label>
                            <span>{{ ucfirst(str_replace('_', ' ', $app->loan_purpose ?? '—')) }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Applied On</label>
                            <span>{{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</span>
                        </div>
                    </div>

                    {{-- ============ PAYMENT PROGRESS ROW (progress bar + Next Due box) ============ --}}
                    <div class="old-progress-row">
                        <div class="old-progress-main">
                            <p class="lsc-progress-title">Payment Progress</p>
                            <div class="old-progress-count">
                                <span id="olsPaidCount">{{ $paidCount }}</span> / <span
                                    id="olsTotalCount">{{ $totalCount }}</span> completed
                            </div>
                            <div class="lsc-progress-bar">
                                <div class="lsc-progress-fill" id="olsProgressFill"
                                    style="width: {{ $totalCount > 0 ? round(($paidCount / $totalCount) * 100) : 0 }}%;">
                                </div>
                            </div>
                        </div>
                        <div class="old-next-due-box" id="olsNextDueBox" style="{{ $nextDue ? '' : 'display:none;' }}">
                            <span class="old-next-due-label">Next Due</span>
                            <span class="old-next-due-date">
                                <i class="feather-calendar"></i>
                                <span
                                    id="olsNextDueDate">{{ $nextDue ? \Carbon\Carbon::parse($nextDue->due_date)->format('M d, Y') : '' }}</span>
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ============ ADMIN REVIEW STATUS ============ --}}
            <div class="panel loan-review-panel">
                <div class="panel-header d-flex align-items-center justify-content-between">
                    <span>Admin Review Status</span>
                    @if ($allReviews->count() > 3)
                        <a href="#" class="old-view-all-link">View All <i class="feather-chevron-right"></i></a>
                    @endif
                </div>
                <div class="panel-body">
                    @if ($allReviews->count() > 0)
                        @foreach ($allReviews as $r)
                            <div class="review-item {{ $r->decision === 'approved' ? 'is-approved' : 'is-rejected' }}">
                                <div class="review-avatar">
                                    @if (
                                        !empty($r->admin_profile_picture) &&
                                            \Illuminate\Support\Facades\Storage::disk('public')->exists($r->admin_profile_picture))
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($r->admin_profile_picture) }}"
                                            alt="{{ $r->admin_name }}">
                                    @else
                                        <i class="feather-user"></i>
                                    @endif
                                </div>
                                <div class="review-body">
                                    <span class="review-name">{{ $r->admin_name }}</span>
                                    @if ($r->remarks)
                                        <span class="review-remark">{{ $r->remarks }}</span>
                                    @endif
                                    <span
                                        class="review-time">{{ \Carbon\Carbon::parse($r->reviewed_at)->format('M d, Y \a\t g:i A') }}</span>
                                </div>
                                <span
                                    class="badge {{ $r->decision === 'approved' ? 'badge-approved' : 'badge-rejected' }}">
                                    {{ ucfirst($r->decision) }}
                                </span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted empty-note">No admin decisions recorded for this loan yet.</p>
                    @endif

                    @if ($comments->count() > 0)
                        <p class="comments-label">Comments</p>
                        @foreach ($comments as $c)
                            <div class="review-item is-comment">
                                <div class="review-avatar {{ $c->admin_id == $adminId ? 'mine' : '' }}">
                                    @if (
                                        !empty($c->admin_profile_picture) &&
                                            \Illuminate\Support\Facades\Storage::disk('public')->exists($c->admin_profile_picture))
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($c->admin_profile_picture) }}"
                                            alt="{{ $c->admin_name }}">
                                    @else
                                        <i class="feather-user"></i>
                                    @endif
                                </div>
                                <div class="review-body">
                                    <span class="review-name">{{ $c->admin_name }}
                                        {{ $c->admin_id == $adminId ? '(You)' : '' }}</span>
                                    <span class="review-remark">{{ $c->comment }}</span>
                                    <span
                                        class="review-time">{{ \Carbon\Carbon::parse($c->created_at)->format('M d, Y \a\t g:i A') }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endif
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
                    <span><strong id="olsScheduledCount">{{ $paymentSchedule->count() }}</strong> of <strong
                            id="olsScheduledTotal">{{ $totalCount }}</strong> payments scheduled</span>
                </div>
            </div>

            @if ($paymentSchedule->count() > 0)
                <div class="table-responsive">
                    <table class="ols-sched-table" id="olsScheduleTable">
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
                        <tbody id="olsScheduleTbody">
                            @foreach ($paymentSchedule as $s)
                                @php
                                    $isPaid = !is_null($s->paid_at);
                                    $isOverdue = !$isPaid && \Carbon\Carbon::parse($s->due_date)->isPast();
                                    $rowStatus = $isPaid ? 'paid' : ($isOverdue ? 'overdue' : 'upcoming');
                                    $dueCarbon = \Carbon\Carbon::parse($s->due_date);
                                    $daysDiff = (int) now()
                                        ->startOfDay()
                                        ->diffInDays($dueCarbon->copy()->startOfDay(), false);
                                    if ($isPaid) {
                                        $dueSubtext = 'Paid';
                                    } elseif ($daysDiff < 0) {
                                        $dueSubtext =
                                            abs($daysDiff) . ' ' . (abs($daysDiff) == 1 ? 'day' : 'days') . ' overdue';
                                    } elseif ($daysDiff == 0) {
                                        $dueSubtext = 'Due today';
                                    } else {
                                        $dueSubtext = 'in ' . $daysDiff . ' ' . ($daysDiff == 1 ? 'day' : 'days');
                                    }
                                    $paidPct =
                                        $s->amount_due > 0
                                            ? min(100, round(($s->amount_paid / $s->amount_due) * 100))
                                            : 0;
                                @endphp
                                <tr data-schedule-row="{{ $s->id }}" data-page-row>
                                    <td><span class="ols-row-num">{{ $s->installment_no }}</span></td>
                                    <td>
                                        <div class="ols-due-date">{{ $dueCarbon->format('M d, Y') }}</div>
                                        <div class="ols-due-sub" data-field="due_sub">{{ $dueSubtext }}</div>
                                    </td>
                                    <td class="ols-amount-due">&#8369;{{ number_format($s->amount_due, 2) }}</td>
                                    <td class="ols-amount-paid-cell">
                                        <div class="ols-amount-paid-value" data-field="amount_paid">
                                            &#8369;{{ number_format($s->amount_paid, 2) }}</div>
                                        <div class="ols-mini-progress-track">
                                            <div class="ols-mini-progress-fill" data-field="progress_fill"
                                                style="width: {{ $paidPct }}%;"></div>
                                        </div>
                                        <div class="ols-mini-progress-pct" data-field="progress_pct">{{ $paidPct }}%
                                        </div>
                                    </td>
                                    <td data-field="paid_on">
                                        {{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                                    <td data-field="status">
                                        @if ($rowStatus === 'paid')
                                            <span class="ols-status-pill is-paid">
                                                Paid</span>
                                        @elseif($rowStatus === 'overdue')
                                            <span class="ols-status-pill is-overdue">
                                                Overdue</span>
                                        @else
                                            <span class="ols-status-pill is-upcoming">
                                                Upcoming</span>
                                        @endif
                                    </td>
                                    <td class="text-end" data-field="action">
                                        <div class="ols-actions-cell">
                                            @if (!$isPaid)
                                                <button type="button" class="btn-record-payment"
                                                    data-schedule-id="{{ $s->id }}"
                                                    data-installment-no="{{ $s->installment_no }}"
                                                    data-amount-due="{{ number_format($s->amount_due, 2) }}">
                                                    <i class="feather-calendar"></i> Record Payment
                                                </button>
                                            @else
                                                <span class="sched-paid-note">Recorded</span>
                                            @endif
                                            <div class="ols-kebab-wrap">
                                                <button type="button" class="ols-kebab-btn" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="feather-chevron-down"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end ols-kebab-menu">
                                                    <li><a class="dropdown-item" href="#">View Installment
                                                            Details</a></li>
                                                    <li><a class="dropdown-item" href="#">Add Note</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>



                <div class="ols-pagination" id="olsPagination" style="display:none;">
                    <button type="button" class="ols-page-arrow" id="olsPrevPage"><i
                            class="feather-chevron-left"></i></button>
                    <span class="ols-page-indicator" id="olsPageIndicator">1</span>
                    <button type="button" class="ols-page-arrow" id="olsNextPage"><i
                            class="feather-chevron-right"></i></button>
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
============================================================ --}}
@push('modals')
    <div class="modal fade nxl-account-modal" id="olsMarkPaidConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ols-confirm-modal">
                <div class="modal-body text-center py-4">
                    <div class="ols-confirm-title">Mark this payment as paid?</div>
                    <div class="ols-confirm-desc">
                        Installment <strong id="olsConfirmInstallmentNo">#1</strong> of
                        <strong>&#8369;<span id="olsConfirmAmount">0.00</span></strong>
                        will be recorded as paid today.
                    </div>
                </div>
                <div class="modal-footer nxl-pw-modal-footer">
                    <button type="button" class="nxl-pw-btn-discard" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="nxl-pw-btn-apply" id="olsConfirmMarkPaidBtn">
                        <span class="ols-confirm-btn-label">Mark as Paid</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@section('page-scripts')
    <script src="{{ asset('functions/ongoing-loan-detail.js') }}"></script>
@endsection