{{-- Fully paid dashboard. Gumagamit ng variables mula sa dashboard(): $app, $amountPaid, $paidCount, $totalCount, $allPayments, $settledAt --}}
@php
    $fpHasImg = file_exists(public_path('assets/borrower/images/loan-completed-illustration.png'));
    $fpMonthly = $totalCount > 0 ? $app->total_amount / $totalCount : 0;
@endphp

<div class="bwl-fp">

    <div class="bwl-fp-hero">
        <div class="bwl-fp-art">
            @if ($fpHasImg)
                <img src="{{ asset('assets/borrower/images/loan-completed-illustration.png') }}" alt="" loading="lazy">
            @else
                <svg viewBox="0 0 200 200" fill="none" aria-hidden="true">
                    <circle cx="100" cy="100" r="86" fill="#D9F2E3"/>
                    <rect x="56" y="36" width="88" height="124" rx="10" fill="#fff" stroke="#0B1F4D" stroke-width="3"/>
                    <circle cx="100" cy="84" r="24" fill="#16A34A"/>
                    <polyline points="89 84 97 92 112 75" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="74" y="124" width="52" height="6" rx="3" fill="#C9D6EE"/>
                    <rect x="74" y="138" width="36" height="6" rx="3" fill="#C9D6EE"/>
                </svg>
            @endif
        </div>

        <div class="bwl-fp-text">
            <span class="bwl-fp-chip">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Loan Completed
            </span>
            <h2 class="bwl-fp-title">Congratulations!</h2>
            <p class="bwl-fp-desc">Your loan with RAN Serenity has been fully paid. Thank you for your trust and continued support!</p>
            <a href="{{ route('borrower.loan-details') }}" class="bwl-fp-btn">
                View Loan Details
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>

        <div class="bwl-fp-stats">
            <div class="bwl-fp-stat-row"><span>Loan Amount</span><strong>₱{{ number_format($app->loan_amount, 2) }}</strong></div>
            <div class="bwl-fp-stat-row"><span>Total Paid</span><strong>₱{{ number_format($amountPaid, 2) }}</strong></div>
            <div class="bwl-fp-settled">
                <span class="bwl-fp-settled-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </span>
                <div>
                    <div class="bwl-fp-settled-title">Fully Paid</div>
                    @if ($settledAt)
                        <div class="bwl-fp-settled-sub">Loan settled on {{ \Carbon\Carbon::parse($settledAt)->format('M d, Y') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="bwl-fp-grid">
        <div class="bwl-fp-card">
            <h3 class="bwl-fp-card-title">Loan Overview</h3>
            <div class="bwl-fp-overview">
                <div class="bwl-fp-list">
                    <div class="bwl-fp-row"><span>Original Amount</span><strong>₱{{ number_format($app->loan_amount, 2) }}</strong></div>
                    <div class="bwl-fp-row"><span>Outstanding Balance</span><strong class="bwl-fp-zero">₱{{ number_format($outstandingAmount, 2) }}</strong></div>
                    <div class="bwl-fp-row"><span>Monthly Payment</span><strong>₱{{ number_format($fpMonthly, 2) }}</strong></div>
                    <div class="bwl-fp-row"><span>Interest Rate</span><strong>{{ number_format($app->interest_rate, 0) }}% monthly</strong></div>
                    <div class="bwl-fp-row"><span>Loan Term</span><strong>{{ $app->loan_term }} months</strong></div>
                    <div class="bwl-fp-row"><span>Payments Made</span><strong>{{ $paidCount }} / {{ $totalCount }}</strong></div>
                </div>
                <div class="bwl-fp-done">
                    <span class="bwl-fp-done-icon">
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </span>
                    <div class="bwl-fp-done-title">Fully Paid</div>
                    <div class="bwl-fp-done-sub">Your loan has been successfully completed.</div>
                </div>
            </div>
        </div>

        <div class="bwl-fp-card">
            <div class="bwl-fp-card-head">
                <h3 class="bwl-fp-card-title">Payment History</h3>
                <a href="{{ route('borrower.loan-details') }}" class="bwl-fp-link">View All →</a>
            </div>
            <div class="bwl-fp-table-wrap">
                <table class="bwl-fp-table">
                    <thead>
                        <tr><th>Payment #</th><th>Amount</th><th>Payment Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($allPayments as $p)
                            <tr>
                                <td>{{ $p->installment_no }}</td>
                                <td>₱{{ number_format($p->amount_paid, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->paid_at)->format('M d, Y') }}</td>
                                <td><span class="bwl-fp-paid">Paid</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bwl-fp-help">
        <div>
            <h3 class="bwl-fp-help-title">Need help?</h3>
            <p class="bwl-fp-help-desc">If you have any questions, feel free to contact our support team.</p>
        </div>
        <a href="{{ route('borrower.faq') }}" class="bwl-fp-btn">Contact Support</a>
    </div>

</div>