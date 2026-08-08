<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>All Borrowers - Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/borrowers.css') }}">
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
                            <li class="nxl-item" data-current="true"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
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
                    <div class="page-header-title"><h5 class="m-b-10">All Borrowers</h5></div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">All Borrowers</li>
                    </ul>
                </div>
            </div>

            <div class="main-content">

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon total"><i class="feather-users"></i></div>
                        <div>
                            <div class="stat-label">Total Borrowers</div>
                            <div class="stat-value">{{ $stats['total'] }}</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon active"><i class="feather-check-circle"></i></div>
                        <div>
                            <div class="stat-label">Active Loans</div>
                            <div class="stat-value">{{ $stats['active_loan'] }}</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon no-loan"><i class="feather-inbox"></i></div>
                        <div>
                            <div class="stat-label">No Active Loan</div>
                            <div class="stat-value">{{ $stats['no_loan'] }}</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon suspended"><i class="feather-slash"></i></div>
                        <div>
                            <div class="stat-label">Suspended Accounts</div>
                            <div class="stat-value">{{ $stats['suspended'] }}</div>
                        </div>
                    </div>
                </div>

                <!-- Filter toolbar: single row — search grows on the left,
                     Status + Sort dropdowns pinned to the right. IDs/onchange
                     handlers unchanged so filterBorrowers() keeps working. -->
                <div class="filter-bar">
                    <div class="search-bar-wrap">
                        <i class="feather-search"></i>
                        <input type="text" class="form-control" id="filterSearchInput"
                               placeholder="Search borrowers by name, email, or Borrower ID..."
                               onkeyup="filterBorrowers()">
                    </div>
                    <div class="filter-bar-actions">
                        <select class="form-select" id="statusFilter" onchange="filterBorrowers()">
                            <option value="">All Loan Statuses</option>
                            <option value="active_loan">Active Loan</option>
                            <option value="no_loan">No Loan</option>
                            <option value="pending">Pending Application</option>
                            <option value="completed">Loan Completed</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        <select class="form-select" id="sortFilter" onchange="sortBorrowers()">
                            <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Sort: Newest First</option>
                            <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Sort: Oldest First</option>
                        </select>
                    </div>
                </div>

                @if(count($borrowers) === 0)
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="feather-users" style="font-size:48px;color:#8a9bb0;"></i>
                        <h5 class="mt-3 fw-bold">No borrowers yet</h5>
                    </div>
                </div>
                @else
                <div class="card">
                    <div class="card-body p-0">
                        <div id="filteredNoResults" class="text-center py-5" style="display:none;">
                            <i class="feather-search" style="font-size:40px;color:#8a9bb0;"></i>
                            <h6 class="mt-3 fw-bold">No matches found</h6>
                            <p class="text-muted mb-0">Try a different search term or filter.</p>
                        </div>
                        <div class="table-responsive" id="borrowerTableWrap">
                            <table class="table table-hover mb-0 borrower-table">
                                <thead>
                                    <tr>
                                        <th>Borrower</th>
                                        <th>Loan Status</th>
                                        <th>Total Loans</th>
                                        <th>Last Activity</th>
                                        <th>Joined</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($borrowers as $b)
                                    @php
                                        $statusMap = [
                                            'active_loan' => ['label' => 'Active Loan', 'class' => 'badge-approved'],
                                            'no_loan'     => ['label' => 'No Loan', 'class' => 'badge-noloan'],
                                            'pending'     => ['label' => 'Pending Application', 'class' => 'badge-pending'],
                                            'completed'   => ['label' => 'Loan Completed', 'class' => 'badge-completed'],
                                            'suspended'   => ['label' => 'Suspended', 'class' => 'badge-suspended'],
                                        ];
                                        $statusInfo = $statusMap[$b['loan_status']];
                                    @endphp
                                    <tr data-search="{{ strtolower($b['name'] . ' ' . $b['email'] . ' ' . $b['borrower_id']) }}"
                                        data-status="{{ $b['loan_status'] }}"
                                        data-joined="{{ \Carbon\Carbon::parse($b['joined_at'])->timestamp }}">
                                        <td>
                                            <div class="borrower-cell">
                                                <div class="borrower-avatar">{{ $b['initials'] }}</div>
                                                <div>
                                                    <div class="borrower-name">{{ $b['name'] }}</div>
                                                    <div class="borrower-id-text">{{ $b['borrower_id'] }}</div>
                                                    <div class="borrower-email">{{ $b['email'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusInfo['class'] }} px-3 py-2">{{ $statusInfo['label'] }}</span>
                                        </td>
                                        <td class="text-muted">{{ $b['total_loans'] }}</td>
                                        <td class="text-muted">
                                            {{ \Carbon\Carbon::parse($b['last_activity'])->format('M d, Y') }}
                                            @if(!$b['has_activity'])
                                                <span class="text-muted" style="font-size:0.75rem;">(registered)</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ \Carbon\Carbon::parse($b['joined_at'])->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            @if($b['detail_id'])
                                                <a href="{{ route('admin.verification-detail', $b['detail_id']) }}?from=borrowers" class="btn-view">
                                                    <i class="feather-eye"></i> View Details
                                                </a>
                                            @else
                                                <button class="btn-view disabled" disabled title="Walang naisumiteng personal details">
                                                    <i class="feather-eye"></i> View Details
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
    <script>
        // Client-side search + status filter — parehong pattern na ginamit sa
        // loan-applications (Approved/Rejected/On Hold search), pinagsama
        // lang dito ang dalawang criteria (text query + status dropdown).
        function filterBorrowers() {
            const query  = document.getElementById('filterSearchInput').value.trim().toLowerCase();
            const status = document.getElementById('statusFilter').value;
            const rows   = document.querySelectorAll('tbody tr[data-search]');
            const noResults = document.getElementById('filteredNoResults');
            const tableWrap = document.getElementById('borrowerTableWrap');
            let visibleCount = 0;

            rows.forEach(function (row) {
                const matchesQuery  = row.dataset.search.includes(query);
                const matchesStatus = !status || row.dataset.status === status;
                const visible = matchesQuery && matchesStatus;
                row.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            if (noResults && tableWrap) {
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
                tableWrap.style.display = visibleCount === 0 ? 'none' : '';
            }
        }

        // Client-side sort — lahat ng rows nasa DOM na (walang pagination sa
        // page na 'to), kaya pwedeng i-reorder lang sa JS instead na mag-
        // navigate sa bagong URL/full page reload. Filter state (display
        // style) stays intact dahil naka-attach yun sa mismong <tr> node.
        function sortBorrowers() {
            const order = document.getElementById('sortFilter').value;
            const tbody = document.querySelector('#borrowerTableWrap table tbody');
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr[data-search]'));
            rows.sort(function (a, b) {
                const aJoined = parseInt(a.dataset.joined, 10) || 0;
                const bJoined = parseInt(b.dataset.joined, 10) || 0;
                return order === 'oldest' ? aJoined - bJoined : bJoined - aJoined;
            });

            rows.forEach(function (row) {
                tbody.appendChild(row);
            });
        }
    </script>
</body>
</html>