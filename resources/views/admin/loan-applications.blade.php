@extends('admin.layout')

@section('title', 'Loan Applications')

@section('page-styles')
     <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-applications.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
@endsection

@section('content')

    @php
        // True kapag galing sa sidebar na "Needs Attention" (?filter=attention)
        $isAttentionView = ($viewStatus ?? 'pending') === 'pending' && request('filter') === 'attention';

        // Stats para sa Needs Attention cards (galing sa $pendingApplications na,
        // kaya walang kailangang baguhin sa controller)
        $attentionApps = collect($pendingApplications ?? [])
            ->filter(fn($a) => ($a['waiting_days'] ?? 0) >= 3);
        $attentionTotal = $attentionApps->count();
        $criticalTotal = $attentionApps->filter(fn($a) => ($a['waiting_days'] ?? 0) >= 7)->count();
        $longestApp = $attentionApps->sortByDesc('waiting_days')->first();
        $longestDays = $longestApp['waiting_days'] ?? 0;

        // Stats para sa Approved / Rejected / On Hold cards (galing sa $filteredApplications)
        $filteredCol = collect($filteredApplications ?? []);
        $filteredTotal = $filteredCol->count();
        $filteredAmount = $filteredCol->sum('loan_amount');
        $filteredAverage = $filteredTotal > 0 ? $filteredAmount / $filteredTotal : 0;

        $purposeCounts = $filteredCol->countBy('loan_purpose')->sortDesc();
        $topPurposeKey = $purposeCounts->keys()->first();
        $topPurpose = $topPurposeKey ? ucfirst(str_replace('_', ' ', $topPurposeKey)) : '—';
        $topPurposeCount = $purposeCounts->first() ?? 0;

        $oldestFiltered = $filteredCol->sortBy('applied_at')->first();
        $oldestFilteredDays = $oldestFiltered
            ? (int) \Carbon\Carbon::parse($oldestFiltered->applied_at)->diffInDays(now())
            : 0;
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">
                    Loan Applications
                    @if ($isAttentionView)
                        &mdash; Needs Attention
                    @elseif (($viewStatus ?? 'pending') !== 'pending')
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

        @if (($viewStatus ?? 'pending') === 'pending')
            {{-- ================= PENDING VIEW ================= --}}

            {{-- ---- Summary cards ---- --}}
            @if ($isAttentionView)
                {{-- Needs Attention view: 3 cards na pang-attention lang --}}
                <div class="la-stats-row is-three">
                    <div class="la-stat-card {{ $attentionTotal > 0 ? 'is-attention' : '' }}">
                        <div class="la-stat-top">
                            <span class="la-stat-label">Needs Attention</span>
                            <i class="feather-alert-triangle la-stat-icon"></i>
                        </div>
                        <div class="la-stat-value">{{ $attentionTotal }}</div>
                        <div class="la-stat-sub">waiting 3+ days</div>
                    </div>
                    <div class="la-stat-card">
                        <div class="la-stat-top">
                            <span class="la-stat-label">Longest Waiting</span>
                            <i class="feather-clock la-stat-icon"></i>
                        </div>
                        @if ($longestApp)
                            <div class="la-stat-value">{{ $longestDays }} {{ $longestDays == 1 ? 'day' : 'days' }}</div>
                            <div class="la-stat-sub">oldest pending application</div>
                        @else
                            <div class="la-stat-value">&mdash;</div>
                            <div class="la-stat-sub">no applications waiting</div>
                        @endif
                    </div>
                    <div class="la-stat-card {{ $criticalTotal > 0 ? 'is-critical' : '' }}">
                        <div class="la-stat-top">
                            <span class="la-stat-label">Waiting 7+ Days</span>
                            <i class="feather-alert-octagon la-stat-icon"></i>
                        </div>
                        <div class="la-stat-value">{{ $criticalTotal }}</div>
                        <div class="la-stat-sub">needs urgent review</div>
                    </div>
                </div>
            @else
                {{-- Regular Pending view: 4 cards --}}
                <div class="la-stats-row">
                    <div class="la-stat-card">
                        <div class="la-stat-top">
                            <span class="la-stat-label">
                                Total Pending
                                @if (($pendingLoanCount ?? 0) > 0)
                                    <span class="la-stat-badge">{{ $pendingLoanCount }}</span>
                                @endif
                            </span>
                            <i class="feather-clock la-stat-icon"></i>
                        </div>
                        <div class="la-stat-value">{{ count($pendingApplications) }}</div>
                        <div class="la-stat-sub">applications waiting for approval</div>
                    </div>
                    <div class="la-stat-card">
                        <div class="la-stat-top">
                            <span class="la-stat-label">New Applications</span>
                            <i class="feather-file-plus la-stat-icon"></i>
                        </div>
                        <div class="la-stat-value">{{ $newApplicationsCount ?? 0 }}</div>
                        <div class="la-stat-sub">submitted within 24 hours</div>
                    </div>
                    {{-- Clickable: i-filter agad ang table sa "Needs Attention (3+ days)" --}}
                    <div class="la-stat-card {{ ($needsAttentionCount ?? 0) > 0 ? 'is-attention' : '' }}"
                        role="button" tabindex="0" style="cursor: pointer;"
                        title="Show applications waiting 3+ days"
                        onclick="var f = document.getElementById('pendingStatusFilter'); if (f) { f.value = 'attention'; filterLoanPending(); }"
                        onkeydown="if (event.key === 'Enter') this.click();">
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
            @endif

            @if (count($pendingApplications) === 0)
                <div class="la-empty-large">
                    <span class="la-empty-icon-lg">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <p class="la-empty-title">All caught up!</p>
                    <p class="la-empty-desc">No pending loan applications to review.</p>
                </div>
            @else
                              <div class="reviewed-card">

                    <div class="reviewed-toolbar">
                        <div class="search-bar-wrap reviewed-search-wrap">
                            <i class="feather-search"></i>
                            <input type="text" id="pendingSearchInput" placeholder="Search borrower name or email..."
                                onkeyup="filterLoanPending()">
                        </div>
                        <div class="reviewed-toolbar-meta">
                            @if ($isAttentionView)
                                {{-- Needs Attention view: naka-lock sa "attention", walang dropdown.
                                     Hidden input para gumana pa rin ang JS (same id). --}}
                                <input type="hidden" id="pendingStatusFilter" value="attention">
                            @else
                                <select id="pendingStatusFilter" class="reviewed-decision-filter" onchange="filterLoanPending()">
                                    <option value="all" selected>All Pending</option>
                                    <option value="new">New Applications</option>
                                    <option value="attention">Needs Attention (3+ days)</option>
                                </select>
                            @endif
                            <select id="pendingSortSelect" class="reviewed-decision-filter" onchange="filterLoanPending()">
                                <option value="applied_desc" selected>Newest First</option>
                                <option value="applied_asc">Oldest First</option>
                                <option value="waiting_desc">Waiting Time (Longest)</option>
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
                                    <th>Reviews</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="loanPendingList">
                                @foreach ($pendingApplications as $app)
                                    @php
                                        $waitingDays = $app['waiting_days'] ?? 0;
                                        $isNewApplication = \Carbon\Carbon::parse($app['applied_at'])->gt(now()->subDay());
                                    @endphp
                              <tr
    data-search="{{ strtolower($app['last_name'] . ' ' . $app['first_name'] . ' ' . $app['email']) }}"
    data-status="{{ $app['status'] }}"
    data-waiting="{{ $waitingDays }}"
    data-amount="{{ $app['loan_amount'] }}"
    data-applied="{{ \Carbon\Carbon::parse($app['applied_at'])->toIso8601String() }}"
    data-new="{{ $isNewApplication ? '1' : '0' }}"
    data-attention="{{ $waitingDays >= 3 ? '1' : '0' }}"
    data-href="{{ route('admin.loan-application-detail', $app['uuid']) }}"
    class="la-clickable-row {{ $waitingDays >= 3 ? 'is-attention-row' : '' }}">
                                        <td>
                                            <div class="rt-name">{{ $app['last_name'] }}, {{ $app['first_name'] }}</div>
                                            <div class="rt-email">{{ $app['email'] }}</div>
                                        </td>
                                        <td class="text-end rt-date">&#8369;{{ number_format($app['loan_amount'], 2) }}</td>
                                        <td class="rt-date">{{ $app['loan_term'] }} months</td>
                                        <td class="rt-remarks" title="{{ ucfirst(str_replace('_', ' ', $app['loan_purpose'] ?? '—')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $app['loan_purpose'] ?? '—')) }}
                                        </td>
                                        <td class="rt-date">{{ date('M d, Y', strtotime($app['applied_at'])) }}</td>
                                        <td>
                                            <div class="progress-mini-table">
                                                <span class="pm-item is-approved">{{ $app['approved_count'] }} Approved</span>
                                                <span class="pm-item is-rejected">{{ $app['rejected_count'] }} Rejected</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($app['status'] === 'on_hold')
                                                <span class="status-subtle is-onhold"><span class="dot"></span>On
                                                    Hold</span>
                                            @else
                                                <span class="status-subtle"><span class="dot"></span>Pending</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.loan-application-detail', $app['uuid']) }}"
                                                class="btn-light">Review</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div id="pendingEmptyState" class="reviewed-empty" style="display:none;">
                            @if ($isAttentionView)
                                No applications need attention right now.
                            @else
                                No applications match your search.
                            @endif
                        </div>
                    </div>

                    <div class="reviewed-pagination pending-pagination-row" id="pendingPagination">
                        <div class="pending-rpp-wrap">
                            <span class="reviewed-count-label">Showing</span>
                            <select id="pendingRowsPerPage" class="reviewed-rpp-select" onchange="pendingChangeRowsPerPage(this)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <div class="reviewed-pagination-controls" id="pendingPaginationControls"></div>
                        <span class="reviewed-count-text" id="pendingPaginationInfo">Showing
                            {{ count($pendingApplications) }} of {{ count($pendingApplications) }}
                            applications</span>
                    </div>
                </div>
            @endif
        @elseif($viewStatus === 'reviewed')
            {{-- ================= REVIEWED VIEW ================= --}}
            {{-- Previously a JS-toggled tab inside Pending; now its own
             page/sidebar link, same pattern as Borrower Verification. --}}

            @if (count($reviewedApplications) === 0)
                <div class="la-empty-large">
                    <span class="la-empty-icon-lg la-empty-icon-lg-muted">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </span>
                    <p class="la-empty-desc">No reviewed applications yet.</p>
                </div>
            @else
                <div class="reviewed-card">

                    <div class="reviewed-toolbar">
                        <div class="search-bar-wrap reviewed-search-wrap">
                            <i class="feather-search"></i>
                            <input type="text" id="loanReviewedSearchInput"
                                placeholder="Search borrower name or email..." onkeyup="filterLoanReviewed()">
                        </div>
                        <select id="loanReviewedDecisionFilter" class="reviewed-decision-filter"
                            onchange="filterLoanReviewed()">
                            <option value="">All Decisions</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <div class="reviewed-toolbar-meta">
                            <span class="reviewed-count-label">Showing</span>
                            <select id="loanReviewedRowsPerPage" class="reviewed-rpp-select"
                                onchange="loanReviewedChangeRowsPerPage(this)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <span class="reviewed-count-text" id="loanReviewedPaginationInfo">of
                                {{ count($reviewedApplications) }} results</span>
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
                                @foreach ($reviewedApplications as $r)
                                    @php
                                        $decisionLower = strtolower($r->decision ?? '');
                                    @endphp
                                 <tr data-search="{{ strtolower($r->last_name . ' ' . $r->first_name . ' ' . $r->email) }}"
    data-decision="{{ $decisionLower }}"
    data-href="{{ route('admin.loan-application-detail', $r->uuid) }}"
    class="la-clickable-row">
                                        <td>
                                            <div class="rt-name">{{ $r->last_name }}, {{ $r->first_name }}</div>
                                            <div class="rt-email">{{ $r->email }}</div>
                                        </td>
                                        <td class="text-end rt-date">&#8369;{{ number_format($r->loan_amount, 0) }}</td>
                                        <td class="rt-remarks"
                                            title="{{ ucfirst(str_replace('_', ' ', $r->loan_purpose ?? '—')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $r->loan_purpose ?? '—')) }}
                                        </td>
                                        <td class="rt-date">{{ $r->loan_term ?? '—' }} months</td>
                                        <td class="rt-date">
                                            {{ $r->applied_at ? \Carbon\Carbon::parse($r->applied_at)->format('M d, Y') : '—' }}
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ $decisionLower === 'approved' ? 'badge-approved' : 'badge-rejected' }}">
                                                {{ ucfirst($r->decision) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.loan-application-detail', $r->uuid) }}"
                                                class="btn-light">View Details</a>
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
                        <div class="reviewed-pagination-controls" id="loanReviewedPaginationControls"></div>
                    </div>
                </div>
            @endif
        @else
            {{-- ================= FILTERED VIEW: Approved / Rejected / On Hold ================= --}}

            @if (count($filteredApplications) === 0)
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
                        @if ($viewStatus === 'approved')
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        @elseif($viewStatus === 'rejected')
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        @else
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        @endif
                    </span>
                    <p class="la-empty-title">{{ $emptyTitle }}</p>
                    <p class="la-empty-desc">{{ $emptyDesc }}</p>
                </div>
            @else
                {{-- ---- Summary cards (Approved / Rejected / On Hold) ---- --}}
                <div class="la-stats-row is-three">
                    @if ($viewStatus === 'approved')
                        <div class="la-stat-card">
                            <div class="la-stat-top">
                                <span class="la-stat-label">Total Approved</span>
                                <i class="feather-check-circle la-stat-icon"></i>
                            </div>
                            <div class="la-stat-value">{{ $filteredTotal }}</div>
                            <div class="la-stat-sub">approved by all administrators</div>
                        </div>
                        <div class="la-stat-card">
                            <div class="la-stat-top">
                                <span class="la-stat-label">Total Amount Approved</span>
                                <i class="feather-dollar-sign la-stat-icon"></i>
                            </div>
                            <div class="la-stat-value">&#8369;{{ number_format($filteredAmount, 0) }}</div>
                            <div class="la-stat-sub">total loan value</div>
                        </div>
                        <div class="la-stat-card">
                            <div class="la-stat-top">
                                <span class="la-stat-label">Average Loan</span>
                                <i class="feather-bar-chart-2 la-stat-icon"></i>
                            </div>
                            <div class="la-stat-value">&#8369;{{ number_format($filteredAverage, 0) }}</div>
                            <div class="la-stat-sub">per approved application</div>
                        </div>
                    @elseif ($viewStatus === 'rejected')
                        <div class="la-stat-card">
                            <div class="la-stat-top">
                                <span class="la-stat-label">Total Rejected</span>
                                <i class="feather-x-circle la-stat-icon"></i>
                            </div>
                            <div class="la-stat-value">{{ $filteredTotal }}</div>
                            <div class="la-stat-sub">rejected by all administrators</div>
                        </div>
                        <div class="la-stat-card">
                            <div class="la-stat-top">
                                <span class="la-stat-label">Amount Rejected</span>
                                <i class="feather-dollar-sign la-stat-icon"></i>
                            </div>
                            <div class="la-stat-value">&#8369;{{ number_format($filteredAmount, 0) }}</div>
                            <div class="la-stat-sub">total loan value</div>
                        </div>
                        <div class="la-stat-card">
                            <div class="la-stat-top">
                                <span class="la-stat-label">Top Purpose</span>
                                <i class="feather-tag la-stat-icon"></i>
                            </div>
                            <div class="la-stat-value">{{ $topPurpose }}</div>
                            <div class="la-stat-sub">{{ $topPurposeCount }} {{ $topPurposeCount == 1 ? 'application' : 'applications' }}</div>
                        </div>
                    @else
                        <div class="la-stat-card">
                            <div class="la-stat-top">
                                <span class="la-stat-label">Total On Hold</span>
                                <i class="feather-pause-circle la-stat-icon"></i>
                            </div>
                            <div class="la-stat-value">{{ $filteredTotal }}</div>
                            <div class="la-stat-sub">waiting for admin agreement</div>
                        </div>
                        <div class="la-stat-card">
                            <div class="la-stat-top">
                                <span class="la-stat-label">Amount On Hold</span>
                                <i class="feather-dollar-sign la-stat-icon"></i>
                            </div>
                            <div class="la-stat-value">&#8369;{{ number_format($filteredAmount, 0) }}</div>
                            <div class="la-stat-sub">total loan value</div>
                        </div>
                        <div class="la-stat-card {{ $oldestFilteredDays >= 3 ? 'is-attention' : '' }}">
                            <div class="la-stat-top">
                                <span class="la-stat-label">Oldest On Hold</span>
                                <i class="feather-clock la-stat-icon"></i>
                            </div>
                            <div class="la-stat-value">{{ $oldestFilteredDays }} {{ $oldestFilteredDays == 1 ? 'day' : 'days' }}</div>
                            <div class="la-stat-sub">since application date</div>
                        </div>
                    @endif
                </div>

                <div class="reviewed-card">

                    <div class="reviewed-toolbar">
                        <div class="search-bar-wrap reviewed-search-wrap">
                            <i class="feather-search"></i>
                            <input type="text" id="filteredSearchInput" placeholder="Search by name or email..."
                                onkeyup="filterLoanFiltered()">
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
                                {{ count($filteredApplications) }} results</span>
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
                                @foreach ($filteredApplications as $app)
                         <tr
    data-search="{{ strtolower($app->last_name . ' ' . $app->first_name . ' ' . $app->email . ' ' . $app->uuid . ' LN-' . substr($app->uuid, 0, 8)) }}"
    data-href="{{ route('admin.loan-application-detail', $app->uuid) }}"
    class="la-clickable-row">
                                        <td>
                                            <div class="rt-name">{{ $app->last_name }}, {{ $app->first_name }}</div>
                                            <div class="rt-email">{{ $app->email }}</div>
                                        </td>
                                        <td class="rt-date" title="{{ $app->uuid }}">LN-{{ strtoupper(substr($app->uuid, 0, 8)) }}</td>
                                        <td class="text-end rt-date">&#8369;{{ number_format($app->loan_amount, 0) }}</td>
                                        <td class="rt-date">{{ $app->loan_term }} months</td>
                                        <td class="rt-remarks"
                                            title="{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}">
                                            {{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}
                                        </td>
                                        <td class="rt-date">{{ date('M d, Y', strtotime($app->applied_at)) }}</td>
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
                                            <a href="{{ route('admin.loan-application-detail', $app->uuid) }}"
                                                class="btn-light">Review</a>
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
                        <div class="reviewed-pagination-controls" id="filteredPaginationControls"></div>
                    </div>
                </div>
            @endif

        @endif

    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/loan-applications.js') }}"></script>

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showLoanDecisionToast(@json(session('decisionStatus')));
            });
        </script>
    @endif
@endsection