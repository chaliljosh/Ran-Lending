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
    <link href="{{ asset('assets/landingpage/img/ranlogo1.jpg') }}" rel="icon">
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
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/sidebar-brand.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-sidebar.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-toast.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-darkmode-global.css') }}" />

    @yield('page-styles')
</head>

<body>

    @php
        $currentRouteName = request()->route()?->getName();

        $laRoutes = ['admin.loan-applications', 'admin.loan-application-detail'];

        $isLoanApplicationsSection = in_array($currentRouteName, $laRoutes, true);

        $rawLoanStatus = $isLoanApplicationsSection ? $viewStatus ?? 'pending' : null;
        $loanStatusTab = in_array($rawLoanStatus, ['approved', 'active'], true) ? 'approved' : $rawLoanStatus;

        // "Needs Attention" = Pending list na naka-filter sa 3+ days (?filter=attention).
        // Sa list page lang ito, hindi sa detail page.
        $isAttentionTab = $currentRouteName === 'admin.loan-applications'
            && $loanStatusTab === 'pending'
            && request()->query('filter') === 'attention';
    @endphp

    <nav class="nxl-navigation">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="{{ route('admin.dashboard') }}" class="b-brand">
                    <img src="{{ asset('assets/landingpage/img/ranlogo.png') }}" alt="Ran Serenity" class="b-brand-icon">
                    <h3 class="fst-italic b-brand-text">RAN Serenity</h3>
                </a>
            </div>
            <div class="navbar-content">
                <ul class="nxl-navbar nxl-navbar-flat">

                    <li class="nxl-flat-label">Main</li>
                    <li class="nxl-item">
                        <a href="{{ route('admin.dashboard') }}"
                            class="nxl-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-airplay"></i></span>
                            <span class="nxl-mtext">Dashboard</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="{{ route('admin.monthly-reports') }}"
                            class="nxl-link {{ request()->routeIs('admin.monthly-reports*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-bar-chart-2"></i></span>
                            <span class="nxl-mtext">Monthly Reports</span>
                        </a>
                    </li>

                    <li class="nxl-flat-label">Loans</li>

                    <li class="nxl-item nxl-flat-parent {{ $isLoanApplicationsSection ? 'is-open' : '' }}">
                        <div class="nxl-flat-row nxl-flat-toggle-trigger" role="button" tabindex="0"
                            aria-expanded="{{ $isLoanApplicationsSection ? 'true' : 'false' }}"
                            aria-controls="nxlLoanSubmenu">
                            <a href="javascript:void(0);"
                                class="nxl-link nxl-flat-parent-link {{ $isLoanApplicationsSection ? 'active' : '' }}">
                                <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                <span class="nxl-mtext">Loan Applications</span>
                                @if (($pendingLoanCount ?? 0) > 0)
                                    <span class="nxl-badge-circle">{{ $pendingLoanCount }}</span>
                                @endif
                            </a>
                            <button type="button" class="nxl-flat-toggle" tabindex="-1">
                                <i class="feather-chevron-down"></i>
                            </button>
                        </div>

                        <ul class="nxl-flat-submenu" id="nxlLoanSubmenu">
                            <li class="nxl-flat-sub-item">
                                <a href="{{ route('admin.loan-applications') }}"
                                    class="nxl-sublink {{ $loanStatusTab === 'pending' && !$isAttentionTab ? 'active' : '' }}">
                                    <span class="nxl-sub-label">Pending Applications</span>
                                    @if (($pendingLoanCount ?? 0) > 0)
                                        <span class="nxl-badge-circle nxl-badge-sm">{{ $pendingLoanCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nxl-flat-sub-item">
                                <a href="{{ route('admin.loan-applications', ['filter' => 'attention']) }}"
                                    class="nxl-sublink {{ $isAttentionTab ? 'active' : '' }}">
                                    <span class="nxl-sub-label">Needs Attention</span>
                                    @if (($needsAttentionCount ?? 0) > 0)
                                        <span class="nxl-badge-circle nxl-badge-sm">{{ $needsAttentionCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nxl-flat-sub-item">
                                <a href="{{ route('admin.loan-applications', ['status' => 'approved']) }}"
                                    class="nxl-sublink {{ $loanStatusTab === 'approved' ? 'active' : '' }}">
                                    <span class="nxl-sub-label">Approved</span>
                                </a>
                            </li>
                            <li class="nxl-flat-sub-item">
                                <a href="{{ route('admin.loan-applications', ['status' => 'rejected']) }}"
                                    class="nxl-sublink {{ $loanStatusTab === 'rejected' ? 'active' : '' }}">
                                    <span class="nxl-sub-label">Rejected</span>
                                </a>
                            </li>
                            <li class="nxl-flat-sub-item">
                                <a href="{{ route('admin.loan-applications', ['status' => 'on_hold']) }}"
                                    class="nxl-sublink {{ $loanStatusTab === 'on_hold' ? 'active' : '' }}">
                                    <span class="nxl-sub-label">On Hold</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nxl-item">
                        <a href="{{ route('admin.ongoing-loans') }}"
                            class="nxl-link {{ request()->routeIs(['admin.ongoing-loans', 'admin.ongoing-loan-detail']) ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                            <span class="nxl-mtext">Ongoing Loans</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="{{ route('admin.overdue-payments') }}"
                            class="nxl-link {{ request()->routeIs(['admin.overdue-payments', 'admin.overdue-payment-detail']) ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-clock"></i></span>
                            <span class="nxl-mtext">Overdue Payments</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="{{ route('admin.completed-loans') }}"
                            class="nxl-link {{ request()->routeIs(['admin.completed-loans', 'admin.completed-loan-detail']) ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-check-circle"></i></span>
                            <span class="nxl-mtext">Completed Loans</span>
                        </a>
                    </li>
                    

                    <li class="nxl-flat-label">Borrowers</li>
                    <li class="nxl-item">
                        <a href="{{ route('admin.borrowers') }}"
                            class="nxl-link {{ request()->routeIs(['admin.borrowers', 'admin.verification-detail']) ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">All Borrowers</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="{{ route('admin.active-borrowers') }}"
                            class="nxl-link {{ request()->routeIs(['admin.active-borrowers', 'admin.active-borrower-detail']) ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">Active Borrowers</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="{{ route('admin.penalty-tracking') }}"
                            class="nxl-link {{ request()->routeIs('admin.penalty-tracking') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-alert-triangle"></i></span>
                            <span class="nxl-mtext">Penalty Tracking</span>
                        </a>
                    </li>

                    <li class="nxl-flat-label">Account</li>

                    <li class="nxl-item">
                        <a href="javascript:void(0);" onclick="confirmLogout()" class="nxl-link nxl-link-danger">
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
                                <span class="nxl-notif-dropdown-title">Notifications</span>
                                @if ($unreadNotifCount > 0)
                                    <button type="button" class="nxl-notif-mark-all" id="nxlMarkAllReadBtn"
                                        data-url="{{ route('admin.notifications-mark-all-read') }}">
                                        Mark all as read
                                    </button>
                                @endif
                            </div>

                            <div class="nxl-notif-tabs" id="nxlNotifTabs">
                                <button type="button" class="nxl-notif-tab is-active" data-filter="all">All</button>
                                <button type="button" class="nxl-notif-tab" data-filter="unread">Unread</button>
                            </div>

                            <div class="nxl-notif-list" id="nxlNotifList">
                               @forelse($notifications->take(30) as $n)
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
                                                class="nxl-notif-time">{{ \Carbon\Carbon::parse($n['ts'])->format('F j, Y, g:i A') }}</span>
                                        </span>
                                        @if (!$n['read'])
                                            <span class="nxl-notif-dot"></span>
                                        @endif
                                    </a>
                                @empty
                                    <div class="nxl-notif-empty">You're all caught up.</div>
                                @endforelse
                                <div class="nxl-notif-empty" id="nxlNotifEmptyUnread" style="display:none;">No unread
                                    notifications.</div>
                            </div>

                            <div class="nxl-notif-dropdown-foot">
                                <a href="{{ route('admin.notifications') }}">See all notifications</a>
                            </div>
                        </div>
                    </details>

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

                        <div class="nxl-pw-strength-bar" id="nxlPwStrengthBar">
                            <span class="nxl-pw-strength-seg" data-seg="1"></span>
                            <span class="nxl-pw-strength-seg" data-seg="2"></span>
                            <span class="nxl-pw-strength-seg" data-seg="3"></span>
                        </div>

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
    <script src="{{ asset('functions/admin-sidebar.js') }}"></script>

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

    @yield('page-scripts')
</body>

</html>