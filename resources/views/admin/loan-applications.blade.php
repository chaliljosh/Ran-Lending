<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Loan Applications - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-applications.css') }}">
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
                    <li class="nxl-item nxl-hasmenu nxl-trigger active">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-file-text"></i></span>
                            <span class="nxl-mtext">Loan Applications</span>
                            @if(($pendingLoanCount ?? 0) > 0)
                                <span class="badge bg-danger ms-auto">{{ $pendingLoanCount }}</span>
                            @endif
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? 'pending') === 'pending' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.loan-applications') }}">Pending Applications</a>
                            </li>
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? '') === 'approved' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'approved']) }}">Approved</a>
                            </li>
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? '') === 'rejected' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'rejected']) }}">Rejected</a>
                            </li>
                            <li class="nxl-item" data-current="{{ ($viewStatus ?? '') === 'on_hold' ? 'true' : 'false' }}">
                                <a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'on_hold']) }}">On Hold</a>
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
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                           <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
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
                    <div class="page-header-title">
                        <h5 class="m-b-10">
                            Loan Applications
                            @if(($viewStatus ?? 'pending') !== 'pending')
                                &mdash; {{ ucfirst(str_replace('_', ' ', $viewStatus)) }}
                            @endif
                        </h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Loan Applications</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">

                @if(($viewStatus ?? 'pending') === 'pending')
                {{-- ================= PENDING VIEW (Pending / Reviewed tabs) ================= --}}

                <div class="la-tabs">
                    <button class="tab-btn active" onclick="showLoanTab('pending', this)">
                        Pending
                        @if($pendingLoanCount > 0)
                            <span class="tab-count">{{ $pendingLoanCount }}</span>
                        @endif
                    </button>
                    <button class="tab-btn" onclick="showLoanTab('reviewed', this)">
                        Reviewed
                        <span class="tab-count is-muted">{{ count($reviewedApplications) }}</span>
                    </button>
                </div>

                <!-- PENDING TAB — same .reviewed-card structure as the Reviewed
                     tab: toolbar (search + rows-per-page) / table / pagination,
                     all inside one continuous card. Votes + Status are shown as
                     table columns instead of the old card header/mini-badges. -->
                <div id="loantab-pending">
                    @if(count($pendingApplications) === 0)
                    <div class="la-empty-large">
                        <span class="la-empty-icon-lg">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </span>
                        <p class="la-empty-title">All caught up!</p>
                        <p class="la-empty-desc">No pending loan applications to review.</p>
                    </div>
                    @else
                    <div class="reviewed-card">

                        {{-- Search + rows-per-page --}}
                        <div class="reviewed-toolbar">
                            <div class="search-bar-wrap reviewed-search-wrap">
                                <i class="feather-search"></i>
                                <input type="text" id="pendingSearchInput"
                                       placeholder="Search borrower name or email..."
                                       onkeyup="filterLoanPending()">
                            </div>
                            <div class="reviewed-rpp-wrap">
                                <label for="pendingRowsPerPage" class="reviewed-rpp-label">Rows per page</label>
                                <select id="pendingRowsPerPage" class="reviewed-rpp-select" onchange="pendingChangeRowsPerPage(this)">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="reviewed-table mb-0" id="pendingLoanTable">
                                <thead>
                                    <tr>
                                        <th>Borrower</th>
                                        <th class="text-end">Loan Amount</th>
                                        <th>Term</th>
                                        <th>Purpose</th>
                                        <th>Applied Date</th>
                                        <th>Votes</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="loanPendingList">
                                    @foreach($pendingApplications as $app)
                                    <tr data-search="{{ strtolower($app['last_name'] . ' ' . $app['first_name'] . ' ' . $app['email']) }}">
                                        <td>
                                            <div class="rt-name">{{ $app['last_name'] }}, {{ $app['first_name'] }}</div>
                                            <div class="rt-email">{{ $app['email'] }}</div>
                                        </td>
                                        <td class="text-end rt-date">&#8369;{{ number_format($app['loan_amount'], 2) }}</td>
                                        <td class="rt-date">{{ $app['loan_term'] }} months</td>
                                        <td class="rt-remarks" title="{{ ucfirst(str_replace('_', ' ', $app['loan_purpose'])) }}">
                                            {{ ucfirst(str_replace('_', ' ', $app['loan_purpose'])) }}
                                        </td>
                                        <td class="rt-date">{{ date('M d, Y', strtotime($app['applied_at'])) }}</td>
                                        <td>
                                            @if($app['approved_count'] > 0 || $app['rejected_count'] > 0)
                                            <div class="progress-mini-table">
                                                <span class="pm-item is-approved">{{ $app['approved_count'] }}/3</span>
                                                <span class="pm-item is-rejected">{{ $app['rejected_count'] }}/3</span>
                                            </div>
                                            @else
                                            <span class="rt-date">&mdash;</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($app['status'] === 'on_hold')
                                                <span class="status-subtle is-onhold"><span class="dot"></span>On Hold</span>
                                            @else
                                                <span class="status-subtle"><span class="dot"></span>Pending</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.loan-application-detail', $app['application_id']) }}" class="btn-light">
                                                <i class="feather-eye me-1"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div id="pendingEmptyState" class="reviewed-empty" style="display:none;">
                                No applications match your search.
                            </div>
                        </div>

                        {{-- Pagination bar --}}
                        <div class="reviewed-pagination" id="pendingPagination">
                            <span class="reviewed-pagination-info" id="pendingPaginationInfo">
                                Showing 1&ndash;{{ min(10, count($pendingApplications)) }} of {{ count($pendingApplications) }}
                            </span>
                            <div class="reviewed-pagination-controls">
                                <button type="button" class="btn-page" id="pendingPrevPage" onclick="pendingGoToPage(-1)">
                                    <i class="feather-chevron-left"></i> Prev
                                </button>
                                <span class="reviewed-page-indicator" id="pendingPageIndicator">Page 1 of 1</span>
                                <button type="button" class="btn-page" id="pendingNextPage" onclick="pendingGoToPage(1)">
                                    Next <i class="feather-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- REVIEWED TAB — single continuous card: toolbar / table / pagination,
                     each divided by a border instead of separate margin-gapped boxes.
                     Matches the Borrower Verification Reviewed table structure. -->
                <div id="loantab-reviewed" style="display:none;">
                    @if(count($reviewedApplications) === 0)
                    <div class="la-empty-large">
                        <span class="la-empty-icon-lg la-empty-icon-lg-muted">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        </span>
                        <p class="la-empty-desc">No reviewed applications yet.</p>
                    </div>
                    @else
                    <div class="reviewed-card">

                        {{-- Search + decision filter + rows-per-page --}}
                        <div class="reviewed-toolbar">
                            <div class="search-bar-wrap reviewed-search-wrap">
                                <i class="feather-search"></i>
                                <input type="text" id="loanReviewedSearchInput"
                                       placeholder="Search borrower name or email..."
                                       onkeyup="filterLoanReviewed()">
                            </div>
                            <select id="loanReviewedDecisionFilter" class="reviewed-decision-filter" onchange="filterLoanReviewed()">
                                <option value="">All Decisions</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <div class="reviewed-rpp-wrap">
                                <label for="loanReviewedRowsPerPage" class="reviewed-rpp-label">Rows per page</label>
                                <select id="loanReviewedRowsPerPage" class="reviewed-rpp-select" onchange="loanReviewedChangeRowsPerPage(this)">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="reviewed-table mb-0" id="loanReviewedTable">
                                <thead>
                                    <tr>
                                        <th>Borrower</th>
                                        <th class="text-end">Loan Amount</th>
                                        <th>Purpose</th>
                                        <th>Term</th>
                                        <th>Applied Date</th>
                                        <th>Decision</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="loanReviewedList">
                                  @foreach($reviewedApplications as $r)
                                  @php
                                      $decisionLower = strtolower($r->decision ?? '');
                                  @endphp
                                  <tr data-search="{{ strtolower($r->last_name . ' ' . $r->first_name . ' ' . $r->email) }}" data-decision="{{ $decisionLower }}">
                                      <td>
                                          <div class="rt-name">{{ $r->last_name }}, {{ $r->first_name }}</div>
                                          <div class="rt-email">{{ $r->email }}</div>
                                      </td>
                                      <td class="text-end rt-date">&#8369;{{ number_format($r->loan_amount, 0) }}</td>
                                      <td class="rt-remarks" title="{{ ucfirst(str_replace('_', ' ', $r->loan_purpose ?? '—')) }}">
                                          {{ ucfirst(str_replace('_', ' ', $r->loan_purpose ?? '—')) }}
                                      </td>
                                      <td class="rt-date">{{ $r->loan_term ?? '—' }} months</td>
                                      <td class="rt-date">{{ $r->applied_at ? \Carbon\Carbon::parse($r->applied_at)->format('M d, Y') : '—' }}</td>
                                      <td>
                                          <span class="badge {{ $decisionLower === 'approved' ? 'badge-approved' : 'badge-rejected' }} px-3 py-2">
                                              {{ ucfirst($r->decision) }}
                                          </span>
                                      </td>
                                      <td class="text-end">
                                          <a href="{{ route('admin.loan-application-detail', $r->application_id) }}" class="btn-light">
                                              <i class="feather-eye me-1"></i> View Details
                                          </a>
                                      </td>
                                  </tr>
                                  @endforeach
                                </tbody>
                            </table>
                            <div id="loanReviewedEmptyState" class="reviewed-empty" style="display:none;">
                                No reviewed applications match your search.
                            </div>
                        </div>

                        {{-- Pagination bar --}}
                        <div class="reviewed-pagination" id="loanReviewedPagination">
                            <span class="reviewed-pagination-info" id="loanReviewedPaginationInfo">
                                Showing 1&ndash;{{ min(10, count($reviewedApplications)) }} of {{ count($reviewedApplications) }}
                            </span>
                            <div class="reviewed-pagination-controls">
                                <button type="button" class="btn-page" id="loanReviewedPrevPage" onclick="loanReviewedGoToPage(-1)">
                                    <i class="feather-chevron-left"></i> Prev
                                </button>
                                <span class="reviewed-page-indicator" id="loanReviewedPageIndicator">Page 1 of 1</span>
                                <button type="button" class="btn-page" id="loanReviewedNextPage" onclick="loanReviewedGoToPage(1)">
                                    Next <i class="feather-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                @else
                {{-- ================= FILTERED VIEW: Approved / Rejected / On Hold ================= --}}
                {{-- Same structure as the Reviewed tab: toolbar (search + rows-per-page)
                     / table / pagination, all inside one .reviewed-card. --}}

                    @if(count($filteredApplications) === 0)
                        @php
                            // Empty state now matches the Pending/Reviewed tabs'
                            // icon + title + description pattern instead of the
                            // old plain "No applications yet" text-only box.
                            $emptyIconMuted = true;
                            if ($viewStatus === 'approved') {
                                $emptyTitle = 'No approved applications yet';
                                $emptyDesc = 'Applications approved by all administrators will appear here.';
                                $emptyIconMuted = false;
                            } elseif ($viewStatus === 'rejected') {
                                $emptyTitle = 'No rejected applications';
                                $emptyDesc = 'Applications rejected by all administrators will appear here.';
                            } else {
                                $emptyTitle = 'No applications on hold';
                                $emptyDesc = 'Applications with mixed admin decisions will appear here.';
                            }
                        @endphp
                        <div class="la-empty-large">
                            <span class="la-empty-icon-lg {{ $emptyIconMuted ? 'la-empty-icon-lg-muted' : '' }}">
                                @if($viewStatus === 'approved')
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                @elseif($viewStatus === 'rejected')
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                @else
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                @endif
                            </span>
                            <p class="la-empty-title">{{ $emptyTitle }}</p>
                            <p class="la-empty-desc">{{ $emptyDesc }}</p>
                        </div>
                    @else
                    <div class="reviewed-card">

                        <div class="reviewed-toolbar">
                            <div class="search-bar-wrap reviewed-search-wrap">
                                <i class="feather-search"></i>
                                <input type="text" id="filteredSearchInput"
                                       placeholder="Search by name or email..."
                                       onkeyup="filterLoanFiltered()">
                            </div>
                            <div class="reviewed-rpp-wrap">
                                <label for="filteredRowsPerPage" class="reviewed-rpp-label">Rows per page</label>
                                <select id="filteredRowsPerPage" class="reviewed-rpp-select" onchange="filteredChangeRowsPerPage(this)">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="reviewed-table mb-0" id="filteredLoanTable">
                                <thead>
                                    <tr>
                                        <th>Borrower</th>
                                        <th>Application ID</th>
                                        <th class="text-end">Loan Amount</th>
                                        <th>Term</th>
                                        <th>Purpose</th>
                                        <th>Applied</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="filteredLoanList">
                                    @foreach($filteredApplications as $app)
                                    <tr data-search="{{ strtolower($app->last_name . ' ' . $app->first_name . ' ' . $app->email) }}">
                                        <td>
                                            <div class="rt-name">{{ $app->last_name }}, {{ $app->first_name }}</div>
                                            <div class="rt-email">{{ $app->email }}</div>
                                        </td>
                                        <td class="rt-date">#{{ $app->application_id }}</td>
                                        <td class="text-end rt-date">&#8369;{{ number_format($app->loan_amount, 0) }}</td>
                                        <td class="rt-date">{{ $app->loan_term }} months</td>
                                        <td class="rt-remarks" title="{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}">
                                            {{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}
                                        </td>
                                        <td class="rt-date">{{ date('M d, Y', strtotime($app->applied_at)) }}</td>
                                        <td>
                                            @if($viewStatus === 'approved')
                                                <span class="badge badge-approved px-3 py-2">Approved</span>
                                            @elseif($viewStatus === 'rejected')
                                                <span class="badge badge-rejected px-3 py-2">Rejected</span>
                                            @else
                                                <span class="badge badge-onhold px-3 py-2">On Hold</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.loan-application-detail', $app->application_id) }}" class="btn-light">
                                                <i class="feather-eye me-1"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div id="filteredEmptyState" class="reviewed-empty" style="display:none;">
                                No applications match your search.
                            </div>
                        </div>

                        {{-- Pagination bar --}}
                        <div class="reviewed-pagination" id="filteredPagination">
                            <span class="reviewed-pagination-info" id="filteredPaginationInfo">
                                Showing 1&ndash;{{ min(10, count($filteredApplications)) }} of {{ count($filteredApplications) }}
                            </span>
                            <div class="reviewed-pagination-controls">
                                <button type="button" class="btn-page" id="filteredPrevPage" onclick="filteredGoToPage(-1)">
                                    <i class="feather-chevron-left"></i> Prev
                                </button>
                                <span class="reviewed-page-indicator" id="filteredPageIndicator">Page 1 of 1</span>
                                <button type="button" class="btn-page" id="filteredNextPage" onclick="filteredGoToPage(1)">
                                    Next <i class="feather-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                @endif

            </div>
        </div>
        <footer class="footer">
            <p class="text-center">&copy; {{ date('Y') }} Ran Serenity Lending System</p>
        </footer>
    </main>

    <!-- Result modals — one of these shows after a decision is recorded from
         the Loan Application Detail page (which redirects back here). -->

    <!-- Generic "decision recorded" -->
    <div class="modal fade modal-modern" id="decisionSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success">
                        <i class="feather-check-circle"></i>
                    </div>
                    <p class="modal-title-text">Decision submitted</p>
                    <p class="modal-message-text">
                        Your decision has been recorded successfully. The application will remain under review until all three administrators have submitted their decisions.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mixed decisions -->
    <div class="modal fade modal-modern" id="onHoldModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-warning">
                        <i class="feather-alert-triangle"></i>
                    </div>
                    <p class="modal-title-text">Application on hold</p>
                    <p class="modal-message-text">
                        The administrators have reached different decisions. The application has been placed on hold for further discussion.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-warning" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fully approved by all 3 -->
    <div class="modal fade modal-modern" id="finalApprovalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success">
                        <i class="feather-check-circle"></i>
                    </div>
                    <p class="modal-title-text">Application approved</p>
                    <p class="modal-message-text">
                        The loan application has been approved by all administrators.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fully rejected by all 3 -->
    <div class="modal fade modal-modern" id="finalRejectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-danger">
                        <i class="feather-x-circle"></i>
                    </div>
                    <p class="modal-title-text">Application rejected</p>
                    <p class="modal-message-text">
                        The loan application has been rejected by all administrators.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-warning" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
    <script src="{{ asset('functions/logout-modal.js') }}"></script>
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const status = @json(session('decisionStatus'));
            let modalId = 'decisionSuccessModal';
            if (status === 'approved') modalId = 'finalApprovalModal';
            else if (status === 'rejected') modalId = 'finalRejectionModal';
            else if (status === 'on_hold') modalId = 'onHoldModal';

            const modalEl = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
            modal.show();
        });
    </script>
    @endif
    <script src="{{ asset('functions/loan-applications.js') }}"></script>
</body>
</html>