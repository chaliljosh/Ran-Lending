@extends('admin.layout')

@section('title', $scopeLabel . ' Audit Logs')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/audit-logs.css') }}?v={{ time() }}">
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">{{ $scopeLabel }} Audit Logs</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Audit Logs</li>
                <li class="breadcrumb-item">{{ $scopeLabel }}</li>
            </ul>
        </div>
    </div>

    <div class="main-content al-page" id="alPage">

        @if ($tableMissing)
            <div class="al-panel">
                <div class="al-panel-body">
                    <div class="al-empty">
                        <strong>The audit_logs table does not exist yet</strong>
                        Run <code>audit-logs.sql</code> in the Supabase SQL Editor, then refresh this page.
                    </div>
                </div>
            </div>
        @else

            <div class="al-panel">
                <div class="al-panel-header">
                    <div>
                        <div class="al-panel-title">{{ $scopeLabel }} Activity</div>
                        <div class="al-panel-sub">
                            A read-only record of what {{ $scope === 'borrower' ? 'borrowers' : 'admins' }} did, and when. Entries cannot be edited or deleted.
                        </div>
                    </div>
                    <div class="al-count-pill">
                        <i class="feather-activity"></i>
                        <span><strong>{{ number_format($logs->total()) }}</strong> {{ \Illuminate\Support\Str::plural('entry', $logs->total()) }}</span>
                    </div>
                </div>

                {{-- Filters --}}
                <form method="GET" action="{{ route($pageRoute) }}" class="al-filters" id="alFilters">

                    <div class="al-filter al-filter-search">
                        <label for="alQ">Search</label>
                        <input type="text" id="alQ" name="q" class="al-input" value="{{ $filters['q'] }}"
                            placeholder="Description, name, action, IP..." autocomplete="off">
                    </div>

                    <div class="al-filter">
                        <label for="alGroup">Type</label>
                        <select id="alGroup" name="group" class="al-input js-al-auto">
                            <option value="">All types</option>
                            @foreach ($groups as $key => $label)
                                <option value="{{ $key }}" @selected($filters['group'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Person dropdown: Admin page only --}}
                    @if ($hasActorFilter)
                        <div class="al-filter">
                            <label for="alActor">{{ $scopeLabel }}</label>
                            <select id="alActor" name="actor" class="al-input js-al-auto">
                                <option value="">Everyone</option>
                                @foreach ($actors as $a)
                                    <option value="{{ $a->actor_user_id }}" @selected($filters['actor'] === (string) $a->actor_user_id)>
                                        {{ $a->actor_name ?: 'User #' . $a->actor_user_id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Dates: same calendar as "Record Payment" (ongoing-loan-detail).
                         audit-logs.js builds the popup. The hidden input holds the real value (YYYY-MM-DD)
                         that is sent to the server; the visible box only shows MM/DD/YYYY. --}}
                    @foreach (['from' => 'From', 'to' => 'To'] as $field => $fieldLabel)
                        <div class="al-filter al-filter-date">
                            <label for="alDate{{ ucfirst($field) }}">{{ $fieldLabel }}</label>
                            <div class="al-date" data-al-date>
                                <input type="hidden" name="{{ $field }}" value="{{ $filters[$field] }}">
                                <input type="text" id="alDate{{ ucfirst($field) }}" class="al-input al-date-input"
                                    placeholder="MM/DD/YYYY" readonly autocomplete="off">
                                <i class="feather-calendar al-date-icon"></i>
                            </div>
                        </div>
                    @endforeach

                    <div class="al-filter-actions">
                        <button type="submit" class="al-btn al-btn-primary">Apply</button>
                        <a href="{{ route($pageRoute) }}" class="al-btn">Reset</a>
                    </div>
                </form>

                <div class="al-table-wrap">
                    @if ($logs->count() > 0)
                        <table class="al-table" id="alTable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Actor</th>
                                    <th>Action</th>
                                    <th>Target</th>
                                    <th>Description</th>
                                    <th>IP</th>
                                    <th class="al-th-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logs as $log)
                                    <tr>
                                        <td class="al-nowrap">
                                            <div class="al-main">{{ $log->when }}</div>
                                            <div class="al-sub">{{ $log->time }}</div>
                                        </td>
                                        <td>
                                            <div class="al-main">{{ $log->actor_name }}</div>
                                            <span class="al-role is-{{ $log->actor_role }}">{{ ucfirst($log->actor_role) }}</span>
                                        </td>
                                        <td><span class="al-action is-{{ $log->group }}">{{ $log->action }}</span></td>
                                        <td class="al-target">{{ $log->target ?? '—' }}</td>
                                        <td class="al-desc">{{ $log->description ?? '—' }}</td>
                                        <td class="al-nowrap al-mono">{{ $log->ip ?? '—' }}</td>
                                        <td class="al-td-end">
                                            <button type="button" class="al-btn al-btn-sm js-al-view"
                                                data-log="{{ json_encode($log->payload) }}">Details</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="al-empty">
                            <strong>No entries found</strong>
                            @if (collect($filters)->filter()->isNotEmpty())
                                Try a different search or clear the filters.
                            @else
                                {{ $scopeLabel }} activity will show up here as soon as something happens.
                            @endif
                        </div>
                    @endif
                </div>

                @if ($logs->hasPages())
                    <div class="al-pagination">
                        <span class="al-page-info">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
                        <div class="al-page-buttons">
                            @if ($logs->onFirstPage())
                                <span class="al-btn al-btn-sm is-disabled">Previous</span>
                            @else
                                <a href="{{ $logs->previousPageUrl() }}" class="al-btn al-btn-sm">Previous</a>
                            @endif

                            @if ($logs->hasMorePages())
                                <a href="{{ $logs->nextPageUrl() }}" class="al-btn al-btn-sm">Next</a>
                            @else
                                <span class="al-btn al-btn-sm is-disabled">Next</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

        @endif
    </div>

@endsection

@push('modals')
    <div class="modal fade al-modal" id="alDetailModal" tabindex="-1" aria-labelledby="alDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title" id="alDetailTitle">Log Details</h6>
                        <div class="al-modal-sub" id="alDetailAction"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="al-detail-desc" id="alDetailDesc"></p>

                    <dl class="al-detail-grid">
                        <div><dt>Time</dt><dd id="alDetailWhen"></dd></div>
                        <div><dt>Actor</dt><dd id="alDetailActor"></dd></div>
                        <div><dt>Target</dt><dd id="alDetailTarget"></dd></div>
                        <div><dt>IP Address</dt><dd id="alDetailIp" class="al-mono"></dd></div>
                        <div class="al-span-2"><dt>Device / Browser</dt><dd id="alDetailUa"></dd></div>
                    </dl>

                    <div class="al-detail-section" id="alChangesSection" hidden>
                        <div class="al-detail-section-title">What changed</div>
                        <div class="al-scroll">
                            <table class="al-mini-table">
                                <thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead>
                                <tbody id="alChangesBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="al-detail-section" id="alContextSection" hidden>
                        <div class="al-detail-section-title">Extra details</div>
                        <div class="al-scroll">
                            <table class="al-mini-table">
                                <tbody id="alContextBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="al-btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@section('page-scripts')
    <script src="{{ asset('functions/audit-logs.js') }}?v={{ time() }}"></script>
@endsection