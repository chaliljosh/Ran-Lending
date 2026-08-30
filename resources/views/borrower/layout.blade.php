<!doctype html>
<html lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Borrower') - Ran Lending</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/borrower/images/favicon.ico') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/logout-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/settings-modal.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    {{-- Page-specific <link> stylesheets (e.g. Cropper.js CDN CSS) go here —
         kept OUTSIDE the <style> block below since <link> tags can't be
         nested inside <style>. --}}
    @yield('page-head-links')
    <style>
        .nxl-container { min-height: calc(100vh - 80px) !important; display: flex !important; flex-direction: column !important; }
        .nxl-content { flex: 1 !important; display: flex !important; flex-direction: column !important; }
        .nxl-content .main-content { flex: 1 !important; }
        .footer { flex-shrink: 0 !important; margin-top: auto !important; }

        /* ============================================================
   Alisin yung blur filter na inilalapat ng theme.min.css sa
   .nxl-container (at kadikit na elements) tuwing may bukas na
   Bootstrap modal. Mabigat computationally yung blur, kaya
   laggy pag-open ng modal — same fix na ginawa natin sa Admin.
============================================================ */
body.modal-open .nxl-container,
body.modal-open .nxl-header,
body.modal-open .nxl-navigation {
    filter: none !important;
}

        /* ============================================================
           HEADER — PROFILE DROPDOWN (moved from bottom of sidebar)
        ============================================================ */
        .bwl-header-profile {
            position: relative;
            display: flex;
            align-items: center;
        }
        .bwl-profile-trigger {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px 5px 6px;
            border-radius: 8px;
            cursor: pointer;
            list-style: none;
            transition: background .15s ease;
        }
        .bwl-profile-trigger::-webkit-details-marker { display: none; }
        .bwl-profile-trigger::marker { content: ""; }
        .bwl-header-profile:hover .bwl-profile-trigger,
        .bwl-header-profile[open] .bwl-profile-trigger {
            background: rgba(0, 0, 0, 0.05);
        }
        .bwl-avatar {
            width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700;
            color: #fff; background: linear-gradient(135deg, #4a7cf7, #7ea3ff);
            overflow: hidden;
        }
        .bwl-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .bwl-name-block { display: flex; flex-direction: column; line-height: 1.2; }
        .bwl-name { font-size: 0.86rem; font-weight: 600; color: #1a2a3a; white-space: nowrap; max-width: 140px; overflow: hidden; text-overflow: ellipsis; }
        .bwl-role { font-size: 0.72rem; color: #8a9bb0; }
        .bwl-caret { font-size: 0.75rem; color: #8a9bb0; }

        .bwl-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            left: auto;
            min-width: 260px;
            background: #fff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 50px -14px rgba(15, 23, 42, 0.25);
            padding: 20px 0 12px;
            font-size: 0.85rem;
            z-index: 1050;
        }
        .bwl-dropdown-head { display: flex; align-items: center; gap: 14px; padding: 0 20px 16px; }
        .bwl-dropdown-avatar {
            width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.05rem; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, #4a7cf7, #7ea3ff); overflow: hidden;
        }
        .bwl-dropdown-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .bwl-dropdown-name { font-size: 0.95rem; font-weight: 700; color: #1a2a3a; }
        .bwl-dropdown-role { font-size: 0.78rem; color: #8a9bb0; }
        .bwl-dropdown-divider { height: 1px; background: rgba(0, 0, 0, 0.07); margin: 0 0 8px; }
        .bwl-dropdown-item {
            display: flex; align-items: center; gap: 14px; padding: 12px 20px;
            font-size: 0.88rem; font-weight: 600; color: #1a2a3a; text-decoration: none;
            transition: background .12s ease; background: none; border: none; width: 100%; text-align: left;
        }
        .bwl-dropdown-item-icon { width: 20px; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #8a9bb0; flex-shrink: 0; }
        .bwl-dropdown-item:hover { background: rgba(0, 0, 0, 0.04); color: #1a2a3a; }
        .bwl-dropdown-item.text-danger:hover { background: rgba(220, 38, 38, 0.08); }
        .bwl-dropdown-item.text-danger .bwl-dropdown-item-icon { color: #DC2626; }

        {{-- Page-specific RAW CSS rules go here (no <link> tags — use
             @section('page-head-links') above for those instead) --}}
        @yield('page-styles')
    </style>
</head>
<body>

    @include('borrower.partials.sidebar')

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
                <div class="d-flex align-items-center gap-2">
                    <div class="nxl-h-item dark-light-theme">
                        <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button"><i class="feather-moon"></i></a>
                        <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display:none"><i class="feather-sun"></i></a>
                    </div>

                    {{-- ============ HEADER — BORROWER PROFILE DROPDOWN ============ --}}
                    <details class="bwl-header-profile" name="header-dropdown-group">
                        <summary class="bwl-profile-trigger">
                            <span class="bwl-avatar js-borrower-avatar" data-initials="{{ $initials }}">
                                @if($profilePicture ?? false)
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
        <span class="bwl-dropdown-avatar js-borrower-avatar" data-initials="{{ $initials }}">
            @if($profilePicture ?? false)
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
    <button type="button" class="bwl-dropdown-item" data-bs-toggle="modal" data-bs-target="#bwlSettingsModal" style="border:none; background:none; width:100%; text-align:left;">
        <span class="bwl-dropdown-item-icon"><i class="feather-settings"></i></span>
        Settings
    </button>
    <button type="button" class="bwl-dropdown-item" data-bs-toggle="modal" data-bs-target="#bwlChangePasswordModal" style="border:none; background:none; width:100%; text-align:left;">
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
                <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Help</a>
                <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Terms</a>
                <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Privacy</a>
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
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="{{ asset('functions/settings-modal.js') }}"></script>
    {{-- Page-specific scripts go here --}}
    @yield('page-scripts')
</body>
</html>