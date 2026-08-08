<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Penalty Tracking - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/penalty-tracking.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
    // Temporary — pinapakita nito sa screen mismo ang anumang JS error,
    // dahil naka-block ang DevTools sa laptop na ito. Tanggalin na lang
    // ito pagkatapos ma-ayos yung bug.
    window.addEventListener('error', function (e) {
        var box = document.createElement('div');
        box.style.cssText = 'position:fixed;bottom:0;left:0;right:0;background:#dc2626;color:#fff;padding:14px;font-family:monospace;font-size:13px;z-index:999999;white-space:pre-wrap;';
        box.textContent = 'JS ERROR: ' + e.message + '  (file: ' + e.filename + ', line: ' + e.lineno + ')';
        document.body.appendChild(box);
    });

    
</script>

</head>
<body>
    <nav class="nxl-navigation">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="{{ route('admin.dashboard') }}" class="b-brand"><h3 class="fst-italic">Ran Serenity</h3></a>
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
                            @if($pendingCount ?? 0 > 0)
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
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Borrowers</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.penalty-tracking') }}">Penalty Tracking</a></li>
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

    <header class="nxl-header">
        <div class="header-wrapper">
            <div class="header-left d-flex align-items-center gap-4">
                <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                    <div class="hamburger hamburger--arrowturn"><div class="hamburger-box"><div class="hamburger-inner"></div></div></div>
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

    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-left d-flex align-items-center">
                    <div class="page-header-title"><h5 class="m-b-10">Penalty Tracking</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Penalty Tracking</li>
                    </ul>
                </div>
            </div>

            <div class="main-content pt-page">
                {{--
                    DATA CONTRACT expected from AdminController::penaltyTracking():
                    $unpaid / $paid / $waived -> collections of penalty rows, each exposing:
                    id, first_name, last_name, application_id, installment_no, due_date,
                    days_late, amount_due, penalty_amount, status, waived_reason (nullable)

                    $totalPenaltiesCount, $outstandingAmount, $totalPaidAmount, $totalWaivedAmount
                --}}
                @php
                    $allPenalties = collect($unpaid ?? [])->concat($paid ?? [])->concat($waived ?? []);
                @endphp
                 <div class="pt-summary-row">
                    <div class="pt-summary-card">
                        <div class="pt-summary-icon is-total"><i class="bi bi-clipboard-data"></i></div>
                        <div>
                            <div class="pt-summary-label">Total Penalties</div>
                            <div class="pt-summary-value">{{ $totalPenaltiesCount ?? $allPenalties->count() }}</div>
                        </div>
                    </div>
                    <div class="pt-summary-card">
                        <div class="pt-summary-icon is-outstanding"><i class="bi bi-exclamation-circle"></i></div>
                        <div>
                            <div class="pt-summary-label">Outstanding Amount</div>
                            <div class="pt-summary-value">₱{{ number_format($outstandingAmount ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <div class="pt-summary-card">
                        <div class="pt-summary-icon is-paid"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <div class="pt-summary-label">Total Paid</div>
                            <div class="pt-summary-value">₱{{ number_format($totalPaidAmount ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <div class="pt-summary-card">
                        <div class="pt-summary-icon is-waived"><i class="bi bi-slash-circle"></i></div>
                        <div>
                            <div class="pt-summary-label">Total Waived</div>
                            <div class="pt-summary-value">₱{{ number_format($totalWaivedAmount ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>

                {{-- ============ TOOLBAR ============ --}}
                <div class="pt-toolbar">
                    <div class="pt-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" id="ptSearchInput" class="pt-search-input" placeholder="Search borrower...">
                    </div>
                    <select id="ptMonthFilter" class="pt-select">
                        <option value="">All Months</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endfor
                    </select>
                    <select id="ptStatusFilter" class="pt-select">
                        <option value="">All Status</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                        <option value="waived">Waived</option>
                    </select>
                    <select id="ptSortSelect" class="pt-select">
                        <option value="days_desc">Sort: Most days late</option>
                        <option value="days_asc">Sort: Fewest days late</option>
                        <option value="amount_desc">Sort: Highest amount due</option>
                        <option value="amount_asc">Sort: Lowest amount due</option>
                        <option value="due_desc">Sort: Due date (newest)</option>
                        <option value="due_asc">Sort: Due date (oldest)</option>
                    </select>
                    <div class="pt-toolbar-spacer"></div>
                    <button type="button" id="ptExportBtn" class="pt-btn-tool"><i class="bi bi-download"></i> Export</button>
                    <button type="button" id="ptRefreshBtn" class="pt-btn-tool is-primary"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                </div>

                {{-- ============ TABLE ============ --}}
                <div class="pt-table-card">
                    <div class="pt-table-scroll">
                        <table class="pt-table" id="ptTable">
                            <thead>
                                <tr>
                                    <th>Borrower</th>
                                    <th>Installment</th>
                                    <th>Due Date</th>
                                    <th>Days Late</th>
                                    <th>Amount Due</th>
                                    <th>Status</th>
                                    <th class="is-actions-col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ptTableBody">
                                @forelse($allPenalties as $p)
                                    @php
                                        $status = $p->status ?? 'unpaid';
                                        $days = (int) ($p->days_late ?? 0);
                                        $due = \Carbon\Carbon::parse($p->due_date);
                                        $fullName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                                        $initials = strtoupper(substr($p->first_name ?? '?', 0, 1) . substr($p->last_name ?? '', 0, 1));

                                        if ($status === 'paid') {
                                            $pillClass = 'is-green';
                                        } elseif ($status === 'waived') {
                                            $pillClass = 'is-gray';
                                        } elseif ($days >= 15) {
                                            $pillClass = 'is-red';
                                        } elseif ($days >= 7) {
                                            $pillClass = 'is-orange';
                                        } else {
                                            $pillClass = 'is-blue';
                                        }
                                    @endphp
                                    <tr class="pt-tr"
                                        data-penalty-id="{{ $p->id }}"
                                        data-view-target="#viewModal{{ $p->id }}"
                                        data-borrower="{{ strtolower($fullName) }}"
                                        data-month="{{ $due->month }}"
                                        data-status="{{ $status }}"
                                        data-days="{{ $days }}"
                                        data-amount="{{ $p->amount_due }}"
                                        data-due="{{ $due->format('Y-m-d') }}">

                                        <td>
                                            <div class="pt-cell-borrower">
                                                <div class="pt-avatar">{{ $initials }}</div>
                                                <div>
                                                    <div class="pt-borrower-name">{{ $fullName !== '' ? $fullName : '—' }}</div>
                                                    <div class="pt-borrower-sub">Loan #{{ str_pad($p->application_id ?? 0, 5, '0', STR_PAD_LEFT) }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="pt-cell-installment">#{{ $p->installment_no }}</td>

                                        <td class="pt-cell-due">
                                            <div>
                                                <div class="pt-due-primary">{{ $due->format('M d, Y') }}</div>
                                                <div class="pt-due-secondary">{{ $due->format('l') }}</div>
                                            </div>
                                        </td>

                                        <td class="pt-cell-days">
                                            <span class="pt-pill {{ $pillClass }}">
                                                @if($status === 'paid') Paid
                                                @elseif($status === 'waived') Waived
                                                @else {{ $days }} Days
                                                @endif
                                            </span>
                                        </td>

                                        <td class="pt-cell-amount">
                                            <div class="pt-amount-value">₱{{ number_format($p->amount_due, 2) }}</div>
                                            <div class="pt-amount-fee">Fee ₱{{ number_format($p->penalty_amount, 2) }}</div>
                                        </td>

                                        <td class="pt-cell-status">
                                            <span class="pt-status-badge is-{{ $status }}">{{ strtoupper($status) }}</span>
                                        </td>

                                        {{-- ===== DIRECT ACTION BUTTONS ===== --}}
                                        <td class="pt-cell-actions">
                                            <div class="pt-actions-group">
                                                <button type="button"
                                                        class="pt-action-btn is-view"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewModal{{ $p->id }}"
                                                        title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                                @if($status === 'unpaid')
                                                    <button type="button"
                                                            class="pt-action-btn is-paid"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#paidConfirmModal{{ $p->id }}"
                                                            title="Mark as Paid">
                                                        <i class="bi bi-check-lg"></i> Paid
                                                    </button>
                                                    <button type="button"
                                                            class="pt-action-btn is-waive"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#waiveModal{{ $p->id }}"
                                                            title="Waive Penalty">
                                                        <i class="bi bi-slash-circle"></i> Waive
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>

                        <div class="pt-empty-state" id="ptEmptyState" style="display:none;">
                            <div class="pt-empty-icon"><i class="bi bi-inbox"></i></div>
                            <div class="pt-empty-title">No penalty records found.</div>
                            <div class="pt-empty-desc">Try adjusting your search or filters.</div>
                        </div>
                    </div>

                    <div class="pt-table-footer">
                        <div class="pt-footer-info">
                            <span id="ptShowingText">Showing 0–0 of 0 records</span>
                            <span>
                                Rows per page:
                                <select id="ptRowsPerPage" class="pt-rows-select">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </span>
                        </div>
                        <div class="pt-pagination" id="ptPagination"></div>
                    </div>
                </div>

            </div>
        </div>
        <footer class="footer">
            <p class="text-center">&copy; {{ date('Y') }} Ran Serenity Lending System</p>
        </footer>
    </main>

    {{-- ====================================================================
         Modals live outside .nxl-container so theme.min.css's blur on
         body.modal-open never affects the modal itself.
    ==================================================================== --}}

    {{-- ---------- SUCCESS MODAL (Mark Paid / Waive confirmation) ---------- --}}
    <div class="modal fade pt-modal" id="successModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-4 text-center">
                    <div class="pt-modal-icon is-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="pt-modal-title">Success</div>
                    <div class="pt-modal-subtitle mb-0" id="successModalMessage">{{ session('success') ?? '' }}</div>
                </div>
            </div>
        </div>
    </div>

    @foreach($allPenalties as $p)
        @php $status = $p->status ?? 'unpaid'; @endphp

        @if($status === 'unpaid')
        {{-- ---------- MARK AS PAID — CONFIRMATION MODAL ---------- --}}
        <div class="modal fade pt-modal" id="paidConfirmModal{{ $p->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header justify-content-end">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="pt-modal-icon is-confirm"><i class="bi bi-question-circle"></i></div>
                        <div class="pt-modal-title">Mark as Paid?</div>
                        <div class="pt-modal-subtitle">Are you sure you want to mark this installment as paid?</div>

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
                                <div class="pt-info-value is-late-fee">₱{{ number_format($p->penalty_amount, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="pt-btn-cancel" data-bs-dismiss="modal">No, Cancel</button>
                        <button type="button"
                                class="pt-btn-confirm-paid"
                                data-mark-paid-url="{{ route('admin.penalty-mark-paid', $p->id) }}"
                                data-penalty-id="{{ $p->id }}">
                            <i class="bi bi-check-lg"></i> Yes, Mark as Paid
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade pt-modal" id="waiveModal{{ $p->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST"
                          action="{{ route('admin.penalty-waive', $p->id) }}"
                          id="waiveForm{{ $p->id }}"
                          class="pt-waive-form"
                          data-penalty-id="{{ $p->id }}">
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
                                    <div class="pt-info-label">Installment</div>
                                    <div class="pt-info-value">#{{ $p->installment_no }}</div>
                                </div>
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Late Fee</div>
                                    <div class="pt-info-value is-late-fee">₱{{ number_format($p->penalty_amount, 2) }}</div>
                                </div>
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Amount Due</div>
                                    <div class="pt-info-value">₱{{ number_format($p->amount_due, 2) }}</div>
                                </div>
                                <div class="pt-info-item is-full">
                                    <div class="pt-info-label">Days Late</div>
                                    <div class="pt-info-value">{{ $p->days_late }} Days</div>
                                </div>
                            </div>

                            <label class="pt-form-label">Reason for Waiver <span class="req">*</span></label>
                            <textarea name="waived_reason" class="pt-textarea" rows="4" placeholder="Enter the reason for waiving this penalty..." required></textarea>

                            <div class="pt-warning-line">
                                <i class="bi bi-exclamation-triangle-fill"></i> This action cannot be undone.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="pt-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="pt-btn-confirm-waive"><i class="bi bi-check-lg"></i> Confirm Waive</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <div class="modal fade pt-modal" id="viewModal{{ $p->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header justify-content-end">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pb-4">
                        <div class="pt-view-avatar">{{ strtoupper(substr($p->first_name ?? '?', 0, 1) . substr($p->last_name ?? '', 0, 1)) }}</div>
                        <div class="pt-modal-title">{{ $p->first_name ?? '—' }} {{ $p->last_name ?? '' }}</div>
                        <div class="pt-modal-subtitle">Penalty details for installment #{{ $p->installment_no }}</div>

                        <div class="pt-info-card">
                            <div class="pt-info-item">
                                <div class="pt-info-label">Status</div>
                                <div class="pt-info-value">{{ ucfirst($status) }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Installment</div>
                                <div class="pt-info-value">#{{ $p->installment_no }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Due Date</div>
                                <div class="pt-info-value">{{ \Carbon\Carbon::parse($p->due_date)->format('M d, Y') }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Days Late</div>
                                <div class="pt-info-value">{{ $p->days_late }} Days</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Amount Due</div>
                                <div class="pt-info-value">₱{{ number_format($p->amount_due, 2) }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Late Fee</div>
                                <div class="pt-info-value is-late-fee">₱{{ number_format($p->penalty_amount, 2) }}</div>
                            </div>
                            @if(!empty($p->waived_reason))
                                <div class="pt-info-item is-full is-waived-reason">
                                    <div class="pt-info-label">Waived Reason</div>
                                    <div class="pt-info-value">{{ $p->waived_reason }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
    <script src="{{ asset('functions/penalty-tracking.js') }}?v=4"></script>
</body>
</html>