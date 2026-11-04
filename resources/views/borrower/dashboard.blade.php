@extends('borrower.layout')

@section('title', 'Dashboard')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-onboarding.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-pending-review.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-status.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-rejected.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/borrower-fully-paid.css') }}?v={{ time() }}">
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Dashboard</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Dashboard</li>
            </ul>
        </div>
    </div>

    <div class="main-content">

@if ($detailsStatus === 'incomplete')
    @include('borrower.partials.onboarding')

@elseif(!$app)
    @php
        $isRejectedLoan  = $latestLoan && $latestLoan->status === 'rejected';
        $isApprovedLoan  = $latestLoan && in_array($latestLoan->status, ['approved', 'active'], true);
        $isCompletedLoan = $latestLoan && $latestLoan->status === 'completed';
    @endphp

    @if (!$latestLoan)
        @php
            $firstName = trim(explode(' ', trim($userName ?? ''))[0] ?? '');
            $eligibleAmount = $eligibleAmount ?? 15000;
            $eligibleTermMonths = $eligibleTermMonths ?? 6;
        @endphp

        <div class="bwl-dash-welcome">
            <div class="bwl-dash-welcome-text">
                <p class="bwl-dash-welcome-eyebrow">Welcome</p>
                <h4 class="bwl-dash-welcome-name">{{ $userName ?: 'there' }} 👋</h4>
                <p class="bwl-dash-welcome-sub">Manage your loan applications and account details here.</p>
            </div>
            <div class="bwl-dash-welcome-art">
                <span class="bwl-dash-welcome-tagline">Your goals are within reach.</span>
                <img
                    class="bwl-dash-welcome-illustration"
                    src="{{ asset('assets/borrower/images/dashboard-welcome-illustration.png') }}"
                    alt=""
                    loading="lazy"
                >
            </div>
        </div>

        <div class="bwl-dash-top-row">

            <div class="bwl-dash-elig">
                <div class="bwl-dash-elig-head">
                    <span class="bwl-dash-elig-eyebrow">Loan Eligibility</span>
                    <span class="bwl-dash-elig-badge">Eligible</span>
                </div>
                <h5 class="bwl-dash-elig-title">You're Eligible for a Loan</h5>
                <p class="bwl-dash-elig-sub">You can now submit a loan application.</p>

                <div class="bwl-dash-elig-stats">
                    <div class="bwl-dash-elig-stat">
                        <span class="bwl-dash-elig-stat-label">Eligible Amount</span>
                        <span class="bwl-dash-elig-stat-value">₱{{ number_format($eligibleAmount, 0) }}</span>
                    </div>
                    <div class="bwl-dash-elig-stat">
                        <span class="bwl-dash-elig-stat-label">Repayment Term</span>
                        <span class="bwl-dash-elig-stat-value">Up to {{ $eligibleTermMonths }} months</span>
                    </div>
                </div>

                <a href="{{ route('borrower.apply-loan') }}" class="bwl-dash-elig-btn">Apply for a Loan</a>
                <p class="bwl-dash-elig-note">Final loan amount and terms are subject to application review.</p>
            </div>

            <div class="bwl-dash-loanstatus">
                <span class="bwl-dash-loanstatus-eyebrow">Your Loan Status</span>
                <p class="bwl-dash-loanstatus-sub">Current status of your loan application.</p>
                <div class="bwl-dash-loanstatus-icon"><i class="feather-file-text"></i></div>
                <p class="bwl-dash-loanstatus-title">No Active Application</p>
                <p class="bwl-dash-loanstatus-desc">You haven't applied for a loan yet.</p>
                <a href="{{ route('borrower.apply-loan') }}" class="bwl-dash-loanstatus-btn">Apply for a Loan</a>
            </div>

            <div class="bwl-dash-quick">
                <span class="bwl-dash-quick-eyebrow">Quick Actions</span>
                <p class="bwl-dash-quick-sub">Common tasks and shortcuts.</p>
                <div class="bwl-dash-quick-list">
                    <a href="{{ route('borrower.apply-loan') }}" class="bwl-dash-quick-item is-primary">
                        <span class="bwl-dash-quick-text">
                            <span class="bwl-dash-quick-title">Apply for a Loan</span>
                            <span class="bwl-dash-quick-desc">Start a new application</span>
                        </span>
                        <i class="feather-arrow-right bwl-dash-quick-arrow"></i>
                    </a>
                    <a href="{{ route('borrower.loan-details') }}" class="bwl-dash-quick-item">
                        <span class="bwl-dash-quick-text">
                            <span class="bwl-dash-quick-title">View Loan Details</span>
                            <span class="bwl-dash-quick-desc">Check your application history</span>
                        </span>
                        <i class="feather-arrow-right bwl-dash-quick-arrow"></i>
                    </a>
                    <a href="{{ route('borrower.profile') }}" class="bwl-dash-quick-item">
                        <span class="bwl-dash-quick-text">
                            <span class="bwl-dash-quick-title">My Profile</span>
                            <span class="bwl-dash-quick-desc">Manage your personal information</span>
                        </span>
                        <i class="feather-arrow-right bwl-dash-quick-arrow"></i>
                    </a>
                    <a href="{{ route('borrower.faq') }}" class="bwl-dash-quick-item">
                        <span class="bwl-dash-quick-text">
                            <span class="bwl-dash-quick-title">Help &amp; FAQ</span>
                            <span class="bwl-dash-quick-desc">Get support and find answers</span>
                        </span>
                        <i class="feather-arrow-right bwl-dash-quick-arrow"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="bwl-dash-bottom-row">
            <div class="bwl-dash-journey">
                <span class="bwl-dash-journey-eyebrow">Your Loan Journey</span>
                <p class="bwl-dash-journey-sub">Track your progress from application to loan release.</p>

                <div class="bwl-dash-journey-steps">
                    <div class="bwl-dash-journey-step is-done">
                        <span class="bwl-dash-journey-dot">1</span>
                        <span class="bwl-dash-journey-title">Account Verified</span>
                        <span class="bwl-dash-journey-desc">Your account is active.</span>
                    </div>
                    <div class="bwl-dash-journey-line is-done"></div>
                    <div class="bwl-dash-journey-step is-current">
                        <span class="bwl-dash-journey-dot">2</span>
                        <span class="bwl-dash-journey-title">Eligible for Loan</span>
                        <span class="bwl-dash-journey-desc">You can now apply.</span>
                    </div>
                    <div class="bwl-dash-journey-line"></div>
                    <div class="bwl-dash-journey-step">
                        <span class="bwl-dash-journey-dot">3</span>
                        <span class="bwl-dash-journey-title">Submit Application</span>
                        <span class="bwl-dash-journey-desc">Complete your loan application.</span>
                    </div>
                    <div class="bwl-dash-journey-line"></div>
                    <div class="bwl-dash-journey-step">
                        <span class="bwl-dash-journey-dot">4</span>
                        <span class="bwl-dash-journey-title">Get a Decision</span>
                        <span class="bwl-dash-journey-desc">We'll review and notify you.</span>
                    </div>
                </div>
            </div>

            <div class="bwl-dash-activity">
                <div class="bwl-dash-activity-head">
                    <div>
                        <span class="bwl-dash-activity-eyebrow">Recent Activity</span>
                        <p class="bwl-dash-activity-sub">Your latest account activity and updates.</p>
                    </div>
                </div>
                <div class="bwl-dash-activity-list">
                    @forelse(($recentActivity ?? []) as $act)
                        <div class="bwl-dash-activity-item">
                            <span class="bwl-dash-activity-dot"></span>
                            <div class="bwl-dash-activity-body">
                                <span class="bwl-dash-activity-title">{{ $act['title'] }}</span>
                                <span class="bwl-dash-activity-date">{{ $act['date'] }}</span>
                            </div>
                            <span class="bwl-dash-activity-badge">{{ $act['badge'] }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0 p-3">No recent activity yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

    @elseif ($isCompletedLoan)
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="bwl-tracker-card">
                    <p class="bwl-tracker-eyebrow">Loan Application</p>

                    <div class="bwl-tracker-steps">
                        <div class="bwl-tracker-step is-done">
                            <span class="bwl-tracker-dot">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span class="bwl-tracker-label">Application Submitted</span>
                        </div>

                        <div class="bwl-tracker-line is-done"></div>

                        <div class="bwl-tracker-step is-done">
                            <span class="bwl-tracker-dot">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span class="bwl-tracker-label">Loan Activated</span>
                        </div>

                        <div class="bwl-tracker-line is-done"></div>

                        <div class="bwl-tracker-step is-done">
                            <span class="bwl-tracker-dot">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span class="bwl-tracker-label">Fully Paid</span>
                        </div>
                    </div>

                    <div class="bwl-tracker-summary">
                        Application #{{ $latestLoan->application_id }}
                        <span class="bwl-tracker-dot-sep">&middot;</span>
                        ₱{{ number_format($latestLoan->loan_amount, 2) }}
                        <span class="bwl-tracker-dot-sep">&middot;</span>
                        Submitted {{ \Carbon\Carbon::parse($latestLoan->applied_at)->format('M j, Y') }}
                    </div>

                    <p class="bwl-tracker-message">
                        Congratulations! Your loan has been fully repaid. You're now eligible to apply for a new loan.
                    </p>

                    <a href="{{ route('borrower.apply-loan') }}" class="bwl-tracker-link">
                        Apply for a New Loan
                    </a>
                </div>
            </div>
        </div>

    @elseif ($isApprovedLoan)
        <div class="bwl-wr-card">
            <div class="bwl-wr-hero">
                <img class="bwl-wr-hero-img"
                    src="{{ asset('assets/borrower/images/loan-approved-illustration.png') }}" alt=""
                    loading="lazy">
                <div class="bwl-wr-hero-text">
                    <h2 class="bwl-wr-title">Your loan has been approved!</h2>
                    <p class="bwl-wr-desc">All required approvals are complete. Your loan is now waiting for
                        release. Your payment schedule will be available once the funds have been released.</p>
                </div>
            </div>

            <div class="bwl-wr-steps">
                <div class="bwl-wr-step is-done">
                    <span class="bwl-wr-dot"><i class="feather-check"></i></span>
                    <span class="bwl-wr-label">Application Submitted</span>
                </div>
                <div class="bwl-wr-line is-done"></div>
                <div class="bwl-wr-step is-done">
                    <span class="bwl-wr-dot"><i class="feather-check"></i></span>
                    <span class="bwl-wr-label">Under Review</span>
                </div>
                <div class="bwl-wr-line is-done"></div>
                <div class="bwl-wr-step is-done">
                    <span class="bwl-wr-dot"><i class="feather-check"></i></span>
                    <span class="bwl-wr-label">Approved</span>
                </div>
                <div class="bwl-wr-line is-current"></div>
                <div class="bwl-wr-step is-current">
                    <span class="bwl-wr-dot"><i class="feather-clock"></i></span>
                    <span class="bwl-wr-label">Loan Release</span>
                </div>
            </div>

            <div class="bwl-wr-summary">
                <div class="bwl-wr-summary-item">
                    <span class="bwl-wr-summary-label">Application #</span>
                    <strong class="bwl-wr-summary-value">#{{ $latestLoan->application_id }}</strong>
                </div>
                <div class="bwl-wr-summary-item">
                    <span class="bwl-wr-summary-label">Loan Amount</span>
                    <strong class="bwl-wr-summary-value">₱{{ number_format($latestLoan->loan_amount, 2) }}</strong>
                </div>
                <div class="bwl-wr-summary-item">
                    <span class="bwl-wr-summary-label">Submitted On</span>
                    <strong class="bwl-wr-summary-value">{{ \Carbon\Carbon::parse($latestLoan->applied_at)->format('M j, Y') }}</strong>
                </div>
            </div>

            <div class="bwl-wr-bottom">
                <div class="bwl-wr-next">
                    <img class="bwl-wr-next-img"
                        src="{{ asset('assets/borrower/images/loan-release-calendar.png') }}" alt=""
                        loading="lazy">
                    <div>
                        <h3 class="bwl-wr-box-title">What's Next?</h3>
                        <p class="bwl-wr-box-desc">Just wait for the release confirmation from Ran Serenity.
                            We'll notify you once it's available.</p>
                    </div>
                </div>
               
            </div>
        </div>

   @elseif ($latestLoan->status === 'on_hold')
    @php
        $stAppCode = !empty($latestLoan->uuid)
            ? 'LN-' . strtoupper(substr($latestLoan->uuid, 0, 8))
            : '#' . $latestLoan->application_id;
        $stHasImg = file_exists(public_path('assets/borrower/images/loan-on-hold-illustration.png'));
        $stHasSupportImg = file_exists(public_path('assets/borrower/images/loan-support-illustration.png'));
    @endphp

    <div class="bwl-st">
        <div class="bwl-st-hero">
            <div class="bwl-st-main">
                <div class="bwl-st-art is-wide">
                    @if ($stHasImg)
                        <img src="{{ asset('assets/borrower/images/loan-on-hold-illustration.png') }}" alt="" loading="lazy">
                    @else
                        <svg viewBox="0 0 200 200" fill="none" aria-hidden="true">
                            <circle cx="100" cy="100" r="86" fill="#E3ECFB"/>
                            <rect x="56" y="36" width="88" height="124" rx="10" fill="#fff" stroke="#0B1F4D" stroke-width="3"/>
                            <circle cx="100" cy="84" r="22" fill="#fff" stroke="#0B1F4D" stroke-width="4"/>
                            <polyline points="100 70 100 84 110 90" stroke="#0B1F4D" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            <rect x="74" y="124" width="52" height="6" rx="3" fill="#C9D6EE"/>
                            <rect x="74" y="138" width="36" height="6" rx="3" fill="#C9D6EE"/>
                        </svg>
                    @endif
                </div>

                <div class="bwl-st-text">
                    <span class="bwl-st-pill is-hold has-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        On Hold
                    </span>
                    <h2 class="bwl-st-title">Your application is on hold</h2>
                    <p class="bwl-st-desc">Your loan application is currently on hold due to additional information or document requirements. Please check your account for further instructions.</p>
                    <a href="{{ route('borrower.loan-details') }}" class="bwl-st-btn">
                        View Application Details
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bwl-st-summary">
                <h3 class="bwl-st-summary-title">Application Summary</h3>
                <div class="bwl-st-row">
                    <span>Application #</span>
                    <strong>{{ $stAppCode }}</strong>
                </div>
                <div class="bwl-st-row">
                    <span>Loan Amount</span>
                    <strong>₱{{ number_format($latestLoan->loan_amount, 2) }}</strong>
                </div>
                <div class="bwl-st-row">
                    <span>Date Applied</span>
                    <strong>{{ \Carbon\Carbon::parse($latestLoan->applied_at)->format('M d, Y') }}</strong>
                </div>
            </div>
        </div>

        <div class="bwl-st-note is-hold">
            <span class="bwl-st-note-icon">!</span>
            <div class="bwl-st-note-body">
               <h4>What happens next?</h4>
<p>Our team will call you to go over your application. Any needed updates will be made on our end, and we'll notify you once the hold is lifted.</p>
            </div>
        </div>

              <div class="bwl-st-bottom">
            <div class="bwl-st-help">
                <span class="bwl-st-reason-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="9" y1="13" x2="15" y2="13"></line>
                        <line x1="9" y1="17" x2="13" y2="17"></line>
                    </svg>
                </span>
                <div class="bwl-st-help-body">
                    <h3 class="bwl-st-help-title">Reason for hold</h3>
                    @if (!empty($latestLoan->hold_reason))
                        <p class="bwl-st-reason-text">{!! nl2br(e($latestLoan->hold_reason)) !!}</p>
                    @else
                        <p class="bwl-st-help-desc mb-0">Our team will contact you to discuss the details of your application.</p>
                    @endif
                </div>
            </div>

            <div class="bwl-st-progress">
                <h3 class="bwl-st-progress-title">Application Progress</h3>
                <div class="bwl-st-steps">
                    <div class="bwl-st-step is-done">
                        <span class="bwl-st-step-dot">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="bwl-st-step-label">Application<br>Submitted</span>
                    </div>
                    <div class="bwl-st-step-line is-done"></div>
                    <div class="bwl-st-step is-current">
                        <span class="bwl-st-step-dot">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </span>
                        <span class="bwl-st-step-label">On Hold</span>
                    </div>
                    <div class="bwl-st-step-line"></div>
                    <div class="bwl-st-step">
                        <span class="bwl-st-step-dot"></span>
                        <span class="bwl-st-step-label">Decision<br>Pending</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @elseif ($isRejectedLoan)
        @php
            $rjAppCode = !empty($latestLoan->uuid)
                ? 'LN-' . strtoupper(substr($latestLoan->uuid, 0, 8))
                : '#' . $latestLoan->application_id;
            $rjHasImg = file_exists(public_path('assets/borrower/images/loan-rejected-illustration.png'));

            // Optional: ipasa mula sa controller. Halimbawa:
            // $adminDecisions = [
            //     ['label' => 'Admin 1', 'decision' => 'rejected'],
            //     ['label' => 'Admin 2', 'decision' => 'rejected'],
            //     ['label' => 'Admin 3', 'decision' => 'approved'],
            // ];
            // decision: approved | rejected | on_hold | pending
            $rjDecisions = collect($adminDecisions ?? [])->take(3)->values();
            $rjDecisionText = [
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'on_hold'  => 'On hold',
                'pending'  => 'Pending',
            ];

            $rjReason = trim((string) ($latestLoan->rejection_reason ?? ''));
            if ($rjReason === '') {
                $rjReason = 'Your application did not meet the current approval requirements.';
            }

            $rjReviewClass = $rjDecisions->isEmpty() ? 'is-solo' : '';
        @endphp

        <div class="bwl-rj">

            {{-- Hero + Application Summary --}}
            <div class="bwl-rj-hero">
                <div class="bwl-rj-main">
                    <div class="bwl-rj-art">
                        @if ($rjHasImg)
                            <img src="{{ asset('assets/borrower/images/loan-rejected-illustration.png') }}" alt="" loading="lazy">
                        @else
                            <svg viewBox="0 0 200 200" fill="none" aria-hidden="true">
                                <circle cx="100" cy="100" r="86" fill="#E3ECFB"/>
                                <rect x="56" y="36" width="88" height="124" rx="10" fill="#fff" stroke="#0B1F4D" stroke-width="3"/>
                                <line x1="84" y1="68" x2="116" y2="100" stroke="#D92D3A" stroke-width="7" stroke-linecap="round"/>
                                <line x1="116" y1="68" x2="84" y2="100" stroke="#D92D3A" stroke-width="7" stroke-linecap="round"/>
                                <rect x="74" y="124" width="52" height="6" rx="3" fill="#C9D6EE"/>
                                <rect x="74" y="138" width="36" height="6" rx="3" fill="#C9D6EE"/>
                            </svg>
                        @endif
                    </div>

                    <div class="bwl-rj-text">
                        <span class="bwl-rj-pill">
                            <span class="bwl-rj-pill-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </span>
                            Rejected
                        </span>
                        <h2 class="bwl-rj-title">Your loan application was not approved</h2>
                        <p class="bwl-rj-desc">Unfortunately, your application did not meet the current approval requirements. You may review the details or apply again when you're eligible.</p>
                        <a href="{{ route('borrower.loan-details') }}" class="bwl-rj-btn">
                            View Application Details
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="bwl-rj-summary">
                    <h3 class="bwl-rj-summary-title">Application Summary</h3>
                    <div class="bwl-rj-row">
                        <span>Application #</span>
                        <strong>{{ $rjAppCode }}</strong>
                    </div>
                    <div class="bwl-rj-row">
                        <span>Loan Amount</span>
                        <strong>₱{{ number_format($latestLoan->loan_amount, 2) }}</strong>
                    </div>
                    <div class="bwl-rj-row">
                        <span>Date Applied</span>
                        <strong>{{ \Carbon\Carbon::parse($latestLoan->applied_at)->format('M d, Y') }}</strong>
                    </div>
                </div>
            </div>

            {{-- Review Summary + Reason --}}
            <div class="bwl-rj-review {{ $rjReviewClass }}">
                @if ($rjDecisions->isNotEmpty())
                    <div class="bwl-rj-cell">
                        <h3 class="bwl-rj-cell-title">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="9" y1="13" x2="15" y2="13"></line>
                                <line x1="9" y1="17" x2="13" y2="17"></line>
                            </svg>
                            Review Summary
                        </h3>
                        <div class="bwl-rj-admins">
                            @foreach ($rjDecisions as $i => $d)
                                @php
                                    $dec = $d['decision'] ?? 'pending';
                                    if (!isset($rjDecisionText[$dec])) { $dec = 'pending'; }
                                @endphp
                                <div class="bwl-rj-admin">
                                    <span class="bwl-rj-dec-icon is-{{ $dec }}">
                                        @if ($dec === 'rejected')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        @elseif ($dec === 'approved')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        @else
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        @endif
                                    </span>
                                    <span class="bwl-rj-admin-name">{{ $d['label'] ?? 'Admin ' . ($i + 1) }}</span>
                                    <span class="bwl-rj-admin-result is-{{ $dec }}">{{ $rjDecisionText[$dec] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bwl-rj-cell bwl-rj-final">
                        <h3 class="bwl-rj-cell-title">Final Decision</h3>
                        <span class="bwl-rj-final-pill">
                            <span class="bwl-rj-dec-icon is-rejected">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </span>
                            Rejected
                        </span>
                    </div>
                @endif

                <div class="bwl-rj-cell">
                    <h3 class="bwl-rj-cell-title">Reason for Rejection</h3>
                    <p class="bwl-rj-reason-text">{!! nl2br(e($rjReason)) !!}</p>
                </div>
            </div>

            {{-- Ready to apply again --}}
            <div class="bwl-rj-again">
                <span class="bwl-rj-again-icon">
                    <svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="8" y1="13" x2="16" y2="13"></line>
                        <line x1="8" y1="17" x2="13" y2="17"></line>
                    </svg>
                </span>
                <div class="bwl-rj-again-body">
                    <h3 class="bwl-rj-again-title">Ready to apply again?</h3>
                    <p class="bwl-rj-again-desc">You may submit a new loan application if you wish to apply for another loan.</p>
                </div>
                <a href="{{ route('borrower.apply-loan') }}" class="bwl-rj-btn">Apply for a New Loan</a>
            </div>

        </div>

    @else
        @php
            $prAppCode = !empty($latestLoan->uuid)
                ? 'LN-' . strtoupper(substr($latestLoan->uuid, 0, 8))
                : '#' . $latestLoan->application_id;
            $prHasHeroImage = file_exists(public_path('assets/borrower/images/loan-pending-illustration.png'));
        @endphp

        <div class="bwl-pr">

            <div class="bwl-pr-hero">
                <div class="bwl-pr-hero-art">
                    @if ($prHasHeroImage)
                        <img src="{{ asset('assets/borrower/images/loan-pending-illustration.png') }}" alt=""
                            loading="lazy">
                    @else
                        <svg viewBox="0 0 280 240" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <ellipse cx="140" cy="214" rx="104" ry="12" fill="#dbe5f7"/>
                            <circle cx="150" cy="112" r="92" fill="#e3ecfb"/>
                            <rect x="74" y="34" width="132" height="168" rx="12" fill="#ffffff" stroke="#0f2a5c" stroke-width="3"/>
                            <circle cx="140" cy="84" r="26" fill="#0f2a5c"/>
                            <polyline points="127 84 136 93 154 74" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                            <rect x="96" y="128" width="88" height="7" rx="3.5" fill="#c9d6ee"/>
                            <rect x="96" y="144" width="88" height="7" rx="3.5" fill="#c9d6ee"/>
                            <rect x="96" y="160" width="60" height="7" rx="3.5" fill="#c9d6ee"/>
                            <circle cx="198" cy="170" r="24" fill="#ffffff" stroke="#f97316" stroke-width="5"/>
                            <line x1="215" y1="188" x2="236" y2="210" stroke="#f97316" stroke-width="7" stroke-linecap="round"/>
                            <polyline points="198 158 198 170 207 176" stroke="#f97316" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M44 206c-4-22-2-40 10-56 2 20 2 38-10 56z" fill="#7cb4e6"/>
                            <path d="M54 206c8-18 18-30 34-36-4 18-14 30-34 36z" fill="#4d8fd0"/>
                            <path d="M52 52l-9-9M64 40l-2-12M40 66l-12-3" stroke="#f97316" stroke-width="3.5" stroke-linecap="round"/>
                        </svg>
                    @endif
                </div>

                <div class="bwl-pr-hero-text">
                    <div class="bwl-pr-eyebrow-row">
                        <span class="bwl-pr-eyebrow">Loan Application</span>
                        <span class="bwl-pr-pill">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Pending Approval
                        </span>
                    </div>

                    <h2 class="bwl-pr-title">Your application is under review</h2>
                    <p class="bwl-pr-desc">
                        Your application has been submitted successfully and is awaiting review by our
                        administrators. We'll notify you once a decision has been made.
                    </p>
                </div>
            </div>

            <div class="bwl-pr-steps">
                <div class="bwl-pr-step is-done">
                    <span class="bwl-pr-dot">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <span class="bwl-pr-label">Application Submitted</span>
                </div>

                <div class="bwl-pr-line is-done"></div>

                <div class="bwl-pr-step is-current">
                    <span class="bwl-pr-dot">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </span>
                    <span class="bwl-pr-label">Pending Approval</span>
                </div>

                <div class="bwl-pr-line"></div>

                <div class="bwl-pr-step">
                    <span class="bwl-pr-dot"></span>
                    <span class="bwl-pr-label">Decision</span>
                </div>
            </div>

            <div class="bwl-pr-bottom">
                <div class="bwl-pr-card">
                    <h3 class="bwl-pr-card-title">Application Summary</h3>
                    <div class="bwl-pr-summary">
                        <div class="bwl-pr-summary-item">
                            <span class="bwl-pr-summary-label">Application #</span>
                            <strong class="bwl-pr-summary-value">{{ $prAppCode }}</strong>
                        </div>
                        <div class="bwl-pr-summary-item">
                            <span class="bwl-pr-summary-label">Loan Amount</span>
                            <strong class="bwl-pr-summary-value">₱{{ number_format($latestLoan->loan_amount, 2) }}</strong>
                        </div>
                        <div class="bwl-pr-summary-item">
                            <span class="bwl-pr-summary-label">Date Applied</span>
                            <strong class="bwl-pr-summary-value">{{ \Carbon\Carbon::parse($latestLoan->applied_at)->format('M d, Y') }}</strong>
                        </div>
                    </div>
                </div>

                <div class="bwl-pr-card bwl-pr-next">
                    <div class="bwl-pr-next-art">
                        <img src="{{ asset('assets/borrower/images/loan-release-calendar.png') }}" alt=""
                            loading="lazy">
                    </div>
                    <div class="bwl-pr-next-body">
                        <h3 class="bwl-pr-next-title">What happens next?</h3>
                        <p class="bwl-pr-next-desc">No action is needed right now.<br>You can check your
                            application status anytime.</p>
                        <div class="bwl-pr-actions">
                            <a href="{{ route('borrower.loan-details') }}" class="bwl-pr-btn">View Application Status</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    @endif

@elseif ($totalCount > 0 && $paidCount >= $totalCount)
    {{-- Fully paid: lahat ng installment ay bayad na --}}
    @include('borrower.partials.dashboard-fully-paid')

@else
    <div class="bwl-dash-widgets">
        <div class="bwl-dash-widget">
            <div class="bwl-dash-widget-top">
                <div class="bwl-dash-widget-label">Outstanding Balance</div>
                <div class="bwl-dash-widget-icon"><i class="feather-credit-card"></i></div>
            </div>
            <div class="bwl-dash-widget-value">₱{{ number_format($outstandingAmount, 2) }}</div>
            <div class="bwl-dash-widget-sub">Loan #{{ str_pad($app->application_id, 5, '0', STR_PAD_LEFT) }}</div>
            <div class="bwl-dash-progress-track">
                <div class="bwl-dash-progress-fill" style="width:{{ $progressPct }}%"></div>
            </div>
        </div>

        <div class="bwl-dash-widget">
            <div class="bwl-dash-widget-top">
                <div class="bwl-dash-widget-label">Next Payment</div>
                <div class="bwl-dash-widget-icon"><i class="feather-calendar"></i></div>
            </div>
            <div class="bwl-dash-widget-value">
                {{ $nextUnpaid ? '₱' . number_format($nextUnpaid->amount_due, 2) : '—' }}
            </div>
            @if ($nextUnpaid)
                @php
                    $dueCarbon = \Carbon\Carbon::parse($nextUnpaid->due_date);
                    $daysDiff = now()
                        ->startOfDay()
                        ->diffInDays($dueCarbon->copy()->startOfDay(), false);
                @endphp
                <div class="bwl-dash-widget-sub">Due on {{ $dueCarbon->format('M d, Y') }}</div>
                <span class="bwl-dash-pill {{ $daysDiff < 0 ? 'is-danger' : 'is-info' }}">
                    {{ $daysDiff < 0 ? abs($daysDiff) . ' ' . (abs($daysDiff) == 1 ? 'day' : 'days') . ' overdue' : $daysDiff . ' ' . ($daysDiff == 1 ? 'day' : 'days') . ' remaining' }}
                </span>
            @else
                <div class="bwl-dash-widget-sub">Loan fully paid</div>
            @endif
        </div>

        <div class="bwl-dash-widget">
            <div class="bwl-dash-widget-top">
                <div class="bwl-dash-widget-label">Total Amount Paid</div>
                <div class="bwl-dash-widget-icon"><i class="feather-check-circle"></i></div>
            </div>
            <div class="bwl-dash-widget-value">₱{{ number_format($amountPaid, 2) }}</div>
            <div class="bwl-dash-widget-sub">{{ $paidCount }} of {{ $totalCount }} payments</div>
            <div class="bwl-dash-progress-track">
                <div class="bwl-dash-progress-fill" style="width:{{ $progressPct }}%"></div>
            </div>
        </div>

        <div class="bwl-dash-widget">
            <div class="bwl-dash-widget-top">
                <div class="bwl-dash-widget-label">Payment Status</div>
                <div class="bwl-dash-widget-icon {{ $isOverdue ? 'is-red' : 'is-green' }}">
                    <i class="feather-{{ $isOverdue ? 'alert-circle' : 'check-circle' }}"></i>
                </div>
            </div>
            <div class="bwl-dash-widget-value {{ $isOverdue ? 'text-danger' : 'text-success' }}">
                {{ $isOverdue ? 'Overdue' : 'Up to date' }}
            </div>
            <div class="bwl-dash-widget-sub">
                {{ $isOverdue ? $daysOverdue . ' ' . ($daysOverdue == 1 ? 'day' : 'days') . ' overdue' : 'No overdue payment' }}
            </div>
        </div>
    </div>

    <div class="bwl-dash-row bwl-dash-row-cal">
        <div class="bwl-dash-card bwl-dash-overview">
            <div class="bwl-dash-card-header">
                <h6>Current Loan Overview</h6>
            </div>
            <div class="bwl-dash-overview-body">
                <div class="bwl-dash-overview-list">
                    <div class="bwl-dash-overview-row">
                        <span>Original Amount</span>
                        <strong>₱{{ number_format($app->loan_amount, 2) }}</strong>
                    </div>
                    <div class="bwl-dash-overview-row">
                        <span>Outstanding Balance</span>
                        <strong>₱{{ number_format($outstandingAmount, 2) }}</strong>
                    </div>
                    <div class="bwl-dash-overview-row">
                        <span>Monthly Payment</span>
                        <strong>₱{{ number_format($totalCount > 0 ? $app->total_amount / $totalCount : 0, 2) }}</strong>
                    </div>
                    <div class="bwl-dash-overview-row">
                        <span>Interest Rate</span>
                        <strong>{{ number_format($app->interest_rate, 0) }}% monthly</strong>
                    </div>
                    <div class="bwl-dash-overview-row">
                        <span>Loan Term</span>
                        <strong>{{ $app->loan_term }} months</strong>
                    </div>
                    <div class="bwl-dash-overview-row">
                        <span>Payments Made</span>
                        <strong>{{ $paidCount }} / {{ $totalCount }}</strong>
                    </div>
                    <div class="bwl-dash-overview-row">
                        <span>Next Due Date</span>
                        <strong>{{ $nextUnpaid ? \Carbon\Carbon::parse($nextUnpaid->due_date)->format('M d, Y') : '—' }}</strong>
                    </div>
                </div>

                <div class="bwl-dash-donut-wrap">
                    <div class="bwl-dash-progress-title">Loan Progress</div>
                    <div class="bwl-dash-progress-sub">{{ $paidCount }} of {{ $totalCount }} payments
                        completed</div>
                    <div class="bwl-dash-donut">
                        <svg class="bwl-dash-donut-svg" viewBox="0 0 150 150">
                            <circle class="bwl-dash-donut-track" cx="75" cy="75" r="65.5" pathLength="100"></circle>
                            <circle class="bwl-dash-donut-fill" cx="75" cy="75" r="65.5" pathLength="100"
                                stroke-dasharray="{{ $progressPct }} 100"></circle>
                        </svg>
                        <span class="bwl-dash-donut-pct">{{ $progressPct }}%</span>
                    </div>
                    <div class="bwl-dash-progress-sub">
                        @if ($activatedAt)
                            Started on {{ \Carbon\Carbon::parse($activatedAt)->format('M d, Y') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div id="bwl-dash-calendar-card" class="bwl-dash-card" data-calendar-data-url="{{ route('borrower.dashboard-calendar-data') }}">
            <div class="bwl-dash-card-header">
                <h6>Payment Calendar</h6>
            </div>
            <div id="bwl-cal-content">
                @include('borrower.partials.payment-calendar')
            </div>
        </div>
    </div>

    <div class="bwl-dash-row">
        <div class="bwl-dash-card bwl-dash-timeline">
            <div class="bwl-dash-card-header">
                <h6>Application Timeline</h6>
            </div>
            <div class="bwl-dash-timeline-list">
                <div class="bwl-dash-timeline-item is-done">
                    <span class="bwl-dash-timeline-dot"></span>
                    <div class="bwl-dash-timeline-body">
                        <div class="bwl-dash-timeline-title">Application Submitted</div>
                        <div class="bwl-dash-timeline-date">
                            {{ \Carbon\Carbon::parse($app->applied_at)->format('F j, Y') }}</div>
                    </div>
                    <span class="bwl-dash-timeline-badge is-done">Completed</span>
                </div>

                @if ($approvedAt)
                    <div class="bwl-dash-timeline-item is-done">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Application Approved</div>
                            <div class="bwl-dash-timeline-date">
                                {{ \Carbon\Carbon::parse($approvedAt)->format('F j, Y') }}</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-done">Completed</span>
                    </div>
                @endif

                @if ($activatedAt)
                    <div class="bwl-dash-timeline-item is-done">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Loan Activated</div>
                            <div class="bwl-dash-timeline-date">
                                {{ \Carbon\Carbon::parse($activatedAt)->format('F j, Y') }}</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-done">Completed</span>
                    </div>
                @endif

                @if ($nextUnpaid)
                    <div class="bwl-dash-timeline-item is-upcoming">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Next Payment</div>
                            <div class="bwl-dash-timeline-date">
                                {{ \Carbon\Carbon::parse($nextUnpaid->due_date)->format('F j, Y') }}</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-upcoming">Upcoming</span>
                    </div>
                @else
                    <div class="bwl-dash-timeline-item is-done">
                        <span class="bwl-dash-timeline-dot"></span>
                        <div class="bwl-dash-timeline-body">
                            <div class="bwl-dash-timeline-title">Loan Fully Paid</div>
                        </div>
                        <span class="bwl-dash-timeline-badge is-done">Completed</span>
                    </div>
                @endif
            </div>
        </div>

                <div class="bwl-dash-card bwl-dash-payments">
            <div class="bwl-dash-card-header">
                <h6>Recent Payments</h6>
                <a href="{{ route('borrower.loan-details') }}">View All →</a>
            </div>
            <div class="bwl-dash-payment-list">
                @forelse($recentPayments as $p)
                    <a href="{{ route('borrower.loan-details', ['loan' => $app->application_id]) }}"
                        class="bwl-dash-payment-item bwl-dash-payment-link">
                        <div class="bwl-dash-payment-body">
                            <div class="bwl-dash-payment-title">Installment #{{ $p->installment_no }}</div>
                            <div class="bwl-dash-payment-date">
                                Due {{ \Carbon\Carbon::parse($p->due_date)->format('M j, Y') }}
                                &middot; Paid {{ \Carbon\Carbon::parse($p->paid_at)->format('M j, Y') }}
                            </div>
                        </div>
                        <span class="bwl-dash-payment-amount">₱{{ number_format($p->amount_paid, 2) }}</span>
                    </a>
                @empty
                    <p class="text-muted mb-0 p-3">No payments recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

@endif

    </div>
@endsection

@section('page-scripts')
    <script src="{{ asset('functions/borrower-dashboard.js') }}?v={{ time() }}"></script>
@endsection