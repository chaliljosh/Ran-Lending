{{-- Assigned Payment Account — included in admin/ongoing-loan-detail.blade.php
     Needs: $app (loan row with ->uuid and ->status), $applicationId --}}
@php
    $assignedMethod = \App\Services\PaymentMethodService::forLoan($applicationId);
    $activeMethods = \App\Services\PaymentMethodService::activeMethods();
    $canAssignMethod = in_array($app->status, ['approved', 'active'], true);

    // Logo files for the known banks / e-wallets (png, jpg, jpeg, webp).
    // Files live in: public/assets/admin/images/banks/  (lowercase names, e.g. gcash.jpg)
    $pmLogoMap = [];
    foreach (['gcash', 'maya', 'bpi', 'bdo', 'metrobank', 'unionbank', 'landbank'] as $pmKey) {
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $pmExt) {
            if (file_exists(public_path("assets/admin/images/banks/{$pmKey}.{$pmExt}"))) {
                $pmLogoMap[$pmKey] = "{$pmKey}.{$pmExt}";
                break;
            }
        }
    }
    $assignedProvider = $assignedMethod->provider ?? '';
    $assignedLogoFile = $pmLogoMap[strtolower($assignedProvider)] ?? null;
@endphp

<div class="panel pm-assign-panel" id="pmAssignPanel"
    data-assign-url="{{ route('admin.loan-payment-method-assign', $app->uuid) }}"
    data-current-id="{{ $assignedMethod->id ?? '' }}"
    data-logo-base="{{ asset('assets/admin/images/banks') }}"
    data-logo-map="{{ json_encode($pmLogoMap) }}">
    <div class="panel-header">Payment Account for this Loan</div>
    <div class="panel-body">
        <div class="pm-assign-body">

            {{-- Current account details --}}
            <div>
                <div class="pm-none-box" id="pmAssignedNone" style="{{ $assignedMethod ? 'display:none;' : '' }}">
                    No payment account assigned yet. The borrower will see a notice until you assign one.
                </div>

                <div class="pm-assigned-box" id="pmAssignedBox" style="{{ $assignedMethod ? '' : 'display:none;' }}">
                    <div class="pm-assigned-top">
                        <span class="pm-assigned-brand">
                            <span class="pm-logo" id="pmdLogo">
                                @if ($assignedLogoFile)
                                    <img src="{{ asset('assets/admin/images/banks/' . $assignedLogoFile) }}"
                                        alt="{{ $assignedProvider }}" width="30" height="30">
                                @elseif ($assignedProvider !== '')
                                    <span class="pm-logo-fallback">{{ strtoupper(mb_substr($assignedProvider, 0, 2)) }}</span>
                                @endif
                            </span>
                            <span class="pm-assigned-provider" id="pmdProvider">{{ $assignedProvider }}</span>
                        </span>
                        <span class="pm-pill is-inactive" id="pmdInactive"
                            style="{{ $assignedMethod && !$assignedMethod->is_active ? '' : 'display:none;' }}">Inactive</span>
                    </div>

                    <div class="pm-assigned-grid">
                        <div class="pm-assigned-item">
                            <label>Account Holder</label>
                            <span id="pmdName">{{ $assignedMethod->account_name ?? '' }}</span>
                        </div>
                        <div class="pm-assigned-item">
                            <label>Account Number</label>
                            <span class="pm-mono" id="pmdNumber">{{ $assignedMethod->account_number ?? '' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Select / change --}}
            <div class="pm-assign-form">
                @if ($canAssignMethod && $activeMethods->count() > 0)
                    <label for="pmAssignSelect" class="pm-label">{{ $assignedMethod ? 'Change the account the borrower sends to' : 'Borrower sends payment to' }}</label>

                    {{-- Real select: the assignment JS keeps reading this one. Hidden by payment-account-dropdown.js once the custom dropdown is ready (works as a fallback if that file fails to load). --}}
                    <select id="pmAssignSelect" class="pm-input">
                        <option value="">Select an account</option>
                        @foreach ($activeMethods as $m)
                            <option value="{{ $m->id }}" @selected(($assignedMethod->id ?? null) == $m->id)>{{ $m->provider }} — {{ $m->account_name }}</option>
                        @endforeach
                    </select>

                    {{-- Custom dropdown with bank logos (mirrors the select above) --}}
                    <div class="pm-dd" id="pmAssignDd" hidden>
                        <button type="button" class="pm-input pm-dd-toggle" id="pmDdToggle"
                            aria-haspopup="listbox" aria-expanded="false">
                            <span class="pm-dd-current" id="pmDdCurrent"></span>
                            <i class="feather-chevron-down pm-dd-caret"></i>
                        </button>
                        <ul class="pm-dd-menu" id="pmDdMenu" role="listbox" hidden>
                            <li class="pm-dd-option pm-dd-empty" role="option" tabindex="-1" data-value="">
                                <span class="pm-dd-placeholder">Select an account</span>
                            </li>
                            @foreach ($activeMethods as $m)
                                @php $optLogo = $pmLogoMap[strtolower($m->provider)] ?? null; @endphp
                                <li class="pm-dd-option" role="option" tabindex="-1" data-value="{{ $m->id }}"
                                    data-label="{{ $m->provider }} — {{ $m->account_name }}"
                                    data-provider="{{ $m->provider }}"
                                    data-holder="{{ $m->account_name }}"
                                    data-number="{{ $m->account_number }}">
                                    <span class="pm-logo">
                                        @if ($optLogo)
                                            <img src="{{ asset('assets/admin/images/banks/' . $optLogo) }}"
                                                alt="{{ $m->provider }}" width="26" height="26">
                                        @else
                                            <span class="pm-logo-fallback">{{ strtoupper(mb_substr($m->provider, 0, 2)) }}</span>
                                        @endif
                                    </span>
                                    <span class="pm-dd-text">
                                        <strong>{{ $m->provider }}</strong>
                                        <small>{{ $m->account_name }} &middot; {{ $m->account_number }}</small>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="pm-btn pm-btn-primary" id="pmAssignBtn" disabled>
                        {{ $assignedMethod ? 'Change Assignment' : 'Assign' }}
                    </button>
                    <div class="pm-assign-feedback" id="pmAssignFeedback" role="status"></div>

                    @if ($assignedMethod && !$assignedMethod->is_active)
                        <div class="pm-assign-note" id="pmInactiveNote">This account is now inactive. It stays on this loan until you pick another one.</div>
                    @endif
                @elseif (!$canAssignMethod)
                    <div class="pm-assign-note">A payment account can only be assigned while the loan is ongoing.</div>
                @else
                    <div class="pm-assign-note">
                        There are no active payment accounts.
                        <a href="{{ route('admin.payment-methods') }}">Add or activate one</a> first.
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

