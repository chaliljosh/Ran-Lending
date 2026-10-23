@extends('admin.layout')

@section('title', 'All Borrowers')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/borrowers.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">All Borrowers</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">All Borrowers</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-label">Total Borrowers</span>
                    <i class="feather-users stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['total'] }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-label">Active Loans</span>
                    <i class="feather-check-circle stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['active_loan'] }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-label">No Active Loan</span>
                    <i class="feather-inbox stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['no_loan'] }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-label">Suspended Accounts</span>
                    <i class="feather-slash stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['suspended'] }}</div>
            </div>
        </div>

        @if (count($borrowers) === 0)
            <div class="card">
                <div class="borrowers-empty">
                    <i class="feather-users"></i>
                    <h6>No borrowers yet</h6>
                    <p>Registered borrowers will appear here.</p>
                </div>
            </div>
        @else
            @php
                $statusMap = [
                    'active_loan' => ['label' => 'Active Loan', 'class' => 'badge-approved'],
                    'no_loan' => ['label' => 'No Loan', 'class' => 'badge-noloan'],
                    'pending' => ['label' => 'Pending Application', 'class' => 'badge-pending'],
                    'completed' => ['label' => 'Loan Completed', 'class' => 'badge-completed'],
                    'suspended' => ['label' => 'Suspended', 'class' => 'badge-suspended'],
                ];
            @endphp

            <div class="card table-card">
                <div class="filter-bar">
                    <div class="search-bar-wrap">
                        <i class="feather-search"></i>
                        <input type="text" class="form-control" id="filterSearchInput"
                            placeholder="Search borrowers by name, email, or Borrower ID..." onkeyup="filterBorrowers()">
                    </div>
                    <div class="filter-bar-actions">
                        <select class="form-select" id="statusFilter" onchange="filterBorrowers()" aria-label="Filter by loan status">
                            <option value="">All Loan Statuses</option>
                            <option value="active_loan">Active Loan</option>
                            <option value="no_loan">No Loan</option>
                            <option value="pending">Pending Application</option>
                            <option value="completed">Loan Completed</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        <select class="form-select" id="sortFilter" onchange="sortBorrowers()" aria-label="Sort borrowers">
                            <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                            <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        </select>
                    </div>
                </div>

                <div id="filteredNoResults" class="borrowers-empty">
                    <i class="feather-search"></i>
                    <h6>No matches found</h6>
                    <p>Try a different search term or filter.</p>
                </div>

                <div class="table-responsive" id="borrowerTableWrap">
                    <table class="table mb-0 borrower-table">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>Loan Status</th>
                                <th>Total Loans</th>
                                <th>Last Application</th>
                                <th>Joined</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($borrowers as $b)
                                @php $statusInfo = $statusMap[$b['loan_status']]; @endphp
                                <tr @if ($b['detail_id']) class="rs-clickable-row" data-href="{{ route('admin.verification-detail', $b['detail_id']) }}" @endif
                                    data-search="{{ strtolower($b['name'] . ' ' . $b['email'] . ' ' . $b['borrower_id']) }}"
                                    data-status="{{ $b['loan_status'] }}"
                                    data-joined="{{ \Carbon\Carbon::parse($b['joined_at'])->timestamp }}">
                                    <td>
                                        <div class="borrower-cell">
                                            <div>
                                                <div class="borrower-name">{{ $b['name'] }}</div>
                                                <div class="borrower-id-text">{{ $b['borrower_id'] }}</div>
                                                <div class="borrower-email">{{ $b['email'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                    </td>
                                    <td class="cell-num">{{ $b['total_loans'] }}</td>
                                    <td class="cell-meta">
                                        @if ($b['has_activity'])
                                            {{ \Carbon\Carbon::parse($b['last_activity'])->format('M d, Y') }}
                                        @else
                                            <span class="cell-note">No application yet</span>
                                        @endif
                                    </td>
                                    <td class="cell-meta">{{ \Carbon\Carbon::parse($b['joined_at'])->format('M d, Y') }}</td>
                                    <td class="text-end">
                                        @if ($b['detail_id'])
                                            <a href="{{ route('admin.verification-detail', $b['detail_id']) }}" class="btn-view-details">
                                                <i class="feather-eye"></i>
                                                <span>View Details</span>
                                            </a>
                                        @else
                                            <span class="cell-note">No details submitted</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <div class="footer-left">
                        <div class="rows-per-page">
                            <label for="borrowersPerPage">Rows per page</label>
                            <select class="per-page-select" id="borrowersPerPage">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <span class="table-footer-info"></span>
                    </div>
                    <div class="pagination-controls"></div>
                    <div></div>
                </div>
            </div>
        @endif

    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/all-borrowers.js') }}?v={{ time() }}"></script>
@endsection