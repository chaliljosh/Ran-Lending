<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Application Detail - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-details.css') }}">
</head>
<body>
    @php
        // Dynamic origin tracking: kung saan galing ang admin (Loan
        // Applications list o Active Borrowers list) para tama ang naka-
        // highlight na parent menu, breadcrumb, at Back button dito.
        // Pina-pass via ?from=loan-applications|active-borrowers sa
        // "View Details" links.
        $cameFrom = request()->query('from', 'loan-applications');
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
                 <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Borrower Verification</span>
                            @if($pendingCount > 0)
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
                    <li class="nxl-item nxl-hasmenu {{ $cameFrom !== 'active-borrowers' ? 'nxl-trigger active' : '' }}">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-file-text"></i></span>
                            <span class="nxl-mtext">Loan Applications</span>
                            @if(($pendingLoanCount ?? 0) > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingLoanCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item" data-current="{{ $cameFrom !== 'active-borrowers' && ($viewStatus ?? 'pending') === 'pending' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.loan-applications') }}">Pending Applications</a>
                            </li>
                            <li class="nxl-item" data-current="{{ $cameFrom !== 'active-borrowers' && ($viewStatus ?? '') === 'approved' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'approved']) }}">Approved</a>
                            </li>
                            <li class="nxl-item" data-current="{{ $cameFrom !== 'active-borrowers' && ($viewStatus ?? '') === 'rejected' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'rejected']) }}">Rejected</a>
                            </li>
                            <li class="nxl-item" data-current="{{ $cameFrom !== 'active-borrowers' && ($viewStatus ?? '') === 'on_hold' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'on_hold']) }}">On Hold</a>
                            </li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu {{ $cameFrom === 'active-borrowers' ? 'nxl-trigger active' : '' }}">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Borrowers</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                            <li class="nxl-item" data-current="{{ $cameFrom === 'active-borrowers' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.active-borrowers') }}">Active Borrowers</a>
                            </li>
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
                    <div class="page-header-title"><h5 class="m-b-10">Application Detail</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        @if($cameFrom === 'active-borrowers')
                            <li class="breadcrumb-item"><a href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ route('admin.loan-applications') }}">Loan Applications</a></li>
                        @endif
                        <li class="breadcrumb-item">#{{ $applicationId }}</li>
                    </ul>
                </div>
                <div class="page-header-right ms-auto">
                    @if($cameFrom === 'active-borrowers')
                        <a href="{{ route('admin.active-borrowers') }}" class="btn btn-light">
                           Back
                        </a>
                    @else
                        <a href="{{ route('admin.loan-applications', ['status' => $viewStatus]) }}" class="btn btn-light">
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

                <div class="row">

                    <!-- LEFT COLUMN -->
                    <div class="col-lg-4 ld-left-col">
                        <div class="ld-sticky-col">
                            <div class="section-card text-center mb-3">
                                <div class="borrower-avatar">
                                    {{ strtoupper(substr($app->first_name ?? '', 0, 1) . substr($app->last_name ?? '', 0, 1)) }}
                                </div>
                                <h6 class="fw-bold mb-1">
                                    {{ $app->last_name }}, {{ $app->first_name }} {{ $app->middle_name }}{{ ($app->suffix ?? null) ? ', ' . $app->suffix : '' }}
                                </h6>
                                <p class="text-muted mb-3" style="font-size:0.83rem;">{{ $app->email }}</p>

                                @php
                                $statusClass = match($app->status) {
                                    'approved' => 'badge-approved',
                                    'rejected' => 'badge-rejected',
                                    'on_hold'  => 'badge-onhold',
                                    default    => 'badge-pending',
                                };
                                @endphp
                                <span class="badge {{ $statusClass }} px-3 py-2 mb-3">
                                    {{ ucfirst(str_replace('_', ' ', $app->status)) }}
                                </span>

                                @if($app->status === 'rejected')
                                <div class="alert alert-danger mb-0" style="font-size:0.85rem;">
                                    <i class="feather-x-circle me-2"></i>
                                    Fully <strong>rejected</strong> by all 3 admins. No further action.
                                </div>

                                @elseif($app->status === 'approved')
                                <div class="alert alert-success mb-0" style="font-size:0.85rem;">
                                    <i class="feather-check-circle me-2"></i>
                                    Fully <strong>approved</strong>. No further action needed.
                                </div>

                                @elseif($myReview)
                                <div class="alert alert-info mb-0 mt-2" style="font-size:0.82rem;">
                                    <i class="feather-check-circle me-1"></i> You have already reviewed this application.
                                </div>

                                @else
                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#decisionModal">
                                    Make a Decision
                                </button>
                                @endif
                            </div>

                            <!-- Loan Summary -->
                            <div class="section-card">
                                <p class="section-title"><i class="feather-file-text me-2"></i>Loan Summary</p>
                                <table class="table table-sm table-borderless table-loan-summary mb-0">
                                    <tr><td class="info-label">Application ID</td><td class="text-end info-value">#{{ $app->application_id }}</td></tr>
                                    <tr><td class="info-label">Loan Amount</td><td class="text-end"><span class="ld-value-pill ld-pill-primary">₱{{ number_format($app->loan_amount, 2) }}</span></td></tr>
                                    <tr><td class="info-label">Term</td><td class="text-end info-value">{{ $app->loan_term }} months</td></tr>
                                    <tr><td class="info-label">Interest Rate</td><td class="text-end info-value">5% / month</td></tr>
                                    <tr><td class="info-label">Total Interest</td><td class="text-end"><span class="ld-value-pill ld-pill-warning">₱{{ number_format($app->total_interest ?? 0, 2) }}</span></td></tr>
                                    <tr><td class="info-label">Total to Pay</td><td class="text-end"><span class="ld-value-pill ld-pill-danger">₱{{ number_format($app->total_amount ?? 0, 2) }}</span></td></tr>
                                    <tr><td class="info-label">Monthly Payment</td><td class="text-end"><span class="ld-value-pill ld-pill-success">₱{{ number_format($app->monthly_payment ?? 0, 2) }}</span></td></tr>
                                    <tr><td class="info-label">Purpose</td><td class="text-end info-value">{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}</td></tr>
                                    <tr><td class="info-label">Referral</td><td class="text-end info-value">{{ ucfirst(str_replace('_', ' ', $app->referral_source ?? '—')) }}</td></tr>
                                    <tr><td class="info-label">Applied</td><td class="text-end info-value">{{ date('M d, Y', strtotime($app->applied_at)) }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="col-lg-8">

                        <!-- Admin Review Status -->
                        <div class="section-card mb-3">
                            <p class="section-title"><i class="feather-users me-2"></i>Admin Review Status ({{ $approvedCount ?? 0 }}/3 approved)</p>
                            <div class="row g-3">
                                @for($i = 0; $i < 3; $i++)
                                    @php $review = $allReviews[$i] ?? null; @endphp
                                    <div class="col-md-4">
                                        @php
                                        $boxClass  = $review ? $review->decision : 'awaiting';
                                        $iconName  = $review ? ($review->decision === 'approved' ? 'check' : 'x') : 'clock';
                                        $iconColor = $review ? ($review->decision === 'approved' ? '#16a34a' : '#dc2626') : '#94a3b8';
                                        $iconBg    = $review ? ($review->decision === 'approved' ? '#dcfce7' : '#fee2e2') : '#f1f5f9';
                                        @endphp
                                        <div class="review-box {{ $boxClass }}">
                                            <div class="review-icon" style="background:{{ $iconBg }};">
                                                <i class="feather-{{ $iconName }}" style="color:{{ $iconColor }};"></i>
                                            </div>
                                            @if($review)
                                                <p class="fw-bold mb-1" style="font-size:0.85rem;">{{ $review->admin_name }}</p>
                                                <span class="badge {{ $review->decision === 'approved' ? 'badge-approved' : 'badge-rejected' }} px-2 py-1">
                                                    {{ ucfirst($review->decision) }}
                                                </span>
                                                <p class="ld-review-time mb-0">{{ \Carbon\Carbon::parse($review->reviewed_at)->format('M d, Y g:i A') }}</p>
                                                @if($review->remarks)
                                                <p class="ld-review-remark mb-0">&ldquo;{{ $review->remarks }}&rdquo;</p>
                                                @endif
                                            @else
                                                <p class="fw-bold mb-1" style="font-size:0.85rem;">Admin {{ $i + 1 }}</p>
                                                <span class="badge badge-pending px-2 py-1">Awaiting</span>
                                            @endif
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="section-card mb-3">
                            <p class="section-title"><i class="feather-user me-2"></i>Personal Information</p>
                            <div class="row g-3">
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Full Name</span><span class="info-value">{{ $app->first_name }} {{ $app->middle_name }} {{ $app->last_name }}{{ ($app->suffix ?? null) ? ', ' . $app->suffix : '' }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Birthday</span><span class="info-value">{{ ($app->birthday ?? null) ? \Carbon\Carbon::parse($app->birthday)->format('F d, Y') : '—' }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Gender</span><span class="info-value">{{ ucfirst($app->gender ?? '—') }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Civil Status</span><span class="info-value">{{ ucfirst($app->civil_status ?? '—') }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Citizenship</span><span class="info-value">{{ $app->citizenship ?? '—' }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Cellphone</span><span class="info-value">{{ $app->cellphone_num ?? '—' }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Email</span><span class="info-value">{{ $app->email }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Monthly Income</span><span class="info-value text-success">{{ ($app->monthly_income ?? null) ? '₱' . number_format($app->monthly_income, 2) : '—' }}</span></div></div>
                            </div>
                        </div>

                        <!-- Present Address -->
                        <div class="section-card mb-3">
                            <p class="section-title"><i class="feather-map-pin me-2"></i>Present Address</p>
                            <div class="row g-3">
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Region</span><span class="info-value">{{ $address->region ?? '—' }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Province</span><span class="info-value">{{ $address->province ?? '—' }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">City / Municipality</span><span class="info-value">{{ $address->city ?? '—' }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Barangay</span><span class="info-value">{{ $address->barangay ?? '—' }}</span></div></div>
                                <div class="col-12"><div class="ld-field"><span class="info-label">Home Address</span><span class="info-value">{{ $address->home_address ?? '—' }}</span></div></div>
                            </div>
                        </div>

                        <!-- Work Information -->
                        <div class="section-card mb-3">
                            <p class="section-title"><i class="feather-briefcase me-2"></i>Work Information</p>
                            <div class="row g-3">
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Employment Status</span><span class="info-value">{{ ucfirst(str_replace('_', ' ', $work->employment_status ?? '—')) }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Company / School</span><span class="info-value">{{ $work->company_name ?? '—' }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Position</span><span class="info-value">{{ $work->position ?? '—' }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">Years in Company</span><span class="info-value">{{ $work->years_in_company ?? '—' }} years</span></div></div>
                            </div>
                        </div>

                        <!-- Valid ID -->
                        @if($app->id_file_path ?? null)
                        <div class="section-card mb-3">
                            <p class="section-title"><i class="feather-credit-card me-2"></i>Submitted Valid ID</p>
                            <div class="row g-3">
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">ID Type</span><span class="info-value">{{ ucfirst(str_replace('_', ' ', $app->id_type ?? '—')) }}</span></div></div>
                                <div class="col-md-6"><div class="ld-field"><span class="info-label">ID Number</span><span class="info-value">{{ $app->id_number ?? '—' }}</span></div></div>
                                <div class="col-12">
                                    @php $ext = strtolower(pathinfo($app->id_file_path, PATHINFO_EXTENSION)); @endphp
                                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                        <div class="ld-id-viewer">
                                            <img src="{{ asset('storage/' . $app->id_file_path) }}" alt="Valid ID">
                                        </div>
                                        <div class="ld-id-actions">
                                            <a href="{{ asset('storage/' . $app->id_file_path) }}" target="_blank" class="btn-id-action">
                                                <i class="feather-maximize-2"></i> View Full Size
                                            </a>
                                            <a href="{{ asset('storage/' . $app->id_file_path) }}" download class="btn-id-action">
                                                <i class="feather-download"></i> Download
                                            </a>
                                        </div>
                                    @elseif($ext === 'pdf')
                                        <div class="ld-id-viewer">
                                            <i class="feather-file-text" style="font-size:44px;color:#94a3b8;"></i>
                                            <p class="text-muted mt-2 mb-0" style="font-size:0.82rem;">PDF document</p>
                                        </div>
                                        <div class="ld-id-actions">
                                            <a href="{{ asset('storage/' . $app->id_file_path) }}" target="_blank" class="btn-id-action">
                                                <i class="feather-external-link"></i> View Full Size
                                            </a>
                                            <a href="{{ asset('storage/' . $app->id_file_path) }}" download class="btn-id-action">
                                                <i class="feather-download"></i> Download
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Discussion -->
                        <div class="section-card">
                            <p class="section-title"><i class="feather-message-square me-2"></i>Discussion</p>

                            <div class="ld-discussion">
                                @forelse($comments as $c)
                                <div class="ld-comment">
                                    <div class="ld-comment-avatar">{{ strtoupper(substr($c->admin_name ?? '?', 0, 1)) }}</div>
                                    <div class="ld-comment-body">
                                        <div class="ld-comment-head">
                                            <span class="ld-comment-name">{{ $c->admin_name }}</span>
                                            @isset($c->created_at)
                                            <span class="ld-comment-time">{{ \Carbon\Carbon::parse($c->created_at)->diffForHumans() }}</span>
                                            @endisset
                                        </div>
                                        <p class="ld-comment-text">{{ $c->comment }}</p>
                                    </div>
                                </div>
                                @empty
                                <p class="ld-discussion-empty">No comments yet. Be the first to add one.</p>
                                @endforelse
                            </div>

                            <form method="POST" action="{{ route('admin.loan-application-detail', $applicationId) }}" class="ld-comment-form">
                                @csrf
                                <textarea name="comment" class="form-control" placeholder="Add a comment..."></textarea>
                                <button type="submit" class="btn-post-comment">Post Comment</button>
                            </form>
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
@if($app->status !== 'rejected' && $app->status !== 'approved' && !$myReview)
<div class="modal fade modal-modern" id="decisionModal" tabindex="-1" aria-labelledby="decisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.loan-application-detail', $applicationId) }}" id="decisionForm">
                @csrf
                <input type="hidden" name="action" id="decisionActionInput" value="">

                <div class="modal-header-clean">
                    <div class="header-left">
                    
                        <h6 id="decisionModalLabel">Review Loan Application</h6>
                    </div>
                    <button type="button" class="btn-close-clean" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>

                <div class="modal-body">

                    <!-- Step 1: remarks -->
                    <div id="decisionStep1">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">Review Remarks <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="remarks" id="decisionRemarks" class="form-control" rows="3" placeholder="Provide additional comments or reason for your decision..."></textarea>
                    </div>

                    <!-- Step 2: confirmation -->
                    <div id="decisionStep2" style="display:none;">
                        <div class="modal-icon-circle icon-success" id="decisionConfirmIconWrap">
                            <i id="decisionConfirmIcon" class="feather-check"></i>
                        </div>
                        <p id="decisionConfirmText" class="modal-title-text"></p>

                        <div class="modal-detail-card">
                            <div class="modal-detail-row">
                                <span class="modal-detail-label">Applicant</span>
                                <span class="modal-detail-value">{{ $app->first_name }} {{ $app->last_name }}</span>
                            </div>
                            <div class="modal-detail-row">
                                <span class="modal-detail-label">Loan amount</span>
                                <span class="modal-detail-value">₱{{ number_format($app->loan_amount, 2) }}</span>
                            </div>
                            <div class="modal-detail-row">
                                <span class="modal-detail-label">Current status</span>
                                <span class="modal-detail-value">{{ ucfirst(str_replace('_', ' ', $app->status)) }}</span>
                            </div>
                        </div>

                       
                    </div>

                </div>

                <div class="modal-footer" id="decisionFooterStep1">
                
                    <button type="button" class="btn btn-modal-primary btn-approve" onclick="decisionShowConfirm('approved')">Approve</button>
                    <button type="button" class="btn btn-modal-primary btn-reject" onclick="decisionShowConfirm('rejected')">Reject</button>
                </div>
                <div class="modal-footer" id="decisionFooterStep2" style="display:none;">
                    <button type="button" class="btn btn-modal-cancel" onclick="decisionBackToStep1()">Back</button>
                    <button type="submit" class="btn btn-modal-primary" id="decisionConfirmBtn"></button>
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
    <script src="{{ asset('functions/loan-application-detail.js') }}"></script>
</body>
</html>