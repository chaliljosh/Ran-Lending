@extends('borrower.layout')

@section('title', 'My Profile')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/profile.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/loan-details.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/payment-receipt.css') }}?v={{ time() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/avatar-placeholder.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/account-pages.css') }}?v={{ time() }}">
@endsection

@section('content')
    @php
        $loans = $loans ?? collect();
        $pd = $profileData ?? [];

        $historyStatus = [
            'pending'          => ['bg-warning',   'Pending'],
            'on_hold'          => ['bg-danger',    'On Hold'],
            'approved'         => ['bg-primary',   'Approved'],
            'active'           => ['bg-ongoing',   'Ongoing'],
            'awaiting_release' => ['bg-info',      'Awaiting Release'],
            'completed'        => ['bg-success',   'Completed'],
            'rejected'         => ['bg-secondary', 'Rejected'],
            'defaulted'        => ['bg-danger',    'Defaulted'],
        ];

        $peso = fn($v) => ($v === null || $v === '') ? '—' : '₱' . number_format((float) $v, 2);
        $dt = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('M j, Y') : '—';

        $fullName = trim(implode(' ', array_filter([
            $pd['first_name'] ?? '',
            $pd['middle_name'] ?? '',
            $pd['last_name'] ?? '',
        ])));

        $income = $pd['monthly_income'] ?? null;
        $incomeLabel = ($income === null || $income === '')
            ? '—'
            : (is_numeric($income) ? '₱' . number_format((float) $income, 2) : $income);

        $yic = $pd['years_in_company'] ?? null;
        if ($yic === null || $yic === '') {
            $yicLabel = '—';
        } elseif (is_numeric($yic)) {
            $yicYears = (int) floor((float) $yic);
            $yicMonths = (int) round(((float) $yic - $yicYears) * 12);
            $yicLabel = $yicYears . ' year' . ($yicYears != 1 ? 's' : '') . ', ' . $yicMonths . ' month' . ($yicMonths != 1 ? 's' : '');
        } else {
            $yicLabel = $yic . ' years';
        }

        // Hero stats
        $totalLoans    = $loans->count();
        $activeLoans   = $loans->whereIn('status', ['approved', 'active'])->count();
        $totalBorrowed = $loans->sum('loan_amount');
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">My Profile</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Profile</li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="row g-3">

            {{-- ===== Profile Hero ===== --}}
            <div class="col-12">
                <div class="profile-hero">
                    <div class="profile-hero__cover"></div>

                    <div class="profile-hero__body">
                        <div class="profile-hero__avatar avatar-circle js-borrower-avatar" data-initials="{{ $initials }}">
                            @if ($profilePicture ?? false)
                                <img src="{{ $profilePicture }}" alt="{{ $userName }}"
                                    onerror="this.closest('.js-borrower-avatar').textContent = this.closest('.js-borrower-avatar').dataset.initials;">
                            @else
                                {{ $initials }}
                            @endif
                        </div>

                        <div class="profile-hero__info">
                            <div class="profile-hero__badges">
                                <span class="profile-hero__badge profile-hero__badge--active">
                                    <i class="feather-check-circle"></i>Active Account
                                </span>
                                <span class="profile-hero__badge profile-hero__badge--verified">
                                    <i class="feather-shield"></i>Verified
                                </span>
                            </div>

                            <h3 class="profile-hero__name">{{ $fullName }}</h3>

                            <div class="profile-hero__meta">
                                <span><i class="feather-mail"></i>{{ $pd['email'] ?? $userEmail }}</span>
                                @if (!empty($pd['cellphone_num']))
                                    <span><i class="feather-phone"></i>{{ $pd['cellphone_num'] }}</span>
                                @endif
                                @if (!empty($pd['city']))
                                    <span><i class="feather-map-pin"></i>{{ $pd['city'] }}{{ !empty($pd['province']) ? ', ' . $pd['province'] : '' }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="profile-hero__stats">
                            <div class="profile-hero__stat">
                                <strong>{{ $totalLoans }}</strong>
                                <span>Total Loans</span>
                            </div>
                            <div class="profile-hero__stat">
                                <strong>{{ $activeLoans }}</strong>
                                <span>Ongoing</span>
                            </div>
                            <div class="profile-hero__stat">
                                <strong>₱{{ number_format($totalBorrowed, 0) }}</strong>
                                <span>Total Borrowed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="profile-card">
                    <div class="profile-card__head">
                        <div class="profile-card__head-text">
                            <i class="feather-file-text"></i>
                            <span>Loan History</span>
                        </div>
                    </div>

                    @if ($loans->count() > 0)
                        <div class="table-responsive" id="profileLoanHistory">
                            <table class="table bwl-ld-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Loan ID</th>
                                        <th>Amount</th>
                                        <th>Term</th>
                                        <th>Date Applied</th>
                                        <th>Status</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($loans as $l)
                                        @php
                                            $loanCode = !empty($l->uuid)
                                                ? 'LN-' . strtoupper(substr($l->uuid, 0, 8))
                                                : 'LN-' . str_pad($l->application_id, 6, '0', STR_PAD_LEFT);

                                            $statusKey = $l->status;
                                            if (in_array($l->status, ['approved', 'active'], true) && (int) ($l->schedule_count ?? 0) === 0) {
                                                $statusKey = 'awaiting_release';
                                            }

                                            $ls = $historyStatus[$statusKey]
                                                ?? ['bg-secondary', ucfirst(str_replace('_', ' ', $l->status))];

                                            $lTerm = (int) $l->loan_term;
                                            $lTotal = (float) $l->total_amount;
                                            $lPaid = (float) ($l->amount_paid ?? 0);
                                            $lRemaining = max($lTotal - $lPaid, 0);

                                            $schedule = collect($l->schedule ?? [])->map(function ($s) use ($peso, $dt) {
                                                $isPaid = !empty($s->paid_at);
                                                $isOverdue = !$isPaid && \Carbon\Carbon::parse($s->due_date)->isPast();

                                                return [
                                                    'no' => $s->installment_no,
                                                    'due' => $dt($s->due_date),
                                                    'amount' => $peso($s->amount_due),
                                                    'principal' => $peso($s->principal),
                                                    'interest' => $peso($s->interest),
                                                    'status' => $isPaid ? 'paid' : ($isOverdue ? 'overdue' : 'upcoming'),
                                                ];
                                            })->values();
                                        @endphp

                                        <tr class="bwl-ld-row-link"
                                            data-loan-id="{{ $loanCode }}"
                                            data-amount="{{ $peso($l->loan_amount) }}"
                                            data-term="{{ $lTerm }} Months"
                                            data-applied="{{ $dt($l->applied_at) }}"
                                            data-status-class="{{ $ls[0] }}"
                                            data-status-label="{{ $ls[1] }}"
                                            data-monthly="{{ $peso($l->monthly_payment) }}"
                                            data-fee="{{ $peso(0) }}"
                                            data-released="{{ $dt($l->released_at ?? null) }}"
                                            data-paid="{{ $peso($lPaid) }}"
                                            data-remaining="{{ $peso($lRemaining) }}"
                                            data-next-due="{{ $dt($l->next_due_date ?? null) }}"
                                            data-paid-count="{{ (int) ($l->paid_count ?? 0) }}"
                                            data-installments="{{ ($l->schedule_count ?? 0) ?: $lTerm }}"
                                            data-schedule="{{ json_encode($schedule) }}"
                                            data-href="{{ route('borrower.loan-details', ['loan' => $l->application_id]) }}">
                                            <td>{{ $loanCode }}</td>
                                            <td class="bwl-ld-cell-strong">₱{{ number_format($l->loan_amount, 2) }}</td>
                                            <td>{{ $lTerm }} Months</td>
                                            <td>{{ \Carbon\Carbon::parse($l->applied_at)->format('M j, Y') }}</td>
                                            <td><span class="bwl-ld-status {{ $ls[0] }}">{{ $ls[1] }}</span></td>
                                            <td class="text-end">
                                                <button type="button" class="bwl-ld-link-btn" data-ld-view-loan><i class="feather-eye"></i>View Details</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0" style="font-size:0.85rem;">You have no loan history yet.</p>
                    @endif
                </div>
            </div>

            <div class="col-lg-6">
                <div class="profile-card h-100">
                    <div class="profile-card__head">
                        <div class="profile-card__head-text">
                            <i class="feather-user"></i>
                            <span>Personal Information</span>
                        </div>
                    </div>
                    <p class="acct-card-sub">Verified during registration. To update some details, go to
                        <a href="{{ route('borrower.settings') }}">Settings</a>.</p>

                    <div class="acct-section-label">Basic info</div>
                    <dl class="acct-grid">
                        <div class="acct-grid__item"><dt>Last name</dt><dd>{{ $pd['last_name'] ?? '—' }}</dd></div>
                        <div class="acct-grid__item"><dt>First name</dt><dd>{{ $pd['first_name'] ?? '—' }}</dd></div>
                        <div class="acct-grid__item"><dt>Middle name</dt><dd>{{ $pd['middle_name'] ?? '—' }}</dd></div>
                        <div class="acct-grid__item">
                            <dt>Birthday</dt>
                            <dd>{{ !empty($pd['birthday']) ? \Carbon\Carbon::parse($pd['birthday'])->format('F d, Y') : '—' }}</dd>
                        </div>
                        <div class="acct-grid__item">
                            <dt>Age</dt>
                            <dd>{{ !empty($pd['birthday']) ? \Carbon\Carbon::parse($pd['birthday'])->age : '—' }}</dd>
                        </div>
                        <div class="acct-grid__item"><dt>Gender</dt><dd>{{ ucfirst($pd['gender'] ?? '—') }}</dd></div>
                        <div class="acct-grid__item"><dt>Citizenship</dt><dd>{{ $pd['citizenship'] ?? '—' }}</dd></div>
                        <div class="acct-grid__item"><dt>Civil status</dt><dd>{{ ucfirst($pd['civil_status'] ?? '—') }}</dd></div>
                    </dl>

                    <div class="acct-section-label">Contact &amp; income</div>
                    <dl class="acct-grid">
                        <div class="acct-grid__item"><dt>Cellphone</dt><dd>{{ $pd['cellphone_num'] ?? '—' }}</dd></div>
                        <div class="acct-grid__item"><dt>Email</dt><dd>{{ $pd['email'] ?? '—' }}</dd></div>
                        <div class="acct-grid__item"><dt>Monthly income</dt><dd>{{ $incomeLabel }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="d-flex flex-column gap-3 h-100">

                    <div class="profile-card">
                        <div class="profile-card__head">
                            <div class="profile-card__head-text">
                                <i class="feather-map-pin"></i>
                                <span>Present Address</span>
                            </div>
                        </div>
                        <dl class="acct-grid">
                            <div class="acct-grid__item"><dt>Country</dt><dd>Philippines</dd></div>
                            <div class="acct-grid__item"><dt>Region</dt><dd>{{ $pd['region'] ?? '—' }}</dd></div>
                            <div class="acct-grid__item"><dt>Province</dt><dd>{{ $pd['province'] ?? '—' }}</dd></div>
                            <div class="acct-grid__item"><dt>City / Municipality</dt><dd>{{ $pd['city'] ?? '—' }}</dd></div>
                            <div class="acct-grid__item"><dt>Barangay</dt><dd>{{ $pd['barangay'] ?? '—' }}</dd></div>
                            <div class="acct-grid__item"><dt>Zipcode</dt><dd>{{ $pd['zipcode'] ?? '—' }}</dd></div>
                            <div class="acct-grid__item acct-span-2"><dt>Home address</dt><dd>{{ $pd['home_address'] ?? '—' }}</dd></div>
                        </dl>
                    </div>

                    <div class="profile-card flex-grow-1">
                        <div class="profile-card__head">
                            <div class="profile-card__head-text">
                                <i class="feather-briefcase"></i>
                                <span>Work Information</span>
                            </div>
                        </div>
                        <dl class="acct-grid">
                            <div class="acct-grid__item">
                                <dt>Employment status</dt>
                                <dd>{{ ucfirst(str_replace('_', ' ', $pd['employment_status'] ?? '—')) }}</dd>
                            </div>
                            <div class="acct-grid__item"><dt>Company / Business</dt><dd>{{ $pd['company_name'] ?? '—' }}</dd></div>
                            <div class="acct-grid__item"><dt>Position</dt><dd>{{ $pd['position'] ?? '—' }}</dd></div>
                            <div class="acct-grid__item"><dt>Duration</dt><dd>{{ $yicLabel }}</dd></div>
                        </dl>
                    </div>

                </div>
            </div>

        </div>
    </div>

    @push('modals')
        <div class="modal fade plm-modal" id="ldLoanModal" tabindex="-1" aria-labelledby="ldLoanTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="plm-head">
                        <div>
                            <span class="plm-eyebrow" id="ldLoanId"></span>
                            <h5 class="plm-title" id="ldLoanTitle">Loan History</h5>
                            <span class="bwl-ld-status" id="ldLoanStatus"></span>
                        </div>
                        <div class="plm-head-right">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            <div class="plm-applied">
                                <i class="feather-calendar"></i>
                                <div>
                                    <span>Date Applied</span>
                                    <strong id="ldLoanApplied">—</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body plm-body">
                        <div class="plm-hero">
                            <div class="plm-hero-main">
                                <div class="plm-hero-icon"><i class="feather-briefcase"></i></div>
                                <div>
                                    <span class="plm-label">Total Loan Amount</span>
                                    <div class="plm-hero-amount" id="ldLoanAmount">—</div>
                                </div>
                            </div>
                            <div class="plm-hero-side">
                                <div class="plm-hero-side-item">
                                    <i class="feather-calendar"></i>
                                    <div><span class="plm-label">Loan Term</span><strong id="ldLoanTerm">—</strong></div>
                                </div>
                                <div class="plm-hero-side-item">
                                    <i class="feather-calendar"></i>
                                    <div><span class="plm-label">Monthly Payment</span><strong id="ldLoanMonthly">—</strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="plm-card">
                            <div class="plm-card-title"><i class="feather-file-text"></i><span>Loan Overview</span></div>
                            <div class="plm-overview">
                                <div class="plm-col">
                                    <div class="plm-field"><span class="plm-label">Loan ID</span><strong id="ldOvId">—</strong></div>
                                    <div class="plm-field"><span class="plm-label">Date Applied</span><strong id="ldOvApplied">—</strong></div>
                                    <div class="plm-field"><span class="plm-label">Release Date</span><strong id="ldOvReleased">—</strong></div>
                                </div>
                                <div class="plm-col">
                                    <div class="plm-field"><span class="plm-label">Principal Amount</span><strong id="ldOvPrincipal">—</strong></div>
                                    <div class="plm-field"><span class="plm-label">Processing Fee</span><strong id="ldOvFee">—</strong></div>
                                </div>
                                <div class="plm-col">
                                    <div class="plm-field"><span class="plm-label">Loan Term</span><strong id="ldOvTerm">—</strong></div>
                                    <div class="plm-field"><span class="plm-label">Status</span><span class="bwl-ld-status" id="ldOvStatus"></span></div>
                                </div>
                            </div>
                        </div>

                        <div class="plm-card">
                            <div class="plm-card-title"><i class="feather-bar-chart-2"></i><span>Payment Progress</span></div>
                            <div class="plm-progress-meta" id="ldProgressText">0 of 0 installments</div>
                            <div class="plm-progress"><div class="plm-progress-bar" id="ldProgressBar"></div></div>
                            <div class="plm-overview plm-overview--3">
                                <div class="plm-field"><span class="plm-label">Amount Paid</span><strong id="ldPaid">—</strong></div>
                                <div class="plm-field"><span class="plm-label">Remaining Balance</span><strong id="ldRemaining">—</strong></div>
                                <div class="plm-field"><span class="plm-label">Next Due Date</span><strong id="ldNextDue">—</strong></div>
                            </div>
                        </div>

                        <div class="plm-card">
                            <div class="plm-card-title plm-card-title--between">
                                <span class="plm-card-title-left"><i class="feather-calendar"></i><span>Repayment Schedule</span></span>
                                <a href="#" class="plm-link" id="ldScheduleLink">View Full Schedule <i class="feather-arrow-right"></i></a>
                            </div>
                            <div class="table-responsive">
                                <table class="plm-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Due Date</th>
                                            <th>Installment Amount</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ldScheduleBody"></tbody>
                                </table>
                            </div>
                            <div class="plm-note">
                                <i class="feather-info"></i>
                                <span>This schedule is an estimated breakdown. Actual amounts may vary depending on the final loan terms.</span>
                            </div>
                        </div>
                    </div>

                    <div class="plm-foot">
                        <button type="button" class="plm-btn-ghost" data-bs-dismiss="modal">Close</button>
                        <a class="plm-btn" id="ldLoanOpen" href="#">View Full Details</a>
                    </div>
                </div>
            </div>
        </div>
    @endpush
@endsection

@section('page-scripts')
    <script src="{{ asset('functions/profile.js') }}"></script>
@endsection