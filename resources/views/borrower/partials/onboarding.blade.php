{{-- Onboarding card — lalabas kapag incomplete pa ang personal details ng borrower.
     Ginagamit sa borrower/dashboard.blade.php gamit ang @include('borrower.partials.onboarding').
     Kailangan ng $userName mula sa controller (galing na sa extractCommonFields). --}}

@php
    // Unang salita lang ng pangalan para sa greeting
    $firstName = trim(explode(' ', trim($userName ?? ''))[0] ?? '');
@endphp

<div class="row justify-content-center">
    <div class="col-lg-8">
        <section class="bwl-ob" aria-labelledby="bwlObTitle">

            <h5 class="bwl-ob-title" id="bwlObTitle">
                {{ $firstName ? 'Welcome, ' . $firstName : 'Welcome' }}
            </h5>
            <p class="bwl-ob-lede">
                One last step before you can apply for a loan. Fill in your personal details
                to get started.
            </p>

            {{-- Ano ang kailangan ihanda --}}
            <ol class="bwl-ob-steps">
                <li class="bwl-ob-step">
                    <span class="bwl-ob-step-num" aria-hidden="true">01</span>
                    <div>
                        <p class="bwl-ob-step-title">Personal &amp; contact info</p>
                        <p class="bwl-ob-step-desc">Your full name, birthday, phone number, and email.</p>
                    </div>
                </li>
                <li class="bwl-ob-step">
                    <span class="bwl-ob-step-num" aria-hidden="true">02</span>
                    <div>
                        <p class="bwl-ob-step-title">Address &amp; employment</p>
                        <p class="bwl-ob-step-desc">Where you live, where you work, and your monthly income.</p>
                    </div>
                </li>
                <li class="bwl-ob-step">
                    <span class="bwl-ob-step-num" aria-hidden="true">03</span>
                    <div>
                        <p class="bwl-ob-step-title">Valid ID &amp; co-maker</p>
                        <p class="bwl-ob-step-desc">
                            Photos of your ID (front and back), and your co-maker's name and contact number.
                            Keep these ready.
                        </p>
                    </div>
                </li>
            </ol>

            {{-- Ipakita ang mabubuksan pagkatapos mag-submit --}}
            <div class="bwl-ob-unlock">
                <p class="bwl-ob-unlock-title">Unlocked after you submit</p>
                <ul class="bwl-ob-unlock-list">
                    <li class="bwl-ob-unlock-item">
                        <span class="bwl-ob-unlock-icon"><i class="feather-lock"></i></span>
                        <span>
                            <strong>Apply for a loan.</strong>
                            Your first loan can be up to ₱10,000, paid over 3, 6, or 12 months.
                        </span>
                    </li>
                    <li class="bwl-ob-unlock-item">
                        <span class="bwl-ob-unlock-icon"><i class="feather-lock"></i></span>
                        <span>
                            <strong>Track your payments.</strong>
                            See your schedule, due dates, and remaining balance.
                        </span>
                    </li>
                </ul>
            </div>

            <div class="bwl-ob-actions">
                <a href="{{ route('borrower.personal-details') }}" class="bwl-ob-btn">
                    Fill up personal details
                </a>
            </div>

        </section>
    </div>
</div>