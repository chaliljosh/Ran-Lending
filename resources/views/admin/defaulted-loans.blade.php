@extends('admin.layout')

@section('title', 'Defaulted Loans')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/defaulted-loans.css') }}">
@endsection

@section('content')

    

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Defaulted Loans</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.ongoing-loans') }}">Active Loans</a></li>
                <li class="breadcrumb-item">Defaulted</li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <!-- Stats -->
        <div class="stats-grid-v2">
            <div class="rs-card stat-card-v2">
                <div class="stat-icon-v2 total"><i class="feather-alert-octagon"></i></div>
                <div>
                    <div class="stat-label-v2">Defaulted Loans</div>
                    <div class="stat-value-v2">{{ $stats['total'] }}</div>
                 
                </div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-icon-v2 amount"><i class="feather-dollar-sign"></i></div>
                <div>
                    <div class="stat-label-v2">Total Outstanding</div>
                    <div class="stat-value-v2">₱{{ number_format($stats['total_outstanding'], 2) }}</div>
         
                </div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-icon-v2 avg"><i class="feather-alert-triangle"></i></div>
                <div>
                    <div class="stat-label-v2">Unpaid Penalties</div>
                    <div class="stat-value-v2">₱{{ number_format($stats['total_penalties'], 2) }}</div>
               
                </div>
            </div>
        </div>

        <!-- Filter toolbar -->
        <div class="dl-filter-bar">
            <div class="dl-search-wrap">
                <i class="feather-search"></i>
                <input type="text" id="dlSearchInput" placeholder="Search borrower or loan ID..." onkeyup="dlFilterRows()">
            </div>
            <select class="dl-select" id="dlDaysFilter" onchange="dlFilterRows()">
                <option value="all">All Days Overdue</option>
                <option value="90">90&ndash;99 days</option>
                <option value="100">100+ days</option>
            </select>
            <select class="dl-select" id="dlSortFilter" onchange="location.href='{{ route('admin.defaulted-loans') }}?sort=' + this.value">
                <option value="most_late" {{ $sort === 'most_late' ? 'selected' : '' }}>Sort: Most Days Late</option>
                <option value="highest_balance" {{ $sort === 'highest_balance' ? 'selected' : '' }}>Sort: Highest Outstanding Balance</option>
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Sort: Newest Applied</option>
                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Sort: Oldest Applied</option>
            </select>
        </div>

        @if(count($defaultedLoans) === 0)
        <div class="rs-card">
            <div class="empty-state-v2">
                <i class="feather-check-circle"></i>
                <h6>No defaulted loans</h6>
                <p>No loans have crossed the {{ $defaultDaysLate }}-day default threshold.</p>
            </div>
        </div>
        @else
        <div class="rs-card rs-table-card">
            <div id="dlNoResults" class="empty-state-v2" style="display:none;">
                <i class="feather-search"></i>
                <h6>No matches found</h6>
                <p>Try a different search term or filter.</p>
            </div>
            <div class="table-responsive" id="dlTableWrap">
                <table class="table rs-table mb-0" id="dlTable">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Loan ID</th>
                            <th>Original Loan</th>
                            <th>Outstanding Balance</th>
                            <th>Days Overdue</th>
                            <th>Unpaid Penalty</th>
                            <th>Last Payment</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($defaultedLoans as $loan)
                        <tr data-search="{{ strtolower($loan['name'] . ' ' . ($loan['loan_id_display'] ?? '')) }}"
                            data-days-late="{{ $loan['days_late'] }}">
                            <td>
                                <div class="borrower-cell-v2">
                                    <div class="dl-avatar">{{ $loan['initials'] }}</div>
                                    <div>
                                        <div class="borrower-name-v2">{{ $loan['name'] }}</div>
                                        <div class="borrower-email-v2">{{ $loan['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="dl-loan-id">{{ $loan['loan_id_display'] ?? ('LN-' . str_pad($loan['application_id'], 5, '0', STR_PAD_LEFT)) }}</span></td>
                            <td class="loan-amount-v2">₱{{ number_format($loan['loan_amount'], 2) }}</td>
                            <td class="dl-outstanding-v2">₱{{ number_format($loan['outstanding_amount'], 2) }}</td>
                            <td><span class="dl-days-text">{{ $loan['days_late'] }} days</span></td>
                            <td class="loan-meta-v2">₱{{ number_format($loan['unpaid_penalty'] ?? 0, 2) }}</td>
                            <td class="loan-meta-v2">
                                {{ $loan['last_payment_at'] ? \Carbon\Carbon::parse($loan['last_payment_at'])->format('M d, Y') : 'No payments made' }}
                            </td>
                            <td><span class="dl-status-pill">Defaulted</span></td>
                            <td class="text-end">
                                <div class="dl-actions-cell">
                                    <a href="{{ route('admin.defaulted-loan-detail', $loan['application_id']) }}" class="btn-view-v2">
                                        <i class="feather-eye"></i> View Details
                                    </a>
                                    <div class="dl-kebab-wrap">
                                        <button type="button" class="dl-kebab-btn" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
    <i class="feather-more-vertical"></i>
</button>
                                        <ul class="dropdown-menu dropdown-menu-end dl-kebab-menu">
                                            <li><a class="dropdown-item" href="#">Send Reminder</a></li>
                                            <li><a class="dropdown-item" href="#">Add Note</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="dl-pagination-bar" id="dlPaginationBar">
                <div class="dl-rows-per-page">
                    <label for="dlRowsPerPage">Rows per page:</label>
                    <select id="dlRowsPerPage" class="dl-select dl-select-sm" onchange="dlChangeRowsPerPage(this)">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <span class="dl-pagination-info" id="dlPaginationInfo">
                    Showing 1&ndash;{{ min(10, count($defaultedLoans)) }} of {{ count($defaultedLoans) }} entries
                </span>
                <div class="dl-pagination-controls">
                    <button type="button" class="dl-page-arrow" id="dlPrevPage" onclick="dlGoToPage(-1)">
                        <i class="feather-chevron-left"></i>
                    </button>
                    <span class="dl-page-number" id="dlPageIndicator">1</span>
                    <button type="button" class="dl-page-arrow" id="dlNextPage" onclick="dlGoToPage(1)">
                        <i class="feather-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
        @endif

    </div>
@endsection

@section('page-scripts')
    <script src="{{ asset('functions/defaulted-loans.js') }}"></script>
@endsection