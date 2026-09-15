@extends('borrower.layout')

@section('title', 'Apply for a Loan')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/apply-loan.css') }}?v={{ time() }}">
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

                        <a href="{{ route('borrower.loan-details') }}" class="aln-link">
                            View Application Status
                        </a>
                    </section>
                @else
                    {{-- INFO NOTICE --}}
                    <div class="aln-notice">
                        @if ($isFirstTime)
                            <strong>First-Time Borrowers:</strong>
                            Maximum loan amount is ₱10,000. After successful repayment, you may apply for higher amounts up to
                            ₱100,000.
                        @else
                            <strong>You're Eligible for Higher Amounts:</strong>
                            Since you've successfully repaid a previous loan, you may now apply for up to
                            ₱{{ number_format($maxLoanAmount) }}.
                        @endif
                    </div>

                    {{-- LOAN APPLICATION FORM --}}
                    <form id="loanApplicationForm" onsubmit="return false;">

                        {{-- Loan Details --}}
                        <section class="aln-section">
                            <h6 class="aln-section-title">Loan Details</h6>
                            <div class="aln-hr aln-hr--tight"></div>

                            <div class="row g-3">

                                {{-- Loan Amount --}}
                                <div class="col-md-6">
                                    <label class="aln-label">
                                        Loan Amount <span class="aln-required">*</span>
                                    </label>
                                    <div class="input-group aln-input-group">
                                        <span class="input-group-text aln-input-prefix">₱</span>
                                        <input type="number" class="form-control aln-input" id="loanAmount"
                                            placeholder="1,000 – {{ number_format($maxLoanAmount) }}" min="1000"
                                            max="{{ $maxLoanAmount }}" step="100" required>
                                    </div>
                                    <small class="aln-hint">Min: ₱1,000 &nbsp;|&nbsp; Max:
                                        ₱{{ number_format($maxLoanAmount) }}</small>
                                </div>

                                {{-- Loan Term --}}
                                <div class="col-md-6">
                                    <label class="aln-label">
                                        Term <span class="aln-required">*</span>
                                    </label>
                                    <select class="form-select aln-input" id="loanTerm" required>
                                        <option value="">Select term</option>
                                        <option value="3">3 Months</option>
                                        <option value="6">6 Months</option>
                                        <option value="12">12 Months</option>
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

                        {{-- LOAN SUMMARY --}}
                        <section class="aln-section">
                            <h6 class="aln-section-title">Loan Summary</h6>
                            <div class="aln-hr aln-hr--tight"></div>

                            <div class="aln-summary-row">
                                <div class="aln-summary-item">
                                    <span class="aln-summary-label">Principal</span>
                                    <span class="aln-summary-value" id="displayPrincipal">₱0.00</span>
                                </div>
                                <div class="aln-summary-item">
                                    <span class="aln-summary-label">Interest Rate</span>
                                    <span class="aln-summary-value">5% / month</span>
                                </div>
                                <div class="aln-summary-item">
                                    <span class="aln-summary-label">Total Interest</span>
                                    <span class="aln-summary-value" id="displayTotalInterest">₱0.00</span>
                                </div>
                                <div class="aln-summary-item">
                                    <span class="aln-summary-label">Total to Pay</span>
                                    <span class="aln-summary-value aln-summary-value--strong"
                                        id="displayTotalAmount">₱0.00</span>
                                </div>
                            </div>

                            <div class="aln-hr aln-hr--tight"></div>

                            <div class="aln-summary-row">
                                <div class="aln-summary-item">
                                    <span class="aln-summary-label">Term</span>
                                    <span class="aln-summary-value" id="displayTerm">— months</span>
                                </div>
                                <div class="aln-summary-item">
                                    <span class="aln-summary-label">Monthly Payment</span>
                                    <span class="aln-summary-value aln-summary-value--strong"
                                        id="displayMonthlyPayment">₱0.00</span>
                                </div>
                                <div class="aln-summary-item">
                                    <span class="aln-summary-label">Due Date</span>
                                    <span class="aln-summary-value">15th of each month</span>
                                </div>
                            </div>
                        </section>

                        {{-- FORM BUTTONS --}}
                        <div class="aln-actions">
                            <button type="button" class="aln-btn aln-btn--primary" id="submitBtn"
                                data-bs-toggle="modal" data-bs-target="#termsModal" disabled>
                                Submit Application
                            </button>

                            <a href="{{ route('borrower.dashboard') }}" class="aln-btn aln-btn--secondary">
                                Cancel
                            </a>
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
        {{-- TERMS MODAL --}}
        <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content aln-modal">

                    <div class="modal-body">
                        <h6 class="aln-modal-title">Loan Terms and Conditions</h6>
                        <p>Please read these terms carefully before submitting your loan application.</p>

                        <h6 class="aln-modal-subtitle">1. Loan Agreement</h6>
                        <p>The borrower agrees to repay the loan amount plus interest within the specified term.</p>

                        <h6 class="aln-modal-subtitle">2. Interest Rate</h6>
                        <p>Fixed at <strong>5% monthly</strong> on the principal loan amount.</p>

                        <h6 class="aln-modal-subtitle">3. Repayment</h6>
                        <p>Payments are due on the <strong>15th of each month</strong>.</p>

                        <h6 class="aln-modal-subtitle">4. Late Payment Penalty</h6>
                        <p>A <strong>2% penalty</strong> will be charged on any late payment.</p>

                        <h6 class="aln-modal-subtitle">5. Default</h6>
                        <p>In the event of default, the lender reserves the right to initiate legal proceedings.</p>

                        <h6 class="aln-modal-subtitle">6. Co-Maker</h6>
                        <p>The co-maker is jointly and severally liable for the loan.</p>

                        <div class="aln-modal-notice">
                            By accepting, you agree to be bound by these terms.
                        </div>
                    </div>

                    <div class="modal-footer aln-modal-footer">
                        <div class="form-check mb-0 me-auto">
                            <input class="form-check-input" type="checkbox" id="agreeTerms">
                            <label class="form-check-label" for="agreeTerms">
                                I have read and agree to the Terms and Conditions
                            </label>
                        </div>

                        <button type="button" class="aln-btn aln-btn--secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="button" class="aln-btn aln-btn--primary" id="finalSubmitBtn"
                            onclick="submitApplication()" disabled>
                            Accept &amp; Submit
                        </button>
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
    <script src="{{ asset('functions/apply-loan.js') }}" data-csrf="{{ csrf_token() }}"
        data-submit-url="{{ url('/api/borrower/apply-loan') }}"
        data-max-amount="{{ $maxLoanAmount ?? 10000 }}"></script>
@endsection