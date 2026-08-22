<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Penalty Details - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/penalty-tracking-detail.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    {{-- NAV --}}
    <nav class="nxl-navigation">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="{{ route('admin.dashboard') }}" class="b-brand">
                    <h3 class="fst-italic">Ran Serenity</h3>
                </a>
            </div>
            <div class="navbar-content">
                <ul class="nxl-navbar">
                    <li class="nxl-item">
                        <a href="{{ route('admin.dashboard') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-airplay"></i></span>
                            <span class="nxl-mtext">Dashboard</span>
                        </a>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Borrower Verification</span>
                            @if(($pendingCount ?? 0) > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification') }}">Pending</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'approved']) }}">Approved</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'rejected']) }}">Rejected</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'on_hold']) }}">On Hold</a></li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-file-text"></i></span>
                            <span class="nxl-mtext">Loan Applications</span>
                            @if(($pendingLoanCount ?? 0) > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingLoanCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications') }}">Pending Applications</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'approved']) }}">Approved</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'rejected']) }}">Rejected</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'on_hold']) }}">On Hold</a></li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu nxl-trigger active">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Borrowers</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
                            <li class="nxl-item" data-current="true">
                                <a class="nxl-link" href="{{ route('admin.penalty-tracking') }}">Penalty Tracking</a>
                            </li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                            <span class="nxl-mtext">Active Loans</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.completed-loans') }}">Completed Loans</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">Defaulted</a></li>
                        </ul>
                    </li>
                    <li class="nxl-item">
                        <a href="javascript:void(0);" onclick="confirmLogout()" class="nxl-link text-danger">
                            <span class="nxl-micon"><i class="feather-log-out"></i></span>
                            <span class="nxl-mtext">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- HEADER — hindi ginalaw, shared across admin pages --}}
    <header class="nxl-header">
        <div class="header-wrapper">
            <div class="header-left d-flex align-items-center gap-4">
                <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                    <div class="hamburger hamburger--arrowturn">
                        <div class="hamburger-box"><div class="hamburger-inner"></div></div>
                    </div>
                </a>
                <div class="nxl-navigation-toggle">
                    <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                    <a href="javascript:void(0);" id="menu-expend-button" style="display:none"><i class="feather-arrow-right"></i></a>
                </div>
            </div>
            <div class="header-right ms-auto">
                <div class="nxl-h-item dark-light-theme">
                    <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button"><i class="feather-moon"></i></a>
                    <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display:none"><i class="feather-sun"></i></a>
                </div>
            </div>
        </div>
    </header>

    {{-- MAIN CONTENT --}}
    <main class="nxl-container">
        <div class="nxl-content">

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

                $totalPoints = !empty($p->breakdown) ? collect($p->breakdown)->sum('points') : ($p->late_fee_periods ?? 0);
            @endphp

           
            <div class="page-header">
                <div class="page-header-left d-flex align-items-center">
                    <div class="page-header-title"><h5 class="m-b-10">Penalty Tracking Detail</h5></div>
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

                {{-- ── UNIFIED HEADER CARD (identity + info grid) ── --}}
                <div class="pd-header-card mb-4">
                    <div class="pd-header-block-left">
                        <div class="pd-avatar">
                            {{ strtoupper(substr($p->first_name ?? 'U', 0, 1) . substr($p->last_name ?? '', 0, 1)) }}
                        </div>
                        <div>
                            <div class="pd-name-row">
                                <span class="pd-borrower-name">{{ $p->first_name ?? '—' }} {{ $p->last_name ?? '' }}</span>
                                <span class="pd-status-badge pd-badge-{{ $status }}">
                                    @if($status === 'paid') <i class="bi bi-check-circle-fill me-1"></i>
                                    @elseif($status === 'waived') <i class="bi bi-slash-circle-fill me-1"></i>
                                    @else <i class="bi bi-exclamation-circle-fill me-1"></i>
                                    @endif
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                            <div class="pd-borrower-sub">
                                Loan ID: {{ $p->loan_id_display ?? ('LN-' . str_pad($p->application_id ?? 0, 5, '0', STR_PAD_LEFT)) }}
                            </div>
                            <div class="pd-meta-row">
                                <span><i class="bi bi-tag"></i>Installment: #{{ $p->installment_no }}</span>
                                <span><i class="bi bi-calendar3"></i>Due Date: {{ \Carbon\Carbon::parse($p->due_date)->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pd-header-block-right">
                        <div class="pd-mini-info">
                            <div class="pd-mini-icon"><i class="bi bi-person"></i></div>
                            <div>
                                <div class="pd-mini-label">Borrower</div>
                                <div class="pd-mini-value">{{ $p->first_name ?? '—' }} {{ $p->last_name ?? '' }}</div>
                            </div>
                        </div>
                        <div class="pd-mini-info">
                            <div class="pd-mini-icon"><i class="bi bi-wallet2"></i></div>
                            <div>
                                <div class="pd-mini-label">Loan ID</div>
                                <div class="pd-mini-value">{{ $p->loan_id_display ?? ('LN-' . str_pad($p->application_id ?? 0, 5, '0', STR_PAD_LEFT)) }}</div>
                            </div>
                        </div>
                        <div class="pd-mini-info">
                            <div class="pd-mini-icon"><i class="bi bi-calendar-check"></i></div>
                            <div>
                                <div class="pd-mini-label">Installment</div>
                                <div class="pd-mini-value">#{{ $p->installment_no }}</div>
                            </div>
                        </div>
                        <div class="pd-mini-info">
                            <div class="pd-mini-icon"><i class="bi bi-check-circle"></i></div>
                            <div>
                                <div class="pd-mini-label">Payment Status</div>
                                <div class="pd-mini-value {{ $statusValueClass }}">{{ $p->payment_status ?? ucfirst($status) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── PENALTY SUMMARY ── --}}
                <div class="pd-card mb-4">
                    <div class="pd-card-header">
                        <div class="pd-card-icon" style="background:#e8f0fe; color:#4a7cf7;">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <div class="pd-card-title">Penalty Summary</div>
                    </div>
                    <div class="pd-card-body">
                        <div class="pd-summary-grid">
                            <div class="pd-summary-box">
                                <div class="pd-summary-icon"><i class="bi bi-calendar3"></i></div>
                                <div>
                                    <div class="pd-summary-label">Days Late</div>
                                    <div class="pd-summary-value">{{ $p->days_late }} days</div>
                                </div>
                            </div>
                            <div class="pd-summary-box">
                                <div class="pd-summary-icon"><i class="bi bi-star"></i></div>
                                <div>
                                    <div class="pd-summary-label">Penalty Points</div>
                                    <div class="pd-summary-value">{{ $p->late_fee_periods }} {{ Str::plural('point', $p->late_fee_periods) }}</div>
                                </div>
                            </div>
                            <div class="pd-summary-box">
                                <div class="pd-summary-icon"><i class="bi bi-percent"></i></div>
                                <div>
                                    <div class="pd-summary-label">Penalty Rate</div>
                                    <div class="pd-summary-value">₱{{ number_format($p->rate ?? 0, 2) }} per point</div>
                                </div>
                            </div>
                            <div class="pd-summary-box is-total">
                                <div class="pd-summary-icon"><i class="bi bi-exclamation-circle"></i></div>
                                <div>
                                    <div class="pd-summary-label">Total Penalty</div>
                                    <div class="pd-summary-value">₱{{ number_format($p->penalty_amount, 2) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="pd-calc-line">
                            <i class="bi bi-info-circle"></i>
                            {{ $p->late_fee_periods }} {{ Str::plural('penalty point', $p->late_fee_periods) }} × ₱{{ number_format($p->rate ?? 0, 2) }} = ₱{{ number_format($p->penalty_amount, 2) }}
                        </div>
                    </div>
                </div>

                {{-- ── PENALTY BREAKDOWN ── --}}
                @if(!empty($p->breakdown))
                <div class="pd-card mb-4">
                    <div class="pd-card-header">
                        <div class="pd-card-icon" style="background:#fff7ed; color:#ea580c;">
                            <i class="bi bi-table"></i>
                        </div>
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
                                    @foreach($p->breakdown as $i => $row)
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
                                        <td class="text-end fw-700" colspan="2">₱{{ number_format($p->penalty_amount, 2) }}</td>
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
                        <div class="pd-card-icon" style="background:#e8f0fe; color:#4a7cf7;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="pd-card-title">Penalty Status</div>
                    </div>
                    <div class="pd-card-body">
                        <div class="pd-status-grid {{ $status === 'unpaid' ? 'is-single' : '' }}">
                            <div class="pd-status-box">
                                <div class="pd-status-box-icon {{ $statusIconClass }}">
                                    @if($status === 'paid') <i class="bi bi-check-lg"></i>
                                    @elseif($status === 'waived') <i class="bi bi-slash-circle"></i>
                                    @else <i class="bi bi-exclamation-triangle"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="pd-status-box-label">Status</div>
                                    <div class="pd-status-box-value {{ $statusValueClass }}">
                                        @if($status === 'paid') ✓ Paid
                                        @elseif($status === 'waived') Waived
                                        @else Unpaid
                                        @endif
                                    </div>
                                    <div class="pd-status-box-desc">
                                        @if($status === 'paid') Penalty has been settled.
                                        @elseif($status === 'waived') This penalty was waived and no longer needs payment.
                                        @else Payment is still pending for this penalty.
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($status === 'paid')
                            <div class="pd-status-box">
                                <div class="pd-status-box-icon is-blue"><i class="bi bi-calendar-check"></i></div>
                                <div>
                                    <div class="pd-status-box-label">Resolved Date</div>
                                    <div class="pd-status-box-value">{{ \Carbon\Carbon::parse($p->updated_at)->format('M d, Y') }}</div>
                                    <div class="pd-status-box-desc">Date when the penalty was fully paid.</div>
                                </div>
                            </div>
                            @elseif($status === 'waived')
                            <div class="pd-status-box">
                                <div class="pd-status-box-icon is-blue"><i class="bi bi-calendar-check"></i></div>
                                <div>
                                    <div class="pd-status-box-label">Waived Date</div>
                                    <div class="pd-status-box-value">{{ \Carbon\Carbon::parse($p->updated_at)->format('M d, Y') }}</div>
                                    <div class="pd-status-box-desc">Waived by {{ $p->waived_by_name ?? '—' }}.</div>
                                </div>
                            </div>
                            @endif
                        </div>

                        @if($status === 'waived' && !empty($p->waived_reason))
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
                @if($status === 'unpaid')
                <div class="pd-action-row mb-4">
                    <button type="button" class="pd-btn-paid" data-bs-toggle="modal" data-bs-target="#paidConfirmModal">
                        <i class="bi bi-check-circle me-2"></i> Mark as Paid
                    </button>
                    <button type="button" class="pd-btn-waive" data-bs-toggle="modal" data-bs-target="#waiveModal">
                        <i class="bi bi-slash-circle me-2"></i> Waive Penalty
                    </button>
                </div>
                @endif

            </div>
        </div>

        <footer class="footer">
            <div class="pd-footer">
                <span>&copy; {{ date('Y') }} Ran Serenity Lending System</span>
                <span class="pd-footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </span>
            </div>
        </footer>
    </main>

    {{-- MODALS — pareho sa dati, walang binago --}}
    @if($status === 'unpaid')

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
                    <button type="button"
                            class="pt-btn-confirm-paid"
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
                <form method="POST"
                      action="{{ route('admin.penalty-waive', $p->id) }}"
                      class="pt-waive-form"
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
                                <div class="pt-info-value">{{ $p->first_name ?? '—' }} {{ $p->last_name ?? '' }}</div>
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

    <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
    <script src="{{ asset('functions/penalty-tracking-detail.js') }}"></script>

</body>
</html>