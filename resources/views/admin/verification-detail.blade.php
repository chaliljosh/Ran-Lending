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
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/verification-detail.css') }}" />

</head>
<body>
    @php
        $cameFrom = request()->query('from', 'borrowers');
    @endphp
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
                    <li class="nxl-item nxl-hasmenu {{ $cameFrom === 'verification' ? 'nxl-trigger active' : '' }}">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Borrower Verification</span>
                            @if($pendingCount > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item" data-current="{{ $cameFrom === 'verification' && ($viewStatus ?? 'pending') === 'pending' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification') }}">Pending</a>
                            </li>
                            <li class="nxl-item" data-current="{{ $cameFrom === 'verification' && ($viewStatus ?? '') === 'approved' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'approved']) }}">Approved</a>
                            </li>
                            <li class="nxl-item" data-current="{{ $cameFrom === 'verification' && ($viewStatus ?? '') === 'rejected' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'rejected']) }}">Rejected</a>
                            </li>
                            <li class="nxl-item" data-current="{{ $cameFrom === 'verification' && ($viewStatus ?? '') === 'on_hold' ? 'true' : 'false' }}">
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
                    <li class="nxl-item nxl-hasmenu {{ $cameFrom !== 'verification' ? 'nxl-trigger active' : '' }}">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Borrowers</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item" data-current="{{ $cameFrom !== 'verification' ? 'true' : 'false' }}"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
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
                        @if($cameFrom === 'verification')
                            <li class="breadcrumb-item"><a href="{{ route('admin.borrower-verification', ($viewStatus ?? 'pending') !== 'pending' ? ['status' => $viewStatus] : []) }}">Borrower Verification</a></li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                        @endif
                        <li class="breadcrumb-item">Details #{{ $detailId }}</li>
                    </ul>
                </div>
                <div class="page-header-right ms-auto d-flex align-items-center gap-2">
                    @if($cameFrom === 'verification')
                        <a href="{{ route('admin.borrower-verification', ($viewStatus ?? 'pending') !== 'pending' ? ['status' => $viewStatus] : []) }}" class="btn btn-light">
                            Back
                        </a>
                    @else
                        <a href="{{ route('admin.borrowers') }}" class="btn btn-light">
                            Back
                        </a>
                    @endif
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

                {{-- ============================================
                     COMPACT BORROWER HEADER
                     (Profile + Overview combined into one card)
                     Layout: left = avatar/name/id/email/badges (stacked)
                             right = View Active Loan button (top) + stats row (bottom)
                ============================================ --}}
                @php
                    $initials = strtoupper(substr($d->first_name ?? 'B', 0, 1) . substr($d->last_name ?? 'R', 0, 1));
                    // Optional loan-overview stats — populated by the controller via getBorrowerLoanStats().
                    $loanStats = $loanStats ?? [];
                @endphp
                <div class="borrower-summary-card">
                    <div class="bsc-left">
                        <div class="borrower-avatar">{{ $initials }}</div>
                        <div class="borrower-summary-info">
                            <h5>{{ $d->first_name }} {{ $d->last_name }}</h5>
                            <div class="borrower-summary-meta">
                                <span><strong>Borrower ID:</strong> #{{ $detailId }}</span>
                                <span><strong>Email:</strong> {{ $d->reg_email }}</span>
                            </div>
                            <div class="bsc-badges">
                                @if($d->status === 'approved')
                                    <span class="badge badge-approved">Approved</span>
                                @elseif($d->status === 'rejected')
                                    <span class="badge badge-rejected">Rejected</span>
                                @elseif($d->status === 'on_hold')
                                    <span class="badge badge-onhold">On Hold</span>
                                @else
                                    <span class="badge badge-pending">Pending</span>
                                @endif

                                @if(!empty($standingLabel))
                                    <span class="abd-standing is-{{ $standing ?? 'good' }}">{{ $standingLabel }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="bsc-right">
                        @if(($loanStats['active'] ?? 0) > 0 && !empty($loanStats['active_application_id']))
                            <a href="{{ route('admin.active-borrower-detail', $loanStats['active_application_id']) }}" class="btn-view-active-loan">
                                View Active Loan <i class="feather-arrow-right"></i>
                            </a>
                        @endif
                        <div class="bsc-stats">
                            <div class="bsc-stat">
                                <span class="bsc-stat-value">{{ $loanStats['total'] ?? '—' }}</span>
                                <span class="bsc-stat-label">Total Loans</span>
                            </div>
                            <div class="bsc-stat">
                                <span class="bsc-stat-value">{{ $loanStats['active'] ?? '—' }}</span>
                                <span class="bsc-stat-label">Active</span>
                            </div>
                            <div class="bsc-stat">
                                <span class="bsc-stat-value">{{ $loanStats['completed'] ?? '—' }}</span>
                                <span class="bsc-stat-label">Completed</span>
                            </div>
                            <div class="bsc-stat">
                                <span class="bsc-stat-value">{{ $loanStats['overdue'] ?? '—' }}</span>
                                <span class="bsc-stat-label">Overdue</span>
                            </div>
                        </div>

                        {{-- Opens the Review Borrower Verification modal. Compact,
                             outline-style trigger — matches the muted button
                             style used elsewhere in the admin (e.g. btn-la-outline
                             on Loan Applications) rather than a large filled CTA. --}}
                        @if($d->status !== 'rejected' && $d->status !== 'approved')
                        <button type="button" class="btn-review-decision" data-bs-toggle="modal" data-bs-target="#decisionModal">
                            Make Decision
                        </button>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">

                        <!-- Personal Information -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="section-title"><i class="feather-user"></i>Personal Information</div>
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
                                    <div class="info-item highlight"><label>Monthly Income</label><span>₱{{ number_format($d->monthly_income ?? 0, 2) }}</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Birth Place -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="section-title"><i class="feather-map-pin"></i>Birth Place</div>
                                <div class="info-row">
                                    <div class="info-item"><label>Country</label><span>{{ $d->birth_country ?? '—' }}</span></div>
                                    <div class="info-item"><label>Province</label><span>{{ $d->birth_province ?? '—' }}</span></div>
                                    <div class="info-item"><label>City</label><span>{{ $d->birth_city ?? '—' }}</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Present Address -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="section-title"><i class="feather-home"></i>Present Address</div>
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

                        <!-- Work Information -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="section-title"><i class="feather-briefcase"></i>Work Information</div>
                                <div class="info-row">
                                    <div class="info-item"><label>Employment Status</label><span>{{ ucfirst(str_replace('_', ' ', $d->employment_status ?? '—')) }}</span></div>
                                    <div class="info-item"><label>Company/Business</label><span>{{ $d->company_name ?? '—' }}</span></div>
                                    <div class="info-item"><label>Position</label><span>{{ $d->position ?? '—' }}</span></div>
                                    <div class="info-item"><label>Years in Company</label><span>{{ $d->years_in_company ?? '—' }}</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Co-Maker Information -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="section-title"><i class="feather-users"></i>Co-Maker Information</div>
                                <div class="info-row">
                                    <div class="info-item"><label>Last Name</label><span>{{ $d->co_last ?? '—' }}</span></div>
                                    <div class="info-item"><label>First Name</label><span>{{ $d->co_first ?? '—' }}</span></div>
                                    <div class="info-item"><label>Middle Name</label><span>{{ $d->co_middle ?? '—' }}</span></div>
                                    <div class="info-item"><label>Relationship</label><span>{{ ucfirst($d->relationship ?? '—') }}</span></div>
                                    <div class="info-item"><label>Contact Number</label><span>{{ $d->co_contact ?? '—' }}</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Valid ID / Attached ID -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="section-title"><i class="feather-credit-card"></i>Valid ID</div>
                                <div class="info-row">
                                    <div class="info-item"><label>ID Type</label><span>{{ $d->id_type ?? '—' }}</span></div>
                                    <div class="info-item"><label>ID Number</label><span>{{ $d->id_number ?? '—' }}</span></div>
                                </div>
                                @if(!empty($d->id_file_path))
                                <div class="mt-1">
                                    <label class="form-label fw-semibold" style="font-size:0.7rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">Attached ID</label><br>
                                    @php $ext = strtolower(pathinfo($d->id_file_path, PATHINFO_EXTENSION)); @endphp
                                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                    <span class="id-thumb-wrap" onclick="openIdViewer('{{ asset('storage/' . $d->id_file_path) }}')">
                                        <img src="{{ asset('storage/' . $d->id_file_path) }}" alt="Valid ID">
                                    </span>
                                    @else
                                    <a href="{{ asset('storage/' . $d->id_file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="feather-file me-1"></i> View Attached ID
                                    </a>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Admin Review History (was: Admin Discussion) -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="feather-message-square me-2"></i>Admin Review History</h6>
                            </div>
                            <div class="card-body">

                                @if($allReviews->count() > 0)
                                <div class="mb-4">
                                    <p class="fw-semibold mb-3" style="font-size:0.75rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">Decisions</p>
                                    @foreach($allReviews as $r)
                                    @php $rInitials = strtoupper(substr($r->admin_name, 0, 2)); @endphp
                                    <div class="discussion-item">
                                        <div class="discussion-avatar">{{ $rInitials }}</div>
                                        <div class="discussion-body">
                                            <div class="discussion-card {{ $r->decision === 'approved' ? 'decision-approved' : 'decision-rejected' }}">
                                                <div class="discussion-header">
                                                    <span class="name">{{ $r->admin_name }}</span>
                                                    <span class="badge {{ $r->decision === 'approved' ? 'badge-approved' : 'badge-rejected' }} px-2 py-1">
                                                        {{ ucfirst($r->decision) }}
                                                    </span>
                                                </div>
                                                @if($r->remarks)
                                                <p class="mb-0 mt-2 text-muted" style="font-size:0.82rem;">{{ $r->remarks }}</p>
                                                @endif
                                                <p class="mb-0 mt-1 time">{{ \Carbon\Carbon::parse($r->reviewed_at)->format('M d, Y h:i A') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif

                                @if($comments->count() > 0)
                                <div class="mb-3">
                                    <p class="fw-semibold mb-3" style="font-size:0.75rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">Comments</p>
                                    @foreach($comments as $c)
                                    @php $cInitials = strtoupper(substr($c->admin_name, 0, 2)); @endphp
                                    <div class="discussion-item">
                                        <div class="discussion-avatar {{ $c->admin_id == $adminId ? 'mine' : '' }}">{{ $cInitials }}</div>
                                        <div class="discussion-body">
                                            <div class="discussion-card comment">
                                                <div class="discussion-header">
                                                    <span class="name">{{ $c->admin_name }} {{ $c->admin_id == $adminId ? '(You)' : '' }}</span>
                                                    <span class="time">{{ \Carbon\Carbon::parse($c->created_at)->format('M d, Y h:i A') }}</span>
                                                </div>
                                                <p class="mb-0 mt-2">{{ $c->comment }}</p>
                                            </div>
                                        </div>
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

                  
                </div>
            </div>
        </div>
        <footer class="footer">
            <p class="text-center">&copy; {{ date('Y') }} Ran Serenity Lending System</p>
        </footer>
    </main>

    <!-- Review Borrower Verification Modal -->
    @if($d->status !== 'rejected' && $d->status !== 'approved')
    <div class="modal fade" id="decisionModal" tabindex="-1" aria-labelledby="decisionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-review-decision">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.verification-detail', $detailId) }}" id="decisionForm">
                    @csrf
                    <input type="hidden" name="action" id="decisionActionInput" value="">
                    <div class="modal-header">
                        <div>
                            <h6 class="modal-title" id="decisionModalLabel">Review Borrower Verification</h6>
                            <p class="modal-subtitle">Confirm whether this borrower's submitted details meet verification requirements.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                       

                        <label class="form-label fw-semibold" for="decisionRemarks">Remarks <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="remarks" id="decisionRemarks" class="form-control" rows="3" placeholder="Add notes or reason...">{{ $myReview->remarks ?? '' }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-decision btn-decision-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-decision btn-decision-reject" onclick="submitDecision('rejected')">Reject</button>
                        <button type="button" class="btn-decision btn-decision-approve" onclick="submitDecision('approved')">Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Small styled confirmation modal — replaces the native browser
         confirm() alert. Shown after Reject/Approve is clicked in the
         review modal above; the review modal hides itself first so only
         one modal is ever visible at a time. Cancel brings the review
         modal back (remarks are preserved since the DOM isn't destroyed,
         just hidden). --}}
    <div class="modal fade" id="decisionConfirmModal" tabindex="-1" aria-labelledby="decisionConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-confirm-decision">
            <div class="modal-content">
                <div class="modal-body">
                    <p class="confirm-decision-text" id="confirmDecisionText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-decision btn-decision-cancel" id="confirmDecisionCancelBtn">Cancel</button>
                    <button type="button" class="btn-decision" id="confirmDecisionOkBtn"></button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ============================================
         VALID ID FULLSCREEN VIEWER (zoom + rotate)
    ============================================ --}}
    <div class="id-viewer-overlay" id="idViewerOverlay">
        <button class="id-viewer-close" onclick="closeIdViewer()"><i class="feather-x"></i></button>
        <img src="" alt="Valid ID full view" id="idViewerImage">
        <div class="id-viewer-toolbar">
            <button onclick="idViewerZoom(-0.2)" title="Zoom out"><i class="feather-zoom-out"></i></button>
            <button onclick="idViewerZoom(0.2)" title="Zoom in"><i class="feather-zoom-in"></i></button>
            <button onclick="idViewerRotate()" title="Rotate"><i class="feather-rotate-cw"></i></button>
            <button onclick="idViewerReset()" title="Reset"><i class="feather-refresh-ccw"></i></button>
        </div>
    </div>

   <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
<script src="{{ asset('functions/logout-modal.js') }}"></script>
<script src="{{ asset('functions/verification-detail.js') }}"></script>
</body>
</html>