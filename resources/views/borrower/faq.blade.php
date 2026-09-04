@extends('borrower.layout')

@section('title', 'Help & FAQ')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/faq.css') }}?v={{ time() }}">
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Help & FAQ</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Help & FAQ</li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="faq-intro">
            <h5 class="fw-bold mb-1">Frequently Asked Questions</h5>
            <p class="text-muted mb-0">Answers to common questions about your account, loan applications, and payments.</p>
        </div>

        <!-- Account & Verification -->
        <div class="faq-card">
            <div class="faq-card__head">
                <i class="feather-user-check"></i>
                <span>Account & Verification</span>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Why do I need to verify my personal information before applying for a loan?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Verification confirms your identity and details are accurate before we allow loan applications. This protects both you and the lending process from fraudulent submissions.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How long does verification take?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Verification typically takes 1-3 business days once your personal details are submitted. You'll see your status update on your dashboard once it's reviewed.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    I submitted my details but "Apply for a Loan" is still locked — why?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Your account is likely still pending admin review. Once your personal information is approved, "Apply for a Loan" will unlock automatically in your sidebar.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How do I edit my personal info, address, or contact number?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Go to <strong>My Profile</strong> and click Edit on the section you want to update. Some fields (like name and birthday) are locked after verification and can't be self-edited.</p>
                </div>
            </div>
        </div>

        <!-- Loan Application -->
        <div class="faq-card">
            <div class="faq-card__head">
                <i class="feather-file-text"></i>
                <span>Loan Application</span>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How do I apply for a loan?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Once your account is verified, go to <strong>Apply for a Loan</strong> in the sidebar and fill out the application form with your desired loan amount and terms.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How long does loan approval take?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Loan applications go through a review process and are typically decided within a few business days. You can check your application status anytime under View Loan Details.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Can I edit my loan application after submitting it?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Once submitted, your application is locked for review. If you need to make changes, contact support before a decision has been made.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Why was my loan application put on hold or rejected?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Applications may be put on hold if additional information is needed, or rejected if they don't meet lending criteria. Check View Loan Details for specific remarks.</p>
                </div>
            </div>
        </div>

        <!-- Payments -->
        <div class="faq-card">
            <div class="faq-card__head">
                <i class="feather-credit-card"></i>
                <span>Payments</span>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How do I know my payment due date?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Your payment schedule and due dates are shown on your loan details page once your loan is approved and active.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    What happens if I miss a payment?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Missed payments may incur penalties and affect your standing for future loan applications. Reach out as early as possible if you expect to miss a due date.</p>
                </div>
            </div>
        </div>

        <!-- Co-Maker -->
        <div class="faq-card">
            <div class="faq-card__head">
                <i class="feather-users"></i>
                <span>Co-Maker</span>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Who can be my co-maker?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>A co-maker is typically a close relative or trusted contact who agrees to help guarantee your loan. Their details are collected during your personal information setup.</p>
                </div>
            </div>
        </div>

        <!-- Account Security -->
        <div class="faq-card">
            <div class="faq-card__head">
                <i class="feather-lock"></i>
                <span>Account Security</span>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How do I change my password?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Go to your profile dropdown at the top right and select Settings, then Change Password.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Is my personal information secure?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Yes. Your data is stored securely and only accessible to authorized admins for verification purposes.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script src="{{ asset('functions/faq.js') }}"></script>
@endsection