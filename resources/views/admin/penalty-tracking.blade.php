@extends('admin.layout')

@section('title', 'Penalty Tracking')

@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/penalty-tracking.css') }}?v={{ time() }}" />
    {{-- styles para sa grouped rows --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/penalty-tracking-grouped.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        // Temporary — pinapakita nito sa screen mismo ang anumang JS error,
        // dahil naka-block ang DevTools sa laptop na ito. Tanggalin na lang
        // ito pagkatapos ma-ayos yung bug.
        window.addEventListener('error', function(e) {
            var box = document.createElement('div');
            box.style.cssText =
                'position:fixed;bottom:0;left:0;right:0;background:#dc2626;color:#fff;padding:14px;font-family:monospace;font-size:13px;z-index:999999;white-space:pre-wrap;';
            box.textContent = 'JS ERROR: ' + e.message + '  (file: ' + e.filename + ', line: ' + e.lineno + ')';
            document.body.appendChild(box);
        });
    </script>
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
        {{--
            DATA galing sa AdminController::penaltyTracking():
            $loans -> isang item kada loan, may: application_id, loan_id_display,
                first_name, last_name, installments_with_penalty, total_installments,
                worst_days_late, total_penalty, unpaid_penalty, status,
                loan_uuid, main_installment, items[] (bawat penalty ng loan)
            $totalPenaltiesCount, $outstandingAmount, $totalPaidAmount, $totalWaivedAmount
        --}}

        {{-- ============ SUMMARY CARDS (walang nagbago) ============ --}}
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

        {{-- ============ TOOLBAR ============ --}}
        <div class="pt-toolbar">
            <div class="pt-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="ptSearchInput" class="pt-search-input" placeholder="Search borrower...">
            </div>
            <select id="ptMonthFilter" class="pt-select">
                <option value="">All Months</option>
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                @endfor
            </select>
            {{-- Unpaid ang default, para yung may problema lang ang unang makita --}}
            <select id="ptStatusFilter" class="pt-select">
                <option value="">All Status</option>
                <option value="unpaid" selected>Unpaid</option>
                <option value="paid">Paid</option>
                <option value="waived">Waived</option>
                <option value="mixed">Mixed</option>
            </select>
            <select id="ptSortSelect" class="pt-select">
                <option value="days_desc">Sort: Most days late</option>
                <option value="days_asc">Sort: Fewest days late</option>
                <option value="penalty_desc">Sort: Highest penalty</option>
                <option value="penalty_asc">Sort: Lowest penalty</option>
            </select>
            <div class="pt-toolbar-spacer"></div>
        </div>

        {{-- ============ TABLE (1 row kada loan) ============ --}}
        <div class="pt-table-card">
            <div class="pt-table-scroll">
                <table class="pt-table" id="ptTable">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Installments w/ Penalty</th>
                            <th>Worst Days Late</th>
                            <th>Total Penalty</th>
                            <th>Status</th>
                            <th class="is-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ptTableBody">
                        @foreach ($loans as $loan)
                            @php
                                $status = $loan['status'];
                                $days = (int) $loan['worst_days_late'];
                                $fullName = trim(($loan['first_name'] ?? '') . ' ' . ($loan['last_name'] ?? ''));

                                // Kulay ng "days late" pill (para sa unpaid lang)
                                if ($days >= 15) {
                                    $pillClass = 'is-red';
                                } elseif ($days >= 7) {
                                    $pillClass = 'is-orange';
                                } else {
                                    $pillClass = 'is-blue';
                                }

                                // Mga buwan ng due date ng lahat ng penalty ng loan (para sa month filter)
                                $months = collect($loan['items'])
                                    ->map(fn($i) => \Carbon\Carbon::parse($i->due_date)->month)
                                    ->unique()
                                    ->implode(',');
                            @endphp

                            <tr class="pt-tr" data-loan-id="{{ $loan['application_id'] }}"
                                data-href="{{ route('admin.penalty-tracking-detail', [$loan['loan_uuid'], $loan['main_installment']]) }}"
                                data-borrower="{{ strtolower($fullName) }}" data-months="{{ $months }}"
                                data-status="{{ $status }}" data-days="{{ $days }}"
                                data-penalty="{{ $loan['total_penalty'] }}">

                                <td>
                                    <div class="pt-cell-borrower">
                                        <div>
                                            <div class="pt-borrower-name">{{ $fullName !== '' ? $fullName : '—' }}</div>
                                            <div class="pt-borrower-sub">Loan
                                                #{{ str_pad($loan['application_id'], 5, '0', STR_PAD_LEFT) }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="pt-cell-installment">
                                    <div class="pt-installments-cell">
                                        <span>{{ $loan['installments_with_penalty'] }} of {{ $loan['total_installments'] }}</span>
                                    </div>
                                </td>

                                <td class="pt-cell-days">
                                    @if ($status === 'unpaid')
                                        <span class="pt-pill {{ $pillClass }}">{{ $days }} Days</span>
                                    @else
                                        <span class="pt-dash">—</span>
                                    @endif
                                </td>

                                <td class="pt-cell-amount">
                                    <div>
                                        <div class="pt-amount-value">₱{{ number_format($loan['total_penalty'], 2) }}</div>
                                        @if ($loan['unpaid_penalty'] > 0)
                                            <div class="pt-amount-fee">Unpaid ₱{{ number_format($loan['unpaid_penalty'], 2) }}</div>
                                        @endif
                                    </div>
                                </td>

                                <td class="pt-cell-status">
                                    <span class="pt-status-badge is-{{ $status }}">{{ strtoupper($status) }}</span>
                                </td>

                                {{-- View Details lang ang action (view-only na ang page na ito) --}}
                                <td class="pt-cell-actions">
                                    <div class="pt-actions-group">
                                        <a href="{{ route('admin.penalty-tracking-detail', [$loan['loan_uuid'], $loan['main_installment']]) }}"
                                            class="pt-action-btn is-view" title="View Details">
                                            <i class="bi bi-eye"></i>
                                            <span>View Details</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="pt-empty-state" id="ptEmptyState" style="display:none;">
                    <div class="pt-empty-icon"><i class="bi bi-inbox"></i></div>
                    <div class="pt-empty-title">No penalty records found.</div>
                    <div class="pt-empty-desc">Try adjusting your search or filters.</div>
                </div>
            </div>

            <div class="pt-table-footer">
                <div class="pt-footer-info">
                    <span id="ptShowingText">Showing 0–0 of 0 records</span>
                    <span>
                        Rows per page:
                        <select id="ptRowsPerPage" class="pt-rows-select">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </span>
                </div>
                <div class="pt-pagination" id="ptPagination"></div>
            </div>
        </div>

    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/penalty-tracking.js') }}?v=8"></script>
@endsection