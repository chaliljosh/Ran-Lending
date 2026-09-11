@extends('admin.layout')

@section('title', 'Overdue Payments')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/overdue-payments.css') }}">
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Overdue Payments</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Active Loans</li>
                <li class="breadcrumb-item">Overdue Payments</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

        <!-- Stats -->
        <!-- Stats -->
        <div class="stats-grid-v2">
            <div class="rs-card stat-card-v2">
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Overdue Loans</span>
                    <i class="feather-alert-triangle stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">{{ $stats['total'] }}</div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Total Overdue Amount</span>
                    <i class="feather-dollar-sign stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">₱{{ number_format($stats['total_overdue_amount'], 0) }}</div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Overdue Installments</span>
                    <i class="feather-clock stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">{{ $stats['total_overdue_installments'] }}</div>
            </div>
        </div>

        <!-- Filter toolbar -->
        <div class="filter-bar-v2">
            <div class="search-wrap-v2">
                <i class="feather-search"></i>
                <input type="text" id="filterSearchInput"
                    placeholder="Search borrowers by name, email, or Borrower ID..." onkeyup="filterOverdueLoans()">
            </div>
            <select class="sort-select-v2" id="sortFilter" onchange="sortOverdueLoans()">
                <option value="most_late" {{ $sort === 'most_late' ? 'selected' : '' }}>Most Days Late</option>
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
            </select>
            <div class="rows-per-page-v2">
                <label for="opRowsPerPage" class="rows-per-page-label-v2">Rows per page</label>
                <select id="opRowsPerPage" class="rows-per-page-select-v2" onchange="opChangeRowsPerPage(this)">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        @if (count($overdueLoans) === 0)
            <div class="rs-card">
                <div class="empty-state-v2">
                    <i class="feather-check-circle"></i>
                    <h6>There are currently no overdue loans.</h6>
                    <p>All active loans are up to date.</p>
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
                                <th>Overdue Installments</th>
                                <th>Days Late</th>
                                <th>Amount Overdue</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($overdueLoans as $loan)
                                <tr data-search="{{ strtolower($loan['name'] . ' ' . $loan['email'] . ' ' . $loan['borrower_id']) }}"
                                    data-applied="{{ \Carbon\Carbon::parse($loan['applied_at'])->timestamp }}"
                                    data-days-late="{{ $loan['days_late'] }}">
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
                                    <td class="loan-meta-v2">{{ $loan['overdue_count'] }}/{{ $loan['total_count'] }}
                                        unpaid</td>
                                    <td>
                                        <span class="days-late-pill {{ $loan['days_late'] >= 30 ? 'severe' : '' }}">
                                            {{ $loan['days_late'] }} {{ Str::plural('day', $loan['days_late']) }}
                                        </span>
                                    </td>
                                    <td class="overdue-amount-v2">₱{{ number_format($loan['overdue_amount'], 2) }}</td>
                                    <td>
                                        @if ($loan['is_defaulted'])
                                            <span class="dl-status-pill">Defaulted</span>
                                        @else
                                            <span class="ov-status-pill">Overdue</span>
                                        @endif
                                    </td>
                                    <td class="text-end">

                                        <a href="{{ route('admin.overdue-payment-detail', $loan['application_id']) }}"
                                            class="btn-view-v2">
                                            <i class="feather-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination bar: info text (left) + Prev/Next controls (right). --}}
                <div class="pagination-bar-v2" id="opPagination">
                    <span class="pagination-info-v2" id="opPaginationInfo">
                        Showing 1&ndash;{{ min(10, count($overdueLoans)) }} of {{ count($overdueLoans) }}
                    </span>
                    <div class="pagination-controls-v2">
                        <button type="button" class="btn-page-v2" id="opPrevPage" onclick="opGoToPage(-1)">
                            <i class="feather-chevron-left"></i> Prev
                        </button>
                        <span class="page-indicator-v2" id="opPageIndicator">Page 1 of 1</span>
                        <button type="button" class="btn-page-v2" id="opNextPage" onclick="opGoToPage(1)">
                            Next <i class="feather-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection

@section('page-scripts')
    <script src="{{ asset('functions/overdue-payments.js') }}"></script>
@endsection
