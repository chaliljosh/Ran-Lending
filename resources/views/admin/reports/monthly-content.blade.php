<div class="row">

    {{-- ============ STAT CARDS ============ --}}
    <div class="col-xxl-3 col-md-6">
        <div class="card stretch stretch-full adm-stat-card">
            <div class="card-body adm-stat-body">
                <div class="adm-stat-top">
                    <span class="adm-stat-label">Applications Approved</span>
                    <i class="feather-file-text adm-stat-icon"></i>
                </div>
                <div class="adm-stat-value">{{ number_format($summary['approved']) }}</div>
                <div class="adm-stat-sub">{{ $periodLabel }}</div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-md-6">
        <div class="card stretch stretch-full adm-stat-card">
            <div class="card-body adm-stat-body">
                <div class="adm-stat-top">
                    <span class="adm-stat-label">Money Collected</span>
                    <i class="feather-dollar-sign adm-stat-icon"></i>
                </div>
                <div class="adm-stat-value">&#8369;{{ number_format($summary['collected'], 2) }}</div>
                <div class="adm-stat-sub">Installments paid</div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-md-6">
        <div class="card stretch stretch-full adm-stat-card">
            <div class="card-body adm-stat-body">
                <div class="adm-stat-top">
                    <span class="adm-stat-label">Total Interest</span>
                    <i class="feather-percent adm-stat-icon"></i>
                </div>
                <div class="adm-stat-value">&#8369;{{ number_format($summary['interest'], 2) }}</div>
                <div class="adm-stat-sub">Collected from installments paid</div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-md-6">
        <div class="card stretch stretch-full adm-stat-card">
            <div class="card-body adm-stat-body">
                <div class="adm-stat-top">
                    <span class="adm-stat-label">Penalties Collected</span>
                    <i class="feather-alert-triangle adm-stat-icon is-orange"></i>
                </div>
                <div class="adm-stat-value">&#8369;{{ number_format($summary['penalty'], 2) }}</div>
                <div class="adm-stat-sub">Penalties marked paid</div>
            </div>
        </div>
    </div>

    {{-- ============ COLLECTED PER MONTH (bar chart) ============ --}}
    @php $maxCollected = max(collect($rows)->max('collected'), 1); @endphp
    <div class="col-12">
        <div class="card stretch stretch-full adm-panel rpt-trend">
            <div class="card-header adm-panel-header">
                <div class="adm-panel-header-left">
                    <div>
                        <h5 class="card-title adm-panel-title">Collected — {{ $year }}</h5>
                        <p class="adm-panel-sub">Money collected per month.</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="adm-trend">
                    <div class="adm-trend-bars">
                        @foreach ($rows as $r)
                            @php
                                $pct = (int) round(($r['collected'] / $maxCollected) * 100);
                                $dim = $month !== 0 && $month !== $r['month'];
                            @endphp
                            <div class="adm-trend-col {{ $dim ? 'is-dim' : '' }} {{ $month === $r['month'] ? 'is-active' : '' }}">
                                <div class="adm-trend-track">
                                    <div class="adm-trend-fill" style="height: {{ $pct }}%"
                                        title="{{ $r['label'] }}: &#8369;{{ number_format($r['collected'], 2) }}"></div>
                                </div>
                                <div class="adm-trend-label">{{ substr($r['label'], 0, 3) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ MONTH BY MONTH ============ --}}
    <div class="col-12">
        <div class="card stretch stretch-full adm-panel">
            <div class="card-header adm-panel-header">
                <div class="adm-panel-header-left">
                    <div>
                        <h5 class="card-title adm-panel-title">Month by Month</h5>
                        <p class="adm-panel-sub">Approved loans, collections, interest and penalties for {{ $year }}.</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 adm-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Approved</th>
                                <th class="text-end">Collected</th>
                                <th class="text-end">Interest</th>
                                <th class="text-end">Penalties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $r)
                                <tr class="{{ $month === $r['month'] ? 'is-active' : '' }}">
                                    <td class="adm-table-name">{{ $r['label'] }}</td>
                                    <td class="text-end adm-table-amount">{{ number_format($r['approved']) }}</td>
                                    <td class="text-end adm-table-amount">&#8369;{{ number_format($r['collected'], 2) }}</td>
                                    <td class="text-end adm-table-amount">&#8369;{{ number_format($r['interest'], 2) }}</td>
                                    <td class="text-end adm-table-amount">&#8369;{{ number_format($r['penalty'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="rpt-total">
                                <td>Total</td>
                                <td class="text-end">{{ number_format(collect($rows)->sum('approved')) }}</td>
                                <td class="text-end">&#8369;{{ number_format(collect($rows)->sum('collected'), 2) }}</td>
                                <td class="text-end">&#8369;{{ number_format(collect($rows)->sum('interest'), 2) }}</td>
                                <td class="text-end">&#8369;{{ number_format(collect($rows)->sum('penalty'), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ APPROVED APPLICATIONS ============ --}}
    <div class="col-12">
        <div class="card stretch stretch-full adm-panel">
            <div class="card-header adm-panel-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="adm-panel-header-left">
                    <div>
                        <h5 class="card-title adm-panel-title">Approved Applications</h5>
                        <p class="adm-panel-sub">Loans approved in {{ $periodLabel }}.</p>
                    </div>
                </div>
                <div class="rpt-search">
                    <i class="feather-search"></i>
                    <input type="search" class="form-control rpt-search-input"
                        placeholder="Search loan ID, borrower, amount..."
                        aria-label="Search approved applications">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 adm-table">
                        <thead>
                            <tr>
                                <th>Loan ID</th>
                                <th>Borrower</th>
                                <th class="text-end">Loan Amount</th>
                                <th class="text-end">Term</th>
                                <th class="text-end">Interest Collected</th>
                                <th>Approved On</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($approvedList as $a)
                                <tr class="rpt-approved-row">
                                    <td class="adm-table-date">{{ $a->loan_code }}</td>
                                    <td class="adm-table-name">{{ $a->borrower_name }}</td>
                                    <td class="text-end adm-table-amount">&#8369;{{ number_format($a->loan_amount, 2) }}</td>
                                    <td class="text-end adm-table-date">{{ $a->loan_term }} mo</td>
                                    <td class="text-end adm-table-amount">&#8369;{{ number_format($a->interest_collected, 2) }}</td>
                                    <td class="adm-table-date">{{ \Carbon\Carbon::parse($a->approved_at)->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No approved applications for this period.</td>
                                </tr>
                            @endforelse
                            <tr class="rpt-no-match" style="display: none;">
                                <td colspan="6" class="text-center text-muted py-4">No results match your search.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>