@extends('borrower.layout')

@section('title', 'Terms & Conditions')

@section('page-head-links')
    <link rel="stylesheet" href="{{ asset('assets/borrower/css/faq.css') }}?v={{ time() }}">
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Terms & Conditions</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Terms & Conditions</li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="faq-intro">
            <h5 class="fw-bold mb-1">Terms & Conditions</h5>
            <p class="text-muted mb-0">Last updated: {{ now()->format('F Y') }}. By using Ran Serenity, you agree to the terms below.</p>
        </div>

        <div class="legal-section">
            <span class="legal-section__num">01</span>
            <div class="legal-section__body">
                <h6 class="legal-section__title">Eligibility & Verification</h6>
                <p class="legal-text">To apply for a loan, you must submit accurate personal, address, and employment information. Your account must be reviewed and approved by our admin team before loan applications are unlocked. Providing false or misleading information may result in account suspension.</p>
            </div>
        </div>

        <div class="legal-section">
            <span class="legal-section__num">02</span>
            <div class="legal-section__body">
                <h6 class="legal-section__title">Loan Terms</h6>
                <p class="legal-text">Approved loans carry a fixed interest rate of 5% per month, applied over your chosen loan term. The total amount payable, including interest, is disclosed to you before you confirm your application. Monthly payments are due on the agreed schedule shown on your Loan Details page.</p>
            </div>
        </div>

        <div class="legal-section">
            <span class="legal-section__num">03</span>
            <div class="legal-section__body">
                <h6 class="legal-section__title">Late Payments & Penalties</h6>
                <p class="legal-text">Payments not received by their due date may incur penalty charges. Loans with payments overdue for an extended period may be marked as defaulted, which may affect your eligibility for future loans.</p>
            </div>
        </div>

        <div class="legal-section">
            <span class="legal-section__num">04</span>
            <div class="legal-section__body">
                <h6 class="legal-section__title">Co-Maker Responsibility</h6>
                <p class="legal-text">If a co-maker is designated on your account, they may be contacted regarding your loan obligations in the event of missed or late payments.</p>
            </div>
        </div>

        <div class="legal-section">
            <span class="legal-section__num">05</span>
            <div class="legal-section__body">
                <h6 class="legal-section__title">Changes to These Terms</h6>
                <p class="legal-text">These terms may be updated from time to time. Continued use of Ran Serenity after changes are posted constitutes acceptance of the updated terms.</p>
            </div>
        </div>
    </div>
@endsection