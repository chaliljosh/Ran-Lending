@extends('admin.layout')

@section('title', 'Dashboard')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-dashboard.css') }}?v={{ time() }}" />
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
                <div class="card stretch stretch-full adm-stat-card">
                    <div class="card-body adm-stat-body">
                        <div class="adm-stat-top">
                            <span class="adm-stat-label">Total Applications</span>
                            <i class="feather-file-text adm-stat-icon"></i>
                        </div>
                        <div class="adm-stat-value">{{ $totalApplications }}</div>
                        <div class="adm-stat-sub">{{ $pendingApplications }} pending review</div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full adm-stat-card">
                    <div class="card-body adm-stat-body">
                        <div class="adm-stat-top">
                            <span class="adm-stat-label">Active Loans</span>
                            <i class="feather-dollar-sign adm-stat-icon"></i>
                        </div>
                        <div class="adm-stat-value">{{ $activeLoans }}</div>
                        <div class="adm-stat-sub">&#8369;{{ number_format($totalActiveAmount, 2) }} total amount</div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full adm-stat-card">
                    <div class="card-body adm-stat-body">
                        <div class="adm-stat-top">
                            <span class="adm-stat-label">Total Borrowers</span>
                            <i class="feather-users adm-stat-icon"></i>
                        </div>
                        <div class="adm-stat-value">{{ $totalBorrowers }}</div>
                        <div class="adm-stat-sub">{{ $activeBorrowers }} active borrowers</div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full adm-stat-card">
                    <div class="card-body adm-stat-body">
                        <div class="adm-stat-top">
                            <span class="adm-stat-label">Pending Verification</span>
                            <i class="feather-user-check adm-stat-icon"></i>
                        </div>
                        <div class="adm-stat-value">{{ $pendingVerificationCount }}</div>
                        <a href="{{ route('admin.borrower-verification') }}" class="adm-stat-sub adm-stat-link">
                            Review now
                        </a>
                    </div>
                </div>
            </div>

            {{-- ============ RECENT LOAN APPLICATIONS ============ --}}
            <div class="col-lg-8">
                <div class="card stretch stretch-full adm-panel">
                    <div class="card-header adm-panel-header">
                        <div class="adm-panel-header-left">
                          
                            <div>
                                <h5 class="card-title adm-panel-title">Recent Loan Applications</h5>
                              
                            </div>
                        </div>
                        <a href="{{ route('admin.loan-applications') }}" class="adm-panel-link">
                            View all 
                        </a>
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
                                        <tr>
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

            {{-- ============ ATTENTION REQUIRED ============ --}}
            <div class="col-lg-4">
                <div class="card stretch stretch-full adm-panel adm-attn-card">
                    <div class="card-header adm-panel-header">
                        <div class="adm-panel-header-left">
                        
                            <div>
                                <h5 class="card-title adm-panel-title">Attention Required</h5>
                            </div>
                        </div>
                        @if(count($attentionItems) > 0)
                            <span class="adm-attn-count-badge">{{ count($attentionItems) }}</span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @foreach($attentionItems as $item)
                            <div class="adm-attn-item border-{{ $item['color'] }}">
                                <div class="adm-attn-item-body">
                                    <div class="adm-attn-item-title">{{ $item['label'] }}</div>
                                    @if(!empty($item['description']))
                                        <div class="adm-attn-item-desc">{{ $item['description'] }}</div>
                                    @endif
                                </div>
                                <div class="adm-attn-item-side">
                                    <a href="{{ $item['url'] }}" class="adm-attn-item-action text-{{ $item['color'] }}">
                                        {{ $item['action'] }} 
                                    </a>
                                    <div class="adm-attn-item-count">{{ $item['count'] }} {{ $item['count'] == 1 ? 'item' : 'items' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ============ UPCOMING PAYMENTS ============ --}}
            <div class="col-lg-6">
                <div class="card stretch stretch-full adm-panel">
                    <div class="card-header adm-panel-header">
                        <div class="adm-panel-header-left">
                           
                            <div>
                                <h5 class="card-title adm-panel-title">Upcoming Payments</h5>
                          
                            </div>
                        </div>
                        <a href="{{ route('admin.ongoing-loans') }}" class="adm-panel-link">
                            View all
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @forelse($upcomingPayments as $payment)
                            @php
                                $dueCarbon = \Carbon\Carbon::parse($payment->due_date);
                                $daysDiff = now()->startOfDay()->diffInDays($dueCarbon->copy()->startOfDay(), false);
                            @endphp
                            <div class="adm-upcoming-row">
                                <div class="adm-upcoming-date">
                                    <span class="adm-upcoming-date-month">{{ $dueCarbon->format('M') }}</span>
                                    <span class="adm-upcoming-date-day">{{ $dueCarbon->format('d') }}</span>
                                </div>
                                <div class="adm-upcoming-body">
                                    <div class="adm-upcoming-name">{{ $payment->borrower_name }}</div>
                                    <div class="adm-upcoming-sub">
                                        Monthly Amortization
                                        @if(!empty($payment->loan_number))
                                            &middot; Loan #{{ $payment->loan_number }}
                                        @endif
                                    </div>
                                </div>
                                <div class="adm-upcoming-side">
                                    <div class="adm-upcoming-amount">&#8369;{{ number_format($payment->amount_due, 2) }}</div>
                                    @if($daysDiff < 0)
                                        <span class="adm-upcoming-pill is-danger">{{ abs($daysDiff) }} {{ abs($daysDiff) == 1 ? 'day' : 'days' }} overdue</span>
                                    @else
                                        <span class="adm-upcoming-pill">Due in {{ $daysDiff }} {{ $daysDiff == 1 ? 'day' : 'days' }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 fs-13">No upcoming payments</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ============ RECENT ACTIVITY ============ --}}
            <div class="col-lg-6">
                <div class="card stretch stretch-full adm-panel">
                    <div class="card-header adm-panel-header">
                        <div class="adm-panel-header-left">
                     
                            <div>
                                <h5 class="card-title adm-panel-title">Recent Activity</h5>
                               
                            </div>
                        </div>
                        <a href="{{ route('admin.dashboard') }}" class="adm-panel-link">
                            View all
                        </a>
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