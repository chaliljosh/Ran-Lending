<!doctype html>
<html lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Loan Details - Ran Lending</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/borrower/images/favicon.ico') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/css/theme.min.css') }}" />
     <link rel="stylesheet" href="{{ asset('assets/borrower/css/logout-modal.css') }}">
    <style>
        .nxl-container { min-height: calc(100vh - 80px) !important; display: flex !important; flex-direction: column !important; }
        .nxl-content { flex: 1 !important; display: flex !important; flex-direction: column !important; }
        .nxl-content .main-content { flex: 1 !important; }
        .footer { flex-shrink: 0 !important; margin-top: auto !important; }
    </style>
</head>
<body>
    @include('borrower.partials.sidebar')

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
                    <div class="page-header-title"><h5 class="m-b-10">View Loan Details</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Loan Details</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">

                @if(!$app)

                    <!-- No loan application yet -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card stretch stretch-full">
                                <div class="card-body text-center py-5">
                                    <i class="feather-file-text fs-1 text-muted mb-3 d-block"></i>
                                    <h5 class="fw-bold mb-2">No Loan Application Yet</h5>
                                    <p class="text-muted mb-3">You haven't submitted a loan application. Apply now to get started.</p>
                                   
                                </div>
                            </div>
                        </div>
                    </div>

                @else

                <div class="row">

                    <!-- Loan Summary Card -->
                    <div class="col-lg-12">
                        <div class="card stretch stretch-full">
                            <div class="card-header">
                                <h5 class="card-title">Loan Summary</h5>
                                <span class="badge {{ $status['badge'] }}">{{ $status['label'] }}</span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted mb-1">Loan Amount</p>
                                        <h4 class="fw-bold">₱{{ number_format($app->loan_amount, 2) }}</h4>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted mb-1">Loan Term</p>
                                        <h4 class="fw-bold">{{ (int) $app->loan_term }} Months</h4>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted mb-1">Interest Rate</p>
                                        <h4 class="fw-bold">{{ number_format($app->interest_rate, 2) }}% Monthly</h4>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted mb-1">Application Date</p>
                                        <p class="fs-13">{{ \Carbon\Carbon::parse($app->applied_at)->format('F j, Y') }}</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted mb-1">Status</p>
                                        <p class="fs-13"><span class="badge {{ $status['badge'] }}">{{ $status['label'] }}</span></p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted mb-1">Loan ID</p>
                                        <p class="fs-13">{{ $loanId }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Schedule -->
                    <div class="col-lg-8">
                        <div class="card stretch stretch-full">
                            <div class="card-header">
                                <h5 class="card-title">Payment Schedule</h5>
                            </div>
                            <div class="card-body">
                                @if($app->status !== 'approved' && $app->status !== 'active')
                                    <p class="text-muted fs-13 mb-3">
                                        <i class="feather-info me-1"></i>
                                        This is an estimated schedule based on your requested amount and term. It will be finalized once your application is approved.
                                    </p>
                                @endif
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th>Due Date</th>
                                                <th>Amount Due</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paymentSchedule as $row)
                                                <tr>
                                                    <td>Month {{ $row['month'] }}</td>
                                                    <td>{{ $row['due']->format('F j, Y') }}</td>
                                                    <td>₱{{ number_format($row['amount'], 2) }}</td>
                                                    <td><span class="badge bg-secondary">Pending</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loan Information -->
                    <div class="col-lg-4">
                        <div class="card stretch stretch-full">
                            <div class="card-header">
                                <h5 class="card-title">Loan Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <p class="text-muted mb-1">Total Amount to Pay</p>
                                    <h4 class="fw-bold text-primary">₱{{ number_format($app->total_amount, 2) }}</h4>
                                    <small class="text-muted">(Principal + Interest)</small>
                                </div>
                                <div class="mb-4">
                                    <p class="text-muted mb-1">Monthly Payment</p>
                                    <h5 class="fw-bold">₱{{ number_format($app->monthly_payment, 2) }}</h5>
                                </div>
                                <div class="mb-3">
                                    <p class="text-muted mb-1">Remaining Balance</p>
                                    <h5 class="fw-bold">₱{{ number_format($app->total_amount, 2) }}</h5>
                                    <small class="text-muted">No payments recorded yet</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Application Timeline -->
                    <div class="col-lg-12">
                        <div class="card stretch stretch-full">
                            <div class="card-header">
                                <h5 class="card-title">Application Timeline</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-3">
                                        <i class="feather-check-circle text-success me-2"></i>
                                        <strong>Application Submitted</strong>
                                        <p class="text-muted fs-12 ms-4 mb-0">{{ \Carbon\Carbon::parse($app->applied_at)->format('F j, Y g:i A') }}</p>
                                    </li>
                                    <li class="mb-0">
                                        <i class="{{ $status['icon'] }} {{ $status['icon_color'] }} me-2"></i>
                                        <strong>Current Status: {{ $status['label'] }}</strong>
                                        <p class="text-muted fs-12 ms-4 mb-0">
                                            @if($app->status === 'pending')
                                                Your application is being reviewed by our admins.
                                            @elseif($app->status === 'approved' || $app->status === 'active')
                                                Your loan has been approved.
                                            @elseif($app->status === 'rejected')
                                                Your application was not approved.
                                            @elseif($app->status === 'on_hold')
                                                Your application is on hold pending additional review.
                                            @elseif($app->status === 'completed')
                                                Your loan has been fully paid off.
                                            @elseif($app->status === 'defaulted')
                                                Your loan has been marked as defaulted.
                                            @endif
                                        </p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

                @endif

            </div>
        </div>

        <footer class="footer">
            <p class="text-center"><span>By: <a target="_blank" href="#">Ran Serenity</a></span></p>
            <div class="d-flex align-items-center gap-4">
                <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Help</a>
                <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Terms</a>
                <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Privacy</a>
            </div>
        </footer>
    </main>

    <script src="{{ asset('assets/borrower/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/borrower/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/borrower/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
</body>
</html>