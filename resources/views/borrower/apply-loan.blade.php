@extends('borrower.layout')

@section('title', 'Apply for a Loan')

@section('page-head-links')
    <style>
        /* =========================
           FORM SECTIONS
        ========================= */

        .form-section {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid #eef1f5;
        }

        .form-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1a2a3a;
            margin-bottom: 16px;
        }

        .info-banner {
            background: #e8f0fe;
            border: 1px solid #cfe0fd;
            color: #2c5cc5;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }


        /* =========================
           LOAN SUMMARY
        ========================= */

        .summary-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .summary-item label {
            font-size: 0.72rem;
            font-weight: 600;
            color: #8a9bb0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .summary-item .value {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a2a3a;
        }

        .summary-item .value.blue {
            color: #4a7cf7;
        }

        .summary-item .value.orange {
            color: #f97316;
        }

        .summary-item .value.red {
            color: #dc3545;
        }

        .summary-item .value.green {
            color: #16a34a;
        }

        .summary-divider {
            height: 1px;
            background: #e9ecef;
            margin: 16px 0;
        }


        /* =========================
           PENDING / ACTIVE CARD
        ========================= */

        .pending-card {
            background: #fff;
            border-radius: 14px;
            padding: 28px;
            border: 1px solid #eef1f5;
        }

        .pending-card__head {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .pending-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #fff8e1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .pending-icon-wrap i {
            font-size: 26px;
            color: #f0a500;
        }

        .pending-card__head-text {
            flex: 1;
        }

        .pending-card__head-text p {
            color: #8a9bb0;
            font-size: 0.85rem;
            margin: 2px 0 0;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.pending {
            background: #fff8e1;
            color: #f0a500;
        }

        .status-badge.approved,
        .status-badge.active {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.on_hold {
            background: #fff3cd;
            color: #856404;
        }

        .pending-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 16px;
        }

        .pending-details__cell .label {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            color: #8a9bb0;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .pending-details__cell .value {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1a2a3a;
        }

        .pending-note {
            color: #8a9bb0;
            font-size: 0.82rem;
            margin-bottom: 20px;
        }

        .pending-card__cta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #4a7cf7;
            color: #fff;
            padding: 10px 22px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.88rem;
        }


        /* =========================
           TERMS MODAL
           ========================= */

        /*
         * Important:
         * Do NOT blur the entire page behind the modal.
         * backdrop-filter can cause noticeable lag during scrolling,
         * especially on larger pages.
         */

        .modal-backdrop {
            background-color: rgba(30, 41, 59, 0.55);
        }

        .modal-backdrop.show {
            opacity: 1;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
        }

        /*
         * Keep the modal itself simple and GPU-friendly.
         */
        #termsModal .modal-content {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        /*
         * Only the terms content scrolls.
         * The modal header/footer remain fixed.
         */
        #termsModal .modal-body {
            max-height: 460px;
            overflow-y: auto;
            overflow-x: hidden;

            /* smoother scrolling */
            -webkit-overflow-scrolling: touch;

            /* prevents unnecessary rendering work */
            overscroll-behavior: contain;

            padding: 24px;
        }

        /*
         * Lightweight scrollbar
         */
        #termsModal .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        #termsModal .modal-body::-webkit-scrollbar-track {
            background: transparent;
        }

        #termsModal .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        #termsModal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /*
         * Modal footer stays clean.
         */
        #termsModal .modal-footer {
            background: #fff;
            border-top: 1px solid #eef1f5;
            padding: 16px 24px;
        }

        /*
         * Avoid unnecessary transitions that can make
         * the modal feel sluggish.
         */
        #termsModal .modal-dialog {
            will-change: auto;
        }

        /*
         * Better spacing for the terms.
         */
        #termsModal .modal-body p {
            color: #6c7a89;
            font-size: 0.88rem;
            line-height: 1.6;
        }

        #termsModal .modal-body h6 {
            color: #1a2a3a;
        }

        /*
         * Info notice inside terms
         */
        #termsModal .alert-info {
            background: #e8f8fa;
            border: 1px solid #c7eef1;
            color: #197b83;
            border-radius: 8px;
            font-size: 0.86rem;
        }


        /* =========================
           SUCCESS OVERLAY
        ========================= */

        #successOverlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: rgba(0, 0, 0, 0.55);
            align-items: center;
            justify-content: center;
        }

        #successOverlay .success-modal {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        #successOverlay .success-modal-top {
            height: 5px;
            background: #16a34a;
        }

        #successOverlay .success-modal-body {
            padding: 36px 28px;
        }

        #successOverlay .success-icon {
            font-size: 48px;
            color: #16a34a;
        }

        #successOverlay .success-modal h5 {
            font-weight: 700;
            margin: 12px 0 8px;
        }

        #successOverlay .success-modal p {
            color: #6c757d;
            font-size: 0.88rem;
            margin-bottom: 20px;
        }

        #successOverlay .waiting-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
        }

        #successOverlay .waiting-box p {
            margin: 0;
            font-size: 0.82rem;
            color: #6c757d;
        }

        #successOverlay .status-link {
            display: inline-block;
            background: #4a7cf7;
            color: #fff;
            border-radius: 10px;
            padding: 10px 36px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
