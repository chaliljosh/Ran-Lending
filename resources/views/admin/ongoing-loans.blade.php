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
                <!-- Filter toolbar -->
                <div class="filter-bar-v2">
                    <div class="search-wrap-v2">
                        <i class="feather-search"></i>
                        <input type="text" id="filterSearchInput"
                            placeholder="Search borrowers by name, email, or Borrower ID..." onkeyup="filterOngoingLoans()">
                    </div>
                    {{-- Lahat ng 12 buwan. Ang value ay "01" hanggang "12" (walang year) --}}
                    <select class="sort-select-v2" id="monthFilter" onchange="filterOngoingLoans()" aria-label="Filter by month">
                        <option value="">All Months</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                    {{-- Awaiting Release = wala pang schedule. Ongoing = may schedule na. --}}
                    <select class="sort-select-v2" id="statusFilter" onchange="filterOngoingLoans()" aria-label="Filter by status">
                        <option value="">All Status</option>
                        <option value="awaiting">Awaiting Release</option>
                        <option value="ongoing">Ongoing</option>
                    </select>
                    <select class="sort-select-v2" id="sortFilter" onchange="sortOngoingLoans()">
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
                                <th data-sort="name" onclick="olSortByColumn('name')">Borrower</th>
                                <th data-sort="loanAmount" onclick="olSortByColumn('loanAmount')">Loan Amount</th>
                                <th data-sort="progress" onclick="olSortByColumn('progress')">Progress</th>
                                <th data-sort="nextDue" onclick="olSortByColumn('nextDue')">Next Due</th>
                                <th data-sort="collected" onclick="olSortByColumn('collected')">Collected</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ongoingLoans as $loan)
                                @php
                                    // Walang schedule = hindi pa nare-release (hindi pa na-send ang proof)
                                    $hasSchedule = $loan['total_count'] > 0;

                                    $progressPercent = $hasSchedule ? round(($loan['paid_count'] / $loan['total_count']) * 100) : 0;
                                    $nextDueStamp = $loan['next_due_date'] ? \Carbon\Carbon::parse($loan['next_due_date'])->timestamp : 9999999999;
                                @endphp
                                <tr data-search="{{ strtolower($loan['name'] . ' ' . $loan['email'] . ' ' . $loan['borrower_id']) }}"
                                    data-name="{{ $loan['name'] }}"
                                    data-loan-amount="{{ $loan['loan_amount'] }}"
                                    data-progress="{{ $progressPercent }}"
                                    data-next-due="{{ $nextDueStamp }}"
                                    data-collected="{{ $loan['amount_collected'] }}"
                                    data-applied="{{ \Carbon\Carbon::parse($loan['applied_at'])->timestamp }}"
                                    data-month="{{ \Carbon\Carbon::parse($loan['applied_at'])->format('m') }}"
                                    data-status="{{ $hasSchedule ? 'ongoing' : 'awaiting' }}"
                                    data-href="{{ route('admin.ongoing-loan-detail', $loan['uuid']) }}?from=ongoing-loans"
                                    onclick="olGoToRow(event, this)"
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
                                                <div class="progress-bar-fill-v2" style="width: {{ $progressPercent }}%"></div>
                                            </div>
                                            <span class="progress-label-v2">
                                                @if ($hasSchedule)
                                                    {{ $loan['paid_count'] }}/{{ $loan['total_count'] }} paid
                                                @else
                                                    Not started
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="loan-meta-v2">
                                        @if (!$hasSchedule)
                                            <span class="purpose-pill">Awaiting Release</span>
                                        @elseif ($loan['next_due_date'])
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

                {{-- Pagination bar: Rows per page + Showing (kaliwa), page numbers (gitna) --}}
                <div class="pagination-bar-v2" id="olPagination">
                    <div class="pagination-left-v2">
                        <div class="rows-per-page-v2">
                            <label for="olRowsPerPage" class="rows-per-page-label-v2">Rows per page</label>
                            <select id="olRowsPerPage" class="rows-per-page-select-v2" onchange="olChangeRowsPerPage(this)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <span class="pagination-info-v2" id="olPaginationInfo">
                            Showing 1&ndash;{{ min(10, count($ongoingLoans)) }} of {{ count($ongoingLoans) }}
                        </span>
                    </div>
                    <div class="pagination-nav-v2" id="olPaginationNav"></div>
                    <div></div>
                </div>
            </div>
        @endif

    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/ongoing-loans.js') }}"></script>
@endsection