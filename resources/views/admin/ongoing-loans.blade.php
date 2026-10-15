@extends('admin.layout')

@section('title', 'Ongoing Loans')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loans.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}">
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Ongoing Loans</h5>
            </div>
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
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Ongoing Loans</span>
                    <i class="feather-repeat stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">{{ $stats['total'] }}</div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Total Loan Amount</span>
                    <i class="feather-dollar-sign stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">₱{{ number_format($stats['total_amount'], 0) }}</div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Total Collected</span>
                    <i class="feather-check-circle stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">₱{{ number_format($stats['total_collected'], 0) }}</div>
            </div>
        </div>

        <!-- Filter toolbar -->
        <div class="filter-bar-v2">
            <div class="search-wrap-v2">
                <i class="feather-search"></i>
                <input type="text" id="filterSearchInput"
                    placeholder="Search borrowers by name, email, or Borrower ID..." onkeyup="filterOngoingLoans()">
            </div>
            <select class="sort-select-v2" id="sortFilter" onchange="sortOngoingLoans()">
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
            </select>
            <div class="rows-per-page-v2">
                <label for="olRowsPerPage" class="rows-per-page-label-v2">Rows per page</label>
                <select id="olRowsPerPage" class="rows-per-page-select-v2" onchange="olChangeRowsPerPage(this)">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        @if (count($ongoingLoans) === 0)
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
                   <table class="rs-table mb-0">
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
                            @foreach ($ongoingLoans as $loan)
                                <tr data-search="{{ strtolower($loan['name'] . ' ' . $loan['email'] . ' ' . $loan['borrower_id']) }}"
                                    data-applied="{{ \Carbon\Carbon::parse($loan['applied_at'])->timestamp }}"
                                    data-href="{{ route('admin.ongoing-loan-detail', $loan['uuid']) }}?from=ongoing-loans"
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
                                    <td>
                                        <div class="progress-cell-v2">
                                            <div class="progress-bar-track-v2">
                                                <div class="progress-bar-fill-v2"
                                                    style="width: {{ $loan['total_count'] ? round(($loan['paid_count'] / $loan['total_count']) * 100) : 0 }}%">
                                                </div>
                                            </div>
                                            <span
                                                class="progress-label-v2">{{ $loan['paid_count'] }}/{{ $loan['total_count'] }}
                                                paid</span>
                                        </div>
                                    </td>
                                    <td class="loan-meta-v2">
                                        @if ($loan['next_due_date'])
                                            {{ \Carbon\Carbon::parse($loan['next_due_date'])->format('M d, Y') }}
                                            <div class="next-due-amount-v2">
                                                ₱{{ number_format($loan['next_due_amount'], 2) }}</div>
                                        @else
                                            <span class="purpose-pill">Fully Paid</span>
                                        @endif
                                    </td>
                                    <td class="loan-amount-v2">₱{{ number_format($loan['amount_collected'], 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.ongoing-loan-detail', $loan['uuid']) }}?from=ongoing-loans"
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
                <div class="pagination-bar-v2" id="olPagination">
                    <span class="pagination-info-v2" id="olPaginationInfo">
                        Showing 1&ndash;{{ min(10, count($ongoingLoans)) }} of {{ count($ongoingLoans) }}
                    </span>
                    <div class="pagination-controls-v2">
                        <button type="button" class="btn-page-v2" id="olPrevPage" onclick="olGoToPage(-1)">
                            <i class="feather-chevron-left"></i> Prev
                        </button>
                        <span class="page-indicator-v2" id="olPageIndicator">Page 1 of 1</span>
                        <button type="button" class="btn-page-v2" id="olNextPage" onclick="olGoToPage(1)">
                            Next <i class="feather-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/ongoing-loans.js') }}"></script>
@endsection