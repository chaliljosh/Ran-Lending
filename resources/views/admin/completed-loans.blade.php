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

                <!-- Stats -->
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

        <!-- Filter toolbar -->
        <div class="filter-bar-v2">
            <div class="search-wrap-v2">
                <i class="feather-search"></i>
                <input type="text" id="filterSearchInput"
                       placeholder="Search borrowers by name, email, or Borrower ID..."
                       onkeyup="onSearchChanged()">
            </div>

            <select class="sort-select-v2" id="sortFilter" onchange="location.href='{{ route('admin.completed-loans') }}?sort=' + this.value">
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                <option value="completed_newest" {{ $sort === 'completed_newest' ? 'selected' : '' }}>Recently Completed</option>
                <option value="completed_oldest" {{ $sort === 'completed_oldest' ? 'selected' : '' }}>Completed (Oldest)</option>
            </select>

            <!-- Rows per page: label + small dropdown, like the Overdue Payments page -->
            <div class="rows-per-page-v2">
                <span class="rows-per-page-label-v2">Rows per page</span>
                <select class="rows-per-page-select-v2" id="rowsPerPageSelect" onchange="onRowsPerPageChanged()">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>

        @if(count($completedLoans) === 0)
        <div class="rs-card">
            <div class="empty-state-v2">
                <i class="feather-inbox"></i>
                <h6>No completed loans yet</h6>
                <p>Completed loans will appear here.</p>
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
                            <th>Term</th>
                            <th>Completed On</th>
                            <th>Total Repaid</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="loanTableBody">
                        @foreach($completedLoans as $loan)
                        <tr data-search="{{ strtolower($loan['name'] . ' ' . $loan['email'] . ' ' . $loan['borrower_id']) }}">
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
                                @if($loan['completed_at'])
                                    {{ \Carbon\Carbon::parse($loan['completed_at'])->format('M d, Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="repaid-amount-v2">₱{{ number_format($loan['amount_collected'], 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.completed-loan-detail', $loan['application_id']) }}" class="btn-view-v2">
                                    <i class="feather-eye"></i> View Details
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination bar (filled in by JS) -->
            <div class="pagination-wrap-v2" id="paginationWrap">
                <div class="pagination-info-v2" id="paginationInfo"></div>
                <div class="pagination-buttons-v2" id="paginationButtons">
                    <button type="button" class="prev-next-btn-v2" id="prevPageBtn" onclick="goToPreviousPage()">
                        <i class="feather-chevron-left"></i> Prev
                    </button>
                    <span class="page-status-v2" id="pageStatusText">Page 1 of 1</span>
                    <button type="button" class="prev-next-btn-v2" id="nextPageBtn" onclick="goToNextPage()">
                        Next <i class="feather-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
        @endif

    </div>
@endsection

@section('page-scripts')
    <script src="{{ asset('functions/completed-loans.js') }}"></script>
@endsection