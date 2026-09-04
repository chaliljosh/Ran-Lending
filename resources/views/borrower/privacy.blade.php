@extends('borrower.layout')

@section('title', 'Privacy Policy')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/faq.css') }}?v={{ time() }}">
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Privacy Policy</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Privacy Policy</li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="faq-intro">
            <h5 class="fw-bold mb-1">Privacy Policy</h5>
            <p class="text-muted mb-0">Last updated: {{ now()->format('F Y') }}. This explains what information we collect and how it's used.</p>
        </div>

        <div class="legal-section">
            <span class="legal-section__num">01</span>
            <div class="legal-section__body">
                <h6 class="legal-section__title">Information We Collect</h6>
                <p class="legal-text">We collect personal information you provide during registration and verification, including your name, birthday, gender, civil status, citizenship, contact number, email address, home address, valid ID, employment details, monthly income, and co-maker information.</p>
            </div>
        </div>

        <div class="legal-section">
            <span class="legal-section__num">02</span>
            <div class="legal-section__body">
                <h6 class="legal-section__title">How We Use Your Information</h6>
                <p class="legal-text">Your information is used to verify your identity, assess loan eligibility, process loan applications, and manage your account. It is reviewed by authorized admin staff as part of our borrower verification process.</p>
            </div>
        </div>

        <div class="legal-section">
            <span class="legal-section__num">03</span>
            <div class="legal-section__body">
                <h6 class="legal-section__title">How We Protect Your Information</h6>
                <p class="legal-text">Your data is stored securely and access is limited to authorized personnel involved in reviewing and managing borrower accounts and loan applications.</p>
            </div>
        </div>

        <div class="legal-section">
            <span class="legal-section__num">04</span>
            <div class="legal-section__body">
                <h6 class="legal-section__title">Data Sharing</h6>
                <p class="legal-text">We do not sell or share your personal information with third parties outside of what is necessary to operate and administer your loan account.</p>
            </div>
        </div>

        <div class="legal-section">
            <span class="legal-section__num">05</span>
            <div class="legal-section__body">
                <h6 class="legal-section__title">Questions</h6>
                <p class="legal-text">If you have questions about how your information is handled, please reach out through the Help & FAQ page.</p>
            </div>
        </div>
    </div>
@endsection