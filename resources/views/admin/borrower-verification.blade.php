@extends('admin.layout')

@section('title', 'Borrower Verification')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/borrower-verification.css') }}">
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">
                    Borrower Verification
                    @if(($viewStatus ?? 'pending') !== 'pending')
                        &mdash; {{ ucfirst(str_replace('_', ' ', $viewStatus)) }}
                    @endif
                </h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Borrower Verification</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

        @if(($viewStatus ?? 'pending') === 'pending')
        {{-- ================= PENDING VIEW (Pending / Reviewed tabs) ================= --}}

        <div class="d-flex gap-2 mb-4">
            <button class="tab-btn active" onclick="showTab('pending', this)">
                Pending
                @if($pendingCount > 0)
                    <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
                @endif
            </button>
            <button class="tab-btn" onclick="showTab('reviewed', this)">
                Reviewed
                <span class="badge bg-secondary ms-1">{{ count($reviewedDetails) }}</span>
            </button>
        </div>

        <!-- PENDING TAB — table, same pattern as Reviewed/Filtered
             (search + status filter + rows-per-page + pagination). -->
        <div id="tab-pending">
            @if(count($pendingDetails) === 0)
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="feather-check-circle" style="font-size:48px; color:#28a745;"></i>
                    <h5 class="mt-3 fw-bold">All caught up!</h5>
                    <p class="text-muted">No pending personal details to review.</p>
                </div>
            </div>
            @else
            <div class="reviewed-card">

                <div class="reviewed-toolbar">
                    <div class="search-bar-wrap reviewed-search-wrap">
                        <i class="feather-search"></i>
                        <input type="text" id="pendingSearchInput"
                               placeholder="Search borrower name or email..."
                               onkeyup="filterPendingTable()">
                    </div>
                    <select id="pendingStatusFilter" class="reviewed-decision-filter" onchange="filterPendingTable()">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="on_hold">On Hold</option>
                    </select>
                    <div class="reviewed-rpp-wrap">
                        <label for="pendingRowsPerPage" class="reviewed-rpp-label">Rows per page</label>
                        <select id="pendingRowsPerPage" class="reviewed-rpp-select" onchange="pendingChangeRowsPerPage(this)">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table reviewed-table mb-0" id="pendingDetailsTable">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>Contact</th>
                                <th>Detail ID</th>
                                <th>Status</th>
                                <th>Approvals</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingDetails as $d)
                            <tr data-search="{{ strtolower($d['last_name'] . ' ' . $d['first_name'] . ' ' . $d['email']) }}" data-status="{{ $d['status'] }}">
                                <td>
                                    <div class="rt-name">{{ $d['last_name'] }}, {{ $d['first_name'] }} {{ $d['middle_name'] }}</div>
                                    <div class="rt-email">{{ $d['email'] }}</div>
                                    @if($d['already_reviewed'])
                                    <div class="rt-email text-success"><i class="feather-check-circle me-1"></i>Reviewed by you</div>
                                    @endif
                                </td>
                                <td class="rt-date">{{ $d['cellphone_num'] ?? '—' }}</td>
                                <td class="rt-date">#{{ $d['detail_id'] }}</td>
                                <td>
                                    @if($d['status'] === 'on_hold')
                                        <span class="badge badge-onhold px-3 py-2">On Hold</span>
                                    @else
                                        <span class="badge badge-pending px-3 py-2">Pending</span>
                                    @endif
                                </td>
                                <td class="rt-date">
                                    {{ $d['approved_count'] }}/3 approved &middot; {{ $d['rejected_count'] }}/3 rejected
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.verification-detail', $d['detail_id']) }}?from=verification" class="btn btn-sm btn-light">
                                        <i class="feather-eye me-1"></i> View Full Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="pendingEmptyState" class="reviewed-empty" style="display:none;">
                        No pending borrowers match your search.
                    </div>
                </div>

                <div class="reviewed-pagination" id="pendingPagination">
                    <span class="reviewed-pagination-info" id="pendingPaginationInfo">
                        Showing 1&ndash;{{ min(10, count($pendingDetails)) }} of {{ count($pendingDetails) }}
                    </span>
                    <div class="reviewed-pagination-controls">
                        <button type="button" class="btn-page" id="pendingPrevPage" onclick="pendingGoToPage(-1)">
                            <i class="feather-chevron-left"></i> Prev
                        </button>
                        <span class="reviewed-page-indicator" id="pendingPageIndicator">Page 1 of 1</span>
                        <button type="button" class="btn-page" id="pendingNextPage" onclick="pendingGoToPage(1)">
                            Next <i class="feather-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- REVIEWED TAB -->
        <div id="tab-reviewed" style="display:none;">
            @if(count($reviewedDetails) === 0)
            <div class="card">
                <div class="card-body text-center py-5">
                    <p class="text-muted">No reviewed details yet.</p>
                </div>
            </div>
            @else
            <div class="reviewed-card">

                {{-- Search + decision filter + rows-per-page --}}
                <div class="reviewed-toolbar">
                    <div class="search-bar-wrap reviewed-search-wrap">
                        <i class="feather-search"></i>
                        <input type="text" id="reviewedSearchInput"
                               placeholder="Search borrower name or email..."
                               onkeyup="filterReviewedTable()">
                    </div>
                    <select id="reviewedDecisionFilter" class="reviewed-decision-filter" onchange="filterReviewedTable()">
                        <option value="">All Decisions</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="on_hold">On Hold</option>
                    </select>
                    <div class="reviewed-rpp-wrap">
                        <label for="reviewedRowsPerPage" class="reviewed-rpp-label">Rows per page</label>
                        <select id="reviewedRowsPerPage" class="reviewed-rpp-select" onchange="reviewedChangeRowsPerPage(this)">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table reviewed-table mb-0" id="reviewedTable">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>Decision</th>
                                <th>Reviewed At</th>
                                <th>Remarks</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reviewedDetails as $r)
                            @php
                                $decisionKey = in_array($r->decision, ['approved', 'rejected']) ? $r->decision : 'on_hold';
                            @endphp
                            <tr data-search="{{ strtolower($r->last_name . ' ' . $r->first_name . ' ' . $r->email) }}" data-decision="{{ $decisionKey }}">
                                <td>
                                    <div class="rt-name">{{ $r->last_name }}, {{ $r->first_name }}</div>
                                    <div class="rt-email">{{ $r->email ?? '—' }}</div>
                                </td>
                                <td>
                                    @if($r->decision === 'approved')
                                        <span class="badge badge-approved px-3 py-2">Approved</span>
                                    @elseif($r->decision === 'rejected')
                                        <span class="badge badge-rejected px-3 py-2">Rejected</span>
                                    @else
                                        <span class="badge badge-onhold px-3 py-2">On Hold</span>
                                    @endif
                                </td>
                                <td class="rt-date">
                                    {{ $r->reviewed_at ? \Carbon\Carbon::parse($r->reviewed_at)->format('M d, Y h:i A') : '—' }}
                                </td>
                                <td class="rt-remarks" title="{{ $r->remarks }}">
                                    {{ $r->remarks ? \Illuminate\Support\Str::limit($r->remarks, 70) : 'No remarks' }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.verification-detail', $r->detail_id) }}?from=verification" class="btn btn-sm btn-light">
                                        <i class="feather-eye me-1"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="reviewedEmptyState" class="reviewed-empty" style="display:none;">
                        No reviewed applications match your search.
                    </div>
                </div>

                {{-- Pagination bar: info text (left) + Prev/Next controls (right).
                     JS (reviewedRenderPage / reviewedUpdatePaginationControls) fills
                     these in on load and after every search/filter/rows-per-page change. --}}
                <div class="reviewed-pagination" id="reviewedPagination">
                    <span class="reviewed-pagination-info" id="reviewedPaginationInfo">
                        Showing 1&ndash;{{ min(10, count($reviewedDetails)) }} of {{ count($reviewedDetails) }}
                    </span>
                    <div class="reviewed-pagination-controls">
                        <button type="button" class="btn-page" id="reviewedPrevPage" onclick="reviewedGoToPage(-1)">
                            <i class="feather-chevron-left"></i> Prev
                        </button>
                        <span class="reviewed-page-indicator" id="reviewedPageIndicator">Page 1 of 1</span>
                        <button type="button" class="btn-page" id="reviewedNextPage" onclick="reviewedGoToPage(1)">
                            Next <i class="feather-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>

        @else
        {{-- ================= FILTERED VIEW: Approved / Rejected / On Hold ================= --}}
        {{-- Same structure as the Reviewed tab: toolbar (search + rows-per-page)
             / table / pagination, all inside one .reviewed-card.
             Columns: Borrower | User ID | Contact | Status | Action --}}

            @if(count($filteredDetails) === 0)
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="feather-inbox" style="font-size:48px;color:#8a9bb0;"></i>
                    <h5 class="mt-3 fw-bold">No borrowers yet</h5>
                </div>
            </div>
            @else
            <div class="reviewed-card">

                <div class="reviewed-toolbar">
                    <div class="search-bar-wrap reviewed-search-wrap">
                        <i class="feather-search"></i>
                        <input type="text" id="filteredSearchInput"
                               placeholder="Search by name or email..."
                               onkeyup="filterDetailsTable()">
                    </div>
                    <div class="reviewed-rpp-wrap">
                        <label for="filteredRowsPerPage" class="reviewed-rpp-label">Rows per page</label>
                        <select id="filteredRowsPerPage" class="reviewed-rpp-select" onchange="filteredChangeRowsPerPage(this)">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table reviewed-table mb-0" id="filteredDetailsTable">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>User ID</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filteredDetails as $d)
                            <tr data-search="{{ strtolower($d->last_name . ' ' . $d->first_name . ' ' . $d->email) }}">
                                <td>
                                    <div class="rt-name">{{ $d->last_name }}, {{ $d->first_name }} {{ $d->middle_name }}</div>
                                    <div class="rt-email">{{ $d->email }}</div>
                                </td>
                                <td class="rt-date">BRW-{{ str_pad($d->user_id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td class="rt-date">{{ $d->cellphone_num ?? '—' }}</td>
                                <td>
                                    @if($viewStatus === 'approved')
                                        <span class="badge badge-approved px-3 py-2">Approved</span>
                                    @elseif($viewStatus === 'rejected')
                                        <span class="badge badge-rejected px-3 py-2">Rejected</span>
                                    @else
                                        <span class="badge badge-onhold px-3 py-2">On Hold</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.verification-detail', $d->detail_id) }}?from=verification" class="btn btn-sm btn-light">
                                        <i class="feather-eye me-1"></i> View Full Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="filteredEmptyState" class="reviewed-empty" style="display:none;">
                        No borrowers match your search.
                    </div>
                </div>

                {{-- Pagination bar --}}
                <div class="reviewed-pagination" id="filteredPagination">
                    <span class="reviewed-pagination-info" id="filteredPaginationInfo">
                        Showing 1&ndash;{{ min(10, count($filteredDetails)) }} of {{ count($filteredDetails) }}
                    </span>
                    <div class="reviewed-pagination-controls">
                        <button type="button" class="btn-page" id="filteredPrevPage" onclick="filteredGoToPage(-1)">
                            <i class="feather-chevron-left"></i> Prev
                        </button>
                        <span class="reviewed-page-indicator" id="filteredPageIndicator">Page 1 of 1</span>
                        <button type="button" class="btn-page" id="filteredNextPage" onclick="filteredGoToPage(1)">
                            Next <i class="feather-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endif

        @endif

    </div>

    <!-- Shared confirmation modal for Approve/Reject on the pending list — modal-modern style -->
    <div class="modal fade modal-modern" id="cardConfirmModal" tabindex="-1" aria-labelledby="cardConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success" id="cardConfirmIconWrap">
                        <i id="cardConfirmIcon" style="font-size:30px;"></i>
                    </div>
                    <p id="cardConfirmText" class="modal-title-text"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-modal-primary btn-approve" id="cardConfirmBtn"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Result modals — one of these shows after a decision is recorded,
         depending on the borrower's status after that decision. -->

    <!-- Generic "decision recorded" -->
    <div class="modal fade modal-modern" id="decisionSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success">
                        <i class="feather-check-circle"></i>
                    </div>
                    <p class="modal-title-text">Decision submitted</p>
                    <p class="modal-message-text">
                        Your decision has been recorded successfully. The application will remain under review until all three administrators have submitted their decisions.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mixed decisions -->
    <div class="modal fade modal-modern" id="onHoldModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-warning">
                        <i class="feather-alert-triangle"></i>
                    </div>
                    <p class="modal-title-text">Application on hold</p>
                    <p class="modal-message-text">
                        The administrators have reached different decisions. The application has been placed on hold for further discussion.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-warning" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fully approved by all 3 -->
    <div class="modal fade modal-modern" id="finalApprovalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success">
                        <i class="feather-check-circle"></i>
                    </div>
                    <p class="modal-title-text">Application approved</p>
                    <p class="modal-message-text">
                        The borrower's application has been approved by all administrators.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    @if(session('success'))
    <script>
        // Landed here via redirect right after a decision was submitted on the
        // detail page — show the correct result modal based on decisionStatus,
        // same mapping as the AJAX flow on this page's pending cards.
        document.addEventListener('DOMContentLoaded', function () {
            const status = @json(session('decisionStatus'));
            let modalId = 'decisionSuccessModal';
            if (status === 'approved') modalId = 'finalApprovalModal';
            else if (status === 'on_hold') modalId = 'onHoldModal';

            const modalEl = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
            modal.show();
        });
    </script>
    @endif
    <script src="{{ asset('functions/borrower-verification.js') }}"></script>
@endsection