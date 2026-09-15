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

        @if($detailsStatus === 'pending')
        <div class="row justify-content-center">
            <div class="col-lg-6">
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
                <div class="alert alert-danger d-flex align-items-center gap-3 mb-4" role="alert" style="border-radius:12px; border-left: 4px solid #dc3545;">
                    <i class="feather-alert-circle" style="font-size:22px;flex-shrink:0;"></i>
                    <div>
                        <strong>Submission Rejected</strong><br>
                        <small>Your previous submission was rejected. Please update your information and resubmit.</small>
                    </div>
                </div>
                @endif

                <form id="personalDetailsForm" onsubmit="return false;">

                    <div class="form-section">
                        <div class="form-section-header">
                            <div><p class="form-section-title">Personal Information</p></div>
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
                                <input type="date" class="form-control" id="birthday" value="{{ $regData['birthday'] }}" {{ $regData['birthday'] ? 'disabled' : 'required' }}>
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
                                <input type="email" class="form-control" id="email" value="{{ $regData['email'] }}" placeholder="Enter email address" {{ $regData['email'] ? 'disabled' : 'required' }}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monthly Income</label>
                                <input type="text" class="form-control" id="monthlyIncome" value="{{ $details->monthly_income ?? '' }}" placeholder="e.g. 25000" inputmode="numeric" maxlength="6" autocomplete="off" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type of ID</label>
                                <select class="form-select" id="idType" required>
                                    <option value="">Select ID type</option>
                                    @foreach(['national_id'=>'National ID (PhilSys)','passport'=>'Passport','drivers_license'=>"Driver's License",'umid'=>'UMID','sss'=>'SSS ID','tin'=>'TIN ID','postal'=>'Postal ID','voters'=>"Voter's ID",'prc'=>'PRC ID'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($details->id_type ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ID/Passport Number</label>
                                <input type="text" class="form-control" id="idNumber" value="{{ $details->id_number ?? '' }}" placeholder="Enter ID or passport number" required>
                            </div>

                            {{-- ============ ID FRONT / BACK — split into two uploads
                                 dahil karamihan ng IDs (UMID, driver's license, PhilID)
                                 ay may importanteng info sa likod (address, signature,
                                 expiry) na kailangan i-verify ng admin. ============ --}}
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

                    <div class="form-section">
                        <div class="form-section-header"><div><p class="form-section-title">Present Address</p></div></div>
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
                                <input type="text" class="form-control" id="zipcode" value="{{ $details->zipcode ?? '' }}" placeholder="e.g. 1000" maxlength="4">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Home Address</label>
                                <textarea class="form-control" id="homeAddress" rows="2" placeholder="e.g. Block 5 Lot 10, Greenfield Subdivision, Rizal Street"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-header"><div><p class="form-section-title">Work Information</p></div></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Employment Status</label>
                                <select class="form-select" id="employmentStatus" required>
                                    <option value="">Select status</option>
                                    @foreach(['employed'=>'Employed','self_employed'=>'Self-Employed','unemployed'=>'Unemployed','student'=>'Student','retired'=>'Retired'] as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6" id="companyNameWrapper">
                                <label class="form-label" id="companyNameLabel">Company/Business Name</label>
                                <input type="text" class="form-control" id="companyName" placeholder="Enter company/business name">
                            </div>
                            <div class="col-md-6" id="positionWrapper">
                                <label class="form-label" id="positionLabel">Position/Occupation</label>
                                <input type="text" class="form-control" id="position" placeholder="Enter position/occupation">
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
                            {{-- Months in Company: hindi kailangan required — hindi naman
                                 alam ng lahat ng borrowers ang eksaktong bilang ng buwan
                                 nila sa trabaho. Years na lang required, defaults to 0. --}}
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

                    <div class="form-section">
                        <div class="form-section-header"><div><p class="form-section-title">Co-Maker Information</p></div></div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Co-Maker Last Name</label>
                                <input type="text" class="form-control" id="coMakerLastName" placeholder="Enter last name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Co-Maker First Name</label>
                                <input type="text" class="form-control" id="coMakerFirstName" placeholder="Enter first name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Co-Maker Middle Name</label>
                                <input type="text" class="form-control" id="coMakerMiddleName" placeholder="Enter middle name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Relationship to Borrower</label>
                                <select class="form-select" id="coMakerRelationship" required>
                                    <option value="">Select relationship</option>
                                    @foreach(['spouse'=>'Spouse','parent'=>'Parent','sibling'=>'Sibling','child'=>'Child','relative'=>'Other Relative','friend'=>'Friend','colleague'=>'Colleague'] as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Co-Maker Contact Number</label>
                                <input type="tel" class="form-control" id="coMakerContact" value="+63 " placeholder="+63 912 345 6789" maxlength="16" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-4">
                        <button type="button" class="btn px-5" id="submitBtn">
                            <i class="feather-send me-2"></i>Submit for Approval
                        </button>
                        <a href="{{ route('borrower.dashboard') }}" class="btn btn-outline-secondary px-5" style="border-radius:12px;">
                            <i class="feather-x me-2"></i>Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
        @endif

    </div>

    <div id="submissionOverlay">
        <div class="modal-box">
            <div class="modal-inner">
                <div class="icon-wrap"><i class="feather-check-circle"></i></div>
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
    @if($detailsStatus === 'incomplete' || $detailsStatus === 'rejected')
    <script src="{{ asset('functions/csrf.js') }}"></script>
    <script src="{{ asset('functions/personal-details.js') }}"></script>
    @endif
@endsection