@extends('borrower.layout')

@section('title', 'Apply for a Loan')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/apply-loan.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/aln-dropzone.css') }}?v={{ time() }}">
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Apply for a Loan</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Apply for a Loan</li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="row justify-content-center">
            <div class="{{ $hasPendingApplication ? 'col-lg-8' : 'col-lg-9' }}">

                @if ($hasPendingApplication)
                    @php
                        $cardEyebrow = 'Loan Application';
                        $cardTitle = 'Pending Review';
                        $cardSubtext = "You'll be notified once a decision is made.";
                        $cardNote = 'A new application can be submitted once this one is approved or rejected.';

                        if ($existingApplication->status === 'approved' || $existingApplication->status === 'active') {
                            $cardEyebrow = 'Active Loan';
                            $cardTitle = 'Loan Active';
                            $cardSubtext = 'Your current loan is active and being repaid.';
                            $cardNote = 'You can apply for a new loan once your active loan has been fully paid off.';
                        }
                    @endphp

                    {{-- Pending / Active Loan Notice --}}
                    <section class="aln-section">
                        <div class="aln-section__head">
                            <div>
                                <span class="aln-eyebrow">{{ $cardEyebrow }}</span>
                                <h5 class="aln-title">{{ $cardTitle }}</h5>
                                <p class="aln-subtext">{{ $cardSubtext }}</p>
                            </div>
                            <span class="aln-status aln-status--{{ $existingApplication->status }}">
                                <span class="aln-status-dot"></span>
                                {{ ucfirst(str_replace('_', ' ', $existingApplication->status)) }}
                            </span>
                        </div>

                        <div class="aln-info-row">
                            <div class="aln-info-item">
                                <span class="aln-info-label">Application ID</span>
                                <span class="aln-info-value">#{{ $existingApplication->application_id }}</span>
                            </div>
                            <div class="aln-info-item">
                                <span class="aln-info-label">Loan Amount</span>
                                <span
                                    class="aln-info-value">₱{{ number_format($existingApplication->loan_amount, 2) }}</span>
                            </div>
                            <div class="aln-info-item">
                                <span class="aln-info-label">Date Applied</span>
                                <span
                                    class="aln-info-value">{{ \Carbon\Carbon::parse($existingApplication->applied_at)->format('M d, Y') }}</span>
                            </div>
                        </div>

                        <p class="aln-note">{{ $cardNote }}</p>

                        <a href="{{ route('borrower.loan-details') }}" class="aln-spotlight-btn" id="viewStatusBtn">
                            <span class="aln-spotlight-btn__glow"></span>
                            <span class="aln-spotlight-btn__label">View Application Status</span>
                        </a>

                        <script>
                            (function () {
                                const btn = document.getElementById('viewStatusBtn');
                                if (!btn) return;

                                btn.addEventListener('mousemove', function (e) {
                                    const rect = btn.getBoundingClientRect();
                                    const x = e.clientX - rect.left;
                                    const y = e.clientY - rect.top;
                                    btn.style.setProperty('--x', x + 'px');
                                    btn.style.setProperty('--y', y + 'px');
                                });
                            })();
                        </script>
                    </section>
                @else
                    {{-- INFO NOTICE --}}
                    <div class="aln-notice">
                        @if ($isFirstTime)
                            <span class="aln-notice__title">First-Time Borrowers</span>
                            <p class="aln-notice__text">
                                Maximum loan amount is ₱{{ number_format($maxLoanAmount) }}. Every time you fully
                                repay a loan, your limit goes up by ₱5,000.
                            </p>
                        @else
                            <span class="aln-notice__title">You're Eligible for Higher Amounts</span>
                            <p class="aln-notice__text">
                                Since you've successfully repaid a previous loan, you may now apply for up to
                                ₱{{ number_format($maxLoanAmount) }}. Every fully repaid loan raises your limit
                                by ₱5,000.
                            </p>
                        @endif
                    </div>

                    {{-- STEP PROGRESS (circles are filled in by apply-loan.js) --}}
                    <div class="aln-progress" id="alnProgress">
                        <div class="aln-progress-step">
                            <div class="aln-progress-circle">1</div>
                            <div class="aln-progress-label">Loan Details</div>
                        </div>
                        <div class="aln-progress-connector"></div>
                        <div class="aln-progress-step">
                            <div class="aln-progress-circle">2</div>
                            <div class="aln-progress-label">Valid ID</div>
                        </div>
                        <div class="aln-progress-connector"></div>
                        <div class="aln-progress-step">
                            <div class="aln-progress-circle">3</div>
                            <div class="aln-progress-label">Source of Income</div>
                        </div>
                    </div>

                    {{-- LOAN APPLICATION FORM (3-step wizard) --}}
                    <form id="loanApplicationForm" onsubmit="return false;">

                        {{-- ═══════════ STEP 1 — Loan Details ═══════════ --}}
                        <div class="aln-step" data-step="1">
                            <section class="aln-section">
                                <h6 class="aln-section-title">Loan Details</h6>
                                <div class="aln-hr aln-hr--tight"></div>

                                <div class="row g-3">

                                    {{-- Loan Amount (text input: JS adds the commas as the user types) --}}
                                    <div class="col-md-6">
                                        <label class="aln-label" for="loanAmount">
                                            Loan Amount <span class="aln-required">*</span>
                                        </label>
                                        <div class="input-group aln-input-group">
                                            <span class="input-group-text aln-input-prefix">₱</span>
                                            <input type="text" inputmode="numeric" autocomplete="off"
                                                class="form-control aln-input" id="loanAmount"
                                                placeholder="1,000 – {{ number_format($maxLoanAmount) }}" required>
                                        </div>
                                        <small class="aln-hint">Min: ₱1,000 &nbsp;|&nbsp; Max:
                                            ₱{{ number_format($maxLoanAmount) }}</small>
                                    </div>

                                    {{-- Loan Term (dropdown 1–12 months) --}}
                                    <div class="col-md-6">
                                        <label class="aln-label" for="loanTerm">
                                            Term <span class="aln-required">*</span>
                                        </label>
                                        <select class="form-select aln-input" id="loanTerm" required>
                                            <option value="">Select term</option>
                                            @for ($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}">{{ $m }} {{ $m === 1 ? 'Month' : 'Months' }}</option>
                                            @endfor
                                        </select>
                                    </div>

                                    {{-- Loan Purpose --}}
                                    <div class="col-12">
                                        <label class="aln-label">
                                            Purpose of the Loan <span class="aln-required">*</span>
                                        </label>
                                        <select class="form-select aln-input" id="loanPurpose" required>
                                            <option value="">Select purpose</option>
                                            <option value="business_capital">Business Capital</option>
                                            <option value="education">Education</option>
                                            <option value="medical_expenses">Medical Expenses</option>
                                            <option value="home_improvement">Home Improvement</option>
                                            <option value="debt_consolidation">Debt Consolidation</option>
                                            <option value="emergency">Emergency</option>
                                            <option value="travel">Travel</option>
                                            <option value="wedding">Wedding</option>
                                            <option value="vehicle_purchase">Vehicle Purchase</option>
                                            <option value="appliance_purchase">Appliance / Electronics</option>
                                            <option value="personal_expenses">Personal Expenses</option>
                                            <option value="others">Others</option>
                                        </select>
                                    </div>

                                    {{-- Purpose: Others (user types the purpose) --}}
                                    <div class="col-12" id="loanPurposeOtherField" style="display:none;">
                                        <label class="aln-label" for="loanPurposeOther">
                                            Please specify your purpose <span class="aln-required">*</span>
                                        </label>
                                        <input type="text" class="form-control aln-input" id="loanPurposeOther"
                                            maxlength="100" placeholder="e.g. Buying a motorcycle for delivery work">
                                    </div>

                                    {{-- Referral --}}
                                    <div class="col-12">
                                        <label class="aln-label">
                                            Where did you find us? <span class="aln-required">*</span>
                                        </label>
                                        <select class="form-select aln-input" id="referralSource" required>
                                            <option value="">Select an option</option>
                                            <option value="facebook">Facebook</option>
                                            <option value="instagram">Instagram</option>
                                            <option value="tiktok">TikTok</option>
                                            <option value="friend_family">Friend / Family Referral</option>
                                            <option value="flyer">Flyer / Poster</option>
                                            <option value="word_of_mouth">Word of Mouth</option>
                                            <option value="google">Google Search</option>
                                            <option value="others">Others</option>
                                        </select>
                                    </div>

                                    {{-- Referral: Others (user types where they found us) --}}
                                    <div class="col-12" id="referralOtherField" style="display:none;">
                                        <label class="aln-label" for="referralOther">
                                            Please specify where you found us <span class="aln-required">*</span>
                                        </label>
                                        <input type="text" class="form-control aln-input" id="referralOther"
                                            maxlength="100" placeholder="e.g. Saw it from a coworker's post">
                                    </div>

                                    {{-- Referral Name --}}
                                    <div class="col-12" id="referralNameField" style="display:none;">
                                        <label class="aln-label">
                                            Who referred you? <span class="aln-required">*</span>
                                        </label>
                                        <input type="text" class="form-control aln-input" id="referralName"
                                            placeholder="Enter the name of the person who referred you">
                                    </div>

                                </div>
                            </section>
                        </div>

                        {{-- ═══════════ STEP 2 — Valid ID ═══════════ --}}
                        @php
                            $hasSavedFront = !empty($idDetails->id_file_path ?? null);
                            $hasSavedBack = !empty($idDetails->id_file_path_back ?? null);
                            $hasSavedSelfie = !empty($idDetails->selfie_id_path ?? null);
                            $savedIdType = $idDetails->id_type ?? '';
                            $idTypeOptions = [
                                'national_id' => 'National ID (PhilSys)',
                                'passport' => 'Passport',
                                'drivers_license' => "Driver's License",
                                'umid' => 'UMID',
                                'sss' => 'SSS ID',
                                'tin' => 'TIN ID',
                                'postal' => 'Postal ID',
                                'voters' => "Voter's ID",
                                'prc' => 'PRC ID',
                                'others' => 'Others',
                            ];
                        @endphp
                        <div class="aln-step" data-step="2">
                            <section class="aln-section">
                                <h6 class="aln-section-title">Valid ID</h6>
                                <div class="aln-hr aln-hr--tight"></div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="aln-label" for="idType">
                                            Type of ID <span class="aln-required">*</span>
                                        </label>
                                        <select class="form-select aln-input" id="idType" required>
                                            <option value="">Select ID type</option>
                                            @foreach ($idTypeOptions as $val => $label)
                                                <option value="{{ $val }}" {{ $savedIdType === $val ? 'selected' : '' }}>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12" id="idTypeOthersWrapper" style="display:none;">
                                        <label class="aln-label" for="idTypeOthers">
                                            Specify ID Type <span class="aln-required">*</span>
                                        </label>
                                        <input type="text" class="form-control aln-input" id="idTypeOthers"
                                            value="{{ $idDetails->id_type_others ?? '' }}" placeholder="Enter ID type">
                                    </div>

                                    {{-- Label and placeholder change depending on the selected ID type (see apply-loan.js) --}}
                                    <div class="col-md-6">
                                        <label class="aln-label" for="idNumber">
                                            <span id="idNumberLabelText">ID Number</span> <span class="aln-required">*</span>
                                        </label>
                                        <input type="text" class="form-control aln-input" id="idNumber"
                                            value="{{ $idDetails->id_number ?? '' }}"
                                            placeholder="Select an ID type first" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="aln-label" for="validIdFront">
                                            Front of ID <span class="aln-required">*</span>
                                        </label>
                                        {{-- Totoong input (nakatago). Yung dropzone sa ibaba ang pipindutin. --}}
                                        <input type="file" class="aln-file-hidden" id="validIdFront"
                                            accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-saved="{{ $hasSavedFront ? '1' : '0' }}">

                                        <label for="validIdFront" class="aln-dropzone" data-dropzone-for="validIdFront">
                                            <span class="aln-dropzone__icon">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="16 16 12 12 8 16"></polyline>
                                                    <line x1="12" y1="12" x2="12" y2="21"></line>
                                                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                                                </svg>
                                            </span>
                                            <span class="aln-dropzone__title"><span>Click to upload</span> or drag and drop</span>
                                            <span class="aln-dropzone__hint">
                                                {{ $hasSavedFront ? 'You already have an ID on file. Upload a new one to replace it.' : 'JPG or PNG, max 5MB.' }}
                                            </span>
                                        </label>

                                        <div id="idFrontPreview" class="aln-file-preview"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="aln-label" for="validIdBack">
                                            Back of ID <span class="aln-required">*</span>
                                        </label>
                                        {{-- Totoong input (nakatago). Yung dropzone sa ibaba ang pipindutin. --}}
                                        <input type="file" class="aln-file-hidden" id="validIdBack"
                                            accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-saved="{{ $hasSavedBack ? '1' : '0' }}">

                                        <label for="validIdBack" class="aln-dropzone" data-dropzone-for="validIdBack">
                                            <span class="aln-dropzone__icon">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="16 16 12 12 8 16"></polyline>
                                                    <line x1="12" y1="12" x2="12" y2="21"></line>
                                                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                                                </svg>
                                            </span>
                                            <span class="aln-dropzone__title"><span>Click to upload</span> or drag and drop</span>
                                            <span class="aln-dropzone__hint">
                                                {{ $hasSavedBack ? 'You already have an ID on file. Upload a new one to replace it.' : 'JPG or PNG, max 5MB.' }}
                                            </span>
                                        </label>

                                        <div id="idBackPreview" class="aln-file-preview"></div>
                                    </div>

                                    {{-- Selfie with ID --}}
                                    <div class="col-md-6">
                                        <label class="aln-label" for="selfieWithId">
                                            Selfie with ID <span class="aln-required">*</span>
                                        </label>
                                        {{-- Totoong input (nakatago). Yung dropzone sa ibaba ang pipindutin. --}}
                                        <input type="file" class="aln-file-hidden" id="selfieWithId"
                                            accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-saved="{{ $hasSavedSelfie ? '1' : '0' }}">

                                        <label for="selfieWithId" class="aln-dropzone" data-dropzone-for="selfieWithId">
                                            <span class="aln-dropzone__icon">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="16 16 12 12 8 16"></polyline>
                                                    <line x1="12" y1="12" x2="12" y2="21"></line>
                                                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                                                </svg>
                                            </span>
                                            <span class="aln-dropzone__title"><span>Click to upload</span> or drag and drop</span>
                                            <span class="aln-dropzone__hint">
                                                {{ $hasSavedSelfie ? 'You already have a selfie on file. Upload a new one to replace it.' : 'JPG or PNG, max 5MB.' }}
                                            </span>
                                        </label>
                                        <small class="aln-hint">Hold your ID next to your face. Make sure your face and the ID are both clear.</small>

                                        <div id="selfiePreview" class="aln-file-preview"></div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        {{-- ═══════════ STEP 3 — Source of Income + Pay Slip ═══════════ --}}
                        @php
                            $savedEmployment = $work->employment_status ?? '';
                            $savedYears = $work->years_in_company ?? '';
                            $savedPrimarySource = $work->primary_source ?? '';
                            $hasSavedPaySlip = !empty($work->payslip_path ?? null);
                            $yearsOptions = [
                                '0-5' => '0 – 5 years',
                                '6-10' => '6 – 10 years',
                                '11-15' => '11 – 15 years',
                                '16-20' => '16 – 20 years',
                                '21+' => 'More than 20 years',
                            ];
                            // Walang "Select status" — Employed ang default
                            $employmentOptions = [
                                'employed' => 'Employed',
                                'self_employed' => 'Self-Employed',
                                'unemployed' => 'Unemployed',
                                'student' => 'Student',
                                'retired' => 'Retired',
                                'others' => 'Others',
                            ];
                            // Primary Source of Funds (Unemployed / Student / Retired)
                            $fundOptions = [
                                'family_support' => 'Family Support',
                                'savings' => 'Savings',
                                'allowance' => 'Allowance',
                                'scholarship' => 'Scholarship',
                                'pension' => 'Pension',
                                'remittance' => 'Remittance',
                                'odd_jobs' => 'Part-time / Odd Jobs',
                                'rental_investments' => 'Rental / Investments',
                            ];
                        @endphp
                        <div class="aln-step" data-step="3">
                            <section class="aln-section">
                                <h6 class="aln-section-title">Source of Income</h6>
                                <div class="aln-hr aln-hr--tight"></div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="aln-label" for="employmentStatus">
                                            Employment Status <span class="aln-required">*</span>
                                        </label>
                                        <select class="form-select aln-input" id="employmentStatus" required>
                                            @foreach ($employmentOptions as $val => $label)
                                                <option value="{{ $val }}" {{ $savedEmployment === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Others: user types their employment status --}}
                                    <div class="col-md-6" id="employmentOtherWrapper" style="display:none;">
                                        <label class="aln-label" for="employmentOther">
                                            Please specify <span class="aln-required">*</span>
                                        </label>
                                        <input type="text" class="form-control aln-input" id="employmentOther" maxlength="100"
                                            value="{{ $work->employment_status_other ?? '' }}" placeholder="e.g. Freelancer, Homemaker">
                                    </div>

                                    {{-- Employed: Employer / Company Name | Self-Employed: Business Name | Student: School Name --}}
                                    {{-- Labels below change depending on the employment status (see apply-loan.js) --}}
                                    <div class="col-md-6" id="companyNameWrapper" style="display:none;">
                                        <label class="aln-label" for="companyName">
                                            <span id="companyNameLabel">Employer / Company Name</span> <span class="aln-required">*</span>
                                        </label>
                                        <input type="text" class="form-control aln-input" id="companyName" maxlength="100"
                                            value="{{ $work->company_name ?? '' }}" placeholder="Enter employer / company name">
                                    </div>

                                    {{-- Employed: Occupation / Job Title | Self-Employed: Occupation / Business Type --}}
                                    <div class="col-md-6" id="positionWrapper" style="display:none;">
                                        <label class="aln-label" for="position">
                                            <span id="positionLabel">Occupation / Job Title</span> <span class="aln-required">*</span>
                                        </label>
                                        <input type="text" class="form-control aln-input" id="position" maxlength="100"
                                            value="{{ $work->position ?? '' }}" placeholder="Enter occupation / job title">
                                    </div>

                                    {{-- Employed: Years Employed | Self-Employed: Years in Business --}}
                                    <div class="col-md-6" id="yearsWrapper" style="display:none;">
                                        <label class="aln-label" for="yearsInCompany">
                                            <span id="yearsLabel">Years Employed</span>
                                        </label>
                                        <select class="form-select aln-input" id="yearsInCompany">
                                            @foreach ($yearsOptions as $val => $label)
                                                <option value="{{ $val }}" {{ $savedYears === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Unemployed / Student / Retired: Primary Source of Funds --}}
                                    <div class="col-md-6" id="primarySourceWrapper" style="display:none;">
                                        <label class="aln-label" for="primarySource">
                                            Primary Source of Funds <span class="aln-required">*</span>
                                        </label>
                                        <select class="form-select aln-input" id="primarySource">
                                            <option value="">Select source</option>
                                            @foreach ($fundOptions as $val => $label)
                                                <option value="{{ $val }}" {{ $savedPrimarySource === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Unemployed / Student / Retired: Other Sources of Income (optional) --}}
                                    <div class="col-md-6" id="otherSourcesWrapper" style="display:none;">
                                        <label class="aln-label" for="otherSources">
                                            Other Sources of Income <span class="aln-hint" style="display:inline;margin:0;">(optional)</span>
                                        </label>
                                        <input type="text" class="form-control aln-input" id="otherSources" maxlength="150"
                                            value="{{ $work->other_sources ?? '' }}" placeholder="e.g. Freelance work, Rental income">
                                    </div>
                                </div>
                            </section>

                            {{-- Pay slip (only for Employed / Self-Employed) --}}
                            <section class="aln-section" id="paySlipSection" style="display:none;">
                                <h6 class="aln-section-title" id="paySlipTitle">Pay Slip</h6>
                                <div class="aln-hr aln-hr--tight"></div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="aln-label" for="paySlip">
                                            <span id="paySlipLabel">Upload Pay Slip</span> <span class="aln-required">*</span>
                                        </label>

                                        {{-- Totoong input (nakatago). Yung dropzone sa ibaba ang pipindutin. --}}
                                        <input type="file" class="aln-file-hidden" id="paySlip"
                                            accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-saved="{{ $hasSavedPaySlip ? '1' : '0' }}">

                                        <label for="paySlip" class="aln-dropzone" data-dropzone-for="paySlip">
                                            <span class="aln-dropzone__icon">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="16 16 12 12 8 16"></polyline>
                                                    <line x1="12" y1="12" x2="12" y2="21"></line>
                                                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                                                </svg>
                                            </span>
                                            <span class="aln-dropzone__title"><span>Click to upload</span> or drag and drop</span>
                                            <span class="aln-dropzone__hint" id="paySlipHint">Latest pay slip. JPG or PNG, max 5MB.</span>
                                        </label>

                                        <div id="paySlipPreview" class="aln-file-preview"></div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        {{-- FORM BUTTONS --}}
                        <div class="aln-actions">
                            <a href="{{ route('borrower.dashboard') }}" class="aln-btn aln-btn--secondary aln-actions__left"
                                id="wizCancel" data-bs-toggle="modal" data-bs-target="#cancelConfirmModal">
                                Cancel
                            </a>

                            <button type="button" class="aln-btn aln-btn--secondary aln-actions__left aln-hide"
                                id="wizBack">
                                Back
                            </button>

                            <button type="button" class="aln-btn aln-btn--primary" id="wizNext" disabled>
                                Next
                            </button>

                            <button type="button" class="aln-btn aln-btn--primary aln-hide" id="submitBtn"
                                data-bs-toggle="modal" data-bs-target="#loanSummaryModal" disabled>
                                Submit Application
                            </button>
                        </div>

                    </form>
                @endif

            </div>
        </div>
    </div>

