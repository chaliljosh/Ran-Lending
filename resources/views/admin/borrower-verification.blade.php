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
                    @if (($viewStatus ?? 'pending') !== 'pending')
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

        @if (($viewStatus ?? 'pending') === 'pending')
            {{-- ================= PENDING VIEW ================= --}}
            {{-- Tabs removed — "Reviewed" is now its own sidebar link
             (see the 'reviewed' branch below), same pattern as
             Approved / Rejected / On Hold. --}}

            {{-- ---- Summary cards (matched 1:1 to Loan Applications) ---- --}}
            <div class="la-stats-row">
                <div class="la-stat-card">
                    <div class="la-stat-top">
                        <span class="la-stat-label">Total Pending</span>
                        <i class="feather-clock la-stat-icon"></i>
                    </div>
                    <div class="la-stat-value">{{ count($pendingDetails) }}</div>
                    <div class="la-stat-sub">borrowers waiting for verification</div>
                </div>
                <div class="la-stat-card">
                    <div class="la-stat-top">
                        <span class="la-stat-label">New Applications</span>
                        <i class="feather-file-plus la-stat-icon"></i>
                    </div>
                    <div class="la-stat-value">{{ $newApplicationsCount ?? 0 }}</div>
                    <div class="la-stat-sub">submitted within 24 hours</div>
                </div>
                <div class="la-stat-card {{ ($needsAttentionCount ?? 0) > 0 ? 'is-attention' : '' }}">
                    <div class="la-stat-top">
                        <span class="la-stat-label">Needs Attention</span>
                        <i class="feather-alert-triangle la-stat-icon"></i>
                    </div>
                    <div class="la-stat-value">{{ $needsAttentionCount ?? 0 }}</div>
                    <div class="la-stat-sub">waiting 3+ days</div>
                </div>
                <div class="la-stat-card">
                    <div class="la-stat-top">
                        <span class="la-stat-label">Total Approved</span>
                        <i class="feather-check-circle la-stat-icon"></i>
                    </div>
                    <div class="la-stat-value">{{ $totalApprovedCount ?? 0 }}</div>
                    <div class="la-stat-sub">this month</div>
                </div>
            </div>

            @if (count($pendingDetails) === 0)
                <div class="la-empty-large">
                    <span class="la-empty-icon-lg">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <p class="la-empty-title">All caught up!</p>
                    <p class="la-empty-desc">No pending personal details to review.</p>
                </div>
            @else
                <div class="reviewed-card">

                    <div class="reviewed-toolbar">
                        <div class="search-bar-wrap reviewed-search-wrap">
                            <i class="feather-search"></i>
                            <input type="text" id="pendingSearchInput" placeholder="Search borrower name or email..."
                                onkeyup="filterPendingTable()">
                        </div>
                        <div class="reviewed-toolbar-meta">
                            <select id="pendingStatusFilter" class="reviewed-decision-filter" onchange="filterPendingTable()">
                                <option value="all" selected>All Pending</option>
                                <option value="new">New Applications</option>
                                <option value="attention">Needs Attention (3+ days)</option>
                                <option value="on_hold">On Hold</option>
                            </select>
                            <select id="pendingSortSelect" class="reviewed-decision-filter" onchange="filterPendingTable()">
                                <option value="applied_desc" selected>Newest First</option>
                                <option value="applied_asc">Oldest First</option>
                                <option value="waiting_desc">Waiting Time (Longest)</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table reviewed-table mb-0" id="pendingDetailsTable">
                            <thead>
                                <tr>
                                    <th class="sortable-th">Borrower</th>
                                    <th class="sortable-th">Contact</th>
                                    <th class="sortable-th">Detail ID</th>
                                    <th class="sortable-th">Applied Date</th>
                                    <th class="sortable-th">Status</th>
                                    <th class="sortable-th">Approvals</th>
                                    <th class="text-end sortable-th">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pendingDetails as $d)
                                    @php
                                        $pendingRowUrl = route('admin.verification-detail', $d['uuid']) . '?from=verification';
                                    @endphp
                                    <tr
                                        data-search="{{ strtolower($d['last_name'] . ' ' . $d['first_name'] . ' ' . $d['email']) }}"
                                        data-status="{{ $d['status'] }}"
                                        data-waiting="{{ $d['waiting_days'] }}"
                                        data-applied="{{ \Carbon\Carbon::parse($d['created_at'])->toIso8601String() }}"
                                        data-new="{{ \Carbon\Carbon::parse($d['created_at'])->gt(now()->subDay()) ? '1' : '0' }}"
                                        data-attention="{{ $d['waiting_days'] >= 3 ? '1' : '0' }}"
                                        class="clickable-row {{ $d['waiting_days'] >= 3 ? 'is-attention-row' : '' }}"
                                        onclick="window.location.href='{{ $pendingRowUrl }}'">
                                        <td>
                                            <div class="rt-name">{{ $d['last_name'] }}, {{ $d['first_name'] }}
                                                {{ $d['middle_name'] }}</div>
                                            <div class="rt-email">{{ $d['email'] }}</div>
                                            @if ($d['already_reviewed'])
                                                <div class="rt-email text-success"><i
                                                        class="feather-check-circle me-1"></i>Reviewed by you</div>
                                            @endif
                                        </td>
                                        <td class="rt-date">{{ $d['cellphone_num'] ?? '—' }}</td>
                                <td class="rt-detailid">#{{ $d['display_id'] }}</td>
                                        <td class="rt-date">{{ \Carbon\Carbon::parse($d['created_at'])->format('M d, Y') }}</td>
                                        <td>
                                            @if ($d['status'] === 'on_hold')
                                                <span class="badge badge-onhold">On Hold</span>
                                            @else
                                                <span class="badge badge-pending">Pending</span>
                                            @endif
                                        </td>
                                        <td class="rt-date">
                                            {{ $d['approved_count'] }}/3 approved &middot; {{ $d['rejected_count'] }}/3
                                            rejected
                                        </td>
                                        <td class="text-end">
                                          <a href="{{ $pendingRowUrl }}"
                                                class="action-link-btn" onclick="event.stopPropagation()">View Details</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div id="pendingEmptyState" class="reviewed-empty" style="display:none;">
                            No pending borrowers match your search.
                        </div>
                    </div>

                    <div class="reviewed-pagination pending-pagination-row" id="pendingPagination">
                        <div class="pending-rpp-wrap">
                            <span class="reviewed-count-label">Showing</span>
                            <select id="pendingRowsPerPage" class="reviewed-rpp-select"
                                onchange="pendingChangeRowsPerPage(this)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <div class="reviewed-pagination-controls" id="pendingPaginationControls"></div>
                        <span class="reviewed-count-text" id="pendingPaginationInfo">Showing
                            {{ count($pendingDetails) }} of {{ count($pendingDetails) }}
                            borrowers</span>
                    </div>
                </div>
            @endif
        @elseif($viewStatus === 'reviewed')
            {{-- ================= REVIEWED VIEW ================= --}}
            {{-- Previously a JS-toggled tab inside the Pending page;
             now its own page/sidebar link. Controller should pass
             $viewStatus = 'reviewed' and $reviewedDetails here. --}}

            @if (count($reviewedDetails) === 0)
                <div class="card">
                    <div class="card-body text-center py-5">
                        <p class="text-muted">No reviewed details yet.</p>
                    </div>
                </div>
            @else
                <div class="reviewed-card">

                    <div class="reviewed-toolbar">
                        <div class="search-bar-wrap reviewed-search-wrap">
                            <i class="feather-search"></i>
                            <input type="text" id="reviewedSearchInput" placeholder="Search borrower name or email..."
                                onkeyup="filterReviewedTable()">
                        </div>
                        <select id="reviewedDecisionFilter" class="reviewed-decision-filter"
                            onchange="filterReviewedTable()">
                            <option value="">All Decisions</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="on_hold">On Hold</option>
                        </select>
                        <div class="reviewed-toolbar-meta">
                            <span class="reviewed-count-label">Showing</span>
                            <select id="reviewedRowsPerPage" class="reviewed-rpp-select"
                                onchange="reviewedChangeRowsPerPage(this)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <span class="reviewed-count-text" id="reviewedPaginationInfo">of {{ count($reviewedDetails) }}
                                results</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table reviewed-table mb-0" id="reviewedTable">
                            <thead>
                                <tr>
                                    <th class="sortable-th">Borrower</th>
                                    <th class="sortable-th">Decision</th>
                                    <th class="sortable-th">Reviewed At</th>
                                    <th class="sortable-th">Remarks</th>
                                    <th class="text-end sortable-th">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reviewedDetails as $r)
                                    @php
                                        $decisionKey = in_array($r->decision, ['approved', 'rejected'])
                                            ? $r->decision
                                            : 'on_hold';
                                        $reviewedRowUrl = route('admin.verification-detail', $r->uuid) . '?from=verification';
                                    @endphp
                                    <tr data-search="{{ strtolower($r->last_name . ' ' . $r->first_name . ' ' . $r->email) }}"
                                        data-decision="{{ $decisionKey }}"
                                        class="clickable-row"
                                        onclick="window.location.href='{{ $reviewedRowUrl }}'">
                                        <td>
                                            <div class="rt-name">{{ $r->last_name }}, {{ $r->first_name }}</div>
                                            <div class="rt-email">{{ $r->email ?? '—' }}</div>
                                        </td>
                                        <td>
                                            @if ($r->decision === 'approved')
                                                <span class="badge badge-approved">Approved</span>
                                            @elseif($r->decision === 'rejected')
                                                <span class="badge badge-rejected">Rejected</span>
                                            @else
                                                <span class="badge badge-onhold">On Hold</span>
                                            @endif
                                        </td>
                                        <td class="rt-date">
                                            {{ $r->reviewed_at ? \Carbon\Carbon::parse($r->reviewed_at)->format('M d, Y h:i A') : '—' }}
                                        </td>
                                        <td class="rt-remarks" title="{{ $r->remarks }}">
                                            {{ $r->remarks ? \Illuminate\Support\Str::limit($r->remarks, 70) : 'No remarks' }}
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ $reviewedRowUrl }}"
                                                class="action-link-btn" onclick="event.stopPropagation()">View Details</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div id="reviewedEmptyState" class="reviewed-empty" style="display:none;">
                            No reviewed applications match your search.
                        </div>
                    </div>

                    <div class="reviewed-pagination" id="reviewedPagination">
                        <div class="reviewed-pagination-controls" id="reviewedPaginationControls"></div>
                    </div>
                </div>
            @endif
        @else
            {{-- ================= FILTERED VIEW: Approved / Rejected / On Hold ================= --}}
            {{-- Columns: Borrower | User ID | Contact | Status | Action --}}

            @if (count($filteredDetails) === 0)
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
                            <input type="text" id="filteredSearchInput" placeholder="Search by name or email..."
                                onkeyup="filterDetailsTable()">
                        </div>
                        <div class="reviewed-toolbar-meta">
                            <span class="reviewed-count-label">Showing</span>
                            <select id="filteredRowsPerPage" class="reviewed-rpp-select"
                                onchange="filteredChangeRowsPerPage(this)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <span class="reviewed-count-text" id="filteredPaginationInfo">of
                                {{ count($filteredDetails) }} results</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table reviewed-table mb-0" id="filteredDetailsTable">
                            <thead>
                                <tr>
                                    <th class="sortable-th">Borrower</th>
                                    <th class="sortable-th">User ID</th>
                                    <th class="sortable-th">Contact</th>
                                    <th class="sortable-th">Status</th>
                                    <th class="text-end sortable-th">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filteredDetails as $d)
                                    @php
                                        $filteredRowUrl = route('admin.verification-detail', $d->uuid) . '?from=verification';
                                    @endphp
                                    <tr
                                        data-search="{{ strtolower($d->last_name . ' ' . $d->first_name . ' ' . $d->email) }}"
                                        class="clickable-row"
                                        onclick="window.location.href='{{ $filteredRowUrl }}'">
                                        <td>
                                            <div class="rt-name">{{ $d->last_name }}, {{ $d->first_name }}
                                                {{ $d->middle_name }}</div>
                                            <div class="rt-email">{{ $d->email }}</div>
                                        </td>
                                        <td class="rt-date">BRW-{{ str_pad($d->user_id, 4, '0', STR_PAD_LEFT) }}</td>
                                        <td class="rt-date">{{ $d->cellphone_num ?? '—' }}</td>
                                        <td>
                                            @if ($viewStatus === 'approved')
                                                <span class="badge badge-approved">Approved</span>
                                            @elseif($viewStatus === 'rejected')
                                                <span class="badge badge-rejected">Rejected</span>
                                            @else
                                                <span class="badge badge-onhold">On Hold</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ $filteredRowUrl }}"
                                                class="action-link-btn" onclick="event.stopPropagation()">View Details</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div id="filteredEmptyState" class="reviewed-empty" style="display:none;">
                            No borrowers match your search.
                        </div>
                    </div>

                    <div class="reviewed-pagination" id="filteredPagination">
                        <div class="reviewed-pagination-controls" id="filteredPaginationControls"></div>
                    </div>
                </div>
            @endif

        @endif

    </div>

    <!-- Shared confirmation modal for Approve/Reject on the pending list — modal-modern style -->
    <div class="modal fade modal-modern" id="cardConfirmModal" tabindex="-1" aria-labelledby="cardConfirmModalLabel"
        aria-hidden="true">
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
                        Your decision has been recorded successfully. The application will remain under review until all
                        three administrators have submitted their decisions.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-success"
                        data-bs-dismiss="modal">OK</button>
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
                        The administrators have reached different decisions. The application has been placed on hold for
                        further discussion.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-warning"
                        data-bs-dismiss="modal">OK</button>
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
                    <button type="button" class="btn btn-modal-primary btn-ok-success"
                        data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const status = @json(session('decisionStatus'));

                let title = 'Decision submitted';
                let message =
                    'Your decision has been recorded successfully. The verification will remain under review until all three administrators have submitted their decisions.';
                let type = 'success';

                if (status === 'approved') {
                    title = 'Borrower approved';
                    message = 'The borrower verification has been approved by all administrators.';
                    type = 'success';
                } else if (status === 'rejected') {
                    title = 'Borrower rejected';
                    message = 'The borrower verification has been rejected by all administrators.';
                    type = 'danger';
                } else if (status === 'on_hold') {
                    title = 'Verification on hold';
                    message =
                        'The administrators have reached different decisions. The verification has been placed on hold for further discussion.';
                    type = 'warning';
                }

                window.showAdminToast(type, title, message);
            });
        </script>
    @endif
    <script src="{{ asset('functions/borrower-verification.js') }}"></script>
@endsection