@extends('admin.layout')

@section('title', 'Penalty Tracking')

@section('page-styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/penalty-tracking.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Penalty Tracking</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Penalty Tracking</li>
            </ul>
        </div>
    </div>

    <div class="main-content pt-page">

        <div class="pt-summary-row">
            <div class="pt-summary-card">
                <div class="pt-summary-card-head">
                    <span class="pt-summary-label">Total Penalties</span>
                    <i class="bi bi-clipboard-data pt-summary-icon"></i>
                </div>
                <div class="pt-summary-value">{{ $totalPenaltiesCount ?? 0 }}</div>
            </div>
            <div class="pt-summary-card">
                <div class="pt-summary-card-head">
                    <span class="pt-summary-label">Outstanding Amount</span>
                    <i class="bi bi-exclamation-circle pt-summary-icon"></i>
                </div>
                <div class="pt-summary-value">₱{{ number_format($outstandingAmount ?? 0, 2) }}</div>
            </div>
            <div class="pt-summary-card">
                <div class="pt-summary-card-head">
                    <span class="pt-summary-label">Total Paid</span>
                    <i class="bi bi-check-circle pt-summary-icon"></i>
                </div>
                <div class="pt-summary-value">₱{{ number_format($totalPaidAmount ?? 0, 2) }}</div>
            </div>
            <div class="pt-summary-card">
                <div class="pt-summary-card-head">
                    <span class="pt-summary-label">Total Waived</span>
                    <i class="bi bi-slash-circle pt-summary-icon"></i>
                </div>
                <div class="pt-summary-value">₱{{ number_format($totalWaivedAmount ?? 0, 2) }}</div>
            </div>
        </div>

        <div class="pt-table-card">
            <div class="pt-filter-bar">
                <div class="pt-search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="ptSearchInput" class="pt-search-input" placeholder="Search borrower...">
                </div>

                <select id="ptMonthFilter" class="pt-select" aria-label="Filter by month">
                    <option value="">All Months</option>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endfor
                </select>

                <select id="ptStatusFilter" class="pt-select" aria-label="Filter by status">
                    <option value="">All Status</option>
                    <option value="unpaid" selected>Unpaid</option>
                    <option value="paid">Paid</option>
                    <option value="waived">Waived</option>
                    <option value="mixed">Mixed</option>
                </select>

                <select id="ptSortSelect" class="pt-select" aria-label="Sort penalties">
                    <option value="days_desc">Most days late</option>
                    <option value="days_asc">Fewest days late</option>
                    <option value="penalty_desc">Highest penalty</option>
                    <option value="penalty_asc">Lowest penalty</option>
                </select>
            </div>

            <div class="pt-empty-state" id="ptEmptyState">
                <i class="bi bi-inbox"></i>
                <h6>No penalty records found</h6>
                <p>Try adjusting your search or filters.</p>
            </div>

            <div class="pt-table-scroll" id="ptTableWrap">
                <table class="pt-table" id="ptTable">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Installments w/ Penalty</th>
                            <th>Worst Days Late</th>
                            <th>Total Penalty</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ptTableBody">
                        @foreach ($loans as $loan)
                            @php
                                $status = $loan['status'];
                                $days = (int) $loan['worst_days_late'];
                                $fullName = trim(($loan['first_name'] ?? '') . ' ' . ($loan['last_name'] ?? ''));

                                if ($days >= 15) {
                                    $daysClass = 'is-red';
                                } elseif ($days >= 7) {
                                    $daysClass = 'is-orange';
                                } else {
                                    $daysClass = 'is-blue';
                                }

                                $months = collect($loan['items'])
                                    ->map(fn($i) => \Carbon\Carbon::parse($i->due_date)->month)
                                    ->unique()
                                    ->implode(',');

                                $detailUrl = route('admin.penalty-tracking-detail', [$loan['loan_uuid'], $loan['main_installment']]);
                            @endphp

                            <tr class="pt-tr" data-loan-id="{{ $loan['application_id'] }}"
                                data-href="{{ $detailUrl }}"
                                data-borrower="{{ strtolower($fullName) }}" data-months="{{ $months }}"
                                data-status="{{ $status }}" data-days="{{ $days }}"
                                data-penalty="{{ $loan['total_penalty'] }}">

                                <td>
                                    <div class="pt-borrower-name">{{ $fullName !== '' ? $fullName : '—' }}</div>
                                    <div class="pt-borrower-sub">Loan #{{ str_pad($loan['application_id'], 5, '0', STR_PAD_LEFT) }}</div>
                                </td>

                                <td class="pt-cell-meta">{{ $loan['installments_with_penalty'] }} of {{ $loan['total_installments'] }}</td>

                                <td>
                                    @if ($status === 'unpaid')
                                        <span class="pt-days {{ $daysClass }}">{{ $days }} {{ $days === 1 ? 'day' : 'days' }}</span>
                                    @else
                                        <span class="pt-dash">—</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="pt-amount-value">₱{{ number_format($loan['total_penalty'], 2) }}</div>
                                    @if ($loan['unpaid_penalty'] > 0)
                                        <div class="pt-amount-fee">Unpaid ₱{{ number_format($loan['unpaid_penalty'], 2) }}</div>
                                    @endif
                                </td>

                                <td>
                                    <span class="pt-status is-{{ $status }}">{{ ucfirst($status) }}</span>
                                </td>

                                <td class="pt-cell-actions text-end">
                                    <a href="{{ $detailUrl }}" class="pt-btn-view">
                                        <i class="bi bi-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-table-footer" id="ptFooter">
                <div class="pt-footer-left">
                    <div class="pt-rows">
                        <label for="ptRowsPerPage" class="pt-rows-label">Rows per page</label>
                        <select id="ptRowsPerPage" class="pt-rows-select">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <span class="pt-footer-info" id="ptShowingText"></span>
                </div>
                <div class="pt-pagination" id="ptPagination"></div>
                <div></div>
            </div>
        </div>

    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/penalty-tracking.js') }}?v={{ time() }}"></script>
@endsection