@endsection

@push('modals')
    <div id="successOverlay" class="aln-toast">
        <button type="button" class="aln-toast-close"
            onclick="document.getElementById('successOverlay').classList.remove('show')">&times;</button>
        <div class="aln-toast-body">
            <div class="aln-toast-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <div class="aln-toast-text">
                <h6>Application Submitted</h6>
                <p>Your loan application is now under review.</p>
            </div>
        </div>
    </div>
@endpush

@unless ($hasPendingApplication)
    @push('modals')
        {{-- LOAN SUMMARY MODAL (opens when the borrower clicks "Submit Application") --}}
        <div class="modal fade" id="loanSummaryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content aln-modal">

                    <div class="modal-body">
                        <h6 class="aln-modal-title">Loan Summary</h6>
                        <p>Please review your loan details before submitting.</p>

                        <div class="aln-hr aln-hr--tight"></div>

                        <div class="aln-summary-row" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));">
                            <div class="aln-summary-item">
                                <span class="aln-summary-label">Principal</span>
                                <span class="aln-summary-value" id="displayPrincipal">₱0.00</span>
                            </div>
                            <div class="aln-summary-item">
                                <span class="aln-summary-label">Interest Rate</span>
                                <span class="aln-summary-value">5% / month</span>
                            </div>
                        </div>

                        <div class="aln-hr aln-hr--tight"></div>

                        <div class="aln-summary-row" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));">
                            <div class="aln-summary-item">
                                <span class="aln-summary-label">Term</span>
                                <span class="aln-summary-value" id="displayTerm">— months</span>
                            </div>
                            <div class="aln-summary-item">
                                <span class="aln-summary-label">Monthly Payment</span>
                                <span class="aln-summary-value aln-summary-value--strong"
                                    id="displayMonthlyPayment">₱0.00</span>
                            </div>
                        </div>

                        <div class="aln-hr aln-hr--tight"></div>

                        <div class="aln-summary-row" style="grid-template-columns: 1fr;">
                            <div class="aln-summary-item">
                                <span class="aln-summary-label">Purpose of Loan</span>
                                <span class="aln-summary-value" id="displayPurpose">—</span>
                            </div>
                        </div>

                        <div class="aln-hr aln-hr--tight"></div>

                        <div class="aln-summary-item">
                            <span class="aln-summary-label">Documents</span>
                            <ul class="aln-doc-list" id="displayDocs"></ul>
                        </div>

                        <div class="aln-hr aln-hr--tight"></div>

                        <div class="form-check aln-agree">
                            <input class="form-check-input" type="checkbox" id="agreeTerms">
                            <label class="form-check-label" for="agreeTerms">
                                I confirm that all the information and documents I provided are true and correct.
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer aln-modal-footer">
                        <button type="button" class="aln-btn aln-btn--secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="button" class="aln-btn aln-btn--primary" id="finalSubmitBtn"
                            onclick="submitApplication()" disabled>
                            Confirm &amp; Submit
                        </button>
                    </div>

                </div>
            </div>
        </div>

        {{-- CANCEL CONFIRMATION MODAL (opens when the borrower clicks "Cancel") --}}
        <div class="modal fade" id="cancelConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content aln-modal">

                    <div class="modal-body">
                        <h6 class="aln-modal-title">Leave this application?</h6>
                        <p>Your progress is saved automatically as a draft, so you can continue later.
                            Pictures can't be saved in a draft, so you'll need to attach them again.</p>
                    </div>

                    <div class="modal-footer aln-modal-footer">
                        <button type="button" class="aln-btn aln-btn--secondary" data-bs-dismiss="modal">
                            No
                        </button>

                        <a href="{{ route('borrower.dashboard') }}" class="aln-btn aln-btn--primary" id="alnLeaveBtn">
                            Yes
                        </a>
                    </div>

                </div>
            </div>
        </div>
    @endpush
@endunless

{{-- Single script, always rendered (regardless of $hasPendingApplication) —
     apply-loan.js reads its config from these data-* attributes instead of
     a separate inline <script> block, and it self-guards: the form-wiring
     part of the file exits early when #loanAmount isn't on the page
     (pending-application view), but the post-submit toast check inside it
     still runs on every load. --}}
@section('page-scripts')
    <script src="{{ asset('functions/apply-loan.js') }}?v={{ filemtime(public_path('functions/apply-loan.js')) }}" data-csrf="{{ csrf_token() }}"
        data-submit-url="{{ url('/api/borrower/apply-loan') }}"
        data-max-amount="{{ $maxLoanAmount ?? 5000 }}"
        data-user-id="{{ session('user_id') }}"></script>
@endsection