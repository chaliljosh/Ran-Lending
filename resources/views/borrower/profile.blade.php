@extends('borrower.layout')

@section('title', 'My Profile')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/profile.css') }}?v={{ time() }}">
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">My Profile</h5></div>
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
                            @if($profilePicture ?? false)
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
                                <span class="badge" style="background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;font-size:0.75rem;">
                                    <i class="feather-check-circle me-1"></i>Active Account
                                </span>
                                <span class="badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;font-size:0.75rem;">
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
                        <a href="javascript:void(0);" class="section-edit-link" id="editLink-personal" onclick="toggleSectionEdit('personal')">
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
                            <div class="field-box">{{ !empty($profileData['birthday']) ? \Carbon\Carbon::parse($profileData['birthday'])->format('F d, Y') : '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Age</div>
                            <div class="field-box">{{ !empty($profileData['birthday']) ? \Carbon\Carbon::parse($profileData['birthday'])->age : '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Gender</div>
                            <div class="field-box">{{ ucfirst($profileData['gender'] ?? '—') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Civil Status</div>
                            <select class="form-select profile-editable" id="civilStatus" data-section="personal" disabled>
                                @foreach(['single','taken','married','widowed','separated'] as $cs)
                                <option value="{{ $cs }}" {{ ($profileData['civil_status'] ?? '') === $cs ? 'selected' : '' }}>{{ ucfirst($cs) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Citizenship</div>
                            <div class="field-box">{{ $profileData['citizenship'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Cellphone Number</div>
                            <input type="tel" class="form-control profile-editable" id="phone" data-section="personal" value="{{ $profileData['cellphone_num'] ?? '' }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <div class="field-label-custom">Email Address</div>
                            <div class="field-box">{{ $profileData['email'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="field-label-custom">Monthly Income</div>
                            <div class="field-box">₱{{ number_format((float) ($profileData['monthly_income'] ?? 0), 2) }}</div>
                        </div>
                    </div>

                    <div class="section-actions d-none" id="sectionActions-personal">
                        <button class="btn btn-cancel-pill btn-sm" onclick="cancelSectionEdit('personal')">
                            <i class="feather-x me-1"></i>Cancel
                        </button>
                        <button class="btn btn-save-pill btn-sm" id="saveBtn-personal" onclick="saveSection('personal')">
                            <i class="feather-save me-1"></i>Save Changes
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
                        <a href="javascript:void(0);" class="section-edit-link" id="editLink-address" onclick="toggleSectionEdit('address')">
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
                            <input type="text" class="form-control profile-editable" id="province" data-section="address" value="{{ $profileData['province'] ?? '' }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">City / Municipality</div>
                            <input type="text" class="form-control profile-editable" id="city" data-section="address" value="{{ $profileData['city'] ?? '' }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Barangay</div>
                            <input type="text" class="form-control profile-editable" id="barangay" data-section="address" value="{{ $profileData['barangay'] ?? '' }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <div class="field-label-custom">Zipcode</div>
                            <input type="text" class="form-control profile-editable" id="zipcode" data-section="address" value="{{ $profileData['zipcode'] ?? '' }}" maxlength="4" disabled>
                        </div>
                        <div class="col-12">
                            <div class="field-label-custom">Home Address</div>
                            <textarea class="form-control profile-editable" id="homeAddress" data-section="address" rows="2" disabled style="resize:none;">{{ $profileData['home_address'] ?? '' }}</textarea>
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

            <!-- Work + Co-Maker -->
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
                            <div class="field-box">{{ ucfirst(str_replace('_', ' ', $profileData['employment_status'] ?? '—')) }}</div>
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
                                $yicYears  = floor($yic);
                                $yicMonths = round(($yic - $yicYears) * 12);
                            @endphp
                            <div class="field-box">
                                {{ (int) $yicYears }} year{{ $yicYears != 1 ? 's' : '' }}, {{ (int) $yicMonths }} month{{ $yicMonths != 1 ? 's' : '' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="profile-card">
                    <div class="profile-card__head">
                        <div class="profile-card__head-text">
                            <i class="feather-users"></i>
                            <span>Co-Maker Information</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="field-label-custom">Last Name</div>
                            <div class="field-box">{{ $profileData['co_last'] ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="field-label-custom">First Name</div>
                            <div class="field-box">{{ $profileData['co_first'] ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="field-label-custom">Relationship</div>
                            <div class="field-box">{{ ucfirst($profileData['relationship'] ?? '—') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="field-label-custom">Contact Number</div>
                            <div class="field-box">{{ $profileData['co_contact'] ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Security Settings card REMOVED — moved to the Settings page
                 (reachable now via the header profile dropdown). --}}

        </div>
    </div>
@endsection

@section('page-scripts')
    <script>
        window.PROFILE_CONFIG = {
            csrfToken: '{{ csrf_token() }}',
            updateSectionUrl: '{{ url("/api/borrower/profile/update-section") }}',
        };
    </script>
    <script src="{{ asset('functions/profile.js') }}"></script>
@endsection