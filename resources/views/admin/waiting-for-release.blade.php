@extends('admin.layout')

@section('title', 'Waiting for Release')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loans.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}">
    <style>
        /* Waiting pills: green = bago pa, amber = 1-2 araw, red = 3+ araw */
        .wait-pill {
            display: inline-block; padding: 4px 10px; border-radius: 6px;
            font-size: 0.74rem; font-weight: 600; white-space: nowrap;
        }
        .wait-pill.is-new   { background: var(--rs-green-soft); color: var(--rs-green); }
        .wait-pill.is-warn  { background: var(--rs-amber-soft); color: var(--rs-amber); }
        .wait-pill.is-late  { background: var(--rs-red-soft);   color: var(--rs-red); }

        html.app-skin-dark .wait-pill.is-new  { background: rgba(22,163,74,.15) !important; color: #6ee7a4 !important; }
        html.app-skin-dark .wait-pill.is-warn { background: rgba(180,83,9,.2) !important;  color: #fbbf24 !important; }
        html.app-skin-dark .wait-pill.is-late { background: rgba(220,38,38,.18) !important; color: #f87171 !important; }
    </style>
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Waiting for Release</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Loans</li>
                <li class="breadcrumb-item">Waiting for Release</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

        <!-- Stats -->
        <div class="stats-grid-v2">
            <div class="rs-card stat-card-v2">
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Waiting for Release</span>
                    <i class="feather-send stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">{{ $loans->count() }}</div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Total to Release</span>
                    <i class="feather-dollar-sign stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">₱{{ number_format($totalAmount, 0) }}</div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Longest Waiting</span>
                    <i class="feather-clock stat-icon-v2"></i>
                </div>
                @php $longest = (int) $loans->max('waiting_days'); @endphp
                <div class="stat-value-v2">{{ $longest }} {{ $longest === 1 ? 'day' : 'days' }}</div>
            </div>
        </div>

        @if ($loans->count() === 0)
            <div class="rs-card">
                <div class="empty-state-v2" style="display:block;">
                    <i class="feather-send"></i>
                    <h6>No loans waiting for release</h6>
                    <p>Approved loans will show up here until the proof of release is sent.</p>
                </div>
            </div>
        @else
            <div class="rs-card rs-table-card">
                <!-- Filter toolbar -->
                <div class="filter-bar-v2">
                    <div class="search-wrap-v2">
                        <i class="feather-search"></i>
                        <input type="text" id="filterSearchInput"
                            placeholder="Search borrowers by name, email, or Loan ID..." onkeyup="filterOngoingLoans()">
                    </div>
                    {{-- Buwan ng approval --}}
                    <select class="sort-select-v2" id="monthFilter" onchange="filterOngoingLoans()" aria-label="Filter by month">
                        <option value="">All Months</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                    <select class="sort-select-v2" id="sortFilter" onchange="sortOngoingLoans()">
                        <option value="oldest" {{ $sort !== 'newest' ? 'selected' : '' }}>Longest Waiting First</option>
                        <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
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
                                <th data-sort="name" onclick="olSortByColumn('name')">Borrower</th>
                                <th data-sort="loanAmount" onclick="olSortByColumn('loanAmount')">Loan Amount</th>
                                <th data-sort="term" onclick="olSortByColumn('term')">Term</th>
                                <th data-sort="approved" onclick="olSortByColumn('approved')">Approved On</th>
                                <th data-sort="waiting" onclick="olSortByColumn('waiting')">Waiting</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($loans as $l)
                                @php
                                    $approvedStamp = $l->approved_at ? \Carbon\Carbon::parse($l->approved_at)->timestamp : 0;
                                    $approvedMonth = $l->approved_at ? \Carbon\Carbon::parse($l->approved_at)->format('m') : '';
                                    $waitClass = $l->waiting_days >= 3 ? 'is-late' : ($l->waiting_days >= 1 ? 'is-warn' : 'is-new');
                                    $detailUrl = route('admin.waiting-for-release-detail', $l->uuid);
                                @endphp
                                <tr data-search="{{ strtolower($l->name . ' ' . $l->email . ' ' . $l->loan_code) }}"
                                    data-name="{{ $l->name }}"
                                    data-loan-amount="{{ $l->loan_amount }}"
                                    data-term="{{ $l->loan_term }}"
                                    data-approved="{{ $approvedStamp }}"
                                    data-waiting="{{ $l->waiting_days }}"
                                    data-applied="{{ $approvedStamp }}"
                                    data-month="{{ $approvedMonth }}"
                                    data-href="{{ $detailUrl }}"
                                    onclick="olGoToRow(event, this)"
                                    class="rs-clickable-row">
                                    <td>
                                        <div class="borrower-cell-v2">
                                            <div>
                                                <div class="borrower-name-v2">{{ $l->name }}</div>
                                                <div class="borrower-id-v2">{{ $l->loan_code }}</div>
                                                <div class="borrower-email-v2">{{ $l->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="loan-amount-v2">₱{{ number_format($l->loan_amount, 2) }}</td>
                                    <td class="loan-meta-v2">{{ $l->loan_term }} {{ $l->loan_term == 1 ? 'month' : 'months' }}</td>
                                    <td class="loan-meta-v2">
                                        {{ $l->approved_at ? \Carbon\Carbon::parse($l->approved_at)->format('M d, Y') : '—' }}
                                    </td>
                                    <td>
                                        <span class="wait-pill {{ $waitClass }}">
                                            @if ($l->waiting_days === 0)
                                                Today
                                            @else
                                                {{ $l->waiting_days }} {{ $l->waiting_days == 1 ? 'day' : 'days' }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ $detailUrl }}" class="btn-view-v2">
                                            <i class="feather-send"></i> Review &amp; Release
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination bar --}}
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
                            Showing 1&ndash;{{ min(10, $loans->count()) }} of {{ $loans->count() }}
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