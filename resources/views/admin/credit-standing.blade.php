@extends('admin.layout')

@section('title', 'Credit Standing')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    {{-- Ginagamit ang design ng Ongoing Loans, tapos dagdag lang ang credit-standing.css --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loans.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/credit-standing.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}">
@endsection

@section('content')

    @php
        // Good at Bad Credit lang. (Wala na ang Watchlist.)
        $tabs = [
            'all'  => 'All',
            'good' => 'Good Credit',
            'bad'  => 'Bad Credit',
        ];

        // Alisin ang Watchlist (warning) sa listahan at i-recompute ang "All" count.
        // TEMPORARY ito sa view; ang tamang ayos ay sa controller.
        $borrowers = collect($borrowers)
            ->reject(fn ($b) => ($b['credit']['color'] ?? '') === 'warning')
            ->values();
        $counts['all'] = $counts['good'] + $counts['bad'];
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Credit Standing</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Borrowers</li>
                <li class="breadcrumb-item">Credit Standing</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

        <!-- Stats -->
        <div class="stats-grid-v2 is-two">
            <div class="rs-card stat-card-v2">
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Good Credit</span>
                    <i class="feather-thumbs-up stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">{{ $counts['good'] }}</div>
            </div>
            <div class="rs-card stat-card-v2">
                <div class="stat-card-head-v2">
                    <span class="stat-label-v2">Bad Credit</span>
                    <i class="feather-thumbs-down stat-icon-v2"></i>
                </div>
                <div class="stat-value-v2">{{ $counts['bad'] }}</div>
            </div>
        </div>

        <div class="rs-card rs-table-card">
            <!-- Filter toolbar -->
            <div class="filter-bar-v2">
                <div class="credit-tabs-v2" role="group" aria-label="Credit filter">
                    @foreach ($tabs as $key => $label)
                        <button type="button" data-standing="{{ $key }}"
                            class="credit-tab-v2 {{ $key === 'all' ? 'is-active' : '' }}"
                            onclick="creditSetTab('{{ $key }}')">
                            {{ $label }}
                            <span class="credit-tab-count-v2">{{ $counts[$key] }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Nakatago. Ito ang binabasa ng ongoing-loans.js para sa tab filter (good / bad) --}}
                <select id="statusFilter" hidden aria-hidden="true">
                    <option value="">All</option>
                    <option value="good">Good</option>
                    <option value="bad">Bad</option>
                </select>

                <div class="search-wrap-v2">
                    <i class="feather-search"></i>
                    <input type="text" id="filterSearchInput"
                        placeholder="Search name, email, or Borrower ID..." onkeyup="filterOngoingLoans()">
                </div>

                <select class="sort-select-v2" id="sortFilter" onchange="sortOngoingLoans()" aria-label="Sort">
                    <option value="newest" selected>Newest First</option>
                    <option value="oldest">Oldest First</option>
                </select>
            </div>

            @if (count($borrowers) === 0)
                <div class="empty-state-v2" style="display:block;">
                    <i class="feather-shield"></i>
                    <h6>No borrowers found</h6>
                    <p>There are no borrowers for this filter.</p>
                </div>
            @else
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
                                <th data-sort="loans" onclick="olSortByColumn('loans')">Completed Loans</th>
                                <th>Credit Standing</th>
                                <th data-sort="applied" onclick="olSortByColumn('applied')">Last Loan</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($borrowers as $b)
                                @php
                                    $lastStamp = $b['last_applied'] ? \Carbon\Carbon::parse($b['last_applied'])->timestamp : 0;
                                    // success = good, danger = bad, kahit ano pa = watch
                                    $standingKey = ['success' => 'good', 'danger' => 'bad'][$b['credit']['color']] ?? 'watch';
                                @endphp
                                {{-- Wala nang data-href / olGoToRow: modal na ang bubukas, sa button lang --}}
                                <tr data-search="{{ strtolower($b['name'] . ' ' . $b['email'] . ' ' . $b['borrower_id']) }}"
                                    data-name="{{ $b['name'] }}"
                                    data-loans="{{ $b['total_loans'] }}"
                                    data-applied="{{ $lastStamp }}"
                                    data-status="{{ $standingKey }}">
                                    <td>
                                        <div class="borrower-cell-v2">
                                            <div>
                                                <div class="borrower-name-v2">{{ $b['name'] }}</div>
                                                <div class="borrower-id-v2">{{ $b['borrower_id'] }}</div>
                                                <div class="borrower-email-v2">{{ $b['email'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="loan-amount-v2">{{ $b['total_loans'] }}</td>
                                    <td>
                                        <span class="status-pill-v2 is-{{ $b['credit']['color'] }}">
                                            {{ $b['credit']['label'] }}
                                        </span>
                                        <div class="credit-reason-v2">{{ $b['credit']['reason'] }}</div>
                                        @if ($b['credit']['wait_until'])
                                            <div class="credit-wait-v2">
                                                Can reapply on {{ $b['credit']['wait_until']->format('M d, Y') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="loan-meta-v2">
                                        {{ $lastStamp ? \Carbon\Carbon::createFromTimestamp($lastStamp)->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn-view-v2"
                                            onclick="csOpenProfile('{{ $b['uuid'] }}')">
                                            <i class="feather-eye"></i> View Details
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

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
                            Showing 1&ndash;{{ min(10, count($borrowers)) }} of {{ count($borrowers) }}
                        </span>
                    </div>
                    <div class="pagination-nav-v2" id="olPaginationNav"></div>
                    <div></div>
                </div>
            @endif
        </div>
    </div>

    {{-- ============ Borrower Credit Profile modal ============ --}}
    <div class="cs-modal-overlay" id="csModal" aria-hidden="true">
        <div class="cs-modal" role="dialog" aria-modal="true" aria-labelledby="csModalTitle">
            <div class="cs-modal-head">
                <h5 id="csModalTitle">Borrower Credit Profile</h5>
                <button type="button" class="cs-x" onclick="csCloseProfile()" aria-label="Close">&times;</button>
            </div>
            <div class="cs-modal-body" id="csModalBody"></div>
            <div class="cs-modal-foot">
                <button type="button" class="cs-close-btn" onclick="csCloseProfile()">Close</button>
            </div>
        </div>
    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/ongoing-loans.js') }}"></script>
    <script src="{{ asset('functions/row-loader.js') }}"></script>

    <script>
        // Tab filter na walang page refresh (hindi ginalaw)
        (function () {
            var valid = ['all', 'good', 'bad'];

            var param = new URLSearchParams(window.location.search).get('standing');
            if (param && param !== 'all') {
                window.location.replace(window.location.pathname + '?standing=all#' + param);
                return;
            }

            window.creditSetTab = function (key) {
                if (valid.indexOf(key) === -1) key = 'all';

                var select = document.getElementById('statusFilter');
                if (select) select.value = key === 'all' ? '' : key;

                document.querySelectorAll('.credit-tab-v2').forEach(function (btn) {
                    btn.classList.toggle('is-active', btn.dataset.standing === key);
                });

                history.replaceState(null, '', window.location.pathname + window.location.search
                    + (key === 'all' ? '' : '#' + key));

                if (typeof filterOngoingLoans === 'function') filterOngoingLoans();
            };

            function initTab() {
                var start = window.location.hash.replace('#', '');
                if (start && start !== 'all') window.creditSetTab(start);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTab);
            } else {
                initTab();
            }
        })();
    </script>

    <script>
        // Borrower Credit Profile modal. Fresh na data galing server sa bawat bukas.
        (function () {
            var urlTpl  = @json(route('admin.credit-standing-profile', ['uuid' => '__UUID__']));
            var overlay = document.getElementById('csModal');
            var body    = document.getElementById('csModalBody');
            var reqId   = 0;

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }
            function money(n) {
                return '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            function row(l, v) { return '<div class="cs-row"><span>' + l + '</span><span>' + v + '</span></div>'; }
            function beh(l, v) { return '<div class="cs-beh-stat"><div class="cs-beh-label">' + l + '</div><div class="cs-beh-num">' + esc(v) + '</div></div>'; }
            function stat(icon, n, label, isLate) {
                return '<div class="cs-stat' + (isLate ? ' is-late' : '') + '"><i class="' + icon + '"></i>'
                    + '<div><div class="cs-stat-num">' + esc(n) + '</div><div class="cs-stat-label">' + label + '</div></div></div>';
            }

            function render(d) {
                var s = d.standing, st = d.stats, bh = d.behavior;
                var bad = s.color === 'danger';
                var cls = bad ? 'is-danger' : 'is-success';
                var icon = bad ? 'feather-alert-triangle' : 'feather-check-circle';

                var loans = d.loans.map(function (l) {
                    return '<div class="cs-loan">'
                        + '<div class="cs-loan-head"><span>Loan #' + esc(l.code) + '</span>'
                        + '<span class="cs-tag ' + (l.status === 'defaulted' ? 'is-danger' : 'is-success') + '">' + esc(l.status_label) + '</span></div>'
                        + row('Original Amount', money(l.original))
                        + row('Loan Term', esc(l.term) + ' months')
                        + row('Monthly Payment', money(l.monthly))
                        + row('Payments Made', esc(l.payments_made) + ' / ' + esc(l.payments_total))
                        + row('Outstanding Balance', money(l.outstanding))
                        + row('Loan Completion', esc(l.completed_on))
                        + '</div>';
                }).join('');

                var late = d.late_records.length
                    ? d.late_records.map(function (r) {
                        return '<div class="cs-late-item">'
                            + '<div class="cs-muted">Installment #' + esc(r.installment_no) + '</div>'
                            + '<span class="cs-late-pill">Late</span>'
                            + '<div class="cs-muted">Due: ' + esc(r.due) + ' | Paid: ' + esc(r.paid) + '</div>'
                            + '<div class="cs-muted">Delay: ' + esc(r.delay) + ' days</div></div>';
                    }).join('')
                    : '<div class="cs-muted">No late installments.</div>';

                body.innerHTML =
                    '<div class="cs-profile">'
                    + '<div class="cs-avatar"><i class="feather-user"></i></div>'
                    + '<div class="cs-profile-info"><div class="cs-name">' + esc(d.borrower.name) + '</div>'
                    + '<div class="cs-bid">' + esc(d.borrower.borrower_id) + '</div>'
                    + '<div class="cs-email">' + esc(d.borrower.email) + '</div></div>'
                    + '<span class="cs-big-pill ' + cls + '"><i class="' + icon + '"></i> ' + esc(s.label) + '</span></div>'

                    + '<div class="cs-stats">'
                    + stat('feather-file-text', st.completed_loans, 'Completed Loans', false)
                    + stat('feather-clock', st.late_installments, 'Late Installments', st.late_installments > 0)
                    + stat('feather-calendar', st.worst_delay, 'Worst Delay (days)', false)
                    + '</div>'

                    + '<div class="cs-grid">'
                    + '<div class="cs-card"><div class="cs-card-title"><i class="feather-shield"></i> Credit Standing Assessment</div>'
                    + row('Current Classification', '<span class="cs-tag ' + cls + '">' + esc(s.label) + '</span>')
                    + '<div class="cs-reason ' + cls + '"><b>Reason for Classification</b>' + esc(s.reason) + '</div>'
                    + (s.wait_until ? '<div class="cs-wait">Can reapply on ' + esc(s.wait_until) + '</div>' : '')
                    + '</div>'
                    + '<div class="cs-card"><div class="cs-card-title"><i class="feather-credit-card"></i> Loan History</div>'
                    + '<div class="cs-loan-scroll">' + loans + '</div></div></div>'

                    + '<div class="cs-card"><div class="cs-card-title"><i class="feather-bar-chart-2"></i> Payment Behavior</div>'
                    + '<div class="cs-behavior"><div class="cs-beh-stats">'
                    + beh('Total Installments', bh.total) + beh('On-Time Payments', bh.on_time)
                    + beh('Late Payments', bh.late) + beh('Worst Delay', bh.worst + ' days')
                    + '</div><div class="cs-late-rec"><div class="cs-late-rec-title">Late Payment Record</div>' + late + '</div></div>'
                    + '<div class="cs-muted" style="font-size:.74rem;margin-top:10px;">Late = paid ' + esc(d.threshold) + ' or more days after the due date (latest loan).</div></div>';
            }

            window.csOpenProfile = function (uuid) {
                var id = ++reqId;
                body.innerHTML = '<div class="cs-state">Loading…</div>';
                overlay.classList.add('is-open');
                overlay.setAttribute('aria-hidden', 'false');

                fetch(urlTpl.replace('__UUID__', encodeURIComponent(uuid)), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw j; return j; }); })
                    .then(function (d) { if (id === reqId) render(d); })
                    .catch(function (e) {
                        if (id === reqId) body.innerHTML = '<div class="cs-state">' + esc((e && e.message) || 'Failed to load the credit profile.') + '</div>';
                    });
            };

            window.csCloseProfile = function () {
                overlay.classList.remove('is-open');
                overlay.setAttribute('aria-hidden', 'true');
            };

            overlay.addEventListener('click', function (e) { if (e.target === overlay) csCloseProfile(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') csCloseProfile(); });
        })();
    </script>
@endsection