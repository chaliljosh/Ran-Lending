<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Borrower Details - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
     <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    <style>
        .section-title { font-size: 0.8rem; font-weight: 700; color: #4a7cf7; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; padding-bottom: 6px; border-bottom: 2px solid #e8f0fe; }
        .info-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .info-item label { font-size: 0.72rem; font-weight: 600; color: #8a9bb0; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 3px; }
        .info-item span { font-size: 0.88rem; color: #1a2a3a; font-weight: 500; }
        .badge-approved { background: #dcfce7; color: #16a34a; border: 1px solid #16a34a; }
        .badge-rejected { background: #fee2e2; color: #dc2626; border: 1px solid #dc2626; }
        .badge-pending  { background: #fff8e1; color: #f0a500; border: 1px solid #f0a500; }
        .badge-onhold   { background: #fff3cd; color: #856404; border: 1px solid #856404; }
        .decision-card { border-radius: 10px; padding: 12px 16px; margin-bottom: 10px; font-size: 0.85rem; }
        .decision-approved { background: #dcfce7; border-left: 4px solid #16a34a; }
        .decision-rejected { background: #fee2e2; border-left: 4px solid #dc2626; }
        .comment-bubble { background: #f8f9fa; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px; font-size: 0.85rem; border-left: 4px solid #4a7cf7; }
        .comment-bubble .admin-name { font-weight: 700; color: #4a7cf7; font-size: 0.78rem; }
        .comment-bubble .comment-time { color: #adb5bd; font-size: 0.75rem; }
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
                            @if(($pendingCount ?? 0) > 0)
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
                    <div class="page-header-title"><h5 class="m-b-10">Borrower Details</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.borrower-verification') }}">Borrower Verification</a></li>
                        <li class="breadcrumb-item">Details #{{ $detailId }}</li>
                    </ul>
                </div>
                <div class="page-header-right ms-auto">
                    <a href="{{ route('admin.borrower-verification') }}" class="btn btn-light">
                        <i class="feather-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>

            <div class="main-content">
                @if(session('success'))
                <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content">
                            <div class="modal-body text-center py-4">
                                <i class="feather-check-circle" style="font-size:48px; color:#16a34a;"></i>
                                <p class="mt-3 mb-0 fw-semibold">Decision saved successfully!</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div class="row">
                    <div class="col-lg-8">

                        <!-- Personal Info -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="section-title">Personal Information</div>
                                <div class="info-row">
                                    <div class="info-item"><label>Last Name</label><span>{{ $d->last_name }}</span></div>
                                    <div class="info-item"><label>First Name</label><span>{{ $d->first_name }}</span></div>
                                    <div class="info-item"><label>Middle Name</label><span>{{ $d->middle_name }}</span></div>
                                    <div class="info-item"><label>Suffix</label><span>{{ $d->suffix ?? '—' }}</span></div>
                                    <div class="info-item"><label>Birthday</label><span>{{ $d->birthday ? \Carbon\Carbon::parse($d->birthday)->format('F d, Y') : '—' }}</span></div>
                                    <div class="info-item"><label>Age</label><span>{{ $d->birthday ? \Carbon\Carbon::parse($d->birthday)->age . ' yrs old' : '—' }}</span></div>
                                    <div class="info-item"><label>Gender</label><span>{{ ucfirst($d->gender ?? '—') }}</span></div>
                                    <div class="info-item"><label>Civil Status</label><span>{{ ucfirst($d->civil_status ?? '—') }}</span></div>
                                    <div class="info-item"><label>Citizenship</label><span>{{ $d->citizenship ?? '—' }}</span></div>
                                    <div class="info-item"><label>Cellphone</label><span>{{ $d->cellphone_num ?? '—' }}</span></div>
                                    <div class="info-item"><label>Email</label><span>{{ $d->reg_email }}</span></div>
                                    <div class="info-item"><label>Monthly Income</label><span>₱{{ number_format($d->monthly_income ?? 0, 2) }}</span></div>
                                </div>

                                <div class="section-title mt-2">Birth Place</div>
                                <div class="info-row">
                                    <div class="info-item"><label>Country</label><span>{{ $d->birth_country ?? '—' }}</span></div>
                                    <div class="info-item"><label>Province</label><span>{{ $d->birth_province ?? '—' }}</span></div>
                                    <div class="info-item"><label>City</label><span>{{ $d->birth_city ?? '—' }}</span></div>
                                </div>

                                <div class="section-title mt-2">Valid ID</div>
                                <div class="info-row">
                                    <div class="info-item"><label>ID Type</label><span>{{ $d->id_type ?? '—' }}</span></div>
                                    <div class="info-item"><label>ID Number</label><span>{{ $d->id_number ?? '—' }}</span></div>
                                </div>
                                @if(!empty($d->id_file_path))
                                <div class="mb-2">
                                    <label class="form-label fw-semibold" style="font-size:0.75rem; color:#8a9bb0; text-transform:uppercase;">Attached ID</label><br>
                                    @php $ext = strtolower(pathinfo($d->id_file_path, PATHINFO_EXTENSION)); @endphp
                                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                    <img src="{{ asset('storage/' . $d->id_file_path) }}" style="max-width:300px; border-radius:8px; border:1px solid #e9ecef;" alt="Valid ID">
                                    @else
                                    <a href="{{ asset('storage/' . $d->id_file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="feather-file me-1"></i> View Attached ID
                                    </a>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Present Address -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="section-title">Present Address</div>
                                <div class="info-row">
                                    <div class="info-item"><label>Region</label><span>{{ $d->region ?? '—' }}</span></div>
                                    <div class="info-item"><label>Province</label><span>{{ $d->province ?? '—' }}</span></div>
                                    <div class="info-item"><label>City</label><span>{{ $d->city ?? '—' }}</span></div>
                                    <div class="info-item"><label>Barangay</label><span>{{ $d->barangay ?? '—' }}</span></div>
                                    <div class="info-item"><label>Zipcode</label><span>{{ $d->zipcode ?? '—' }}</span></div>
                                    <div class="info-item"><label>Home Address</label><span>{{ $d->home_address ?? '—' }}</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Work Info -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="section-title">Work Information</div>
                                <div class="info-row">
                                    <div class="info-item"><label>Employment Status</label><span>{{ ucfirst(str_replace('_', ' ', $d->employment_status ?? '—')) }}</span></div>
                                    <div class="info-item"><label>Company/Business</label><span>{{ $d->company_name ?? '—' }}</span></div>
                                    <div class="info-item"><label>Position</label><span>{{ $d->position ?? '—' }}</span></div>
                                    <div class="info-item"><label>Years in Company</label><span>{{ $d->years_in_company ?? '—' }}</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Co-Maker -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="section-title">Co-Maker Information</div>
                                <div class="info-row">
                                    <div class="info-item"><label>Last Name</label><span>{{ $d->co_last ?? '—' }}</span></div>
                                    <div class="info-item"><label>First Name</label><span>{{ $d->co_first ?? '—' }}</span></div>
                                    <div class="info-item"><label>Middle Name</label><span>{{ $d->co_middle ?? '—' }}</span></div>
                                    <div class="info-item"><label>Relationship</label><span>{{ ucfirst($d->relationship ?? '—') }}</span></div>
                                    <div class="info-item"><label>Contact Number</label><span>{{ $d->co_contact ?? '—' }}</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Discussion Thread -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="feather-message-square me-2"></i>Admin Discussion</h6>
                            </div>
                            <div class="card-body">

                                @if($allReviews->count() > 0)
                                <div class="mb-3">
                                    <p class="fw-semibold mb-2" style="font-size:0.78rem; color:#8a9bb0; text-transform:uppercase;">Decisions</p>
                                    @foreach($allReviews as $r)
                                    <div class="decision-card {{ $r->decision === 'approved' ? 'decision-approved' : 'decision-rejected' }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold">{{ $r->admin_name }}</span>
                                            <span class="badge {{ $r->decision === 'approved' ? 'badge-approved' : 'badge-rejected' }} px-2 py-1">
                                                {{ ucfirst($r->decision) }}
                                            </span>
                                        </div>
                                        @if($r->remarks)
                                        <p class="mb-0 mt-1 text-muted" style="font-size:0.82rem;">Remarks: {{ $r->remarks }}</p>
                                        @endif
                                        <p class="mb-0 mt-1" style="font-size:0.75rem; color:#adb5bd;">{{ \Carbon\Carbon::parse($r->reviewed_at)->format('M d, Y h:i A') }}</p>
                                    </div>
                                    @endforeach
                                </div>
                                @endif

                                @if($comments->count() > 0)
                                <div class="mb-3">
                                    <p class="fw-semibold mb-2" style="font-size:0.78rem; color:#8a9bb0; text-transform:uppercase;">Comments</p>
                                    @foreach($comments as $c)
                                    <div class="comment-bubble {{ $c->admin_id == $adminId ? 'border-primary' : '' }}">
                                        <div class="d-flex justify-content-between">
                                            <span class="admin-name">{{ $c->admin_name }} {{ $c->admin_id == $adminId ? '(You)' : '' }}</span>
                                            <span class="comment-time">{{ \Carbon\Carbon::parse($c->created_at)->format('M d, Y h:i A') }}</span>
                                        </div>
                                        <p class="mb-0 mt-1">{{ $c->comment }}</p>
                                    </div>
                                    @endforeach
                                </div>
                                @endif

                                <!-- Comment box — hidden if fully rejected -->
                                @if($d->status !== 'rejected')
                                <form method="POST" action="{{ route('admin.verification-detail', $detailId) }}">
                                    @csrf
                                    <div class="d-flex gap-2">
                                        <input type="text" name="comment" class="form-control" placeholder="Add a comment for other admins..." required>
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="feather-send"></i>
                                        </button>
                                    </div>
                                </form>
                                @endif

                            </div>
                        </div>

                    </div>

                    <!-- Decision Panel -->
                    <div class="col-lg-4">
                        <div class="card" style="position: sticky; top: 80px;">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i ></i>Admin Decision</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="font-size:0.75rem; color:#8a9bb0; text-transform:uppercase;">Current Status</label><br>
                                    @if($d->status === 'approved')
                                    <span class="badge badge-approved px-3 py-2">Approved</span>
                                    @elseif($d->status === 'rejected')
                                    <span class="badge badge-rejected px-3 py-2">Rejected</span>
                                    @elseif($d->status === 'on_hold')
                                    <span class="badge badge-onhold px-3 py-2">On Hold</span>
                                    @else
                                    <span class="badge badge-pending px-3 py-2">Pending</span>
                                    @endif
                                </div>

                                @if($d->status === 'rejected')
                                <!-- FULLY REJECTED — no actions allowed -->
                                <div class="alert alert-danger mb-0" style="font-size:0.85rem;">
                                    <i class="feather-x-circle me-2"></i>
                                    This borrower has been <strong>fully rejected</strong> by all 3 admins. No further action can be taken.
                                </div>

                                @elseif($d->status === 'approved')
                                <!-- FULLY APPROVED — no actions allowed -->
                                <div class="alert alert-success mb-0" style="font-size:0.85rem;">
                                    <i class="feather-check-circle me-2"></i>
                                    This borrower has been <strong>fully approved</strong>. No further action needed.
                                </div>

                                @elseif($myReview)
                                <!-- Already reviewed — show current decision, button opens modal to change it -->
                                <div class="alert alert-info mb-3" style="font-size:0.85rem;">
                                    <i class="feather-check-circle me-2"></i>
                                    You submitted a <strong>{{ $myReview->decision }}</strong> decision on
                                    {{ \Carbon\Carbon::parse($myReview->reviewed_at)->format('M d, Y') }}.
                                </div>
                                <p class="text-muted mb-3" style="font-size:0.82rem;">Remarks: {{ $myReview->remarks ?: 'None' }}</p>
                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#decisionModal">
                                    <i class="feather-edit-2 me-2"></i>Change Decision
                                </button>

                                @else
                                <!-- Not yet reviewed — button opens modal -->
                                <p class="text-muted mb-3" style="font-size:0.85rem;">Review the borrower's details, then make your decision.</p>
                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#decisionModal">
                                    <i></i>Make a Decision
                                </button>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer">
            <p class="text-center">&copy; {{ date('Y') }} Ran Serenity Lending System</p>
        </footer>
    </main>

    <!-- Decision Modal -->
    @if($d->status !== 'rejected' && $d->status !== 'approved')
    <div class="modal fade" id="decisionModal" tabindex="-1" aria-labelledby="decisionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.verification-detail', $detailId) }}" id="decisionForm">
                    @csrf
                    <input type="hidden" name="action" id="decisionActionInput" value="">
                    <div class="modal-header">
                        <h6 class="modal-title" id="decisionModalLabel">
                            <i class="feather-shield me-2"></i>{{ $myReview ? 'Change Decision' : 'Make a Decision' }}
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Step 1: remarks -->
                        <div id="decisionStep1">
                            <label class="form-label fw-semibold">Remarks <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="remarks" id="decisionRemarks" class="form-control" rows="3" placeholder="Add notes or reason...">{{ $myReview->remarks ?? '' }}</textarea>
                        </div>
                        <!-- Step 2: confirmation -->
                        <div id="decisionStep2" class="text-center py-3" style="display:none;">
                            <i id="decisionConfirmIcon" style="font-size:40px;"></i>
                            <p id="decisionConfirmText" class="mt-3 mb-0 fw-semibold"></p>
                        </div>
                    </div>
                    <div class="modal-footer" id="decisionFooterStep1">
                        <button type="button" class="btn btn-success flex-fill" onclick="decisionShowConfirm('approved')">
                            <i class="feather-check me-2"></i>Approve
                        </button>
                        <button type="button" class="btn btn-danger flex-fill" onclick="decisionShowConfirm('rejected')">
                            <i class="feather-x me-2"></i>Reject
                        </button>
                    </div>
                    <div class="modal-footer" id="decisionFooterStep2" style="display:none;">
                        <button type="submit" class="btn flex-fill" id="decisionConfirmBtn"></button>
                        <button type="button" class="btn btn-light flex-fill" onclick="decisionBackToStep1()">
                            <i></i>Back
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
<script src="{{ asset('functions/verification-detail.js') }}"></script>
</body>
</html>