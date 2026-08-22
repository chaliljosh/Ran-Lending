<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Active Borrowers - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/admin/css/active-borrowers.css') }}">
    
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
                    <li class="nxl-item nxl-hasmenu nxl-trigger active">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Borrowers</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                            <li class="nxl-item" data-current="true"><a class="nxl-link" href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
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
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.completed-loans') }}">Completed Loans</a></li>
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
                    <div class="page-header-title"><h5 class="m-b-10">Active Borrowers</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.borrowers') }}">Borrowers</a></li>
                        <li class="breadcrumb-item">Active Borrowers</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">

                <!-- Stats -->
                <div class="stats-grid-v2">
                    <div class="rs-card stat-card-v2">
                        <div class="stat-icon-v2 total"><i class="feather-users"></i></div>
                        <div>
                            <div class="stat-label-v2">Active Borrowers</div>
                            <div class="stat-value-v2">{{ $stats['total'] }}</div>
                        </div>
                    </div>
                    <div class="rs-card stat-card-v2">
                        <div class="stat-icon-v2 amount"><i class="feather-dollar-sign"></i></div>
                        <div>
                            <div class="stat-label-v2">Total Active Amount</div>
                            <div class="stat-value-v2">₱{{ number_format($stats['total_amount'], 0) }}</div>
                        </div>
                    </div>
                    <div class="rs-card stat-card-v2">
                        <div class="stat-icon-v2 avg"><i class="feather-trending-up"></i></div>
                        <div>
                            <div class="stat-label-v2">Average Loan Amount</div>
                            <div class="stat-value-v2">₱{{ number_format($stats['avg_amount'], 0) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Filter toolbar -->
                <div class="filter-bar-v2">
                    <div class="search-wrap-v2">
                        <i class="feather-search"></i>
                        <input type="text" id="filterSearchInput"
                               placeholder="Search borrowers by name, email, or Borrower ID..."
                               onkeyup="filterActiveBorrowers()">
                    </div>
                    <select class="sort-select-v2" id="sortFilter" onchange="sortActiveBorrowers()">
                        <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    </select>
                </div>

                @if(count($activeBorrowers) === 0)
                <div class="rs-card">
                    <div class="empty-state-v2">
                        <i class="feather-users"></i>
                        <h6>No active borrowers yet</h6>
                        <p>There are currently no borrowers with approved or active loans.</p>
                    </div>
                </div>
                @else
                <div class="rs-card rs-table-card">
                    <div id="filteredNoResults" class="empty-state-v2" style="display:none;">
                        <i class="feather-search"></i>
                        <h6>No matches found</h6>
                        <p>Try a different search term.</p>
                    </div>
                    <div class="table-responsive" id="borrowerTableWrap">
                        <table class="table rs-table mb-0">
                            <thead>
                                <tr>
                                    <th>Borrower</th>
                                    <th>Loan Amount</th>
                                    <th>Term</th>
                                    <th>Purpose</th>
                                    <th>Applied</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeBorrowers as $b)
                                <tr data-search="{{ strtolower($b['name'] . ' ' . $b['email'] . ' ' . $b['borrower_id']) }}"
                                    data-applied="{{ \Carbon\Carbon::parse($b['applied_at'])->timestamp }}"
                                    data-match="true">
                                    <td>
                                        <div class="borrower-cell-v2">
                                            <div class="borrower-avatar-v2">{{ $b['initials'] }}</div>
                                            <div>
                                                <div class="borrower-name-v2">{{ $b['name'] }}</div>
                                                <div class="borrower-id-v2">{{ $b['borrower_id'] }}</div>
                                                <div class="borrower-email-v2">{{ $b['email'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="loan-amount-v2">₱{{ number_format($b['loan_amount'], 2) }}</td>
                                    <td class="loan-meta-v2">{{ $b['loan_term'] }} {{ Str::plural('month', $b['loan_term']) }}</td>
                                    <td>
                                        @if($b['loan_purpose'])
                                            <span class="purpose-pill">{{ str_replace('_', ' ', $b['loan_purpose']) }}</span>
                                        @else
                                            <span class="loan-meta-v2">—</span>
                                        @endif
                                    </td>
                                    <td class="loan-meta-v2">{{ \Carbon\Carbon::parse($b['applied_at'])->format('M d, Y') }}</td>
                                    <td class="text-end">
                                        {{-- Points to the new, focused Active Borrower Detail page
                                             (profile + standing, Loan Summary, Payment Progress,
                                             Payment Schedule) instead of loan-application-detail,
                                             so that page's approve/reject review flow stays untouched. --}}
                                        <a href="{{ route('admin.active-borrower-detail', $b['application_id']) }}" class="btn-view-v2">
                                            <i class="feather-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="rs-pagination-bar" id="rsPaginationBar">
                        <div class="rs-pagination-info" id="rsPaginationInfo"></div>
                        <div class="rs-pagination-right">
                            <div class="rs-per-page">
                                <label for="rowsPerPageSelect">Rows per page</label>
                                <select id="rowsPerPageSelect" onchange="changeRowsPerPage()">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="all">All</option>
                                </select>
                            </div>
                            <div class="rs-pagination-controls" id="rsPaginationControls"></div>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>
        <footer class="footer">
            <p class="text-center">&copy; {{ date('Y') }} Ran Serenity Lending System</p>
        </footer>
    </main>

    <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
    <script src="{{ asset('functions/active-borrowers.js') }}"></script>
</body>
</html>