@endsection

@section('content')

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Apply for a Loan</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Apply for a Loan</li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="row justify-content-center">
            <div class="{{ $hasPendingApplication ? 'col-lg-8' : 'col-lg-10' }}">

                @if($hasPendingApplication)

                    @php
                        $cardIcon    = 'feather-clock';
                        $cardTitle   = 'Application In Review';
                        $cardSubtext = "You'll be notified once a decision is made.";
                        $cardNote    = 'A new application can be submitted once this one is approved or rejected.';

                        if (
                            $existingApplication->status === 'approved' ||
                            $existingApplication->status === 'active'
                        ) {
                            $cardIcon    = 'feather-check-circle';
                            $cardTitle   = 'You Have an Active Loan';
                            $cardSubtext = 'Your current loan is active and being repaid.';
                            $cardNote    = 'You can apply for a new loan once your active loan has been fully paid off.';
                        }
                    @endphp

                    {{-- Pending / Active Loan Notice --}}
                    <div class="pending-card">
                        <div class="pending-card__head">
                            <div class="pending-icon-wrap">
                                <i class="{{ $cardIcon }}"></i>
                            </div>
                            <div class="pending-card__head-text">
                                <h6 class="mb-0">{{ $cardTitle }}</h6>
                                <p class="mb-0">{{ $cardSubtext }}</p>
                            </div>
                            <span class="status-badge {{ $existingApplication->status }}">
                                {{ ucfirst(str_replace('_', ' ', $existingApplication->status)) }}
                            </span>
                        </div>

                        <div class="pending-details">
                            <div class="pending-details__cell">
                                <span class="label">Application ID</span>
                                <span class="value">#{{ $existingApplication->application_id }}</span>
                            </div>
                            <div class="pending-details__cell">
                                <span class="label">Loan Amount</span>
                                <span class="value">₱{{ number_format($existingApplication->loan_amount, 2) }}</span>
                            </div>
                            <div class="pending-details__cell">
                                <span class="label">Date Applied</span>
                                <span class="value">{{ \Carbon\Carbon::parse($existingApplication->applied_at)->format('M d, Y') }}</span>
                            </div>
                        </div>

                        <p class="pending-note">{{ $cardNote }}</p>

                        <a href="{{ route('borrower.loan-details') }}" class="pending-card__cta">
                            View Application Status
                            <i class="feather-arrow-right"></i>
                        </a>
                    </div>

                @else

                    {{-- INFO BANNER --}}
                    <div class="info-banner">
                        <i class="feather-info me-2"></i>
                        <strong>First-Time Borrowers:</strong>
                        Maximum loan amount is ₱10,000.
                        After successful repayment, you may apply for higher amounts up to ₱100,000.
                    </div>

                    {{-- LOAN APPLICATION FORM --}}
                    <form id="loanApplicationForm" onsubmit="return false;">

                        {{-- Loan Details --}}
                        <div class="form-section">
                            <p class="form-section-title">Loan Details</p>

                            <div class="row g-3">

                                {{-- Loan Amount --}}
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Loan Amount <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="loanAmount"
                                               placeholder="1,000 – 10,000" min="1000" max="10000" step="100" required>
                                    </div>
                                    <small class="text-muted">Min: ₱1,000 &nbsp;|&nbsp; Max: ₱10,000</small>
                                </div>

                                {{-- Loan Term --}}
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Term <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="loanTerm" required>
                                        <option value="">Select term</option>
                                        <option value="3">3 Months</option>
                                        <option value="6">6 Months</option>
                                        <option value="12">12 Months</option>
                                    </select>
                                </div>

                                {{-- Loan Purpose --}}
                                <div class="col-12">
                                    <label class="form-label">
                                        Purpose of the Loan <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="loanPurpose" required>
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

                                {{-- Referral --}}
                                <div class="col-12">
                                    <label class="form-label">
                                        Where did you find us? <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="referralSource" required>
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

                                {{-- Referral Name --}}
                                <div class="col-12" id="referralNameField" style="display:none;">
                                    <label class="form-label">
                                        Who referred you? <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="referralName"
                                           placeholder="Enter the name of the person who referred you">
                                </div>

                            </div>
                        </div>

                        {{-- LOAN SUMMARY --}}
                        <div class="form-section">
                            <p class="form-section-title">Loan Summary</p>

                            <div class="summary-card">
                                <div class="row g-3">
                                    <div class="col-6 col-md-3 summary-item">
                                        <label>Principal</label>
                                        <span class="value blue" id="displayPrincipal">₱0.00</span>
                                    </div>
                                    <div class="col-6 col-md-3 summary-item">
                                        <label>Interest Rate</label>
                                        <span class="value">5% / month</span>
                                    </div>
                                    <div class="col-6 col-md-3 summary-item">
                                        <label>Total Interest</label>
                                        <span class="value orange" id="displayTotalInterest">₱0.00</span>
                                    </div>
                                    <div class="col-6 col-md-3 summary-item">
                                        <label>Total to Pay</label>
                                        <span class="value red" id="displayTotalAmount">₱0.00</span>
                                    </div>
                                </div>

                                <div class="summary-divider"></div>

                                <div class="row g-3">
                                    <div class="col-6 col-md-4 summary-item">
                                        <label>Term</label>
                                        <span class="value" id="displayTerm">— months</span>
                                    </div>
                                    <div class="col-6 col-md-4 summary-item">
                                        <label>Monthly Payment</label>
                                        <span class="value green" id="displayMonthlyPayment">₱0.00</span>
                                    </div>
                                    <div class="col-6 col-md-4 summary-item">
                                        <label>Due Date</label>
                                        <span class="value">15th of each month</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- FORM BUTTONS --}}
                        <div class="d-flex gap-3 mb-4">
                            <button type="button" class="btn btn-primary flex-fill" id="submitBtn"
                                    data-bs-toggle="modal" data-bs-target="#termsModal" disabled>
                                <i class="feather-send me-2"></i>
                                Submit Application
                            </button>

                            <a href="{{ route('borrower.dashboard') }}" class="btn btn-outline-secondary flex-fill">
                                <i class="feather-x me-2"></i>
                                Cancel
                            </a>
                        </div>

                    </form>

                @endif

            </div>
        </div>
    </div>

