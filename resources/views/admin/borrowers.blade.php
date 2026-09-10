@extends('admin.layout')

@section('title', 'All Borrowers')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/borrowers.css') }}">
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

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-label">Total Borrowers</span>
                    <i class="feather-users stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-sub">Registered accounts</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-label">Active Loans</span>
                    <i class="feather-check-circle stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['active_loan'] }}</div>
                <div class="stat-sub">Currently active</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-label">No Active Loan</span>
                    <i class="feather-inbox stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['no_loan'] }}</div>
                <div class="stat-sub">Awaiting first loan</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-label">Suspended Accounts</span>
                    <i class="feather-slash stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['suspended'] }}</div>
                <div class="stat-sub">Restricted access</div>
            </div>
        </div>

        <!-- Filter toolbar: single row — search grows on the left,
                     Status + Sort dropdowns pinned to the right. IDs/onchange
                     handlers unchanged so filterBorrowers() keeps working. -->
        <div class="filter-bar">
            <div class="search-bar-wrap">
                <i class="feather-search"></i>
                <input type="text" class="form-control" id="filterSearchInput"
                    placeholder="Search borrowers by name, email, or Borrower ID..." onkeyup="filterBorrowers()">
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

        @if (count($borrowers) === 0)
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
                                @foreach ($borrowers as $b)
                                    @php
                                        $statusMap = [
                                            'active_loan' => ['label' => 'Active Loan', 'class' => 'badge-approved'],
                                            'no_loan' => ['label' => 'No Loan', 'class' => 'badge-noloan'],
                                            'pending' => ['label' => 'Pending Application', 'class' => 'badge-pending'],
                                            'completed' => ['label' => 'Loan Completed', 'class' => 'badge-completed'],
                                            'suspended' => ['label' => 'Suspended', 'class' => 'badge-suspended'],
                                        ];
                                        $statusInfo = $statusMap[$b['loan_status']];
                                    @endphp
                                    <tr data-search="{{ strtolower($b['name'] . ' ' . $b['email'] . ' ' . $b['borrower_id']) }}"
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
                                            <span
                                                class="badge {{ $statusInfo['class'] }} px-3 py-2">{{ $statusInfo['label'] }}</span>
                                        </td>
                                        <td class="text-muted">{{ $b['total_loans'] }}</td>
                                        <td class="text-muted">
                                            {{ \Carbon\Carbon::parse($b['last_activity'])->format('M d, Y') }}
                                            @if (!$b['has_activity'])
                                                <span class="text-muted" style="font-size:0.75rem;">(registered)</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">
                                            {{ \Carbon\Carbon::parse($b['joined_at'])->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            @if ($b['detail_id'])
                                                <a href="{{ route('admin.verification-detail', $b['detail_id']) }}?from=borrowers"
                                                    class="btn-view">
                                                    <i class="feather-eye"></i> View Details
                                                </a>
                                            @else
                                                <button class="btn-view disabled" disabled
                                                    title="No verification details available">
                                                    <i class="feather-eye"></i> View Details
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">
                        <div class="rows-per-page">
                            <span>Rows per page</span>
                            <select class="per-page-select">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>

                        <div class="table-footer-info">
                        </div>

                        <div class="pagination-controls">
                            <button class="page-btn" data-action="prev" disabled><i
                                    class="feather-chevron-left"></i></button>
                            <button class="page-btn active">1</button>
                            <button class="page-btn">2</button>
                            <button class="page-btn">3</button>
                            <button class="page-btn" data-action="next"><i class="feather-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/all-borrowers.js') }}"></script>
@endsection
