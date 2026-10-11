@extends('borrower.layout')

    @section('title', 'Personal Details')

    @section('page-head-links')
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/vendors/css/daterangepicker.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/borrower/css/personal-details.css') }}">
    @endsection

    @section('content')
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title"><h5 class="m-b-10">Personal Details</h5></div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Personal Details</li>
                </ul>
            </div>
        </div>

        <div class="main-content">

            {{-- Wala nang 'pending' state — direktang 'approved' na ang status
                pagka-submit, kaya diretso na lang dito ang "already submitted"
                card. --}}
            @if($detailsStatus === 'approved')
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card text-center bwl-status-card" style="border: 2px solid #28a745;">
                        <div class="card-body py-5">
                            <div style="width:80px;height:80px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                                <i class="feather-check-circle" style="font-size:36px;color:#28a745;"></i>
                            </div>
                            <h4 class="fw-bold mb-2">Details Submitted</h4>
                            <p class="text-muted mb-4">Your personal details have been submitted. You are now eligible to apply for a loan.</p>
                            <a href="{{ route('borrower.apply-loan') }}" class="btn btn-success px-5">
                                <i class="feather-file-plus me-2"></i>Apply for a Loan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @else
            <div class="row">
                <div class="col-lg-12">

                    {{-- ============================================================
                        WIZARD CARD — iisang malaking white card na naglalaman
                        ng progress bar + buong form.
                        Ang bilang ng .progress-step dito ay dapat KAPAREHO ng
                        bilang ng .pd-step sa loob ng form sa baba (3 na lang
                        ngayon — nailipat na ang Valid ID sa Apply for a Loan).
                    ============================================================ --}}
                    <div class="pd-wizard-card">

                    <div class="form-progress" id="pdProgress">
                        <div class="progress-step">
                            <div class="progress-step-circle">1</div>
                            <div class="progress-step-label">Personal Info</div>
                        </div>
                        <div class="progress-connector"></div>
                        <div class="progress-step">
                            <div class="progress-step-circle">2</div>
                            <div class="progress-step-label">Address</div>
                        </div>
                        <div class="progress-connector"></div>
                        <div class="progress-step">
                            <div class="progress-step-circle">3</div>
                            <div class="progress-step-label">Work Info</div>
                        </div>
                    </div>

                    <form id="personalDetailsForm" onsubmit="return false;">

                        {{-- ============ STEP 1 — PERSONAL INFORMATION ============ --}}
                        <div class="pd-step">
                            <div class="pd-step-alert"></div>

                            <div class="form-section">
                                <div class="form-section-header">
                                    <div>
                                        <p class="form-section-title">Personal Information</p>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" value="{{ $regData['lastName'] }}" placeholder="Enter last name" {{ $regData['lastName'] ? 'disabled' : 'required' }}>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="firstName" value="{{ $regData['firstName'] }}" placeholder="Enter first name" {{ $regData['firstName'] ? 'disabled' : 'required' }}>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="middleName" value="{{ $regData['middleName'] }}" placeholder="Enter middle name" {{ $regData['middleName'] ? 'disabled' : 'required' }}>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Suffix</label>
                                        <select class="form-select" id="suffix">
                                            <option value="" {{ ($details->suffix ?? '') === '' ? 'selected' : '' }}>None</option>
                                            @foreach(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $s)
                                            <option value="{{ $s }}" {{ ($details->suffix ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Birthday</label>
                                        <div class="pd-input-icon-wrap">
                                            <i class="feather-calendar pd-input-icon"></i>
                                            <input type="date" class="form-control" id="birthday" value="{{ $regData['birthday'] }}" {{ $regData['birthday'] ? 'disabled' : 'required' }}>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Age</label>
                                        <input type="number" class="form-control" id="age" placeholder="Auto-calculated" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" id="gender" required>
                                            <option value="">Select gender</option>
                                            <option value="male" {{ ($details->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ ($details->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Civil Status</label>
                                        <select class="form-select" id="civilStatus" required>
                                            <option value="">Select status</option>
                                            @foreach(['single','taken','married','widowed','separated'] as $cs)
                                            <option value="{{ $cs }}" {{ ($details->civil_status ?? '') === $cs ? 'selected' : '' }}>{{ ucfirst($cs) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Citizenship</label>
                                        <input type="text" class="form-control" id="citizenship" value="{{ $details->citizenship ?? '' }}" placeholder="e.g. Filipino" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Cellphone Number</label>
                                        <input type="tel" class="form-control" id="cellphone" value="{{ $details->cellphone_num ?? '+63 ' }}" placeholder="+63 912 345 6789" maxlength="16" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Birth Country</label>
                                        <input type="text" class="form-control" id="birthCountry" value="{{ $details->birth_country ?? '' }}" placeholder="e.g. Philippines" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Birth Province</label>
                                        <input type="text" class="form-control" id="birthProvince" value="{{ $details->birth_province ?? '' }}" placeholder="e.g. Cagayan" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Birth City</label>
                                        <input type="text" class="form-control" id="birthCity" value="{{ $details->birth_city ?? '' }}" placeholder="e.g. Tuguegarao" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <div class="pd-input-icon-wrap">
                                            <i class="feather-mail pd-input-icon"></i>
                                            <input type="email" class="form-control" id="email" value="{{ $regData['email'] }}" placeholder="Enter email address" {{ $regData['email'] ? 'disabled' : 'required' }}>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Monthly Income</label>
                                        <select class="form-select" id="monthlyIncome" required>
                                            <option value="">Select income range</option>
                                            <option value="Below ₱10,000" {{ ($details->monthly_income ?? '') === 'Below ₱10,000' ? 'selected' : '' }}>Below ₱10,000</option>
                                            <option value="₱10,000 - ₱20,000" {{ ($details->monthly_income ?? '') === '₱10,000 - ₱20,000' ? 'selected' : '' }}>₱10,000 - ₱20,000</option>
                                            <option value="₱20,001 - ₱30,000" {{ ($details->monthly_income ?? '') === '₱20,001 - ₱30,000' ? 'selected' : '' }}>₱20,001 - ₱30,000</option>
                                            <option value="₱30,001 - ₱50,000" {{ ($details->monthly_income ?? '') === '₱30,001 - ₱50,000' ? 'selected' : '' }}>₱30,001 - ₱50,000</option>
                                            <option value="₱50,001 - ₱100,000" {{ ($details->monthly_income ?? '') === '₱50,001 - ₱100,000' ? 'selected' : '' }}>₱50,001 - ₱100,000</option>
                                            <option value="Above ₱100,000" {{ ($details->monthly_income ?? '') === 'Above ₱100,000' ? 'selected' : '' }}>Above ₱100,000</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============ STEP 2 — PRESENT ADDRESS ============ --}}
                        <div class="pd-step">
                            <div class="pd-step-alert"></div>

                            <div class="form-section">
                                <div class="form-section-header">
                                    <div>
                                        <p class="form-section-title">Present Address</p>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Country</label>
                                        <input type="text" class="form-control" id="country" value="Philippines" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Region</label>
                                        <select class="form-select" id="region" required><option value="">Select Region</option></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Province</label>
                                        <select class="form-select" id="province" disabled required><option value="">Select Province</option></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">City/Municipality</label>
                                        <select class="form-select" id="city" disabled required><option value="">Select City/Municipality</option></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Barangay</label>
                                        <select class="form-select" id="barangay" disabled required><option value="">Select Barangay</option></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Zipcode</label>
                                        <input type="text" class="form-control" id="zipcode" value="{{ $address->zipcode ?? '' }}" placeholder="e.g. 1000" maxlength="4">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Home Address</label>
                                        <textarea class="form-control" id="homeAddress" rows="2" placeholder="e.g. Block 5 Lot 10, Greenfield Subdivision, Rizal Street">{{ $address->home_address ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============ STEP 3 — WORK INFORMATION ============ --}}
                        {{-- Ang employment status, company, position, at years/months
                            ay pine-prefill ng personal-details.js (window.pdSaved),
                            dahil nire-reset ng handleEmploymentStatus() ang mga field. --}}
                        <div class="pd-step">
                            <div class="pd-step-alert"></div>

                            <div class="form-section">
                                <div class="form-section-header">
                                    <div>
                                        <p class="form-section-title">Work Information</p>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Employment Status</label>
                                        <select class="form-select" id="employmentStatus" required>
                                            <option value="">Select status</option>
                                            @foreach(['employed'=>'Employed','self_employed'=>'Self-Employed','unemployed'=>'Unemployed','student'=>'Student','retired'=>'Retired'] as $val => $label)
                                            <option value="{{ $val }}" {{ ($work->employment_status ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="companyNameWrapper">
                                        <label class="form-label" id="companyNameLabel">Company/Business Name</label>
                                        <input type="text" class="form-control" id="companyName" value="{{ $work->company_name ?? '' }}" placeholder="Enter company/business name">
                                    </div>
                                    <div class="col-md-6" id="positionWrapper">
                                        <label class="form-label" id="positionLabel">Position/Occupation</label>
                                        <input type="text" class="form-control" id="position" value="{{ $work->position ?? '' }}" placeholder="Enter position/occupation">
                                    </div>
                                    <div class="col-md-6" id="yearLevelWrapper">
                                        <label class="form-label" id="yearLevelLabel">Year Level</label>
                                        <select class="form-select" id="yearLevel">
                                            <option value="">Select year level</option>
                                            <option value="1st Year">1st Year</option>
                                            <option value="2nd Year">2nd Year</option>
                                            <option value="3rd Year">3rd Year</option>
                                            <option value="4th Year">4th Year</option>
                                            <option value="5th Year">5th Year</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3" id="yearsWrapper">
                                        <label class="form-label" id="yearsLabel">Years</label>
                                        <input type="number" class="form-control" id="yearsInCompany" placeholder="e.g. 2" min="0" max="50" step="1">
                                    </div>

                                    <div class="col-md-3" id="monthsWrapper">
                                        <label class="form-label" id="monthsLabel">Months</label>
                                        <select class="form-select" id="monthsInCompany">
                                            @for($m = 0; $m <= 11; $m++)
                                            <option value="{{ $m }}">{{ $m }} months</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============ WIZARD NAVIGATION ============ --}}
                        <div class="pd-wizard-nav mb-4">
                            <button type="button" class="btn pd-btn-back" id="pdBtnBack">
                                Back
                            </button>

                            <div class="pd-wizard-nav-right">
                                <a href="{{ route('borrower.dashboard') }}" class="btn btn-outline-secondary px-5" style="border-radius:12px;">
                                    Cancel
                                </a>
                                <button type="button" class="btn px-5" id="pdBtnNext">
                                    Next
                                </button>
                                <button type="button" class="btn px-5" id="submitBtn">
                                    <i class="feather-send me-2"></i>Submit for Approval
                                </button>
                            </div>
                        </div>

                    </form>

                    </div>{{-- /.pd-wizard-card --}}

                </div>
            </div>
            @endif

        </div>

        <div id="submissionOverlay">
            <div class="modal-box">
                <div class="modal-inner">

                    <h5>Submitted Successfully!</h5>
                    <p class="sub-text">Your personal details have been submitted.</p>
                    <a href="{{ route('borrower.dashboard') }}" class="btn-go">
                        Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    @endsection

    @section('page-scripts')
        <script src="{{ asset('assets/borrower/vendors/js/daterangepicker.min.js') }}"></script>
        {{-- Wala nang rejected/on_hold states — 'incomplete' na lang ang
            case kung kailan ipapakita ang form (at ang JS prefill nito). --}}
        @if($detailsStatus === 'incomplete')
        {{-- Saved values para sa JS prefill (address cascade + work info) --}}
        @php
            $pdSaved = [
                'region'           => $address->region ?? null,
                'province'         => $address->province ?? null,
                'city'             => $address->city ?? null,
                'barangay'         => $address->barangay ?? null,
                'zipcode'          => $address->zipcode ?? null,
                'employmentStatus' => $work->employment_status ?? null,
                'companyName'      => $work->company_name ?? null,
                'position'         => $work->position ?? null,
                'yearsInCompany'   => $work->years_in_company ?? null,
            ];
        @endphp
        <script>
            window.pdSaved = {!! json_encode($pdSaved) !!};
        </script>
        <script src="{{ asset('functions/csrf.js') }}"></script>
        <script src="{{ asset('functions/personal-details.js') }}"></script>
        @endif
    @endsection