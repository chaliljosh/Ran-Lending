<!doctype html>
<html lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BORROWER DASHBOARD</title>
    <link rel="shortcut icon" type="image/x-icon" href="/assets/borrower/images/favicon.ico" />
    <link rel="stylesheet" type="text/css" href="/assets/borrower/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="/assets/borrower/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="/assets/borrower/vendors/css/daterangepicker.min.css" />
    <link rel="stylesheet" type="text/css" href="/assets/borrower/css/theme.min.css" />
    <style>
        .nxl-container { min-height: calc(100vh - 80px) !important; display: flex !important; flex-direction: column !important; }
        .nxl-content { flex: 1 !important; display: flex !important; flex-direction: column !important; }
        .nxl-content .main-content { flex: 1 !important; }
        .footer { flex-shrink: 0 !important; margin-top: auto !important; }
    </style>
</head>
<body>

    <nav class="nxl-navigation">
        <div class="navbar-wrapper" style="position: relative;">
            <div class="m-header">
                <a href="{{ route('borrower.dashboard') }}" class="b-brand">
                    <h3 class="fst-italic">Ran Serenity</h3>
                </a>
            </div>
            <div class="navbar-content" style="padding-bottom: 80px;">
                <ul class="nxl-navbar">
                    <li class="nxl-item">
                        <a href="{{ route('borrower.dashboard') }}" class="nxl-link active">
                            <span class="nxl-micon"><i class="feather-airplay"></i></span>
                            <span class="nxl-mtext">Dashboard</span>
                        </a>
                    </li>

                    @if(!$isFullyApproved)
                    <li class="nxl-item">
                        <a href="/borrower/personal-details" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Personal Details</span>
                        </a>
                    </li>
                    @endif

                    @if($isFullyApproved)
                    <li class="nxl-item">
                        <a href="/borrower/apply-loan" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-file-plus"></i></span>
                            <span class="nxl-mtext">Apply for a Loan</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="/borrower/loan-details" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-file-text"></i></span>
                            <span class="nxl-mtext">View Loan Details</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="/borrower/notifications" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-bell"></i></span>
                            <span class="nxl-mtext">Notifications</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="/borrower/profile" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user"></i></span>
                            <span class="nxl-mtext">My Profile</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="javascript:void(0);" onclick="confirmLogout()" class="nxl-link" style="color:#e74c3c;">
                            <span class="nxl-micon"><i class="feather-log-out" style="color:#e74c3c;"></i></span>
                            <span class="nxl-mtext">Logout</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>

            <div style="padding:14px 16px;border-top:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:12px;position:absolute;bottom:0;left:0;right:0;background:inherit;">
                <div style="width:38px;height:38px;border-radius:50%;background:#4a7cf7;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:0.85rem;flex-shrink:0;">
                    {{ $initials }}
                </div>
                <div style="overflow:hidden;flex:1;">
                    <p style="margin:0;font-size:0.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $userName }}</p>
                    <p style="margin:0;font-size:0.75rem;opacity:0.6;">Borrower</p>
                </div>
                <a href="javascript:void(0);" onclick="confirmLogout()" title="Logout" style="color:#e74c3c;font-size:1.1rem;flex-shrink:0;opacity:0.8;">
                    <i class="feather-log-out"></i>
                </a>
            </div>
        </div>
    </nav>

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
                <div class="d-flex align-items-center">
                    <div class="nxl-h-item dark-light-theme">
                        <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button"><i class="feather-moon"></i></a>
                        <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display:none"><i class="feather-sun"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-left d-flex align-items-center">
                    <div class="page-header-title"><h5 class="m-b-10">Dashboard</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Dashboard</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">

                @if($detailsStatus === 'incomplete')
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card text-center" style="border: 2px dashed #4a7cf7;">
                            <div class="card-body py-5">
                                <div style="width:80px; height:80px; background:#e8f0fe; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                                    <i class="feather-user-check" style="font-size:36px; color:#4a7cf7;"></i>
                                </div>
                                <h4 class="fw-bold mb-2">Complete Your Profile</h4>
                                <p class="text-muted mb-4">Please fill up your personal details first before you can access the full dashboard and apply for a loan.</p>
                                <a href="/borrower/personal-details" class="btn btn-primary px-5">
                                    <i class="feather-edit me-2"></i>Fill Up Personal Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                @elseif($detailsStatus === 'pending')
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card text-center">
                            <div class="card-body py-5">
                                <div style="width:80px; height:80px; background:#fff8e1; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                                    <i class="feather-clock" style="font-size:36px; color:#f0a500;"></i>
                                </div>
                                <h4 class="fw-bold mb-2">Under Review</h4>
                                <p class="text-muted mb-4">Your personal details have been submitted and are currently being reviewed by our admins. Please wait for approval.</p>
                                <div style="background:#f8f9fa; border-radius:12px; padding:20px; margin-bottom:20px;">
                                    <p class="fw-bold mb-2" style="font-size:0.85rem; color:#6c757d; text-transform:uppercase; letter-spacing:0.5px;">Admin Approvals</p>
                                    <h2 style="font-size:2.5rem; font-weight:700; margin:0;">{{ $approvedCount }} / 3</h2>
                                </div>
                                <p class="text-muted fs-12">You will be notified once your account is fully approved.</p>
                            </div>
                        </div>
                    </div>
                </div>

                @elseif($detailsStatus === 'rejected')
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card text-center" style="border: 2px solid #e74c3c;">
                            <div class="card-body py-5">
                                <div style="width:80px; height:80px; background:#fee2e2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                                    <i class="feather-x-circle" style="font-size:36px; color:#e74c3c;"></i>
                                </div>
                                <h4 class="fw-bold mb-2">Application Rejected</h4>
                                <p class="text-muted mb-4">Unfortunately, your personal details were not approved. Please update your information and resubmit.</p>
                                <a href="/borrower/personal-details" class="btn btn-danger px-5">
                                    <i class="feather-edit me-2"></i>Update Personal Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                @else
                <div class="row">
                    <div class="col-xxl-3 col-md-6">
                        <div class="card stretch stretch-full">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-4">
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="avatar-text avatar-lg bg-gray-200">
                                            <i class="feather-file-text"></i>
                                        </div>
                                        <div>
                                            <span class="fs-13 fw-semibold text-muted">Loan Application</span>
                                            <h3>{{ $loanStatus['label'] }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="progress ht-3">
                                    <div class="progress-bar {{ $loanStatus['bar'] }}" style="width:{{ $loanStatus['progress'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-md-6">
                        <div class="card stretch stretch-full">
                            <div class="card-body">
                                <div class="d-flex gap-4 align-items-center mb-4">
                                    <div class="avatar-text avatar-lg bg-gray-200">
                                        <i class="feather-calendar"></i>
                                    </div>
                                    <div>
                                        <span class="fs-13 fw-semibold text-muted">Loan Term</span>
                                        <h3>{{ $latestLoan ? $latestLoan->loan_term . ' Months' : '—' }}</h3>
                                    </div>
                                </div>
                                <p class="fs-12 text-muted">
                                    {{ $latestLoan ? 'Interest Rate: ' . number_format($latestLoan->interest_rate, 2) . '% monthly' : 'No active loan term yet' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-md-6">
                        <div class="card stretch stretch-full">
                            <div class="card-body">
                                <div class="d-flex gap-4 align-items-center mb-4">
                                    <div class="avatar-text avatar-lg bg-gray-200">
                                        <i class="feather-credit-card"></i>
                                    </div>
                                    <div>
                                        <span class="fs-13 fw-semibold text-muted">Outstanding Balance</span>
                                        <h3>{{ $latestLoan ? '₱' . number_format($latestLoan->total_amount, 2) : '₱0.00' }}</h3>
                                    </div>
                                </div>
                                <p class="fs-12 text-muted">
                                    {{ $latestLoan ? 'Principal + Interest' : 'No active loan' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-md-6">
                        <div class="card stretch stretch-full">
                            <div class="card-body">
                                <div class="d-flex gap-4 align-items-center mb-4">
                                    <div class="avatar-text avatar-lg bg-gray-200">
                                        <i class="feather-user-check"></i>
                                    </div>
                                    <div>
                                        <span class="fs-13 fw-semibold text-muted">Account Status</span>
                                        <h3 class="text-success">Active</h3>
                                    </div>
                                </div>
                                <p class="fs-12 text-muted">Eligible for loan processing</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-8">
                        <div class="card stretch stretch-full">
                            <div class="card-header"><h5 class="card-title">Application Timeline</h5></div>
                            <div class="card-body fs-13">
                                @if(!$latestLoan)
                                    <p class="text-muted mb-0">You haven't applied for a loan yet.</p>
                                @else
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="feather-check-circle text-success me-2"></i>
                                            Application Submitted —
                                            <span class="text-muted">{{ \Carbon\Carbon::parse($latestLoan->applied_at)->format('F j, Y') }}</span>
                                        </li>
                                        <li class="mb-0">
                                            <i class="feather-info me-2"></i>
                                            Current Status: <strong>{{ $loanStatus['label'] }}</strong>
                                        </li>
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card stretch stretch-full">
                            <div class="card-header"><h6>Notifications</h6></div>
                            <div class="card-body fs-13">
                                @if(!$app)
                                    <p class="text-muted mb-0">You don't have an active loan yet, so there are no payment reminders.</p>
                                @elseif(empty($notifications))
                                    <p class="text-success mb-0"><i class="feather-check-circle me-1"></i>You're all caught up — no payments due soon.</p>
                                @else
                                    <p class="mb-2">{{ $notifications[0]['message'] }}</p>
                                    <a href="/borrower/notifications" class="fs-12 fw-semibold">View all notifications →</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>

        <footer class="footer">
            <p class="text-center mb-0">
                <span>By: <a target="_blank" href="#">Ran Serenity</a></span>
            </p>
            <div class="d-flex align-items-center gap-4">
                <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Help</a>
                <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Terms</a>
                <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Privacy</a>
            </div>
        </footer>
    </main>

    <script src="/assets/borrower/vendors/js/vendors.min.js"></script>
    <script src="/assets/borrower/vendors/js/daterangepicker.min.js"></script>
    <script src="/assets/borrower/vendors/js/apexcharts.min.js"></script>
    <script src="/assets/borrower/vendors/js/circle-progress.min.js"></script>
    <script src="/assets/borrower/js/common-init.min.js"></script>
    <script src="/assets/borrower/js/dashboard-init.min.js"></script>
    <script src="/assets/borrower/js/theme-customizer-init.min.js"></script>
    <script src="/functions/logout-modal.js"></script>
</body>
</html>