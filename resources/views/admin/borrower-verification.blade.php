<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Borrower Verification - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/borrower-verification.css') }}">
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
                    <li class="nxl-item nxl-hasmenu nxl-trigger active">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Borrower Verification</span>
                            @if($pendingCount > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? 'pending') === 'pending' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification') }}">Pending</a>
                            </li>
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? '') === 'approved' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'approved']) }}">Approved</a>
                            </li>
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? '') === 'rejected' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'rejected']) }}">Rejected</a>
                            </li>
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? '') === 'on_hold' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'on_hold']) }}">On Hold</a>
                            </li>
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
                    <div class="page-header-title">
                        <h5 class="m-b-10">
                            Borrower Verification
                            @if(($viewStatus ?? 'pending') !== 'pending')
                                &mdash; {{ ucfirst(str_replace('_', ' ', $viewStatus)) }}
                            @endif
                        </h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Borrower Verification</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">

                @if(($viewStatus ?? 'pending') === 'pending')
                {{-- ================= PENDING VIEW (Pending / Reviewed tabs) ================= --}}

                <div class="d-flex gap-2 mb-4">
                    <button class="tab-btn active" onclick="showTab('pending', this)">
                        Pending
                        @if($pendingCount > 0)
                            <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
                        @endif
                    </button>
                    <button class="tab-btn" onclick="showTab('reviewed', this)">
                        Reviewed
                        <span class="badge bg-secondary ms-1">{{ count($reviewedDetails) }}</span>
                    </button>
                </div>

                <!-- PENDING TAB -->
                <div id="tab-pending">
                    @if(count($pendingDetails) === 0)
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="feather-check-circle" style="font-size:48px; color:#28a745;"></i>
                            <h5 class="mt-3 fw-bold">All caught up!</h5>
                            <p class="text-muted">No pending personal details to review.</p>
                        </div>
                    </div>
                    @else
                    @foreach($pendingDetails as $d)
                    <div class="detail-card">
                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                            <div>
                                <div class="bname">{{ $d['last_name'] }}, {{ $d['first_name'] }} {{ $d['middle_name'] }}</div>
                                <div class="bemail">{{ $d['email'] }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                @if($d['status'] === 'on_hold')
                                    <span class="badge badge-onhold px-3 py-2">On Hold</span>
                                @else
                                    <span class="badge badge-pending px-3 py-2">Pending</span>
                                @endif
                                <span class="approval-status">
                                    <span class="approved">&#9989; {{ $d['approved_count'] }} approved</span>
                                    &nbsp;|&nbsp;
                                    <span class="rejected">&#10060; {{ $d['rejected_count'] }} rejected</span>
                                </span>
                                <a href="{{ route('admin.verification-detail', $d['detail_id']) }}?from=verification" class="btn btn-sm btn-outline-primary">
                                    <i class="feather-eye me-1"></i> View Full Details
                                </a>
                            </div>
                        </div>
                        <div class="info-grid">
                            <div class="info-item"><label>Contact</label><span>{{ $d['cellphone_num'] ?? '—' }}</span></div>
                            <div class="info-item"><label>Detail ID</label><span>#{{ $d['detail_id'] }}</span></div>
                        </div>
                        @if($d['already_reviewed'])
                        <div class="reviewed-badge">
                            <i class="feather-check-circle"></i> You have already reviewed this — decision recorded.
                        </div>
                        @endif
                    </div>
                    @endforeach
                    @endif
                </div>

                <!-- REVIEWED TAB -->
                <div id="tab-reviewed" style="display:none;">
                    @if(count($reviewedDetails) === 0)
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <p class="text-muted">No reviewed details yet.</p>
                        </div>
                    </div>
                    @else
                    <div class="card stretch stretch-full">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Borrower</th>
                                            <th>Email</th>
                                            <th>Decision</th>
                                            <th>Remarks</th>
                                            <th>Reviewed At</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($reviewedDetails as $r)
                                        <tr>
                                            <td class="fw-bold">{{ $r->last_name }}, {{ $r->first_name }}</td>
                                            <td class="text-muted">{{ $r->email ?? '—' }}</td>
                                            <td>
                                                @if($r->decision === 'approved')
                                                    <span class="badge badge-approved px-3 py-2">Approved</span>
                                                @elseif($r->decision === 'rejected')
                                                    <span class="badge badge-rejected px-3 py-2">Rejected</span>
                                                @else
                                                    <span class="badge badge-pending px-3 py-2">Pending</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $r->remarks ?? '—' }}</td>
                                            <td class="text-muted">{{ $r->reviewed_at ? \Carbon\Carbon::parse($r->reviewed_at)->format('M d, Y h:i A') : '—' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.verification-detail', $r->detail_id) }}?from=verification" class="btn btn-sm btn-light">
                                                    <i class="feather-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                @else
                {{-- ================= FILTERED VIEW: Approved / Rejected / On Hold ================= --}}

                    <div class="search-bar-wrap">
                        <i class="feather-search"></i>
                        <input type="text" class="form-control" id="filterSearchInput"
                               placeholder="Search by name or email..."
                               onkeyup="filterDetails(this.value)">
                    </div>

                    @if(count($filteredDetails) === 0)
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="feather-inbox" style="font-size:48px;color:#8a9bb0;"></i>
                            <h5 class="mt-3 fw-bold">No borrowers yet</h5>
                        </div>
                    </div>
                    @else
                        @foreach($filteredDetails as $d)
                        <div class="detail-card" data-search="{{ strtolower($d->last_name . ' ' . $d->first_name . ' ' . $d->email) }}">
                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                                <div>
                                    <div class="bname">{{ $d->last_name }}, {{ $d->first_name }} {{ $d->middle_name }}</div>
                                    <div class="bemail">{{ $d->email }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if($viewStatus === 'approved')
                                        <span class="badge badge-approved px-3 py-2">Approved</span>
                                    @elseif($viewStatus === 'rejected')
                                        <span class="badge badge-rejected px-3 py-2">Rejected</span>
                                    @else
                                        <span class="badge badge-onhold px-3 py-2">On Hold</span>
                                    @endif
                                    <a href="{{ route('admin.verification-detail', $d->detail_id) }}?from=verification" class="btn btn-sm btn-outline-primary">
                                        <i class="feather-eye me-1"></i> View Full Details
                                    </a>
                                </div>
                            </div>
                            <div class="info-grid">
                                <div class="info-item"><label>Contact</label><span>{{ $d->cellphone_num ?? '—' }}</span></div>
                                <div class="info-item"><label>Detail ID</label><span>#{{ $d->detail_id }}</span></div>
                            </div>
                        </div>
                        @endforeach
                    @endif

                @endif

            </div>
        </div>
        <footer class="footer">
            <p class="text-center">&copy; {{ date('Y') }} Ran Serenity Lending System</p>
        </footer>
    </main>

    <!-- Shared confirmation modal for Approve/Reject on the pending list — modal-modern style -->
    <div class="modal fade modal-modern" id="cardConfirmModal" tabindex="-1" aria-labelledby="cardConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success" id="cardConfirmIconWrap">
                        <i id="cardConfirmIcon" style="font-size:30px;"></i>
                    </div>
                    <p id="cardConfirmText" class="modal-title-text"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-modal-primary btn-approve" id="cardConfirmBtn"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Result modals — one of these shows after a decision is recorded,
         depending on the borrower's status after that decision. -->

    <!-- Generic "decision recorded" -->
    <div class="modal fade modal-modern" id="decisionSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success">
                        <i class="feather-check-circle"></i>
                    </div>
                    <p class="modal-title-text">Decision submitted</p>
                    <p class="modal-message-text">
                        Your decision has been recorded successfully. The application will remain under review until all three administrators have submitted their decisions.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mixed decisions -->
    <div class="modal fade modal-modern" id="onHoldModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-warning">
                        <i class="feather-alert-triangle"></i>
                    </div>
                    <p class="modal-title-text">Application on hold</p>
                    <p class="modal-message-text">
                        The administrators have reached different decisions. The application has been placed on hold for further discussion.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-warning" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fully approved by all 3 -->
    <div class="modal fade modal-modern" id="finalApprovalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success">
                        <i class="feather-check-circle"></i>
                    </div>
                    <p class="modal-title-text">Application approved</p>
                    <p class="modal-message-text">
                        The borrower's application has been approved by all administrators.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
    @if(session('success'))
    <script>
        // Landed here via redirect right after a decision was submitted on the
        // detail page — show the correct result modal based on decisionStatus,
        // same mapping as the AJAX flow on this page's pending cards.
        document.addEventListener('DOMContentLoaded', function () {
            const status = @json(session('decisionStatus'));
            let modalId = 'decisionSuccessModal';
            if (status === 'approved') modalId = 'finalApprovalModal';
            else if (status === 'on_hold') modalId = 'onHoldModal';

            const modalEl = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
            modal.show();
        });
    </script>
    @endif
    <script src="{{ asset('functions/borrower-verification.js') }}"></script>
</body>
</html>