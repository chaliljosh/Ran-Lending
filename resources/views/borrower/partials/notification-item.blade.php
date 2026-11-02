@php
    $nfUrl  = route('borrower.notification-open', $n['key']) . '?redirect=' . urlencode($n['url']);
    $nfRead = (bool) ($n['read'] ?? false);
    $nfTs   = $n['ts'] ?? null;
    $nfTime = $nfTs
        ? (($n['date_only'] ?? false) ? $nfTs->format('F j, Y') : $nfTs->format('F j, Y, g:i A'))
        : '';

    // Category para sa filter (Loans / Payments / Account), galing sa prefix ng key.
    $nfCat = \Illuminate\Support\Str::startsWith($n['key'], [
            'loan_status:', 'eligible:', 'submitted:', 'released:', 'onhold:', 'completed:', 'pm:',
        ]) ? 'loans'
        : (\Illuminate\Support\Str::startsWith($n['key'], [
            'paid:', 'voided:', 'penalty', 'payment:', 'receipt', 'partial', 'reject',
        ]) ? 'payments' : 'account');
@endphp
<a href="{{ $nfUrl }}" class="bwl-nf-item {{ $nfRead ? '' : 'is-unread' }}" data-read="{{ $nfRead ? 1 : 0 }}" data-cat="{{ $nfCat }}">
    <span class="bwl-nf-icon is-{{ $n['color'] ?? 'primary' }}"><i class="{{ $n['icon'] ?? 'feather-bell' }}"></i></span>
    <span class="bwl-nf-body">
        <span class="bwl-nf-title">{{ $n['title'] }}</span>
        <span class="bwl-nf-sub">{{ $n['subtitle'] }}</span>
        @if ($nfTime !== '')
            <span class="bwl-nf-time">{{ $nfTime }}</span>
        @endif
    </span>
    <span class="bwl-nf-dot" aria-hidden="true"></span>
</a>