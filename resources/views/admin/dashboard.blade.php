@extends('admin.layout')

@section('title', 'Dashboard')

@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-dashboard.css') }}" />
    
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
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div class="d-flex gap-4 align-items-center">
                                <div class="avatar-text avatar-lg bg-soft-primary text-primary">
                                    <i class="feather-file-text"></i>
                                </div>
                                <div>
                                    <div class="fs-13 fw-semibold text-muted text-truncate-1-line">Total Applications</div>
                                    <h3 class="fw-bold text-dark mt-1">{{ $totalApplications }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fs-12 text-muted">Pending Review</span>
                                <span class="fs-12 fw-semibold text-dark">{{ $pendingApplications }}</span>
                            </div>
                            <div class="progress ht-3">
                                @php
                                    $pendingPct = $totalApplications > 0 ? round(($pendingApplications / $totalApplications) * 100) : 0;
                                @endphp
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pendingPct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div class="d-flex gap-4 align-items-center">
                                <div class="avatar-text avatar-lg bg-soft-success text-success">
                                    <i class="feather-dollar-sign"></i>
                                </div>
                                <div>
                                    <div class="fs-13 fw-semibold text-muted text-truncate-1-line">Active Loans</div>
                                    <h3 class="fw-bold text-dark mt-1">{{ $activeLoans }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fs-12 text-muted">Total Amount</span>
                                <span class="fs-12 fw-semibold text-dark">&#8369;{{ number_format($totalActiveAmount, 2) }}</span>
                            </div>
                            <div class="progress ht-3">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div class="d-flex gap-4 align-items-center">
                                <div class="avatar-text avatar-lg bg-soft-warning text-warning">
                                    <i class="feather-users"></i>
                                </div>
                                <div>
                                    <div class="fs-13 fw-semibold text-muted text-truncate-1-line">Total Borrowers</div>
                                    <h3 class="fw-bold text-dark mt-1">{{ $totalBorrowers }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fs-12 text-muted">Active Borrowers</span>
                                <span class="fs-12 fw-semibold text-dark">{{ $activeBorrowers }}</span>
                            </div>
                            <div class="progress ht-3">
                                @php
                                    $activePct = $totalBorrowers > 0 ? round(($activeBorrowers / $totalBorrowers) * 100) : 0;
                                @endphp
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $activePct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div class="d-flex gap-4 align-items-center">
                                <div class="avatar-text avatar-lg bg-soft-danger text-danger">
                                    <i class="feather-user-check"></i>
                                </div>
                                <div>
                                    <div class="fs-13 fw-semibold text-muted text-truncate-1-line">Pending Verification</div>
                                    <h3 class="fw-bold text-dark mt-1">{{ $pendingVerificationCount }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-top">
                            <a href="{{ route('admin.borrower-verification') }}" class="fs-12 fw-semibold text-primary">
                                Review now <i class="feather-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card stretch stretch-full">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title">Recent Loan Applications</h5>
                        <a href="{{ route('admin.loan-applications') }}" class="fs-12 fw-semibold text-primary">
                            View All <i class="feather-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body custom-card-action p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr class="border-b">
                                        <th>Borrower</th>
                                        <th>Loan Amount</th>
                                        <th>Date Applied</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentApplications as $app)
                                        <tr>
                                            <td><span class="fw-bold text-dark d-block">{{ $app->borrower_name }}</span></td>
                                            <td class="fw-bold text-dark">&#8369;{{ number_format($app->total_amount, 2) }}</td>
                                            <td>{{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</td>
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
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No Pending applications.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card stretch stretch-full">
                    <div class="card-header">
                        <h5 class="card-title">Attention Required</h5>
                    </div>
                    <div class="card-body">
                        @foreach($attentionItems as $item)
                            <div class="attn-item">
                                <div class="attn-icon bg-soft-{{ $item['color'] }} text-{{ $item['color'] }}">
                                    <i class="{{ $item['icon'] }}"></i>
                                </div>
                                <div class="attn-text">
                                    <div class="attn-label">{{ $item['label'] }}</div>
                                    <div class="attn-count">{{ $item['count'] }} {{ $item['count'] == 1 ? 'item' : 'items' }}</div>
                                </div>
                                <a href="{{ $item['url'] }}" class="attn-action text-{{ $item['color'] }}">
                                    {{ $item['action'] }} <i class="feather-arrow-right"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card stretch stretch-full">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title">Upcoming Payments</h5>
                    </div>
                    <div class="card-body">
                        @forelse($upcomingPayments as $payment)
                            <div class="upcoming-row">
                                <div>
                                    <div class="upcoming-name">{{ $payment->borrower_name }}</div>
                                    <div class="upcoming-due">{{ \Carbon\Carbon::parse($payment->due_date)->format('M d, Y') }}</div>
                                </div>
                                <div class="upcoming-amount">&#8369;{{ number_format($payment->amount_due, 2) }}</div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 fs-13">No upcoming payments</div>
                        @endforelse
                    </div>
                    @if($upcomingPayments->isNotEmpty())
                        <div class="card-footer text-center border-top py-3">
                            <a href="{{ route('admin.ongoing-loans') }}" class="fs-12 fw-semibold text-primary">
                                View Active Loans <i class="feather-arrow-right"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card stretch stretch-full">
                    <div class="card-header">
                        <h5 class="card-title">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        @forelse($recentActivity as $activity)
                            <div class="activity-row">
                                <div class="activity-dot bg-soft-{{ $activity['color'] }} text-{{ $activity['color'] }}">
                                    <i class="{{ $activity['icon'] }}"></i>
                                </div>
                                <div>
                                    <div class="activity-text">{{ $activity['text'] }}</div>
                                    <div class="activity-meta">
                                        @if(!empty($activity['meta']))
                                            {{ $activity['meta'] }} &middot;
                                        @endif
                                        {{ \Carbon\Carbon::parse($activity['ts'])->diffForHumans() }}
                                    </div>
                                </div>
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