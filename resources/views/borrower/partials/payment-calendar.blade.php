<div class="bwl-dash-cal-month">
    <a href="#" class="bwl-dash-cal-nav" data-month="{{ $calPrevMonth }}" aria-label="Previous month">‹</a>
    <span>{{ $calMonth->format('F Y') }}</span>
    <a href="#" class="bwl-dash-cal-nav" data-month="{{ $calNextMonth }}" aria-label="Next month">›</a>
</div>
<div class="bwl-dash-cal-grid">
    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
        <div class="bwl-dash-cal-dow">{{ $d }}</div>
    @endforeach

    @php
        $firstDow = $calMonth->copy()->startOfMonth()->dayOfWeek;
        $daysInMonth = $calMonth->copy()->endOfMonth()->day;
        $today = \Carbon\Carbon::now()->day;
        $isCurrentMonth = \Carbon\Carbon::now()->isSameMonth($calMonth);
    @endphp

    @for($i = 0; $i < $firstDow; $i++)
        <div class="bwl-dash-cal-day is-empty"></div>
    @endfor

    @for($day = 1; $day <= $daysInMonth; $day++)
        @php $hasPayment = $calDaysWithPayments->has($day); @endphp
        <div class="bwl-dash-cal-day {{ $isCurrentMonth && $day == $today ? 'is-today' : '' }} {{ $hasPayment ? 'has-payment' : '' }}">
            {{ $day }}
        </div>
    @endfor
</div>

@if($nextUnpaid)
    <div class="bwl-dash-cal-next">
        <span class="bwl-dash-cal-dot"></span>
        <div>
            <div class="bwl-dash-cal-next-label">{{ \Carbon\Carbon::parse($nextUnpaid->due_date)->format('M d, Y') }}</div>
            <div class="bwl-dash-cal-next-sub">Next payment due</div>
        </div>
        <strong>₱{{ number_format($nextUnpaid->amount_due, 2) }}</strong>
    </div>
@endif