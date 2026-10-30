@extends('admin.layout')

@section('title', 'Ongoing Loan Detail')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-detail.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-release-modal.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-released-modal.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/payment-methods.css') }}?v={{ time() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
@endsection

@section('content')

    @php
        $initials = strtoupper(substr($app->first_name ?? 'B', 0, 1) . substr($app->last_name ?? 'R', 0, 1));
        $remainingAmount = max(0, ($app->total_amount ?? 0) - $amountCollected);
        $nextDue = $paymentSchedule->firstWhere('paid_at', null);
        $nextIsOverdue = $nextDue && \Carbon\Carbon::parse($nextDue->due_date)->isPast();
        $dueInDays = $nextDue ? (int) now()->diffInDays(\Carbon\Carbon::parse($nextDue->due_date)) : null;
        $loanCode = strtoupper(substr($app->uuid, 0, 8));

        // Ilang araw pagkatapos i-record ang payment pwede pang i-void (galing sa AdminController)
        $voidWindowDays = $voidWindowDays ?? 7;

        // Lahat ng bayad (partial + full) kada installment, galing sa ledger (AdminController)
        $paymentsBySchedule = $paymentsBySchedule ?? collect();
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Ongoing Loan Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                <li class="breadcrumb-item">Loan #{{ $loanCode }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('admin.ongoing-loans') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="main-content">

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
                        {{-- Hindi pa na-send ang Proof of Release = hindi pa na-release ang loan --}}
                        <span class="badge badge-active olh-status-badge">{{ empty($app->proof_sent_at) ? 'Awaiting Release' : 'Active' }}</span>
                    </div>
                    <div class="olh-meta-row">
                        <span><strong>Loan ID:</strong> LN-{{ $loanCode }}</span>
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

        {{-- Proof of Release: kapag may proof_sent_at na, "already sent" na ang ipapakita (wala nang button) --}}
        @if (!empty($app->proof_sent_at))
            <div class="olr-card is-sent">
                <div class="olr-icon"><i class="feather-check"></i></div>
                <div class="olr-text">
                    <div class="olr-title">Loan was already released</div>
                    <div class="olr-sub">
                        Release confirmation emailed to {{ $app->email }} on
                        {{ \Carbon\Carbon::parse($app->proof_sent_at)->format('M d, Y \a\t g:i A') }}.
                    </div>
                </div>
                <span class="olr-sent-badge"><i class="feather-check-circle"></i> Sent</span>
            </div>
        @else
            <div class="olr-card">
                <div class="olr-icon"><i class="feather-send"></i></div>
                <div class="olr-text">
                    <div class="olr-title">Proof of Release</div>
                    <div class="olr-sub">Email the borrower proof that the &#8369;{{ number_format($app->loan_amount ?? 0, 2) }} loan was released. The payment schedule will be created once the proof is sent.</div>
                </div>
                <button type="button" class="btn-send-release" id="olsSendProofBtn"
                    data-application-id="{{ $applicationId }}"
                    data-amount="{{ number_format($app->loan_amount ?? 0, 2) }}"
                    data-borrower-name="{{ $app->first_name }} {{ $app->last_name }}"
                    data-borrower-email="{{ $app->email }}">
                    Release Loan
                </button>
            </div>
        @endif

        <div class="loan-detail-grid">

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
                            <label>Loan Amount</label>
                            <span>&#8369;{{ number_format($app->loan_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Monthly Payment</label>
                            <span>&#8369;{{ number_format($monthlyPayment, 2) }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Purpose</label>
                            <span>{{ $appDisplay['purpose'] }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Applied On</label>
                            <span>{{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</span>
                        </div>
                    </div>

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

        {{-- Assigned Payment Method: admin picks which account this borrower pays to --}}
        @include('admin.partials.assigned-payment-method')

        <div class="panel payment-schedule-panel">
            <div class="ols-sched-header">
                <div class="ols-sched-header-left">
                    <div>
                        <div class="ols-sched-title">Payment Schedule</div>
                    </div>
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

                                    $borrowerReceipt = $isPaid ? null : $borrowerReceipts->get($s->id);

                                    // Natitirang babayaran (kung may partial na natanggap na)
                                    $balance = max(0, round((float) $s->amount_due - (float) $s->amount_paid, 2));

                                    $voidReq = $voidRequests->get($s->id);
                                    $voidVoteRows = $voidReq ? $voidVotes->get($voidReq->id, collect()) : collect();
                                    $voidApprovals = $voidVoteRows->where('decision', 'approved')->count();
                                    $voidVotesJson = $voidVoteRows
                                        ->map(fn($v) => [
                                            'name' => $v->admin_name,
                                            'decision' => $v->decision,
                                            'remarks' => $v->remarks,
                                            'time' => \Carbon\Carbon::parse($v->reviewed_at)->format('M d, Y g:i A'),
                                            'me' => (int) $v->admin_id === (int) $adminId,
                                        ])
                                        ->values();

                                    // 7-day void window: nagsisimula sa "Recorded" date (updated_at) ng payment.
                                    // Pagkalipas nito, hindi na pwedeng mag-request ng void.
                                    $voidWindowEnd = $isPaid
                                        ? \Carbon\Carbon::parse($s->updated_at ?? $s->paid_at)->addDays($voidWindowDays)
                                        : null;
                                    $voidWindowOpen = $isPaid && now()->lte($voidWindowEnd);
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
                                        @if (!$isPaid && (float) $s->amount_paid > 0)
                                            <div class="ols-due-sub">Balance &#8369;{{ number_format(max(0, $s->amount_due - $s->amount_paid), 2) }}</div>
                                        @endif
                                    </td>
                                    <td data-field="paid_on">
                                        {{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                                    <td data-field="status">
                                        @if ($rowStatus === 'paid')
                                            <span class="ols-status-pill is-paid">Paid</span>
                                        @elseif($rowStatus === 'overdue')
                                            <span class="ols-status-pill is-overdue">Overdue</span>
                                        @else
                                            <span class="ols-status-pill is-upcoming">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-end" data-field="action">
                                        <div class="ols-actions-cell">
                                            @if (!$isPaid)
                                                @if ($borrowerReceipt)
                                                    <button type="button" class="btn-view-receipt js-view-borrower-receipt"
                                                        data-schedule-id="{{ $s->id }}"
                                                        data-installment-no="{{ $s->installment_no }}"
                                                        data-amount-due="{{ number_format($s->amount_due, 2) }}"
                                                        data-amount-paid="{{ number_format($s->amount_paid, 2) }}"
                                                        data-balance="{{ number_format($balance, 2) }}"
                                                        data-due="{{ $dueCarbon->format('F j, Y') }}"
                                                        data-submitted="{{ !empty($borrowerReceipt->submitted_at) ? \Carbon\Carbon::parse($borrowerReceipt->submitted_at)->format('F j, Y \a\t g:i A') : '' }}"
                                                        data-paid-date="{{ !empty($borrowerReceipt->user_paid_date) ? \Carbon\Carbon::parse($borrowerReceipt->user_paid_date)->format('F j, Y') : '' }}"
                                                        data-paid-ymd="{{ !empty($borrowerReceipt->user_paid_date) ? \Carbon\Carbon::parse($borrowerReceipt->user_paid_date)->format('Y-m-d') : '' }}"
                                                        data-file-url="{{ route('admin.borrower-receipt-file', $borrowerReceipt->receipt_id) }}"
                                                        data-download-url="{{ route('admin.borrower-receipt-file', ['id' => $borrowerReceipt->receipt_id, 'download' => 1]) }}">
                                                        <i class="feather-file-text"></i> View Receipt
                                                    </button>
                                                @else
                                                    <span class="sched-paid-note">{{ (float) $s->amount_paid > 0 ? 'Awaiting receipt for balance' : 'Awaiting receipt' }}</span>
                                                @endif
                                            @else
                                                @if ($voidReq)
                                                    <span class="ols-void-pending"><i class="feather-clock"></i> Void Pending {{ $voidApprovals }}/{{ $requiredVoidApprovals }}</span>
                                                @else
                                                    <span class="sched-paid-note">Recorded</span>
                                                @endif
                                            @endif

                                            <div class="ols-kebab-wrap {{ $isPaid ? '' : 'd-none' }}">
                                                <button type="button" class="ols-kebab-btn" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="feather-chevron-down"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end ols-kebab-menu">
                                                    <li>
                                                        <button type="button"
                                                            class="dropdown-item js-view-payment-details"
                                                            data-installment-no="{{ $s->installment_no }}"
                                                            data-due="{{ $dueCarbon->format('F j, Y') }}"
                                                            data-amount="{{ number_format($isPaid ? $s->amount_paid : $s->amount_due, 2) }}"
                                                            data-status="{{ $rowStatus }}"
                                                            data-paid="{{ $isPaid ? \Carbon\Carbon::parse($s->paid_at)->format('F j, Y') : '' }}"
                                                            data-recorded="{{ $isPaid && !empty($s->updated_at) ? \Carbon\Carbon::parse($s->updated_at)->format('F j, Y') : '' }}"
                                                            data-payments="{{ json_encode($paymentsBySchedule->get($s->id, [])) }}"
                                                            data-receipt-url="{{ !empty($s->receipt_path) ? route('admin.installment-receipt', $s->id) : '' }}">
                                                            View Payment Details
                                                        </button>
                                                    </li>

                                                    @if ($voidReq)
                                                        <li>
                                                            <hr class="dropdown-divider">
                                                            <button type="button"
                                                                class="dropdown-item ols-item-danger js-view-void-request"
                                                                data-request-id="{{ $voidReq->id }}"
                                                                data-installment-no="{{ $s->installment_no }}"
                                                                data-amount="{{ number_format($voidReq->amount_snapshot, 2) }}"
                                                                data-reason="{{ $voidReq->reason }}"
                                                                data-requester="{{ $voidReq->requester_name }}"
                                                                data-requested-at="{{ \Carbon\Carbon::parse($voidReq->created_at)->format('M d, Y g:i A') }}"
                                                                data-approvals="{{ $voidApprovals }}"
                                                                data-required="{{ $requiredVoidApprovals }}"
                                                                data-is-requester="{{ (int) $voidReq->requested_by === (int) $adminId ? '1' : '0' }}"
                                                                data-votes="{{ json_encode($voidVotesJson) }}">
                                                                View Void Request
                                                            </button>
                                                        </li>
                                                    @endif

                                                    {{-- Request Void: sa unang {{ $voidWindowDays }} araw lang pagkatapos i-record ang payment --}}
                                                    <li class="js-void-item {{ $isPaid && !$voidReq && $voidWindowOpen ? '' : 'd-none' }}">
                                                        <hr class="dropdown-divider">
                                                        <button type="button"
                                                            class="dropdown-item ols-item-danger js-void-payment"
                                                            data-schedule-id="{{ $s->id }}"
                                                            data-installment-no="{{ $s->installment_no }}"
                                                            data-amount="{{ number_format($s->amount_paid, 2) }}">
                                                            Request Void
                                                        </button>
                                                        @if ($voidWindowEnd)
                                                            <span class="dropdown-item-text text-muted"
                                                                style="font-size:0.74rem; padding-top:0;">
                                                                Void allowed until {{ $voidWindowEnd->format('M d, Y') }}
                                                            </span>
                                                        @endif
                                                    </li>

                                                    {{-- Lumipas na ang void period: hindi na pwedeng mag-request --}}
                                                    <li class="{{ $isPaid && !$voidReq && !$voidWindowOpen ? '' : 'd-none' }}">
                                                        <hr class="dropdown-divider">
                                                        <span class="dropdown-item-text text-muted"
                                                            style="font-size:0.78rem;">
                                                            Void period ended
                                                            @if ($voidWindowEnd)
                                                                on {{ $voidWindowEnd->format('M d, Y') }}
                                                            @endif
                                                        </span>
                                                    </li>
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
                    <p class="text-muted empty-note">
                        @if (empty($app->proof_sent_at))
                            The payment schedule will be created once the proof of release is sent.
                        @else
                            No payment schedule found for this loan.
                        @endif
                    </p>
                </div>
            @endif
        </div>

        @if ($voidRequests->count() || $voidHistory->count())
            <div class="panel ols-void-panel">
                <div class="panel-header">Void Requests</div>
                <div class="panel-body">
                    @foreach ($voidRequests->values()->concat($voidHistory) as $r)
                        @php
                            $isPending = $r->status === 'pending';
                            $rVotes = $voidVotes->get($r->id, collect());
                            $rApprovals = $rVotes->where('decision', 'approved')->count();
                            $stateLabel = [
                                'pending' => 'Pending ' . $rApprovals . '/' . $requiredVoidApprovals,
                                'approved' => 'Voided',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled',
                            ][$r->status] ?? ucfirst($r->status);
                            $when = $isPending ? $r->created_at : ($r->resolved_at ?? $r->created_at);
                        @endphp
                        <div class="ols-void-item">
                            <div class="ols-void-main">
                                <div class="ols-void-title">Installment #{{ $r->installment_no }} &middot; &#8369;{{ number_format($r->amount_snapshot, 2) }}</div>
                                <div class="ols-void-reason">{{ $r->reason }}</div>
                                <div class="ols-void-meta">
                                    Requested by {{ $r->requester_name }} &middot; {{ \Carbon\Carbon::parse($when)->format('M d, Y g:i A') }}
                                </div>
                                @if ($rVotes->count())
                                    <div class="ols-void-votes">
                                        @foreach ($rVotes as $v)
                                            <span class="ols-void-vote is-{{ $v->decision }}">{{ $v->admin_name }}: {{ $v->decision }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <span class="ols-void-state is-{{ $r->status }}">{{ $stateLabel }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

@endsection

@push('modals')
    <div class="modal fade nxl-account-modal" id="olsMarkPaidConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ols-confirm-modal">
                <div class="modal-body py-4">
                    <div class="text-center">
                        <div class="ols-confirm-title">Mark this payment as paid?</div>
                        <div class="ols-confirm-desc">
                            Installment <strong id="olsConfirmInstallmentNo">#1</strong> of
                            <strong>&#8369;<span id="olsConfirmAmount">0.00</span></strong>
                            will be recorded as paid on the date below.
                        </div>
                    </div>

                    <div class="ols-field">
                        <label for="olsPaidDateDisplay" class="ols-field-label">Date Paid <span class="ols-req">*</span></label>
                        <div class="ols-date-wrap">
                            <input type="text" id="olsPaidDateDisplay" class="ols-input ols-date-input"
                                placeholder="MM/DD/YYYY" readonly autocomplete="off">
                            <i class="feather-calendar ols-date-icon"></i>
                        </div>
                        <input type="hidden" id="olsPaidDate">
                    </div>

                    <div class="ols-field" id="olsUsingBorrowerReceipt" style="display:none;">
                        <label class="ols-field-label">Receipt</label>
                        <div class="ols-preview">
                            <img id="olsUsingBorrowerThumb" alt="Borrower receipt">
                            <div class="ols-preview-meta">
                                <span>Receipt from the borrower</span>
                                <span>Will be saved as the payment receipt</span>
                            </div>
                            <button type="button" class="ols-preview-remove" id="olsUseOwnReceiptBtn"
                                aria-label="Remove borrower receipt" title="Remove">
                                <i class="feather-x"></i>
                            </button>
                        </div>
                    </div>

                    <div class="ols-form-error" id="olsMarkPaidError" style="display:none;"></div>
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

    {{-- RELEASE CONFIRMATION (Proof of Release). Ang JS ang pumupuno ng pangalan, amount at email
         (data-* ng #olsSendProofBtn). Ang Loan Account ay galing na sa server. --}}
    <div class="modal fade nxl-account-modal" id="olsSendProofModal" tabindex="-1"
        aria-labelledby="olsProofTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content olrm-modal">

                <div class="olrm-body">
                    <div class="olrm-head">
                        <h5 class="olrm-title" id="olsProofTitle">Release Confirmation</h5>
                        <button type="button" class="olrm-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="feather-x"></i>
                        </button>
                    </div>

                    <p class="olrm-desc">
                        Please verify the borrower and loan details before proceeding.
                        Attaching the proof <strong>of release</strong> will initiate the payment schedule.
                    </p>

                    <div class="olrm-summary">
                        <div class="olrm-summary-item">
                            <span class="olrm-label">Borrower</span>
                            <span class="olrm-value" id="olsProofBorrowerName"></span>
                        </div>
                        <div class="olrm-summary-item">
                            <span class="olrm-label">Loan Amount</span>
                            <span class="olrm-value">&#8369;<span id="olsProofAmount">0.00</span></span>
                        </div>
                        <div class="olrm-summary-item">
                            <span class="olrm-label">Loan Account</span>
                            <span class="olrm-value">LN-{{ $loanCode }}</span>
                        </div>
                        <div class="olrm-summary-item">
                            <span class="olrm-label">Recipient Email</span>
                            <span class="olrm-value" id="olsProofBorrowerEmail"></span>
                        </div>
                    </div>

                    <div class="olrm-field">
                        <span class="olrm-field-label">Attach proof of release <span class="ols-req">*</span></span>

                        <label for="olsProofInput" class="ols-dropzone olrm-dropzone" id="olsProofDropzone">
                            <i class="feather-upload-cloud"></i>
                            <span class="ols-dropzone-text">Click to upload image</span>
                            <span class="ols-dropzone-hint">JPG or PNG (up to 5MB)</span>
                        </label>
                        <input type="file" id="olsProofInput" accept="image/jpeg,image/png" hidden>

                        <div class="ols-preview" id="olsProofPreview" style="display:none;">
                            <img id="olsProofPreviewImg" alt="Proof preview">
                            <div class="ols-preview-meta">
                                <span id="olsProofFileName"></span>
                                <span id="olsProofFileSize"></span>
                            </div>
                            <button type="button" class="ols-preview-remove" id="olsProofRemove"
                                aria-label="Remove proof">
                                <i class="feather-x"></i>
                            </button>
                        </div>
                    </div>

                    <div class="ols-form-error" id="olsProofError" style="display:none;"></div>

                    <div class="olrm-note">
                        <span class="olrm-note-icon" aria-hidden="true">i</span>
                        <div class="olrm-note-text">
                            <strong>Important</strong>
                            <p>This action can only be completed once for this loan. Please ensure that all
                                details and the attached proof are correct before confirming.</p>
                        </div>
                    </div>
                </div>

                <div class="olrm-footer">
                    <button type="button" class="olrm-btn olrm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="olrm-btn olrm-btn-confirm" id="olsProofSendBtn">
                        <span class="ols-confirm-btn-label">Confirm &amp; Send</span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- LOAN RELEASED (success modal pagkatapos ma-send ang release confirmation) --}}
    @include('admin.partials.loan-released-modal')

    <div class="modal fade nxl-account-modal" id="olsVoidModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ols-confirm-modal">
                <div class="modal-body py-4">
                    <div class="text-center">
                        <div class="ols-confirm-title">Request to void this payment?</div>
                        <div class="ols-confirm-desc">
                            Installment <strong id="olsVoidInstallmentNo">#1</strong> of
                            <strong>&#8369;<span id="olsVoidAmount">0.00</span></strong>
                            will only be voided once all {{ $requiredVoidApprovals }} admins approve.
                            Your request counts as the first approval. The payment stays recorded until then.
                            A payment can only be voided within {{ $voidWindowDays }} days after it was recorded.
                        </div>
                    </div>

                    <div class="ols-field">
                        <label for="olsVoidReason" class="ols-field-label">Reason <span class="ols-req">*</span></label>
                        <textarea id="olsVoidReason" class="ols-input" rows="3" maxlength="500"
                            placeholder="e.g. Recorded to the wrong borrower"></textarea>
                    </div>

                    <div class="ols-form-error" id="olsVoidError" style="display:none;"></div>
                </div>
                <div class="modal-footer nxl-pw-modal-footer">
                    <button type="button" class="nxl-pw-btn-discard" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="nxl-pw-btn-apply ols-btn-danger" id="olsConfirmVoidBtn">
                        <span class="ols-confirm-btn-label">Submit Request</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade nxl-account-modal" id="olsVoteVoidModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ols-confirm-modal">
                <div class="modal-body py-4">
                    <div class="text-center">
                        <div class="ols-confirm-title">Void Request</div>
                        <div class="ols-confirm-desc">
                            Installment <strong id="olsVvInstallment">#1</strong> of
                            <strong>&#8369;<span id="olsVvAmount">0.00</span></strong><br>
                            Requested by <strong id="olsVvRequester"></strong> on <span id="olsVvDate"></span>
                        </div>
                    </div>

                    <div class="ols-field">
                        <label class="ols-field-label">Reason</label>
                        <div class="ols-vv-reason" id="olsVvReason"></div>
                    </div>

                    <div class="ols-field">
                        <label class="ols-field-label">Votes (<span id="olsVvCount">0</span>/{{ $requiredVoidApprovals }} approved)</label>
                        <div class="ols-vv-votes" id="olsVvVotes"></div>
                    </div>

                    <div class="ols-vv-note" id="olsVvNote" style="display:none;"></div>

                    <div class="ols-field" id="olsVvRemarksField">
                        <label for="olsVvRemarks" class="ols-field-label">Remarks (required when rejecting)</label>
                        <textarea id="olsVvRemarks" class="ols-input" rows="2" maxlength="255"></textarea>
                    </div>

                    <div class="ols-form-error" id="olsVvError" style="display:none;"></div>
                </div>
                <div class="modal-footer nxl-pw-modal-footer">
                    <button type="button" class="nxl-pw-btn-discard" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="nxl-pw-btn-discard" id="olsVvCancel" style="display:none;">Cancel Request</button>
                    <button type="button" class="nxl-pw-btn-apply ols-btn-danger" id="olsVvReject">
                        <span class="ols-confirm-btn-label">Reject</span>
                    </button>
                    <button type="button" class="nxl-pw-btn-apply" id="olsVvApprove">
                        <span class="ols-confirm-btn-label">Approve</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="olsPaymentDetailsModal" tabindex="-1" aria-labelledby="olsPdTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ols-details-modal">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title" id="olsPdTitle">Payment Details</h6>
                        <div class="ols-details-sub">Installment <span id="olsPdInstallment"></span></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <dl class="ols-details-grid">
                        <div><dt>Due Date</dt><dd id="olsPdDue"></dd></div>
                        <div><dt>Date Paid</dt><dd id="olsPdPaid"></dd></div>
                        <div><dt>Payment Amount</dt><dd id="olsPdAmount"></dd></div>
                        <div><dt>Payment Status</dt><dd><span id="olsPdStatus" class="ols-status-pill"></span></dd></div>
                        <div><dt>Recorded</dt><dd id="olsPdRecorded"></dd></div>
                    </dl>
                </div>

                {{-- Lahat ng bayad ng installment (partial + full), galing sa ledger --}}
                <div class="ols-details-section" id="olsPdPaymentsSection" style="display:none;">
                    <div class="ols-details-section-head">
                        <span class="ols-details-section-title">Payments</span>
                    </div>
                    <div class="ols-pd-pay-list" id="olsPdPaymentsList"></div>
                </div>

                {{-- Fallback: isang receipt lang (kapag walang ledger rows) --}}
                <div class="ols-details-section" id="olsPdReceiptSection">
                    <div class="ols-details-section-head">
                        <span class="ols-details-section-title">Payment Receipt</span>
                        <a href="#" target="_blank" rel="noopener" class="ols-details-fulllink" id="olsPdFullLink"
                            style="display:none;">View Full Receipt</a>
                    </div>
                    <div class="ols-details-receipt">
                        <img id="olsPdReceiptImg" alt="Payment receipt" style="display:none;">
                        <span class="ols-details-empty" id="olsPdReceiptEmpty">No receipt uploaded.</span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="ols-details-close" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- RECEIPT VIEWER: bubukas kapag na-click ang "View receipt" sa Payment Details.
         Pagsara nito, babalik sa Payment Details. --}}
    <div class="modal fade" id="olsReceiptViewerModal" tabindex="-1" aria-labelledby="olsRvTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content ols-rv-modal">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title" id="olsRvTitle">Payment Receipt</h6>
                        <div class="ols-rv-sub" id="olsRvSub"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="ols-rv-body">
                    <span class="ols-rv-state" id="olsRvLoading">Loading receipt...</span>
                    <span class="ols-rv-state" id="olsRvError" style="display:none;">Unable to load the receipt.</span>
                    <img id="olsRvImg" alt="Payment receipt" style="display:none;">
                </div>

                <div class="modal-footer">
                    <a href="#" target="_blank" rel="noopener" class="ols-rv-fulllink" id="olsRvFullLink">Open in new tab</a>
                    <button type="button" class="ols-details-close" data-bs-dismiss="modal">Back</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="olsBorrowerReceiptModal" tabindex="-1" aria-labelledby="olsBrTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ols-br-modal">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title" id="olsBrTitle">Borrower Receipt</h6>
                        <div class="ols-br-sub">Installment <span id="olsBrInstallment"></span></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="ols-br-body">
                    <div class="ols-br-preview">
                        <div class="ols-br-state" id="olsBrLoading">Loading receipt...</div>
                        <div class="ols-br-state" id="olsBrError" style="display:none;">Unable to load the receipt.</div>
                        <a href="#" target="_blank" rel="noopener" id="olsBrImgLink" title="Open full size"
                            style="display:none;">
                            <img id="olsBrImg" alt="Receipt uploaded by the borrower">
                        </a>
                        <div class="ols-br-hint" id="olsBrHint" style="display:none;">Click the image to open it full size</div>
                    </div>

                    <div class="ols-br-details">
                        <div class="ols-br-amount-label">Balance Due</div>
                        <div class="ols-br-amount">&#8369;<span id="olsBrAmount"></span></div>

                        <dl class="ols-br-list">
                            <div><dt>Installment Amount</dt><dd>&#8369;<span id="olsBrTotal"></span></dd></div>
                            <div><dt>Received So Far</dt><dd>&#8369;<span id="olsBrReceived"></span></dd></div>
                            <div><dt>Due Date</dt><dd id="olsBrDue"></dd></div>
                            <div><dt>Submitted</dt><dd id="olsBrSubmitted"></dd></div>
                            <div><dt>Date Paid</dt><dd id="olsBrPaidDate"></dd></div>
                        </dl>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="ols-details-close" data-bs-dismiss="modal">Close</button>
                    <a href="#" class="ols-br-btn-download" id="olsBrDownload">
                        <i class="feather-download"></i> Download
                    </a>
                    <button type="button" class="ols-br-btn-reject" id="olsBrRejectBtn">Reject</button>
                    <button type="button" class="ols-br-btn-partial" id="olsBrPartialBtn">Partial Payment</button>
                    <button type="button" class="ols-br-btn-record" id="olsBrRecordBtn">Record Payment</button>
                </div>
            </div>
        </div>
    </div>

    {{-- PARTIAL PAYMENT: kulang ang binayad. Ire-record ang natanggap, hindi pa Paid, at maie-email si borrower. --}}
    <div class="modal fade nxl-account-modal" id="olsPartialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ols-confirm-modal">
                <div class="modal-body py-4">
                    <div class="text-center">
                        <div class="ols-confirm-title">Record a partial payment?</div>
                        <div class="ols-confirm-desc">
                            Installment <strong id="olsPartialInstallmentNo">#1</strong> still has a balance of
                            <strong>&#8369;<span id="olsPartialBalance">0.00</span></strong>.
                            Enter the amount that is actually shown on the receipt. The installment stays unpaid,
                            the borrower is emailed, and they can upload another receipt for the balance.
                        </div>
                    </div>

                    <div class="ols-field">
                        <label for="olsPartialAmount" class="ols-field-label">Amount received <span class="ols-req">*</span></label>
                        <input type="number" id="olsPartialAmount" class="ols-input" min="0.01" step="0.01"
                            placeholder="e.g. 1500.00">
                    </div>

                    <div class="ols-field">
                        <label for="olsPartialRemarks" class="ols-field-label">Remarks (optional)</label>
                        <textarea id="olsPartialRemarks" class="ols-input" rows="2" maxlength="500"
                            placeholder="e.g. Please pay the remaining balance by Friday"></textarea>
                    </div>

                    <div class="ols-form-error" id="olsPartialError" style="display:none;"></div>
                </div>
                <div class="modal-footer nxl-pw-modal-footer">
                    <button type="button" class="nxl-pw-btn-discard" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="nxl-pw-btn-apply" id="olsPartialConfirmBtn">
                        <span class="ols-confirm-btn-label">Record &amp; Notify</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- REJECT RECEIPT: malabo, mali, o peke ang receipt. Walang amount na nare-record. --}}
    <div class="modal fade nxl-account-modal" id="olsRejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ols-confirm-modal">
                <div class="modal-body py-4">
                    <div class="text-center">
                        <div class="ols-confirm-title">Reject this receipt?</div>
                        <div class="ols-confirm-desc">
                            The receipt for installment <strong id="olsRejectInstallmentNo">#1</strong> will be rejected
                            and no payment will be recorded. The borrower is emailed the reason and can upload a new receipt.
                        </div>
                    </div>

                    <div class="ols-field">
                        <label for="olsRejectReason" class="ols-field-label">Reason <span class="ols-req">*</span></label>
                        <textarea id="olsRejectReason" class="ols-input" rows="3" maxlength="500"
                            placeholder="e.g. The receipt is blurry, please upload a clearer photo"></textarea>
                    </div>

                    <div class="ols-form-error" id="olsRejectError" style="display:none;"></div>
                </div>
                <div class="modal-footer nxl-pw-modal-footer">
                    <button type="button" class="nxl-pw-btn-discard" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="nxl-pw-btn-apply ols-btn-danger" id="olsRejectConfirmBtn">
                        <span class="ols-confirm-btn-label">Reject Receipt</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@section('page-scripts')
    <script src="{{ asset('functions/ongoing-loan-detail.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('functions/assigned-payment-method.js') }}?v={{ time() }}"></script>
      <script src="{{ asset('functions/payment-account-dropdown.js') }}?v={{ time() }}"></script>
@endsection