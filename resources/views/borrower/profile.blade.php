@extends('borrower.layout')

@section('title', 'My Profile')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/profile.css') }}?v={{ time() }}">
    {{-- Para sa Loan History table, status pills, at modal (galing sa Loan Details) --}}
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/loan-details.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/payment-receipt.css') }}?v={{ time() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/avatar-placeholder.css') }}" />
@endsection

@section('content')
    @php
        $loans = $loans ?? collect();

        // status => [badge class, label]
        $historyStatus = [
            'pending'   => ['bg-warning',   'Pending'],
            'on_hold'   => ['bg-danger',    'On Hold'],
            'approved'  => ['bg-primary',   'Approved'],
            'active'    => ['bg-primary',   'Active'],
            'completed' => ['bg-success',   'Completed'],
            'rejected'  => ['bg-secondary', 'Rejected'],
            'defaulted' => ['bg-danger',    'Defaulted'],
        ];
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

            <!-- Profile Header -->
            <div class="col-12">
                <div class="profile-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-circle js-borrower-avatar" data-initials="{{ $initials }}">
                            @if ($profilePicture ?? false)
                                <img src="{{ $profilePicture }}" alt="{{ $userName }}"
                                    onerror="this.closest('.js-borrower-avatar').textContent = this.closest('.js-borrower-avatar').dataset.initials;">
                            @else
                                {{ $initials }}
                            @endif
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">
                                {{ trim(($profileData['first_name'] ?? '') . ' ' . ($profileData['middle_name'] ?? '') . ' ' . ($profileData['last_name'] ?? '')) }}
                            </h5>
                            <p class="text-muted mb-2" style="font-size:0.85rem;">{{ $profileData['email'] ?? '' }}</p>
                            <div class="d-flex gap-2">
                                <span class="badge"
                                    style="background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;font-size:0.75rem;">
                                    <i class="feather-check-circle me-1"></i>Active Account
                                </span>
                                <span class="badge"
                                    style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;font-size:0.75rem;">
                                    <i class="feather-shield me-1"></i>Verified
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="col-12">
                <div class="profile-card">
                    <div class="profile-card__head">
                        <div class="profile-card__head-text">
                            <i class="feather-user"></i>
                            <span>Personal Information</span>
                        </div>
                        <a href="javascript:void(0);" class="section-edit-link" id="editLink-personal"
                            onclick="toggleSectionEdit('personal')">
                            <i class="feather-edit-2"></i> Edit
                        </a>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="field-label-custom">Last Name</div>
                            <div class="field-box">{{ $profileData['last_name'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">First Name</div>
                            <div class="field-box">{{ $profileData['first_name'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Middle Name</div>
                            <div class="field-box">{{ $profileData['middle_name'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Birthday</div>
                            <div class="field-box">
                                {{ !empty($profileData['birthday']) ? \Carbon\Carbon::parse($profileData['birthday'])->format('F d, Y') : '—' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Age</div>
                            <div class="field-box">
                                {{ !empty($profileData['birthday']) ? \Carbon\Carbon::parse($profileData['birthday'])->age : '—' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Gender</div>
                            <div class="field-box">{{ ucfirst($profileData['gender'] ?? '—') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Civil Status</div>
                            <select class="form-select profile-editable" id="civilStatus" data-section="personal" disabled>
                                @foreach (['single', 'taken', 'married', 'widowed', 'separated'] as $cs)
                                    <option value="{{ $cs }}"
                                        {{ ($profileData['civil_status'] ?? '') === $cs ? 'selected' : '' }}>
                                        {{ ucfirst($cs) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Citizenship</div>
                            <div class="field-box">{{ $profileData['citizenship'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Cellphone Number</div>
                            <input type="tel" class="form-control profile-editable" id="phone"
                                data-section="personal" value="{{ $profileData['cellphone_num'] ?? '' }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <div class="field-label-custom">Email Address</div>
                            <div class="field-box">{{ $profileData['email'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="field-label-custom">Monthly Income</div>
                            <div class="field-box">₱{{ number_format((float) ($profileData['monthly_income'] ?? 0), 2) }}
                            </div>
                        </div>
                    </div>

                    <div class="section-actions d-none" id="sectionActions-personal">
                        <button class="btn btn-cancel-pill btn-sm" onclick="cancelSectionEdit('personal')">
                            <i class="bi bi-x-lg"></i>Cancel
                        </button>
                        <button class="btn btn-save-pill btn-sm" id="saveBtn-personal" onclick="saveSection('personal')">
                            <i class="bi bi-check2"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <!-- Present Address -->
            <div class="col-12">
                <div class="profile-card">
                    <div class="profile-card__head">
                        <div class="profile-card__head-text">
                            <i class="feather-map-pin"></i>
                            <span>Present Address</span>
                        </div>
                        <a href="javascript:void(0);" class="section-edit-link" id="editLink-address"
                            onclick="toggleSectionEdit('address')">
                            <i class="feather-edit-2"></i> Edit
                        </a>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="field-label-custom">Country</div>
                            <div class="field-box">Philippines</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Region</div>
                            <div class="field-box">{{ $profileData['region'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Province</div>
                            <input type="text" class="form-control profile-editable" id="province"
                                data-section="address" value="{{ $profileData['province'] ?? '' }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">City / Municipality</div>
                            <input type="text" class="form-control profile-editable" id="city"
                                data-section="address" value="{{ $profileData['city'] ?? '' }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Barangay</div>
                            <input type="text" class="form-control profile-editable" id="barangay"
                                data-section="address" value="{{ $profileData['barangay'] ?? '' }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Zipcode</div>
                            <input type="text" class="form-control profile-editable" id="zipcode"
                                data-section="address" value="{{ $profileData['zipcode'] ?? '' }}" maxlength="4"
                                disabled>
                        </div>
                        <div class="col-12">
                            <div class="field-label-custom">Home Address</div>
                            <textarea class="form-control profile-editable" id="homeAddress" data-section="address" rows="2" disabled
                                style="resize:none;">{{ $profileData['home_address'] ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="section-actions d-none" id="sectionActions-address">
                        <button class="btn btn-cancel-pill btn-sm" onclick="cancelSectionEdit('address')">
                            <i class="feather-x me-1"></i>Cancel
                        </button>
                        <button class="btn btn-save-pill btn-sm" id="saveBtn-address" onclick="saveSection('address')">
                            <i class="feather-save me-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <!-- Work Information -->
            <div class="col-lg-6">
                <div class="profile-card">
                    <div class="profile-card__head">
                        <div class="profile-card__head-text">
                            <i class="feather-briefcase"></i>
                            <span>Work Information</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="field-label-custom">Employment Status</div>
                            <div class="field-box">
                                {{ ucfirst(str_replace('_', ' ', $profileData['employment_status'] ?? '—')) }}</div>
                        </div>
                        <div class="col-12">
                            <div class="field-label-custom">Company / Business Name</div>
                            <div class="field-box">{{ $profileData['company_name'] ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="field-label-custom">Position / Occupation</div>
                            <div class="field-box">{{ $profileData['position'] ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="field-label-custom">Duration in Company</div>
                            @php
                                $yic = (float) ($profileData['years_in_company'] ?? 0);
                                $yicYears = floor($yic);
                                $yicMonths = round(($yic - $yicYears) * 12);
                            @endphp
                            <div class="field-box">
                                {{ (int) $yicYears }} year{{ $yicYears != 1 ? 's' : '' }}, {{ (int) $yicMonths }}
                                month{{ $yicMonths != 1 ? 's' : '' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ LOAN HISTORY ============ -->
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
                                            $ls = $historyStatus[$l->status]
                                                ?? ['bg-secondary', ucfirst(str_replace('_', ' ', $l->status))];

                                            $lMonthly = data_get($l, 'monthly_payment');
                                            $lTotal   = data_get($l, 'total_amount');
                                        @endphp
                                        <tr class="bwl-ld-row-link"
                                            data-loan-id="LN-{{ str_pad($l->application_id, 6, '0', STR_PAD_LEFT) }}"
                                            data-amount="₱{{ number_format($l->loan_amount, 2) }}"
                                            data-term="{{ (int) $l->loan_term }} Months"
                                            data-applied="{{ \Carbon\Carbon::parse($l->applied_at)->format('F j, Y') }}"
                                            data-status-class="{{ $ls[0] }}"
                                            data-status-label="{{ $ls[1] }}"
                                            data-monthly="{{ $lMonthly !== null ? '₱' . number_format($lMonthly, 2) : '' }}"
                                            data-total="{{ $lTotal !== null ? '₱' . number_format($lTotal, 2) : '' }}"
                                            data-href="{{ route('borrower.loan-details', ['loan' => $l->application_id]) }}">
                                            <td>LN-{{ str_pad($l->application_id, 6, '0', STR_PAD_LEFT) }}</td>
                                            <td class="bwl-ld-cell-strong">₱{{ number_format($l->loan_amount, 2) }}</td>
                                            <td>{{ (int) $l->loan_term }} Months</td>
                                            <td>{{ \Carbon\Carbon::parse($l->applied_at)->format('M j, Y') }}</td>
                                            <td><span class="bwl-ld-status {{ $ls[0] }}">{{ $ls[1] }}</span></td>
                                            <td class="text-end">
                                                <button type="button" class="bwl-ld-link-btn" data-ld-view-loan>View</button>
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

        </div>
    </div>

    {{-- ============ LOAN VIEW MODAL ============ --}}
    @push('modals')
        <div class="modal fade bwl-ld-modal" id="ldLoanModal" tabindex="-1"
            aria-labelledby="ldLoanTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <span class="bwl-ld-eyebrow" id="ldLoanId"></span>
                            <h5 class="modal-title" id="ldLoanTitle">Loan Details</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <span class="bwl-ld-status" id="ldLoanStatus"></span>
                        </div>

                        <div class="bwl-ld-stat-grid bwl-ld-stat-grid--three">
                            <div class="bwl-ld-stat-card">
                                <span class="bwl-ld-stat-label">Loan Amount</span>
                                <div class="bwl-ld-stat-value bwl-ld-stat-value--sm" id="ldLoanAmount">—</div>
                            </div>
                            <div class="bwl-ld-stat-card">
                                <span class="bwl-ld-stat-label">Loan Term</span>
                                <div class="bwl-ld-stat-value bwl-ld-stat-value--sm" id="ldLoanTerm">—</div>
                            </div>
                            <div class="bwl-ld-stat-card">
                                <span class="bwl-ld-stat-label">Date Applied</span>
                                <div class="bwl-ld-stat-value bwl-ld-stat-value--sm" id="ldLoanApplied">—</div>
                            </div>
                        </div>

                        <div class="bwl-ld-stat-grid bwl-ld-stat-grid--second mt-3" id="ldLoanExtra">
                            <div class="bwl-ld-stat-card" id="ldLoanMonthlyWrap">
                                <span class="bwl-ld-stat-label">Monthly Payment</span>
                                <div class="bwl-ld-stat-value bwl-ld-stat-value--sm" id="ldLoanMonthly">—</div>
                            </div>
                            <div class="bwl-ld-stat-card" id="ldLoanTotalWrap">
                                <span class="bwl-ld-stat-label">Total Amount to Pay</span>
                                <div class="bwl-ld-stat-value bwl-ld-stat-value--sm" id="ldLoanTotal">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="bwl-ld-btn-ghost" data-bs-dismiss="modal">Close</button>
                        <a class="bwl-ld-btn" id="ldLoanOpen" href="#">Open full page</a>
                    </div>
                </div>
            </div>
        </div>
    @endpush
@endsection

@section('page-scripts')
    <script>
        window.PROFILE_CONFIG = {
            csrfToken: '{{ csrf_token() }}',
            updateSectionUrl: '{{ url('/api/borrower/profile/update-section') }}',
        };
    </script>
    <script src="{{ asset('functions/profile.js') }}"></script>
@endsection