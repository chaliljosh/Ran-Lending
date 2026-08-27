@extends('admin.layout')

@section('title', 'Ongoing Loan Detail')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-detail.css') }}" />
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
            <div class="page-header-title"><h5 class="m-b-10">Ongoing Loan Detail</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                <li class="breadcrumb-item">Loan #{{ $applicationId }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('admin.ongoing-loans') }}" class="btn btn-light"><i class="feather-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="main-content">

        {{-- ============ BORROWER STRIP ============ --}}
        <div class="borrower-strip">
            <div class="bs-left">
                <div class="loan-avatar">{{ $initials }}</div>
                <div class="bs-info">
                    <h5>{{ $app->first_name }} {{ $app->last_name }}</h5>
                    <div class="bs-meta">
                        <span>{{ $app->email }}</span>
                        <span class="bs-dot">&middot;</span>
                        <span>Loan ID: <strong>LN-{{ str_pad($applicationId, 5, '0', STR_PAD_LEFT) }}</strong></span>
                    </div>
                </div>
            </div>
            <div class="bs-right">
                <span class="badge badge-active">Active</span>
            </div>
        </div>

        {{-- ============ STAT CARDS ROW ============ --}}
        <div class="row old-stat-row">
            <div class="col-6 col-xl-3">
                <div class="card stretch stretch-full old-stat-card">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar-text avatar-md bg-soft-primary text-primary">
                                <i class="feather-file-text"></i>
                            </div>
                            <div>
                                <div class="old-stat-label">Total Loan Amount</div>
                                <div class="old-stat-value">&#8369;{{ number_format($app->total_amount ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card stretch stretch-full old-stat-card">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar-text avatar-md bg-soft-success text-success">
                                <i class="feather-check-circle"></i>
                            </div>
                            <div>
                                <div class="old-stat-label">Amount Paid</div>
                                <div class="old-stat-value">&#8369;<span id="olsAmountPaidStat">{{ number_format($amountCollected, 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card stretch stretch-full old-stat-card">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar-text avatar-md bg-soft-warning text-warning">
                                <i class="feather-pie-chart"></i>
                            </div>
                            <div>
                                <div class="old-stat-label">Remaining Balance</div>
                                <div class="old-stat-value">&#8369;<span id="olsRemainingStat">{{ number_format($remainingAmount, 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card stretch stretch-full old-stat-card">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar-text avatar-md bg-soft-purple text-purple">
                                <i class="feather-calendar"></i>
                            </div>
                            <div>
                                <div class="old-stat-label">Due In</div>
                                <div class="old-stat-value" id="olsDueInStat">
                                    @if(!$nextDue)
                                        &mdash;
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

        <div class="loan-detail-grid">

            {{-- ============ LOAN SUMMARY ============ --}}
            <div class="panel loan-summary-panel">
                <div class="panel-header"><i class="feather-file-text me-2"></i>Loan Summary</div>
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
                                <span id="olsPaidCount">{{ $paidCount }}</span> / <span id="olsTotalCount">{{ $totalCount }}</span> completed
                            </div>
                            <div class="lsc-progress-bar">
                                <div class="lsc-progress-fill" id="olsProgressFill" style="width: {{ $totalCount > 0 ? round(($paidCount / $totalCount) * 100) : 0 }}%;"></div>
                            </div>
                        </div>
                        <div class="old-next-due-box" id="olsNextDueBox" style="{{ $nextDue ? '' : 'display:none;' }}">
                            <span class="old-next-due-label">Next Due</span>
                            <span class="old-next-due-date">
                                <i class="feather-calendar"></i>
                                <span id="olsNextDueDate">{{ $nextDue ? \Carbon\Carbon::parse($nextDue->due_date)->format('M d, Y') : '' }}</span>
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ============ ADMIN REVIEW STATUS ============ --}}
            <div class="panel loan-review-panel">
                <div class="panel-header d-flex align-items-center justify-content-between">
                    <span><i class="feather-shield me-2"></i>Admin Review Status</span>
                    @if($allReviews->count() > 3)
                        <a href="#" class="old-view-all-link">View All <i class="feather-chevron-right"></i></a>
                    @endif
                </div>
                <div class="panel-body">
                    @if($allReviews->count() > 0)
                        @php $avatarColors = ['is-purple', 'is-blue']; @endphp
                        @foreach($allReviews as $r)
                        @php $rInitials = strtoupper(substr($r->admin_name, 0, 2)); @endphp
                        <div class="review-item {{ $r->decision === 'approved' ? 'is-approved' : 'is-rejected' }}">
                            <div class="review-avatar {{ $avatarColors[$loop->index % 2] }}">{{ $rInitials }}</div>
                            <div class="review-body">
                                <span class="review-name">{{ $r->admin_name }}</span>
                                @if($r->remarks)
                                    <span class="review-remark">{{ $r->remarks }}</span>
                                @endif
                                <span class="review-time">{{ \Carbon\Carbon::parse($r->reviewed_at)->format('M d, Y \a\t g:i A') }}</span>
                            </div>
                            <span class="badge {{ $r->decision === 'approved' ? 'badge-approved' : 'badge-rejected' }}">
                                {{ ucfirst($r->decision) }}
                            </span>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted empty-note">No admin decisions recorded for this loan yet.</p>
                    @endif

                    @if($comments->count() > 0)
                        <p class="comments-label">Comments</p>
                        @foreach($comments as $c)
                        @php $cInitials = strtoupper(substr($c->admin_name, 0, 2)); @endphp
                        <div class="review-item is-comment">
                            <div class="review-avatar {{ $c->admin_id == $adminId ? 'mine' : '' }}">{{ $cInitials }}</div>
                            <div class="review-body">
                                <span class="review-name">{{ $c->admin_name }} {{ $c->admin_id == $adminId ? '(You)' : '' }}</span>
                                <span class="review-remark">{{ $c->comment }}</span>
                                <span class="review-time">{{ \Carbon\Carbon::parse($c->created_at)->format('M d, Y \a\t g:i A') }}</span>
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
                    <div class="ols-sched-icon"><i class="feather-calendar"></i></div>
                    <div>
                        <div class="ols-sched-title">Payment Schedule</div>
                        <div class="ols-sched-subtitle">View and manage the borrower's payment installments.</div>
                    </div>
                </div>
                <div class="ols-sched-count-pill">
                    <i class="feather-calendar"></i>
                    <span><strong id="olsScheduledCount">{{ $paymentSchedule->count() }}</strong> of <strong id="olsScheduledTotal">{{ $totalCount }}</strong> payments scheduled</span>
                </div>
            </div>

            @if($paymentSchedule->count() > 0)
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
                        @foreach($paymentSchedule as $s)
                        @php
                            $isPaid = !is_null($s->paid_at);
                            $isOverdue = !$isPaid && \Carbon\Carbon::parse($s->due_date)->isPast();
                            $rowStatus = $isPaid ? 'paid' : ($isOverdue ? 'overdue' : 'upcoming');
                            $dueCarbon = \Carbon\Carbon::parse($s->due_date);
                            $daysDiff = (int) now()->startOfDay()->diffInDays($dueCarbon->copy()->startOfDay(), false);
                            if ($isPaid) {
                                $dueSubtext = 'Paid';
                            } elseif ($daysDiff < 0) {
                                $dueSubtext = abs($daysDiff) . ' ' . (abs($daysDiff) == 1 ? 'day' : 'days') . ' overdue';
                            } elseif ($daysDiff == 0) {
                                $dueSubtext = 'Due today';
                            } else {
                                $dueSubtext = 'in ' . $daysDiff . ' ' . ($daysDiff == 1 ? 'day' : 'days');
                            }
                            $paidPct = $s->amount_due > 0 ? min(100, round(($s->amount_paid / $s->amount_due) * 100)) : 0;
                        @endphp
                        <tr data-schedule-row="{{ $s->id }}" data-page-row>
                            <td><span class="ols-row-num">{{ $s->installment_no }}</span></td>
                            <td>
                                <div class="ols-due-date">{{ $dueCarbon->format('M d, Y') }}</div>
                                <div class="ols-due-sub" data-field="due_sub">{{ $dueSubtext }}</div>
                            </td>
                            <td class="ols-amount-due">&#8369;{{ number_format($s->amount_due, 2) }}</td>
                            <td class="ols-amount-paid-cell">
                                <div class="ols-amount-paid-value" data-field="amount_paid">&#8369;{{ number_format($s->amount_paid, 2) }}</div>
                                <div class="ols-mini-progress-track">
                                    <div class="ols-mini-progress-fill" data-field="progress_fill" style="width: {{ $paidPct }}%;"></div>
                                </div>
                                <div class="ols-mini-progress-pct" data-field="progress_pct">{{ $paidPct }}%</div>
                            </td>
                            <td data-field="paid_on">{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                            <td data-field="status">
                                @if($rowStatus === 'paid')
                                    <span class="ols-status-pill is-paid"><i class="feather-check-circle"></i> Paid</span>
                                @elseif($rowStatus === 'overdue')
                                    <span class="ols-status-pill is-overdue"><i class="feather-alert-circle"></i> Overdue</span>
                                @else
                                    <span class="ols-status-pill is-upcoming"><i class="feather-clock"></i> Upcoming</span>
                                @endif
                            </td>
                            <td class="text-end" data-field="action">
                                <div class="ols-actions-cell">
                                    @if(!$isPaid)
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
                                        <button type="button" class="ols-kebab-btn" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="feather-chevron-down"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end ols-kebab-menu">
                                            <li><a class="dropdown-item" href="#">View Installment Details</a></li>
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

            <div class="ols-sched-footnote">
                <i class="feather-info"></i>
                <span>All payments must be recorded on or before their due dates.</span>
            </div>

            <div class="ols-pagination" id="olsPagination" style="display:none;">
                <button type="button" class="ols-page-arrow" id="olsPrevPage"><i class="feather-chevron-left"></i></button>
                <span class="ols-page-indicator" id="olsPageIndicator">1</span>
                <button type="button" class="ols-page-arrow" id="olsNextPage"><i class="feather-chevron-right"></i></button>
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
     Pushed into the layout's @stack('modals') slot — rendered
     OUTSIDE <main class="nxl-container">, same place as the
     shared My Profile / Account Settings / Change Password
     modals. This matters because theme.min.css applies a blur
     filter to .nxl-container while a Bootstrap modal is open
     (body.modal-open); a modal nested INSIDE that container gets
     blurred right along with the page behind it, which is why
     the modal looked "hidden behind the blur" before this fix.
============================================================ --}}
@push('modals')
    <div class="modal fade nxl-account-modal" id="olsMarkPaidConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="ols-confirm-icon"><i class="feather-help-circle"></i></div>
                    <div class="ols-confirm-title">Mark this payment as paid?</div>
                    <div class="ols-confirm-desc">
                        Installment <strong id="olsConfirmInstallmentNo">#1</strong> of
                        <strong>&#8369;<span id="olsConfirmAmount">0.00</span></strong>
                        will be recorded as paid today. This cannot be undone.
                    </div>
                </div>
                <div class="modal-footer nxl-pw-modal-footer">
                    <button type="button" class="nxl-pw-btn-discard" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="nxl-pw-btn-apply" id="olsConfirmMarkPaidBtn">
                        <i class="feather-check"></i> Yes, Mark as Paid
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@section('page-scripts')
    <script src="{{ asset('functions/ongoing-loan-detail.js') }}"></script>
@endsection