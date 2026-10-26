@extends('admin.layout')

@section('title', 'Dashboard')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-dashboard.css') }}?v={{ time() }}" />
@endsection

@section('page-scripts')
    <script src="{{ asset('functions/admin-dashboard.js') }}?v={{ time() }}"></script>
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Dashboard</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Dashboard</li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="row">
            <div class="col-xxl-3 col-md-6">
                <a href="{{ route('admin.loan-applications') }}" class="card stretch stretch-full adm-stat-card">
                    <div class="card-body adm-stat-body">
                        <div class="adm-stat-top">
                            <span class="adm-stat-label">Total Applications</span>
                            <i class="feather-file-text adm-stat-icon"></i>
                        </div>
                        <div class="adm-stat-value">{{ $totalApplications }}</div>
                        <div class="adm-stat-sub">{{ $pendingApplications }} pending review</div>
                        <i class="feather-chevron-right adm-stat-chevron"></i>
                    </div>
                </a>
            </div>

            <div class="col-xxl-3 col-md-6">
               <a href="{{ route('admin.ongoing-loans') }}" class="card stretch stretch-full adm-stat-card">
                    <div class="card-body adm-stat-body">
                        <div class="adm-stat-top">
                            <span class="adm-stat-label">Active Loans</span>
                            <i class="feather-dollar-sign adm-stat-icon"></i>
                        </div>
                        <div class="adm-stat-value">{{ $activeLoans }}</div>
                        <div class="adm-stat-sub">&#8369;{{ number_format($totalActiveAmount, 2) }} total amount</div>
                        <i class="feather-chevron-right adm-stat-chevron"></i>
                    </div>
                </a>
            </div>

            <div class="col-xxl-3 col-md-6">
                <a href="{{ route('admin.borrowers') }}" class="card stretch stretch-full adm-stat-card">
                    <div class="card-body adm-stat-body">
                        <div class="adm-stat-top">
                            <span class="adm-stat-label">Total Borrowers</span>
                            <i class="feather-users adm-stat-icon"></i>
                        </div>
                        <div class="adm-stat-value">{{ $totalBorrowers }}</div>
                        <div class="adm-stat-sub">{{ $activeBorrowers }} active borrowers</div>
                        <i class="feather-chevron-right adm-stat-chevron"></i>
                    </div>
                </a>
            </div>

            <div class="col-xxl-3 col-md-6">
                <a href="{{ route('admin.loan-applications', ['status' => 'pending']) }}" class="card stretch stretch-full adm-stat-card adm-stat-card--pending">
                    <div class="card-body adm-stat-body">
                        <div class="adm-stat-top">
                            <span class="adm-stat-label">Pending Applications</span>
                            <i class="feather-clock adm-stat-icon is-orange"></i>
                        </div>
                        <div class="adm-stat-value">{{ $pendingApplications }}</div>
                        <div class="adm-stat-sub">Awaiting review</div>
                        <i class="feather-chevron-right adm-stat-chevron"></i>
                    </div>
                </a>
            </div>

            {{-- ============ VOID REQUESTS NA HINIHINTAY ANG BOTO MO ============
                 Lalabas lang kapag may kailangang iboto ang admin na nakabukas.
                 Hindi kasama ang request na siya mismo ang nag-request. --}}
            @if ($pendingVoidCount > 0)
                <div class="col-12">
                    <div class="card stretch stretch-full adm-panel adm-void-card">
                        <div class="card-header adm-panel-header">
                            <div class="adm-panel-header-left">
                                <div>
                                    <h5 class="card-title adm-panel-title">Void Requests Need Your Vote</h5>
                                    <p class="adm-panel-sub">A payment is only voided once all admins approve. Click a row to review and vote.</p>
                                </div>
                            </div>
                            <span class="adm-void-count">
                                <i class="feather-rotate-ccw"></i>
                                {{ $pendingVoidCount }} waiting for your vote
                            </span>
                        </div>
                        <div class="card-body custom-card-action p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 adm-table">
                                    <thead>
                                        <tr class="border-b">
                                            <th>Borrower</th>
                                            <th>Installment</th>
                                            <th>Amount</th>
                                            <th>Requested By</th>
                                            <th>Requested</th>
                                            <th class="adm-table-chevron-col"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pendingVoidRequests as $void)
                                            <tr data-href="{{ $void->url }}">
                                                <td>
                                                    <div class="adm-table-borrower">
                                                        <span class="adm-table-name">{{ $void->borrower_name }}</span>
                                                    </div>
                                                </td>
                                                <td class="adm-table-amount">#{{ $void->installment_no }}</td>
                                                <td class="adm-table-amount">&#8369;{{ number_format($void->amount_snapshot, 2) }}</td>
                                                <td class="adm-table-date">{{ $void->requester_name }}</td>
                                                <td class="adm-table-date">{{ \Carbon\Carbon::parse($void->created_at)->diffForHumans() }}</td>
                                                <td class="adm-table-chevron-col">
                                                    <i class="feather-chevron-right adm-table-chevron"></i>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if ($pendingVoidCount > $pendingVoidRequests->count())
                                <div class="adm-void-more">
                                    Showing {{ $pendingVoidRequests->count() }} of {{ $pendingVoidCount }}.
                                    Vote on these first and the rest will show up here.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- ============ ROW 1: PAYMENT OVERVIEW (col-lg-7, dating pwesto ng Loan Applications) ============ --}}
            <div class="col-lg-7">
                <div class="card stretch stretch-full adm-panel adm-overview-card">
                    <div class="card-header adm-panel-header">
                        <div class="adm-panel-header-left">
                            <div>
                                <h5 class="card-title adm-panel-title">Payment Overview</h5>
                                <p class="adm-panel-sub">Summary of loan payments across all active loans.</p>
                            </div>
                        </div>
                        <div class="adm-overview-month">
                            {{ \Carbon\Carbon::parse($overviewMonth)->format('F Y') }}
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="adm-overview-grid">
                            <div class="adm-overview-cell">
                                <div class="adm-overview-label">Collected This Month</div>
                                <div class="adm-overview-value">&#8369;{{ number_format($collectedThisMonth, 2) }}</div>
                                <div class="adm-overview-trend {{ $collectedPctChange >= 0 ? 'is-up' : 'is-down' }}">
                                    <i class="feather-arrow-{{ $collectedPctChange >= 0 ? 'up' : 'down' }}"></i>
                                    {{ abs($collectedPctChange) }}% vs last month
                                </div>
                            </div>
                            <div class="adm-overview-cell">
                                <div class="adm-overview-label">Expected This Month</div>
                                <div class="adm-overview-value">&#8369;{{ number_format($expectedThisMonth, 2) }}</div>
                                <div class="adm-overview-trend {{ $expectedPctChange >= 0 ? 'is-up' : 'is-down' }}">
                                    <i class="feather-arrow-{{ $expectedPctChange >= 0 ? 'up' : 'down' }}"></i>
                                    {{ abs($expectedPctChange) }}% vs last month
                                </div>
                            </div>
                            <div class="adm-overview-cell">
                                <div class="adm-overview-label">Outstanding</div>
                                <div class="adm-overview-value">&#8369;{{ number_format($outstandingThisMonth, 2) }}</div>
                                <div class="adm-overview-trend {{ $outstandingPctChange >= 0 ? 'is-up' : 'is-down' }}">
                                    <i class="feather-arrow-{{ $outstandingPctChange >= 0 ? 'up' : 'down' }}"></i>
                                    {{ abs($outstandingPctChange) }}% vs last month
                                </div>
                            </div>
                            <div class="adm-overview-cell">
                                <div class="adm-overview-label">Overdue</div>
                                <div class="adm-overview-value">&#8369;{{ number_format($overdueThisMonth, 2) }}</div>
                                <div class="adm-overview-trend {{ $overduePctChange == 0 ? 'is-flat' : ($overduePctChange >= 0 ? 'is-up' : 'is-down') }}">
                                    @if($overduePctChange == 0)
                                        &mdash; vs last month
                                    @else
                                        <i class="feather-arrow-{{ $overduePctChange >= 0 ? 'up' : 'down' }}"></i>
                                        {{ abs($overduePctChange) }}% vs last month
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="adm-trend">
                            <div class="adm-trend-head">
                                <h6>Collected — Last 6 Months</h6>
                            </div>
                            <div class="adm-trend-bars">
                                @foreach($paymentTrend as $point)
                                    <div class="adm-trend-col">
                                        <div class="adm-trend-track">
                                            <div class="adm-trend-fill" style="height: {{ $point['pct'] }}%" title="&#8369;{{ number_format($point['collected'], 2) }}"></div>
                                        </div>
                                        <div class="adm-trend-label">{{ $point['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ RECENT PAYMENTS (col-lg-5, di ginalaw) ============ --}}
            <div class="col-lg-5">
                <div class="card stretch stretch-full adm-panel">
                    <div class="card-header adm-panel-header">
                        <div class="adm-panel-header-left">
                            <div>
                                <h5 class="card-title adm-panel-title">Recent Payments</h5>
                                <p class="adm-panel-sub">Latest recorded loan payments.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 adm-overview-table">
                                <thead>
                                    <tr>
                                        <th>Borrower</th>
                                        <th>Amount</th>
                                        <th>Date Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentPayments as $payment)
                                        <tr>
                                            <td class="adm-overview-table-name">{{ $payment->borrower_name }}</td>
                                            <td class="adm-overview-table-amount">&#8369;{{ number_format($payment->amount_paid, 2) }}</td>
                                            <td class="adm-overview-table-date">{{ \Carbon\Carbon::parse($payment->paid_at)->format('M d, Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">No payments recorded yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ ROW 2: LOAN APPLICATIONS (col-lg-6 d-flex, dating pwesto ng Payment Overview) ============ --}}
            <div class="col-lg-6 d-flex">
                <div class="card stretch stretch-full adm-panel h-100 w-100">
                    <div class="card-header adm-panel-header">
                        <div class="adm-panel-header-left">
                            <div>
                                <h5 class="card-title adm-panel-title">Loan Applications</h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body custom-card-action p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 adm-table">
                                <thead>
                                    <tr class="border-b">
                                        <th>Borrower</th>
                                        <th>Loan Amount</th>
                                        <th>Date Applied</th>
                                        <th>Status</th>
                                        <th class="adm-table-chevron-col"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $avatarColors = ['is-blue', 'is-purple', 'is-green', 'is-orange'];
                                    @endphp
                                    @forelse($recentApplications as $app)
                                        @php
                                            $nameParts = preg_split('/\s+/', trim($app->borrower_name));
                                            $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr(end($nameParts), 0, 1));
                                            $avatarColor = $avatarColors[$loop->index % count($avatarColors)];
                                        @endphp
                                     <tr data-href="{{ route('admin.loan-application-detail', $app->uuid) }}">
                                            <td>
                                                <div class="adm-table-borrower">
                                                    <span class="adm-table-name">{{ $app->borrower_name }}</span>
                                                </div>
                                            </td>
                                            <td class="adm-table-amount">&#8369;{{ number_format($app->total_amount, 2) }}</td>
                                            <td class="adm-table-date">{{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</td>
                                            <td>
                                                @php
                                                    $statusColors = [
                                                        'pending'  => 'warning',
                                                        'active'   => 'success',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        'on_hold'  => 'secondary',
                                                    ];
                                                    $color = $statusColors[$app->status] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-soft-{{ $color }} text-{{ $color }}">{{ ucfirst($app->status) }}</span>
                                            </td>
                                            <td class="adm-table-chevron-col">
                                                <i class="feather-chevron-right adm-table-chevron"></i>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No Pending applications.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ RECENT ACTIVITY (col-lg-6 d-flex, di ginalaw) ============ --}}
            <div class="col-lg-6 d-flex">
                <div class="card stretch stretch-full adm-panel h-100 w-100">
                    <div class="card-header adm-panel-header">
                        <div class="adm-panel-header-left">
                            <div>
                                <h5 class="card-title adm-panel-title">Activity Log</h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @forelse($recentActivity as $activity)
                            <div class="adm-activity-row">
                                <div class="adm-activity-body">
                                    <div class="adm-activity-text">{{ $activity['text'] }}</div>
                                    <div class="adm-activity-meta">
                                        @if(!empty($activity['meta']))
                                            {{ $activity['meta'] }} &middot;
                                        @endif
                                        {{ \Carbon\Carbon::parse($activity['ts'])->diffForHumans() }}
                                    </div>
                                </div>
                                <span class="adm-activity-pill bg-soft-{{ $activity['color'] }} text-{{ $activity['color'] }}">
                                    {{ $activity['category'] ?? ucfirst($activity['color']) }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 fs-13">No recent activity yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection