<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="login-route" content="{{ route('login') }}">
    <meta name="requires-auth" content="true">
    <title>Ran Serenity || @yield('title', 'Dashboard')</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-shared-modals.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-header-dropdown.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/avatar-placeholder.css') }}" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-toast.css') }}" />
    {{-- Page-specific stylesheets (e.g. admin-dashboard.css) go here --}}
    @yield('page-styles')
</head>

<body>

    @php
        $currentRouteName = request()->route()?->getName();

        $bvRoutes = ['admin.borrower-verification', 'admin.verification-detail'];
        $laRoutes = ['admin.loan-applications', 'admin.loan-application-detail'];

        $isBorrowerVerificationSection = in_array($currentRouteName, $bvRoutes, true);
        $isLoanApplicationsSection = in_array($currentRouteName, $laRoutes, true);

        $bvStatusTab = $isBorrowerVerificationSection ? $viewStatus ?? 'pending' : null;

        $rawLoanStatus = $isLoanApplicationsSection ? $viewStatus ?? 'pending' : null;
        $loanStatusTab = in_array($rawLoanStatus, ['approved', 'active'], true) ? 'approved' : $rawLoanStatus;
    @endphp

    <nav class="nxl-navigation">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="{{ route('admin.dashboard') }}" class="b-brand">
                    <h3 class="fst-italic">Ran Serenity</h3>
                </a>
            </div>
            <div class="navbar-content">
                <ul class="nxl-navbar">
                    <li class="nxl-item">
                        <a href="{{ route('admin.dashboard') }}"
                            class="nxl-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-airplay"></i></span>
                            <span class="nxl-mtext">Dashboard</span>
                        </a>
                    </li>

                    <!-- Borrower Verification -->
                    <li class="nxl-item nxl-hasmenu {{ $isBorrowerVerificationSection ? 'active' : '' }}">
                        <a href="javascript:void(0);"
                            class="nxl-link {{ $isBorrowerVerificationSection ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Borrower Verification</span>
                            @if ($pendingCount > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item" data-current="{{ $bvStatusTab === 'pending' ? 'true' : 'false' }}">
                                <a class="nxl-link nxl-submenu-link-badge" href="{{ route('admin.borrower-verification') }}">
                                    Pending
                                    @if ($pendingCount > 0)
                                        <span class="badge bg-danger nxl-submenu-badge">{{ $pendingCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nxl-item" data-current="{{ $bvStatusTab === 'reviewed' ? 'true' : 'false' }}">
                                <a class="nxl-link"
                                    href="{{ route('admin.borrower-verification', ['status' => 'reviewed']) }}">Reviewed</a>
                            </li>
                            <li class="nxl-item" data-current="{{ $bvStatusTab === 'approved' ? 'true' : 'false' }}">
                                <a class="nxl-link"
                                    href="{{ route('admin.borrower-verification', ['status' => 'approved']) }}">Approved</a>
                            </li>
                            <li class="nxl-item" data-current="{{ $bvStatusTab === 'rejected' ? 'true' : 'false' }}">
                                <a class="nxl-link"
                                    href="{{ route('admin.borrower-verification', ['status' => 'rejected']) }}">Rejected</a>
                            </li>
                            <li class="nxl-item" data-current="{{ $bvStatusTab === 'on_hold' ? 'true' : 'false' }}">
                                <a class="nxl-link"
                                    href="{{ route('admin.borrower-verification', ['status' => 'on_hold']) }}">On
                                    Hold</a>
                            </li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu {{ $isLoanApplicationsSection ? 'active' : '' }}">
                        <a href="javascript:void(0);"
                            class="nxl-link {{ $isLoanApplicationsSection ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-file-text"></i></span>
                            <span class="nxl-mtext">Loan Applications</span>
                            @if (($pendingLoanCount ?? 0) > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingLoanCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"
                                data-current="{{ $loanStatusTab === 'pending' ? 'true' : 'false' }}">
                                <a class="nxl-link nxl-submenu-link-badge" href="{{ route('admin.loan-applications') }}">
                                    Pending Applications
                                    @if (($pendingLoanCount ?? 0) > 0)
                                        <span class="badge bg-danger nxl-submenu-badge">{{ $pendingLoanCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nxl-item"
                                data-current="{{ $loanStatusTab === 'reviewed' ? 'true' : 'false' }}">
                                <a class="nxl-link"
                                    href="{{ route('admin.loan-applications', ['status' => 'reviewed']) }}">Reviewed</a>
                            </li>
                            <li class="nxl-item"
                                data-current="{{ $loanStatusTab === 'approved' ? 'true' : 'false' }}">
                                <a class="nxl-link"
                                    href="{{ route('admin.loan-applications', ['status' => 'approved']) }}">Approved</a>
                            </li>
                            <li class="nxl-item"
                                data-current="{{ $loanStatusTab === 'rejected' ? 'true' : 'false' }}">
                                <a class="nxl-link"
                                    href="{{ route('admin.loan-applications', ['status' => 'rejected']) }}">Rejected</a>
                            </li>
                            <li class="nxl-item"
                                data-current="{{ $loanStatusTab === 'on_hold' ? 'true' : 'false' }}">
                                <a class="nxl-link"
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
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All
                                    Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link"
                                    href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link"
                                    href="{{ route('admin.penalty-tracking') }}">Penalty Tracking</a></li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                            <span class="nxl-mtext">Active Loans</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link"
                                    href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                            <li class="nxl-item"><a class="nxl-link"
                                    href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                            <li class="nxl-item"><a class="nxl-link"
                                    href="{{ route('admin.completed-loans') }}">Completed Loans</a></li>
                            <li class="nxl-item"><a class="nxl-link"
                                    href="{{ route('admin.defaulted-loans') }}">Defaulted</a></li>
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
                    <div class="hamburger hamburger--arrowturn">
                        <div class="hamburger-box">
                            <div class="hamburger-inner"></div>
                        </div>
                    </div>
                </a>
                <div class="nxl-navigation-toggle">
                    <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                    <a href="javascript:void(0);" id="menu-expend-button" style="display: none"><i
                            class="feather-arrow-right"></i></a>
                </div>
            </div>
            <div class="header-right ms-auto">
                <div class="d-flex align-items-center gap-2">
                    <div class="nxl-h-item dark-light-theme">
                        <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button"><i
                                class="feather-moon"></i></a>
                        <a href="javascript:void(0);" class="nxl-head-link me-0 light-button"
                            style="display: none"><i class="feather-sun"></i></a>
                    </div>

                    {{-- ============ NOTIFICATIONS ============ --}}
                    <details class="nxl-header-notif" name="header-dropdown-group">
                        <summary class="nxl-notif-trigger">
                            <i class="feather-bell"></i>
                            @if ($unreadNotifCount > 0)
                                <span
                                    class="nxl-notif-badge">{{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}</span>
                            @endif
                        </summary>
                        <div class="nxl-notif-dropdown-menu">
                            <div class="nxl-notif-dropdown-head">
                                <span>Notifications</span>
                                @if ($unreadNotifCount > 0)
                                    <button type="button" class="nxl-notif-mark-all" id="nxlMarkAllReadBtn"
                                        data-url="{{ route('admin.notifications-mark-all-read') }}">
                                        Mark all as read
                                    </button>
                                @endif
                            </div>
                            <div class="nxl-notif-list">
                                @forelse($notifications->take(8) as $n)
                                    <a href="{{ route('admin.notification-open', $n['key']) }}?redirect={{ urlencode($n['url']) }}"
                                        class="nxl-notif-item {{ $n['read'] ? '' : 'is-unread' }}">
                                        <span
                                            class="nxl-notif-icon bg-soft-{{ $n['color'] }} text-{{ $n['color'] }}">
                                            <i class="{{ $n['icon'] }}"></i>
                                        </span>
                                        <span class="nxl-notif-body">
                                            <span class="nxl-notif-title">{{ $n['title'] }}</span>
                                            <span class="nxl-notif-sub">{{ $n['subtitle'] }}</span>
                                            <span
                                                class="nxl-notif-time">{{ \Carbon\Carbon::parse($n['ts'])->diffForHumans() }}</span>
                                        </span>
                                        @if (!$n['read'])
                                            <span class="nxl-notif-dot"></span>
                                        @endif
                                    </a>
                                @empty
                                    <div class="nxl-notif-empty">You're all caught up.</div>
                                @endforelse
                            </div>
                            <div class="nxl-notif-dropdown-foot">
                                <a href="{{ route('admin.notifications') }}">View all notifications</a>
                            </div>
                        </div>
                    </details>

                    {{-- ============ HEADER — ADMIN PROFILE ============
                         My Profile / Account Settings now open modals
                         instead of navigating away. --}}
                    <details class="nxl-header-admin" name="header-dropdown-group">
                        <summary class="nxl-admin-trigger-header">
                            <span class="nxl-admin-avatar-header js-admin-avatar"
                                data-initials="{{ $adminInitials }}">
                                @if ($adminProfilePicture ?? false)
                                    <img src="{{ $adminProfilePicture }}" alt="{{ $adminName }}"
                                        onerror="this.parentElement.textContent=this.parentElement.dataset.initials;">
                                @else
                                    {{ $adminInitials }}
                                @endif
                            </span>
                            <span class="nxl-admin-name-header">{{ $adminName }}</span>
                            <i class="feather-chevron-down nxl-admin-caret-header"></i>
                        </summary>
                        <div class="nxl-admin-dropdown-menu">
                            <div class="nxl-admin-dropdown-head">
                                <span class="nxl-admin-dropdown-avatar js-admin-avatar"
                                    data-initials="{{ $adminInitials }}">
                                    @if ($adminProfilePicture ?? false)
                                        <img src="{{ $adminProfilePicture }}" alt="{{ $adminName }}"
                                            onerror="this.parentElement.textContent=this.parentElement.dataset.initials;">
                                    @else
                                        {{ $adminInitials }}
                                    @endif
                                </span>
                                <div>
                                    <div class="nxl-admin-name">{{ $adminName }}</div>
                                    <div class="nxl-admin-email">{{ $adminEmail }}</div>
                                </div>
                            </div>
                            <div class="nxl-admin-dropdown-divider"></div>
                            <a class="nxl-admin-dropdown-item" href="javascript:void(0);" data-bs-toggle="modal"
                                data-bs-target="#nxlMyProfileModal"
                                onclick="document.querySelector('.nxl-header-admin').removeAttribute('open')">
                                <span class="nxl-admin-dropdown-item-icon"><i class="feather-user"></i></span>
                                My Profile
                            </a>
                            <a class="nxl-admin-dropdown-item" href="javascript:void(0);" data-bs-toggle="modal"
                                data-bs-target="#nxlAccountSettingsModal"
                                onclick="document.querySelector('.nxl-header-admin').removeAttribute('open')">
                                <span class="nxl-admin-dropdown-item-icon"><i class="feather-settings"></i></span>
                                Account Settings
                            </a>
                            <div class="nxl-admin-dropdown-divider"></div>
                            <a class="nxl-admin-dropdown-item text-danger" href="javascript:void(0);"
                                onclick="confirmLogout()">
                                <span class="nxl-admin-dropdown-item-icon"><i class="feather-log-out"></i></span>
                                Logout
                            </a>
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
            <p class="text-center"><span>&copy; {{ date('Y') }} Ran Serenity Lending System</span></p>
        </footer>
    </main>

    {{-- ============================================================
     MY PROFILE MODAL — view-only account info
============================================================ --}}
    <div class="modal fade nxl-account-modal" id="nxlMyProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header nxl-profile-modal-header">
                    <h6 class="modal-title">My Profile</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="nxl-profile-modal-top">
                        <div class="nxl-profile-modal-avatar-wrap">
                            <div class="nxl-profile-modal-avatar js-admin-avatar" id="nxlProfileAvatarPreview"
                                data-initials="{{ $adminInitials }}">
                                @if ($adminProfilePicture ?? false)
                                    <img src="{{ $adminProfilePicture }}" alt="{{ $adminName }}"
                                        onerror="this.closest('.js-admin-avatar').textContent = this.closest('.js-admin-avatar').dataset.initials;">
                                @else
                                    {{ $adminInitials }}
                                @endif
                            </div>
                            <button type="button" class="nxl-avatar-upload-btn" id="nxlAvatarUploadTrigger"
                                title="Change photo"
                                data-upload-url="{{ route('admin.account-settings.profile-picture') }}">
                                <i class="feather-camera"></i>
                            </button>
                            <input type="file" id="nxlAvatarFileInput" accept="image/png,image/jpeg,image/webp"
                                hidden>
                        </div>

                        <div class="nxl-profile-modal-info">
                            <div class="nxl-profile-modal-name">{{ $adminName }}</div>
                            <div class="nxl-profile-modal-meta">
                                <span>{{ $adminRole }}</span>
                                <span class="nxl-meta-dot-sep">&bull;</span>
                                <span class="nxl-status-inline {{ $adminIsActive ? 'is-active' : 'is-inactive' }}">
                                    <span class="nxl-status-dot"></span>
                                    {{ $adminIsActive ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <div class="nxl-profile-modal-actions">
                                <button type="button" class="nxl-avatar-upload-text-btn"
                                    id="nxlAvatarUploadTriggerText">
                                    <i class="feather-user"></i> Upload Profile Picture
                                </button>
                                <button type="button" class="nxl-avatar-remove-btn" id="nxlAvatarRemoveBtn"
                                    data-remove-url="{{ route('admin.account-settings.profile-picture.remove') }}"
                                    style="{{ $adminProfilePicture ?? false ? '' : 'display:none;' }}">
                                    Remove photo
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="nxl-profile-modal-divider"></div>

                    <div class="nxl-account-field">
                        <label>Full Name</label>
                        <span>{{ $adminName }}</span>
                    </div>
                    <div class="nxl-account-field">
                        <label>Email</label>
                        <span>{{ $adminEmail }}</span>
                    </div>

                    <div class="nxl-profile-modal-divider"></div>

                    <div class="nxl-account-field-grid">
                        <div class="nxl-account-field">
                            <label>Role</label>
                            <span>{{ $adminRole }}</span>
                        </div>
                        <div class="nxl-account-field">
                            <label>Account Status</label>
                            <span class="nxl-status-pill {{ $adminIsActive ? 'is-active' : 'is-inactive' }}">
                                <span class="nxl-status-dot"></span>
                                {{ $adminIsActive ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>

                    <div class="nxl-account-field">
                        <label>Last Login</label>
                        <span>{{ $lastLoginAt ? \Carbon\Carbon::parse($lastLoginAt)->format('F d, Y \a\t g:i A') : 'Not available' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
     ACCOUNT SETTINGS MODAL — Account (email) + Security (password)
============================================================ --}}
    <div class="modal fade nxl-account-modal" id="nxlAccountSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header nxl-profile-modal-header">
                    <h6 class="modal-title">Account Settings</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="nxl-account-section-label">Account</p>
                    <div class="nxl-settings-card">
                        <div class="nxl-settings-card-left">
                            <div>
                                <span class="nxl-settings-card-label">Email</span>
                                <div class="nxl-settings-card-value">{{ $adminEmail }}</div>
                            </div>
                        </div>
                        <button type="button" class="nxl-settings-card-btn" disabled
                            title="Email changes aren't supported for the 3 predefined admin accounts.">
                            Edit
                        </button>
                    </div>

                    <p class="nxl-account-section-label mt-3">Security</p>
                    <div class="nxl-settings-card">
                        <div class="nxl-settings-card-left">
                            <div>
                                <span class="nxl-settings-card-label">Password</span>
                                <div class="nxl-settings-card-value">••••••••••••••</div>
                                <div class="nxl-settings-card-sub" id="nxlPasswordLastChanged">
                                    Last changed: {{ $passwordChangedAt ?? 'Not available' }}
                                </div>
                            </div>
                        </div>
                        <button type="button" class="nxl-settings-card-btn" data-bs-toggle="modal"
                            data-bs-target="#nxlChangePasswordModal" data-bs-dismiss="modal">
                            Change
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         CHANGE PASSWORD MODAL — submits via AJAX to
         admin.account-settings.password, no page reload
    ============================================================ --}}
    <div class="modal fade nxl-account-modal" id="nxlChangePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="nxlChangePasswordForm" action="{{ route('admin.account-settings.password') }}"
                    method="POST">
                    @csrf
                    <div class="modal-body">
                        <div id="nxlPasswordFormAlert" class="nxl-password-alert" style="display:none;"></div>

                        <div class="mb-3">
                            <label class="nxl-pw-label">Current Password<span class="nxl-pw-req">*</span></label>
                            <div class="nxl-password-field-wrap">
                                <input type="password" name="current_password" class="form-control" required>
                                <button type="button" class="nxl-password-toggle-btn" tabindex="-1"
                                    aria-label="Show password">
                                    <i class="feather-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="nxl-pw-label">New Password<span class="nxl-pw-req">*</span></label>
                            <div class="nxl-password-field-wrap">
                                <input type="password" name="new_password" id="nxlNewPassword" class="form-control"
                                    required minlength="8">
                                <button type="button" class="nxl-password-toggle-btn" tabindex="-1"
                                    aria-label="Show password">
                                    <i class="feather-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="nxl-pw-label-row">
                                <label class="nxl-pw-label mb-0">Confirm New Password<span
                                        class="nxl-pw-req">*</span></label>
                                <button type="button" class="nxl-pw-clear-btn"
                                    id="nxlPasswordClearBtn">Clear</button>
                            </div>
                            <div class="nxl-password-field-wrap">
                                <input type="password" name="new_password_confirmation" id="nxlConfirmPassword"
                                    class="form-control" required minlength="8">
                                <button type="button" class="nxl-password-toggle-btn" tabindex="-1"
                                    aria-label="Show password">
                                    <i class="feather-eye"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Live strength bar --}}
                        <div class="nxl-pw-strength-bar" id="nxlPwStrengthBar">
                            <span class="nxl-pw-strength-seg" data-seg="1"></span>
                            <span class="nxl-pw-strength-seg" data-seg="2"></span>
                            <span class="nxl-pw-strength-seg" data-seg="3"></span>
                        </div>

                        {{-- Requirement checklist --}}
                        <div class="nxl-pw-checklist">
                            <div class="nxl-pw-checklist-label" id="nxlPwStrengthLabel">Weak password. Must contain;
                            </div>
                            <div class="nxl-pw-check-item" data-rule="uppercase">
                                <span class="nxl-pw-check-icon"><i class="feather-x"></i></span>
                                At least 1 uppercase
                            </div>
                            <div class="nxl-pw-check-item" data-rule="number">
                                <span class="nxl-pw-check-icon"><i class="feather-x"></i></span>
                                At least 1 number
                            </div>
                            <div class="nxl-pw-check-item" data-rule="length">
                                <span class="nxl-pw-check-icon"><i class="feather-x"></i></span>
                                At least 8 characters
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer nxl-pw-modal-footer">
                        <button type="button" class="nxl-pw-btn-discard" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="nxl-pw-btn-apply" id="nxlPasswordSubmitBtn">Apply
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
         PASSWORD CHANGED — SUCCESS MODAL
         (sibling ng ibang modals — HINDI ito nested sa loob ng
         Change Password modal, para hindi masira ang Bootstrap
         modal stacking/backdrop behavior)
    ============================================================ --}}
    <div class="modal fade nxl-account-modal" id="nxlPasswordSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="nxl-pw-success-icon"><i class="feather-check-circle"></i></div>
                    <div class="nxl-pw-success-title">Password Changed Successfully</div>
                    <div class="nxl-pw-success-desc">Your password has been updated.</div>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="nxl-pw-btn-apply" style="flex: 0 0 auto; padding: 0 32px;"
                        data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
     CROP PHOTO MODAL — lumalabas pagkatapos pumili ng file,
     bago mag-upload. Gumagamit ng Cropper.js (CDN, walang build
     step). data-bs-backdrop="static" para hindi masarado ng
     hindi sinasadyang click sa labas.
============================================================ --}}
    <div class="modal fade nxl-account-modal" id="nxlCropPhotoModal" tabindex="-1" aria-hidden="true"
        data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header nxl-profile-modal-header">
                    <h6 class="modal-title">Crop Photo</h6>
                    <button type="button" class="btn-close" id="nxlCropCloseX" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="nxl-crop-wrap is-round-crop">
                        <img id="nxlCropImage" src="" alt="Crop preview">
                    </div>
                    <p class="nxl-crop-hint">Drag to reposition. Scroll or pinch to zoom.</p>
                </div>
                <div class="modal-footer nxl-pw-modal-footer">
                    <button type="button" class="nxl-pw-btn-discard" id="nxlCropCancelBtn">Cancel</button>
                    <button type="button" class="nxl-pw-btn-apply" id="nxlCropApplyBtn">
                        <span id="nxlCropApplyText">Apply</span>
                        <span id="nxlCropApplySpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>



    <div class="modal fade nxl-account-modal" id="nxlRemovePhotoConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered nxl-account-modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="nxl-remove-confirm-title">Remove profile picture?</div>
                    <div class="nxl-remove-confirm-desc">
                        This action will permanently delete the current photo from your account. You can upload a new
                        photo later if you wish.
                    </div>
                </div>
                <div class="modal-footer nxl-pw-modal-footer">
                    <button type="button" class="nxl-pw-btn-discard" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="nxl-pw-btn-apply nxl-btn-danger-fill"
                        id="nxlConfirmRemovePhotoBtn">
                        <span id="nxlConfirmRemovePhotoText">Remove</span>
                        <span id="nxlConfirmRemovePhotoSpinner"
                            class="spinner-border spinner-border-sm ms-2 d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @stack('modals')

    <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/session-sync.js') }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
    <script src="{{ asset('functions/admin-header.js') }}"></script>
    <script src="{{ asset('functions/admin-account-modals.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="{{ asset('functions/admin-toast.js') }}"></script>
    <script src="{{ asset('functions/admin-profile-picture.js') }}"></script>
    <script src="{{ asset('functions/admin-remove-blur.js') }}"></script>

    {{-- ============================================================
         DEEP-LINK MODAL OPENER
         Pinapayagan ang pagbukas ng "My Profile" o "Account Settings"
         modal direkta mula sa ibang page (hal. landing page dropdown)
         sa pamamagitan ng ?open=profile o ?open=settings query param.
         Ni-clean ang URL pagkatapos buksan para hindi na bumukas
         ulit ang modal kapag nag-refresh.
    ============================================================ --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            const openTarget = params.get('open');

            if (openTarget === 'profile') {
                const modalEl = document.getElementById('nxlMyProfileModal');
                if (modalEl) {
                    new bootstrap.Modal(modalEl).show();
                }
            } else if (openTarget === 'settings') {
                const modalEl = document.getElementById('nxlAccountSettingsModal');
                if (modalEl) {
                    new bootstrap.Modal(modalEl).show();
                }
            }

            if (openTarget) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        });
    </script>

    {{-- Page-specific scripts go here --}}
    @yield('page-scripts')
</body>

</html>