<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Monthly Report — {{ $periodLabel }}</title>
    {{-- Inline CSS on purpose: dompdf can't load our external stylesheets --}}
    <style>
        /* DejaVu Sans is used because it has the ₱ glyph */
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .muted { color: #6b7280; margin: 0 0 16px; }
        h2 { font-size: 13px; margin: 20px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; }
        th { background: #f3f4f6; text-align: left; }
        .r { text-align: right; }
        .summary td:first-child { width: 60%; }
        .total th { background: #e5e7eb; }
        .empty { text-align: center; color: #6b7280; }
    </style>
</head>
<body>
    <h1>Ran Serenity — Monthly Report</h1>
    <p class="muted">{{ $periodLabel }} &bull; Generated {{ $generatedAt->format('M d, Y g:i A') }}</p>

    <h2>Summary</h2>
    <table class="summary">
        <tr><td>Applications approved</td><td class="r">{{ number_format($summary['approved']) }}</td></tr>
        <tr><td>Money collected</td><td class="r">₱{{ number_format($summary['collected'], 2) }}</td></tr>
        <tr><td>Total interest (collected)</td><td class="r">₱{{ number_format($summary['interest'], 2) }}</td></tr>
        <tr><td>Penalties collected</td><td class="r">₱{{ number_format($summary['penalty'], 2) }}</td></tr>
    </table>

    @if ($month === 0)
        <h2>Month by Month — {{ $year }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="r">Approved</th>
                    <th class="r">Collected</th>
                    <th class="r">Interest</th>
                    <th class="r">Penalties</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td>{{ $r['label'] }}</td>
                        <td class="r">{{ number_format($r['approved']) }}</td>
                        <td class="r">₱{{ number_format($r['collected'], 2) }}</td>
                        <td class="r">₱{{ number_format($r['interest'], 2) }}</td>
                        <td class="r">₱{{ number_format($r['penalty'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <th>Total</th>
                    <th class="r">{{ number_format(collect($rows)->sum('approved')) }}</th>
                    <th class="r">₱{{ number_format(collect($rows)->sum('collected'), 2) }}</th>
                    <th class="r">₱{{ number_format(collect($rows)->sum('interest'), 2) }}</th>
                    <th class="r">₱{{ number_format(collect($rows)->sum('penalty'), 2) }}</th>
                </tr>
            </tbody>
        </table>
    @endif

    <h2>Approved Applications</h2>
    <table>
        <thead>
            <tr>
                <th>Loan ID</th>
                <th>Borrower</th>
                <th class="r">Loan Amount</th>
                <th class="r">Term</th>
                <th class="r">Interest Collected</th>
                <th>Approved On</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($approvedList as $a)
                <tr>
                    <td>{{ $a->loan_code }}</td>
                    <td>{{ $a->borrower_name }}</td>
                    <td class="r">₱{{ number_format($a->loan_amount, 2) }}</td>
                    <td class="r">{{ $a->loan_term }} mo</td>
                    <td class="r">₱{{ number_format($a->interest_collected, 2) }}</td>
                    <td>{{ \Carbon\Carbon::parse($a->approved_at)->format('M d, Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No approved applications for this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>