@extends('borrower.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Dashboard</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Dashboard</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

        @if($detailsStatus === 'incomplete')
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card text-center" style="">
                    <div class="card-body py-5">
                        <div style="width:80px; height:80px; background:#e8f0fe; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                            <i class="feather-user-check" style="font-size:36px; color:#4a7cf7;"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Complete Your Profile</h4>
                        <p class="text-muted mb-4">Please fill up your personal details first before you can access the full dashboard and apply for a loan.</p>
                        <a href="{{ route('borrower.personal-details') }}" class="btn btn-primary px-5">
                            <i class="feather-edit me-2"></i>Fill Up Personal Details
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @elseif($detailsStatus === 'pending')
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card text-center">
                    <div class="card-body py-5">
                        <div style="width:80px; height:80px; background:#F97316; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                            <i class="feather-clock" style="font-size:36px; color:#FFFFFF;"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Under Review</h4>
                        <p class="text-muted mb-4">Your personal details have been submitted and are currently being reviewed by our admins. Please wait for approval.</p>
                        <div style="background:#f8f9fa; border-radius:12px; padding:20px; margin-bottom:20px;">
                            <p class="fw-bold mb-2" style="font-size:0.85rem; color:#6c757d; text-transform:uppercase; letter-spacing:0.5px;">Admin Approvals</p>
                            <h2 style="font-size:2.5rem; font-weight:700; margin:0;">{{ $approvedCount }} / 3</h2>
                        </div>
                        <p class="text-muted fs-12">You will be notified once your account is fully approved.</p>
                    </div>
                </div>
            </div>
        </div>

        @elseif($detailsStatus === 'rejected')
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card text-center" style="border: 2px solid #e74c3c;">
                    <div class="card-body py-5">
                        <div style="width:80px; height:80px; background:#fee2e2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                            <i class="feather-x-circle" style="font-size:36px; color:#e74c3c;"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Application Rejected</h4>
                        <p class="text-muted mb-4">Unfortunately, your personal details were not approved. Please update your information and resubmit.</p>
                        <a href="{{ route('borrower.personal-details') }}" class="btn btn-danger px-5">
                            <i class="feather-edit me-2"></i>Update Personal Details
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @else
        <div class="row">
            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div class="d-flex gap-4 align-items-center">
                                <div class="avatar-text avatar-lg bg-gray-200">
                                    <i class="feather-file-text"></i>
                                </div>
                                <div>
                                    <span class="fs-13 fw-semibold text-muted">Loan Application</span>
                                    <h3>{{ $loanStatus['label'] }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="progress ht-3">
                            <div class="progress-bar {{ $loanStatus['bar'] }}" style="width:{{ $loanStatus['progress'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex gap-4 align-items-center mb-4">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-calendar"></i>
                            </div>
                            <div>
                                <span class="fs-13 fw-semibold text-muted">Loan Term</span>
                                <h3>{{ $latestLoan ? $latestLoan->loan_term . ' Months' : '—' }}</h3>
                            </div>
                        </div>
                        <p class="fs-12 text-muted">
                            {{ $latestLoan ? 'Interest Rate: ' . number_format($latestLoan->interest_rate, 2) . '% monthly' : 'No active loan term yet' }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex gap-4 align-items-center mb-4">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-credit-card"></i>
                            </div>
                            <div>
                                <span class="fs-13 fw-semibold text-muted">Outstanding Balance</span>
                                <h3>{{ $latestLoan ? '₱' . number_format($latestLoan->total_amount, 2) : '₱0.00' }}</h3>
                            </div>
                        </div>
                        <p class="fs-12 text-muted">
                            {{ $latestLoan ? 'Principal + Interest' : 'No active loan' }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-md-6">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex gap-4 align-items-center mb-4">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-user-check"></i>
                            </div>
                            <div>
                                <span class="fs-13 fw-semibold text-muted">Account Status</span>
                                <h3 class="text-success">Active</h3>
                            </div>
                        </div>
                        <p class="fs-12 text-muted">Eligible for loan processing</p>
                    </div>
                </div>
            </div>

            <div class="col-xxl-8">
                <div class="card stretch stretch-full">
                    <div class="card-header"><h5 class="card-title">Application Timeline</h5></div>
                    <div class="card-body fs-13">
                        @if(!$latestLoan)
                            <p class="text-muted mb-0">You haven't applied for a loan yet.</p>
                        @else
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="feather-check-circle text-success me-2"></i>
                                    Application Submitted —
                                    <span class="text-muted">{{ \Carbon\Carbon::parse($latestLoan->applied_at)->format('F j, Y') }}</span>
                                </li>
                                <li class="mb-0">
                                    <i class="feather-info me-2"></i>
                                    Current Status: <strong>{{ $loanStatus['label'] }}</strong>
                                </li>
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card stretch stretch-full">
                    <div class="card-header"><h6>Notifications</h6></div>
                    <div class="card-body fs-13">
                        @if(!$app)
                            <p class="text-muted mb-0">You don't have an active loan yet, so there are no payment reminders.</p>
                        @elseif(empty($notifications))
                            <p class="text-success mb-0"><i class="feather-check-circle me-1"></i>You're all caught up — no payments due soon.</p>
                        @else
                            <p class="mb-2">{{ $notifications[0]['message'] }}</p>
                            <a href="{{ route('borrower.notifications') }}" class="fs-12 fw-semibold">View all notifications →</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
@endsection