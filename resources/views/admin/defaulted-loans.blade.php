@extends('admin.layout')

@section('title', 'Defaulted Loans')

@section('page-styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/defaulted-loans.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Defaulted Loans</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.ongoing-loans') }}">Active Loans</a></li>
                <li class="breadcrumb-item">Defaulted</li>
            </ul>
        </div>
    </div>

    <div class="main-content dl-page">

        <div class="dl-summary-row">
            <div class="dl-summary-card">
                <div class="dl-summary-card-head">
                    <span class="dl-summary-label">Defaulted Loans</span>
                    <i class="bi bi-exclamation-octagon dl-summary-icon"></i>
                </div>
                <div class="dl-summary-value">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="dl-summary-card">
                <div class="dl-summary-card-head">
                    <span class="dl-summary-label">Total Outstanding</span>
                    <i class="bi bi-cash-stack dl-summary-icon"></i>
                </div>
                <div class="dl-summary-value">₱{{ number_format($stats['total_outstanding'] ?? 0, 2) }}</div>
            </div>
            <div class="dl-summary-card">
                <div class="dl-summary-card-head">
                    <span class="dl-summary-label">Unpaid Penalties</span>
                    <i class="bi bi-exclamation-triangle dl-summary-icon"></i>
                </div>
                <div class="dl-summary-value">₱{{ number_format($stats['total_penalties'] ?? 0, 2) }}</div>
            </div>
        </div>

        <div class="dl-table-card">
            <div class="dl-filter-bar">
                <div class="dl-search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="dlSearchInput" class="dl-search-input" placeholder="Search borrower or loan ID...">
                </div>

                <select id="dlDaysFilter" class="dl-select" aria-label="Filter by days overdue">
                    <option value="all">All Days Overdue</option>
                    <option value="90">90&ndash;99 days</option>
                    <option value="100">100+ days</option>
                </select>

                <select id="dlSortSelect" class="dl-select" aria-label="Sort defaulted loans"
                    data-base-url="{{ route('admin.defaulted-loans') }}">
                    <option value="most_late" {{ $sort === 'most_late' ? 'selected' : '' }}>Most days late</option>
                    <option value="highest_balance" {{ $sort === 'highest_balance' ? 'selected' : '' }}>Highest balance</option>
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest applied</option>
                    <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest applied</option>
                </select>
            </div>

            @if (count($defaultedLoans) === 0)
                <div class="dl-empty-state" style="display:block;">
                    <i class="bi bi-check-circle is-good"></i>
                    <h6>No defaulted loans</h6>
                    <p>No loans have crossed the {{ $defaultDaysLate }}-day default threshold.</p>
                </div>
            @else
                <div class="dl-empty-state" id="dlEmptyState">
                    <i class="bi bi-inbox"></i>
                    <h6>No defaulted loans found</h6>
                    <p>Try adjusting your search or filters.</p>
                </div>

                <div class="dl-table-scroll" id="dlTableWrap">
                    <table class="dl-table" id="dlTable">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>Original Loan</th>
                                <th>Outstanding Balance</th>
                                <th>Days Overdue</th>
                                <th>Unpaid Penalty</th>
                                <th>Last Payment</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="dlTableBody">
                            @foreach ($defaultedLoans as $loan)
                                @php
                                    $loanCode = 'LN-' . str_pad($loan['application_id'], 5, '0', STR_PAD_LEFT);
                                    $detailUrl = route('admin.defaulted-loan-detail', $loan['application_id']);
                                    $days = (int) $loan['days_late'];
                                @endphp
                                <tr class="dl-tr"
                                    data-href="{{ $detailUrl }}"
                                    data-search="{{ strtolower($loan['name'] . ' ' . $loanCode . ' ' . $loan['email']) }}"
                                    data-days="{{ $days }}">

                                    <td>
                                        <div class="dl-borrower-name">{{ $loan['name'] }}</div>
                                        <div class="dl-borrower-sub">{{ $loanCode }}</div>
                                        <div class="dl-borrower-sub">{{ $loan['email'] }}</div>
                                    </td>

                                    <td class="dl-amount-value">₱{{ number_format($loan['loan_amount'], 2) }}</td>

                                    <td class="dl-outstanding">₱{{ number_format($loan['outstanding_amount'], 2) }}</td>

                                    <td>
                                        <span class="dl-days">{{ $days }} {{ $days === 1 ? 'day' : 'days' }}</span>
                                    </td>

                                    <td class="dl-cell-meta">₱{{ number_format($loan['unpaid_penalties'] ?? 0, 2) }}</td>

                                    <td class="dl-cell-meta">
                                        {{ $loan['last_payment_at'] ? \Carbon\Carbon::parse($loan['last_payment_at'])->format('M d, Y') : 'No payments made' }}
                                    </td>

                                    <td>
                                        <span class="dl-status">Defaulted</span>
                                    </td>

                                    <td class="dl-cell-actions text-end">
                                        <a href="{{ $detailUrl }}" class="dl-btn-view">
                                            <i class="bi bi-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="dl-table-footer" id="dlFooter">
                    <div class="dl-footer-left">
                        <div class="dl-rows">
                            <label for="dlRowsPerPage" class="dl-rows-label">Rows per page</label>
                            <select id="dlRowsPerPage" class="dl-rows-select">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <span class="dl-footer-info" id="dlShowingText"></span>
                    </div>
                    <div class="dl-pagination" id="dlPagination"></div>
                    <div></div>
                </div>
            @endif
        </div>

    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/defaulted-loans.js') }}?v={{ time() }}"></script>
@endsection