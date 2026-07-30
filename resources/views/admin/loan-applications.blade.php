<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Loan Applications - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <style>
        .app-card { border:1px solid #e9ecef; border-radius:12px; padding:20px; margin-bottom:16px; background:#fff; }
        .badge-pending  { background:#fff8e1; color:#f0a500; border:1px solid #f0a500; }
        .badge-onhold   { background:#fff3cd; color:#856404; border:1px solid #856404; }
        .badge-approved { background:#dcfce7; color:#16a34a; border:1px solid #16a34a; }
        .badge-rejected { background:#fee2e2; color:#dc2626; border:1px solid #dc2626; }
        .reviewed-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; background:#e8f0fe; color:#4a7cf7; font-size:0.82rem; font-weight:600; }
        .action-bar { display:flex; gap:8px; align-items:flex-start; flex-wrap:wrap; margin-top:14px; padding-top:14px; border-top:1px solid #f1f3f5; }
        .info-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:10px; margin:14px 0; }
        .info-item label { font-size:0.72rem; font-weight:600; color:#8a9bb0; text-transform:uppercase; display:block; }
        .info-item span  { font-size:0.85rem; color:#1a2a3a; font-weight:500; }
        .tab-btn { cursor:pointer; padding:8px 20px; border-radius:8px; font-weight:500; font-size:0.88rem; border:1.5px solid #e9ecef; background:#fff; color:#6c757d; transition:all 0.2s; }
        .tab-btn.active { background:#4a7cf7; color:#fff; border-color:#4a7cf7; }
        .decision-card-form.is-submitting { opacity: 0.6; pointer-events: none; }
    </style>
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
                    <li class="nxl-item">
                        <a href="{{ route('admin.borrower-verification') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Borrower Verification</span>
                            @if(($pendingCount ?? 0) > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nxl-item nxl-hasmenu nxl-trigger active">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-file-text"></i></span>
                            <span class="nxl-mtext">Loan Applications</span>
                            @if(($pendingLoanCount ?? 0) > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingLoanCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item">
                                <a class="nxl-link {{ ($viewStatus ?? 'pending') === 'pending' ? 'active' : '' }}"
                                   href="{{ route('admin.loan-applications') }}">Pending Applications</a>
                            </li>
                            <li class="nxl-item">
                                <a class="nxl-link {{ ($viewStatus ?? '') === 'approved' ? 'active' : '' }}"
                                   href="{{ route('admin.loan-applications', ['status' => 'approved']) }}">Approved</a>
                            </li>
                            <li class="nxl-item">
                                <a class="nxl-link {{ ($viewStatus ?? '') === 'rejected' ? 'active' : '' }}"
                                   href="{{ route('admin.loan-applications', ['status' => 'rejected']) }}">Rejected</a>
                            </li>
                            <li class="nxl-item">
                                <a class="nxl-link {{ ($viewStatus ?? '') === 'on_hold' ? 'active' : '' }}"
                                   href="{{ route('admin.loan-applications', ['status' => 'on_hold']) }}">On Hold</a>
                            </li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Borrowers</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="#">All Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">Active Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">Penalty Tracking</a></li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                            <span class="nxl-mtext">Active Loans</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="#">Ongoing Loans</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">Overdue Payments</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">Completed Loans</a></li>
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
                            Loan Applications
                            @if(($viewStatus ?? 'pending') !== 'pending')
                                &mdash; {{ ucfirst(str_replace('_', ' ', $viewStatus)) }}
                            @endif
                        </h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Loan Applications</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">

                @if(($viewStatus ?? 'pending') === 'pending')
                {{-- ================= PENDING VIEW (Pending / Reviewed tabs) ================= --}}

                <div class="d-flex gap-2 mb-4">
                    <button class="tab-btn active" onclick="showLoanTab('pending', this)">
                        Pending
                        @if($pendingLoanCount > 0)
                            <span class="badge bg-danger ms-1">{{ $pendingLoanCount }}</span>
                        @endif
                    </button>
                    <button class="tab-btn" onclick="showLoanTab('reviewed', this)">
                        Reviewed
                        <span class="badge bg-secondary ms-1">{{ count($reviewedApplications) }}</span>
                    </button>
                </div>

                <!-- PENDING TAB -->
                <div id="loantab-pending">
                    @if(count($pendingApplications) === 0)
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="feather-check-circle" style="font-size:48px;color:#28a745;"></i>
                            <h5 class="mt-3 fw-bold">All caught up!</h5>
                            <p class="text-muted">No pending loan applications to review.</p>
                        </div>
                    </div>
                    @else
                        @foreach($pendingApplications as $app)
                        <div class="app-card">
                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                                <div>
                                    <div style="font-size:1rem;font-weight:700;">
                                        {{ $app['last_name'] }}, {{ $app['first_name'] }}
                                    </div>
                                    <div style="font-size:0.82rem;color:#8a9bb0;">{{ $app['email'] }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if($app['status'] === 'on_hold')
                                        <span class="badge badge-onhold px-3 py-2">On Hold</span>
                                    @else
                                        <span class="badge badge-pending px-3 py-2">Pending</span>
                                    @endif
                                    <span style="font-size:0.82rem;">
                                        <span style="color:#16a34a;font-weight:600;">✅ {{ $app['approved_count'] }} approved</span>
                                        &nbsp;|&nbsp;
                                        <span style="color:#dc2626;font-weight:600;">❌ {{ $app['rejected_count'] }} rejected</span>
                                        &nbsp;|&nbsp; 
                                    </span>
                                    <a href="{{ route('admin.loan-application-detail', $app['application_id']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="feather-eye me-1"></i> View Details
                                    </a>
                                </div>
                            </div>

                            <div class="info-grid">
                                <div class="info-item"><label>Application ID</label><span>#{{ $app['application_id'] }}</span></div>
                                <div class="info-item"><label>Loan Amount</label><span>₱{{ number_format($app['loan_amount'], 2) }}</span></div>
                                <div class="info-item"><label>Term</label><span>{{ $app['loan_term'] }} months</span></div>
                                <div class="info-item"><label>Purpose</label><span>{{ ucfirst(str_replace('_', ' ', $app['loan_purpose'])) }}</span></div>
                                <div class="info-item"><label>Applied</label><span>{{ date('M d, Y', strtotime($app['applied_at'])) }}</span></div>
                            </div>

                            <form method="POST" action="{{ route('admin.loan-applications') }}" class="action-bar decision-card-form">
                                @csrf
                                <input type="hidden" name="application_id" value="{{ $app['application_id'] }}">
                                <input type="text" name="remarks" class="form-control" style="flex:1;min-width:200px;" placeholder="Remarks (optional)">
                               
                            </form>
                        </div>
                        @endforeach
                    @endif
                </div>

                <!-- REVIEWED TAB -->
                <div id="loantab-reviewed" style="display:none;">
                    @if(count($reviewedApplications) === 0)
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <p class="text-muted">No reviewed applications yet.</p>
                        </div>
                    </div>
                    @else
                      @foreach($reviewedApplications as $r)
                      <div class="app-card" style="padding:24px 28px; min-height:120px;">
                          <div class="d-flex justify-content-between">
                              <div>
                                  <div style="font-weight:700;">{{ $r->last_name }}, {{ $r->first_name }}</div>
                                  <div style="font-size:0.82rem;color:#8a9bb0;">{{ $r->email }}</div>
                              </div>
                              <div class="d-flex align-items-center gap-2 flex-wrap">
                                  <span class="reviewed-badge">
                                      <i class="feather-check-circle"></i> You {{ $r->decision }} this — ₱{{ number_format($r->loan_amount, 2) }}
                                      <span class="text-muted" style="font-weight:500;">({{ $r->total_reviewed }}/3 reviewed)</span>
                                  </span>
                                  <a href="{{ route('admin.loan-application-detail', $r->application_id) }}" class="btn btn-sm btn-outline-primary">
                                      <i class="feather-eye me-1"></i> View Details
                                  </a>
                              </div>
                          </div>
                          @if($r->remarks)
                          <p class="text-muted mt-2 mb-0" style="font-size:0.8rem;">"{{ $r->remarks }}"</p>
                          @endif
                      </div>
                      @endforeach
                    @endif
                </div>

                @else
                {{-- ================= FILTERED VIEW: Approved / Rejected / On Hold ================= --}}

                    @if(count($filteredApplications) === 0)
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="feather-inbox" style="font-size:48px;color:#8a9bb0;"></i>
                            <h5 class="mt-3 fw-bold">Wala pang application dito</h5>
                            <p class="text-muted">No {{ str_replace('_', ' ', $viewStatus) }} applications yet.</p>
                        </div>
                    </div>
                    @else
                        @foreach($filteredApplications as $app)
                        <div class="app-card">
                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                                <div>
                                    <div style="font-size:1rem;font-weight:700;">
                                        {{ $app->last_name }}, {{ $app->first_name }}
                                    </div>
                                    <div style="font-size:0.82rem;color:#8a9bb0;">{{ $app->email }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if($viewStatus === 'approved')
                                        <span class="badge badge-approved px-3 py-2">Approved</span>
                                    @elseif($viewStatus === 'rejected')
                                        <span class="badge badge-rejected px-3 py-2">Rejected</span>
                                    @else
                                        <span class="badge badge-onhold px-3 py-2">On Hold</span>
                                    @endif
                                    <a href="{{ route('admin.loan-application-detail', $app->application_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="feather-eye me-1"></i> View Details
                                    </a>
                                </div>
                            </div>

                            <div class="info-grid">
                                <div class="info-item"><label>Application ID</label><span>#{{ $app->application_id }}</span></div>
                                <div class="info-item"><label>Loan Amount</label><span>₱{{ number_format($app->loan_amount, 2) }}</span></div>
                                <div class="info-item"><label>Term</label><span>{{ $app->loan_term }} months</span></div>
                                <div class="info-item"><label>Purpose</label><span>{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}</span></div>
                                <div class="info-item"><label>Applied</label><span>{{ date('M d, Y', strtotime($app->applied_at)) }}</span></div>
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

    <!-- Confirm modal for Approve/Reject on the pending list -->
    <div class="modal fade modal-modern" id="loanCardConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success" id="loanCardConfirmIconWrap">
                        <i id="loanCardConfirmIcon" style="font-size:30px;"></i>
                    </div>
                    <p id="loanCardConfirmText" class="modal-title-text"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-modal-primary btn-approve" id="loanCardConfirmBtn"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success modal -->
    <div class="modal modal-modern" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success">
                        <i class="feather-check-circle"></i>
                    </div>
                    <p class="modal-title-text">Decision submitted successfully!</p>
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
        document.addEventListener('DOMContentLoaded', function () {
            const successModal = new bootstrap.Modal(document.getElementById('successModal'), { backdrop: 'static' });
            successModal.show();
            setTimeout(() => successModal.hide(), 2000);
        });
    </script>
    @endif
    <script src="{{ asset('functions/loan-applications.js') }}"></script>
</body>
</html>