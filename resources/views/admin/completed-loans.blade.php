@extends('admin.layout')

@section('title', 'Completed Loans')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/completed-loans.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Completed Loans</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Active Loans</li>
                <li class="breadcrumb-item">Completed Loans</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

        <div class="stats-grid-v2">
            <div class="rs-card stat-card-v2">
                <div class="stat-card-v2-head">
                    <div class="stat-label-v2">Completed Loans</div>
                    <i class="feather-check-circle stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">{{ $stats['total'] }}</div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-card-v2-head">
                    <div class="stat-label-v2">Total Amount Repaid</div>
                    <i class="feather-dollar-sign stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">₱{{ number_format($stats['total_amount'], 0) }}</div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-card-v2-head">
                    <div class="stat-label-v2">Total Interest Earned</div>
                    <i class="feather-trending-up stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">₱{{ number_format($stats['total_interest'], 0) }}</div>
            </div>
        </div>

        @if (count($completedLoans) === 0)
            <div class="rs-card">
                <div class="empty-state-v2">
                    <i class="feather-inbox"></i>
                    <h6>No completed loans yet</h6>
                    <p>Completed loans will appear here.</p>
                </div>
            </div>
        @else
            <div class="rs-card rs-table-card">
                <div class="filter-bar-v2">
                    <div class="search-wrap-v2">
                        <i class="feather-search"></i>
                        <input type="text" id="filterSearchInput"
                            placeholder="Search borrowers by name, email, or Borrower ID..."
                            onkeyup="filterCompletedLoans()">
                    </div>

                    <select class="sort-select-v2" id="monthFilter" onchange="filterCompletedLoans()" aria-label="Filter by month">
                        <option value="">All Months</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>

                    <select class="sort-select-v2" id="sortFilter" onchange="sortCompletedLoans()">
                        <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        <option value="completed_newest" {{ $sort === 'completed_newest' ? 'selected' : '' }}>Recently Completed</option>
                        <option value="completed_oldest" {{ $sort === 'completed_oldest' ? 'selected' : '' }}>Completed (Oldest)</option>
                    </select>
                </div>

                <div id="filteredNoResults" class="empty-state-v2">
                    <i class="feather-search"></i>
                    <h6>No matches found</h6>
                    <p>Try a different search term or month.</p>
                </div>

                <div class="table-responsive" id="loanTableWrap">
                    <table class="table rs-table mb-0">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>Loan Amount</th>
                                <th>Term</th>
                                <th>Completed On</th>
                                <th>Total Repaid</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="loanTableBody">
                            @foreach ($completedLoans as $loan)
                                <tr data-search="{{ strtolower($loan['name'] . ' ' . $loan['email'] . ' ' . $loan['borrower_id']) }}"
                                    data-applied="{{ !empty($loan['applied_at']) ? \Carbon\Carbon::parse($loan['applied_at'])->timestamp : 0 }}"
                                    data-completed="{{ !empty($loan['completed_at']) ? \Carbon\Carbon::parse($loan['completed_at'])->timestamp : 0 }}"
                                    data-month="{{ $loan['completed_at'] ? \Carbon\Carbon::parse($loan['completed_at'])->format('m') : '' }}"
                                    data-href="{{ route('admin.completed-loan-detail', $loan['uuid']) }}"
                                    class="rs-clickable-row">
                                    <td>
                                        <div class="borrower-cell-v2">
                                            <div>
                                                <div class="borrower-name-v2">{{ $loan['name'] }}</div>
                                                <div class="borrower-id-v2">{{ $loan['borrower_id'] }}</div>
                                                <div class="borrower-email-v2">{{ $loan['email'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="loan-amount-v2">₱{{ number_format($loan['loan_amount'], 2) }}</td>
                                    <td class="loan-meta-v2">{{ $loan['loan_term'] }} {{ Str::plural('month', $loan['loan_term']) }}</td>
                                    <td class="loan-meta-v2">
                                        @if ($loan['completed_at'])
                                            {{ \Carbon\Carbon::parse($loan['completed_at'])->format('M d, Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="repaid-amount-v2">₱{{ number_format($loan['amount_collected'], 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.completed-loan-detail', $loan['uuid']) }}" class="btn-view-v2">
                                            <i class="feather-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pagination-bar-v2" id="clPagination">
                    <div class="pagination-left-v2">
                        <div class="rows-per-page-v2">
                            <label for="clRowsPerPage" class="rows-per-page-label-v2">Rows per page</label>
                            <select id="clRowsPerPage" class="rows-per-page-select-v2" onchange="clChangeRowsPerPage(this)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <span class="pagination-info-v2" id="clPaginationInfo">
                            Showing 1&ndash;{{ min(10, count($completedLoans)) }} of {{ count($completedLoans) }}
                        </span>
                    </div>
                    <div class="pagination-nav-v2" id="clPaginationNav"></div>
                    <div></div>
                </div>
            </div>
        @endif

    </div>
@endsection

@section('page-scripts')
    <script src="{{ asset('functions/completed-loans.js') }}?v={{ time() }}"></script>
@endsection