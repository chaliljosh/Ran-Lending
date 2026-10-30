{{-- resources/views/borrower/partials/assigned-payment-method.blade.php
     Read-only. Ipinapakita ang payment account na itinakda ng admin sa loan.
     Kailangan: $assignedPaymentMethod (object o null) mula sa BorrowerController::loanDetails(). --}}
@php
    $pm = $assignedPaymentMethod ?? null;
    // Para sa Copy button: tanggal ang spaces at dashes para madaling i-paste sa payment app.
    $pmCopyValue = $pm ? preg_replace('/[\s-]+/', '', (string) $pm->account_number) : '';
    // Uri ng account (tag lang). Walang tag kapag "Other" kasi hindi natin alam kung bank o e-wallet.
    $pmType = null;
    if ($pm) {
        $pmKey = strtolower(trim((string) $pm->provider));
        if (in_array($pmKey, ['gcash', 'maya'], true)) {
            $pmType = 'E-wallet';
        } elseif (in_array($pmKey, ['bpi', 'bdo', 'metrobank', 'unionbank', 'landbank'], true)) {
            $pmType = 'Bank';
        }
    }
@endphp

<div class="bwl-pm" id="bwlPaymentMethod">
    <div class="bwl-pm__head">
        <span class="bwl-pm__eyebrow">Where to Send Your Payment</span>
        <span class="bwl-pm__hint">Send your payment to this account using your own bank or e-wallet app, then upload your receipt below. Your payment is recorded once the admin verifies it.</span>
    </div>

    @if($pm)
        <div class="bwl-pm__body">
            <div class="bwl-pm__provider">
                <span class="bwl-pm__provider-name">{{ $pm->provider }}</span>
                @if($pmType)
                    <span class="bwl-pm__provider-label">{{ $pmType }}</span>
                @endif
            </div>

            <div class="bwl-pm__grid">
                <div class="bwl-pm__field">
                    <span class="bwl-pm__field-label">Account Holder</span>
                    <div class="bwl-pm__field-value">{{ $pm->account_name }}</div>
                </div>

                <div class="bwl-pm__field">
                    <span class="bwl-pm__field-label">Account Number</span>
                    <div class="bwl-pm__number-row">
                        <span class="bwl-pm__field-value bwl-pm__number">{{ $pm->account_number }}</span>
                        <button type="button" class="bwl-pm__copy" data-pm-copy="{{ $pmCopyValue }}">
                            <i class="feather-copy"></i><span class="bwl-pm__copy-text">Copy Account Number</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bwl-pm__empty">
            <i class="feather-info"></i>
            <span>Your payment account hasn't been set yet. Please wait for the admin to assign one. Once it's assigned, the account details will appear here.</span>
        </div>
    @endif
</div>