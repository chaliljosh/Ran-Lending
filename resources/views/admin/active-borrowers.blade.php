@extends('admin.layout')

@section('title', 'Active Borrowers')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/active-borrowers.css') }}">
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Active Borrowers</h5>
            </div>
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
                <div class="stat-card-v2-head">
                    <span class="stat-label-v2">Active Borrowers</span>
                    <i class="feather-users stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">{{ $stats['total'] }}</div>
                <div class="stat-sub-v2">Currently active</div>
            </div>

            <div class="rs-card stat-card-v2">
                <div class="stat-card-v2-head">
                    <span class="stat-label-v2">Total Active Amount</span>
                    <i class="feather-dollar-sign stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">₱{{ number_format($stats['total_amount'], 0) }}</div>
                <div class="stat-sub-v2">Combined outstanding</div>
            </div>

            <div class="rs-card stat-card-v2">
                <div class="stat-card-v2-head">
                    <span class="stat-label-v2">Average Loan Amount</span>
                    <i class="feather-trending-up stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">₱{{ number_format($stats['avg_amount'], 0) }}</div>
                <div class="stat-sub-v2">Per active borrower</div>
            </div>
        </div>

        <!-- Filter toolbar -->
        <div class="filter-bar-v2">
            <div class="search-wrap-v2">
                <i class="feather-search"></i>
                <input type="text" id="filterSearchInput"
                    placeholder="Search borrowers by name, email, or Borrower ID..." onkeyup="filterActiveBorrowers()">
            </div>
            <select class="sort-select-v2" id="sortFilter" onchange="sortActiveBorrowers()">
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
            </select>
        </div>

        @if (count($activeBorrowers) === 0)
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
                            @foreach ($activeBorrowers as $b)
                                <tr data-search="{{ strtolower($b['name'] . ' ' . $b['email'] . ' ' . $b['borrower_id']) }}"
                                    data-applied="{{ \Carbon\Carbon::parse($b['applied_at'])->timestamp }}"
                                    data-match="true">
                                    <td>
                                        <div class="borrower-cell-v2">
                                            <div>
                                                <div class="borrower-name-v2">{{ $b['name'] }}</div>
                                                <div class="borrower-id-v2">{{ $b['borrower_id'] }}</div>
                                                <div class="borrower-email-v2">{{ $b['email'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="loan-amount-v2">₱{{ number_format($b['loan_amount'], 2) }}</td>
                                    <td class="loan-meta-v2">{{ $b['loan_term'] }}
                                        {{ Str::plural('month', $b['loan_term']) }}</td>
                                    <td>
                                        @if ($b['loan_purpose'])
                                            <span
                                                class="purpose-pill">{{ str_replace('_', ' ', $b['loan_purpose']) }}</span>
                                        @else
                                            <span class="loan-meta-v2">—</span>
                                        @endif
                                    </td>
                                    <td class="loan-meta-v2">
                                        {{ \Carbon\Carbon::parse($b['applied_at'])->format('M d, Y') }}</td>
                                    <td class="text-end">
                                        {{-- Points to the new, focused Active Borrower Detail page
                                     (profile + standing, Loan Summary, Payment Progress,
                                     Payment Schedule) instead of loan-application-detail,
                                     so that page's approve/reject review flow stays untouched. --}}
                                        <a href="{{ route('admin.active-borrower-detail', $b['application_id']) }}"
                                            class="btn-view-v2">
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

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/active-borrowers.js') }}"></script>
@endsection
