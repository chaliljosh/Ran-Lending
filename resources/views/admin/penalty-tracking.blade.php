@extends('admin.layout')

@section('title', 'Penalty Tracking')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/penalty-tracking.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
    // Temporary — pinapakita nito sa screen mismo ang anumang JS error,
    // dahil naka-block ang DevTools sa laptop na ito. Tanggalin na lang
    // ito pagkatapos ma-ayos yung bug.
    window.addEventListener('error', function (e) {
        var box = document.createElement('div');
        box.style.cssText = 'position:fixed;bottom:0;left:0;right:0;background:#dc2626;color:#fff;padding:14px;font-family:monospace;font-size:13px;z-index:999999;white-space:pre-wrap;';
        box.textContent = 'JS ERROR: ' + e.message + '  (file: ' + e.filename + ', line: ' + e.lineno + ')';
        document.body.appendChild(box);
    });
    </script>
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Penalty Tracking</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Penalty Tracking</li>
            </ul>
        </div>
    </div>

    <div class="main-content pt-page">
        {{--
            DATA CONTRACT expected from AdminController::penaltyTracking():
            $unpaid / $paid / $waived -> collections of penalty rows, each exposing:
            id, first_name, last_name, application_id, installment_no, due_date,
            days_late, amount_due, penalty_amount, status, waived_reason (nullable),
            late_fee_periods, rate, breakdown[], payment_status, loan_id_display

            $totalPenaltiesCount, $outstandingAmount, $totalPaidAmount, $totalWaivedAmount
        --}}
        @php
            $allPenalties = collect($unpaid ?? [])->concat($paid ?? [])->concat($waived ?? []);
        @endphp
         <div class="pt-summary-row">
            <div class="pt-summary-card">
                <div class="pt-summary-icon is-total"><i class="bi bi-clipboard-data"></i></div>
                <div>
                    <div class="pt-summary-label">Total Penalties</div>
                    <div class="pt-summary-value">{{ $totalPenaltiesCount ?? $allPenalties->count() }}</div>
                </div>
            </div>
            <div class="pt-summary-card">
                <div class="pt-summary-icon is-outstanding"><i class="bi bi-exclamation-circle"></i></div>
                <div>
                    <div class="pt-summary-label">Outstanding Amount</div>
                    <div class="pt-summary-value">₱{{ number_format($outstandingAmount ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="pt-summary-card">
                <div class="pt-summary-icon is-paid"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="pt-summary-label">Total Paid</div>
                    <div class="pt-summary-value">₱{{ number_format($totalPaidAmount ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="pt-summary-card">
                <div class="pt-summary-icon is-waived"><i class="bi bi-slash-circle"></i></div>
                <div>
                    <div class="pt-summary-label">Total Waived</div>
                    <div class="pt-summary-value">₱{{ number_format($totalWaivedAmount ?? 0, 2) }}</div>
                </div>
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
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                @endfor
            </select>
            <select id="ptStatusFilter" class="pt-select">
                <option value="">All Status</option>
                <option value="unpaid">Unpaid</option>
                <option value="paid">Paid</option>
                <option value="waived">Waived</option>
            </select>
            <select id="ptSortSelect" class="pt-select">
                <option value="days_desc">Sort: Most days late</option>
                <option value="days_asc">Sort: Fewest days late</option>
                <option value="amount_desc">Sort: Highest amount due</option>
                <option value="amount_asc">Sort: Lowest amount due</option>
                <option value="due_desc">Sort: Due date (newest)</option>
                <option value="due_asc">Sort: Due date (oldest)</option>
            </select>
            <div class="pt-toolbar-spacer"></div>
        </div>

        {{-- ============ TABLE ============ --}}
        <div class="pt-table-card">
            <div class="pt-table-scroll">
                <table class="pt-table" id="ptTable">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Installment</th>
                            <th>Due Date</th>
                            <th>Days Late</th>
                            <th>Amount Due</th>
                            <th>Status</th>
                            <th class="is-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ptTableBody">
                        @forelse($allPenalties as $p)
                            @php
                                $status = $p->status ?? 'unpaid';
                                $days = (int) ($p->days_late ?? 0);
                                $due = \Carbon\Carbon::parse($p->due_date);
                                $fullName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                                $initials = strtoupper(substr($p->first_name ?? '?', 0, 1) . substr($p->last_name ?? '', 0, 1));

                                if ($status === 'paid') {
                                    $pillClass = 'is-green';
                                } elseif ($status === 'waived') {
                                    $pillClass = 'is-gray';
                                } elseif ($days >= 15) {
                                    $pillClass = 'is-red';
                                } elseif ($days >= 7) {
                                    $pillClass = 'is-orange';
                                } else {
                                    $pillClass = 'is-blue';
                                }
                            @endphp
                            <tr class="pt-tr"
                                data-penalty-id="{{ $p->id }}"
                                data-view-target="#viewModal{{ $p->id }}"
                                data-borrower="{{ strtolower($fullName) }}"
                                data-month="{{ $due->month }}"
                                data-status="{{ $status }}"
                                data-days="{{ $days }}"
                                data-amount="{{ $p->amount_due }}"
                                data-due="{{ $due->format('Y-m-d') }}">

                                <td>
                                    <div class="pt-cell-borrower">
                                       
                                        <div>
                                            <div class="pt-borrower-name">{{ $fullName !== '' ? $fullName : '—' }}</div>
                                            <div class="pt-borrower-sub">Loan #{{ str_pad($p->application_id ?? 0, 5, '0', STR_PAD_LEFT) }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="pt-cell-installment">#{{ $p->installment_no }}</td>

                                <td class="pt-cell-due">
                                    <div>
                                        <div class="pt-due-primary">{{ $due->format('M d, Y') }}</div>
                                        <div class="pt-due-secondary">{{ $due->format('l') }}</div>
                                    </div>
                                </td>

                                <td class="pt-cell-days">
                                    <span class="pt-pill {{ $pillClass }}">
                                        @if($status === 'paid') Paid
                                        @elseif($status === 'waived') Waived
                                        @else {{ $days }} Days
                                        @endif
                                    </span>
                                </td>

                                <td class="pt-cell-amount">
                                    <div class="pt-amount-value">₱{{ number_format($p->amount_due, 2) }}</div>
                                    <div class="pt-amount-fee">Fee ₱{{ number_format($p->penalty_amount, 2) }}</div>
                                </td>

                                <td class="pt-cell-status">
                                    <span class="pt-status-badge is-{{ $status }}">{{ strtoupper($status) }}</span>
                                </td>

                                {{-- ===== DIRECT ACTION BUTTONS ===== --}}
                                <td class="pt-cell-actions">
                                    <div class="pt-actions-group">
                                     <a href="{{ route('admin.penalty-tracking-detail', $p->id) }}"
   class="pt-action-btn is-view"
   title="View Details">
    <i class="bi bi-eye me-1"></i> View Details
</a>

                                        @if($status === 'unpaid')
                                            <button type="button"
                                                    class="pt-action-btn is-paid"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#paidConfirmModal{{ $p->id }}"
                                                    title="Mark as Paid">
                                                <i class="bi bi-check-lg"></i> Paid
                                            </button>
                                            <button type="button"
                                                    class="pt-action-btn is-waive"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#waiveModal{{ $p->id }}"
                                                    title="Waive Penalty">
                                                <i class="bi bi-slash-circle"></i> Waive
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                        @endforelse
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

{{-- ============================================================
     MODALS — pinush sa dulo ng <body> gamit ang @stack('modals')
     sa layout, para hindi sila ma-trap sa loob ng .nxl-container
     (ito rin ang dating dahilan kung bakit sila nasa labas ng
     <main> sa standalone na bersyon dati).
============================================================ --}}
@push('modals')

    {{-- ---------- SUCCESS MODAL (Mark Paid / Waive confirmation) ---------- --}}
    <div class="modal fade pt-modal" id="successModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-4 text-center">
                    <div class="pt-modal-icon is-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="pt-modal-title">Success</div>
                    <div class="pt-modal-subtitle mb-0" id="successModalMessage">{{ session('success') ?? '' }}</div>
                </div>
            </div>
        </div>
    </div>

    @foreach($allPenalties as $p)
        @php $status = $p->status ?? 'unpaid'; @endphp

        @if($status === 'unpaid')
        {{-- ---------- MARK AS PAID — CONFIRMATION MODAL ---------- --}}
        <div class="modal fade pt-modal" id="paidConfirmModal{{ $p->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header justify-content-end">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="pt-modal-icon is-confirm"><i class="bi bi-question-circle"></i></div>
                        <div class="pt-modal-title">Mark as Paid?</div>
                        <div class="pt-modal-subtitle">Are you sure you want to mark this installment as paid?</div>

                        <div class="pt-info-card">
                            <div class="pt-info-item">
                                <div class="pt-info-label">Borrower</div>
                                <div class="pt-info-value">{{ $p->first_name ?? '—' }} {{ $p->last_name ?? '' }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Installment</div>
                                <div class="pt-info-value">#{{ $p->installment_no }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Amount Due</div>
                                <div class="pt-info-value">₱{{ number_format($p->amount_due, 2) }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Late Fee</div>
                                <div class="pt-info-value is-late-fee">₱{{ number_format($p->penalty_amount, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="pt-btn-cancel" data-bs-dismiss="modal">No, Cancel</button>
                        <button type="button"
                                class="pt-btn-confirm-paid"
                                data-mark-paid-url="{{ route('admin.penalty-mark-paid', $p->id) }}"
                                data-penalty-id="{{ $p->id }}">
                            <i class="bi bi-check-lg"></i> Yes, Mark as Paid
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade pt-modal" id="waiveModal{{ $p->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST"
                          action="{{ route('admin.penalty-waive', $p->id) }}"
                          id="waiveForm{{ $p->id }}"
                          class="pt-waive-form"
                          data-penalty-id="{{ $p->id }}">
                        @csrf
                        <div class="modal-header justify-content-end">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="pt-modal-icon"><i class="bi bi-exclamation-triangle"></i></div>
                            <div class="pt-modal-title">Waive Penalty</div>
                            <div class="pt-modal-subtitle">This action will permanently waive the selected penalty.</div>

                            <div class="pt-info-card">
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Borrower</div>
                                    <div class="pt-info-value">{{ $p->first_name ?? '—' }} {{ $p->last_name ?? '' }}</div>
                                </div>
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Installment</div>
                                    <div class="pt-info-value">#{{ $p->installment_no }}</div>
                                </div>
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Late Fee</div>
                                    <div class="pt-info-value is-late-fee">₱{{ number_format($p->penalty_amount, 2) }}</div>
                                </div>
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Amount Due</div>
                                    <div class="pt-info-value">₱{{ number_format($p->amount_due, 2) }}</div>
                                </div>
                                <div class="pt-info-item is-full">
                                    <div class="pt-info-label">Days Late</div>
                                    <div class="pt-info-value">{{ $p->days_late }} Days</div>
                                </div>
                            </div>

                            <label class="pt-form-label">Reason for Waiver <span class="req">*</span></label>
                            <textarea name="waived_reason" class="pt-textarea" rows="4" placeholder="Enter the reason for waiving this penalty..." required></textarea>

                            <div class="pt-warning-line">
                                <i class="bi bi-exclamation-triangle-fill"></i> This action cannot be undone.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="pt-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="pt-btn-confirm-waive"><i class="bi bi-check-lg"></i> Confirm Waive</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- ================================================================
             VIEW DETAILS MODAL — Penalty Details
             Buong impormasyon ng ISANG penalty record: borrower/loan info,
             penalty summary (Days Late / Points / Rate / Total), Penalty
             Breakdown history table, at status section (Pending -> mga
             action button; Paid -> resolved date; Waived -> waived info).
             Reused lang ang existing .pt-* classes — walang bagong design
             system, sundan lang yung look ng ibang modal sa page na ito.
        ================================================================= --}}
        <div class="modal fade pt-modal" id="viewModal{{ $p->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header justify-content-end">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pb-4">
                        <div class="pt-modal-title">Penalty Details</div>
                        <div class="pt-modal-subtitle">Penalty information for this installment</div>

                        {{-- ---- Borrower / Loan Information ---- --}}
                        <div class="pt-detail-section-title">Borrower &amp; Loan Information</div>
                        <div class="pt-info-card">
                            <div class="pt-info-item">
                                <div class="pt-info-label">Borrower</div>
                                <div class="pt-info-value">{{ $p->first_name ?? '—' }} {{ $p->last_name ?? '' }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Loan ID</div>
                                <div class="pt-info-value">{{ $p->loan_id_display ?? ('LN-' . str_pad($p->application_id ?? 0, 5, '0', STR_PAD_LEFT)) }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Installment</div>
                                <div class="pt-info-value">#{{ $p->installment_no }}</div>
                            </div>
                            <div class="pt-info-item">
                                <div class="pt-info-label">Due Date</div>
                                <div class="pt-info-value">{{ \Carbon\Carbon::parse($p->due_date)->format('F d, Y') }}</div>
                            </div>
                            <div class="pt-info-item is-full">
                                <div class="pt-info-label">Payment Status</div>
                                <div class="pt-info-value">{{ $p->payment_status ?? 'Overdue' }}</div>
                            </div>
                        </div>

                        {{-- ---- Penalty Summary ---- --}}
                        <div class="pt-detail-section-title">Penalty Summary</div>
                        <div class="pt-penalty-summary">
                            <div class="pt-penalty-summary-row">
                                <div class="pt-penalty-summary-item">
                                    <div class="pt-info-label">Days Late</div>
                                    <div class="pt-info-value">{{ $p->days_late }} days</div>
                                </div>
                                <div class="pt-penalty-summary-item">
                                    <div class="pt-info-label">Penalty Points</div>
                                    <div class="pt-info-value">{{ $p->late_fee_periods }} {{ Str::plural('point', $p->late_fee_periods) }}</div>
                                </div>
                                <div class="pt-penalty-summary-item">
                                    <div class="pt-info-label">Penalty Rate</div>
                                    <div class="pt-info-value">₱{{ number_format($p->rate ?? 0, 2) }} per point</div>
                                </div>
                            </div>
                            <div class="pt-penalty-total-row">
                                <span class="pt-penalty-total-label">Total Penalty</span>
                                <span class="pt-penalty-total-value">₱{{ number_format($p->penalty_amount, 2) }}</span>
                            </div>
                            <div class="pt-calc-line">
                                {{ $p->late_fee_periods }} {{ Str::plural('penalty point', $p->late_fee_periods) }} × ₱{{ number_format($p->rate ?? 0, 2) }} = ₱{{ number_format($p->penalty_amount, 2) }}
                            </div>
                        </div>

                        {{-- ---- Penalty Breakdown ---- --}}
                        @if(!empty($p->breakdown))
                        <div class="pt-detail-section-title">Penalty Breakdown</div>
                        <div class="pt-breakdown-table-wrap">
                            <table class="pt-breakdown-table">
                                <thead>
                                    <tr>
                                        <th>Date Applied</th>
                                        <th>Reason</th>
                                        <th>Days Overdue</th>
                                        <th>Points</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($p->breakdown as $row)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                                        <td>{{ ucfirst($row['reason']) }}</td>
                                        <td>{{ $row['days_overdue'] }} days</td>
                                        <td>+{{ $row['points'] }}</td>
                                        <td class="text-end">₱{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4">Total Penalty</td>
                                        <td class="text-end">₱{{ number_format($p->penalty_amount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @endif

                        {{-- ---- Penalty Status ----
                             Laging may .pt-info-card dito (kahit Pending pa
                             lang), dahil ito ang tinitignan ng existing
                             updateRowToWaived() JS function (unang
                             .pt-info-value nito ang binabago papuntang
                             "Waived" — kaya kailangan laging present ito
                             para hindi masira ang live update pagkatapos
                             mag-waive/mag-mark-paid nang walang reload). --}}
                        <div class="pt-detail-section-title">Penalty Status</div>
                        <div class="pt-info-card pt-status-card">
                            <div class="pt-info-item">
                                <div class="pt-info-label">Status</div>
                                <div class="pt-info-value">{{ ucfirst($status) }}</div>
                            </div>
                            @if($status === 'paid')
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Resolved Date</div>
                                    <div class="pt-info-value">{{ \Carbon\Carbon::parse($p->updated_at)->format('M d, Y') }}</div>
                                </div>
                            @elseif($status === 'waived')
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Waived Date</div>
                                    <div class="pt-info-value">{{ \Carbon\Carbon::parse($p->updated_at)->format('M d, Y') }}</div>
                                </div>
                                <div class="pt-info-item">
                                    <div class="pt-info-label">Waived By</div>
                                    <div class="pt-info-value">{{ $p->waived_by_name ?? '—' }}</div>
                                </div>
                                @if(!empty($p->waived_reason))
                                <div class="pt-info-item is-full is-waived-reason">
                                    <div class="pt-info-label">Waived Reason</div>
                                    <div class="pt-info-value">{{ $p->waived_reason }}</div>
                                </div>
                                @endif
                            @endif
                        </div>

                        {{-- ---- Action buttons — Pending lang ---- --}}
                        @if($status === 'unpaid')
                        <div class="pt-view-actions">
                            <button type="button"
                                    class="pt-btn-confirm-paid"
                                    data-bs-toggle="modal"
                                    data-bs-target="#paidConfirmModal{{ $p->id }}"
                                    data-bs-dismiss="modal">
                                <i class="bi bi-check-lg"></i> Mark as Paid
                            </button>
                            <button type="button"
                                    class="pt-btn-confirm-waive"
                                    data-bs-toggle="modal"
                                    data-bs-target="#waiveModal{{ $p->id }}"
                                    data-bs-dismiss="modal">
                                <i class="bi bi-slash-circle"></i> Waive Penalty
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@endpush

@section('page-scripts')
    <script src="{{ asset('functions/penalty-tracking.js') }}?v=5"></script>
@endsection