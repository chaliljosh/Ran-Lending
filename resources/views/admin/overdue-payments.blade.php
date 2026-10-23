@extends('admin.layout')

@section('title', 'Overdue Payments')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/overdue-payments.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
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
                <!-- Filter toolbar -->
                <div class="filter-bar-v2">
                    <div class="search-wrap-v2">
                        <i class="feather-search"></i>
                        <input type="text" id="filterSearchInput"
                            placeholder="Search borrowers by name, email, or Borrower ID..." onkeyup="filterOverdueLoans()">
                    </div>
                    {{-- Lahat ng 12 buwan. Ang value ay "01" hanggang "12" (walang year) --}}
                    <select class="sort-select-v2" id="monthFilter" onchange="filterOverdueLoans()" aria-label="Filter by month">
                        <option value="">All Months</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                    <select class="sort-select-v2" id="sortFilter" onchange="sortOverdueLoans()">
                        <option value="most_late" {{ $sort === 'most_late' ? 'selected' : '' }}>Most Days Late</option>
                        <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    </select>
                </div>

                <div id="filteredNoResults" class="empty-state-v2">
                    <i class="feather-search"></i>
                    <h6>No matches found</h6>
                    <p>Try a different search term.</p>
                </div>
                <div class="table-responsive" id="loanTableWrap">
                    <table class="table rs-table mb-0">
                        <thead>
                            <tr>
                                {{-- Click ang header para mag-sort. Ang data-sort ay tumutugma sa data-* ng <tr> --}}
                                <th data-sort="name" onclick="opSortByColumn('name')">Borrower</th>
                                <th data-sort="loanAmount" onclick="opSortByColumn('loanAmount')">Loan Amount</th>
                                <th data-sort="overdueCount" onclick="opSortByColumn('overdueCount')">Overdue Installments</th>
                                <th data-sort="daysLate" onclick="opSortByColumn('daysLate')">Days Late</th>
                                <th data-sort="overdueAmount" onclick="opSortByColumn('overdueAmount')">Amount Overdue</th>
                                <th data-sort="status" onclick="opSortByColumn('status')">Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($overdueLoans as $loan)
                                <tr data-search="{{ strtolower($loan['name'] . ' ' . $loan['email'] . ' ' . $loan['borrower_id']) }}"
                                    data-name="{{ $loan['name'] }}"
                                    data-loan-amount="{{ $loan['loan_amount'] }}"
                                    data-overdue-count="{{ $loan['overdue_count'] }}"
                                    data-overdue-amount="{{ $loan['overdue_amount'] }}"
                                    data-status="{{ $loan['is_defaulted'] ? 1 : 0 }}"
                                    data-applied="{{ \Carbon\Carbon::parse($loan['applied_at'])->timestamp }}"
                                    data-month="{{ \Carbon\Carbon::parse($loan['applied_at'])->format('m') }}"
                                    data-days-late="{{ $loan['days_late'] }}"
                                    data-href="{{ route('admin.overdue-payment-detail', $loan['uuid']) }}"
                                    onclick="opGoToRow(event, this)"
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

                                        <a href="{{ route('admin.overdue-payment-detail', $loan['uuid']) }}"
                                            class="btn-view-v2">
                                            <i class="feather-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination bar: Rows per page + Showing (kaliwa), page numbers (gitna) --}}
                <div class="pagination-bar-v2" id="opPagination">
                    <div class="pagination-left-v2">
                        <div class="rows-per-page-v2">
                            <label for="opRowsPerPage" class="rows-per-page-label-v2">Rows per page</label>
                            <select id="opRowsPerPage" class="rows-per-page-select-v2" onchange="opChangeRowsPerPage(this)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <span class="pagination-info-v2" id="opPaginationInfo">
                            Showing 1&ndash;{{ min(10, count($overdueLoans)) }} of {{ count($overdueLoans) }}
                        </span>
                    </div>
                    <div class="pagination-nav-v2" id="opPaginationNav"></div>
                    <div></div>
                </div>
            </div>
        @endif

    </div>
@endsection

@section('page-scripts')
<script src="{{ asset('functions/overdue-payment.js') }}"></script>
@endsection