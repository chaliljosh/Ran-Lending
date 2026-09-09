<!doctype html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Borrower') - Ran Lending</title>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/borrower/images/favicon.ico') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/logout-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/settings-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/header-profile.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/header-notifications.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-layout.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/avatar-placeholder.css') }}" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    {{-- Page-specific <link> stylesheets go here — e.g. borrower-dashboard.css,
         Cropper.js CDN CSS, atbp. --}}
    @yield('page-head-links')
    {{-- Layout-level structural/performance fixes — MUST be last so it wins
         the cascade over all the CSS files above (feather icon font fix
         needs to override the wildcard font-family overrides in
         borrower-sidebar.css, header-profile.css, borrower-dashboard.css). --}}
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/layout-fixes.css') }}?v={{ time() }}">
</head>

<body>

    @include('borrower.partials.sidebar')

    <header class="nxl-header">
        <div class="header-wrapper">
            <div class="header-left d-flex align-items-center gap-4">
                <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                    <div class="hamburger hamburger--arrowturn">
                        <div class="hamburger-box">
                            <div class="hamburger-inner"></div>
                        </div>
                    </div>
                </a>
                <div class="nxl-navigation-toggle">
                    <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                    <a href="javascript:void(0);" id="menu-expend-button" style="display:none"><i
                            class="feather-arrow-right"></i></a>
                </div>
            </div>
            <div class="header-right ms-auto">
                <div class="d-flex align-items-center gap-2">
                    <div class="nxl-h-item dark-light-theme">
                        <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button"><i
                                class="feather-moon"></i></a>
                        <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display:none"><i
                                class="feather-sun"></i></a>
                    </div>

                    {{-- ============ HEADER — NOTIFICATION BELL ============ --}}
                    <details class="bwl-notif-bell" name="header-dropdown-group">
                        <summary class="bwl-notif-trigger">
                            <i class="feather-bell"></i>
                            @if (($unreadNotifCount ?? 0) > 0)
                                <span
                                    class="bwl-notif-badge">{{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}</span>
                            @endif
                        </summary>
                        <div class="bwl-notif-dropdown">
                            <div class="bwl-notif-head">
                                <h6>Notifications</h6>
                                @if (($unreadNotifCount ?? 0) > 0)
                                    <button type="button" id="bwlNotifMarkAllRead"
                                        data-url="{{ route('borrower.notifications.mark-all-read') }}">Mark all as
                                        read</button>
                                @endif
                            </div>
                            <div class="bwl-notif-list">
                                @forelse(($headerNotifications ?? collect()) as $n)
                                    <a href="{{ route('borrower.notification-open', $n['key']) }}?redirect={{ urlencode($n['url']) }}"
                                        class="bwl-notif-item {{ $n['read'] ? '' : 'is-unread' }}">
                                        <span class="bwl-notif-item-body">
                                            <span class="bwl-notif-item-title">{{ $n['title'] }}</span>
                                            <span class="bwl-notif-item-sub">{{ $n['subtitle'] }}</span>
                                            @if (!empty($n['ts']))
                                                <span
                                                    class="bwl-notif-item-time">{{ $n['ts']->diffForHumans() }}</span>
                                            @endif
                                        </span>
                                    </a>
                                @empty
                                    <p class="bwl-notif-empty">No notifications yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </details>


                    {{-- ============ HEADER — BORROWER PROFILE DROPDOWN ============ --}}
                    <details class="bwl-header-profile" name="header-dropdown-group">
                        <summary class="bwl-profile-trigger">
                            <span class="bwl-avatar js-borrower-avatar" data-initials="{{ $initials }}">
                                @if ($profilePicture ?? false)
                                    <img src="{{ $profilePicture }}" alt="{{ $userName }}"
                                        onerror="this.parentElement.textContent=this.parentElement.dataset.initials;">
                                @else
                                    {{ $initials }}
                                @endif
                            </span>
                            <span class="bwl-name-block">
                                <span class="bwl-name">{{ $userName }}</span>
                                <span class="bwl-role">Borrower</span>
                            </span>
                            <i class="feather-chevron-down bwl-caret"></i>
                        </summary>
                        <div class="bwl-dropdown-menu">
                            <div class="bwl-dropdown-head">
                                <span class="bwl-dropdown-avatar js-borrower-avatar"
                                    data-initials="{{ $initials }}">
                                    @if ($profilePicture ?? false)
                                        <img src="{{ $profilePicture }}" alt="{{ $userName }}"
                                            onerror="this.parentElement.textContent=this.parentElement.dataset.initials;">
                                    @else
                                        {{ $initials }}
                                    @endif
                                </span>
                                <div>
                                    <div class="bwl-dropdown-name">{{ $userName }}</div>
                                    <div class="bwl-dropdown-role">Borrower</div>
                                </div>
                            </div>
                            <div class="bwl-dropdown-divider"></div>
                            <button type="button" class="bwl-dropdown-item" data-bs-toggle="modal"
                                data-bs-target="#bwlSettingsModal"
                                style="border:none; background:none; width:100%; text-align:left;">
                                <span class="bwl-dropdown-item-icon"><i class="feather-settings"></i></span>
                                Settings
                            </button>
                            <button type="button" class="bwl-dropdown-item" data-bs-toggle="modal"
                                data-bs-target="#bwlChangePasswordModal"
                                style="border:none; background:none; width:100%; text-align:left;">
                                <span class="bwl-dropdown-item-icon"><i class="feather-lock"></i></span>
                                Change Password
                            </button>
                            <div class="bwl-dropdown-divider"></div>
                            <button type="button" class="bwl-dropdown-item text-danger" onclick="confirmLogout()">
                                <span class="bwl-dropdown-item-icon"><i class="feather-log-out"></i></span>
                                Logout
                            </button>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </header>

    <main class="nxl-container">
        <div class="nxl-content">
            @yield('content')
        </div>

        <footer class="footer">
            <p class="text-center mb-0">
                <span>By: <a target="_blank" href="#">Ran Serenity</a></span>
            </p>
            <div class="d-flex align-items-center gap-4">
                <a href="{{ route('borrower.faq') }}" class="fs-11 fw-semibold text-uppercase">Help</a>
                <a href="{{ route('borrower.terms') }}" class="fs-11 fw-semibold text-uppercase">Terms</a>
                <a href="{{ route('borrower.privacy') }}" class="fs-11 fw-semibold text-uppercase">Privacy</a>
            </div>
        </footer>
    </main>

    {{-- ACCOUNT SETTINGS MODAL — included dito para available saanman
         sa borrower area, dahil ang trigger niya (header dropdown) ay
         laging visible sa lahat ng pages. --}}
    @include('borrower.partials.settings-modal')

    {{-- Modals pushed from child pages render here — OUTSIDE .nxl-container,
         same reasoning as the admin layout: avoids the blur-filter-on-modal-open
         issue when a modal is nested inside a blurred container. --}}
    @stack('modals')

    <script src="{{ asset('assets/borrower/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/borrower/vendors/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/borrower/vendors/js/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/borrower/vendors/js/circle-progress.min.js') }}"></script>
    <script src="{{ asset('assets/borrower/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/borrower/js/dashboard-init.min.js') }}"></script>
    <script src="{{ asset('assets/borrower/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/borrower-header-notifications.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="{{ asset('functions/settings-modal.js') }}"></script>

    {{-- Page-specific scripts go here --}}
    @yield('page-scripts')
</body>

</html>
