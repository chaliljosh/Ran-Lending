<div class="wr-card">
    <div class="wr-hero">
        <img class="wr-hero-img" src="{{ asset('images/loan-approved.png') }}" alt="">
        <div class="wr-hero-text">
            <h1>Your loan has been approved!</h1>
            <p>All required approvals are complete. Your loan is now waiting for release. Your payment schedule will be available once the funds have been released.</p>
        </div>
    </div>

    <div class="wr-steps">
        <div class="wr-step done">
            <span class="wr-dot"><i class="fa-solid fa-check"></i></span>
            <span class="wr-label">Application Submitted</span>
        </div>
        <span class="wr-line done"></span>
        <div class="wr-step done">
            <span class="wr-dot"><i class="fa-solid fa-check"></i></span>
            <span class="wr-label">Under Review</span>
        </div>
        <span class="wr-line done"></span>
        <div class="wr-step done">
            <span class="wr-dot"><i class="fa-solid fa-check"></i></span>
            <span class="wr-label">Approved</span>
        </div>
        <span class="wr-line current"></span>
        <div class="wr-step current">
            <span class="wr-dot"><i class="fa-regular fa-clock"></i></span>
            <span class="wr-label">Loan Release</span>
        </div>
    </div>

    <div class="wr-summary">
        <div class="wr-summary-item">
            <span class="wr-summary-label">Application #</span>
            <strong>#{{ $application->application_id }}</strong>
        </div>
        <div class="wr-summary-item">
            <span class="wr-summary-label">Loan Amount</span>
            <strong>₱{{ number_format($application->loan_amount, 2) }}</strong>
        </div>
        <div class="wr-summary-item">
            <span class="wr-summary-label">Submitted On</span>
            <strong>{{ \Carbon\Carbon::parse($application->created_at)->format('M d, Y') }}</strong>
        </div>
    </div>

    <div class="wr-bottom">
        <div class="wr-next">
            <img class="wr-next-img" src="{{ asset('images/loan-calendar.png') }}" alt="">
            <div>
                <h3>What's Next?</h3>
                <p>Just wait for the release confirmation from Ran Serenity. We'll notify you once it's available.</p>
            </div>
        </div>
        <div class="wr-help">
            <h3>Need Help?</h3>
            <p>If you have any concerns, feel free to contact our support team.</p>
            <a href="#" class="wr-help-btn">Contact Support</a>
        </div>
    </div>
</div>