{{-- CONFIRM ASSIGN / CHANGE (opened by payment-account-dropdown.js before the assignment runs) --}}
@push('modals')
    <div class="modal fade pm-modal" id="pmAssignConfirmModal" tabindex="-1"
        aria-labelledby="pmAssignConfirmTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="pmAssignConfirmTitle">Assign payment account?</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="pm-confirm-text" id="pmAssignConfirmText"></p>

                    <div class="pm-confirm-account">
                        <span class="pm-logo" id="pmAssignConfirmLogo"></span>
                        <div class="pm-confirm-account-text">
                            <strong id="pmAssignConfirmProvider"></strong>
                            <span id="pmAssignConfirmHolder"></span>
                            <span class="pm-mono" id="pmAssignConfirmNumber"></span>
                        </div>
                    </div>

                    <div class="pm-error" id="pmAssignConfirmError" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="pm-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="pm-btn pm-btn-primary" id="pmAssignConfirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>
@endpush

{{-- SUCCESS (shown after the assignment is saved) --}}
@push('modals')
    <div class="modal fade pm-modal pm-success-modal" id="pmAssignSuccessModal" tabindex="-1"
        aria-labelledby="pmAssignSuccessTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body pm-success-body">
                    <div class="pm-success-icon"><i class="feather-check"></i></div>
                    <div class="pm-success-title" id="pmAssignSuccessTitle">Payment Account Assigned!</div>
                    <p class="pm-success-text" id="pmAssignSuccessText"></p>

                    <div class="pm-success-details">
                        <div class="pm-success-row">
                            <label>Payment Provider</label>
                            <strong id="pmAssignSuccessProvider"></strong>
                        </div>
                        <div class="pm-success-row">
                            <label>Account Holder</label>
                            <strong id="pmAssignSuccessHolder"></strong>
                        </div>
                        <div class="pm-success-row">
                            <label>Account Number</label>
                            <strong class="pm-mono" id="pmAssignSuccessNumber"></strong>
                        </div>
                    </div>

                    <button type="button" class="pm-success-done" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>
@endpush