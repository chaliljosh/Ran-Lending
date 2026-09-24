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

        {{-- FIX: dating "$detailsStatus === 'pending'" lang ang check, kaya
             pati yung on_hold (2 approved + 1 rejected) ay napupunta dito
             dahil ang $detailsStatus column sa DB ay 'pending' pa rin kahit
             on-hold na. Ginamit na ang computed $isOnHold flag. --}}
        @if($detailsStatus === 'pending' && !$isOnHold)
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card bwl-status-card">
                    <div class="bwl-status-header">
                        <div class="bwl-status-icon"><i class="feather-check-circle"></i></div>
                        <div class="bwl-status-title-wrap">
                            <p class="bwl-status-title">Details Already Submitted</p>
                            <p class="bwl-status-desc">Your personal details have been submitted and are currently being reviewed by our admins. Please wait for approval.</p>
                        </div>
                    </div>
                    <div class="bwl-status-divider"></div>
                    <div class="bwl-status-approvals">
                        <div class="bwl-status-approvals-icon"><i class="feather-file-text"></i></div>
                        <div>
                            <div class="bwl-status-approvals-label">Admin Approvals</div>
                            <div class="bwl-status-approvals-value">{{ $approvedCount }} / 3</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @elseif($detailsStatus === 'approved')
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card text-center bwl-status-card" style="border: 2px solid #28a745;">
                    <div class="card-body py-5">
                        <div style="width:80px;height:80px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                            <i class="feather-check-circle" style="font-size:36px;color:#28a745;"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Profile Approved</h4>
                        <p class="text-muted mb-4">Your personal details have been verified and approved. You are now eligible to apply for a loan.</p>
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

                @if($detailsStatus === 'rejected')
                {{-- ============ REJECTED NOTE (same layout as on_hold, red variant) ============ --}}
                <section class="bwl-note bwl-note--rejected" aria-label="Submission rejected">

                    <div class="bwl-note-fix">
                        <p class="bwl-note-label">Submission Rejected — fix these, then resubmit</p>

                        @if(($rejectionRemarks ?? collect())->isNotEmpty())
                            <ol class="bwl-note-list">
                                @foreach($rejectionRemarks as $remark)
                                    <li class="bwl-note-item">
                                        <span class="bwl-note-num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="bwl-note-text">{{ $remark }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <p class="bwl-note-empty">
                                The reviewer didn't leave a note. Double-check your ID photos and personal details, then resubmit.
                            </p>
                        @endif
                    </div>

                </section>
                {{-- FIX: dating $detailsStatus === 'on_hold' — hindi ito
                     nagma-match dahil ang DB status column ay 'pending' pa
                     rin sa on-hold state. Ginamit na ang $isOnHold flag. --}}
                @elseif($isOnHold)
                {{-- ============ ON HOLD NOTE ============ --}}
                <section class="bwl-note" aria-label="Verification on hold">

                    <div class="bwl-note-fix">
                        <p class="bwl-note-label">Fix these, then resubmit</p>

                        @if(($rejectionRemarks ?? collect())->isNotEmpty())
                            <ol class="bwl-note-list">
                                @foreach($rejectionRemarks as $remark)
                                    <li class="bwl-note-item">
                                        <span class="bwl-note-num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="bwl-note-text">{{ $remark }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <p class="bwl-note-empty">
                                The reviewer didn't leave a note. Double-check your ID photos and personal details, then resubmit.
                            </p>
                        @endif
                    </div>

                </section>
                @endif

                {{-- ============================================================
                     WIZARD CARD — iisang malaking white card na naglalaman
                     ng progress bar + buong form.
                     Ang bilang ng .progress-step dito ay dapat KAPAREHO ng
                     bilang ng .pd-step sa loob ng form sa baba.
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
                        <div class="progress-step-label">Valid ID</div>
                    </div>
                    <div class="progress-connector"></div>
                    <div class="progress-step">
                        <div class="progress-step-circle">3</div>
                        <div class="progress-step-label">Address</div>
                    </div>
                    <div class="progress-connector"></div>
                    <div class="progress-step">
                        <div class="progress-step-circle">4</div>
                        <div class="progress-step-label">Work Info</div>
                    </div>
                    <div class="progress-connector"></div>
                    <div class="progress-step">
                        <div class="progress-step-circle">5</div>
                        <div class="progress-step-label">Co-Maker</div>
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

                    {{-- ============ STEP 2 — VALID ID ============ --}}
                    <div class="pd-step">
                        <div class="pd-step-alert"></div>

                        <div class="form-section">
                            <div class="form-section-header">
                                <div>
                                    <p class="form-section-title">Valid ID</p>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Type of ID</label>
                                    <select class="form-select" id="idType" required>
                                        <option value="">Select ID type</option>
                                        @foreach(['national_id'=>'National ID (PhilSys)','passport'=>'Passport','drivers_license'=>"Driver's License",'umid'=>'UMID','sss'=>'SSS ID','tin'=>'TIN ID','postal'=>'Postal ID','voters'=>"Voter's ID",'prc'=>'PRC ID','others'=>'Others'] as $val => $label)
                                        <option value="{{ $val }}" {{ ($details->id_type ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6" id="idTypeOthersWrapper" style="display:none;">
                                    <label class="form-label">Specify ID Type</label>
                                    <input type="text" class="form-control" id="idTypeOthers" value="{{ $details->id_type_others ?? '' }}" placeholder="Enter ID type">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ID/Passport Number</label>
                                    <input type="text" class="form-control" id="idNumber" value="{{ $details->id_number ?? '' }}" placeholder="Enter ID or passport number" required>
                                </div>

                                {{-- ID FRONT / BACK — dalawang hiwalay na upload dahil
                                     karamihan ng IDs ay may importanteng info sa likod. --}}
                                <div class="col-md-6">
                                    <label class="form-label">Front ID</label>
                                    <input type="file" class="form-control" id="validIdFront" accept="image/*,.pdf" {{ ($details->id_file_path ?? null) ? '' : 'required' }}>
                                    <small class="text-muted mt-1 d-block"><i class="feather-info me-1"></i>Accepted: JPG, PNG, PDF (Max 5MB)</small>
                                    <div id="idFrontPreview" class="mt-2"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Back ID</label>
                                    <input type="file" class="form-control" id="validIdBack" accept="image/*,.pdf" {{ ($details->id_file_path_back ?? null) ? '' : 'required' }}>
                                    <div id="idBackPreview" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ STEP 3 — PRESENT ADDRESS ============ --}}
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

                    {{-- ============ STEP 4 — WORK INFORMATION ============ --}}
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

                    {{-- ============ STEP 5 — CO-MAKER INFORMATION ============ --}}
                    <div class="pd-step">
                        <div class="pd-step-alert"></div>

                        <div class="form-section">
                            <div class="form-section-header">
                                <div>
                                    <p class="form-section-title">Co-Maker Information</p>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Co-Maker Last Name</label>
                                    <input type="text" class="form-control" id="coMakerLastName" value="{{ $comaker->last_name ?? '' }}" placeholder="Enter last name" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Co-Maker First Name</label>
                                    <input type="text" class="form-control" id="coMakerFirstName" value="{{ $comaker->first_name ?? '' }}" placeholder="Enter first name" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Co-Maker Middle Name</label>
                                    <input type="text" class="form-control" id="coMakerMiddleName" value="{{ $comaker->middle_name ?? '' }}" placeholder="M.I." maxlength="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Relationship to Borrower</label>
                                    <select class="form-select" id="coMakerRelationship" required>
                                        <option value="">Select relationship</option>
                                        @foreach(['spouse'=>'Spouse','parent'=>'Parent','sibling'=>'Sibling','child'=>'Child','relative'=>'Other Relative','friend'=>'Friend','colleague'=>'Colleague'] as $val => $label)
                                        <option value="{{ $val }}" {{ ($comaker->relationship ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Co-Maker Contact Number</label>
                                    <input type="tel" class="form-control" id="coMakerContact" value="{{ $comaker->contact_number ?? '+63 ' }}" placeholder="+63 912 345 6789" maxlength="16" required>
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
                <p class="sub-text">Your personal details have been submitted.<br>Please wait for the admin to review your information.</p>
                <a href="{{ route('borrower.dashboard') }}" class="btn-go">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script src="{{ asset('assets/borrower/vendors/js/daterangepicker.min.js') }}"></script>
    {{-- FIX: dating condition ay hindi na-trigger for on_hold kasi
         $detailsStatus ay 'pending' sa DB, gamit na ang $isOnHold. --}}
    @if($detailsStatus === 'incomplete' || $detailsStatus === 'rejected' || $isOnHold)
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