@endsection

@unless($hasPendingApplication)

    @push('modals')

        {{-- SUCCESS OVERLAY --}}
        <div id="successOverlay">
            <div class="success-modal">
                <div class="success-modal-top"></div>
                <div class="success-modal-body">
                    <i class="feather-check-circle success-icon"></i>
                    <h5>Application Submitted!</h5>
                    <p>Your loan application is now being reviewed by our admins. Please wait for approval.</p>
                    <div class="waiting-box">
                        <p><i class="feather-clock me-2 text-warning"></i> Waiting for admin approval...</p>
                    </div>
                    <a href="{{ route('borrower.loan-details') }}" class="status-link">View Application Status</a>
                </div>
            </div>
        </div>

        {{-- TERMS MODAL --}}
        <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">

                    <div class="modal-body">
                        <h6 class="fw-bold text-primary mb-3">LOAN TERMS AND CONDITIONS</h6>
                        <p>Please read these terms carefully before submitting your loan application.</p>

                        <h6 class="fw-bold mt-4 mb-2">1. Loan Agreement</h6>
                        <p>The borrower agrees to repay the loan amount plus interest within the specified term.</p>

                        <h6 class="fw-bold mt-4 mb-2">2. Interest Rate</h6>
                        <p>Fixed at <strong>5% monthly</strong> on the principal loan amount.</p>

                        <h6 class="fw-bold mt-4 mb-2">3. Repayment</h6>
                        <p>Payments are due on the <strong>15th of each month</strong>.</p>

                        <h6 class="fw-bold mt-4 mb-2">4. Late Payment Penalty</h6>
                        <p>A <strong>2% penalty</strong> will be charged on any late payment.</p>

                        <h6 class="fw-bold mt-4 mb-2">5. Default</h6>
                        <p>In the event of default, the lender reserves the right to initiate legal proceedings.</p>

                        <h6 class="fw-bold mt-4 mb-2">6. Co-Maker</h6>
                        <p>The co-maker is jointly and severally liable for the loan.</p>

                        <div class="alert alert-info mt-4 mb-0">
                            <i class="feather-info me-2"></i>
                            By accepting, you agree to be bound by these terms.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <div class="form-check mb-0 me-auto">
                            <input class="form-check-input" type="checkbox" id="agreeTerms">
                            <label class="form-check-label" for="agreeTerms">
                                I have read and agree to the Terms and Conditions
                            </label>
                        </div>

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="feather-x me-2"></i>
                            Cancel
                        </button>

                        <button type="button" class="btn btn-primary" id="finalSubmitBtn" onclick="submitApplication()" disabled>
                            <i class="feather-check me-2"></i>
                            Accept & Submit
                        </button>
                    </div>

                </div>
            </div>
        </div>

    @endpush

    @section('page-scripts')
        <script>
            window.APPLY_LOAN_CONFIG = {
                csrfToken: '{{ csrf_token() }}',
                submitUrl: '{{ url("/api/borrower/apply-loan") }}'
            };
        </script>
        <script src="{{ asset('functions/apply-loan.js') }}"></script>
    @endsection

@endunless