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
    <style>
        .detail-card { border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; margin-bottom: 16px; background: #fff; transition: box-shadow 0.2s; }
        .detail-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .detail-card .bname { font-size: 1rem; font-weight: 700; color: #1a2a3a; }
        .detail-card .bemail { font-size: 0.82rem; color: #8a9bb0; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin: 14px 0; }
        .info-item label { font-size: 0.72rem; font-weight: 600; color: #8a9bb0; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .info-item span { font-size: 0.85rem; color: #1a2a3a; font-weight: 500; }
        .action-bar { display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap; margin-top: 14px; padding-top: 14px; border-top: 1px solid #f1f3f5; }
        .remarks-input { flex: 1; min-width: 200px; }
        .badge-pending  { background: #fff8e1; color: #f0a500; border: 1px solid #f0a500; }
        .badge-approved { background: #dcfce7; color: #16a34a; border: 1px solid #16a34a; }
        .badge-rejected { background: #fee2e2; color: #dc2626; border: 1px solid #dc2626; }
        .reviewed-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; background: #e8f0fe; color: #4a7cf7; font-size: 0.82rem; font-weight: 600; }
        .tab-btn { cursor: pointer; padding: 8px 20px; border-radius: 8px; font-weight: 500; font-size: 0.88rem; border: 1.5px solid #e9ecef; background: #fff; color: #6c757d; transition: all 0.2s; }
        .tab-btn.active { background: #4a7cf7; color: #fff; border-color: #4a7cf7; }
        .approval-status { font-size: 0.82rem; color: #6c757d; }
        .approval-status .approved { color: #16a34a; font-weight: 600; }
        .approval-status .rejected { color: #dc2626; font-weight: 600; }
        html.app-skin-dark .remarks-input { background-color: #2a3547; color: #e2e8f0; border-color: #3d4f6b; }
        html.app-skin-dark .remarks-input::placeholder { color: #64748b; }
        html.app-skin-dark .detail-card { background: #1e2a3a; border-color: #2d3f55; }
        html.app-skin-dark .detail-card .bname { color: #e2e8f0; }
        html.app-skin-dark .info-item span { color: #e2e8f0; }
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
                        <a href="{{ route('admin.borrower-verification') }}" class="nxl-link active">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Borrower Verification</span>
                            @if($pendingCount > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-file-text"></i></span>
                            <span class="nxl-mtext">Loan Applications</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="#">Pending Applications</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">Approved</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">Rejected</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="#">On Hold</a></li>
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
                    <div class="page-header-title"><h5 class="m-b-10">Borrower Verification</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Borrower Verification</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="feather-check-circle me-2"></i> Decision saved successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

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
                                    <span class="badge" style="background:#fff3cd;color:#856404;border:1px solid #856404;">On Hold</span>
                                @else
                                    <span class="badge badge-pending px-3 py-2">Pending</span>
                                @endif
                                <span class="approval-status">
                                    <span class="approved">&#9989; {{ $d['approved_count'] }} approved</span>
                                    &nbsp;|&nbsp;
                                    <span class="rejected">&#10060; {{ $d['rejected_count'] }} rejected</span>
                                </span>
                                <a href="{{ route('admin.verification-detail', $d['detail_id']) }}" class="btn btn-sm btn-outline-primary">
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
                        @else
                        <form method="POST" action="{{ route('admin.borrower-verification') }}" class="action-bar">
                            @csrf
                            <input type="hidden" name="detail_id" value="{{ $d['detail_id'] }}">
                            <input type="text" name="remarks" class="form-control remarks-input" placeholder="Remarks (optional)">
                            <button type="submit" name="action" value="approved" class="btn btn-success" onclick="return confirm('Approve this borrower?')">
                                <i class="feather-check me-1"></i> Approve
                            </button>
                            <button type="submit" name="action" value="rejected" class="btn btn-danger" onclick="return confirm('Reject this borrower?')">
                                <i class="feather-x me-1"></i> Reject
                            </button>
                        </form>
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
                                                <a href="{{ route('admin.verification-detail', $r->detail_id) }}" class="btn btn-sm btn-light">
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

            </div>
        </div>
        <footer class="footer">
            <p class="text-center">&copy; {{ date('Y') }} Ran Serenity Lending System</p>
        </footer>
    </main>

    <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
    <script>
        function showTab(tab, btn) {
            document.getElementById('tab-pending').style.display  = tab === 'pending'  ? 'block' : 'none';
            document.getElementById('tab-reviewed').style.display = tab === 'reviewed' ? 'block' : 'none';
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    </script>
</body>
</html>