@extends('borrower.layout')

@section('title', 'Apply for a Loan')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/apply-loan.css') }}?v={{ time() }}">
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
                        $cardTitle   = 'Application In Review';
                        $cardSubtext = "You'll be notified once a decision is made.";
                        $cardNote    = 'A new application can be submitted once this one is approved or rejected.';

                        if (
                            $existingApplication->status === 'approved' ||
                            $existingApplication->status === 'active'
                        ) {
                            $cardTitle   = 'You Have an Active Loan';
                            $cardSubtext = 'Your current loan is active and being repaid.';
                            $cardNote    = 'You can apply for a new loan once your active loan has been fully paid off.';
                        }
                    @endphp

                    {{-- Pending / Active Loan Notice --}}
                    <div class="pending-section">
                        <div class="pending-section__head">
                            <div class="pending-section__head-text">
                                <span class="pending-eyebrow">Current Loan</span>
                                <h5 class="mb-0">{{ $cardTitle }}</h5>
                                <p class="mb-0">{{ $cardSubtext }}</p>
                            </div>
                            <span class="status-badge {{ $existingApplication->status }}">
                                {{ ucfirst(str_replace('_', ' ', $existingApplication->status)) }}
                            </span>
                        </div>

                        <div class="bwl-stat-grid">
                            <div class="bwl-stat-card">
                                <div class="bwl-stat-top">
                                    <span class="bwl-stat-label">Application ID</span>
                            
                                </div>
                                <div class="bwl-stat-value">#{{ $existingApplication->application_id }}</div>
                            </div>

                            <div class="bwl-stat-card">
                                <div class="bwl-stat-top">
                                    <span class="bwl-stat-label">Loan Amount</span>
                                  
                                </div>
                                <div class="bwl-stat-value">₱{{ number_format($existingApplication->loan_amount, 2) }}</div>
                            </div>

                            <div class="bwl-stat-card">
                                <div class="bwl-stat-top">
                                    <span class="bwl-stat-label">Date Applied</span>
                                 
                                </div>
                                <div class="bwl-stat-value">{{ \Carbon\Carbon::parse($existingApplication->applied_at)->format('M d, Y') }}</div>
                            </div>
                        </div>

                        <p class="pending-note">{{ $cardNote }}</p>

                        <a href="{{ route('borrower.loan-details') }}" class="pending-card__cta">
                            View Application Status
                           
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