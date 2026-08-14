<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ongoing Loans - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loans.css') }}">
    
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
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Borrowers</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.penalty-tracking') }}">Penalty Tracking</a></li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu nxl-trigger active">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                            <span class="nxl-mtext">Active Loans</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item" data-current="true"><a class="nxl-link" href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
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
                    <div class="page-header-title"><h5 class="m-b-10">Ongoing Loans</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Active Loans</li>
                        <li class="breadcrumb-item">Ongoing Loans</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">

                <!-- Stats -->
                <div class="stats-grid-v2">
                    <div class="rs-card stat-card-v2">
                        <div class="stat-icon-v2 total"><i class="feather-repeat"></i></div>
                        <div>
                            <div class="stat-label-v2">Ongoing Loans</div>
                            <div class="stat-value-v2">{{ $stats['total'] }}</div>
                        </div>
                    </div>
                    <div class="rs-card stat-card-v2">
                        <div class="stat-icon-v2 amount"><i class="feather-dollar-sign"></i></div>
                        <div>
                            <div class="stat-label-v2">Total Loan Amount</div>
                            <div class="stat-value-v2">₱{{ number_format($stats['total_amount'], 0) }}</div>
                        </div>
                    </div>
                    <div class="rs-card stat-card-v2">
                        <div class="stat-icon-v2 avg"><i class="feather-check-circle"></i></div>
                        <div>
                            <div class="stat-label-v2">Total Collected</div>
                            <div class="stat-value-v2">₱{{ number_format($stats['total_collected'], 0) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Filter toolbar -->
                <div class="filter-bar-v2">
                    <div class="search-wrap-v2">
                        <i class="feather-search"></i>
                        <input type="text" id="filterSearchInput"
                               placeholder="Search borrowers by name, email, or Borrower ID..."
                               onkeyup="filterOngoingLoans()">
                    </div>
                    <select class="sort-select-v2" id="sortFilter" onchange="sortOngoingLoans()">
                        <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    </select>
                </div>

                @if(count($ongoingLoans) === 0)
                <div class="rs-card">
                    <div class="empty-state-v2">
                        <i class="feather-repeat"></i>
                        <h6>No ongoing loans yet</h6>
                        <p>There are currently no active loans with up-to-date payments.</p>
                    </div>
                </div>
                @else
                <div class="rs-card rs-table-card">
                    <div id="filteredNoResults" class="empty-state-v2" style="display:none;">
                        <i class="feather-search"></i>
                        <h6>No matches found</h6>
                        <p>Try a different search term.</p>
                    </div>
                    <div class="table-responsive" id="loanTableWrap">
                        <table class="table rs-table mb-0">
                            <thead>
                                <tr>
                                    <th>Borrower</th>
                                    <th>Loan Amount</th>
                                    <th>Progress</th>
                                    <th>Next Due</th>
                                    <th>Collected</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ongoingLoans as $loan)
                                <tr data-search="{{ strtolower($loan['name'] . ' ' . $loan['email'] . ' ' . $loan['borrower_id']) }}"
                                    data-applied="{{ \Carbon\Carbon::parse($loan['applied_at'])->timestamp }}">
                                    <td>
                                        <div class="borrower-cell-v2">
                                            <div class="borrower-avatar-v2">{{ $loan['initials'] }}</div>
                                            <div>
                                                <div class="borrower-name-v2">{{ $loan['name'] }}</div>
                                                <div class="borrower-id-v2">{{ $loan['borrower_id'] }}</div>
                                                <div class="borrower-email-v2">{{ $loan['email'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="loan-amount-v2">₱{{ number_format($loan['loan_amount'], 2) }}</td>
                                    <td>
                                        <div class="progress-cell-v2">
                                            <div class="progress-bar-track-v2">
                                                <div class="progress-bar-fill-v2" style="width: {{ $loan['total_count'] ? round($loan['paid_count'] / $loan['total_count'] * 100) : 0 }}%"></div>
                                            </div>
                                            <span class="progress-label-v2">{{ $loan['paid_count'] }}/{{ $loan['total_count'] }} paid</span>
                                        </div>
                                    </td>
                                    <td class="loan-meta-v2">
                                        @if($loan['next_due_date'])
                                            {{ \Carbon\Carbon::parse($loan['next_due_date'])->format('M d, Y') }}
                                            <div class="next-due-amount-v2">₱{{ number_format($loan['next_due_amount'], 2) }}</div>
                                        @else
                                            <span class="purpose-pill">Fully Paid</span>
                                        @endif
                                    </td>
                                    <td class="loan-amount-v2">₱{{ number_format($loan['amount_collected'], 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.ongoing-loan-detail', $loan['application_id']) }}?from=ongoing-loans" class="btn-view-v2">
                                            <i class="feather-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
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
    <script>
        function filterOngoingLoans() {
            const query = document.getElementById('filterSearchInput').value.trim().toLowerCase();
            const rows = document.querySelectorAll('tbody tr[data-search]');
            const noResults = document.getElementById('filteredNoResults');
            const tableWrap = document.getElementById('loanTableWrap');
            let visibleCount = 0;

            rows.forEach(function (row) {
                const visible = row.dataset.search.includes(query);
                row.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            if (noResults && tableWrap) {
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
                tableWrap.style.display = visibleCount === 0 ? 'none' : '';
            }
        }

        function sortOngoingLoans() {
            const order = document.getElementById('sortFilter').value;
            const tbody = document.querySelector('#loanTableWrap table tbody');
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr[data-search]'));
            rows.sort(function (a, b) {
                const aApplied = parseInt(a.dataset.applied, 10) || 0;
                const bApplied = parseInt(b.dataset.applied, 10) || 0;
                return order === 'oldest' ? aApplied - bApplied : bApplied - aApplied;
            });

            rows.forEach(function (row) {
                tbody.appendChild(row);
            });
        }
    </script>
</body>
</html>