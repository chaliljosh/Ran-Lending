@extends('admin.layout')

@section('title', 'Loan Applications')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-applications.css') }}">
@endsection

@section('content')


    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">
                    Loan Applications
                    @if(($viewStatus ?? 'pending') !== 'pending')
                        &mdash; {{ ucfirst(str_replace('_', ' ', $viewStatus)) }}
                    @endif
                </h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Loan Applications</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

        @if(($viewStatus ?? 'pending') === 'pending')
        {{-- ================= PENDING VIEW (Pending / Reviewed tabs) ================= --}}

        <div class="la-tabs">
            <button class="tab-btn active" onclick="showLoanTab('pending', this)">
                Pending
                @if($pendingLoanCount > 0)
                    <span class="tab-count">{{ $pendingLoanCount }}</span>
                @endif
            </button>
            <button class="tab-btn" onclick="showLoanTab('reviewed', this)">
                Reviewed
                <span class="tab-count is-muted">{{ count($reviewedApplications) }}</span>
            </button>
        </div>

        <!-- PENDING TAB -->
        <div id="loantab-pending">
            @if(count($pendingApplications) === 0)
            <div class="la-empty-large">
                <span class="la-empty-icon-lg">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </span>
                <p class="la-empty-title">All caught up!</p>
                <p class="la-empty-desc">No pending loan applications to review.</p>
            </div>
            @else
            <div class="reviewed-card">

                <div class="reviewed-toolbar">
                    <div class="search-bar-wrap reviewed-search-wrap">
                        <i class="feather-search"></i>
                        <input type="text" id="pendingSearchInput"
                               placeholder="Search borrower name or email..."
                               onkeyup="filterLoanPending()">
                    </div>
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
                    <table class="reviewed-table mb-0" id="pendingLoanTable">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th class="text-end">Loan Amount</th>
                                <th>Term</th>
                                <th>Purpose</th>
                                <th>Applied Date</th>
                                <th>Votes</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="loanPendingList">
                            @foreach($pendingApplications as $app)
                            <tr data-search="{{ strtolower($app['last_name'] . ' ' . $app['first_name'] . ' ' . $app['email']) }}">
                                <td>
                                    <div class="rt-name">{{ $app['last_name'] }}, {{ $app['first_name'] }}</div>
                                    <div class="rt-email">{{ $app['email'] }}</div>
                                </td>
                                <td class="text-end rt-date">&#8369;{{ number_format($app['loan_amount'], 2) }}</td>
                                <td class="rt-date">{{ $app['loan_term'] }} months</td>
                                <td class="rt-remarks" title="{{ ucfirst(str_replace('_', ' ', $app['loan_purpose'])) }}">
                                    {{ ucfirst(str_replace('_', ' ', $app['loan_purpose'])) }}
                                </td>
                                <td class="rt-date">{{ date('M d, Y', strtotime($app['applied_at'])) }}</td>
                                <td>
                                    @if($app['approved_count'] > 0 || $app['rejected_count'] > 0)
                                    <div class="progress-mini-table">
                                        <span class="pm-item is-approved">{{ $app['approved_count'] }}/3</span>
                                        <span class="pm-item is-rejected">{{ $app['rejected_count'] }}/3</span>
                                    </div>
                                    @else
                                    <span class="rt-date">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    @if($app['status'] === 'on_hold')
                                        <span class="status-subtle is-onhold"><span class="dot"></span>On Hold</span>
                                    @else
                                        <span class="status-subtle"><span class="dot"></span>Pending</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.loan-application-detail', $app['application_id']) }}" class="btn-light">
                                        <i class="feather-eye me-1"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="pendingEmptyState" class="reviewed-empty" style="display:none;">
                        No applications match your search.
                    </div>
                </div>

                <div class="reviewed-pagination" id="pendingPagination">
                    <span class="reviewed-pagination-info" id="pendingPaginationInfo">
                        Showing 1&ndash;{{ min(10, count($pendingApplications)) }} of {{ count($pendingApplications) }}
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
        <div id="loantab-reviewed" style="display:none;">
            @if(count($reviewedApplications) === 0)
            <div class="la-empty-large">
                <span class="la-empty-icon-lg la-empty-icon-lg-muted">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </span>
                <p class="la-empty-desc">No reviewed applications yet.</p>
            </div>
            @else
            <div class="reviewed-card">

                <div class="reviewed-toolbar">
                    <div class="search-bar-wrap reviewed-search-wrap">
                        <i class="feather-search"></i>
                        <input type="text" id="loanReviewedSearchInput"
                               placeholder="Search borrower name or email..."
                               onkeyup="filterLoanReviewed()">
                    </div>
                    <select id="loanReviewedDecisionFilter" class="reviewed-decision-filter" onchange="filterLoanReviewed()">
                        <option value="">All Decisions</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <div class="reviewed-rpp-wrap">
                        <label for="loanReviewedRowsPerPage" class="reviewed-rpp-label">Rows per page</label>
                        <select id="loanReviewedRowsPerPage" class="reviewed-rpp-select" onchange="loanReviewedChangeRowsPerPage(this)">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="reviewed-table mb-0" id="loanReviewedTable">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th class="text-end">Loan Amount</th>
                                <th>Purpose</th>
                                <th>Term</th>
                                <th>Applied Date</th>
                                <th>Decision</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="loanReviewedList">
                          @foreach($reviewedApplications as $r)
                          @php
                              $decisionLower = strtolower($r->decision ?? '');
                          @endphp
                          <tr data-search="{{ strtolower($r->last_name . ' ' . $r->first_name . ' ' . $r->email) }}" data-decision="{{ $decisionLower }}">
                              <td>
                                  <div class="rt-name">{{ $r->last_name }}, {{ $r->first_name }}</div>
                                  <div class="rt-email">{{ $r->email }}</div>
                              </td>
                              <td class="text-end rt-date">&#8369;{{ number_format($r->loan_amount, 0) }}</td>
                              <td class="rt-remarks" title="{{ ucfirst(str_replace('_', ' ', $r->loan_purpose ?? '—')) }}">
                                  {{ ucfirst(str_replace('_', ' ', $r->loan_purpose ?? '—')) }}
                              </td>
                              <td class="rt-date">{{ $r->loan_term ?? '—' }} months</td>
                              <td class="rt-date">{{ $r->applied_at ? \Carbon\Carbon::parse($r->applied_at)->format('M d, Y') : '—' }}</td>
                              <td>
                                  <span class="badge {{ $decisionLower === 'approved' ? 'badge-approved' : 'badge-rejected' }} px-3 py-2">
                                      {{ ucfirst($r->decision) }}
                                  </span>
                              </td>
                              <td class="text-end">
                                  <a href="{{ route('admin.loan-application-detail', $r->application_id) }}" class="btn-light">
                                      <i class="feather-eye me-1"></i> View Details
                                  </a>
                              </td>
                          </tr>
                          @endforeach
                        </tbody>
                    </table>
                    <div id="loanReviewedEmptyState" class="reviewed-empty" style="display:none;">
                        No reviewed applications match your search.
                    </div>
                </div>

                <div class="reviewed-pagination" id="loanReviewedPagination">
                    <span class="reviewed-pagination-info" id="loanReviewedPaginationInfo">
                        Showing 1&ndash;{{ min(10, count($reviewedApplications)) }} of {{ count($reviewedApplications) }}
                    </span>
                    <div class="reviewed-pagination-controls">
                        <button type="button" class="btn-page" id="loanReviewedPrevPage" onclick="loanReviewedGoToPage(-1)">
                            <i class="feather-chevron-left"></i> Prev
                        </button>
                        <span class="reviewed-page-indicator" id="loanReviewedPageIndicator">Page 1 of 1</span>
                        <button type="button" class="btn-page" id="loanReviewedNextPage" onclick="loanReviewedGoToPage(1)">
                            Next <i class="feather-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>

        @else
        {{-- ================= FILTERED VIEW: Approved / Rejected / On Hold ================= --}}

            @if(count($filteredApplications) === 0)
                @php
                    $emptyIconMuted = true;
                    if ($viewStatus === 'approved') {
                        $emptyTitle = 'No approved applications yet';
                        $emptyDesc = 'Applications approved by all administrators will appear here.';
                        $emptyIconMuted = false;
                    } elseif ($viewStatus === 'rejected') {
                        $emptyTitle = 'No rejected applications';
                        $emptyDesc = 'Applications rejected by all administrators will appear here.';
                    } else {
                        $emptyTitle = 'No applications on hold';
                        $emptyDesc = 'Applications with mixed admin decisions will appear here.';
                    }
                @endphp
                <div class="la-empty-large">
                    <span class="la-empty-icon-lg {{ $emptyIconMuted ? 'la-empty-icon-lg-muted' : '' }}">
                        @if($viewStatus === 'approved')
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        @elseif($viewStatus === 'rejected')
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                        @else
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        @endif
                    </span>
                    <p class="la-empty-title">{{ $emptyTitle }}</p>
                    <p class="la-empty-desc">{{ $emptyDesc }}</p>
                </div>
            @else
            <div class="reviewed-card">

                <div class="reviewed-toolbar">
                    <div class="search-bar-wrap reviewed-search-wrap">
                        <i class="feather-search"></i>
                        <input type="text" id="filteredSearchInput"
                               placeholder="Search by name or email..."
                               onkeyup="filterLoanFiltered()">
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
                    <table class="reviewed-table mb-0" id="filteredLoanTable">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>Application ID</th>
                                <th class="text-end">Loan Amount</th>
                                <th>Term</th>
                                <th>Purpose</th>
                                <th>Applied</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="filteredLoanList">
                            @foreach($filteredApplications as $app)
                            <tr data-search="{{ strtolower($app->last_name . ' ' . $app->first_name . ' ' . $app->email) }}">
                                <td>
                                    <div class="rt-name">{{ $app->last_name }}, {{ $app->first_name }}</div>
                                    <div class="rt-email">{{ $app->email }}</div>
                                </td>
                                <td class="rt-date">#{{ $app->application_id }}</td>
                                <td class="text-end rt-date">&#8369;{{ number_format($app->loan_amount, 0) }}</td>
                                <td class="rt-date">{{ $app->loan_term }} months</td>
                                <td class="rt-remarks" title="{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}">
                                    {{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}
                                </td>
                                <td class="rt-date">{{ date('M d, Y', strtotime($app->applied_at)) }}</td>
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
                                    <a href="{{ route('admin.loan-application-detail', $app->application_id) }}" class="btn-light">
                                        <i class="feather-eye me-1"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="filteredEmptyState" class="reviewed-empty" style="display:none;">
                        No applications match your search.
                    </div>
                </div>

                <div class="reviewed-pagination" id="filteredPagination">
                    <span class="reviewed-pagination-info" id="filteredPaginationInfo">
                        Showing 1&ndash;{{ min(10, count($filteredApplications)) }} of {{ count($filteredApplications) }}
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

    <!-- Result modals — one of these shows after a decision is recorded -->
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

    <div class="modal fade modal-modern" id="finalApprovalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-success">
                        <i class="feather-check-circle"></i>
                    </div>
                    <p class="modal-title-text">Application approved</p>
                    <p class="modal-message-text">
                        The loan application has been approved by all administrators.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-modern" id="finalRejectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-icon-circle icon-danger">
                        <i class="feather-x-circle"></i>
                    </div>
                    <p class="modal-title-text">Application rejected</p>
                    <p class="modal-message-text">
                        The loan application has been rejected by all administrators.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-primary btn-ok-warning" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('page-scripts')
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const status = @json(session('decisionStatus'));
            let modalId = 'decisionSuccessModal';
            if (status === 'approved') modalId = 'finalApprovalModal';
            else if (status === 'rejected') modalId = 'finalRejectionModal';
            else if (status === 'on_hold') modalId = 'onHoldModal';

            const modalEl = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
            modal.show();
        });
    </script>
    @endif

    <script src="{{ asset('functions/loan-applications.js') }}"></script>
@endsection