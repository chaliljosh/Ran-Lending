<!doctype html>
<html lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Notifications - Ran Lending</title>
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
                    <div class="page-header-title"><h5 class="m-b-10">Notifications</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Notifications</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card stretch stretch-full">
                            <div class="card-header">
                                <h5 class="card-title">Payment Updates &amp; Reminders</h5>
                            </div>
                            <div class="card-body">

                                @if(!$app)

                                    <div class="text-center py-5">
                                        <i class="feather-bell-off fs-1 text-muted mb-3 d-block"></i>
                                        <h6 class="fw-bold mb-1">No Notifications</h6>
                                        <p class="text-muted mb-0">You don't have an active loan yet, so there are no payment updates to show.</p>
                                    </div>

                                @elseif(empty($notifications))

                                    <div class="text-center py-5">
                                        <i class="feather-check-circle fs-1 text-success mb-3 d-block"></i>
                                        <h6 class="fw-bold mb-1">You're All Caught Up</h6>
                                        <p class="text-muted mb-0">No new payment updates, and no payments are due within the next 14 days.</p>
                                    </div>

                                @else

                                    @foreach($notifications as $index => $n)
                                        <div class="alert alert-{{ $n['type'] }} d-flex align-items-start {{ $index < count($notifications) - 1 ? 'mb-3' : 'mb-0' }}">
                                            <i class="{{ $n['icon'] }} me-3 fs-4"></i>
                                            <div class="flex-grow-1">
                                                <h6 class="alert-heading mb-1">{{ $n['title'] }}</h6>
                                                <p class="mb-0">{{ $n['message'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach

                                @endif

                            </div>
                        </div>
                    </div>
                </div>
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