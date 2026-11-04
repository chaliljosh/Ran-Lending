<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Monthly Report — {{ $periodLabel }}</title>
    {{-- Inline CSS on purpose: dompdf can't load our external stylesheets.
         dompdf walang flex/grid, kaya tables ang gamit sa layout. --}}
    <style>
        /* Colors galing sa landing page */
        /* cream bg #f9f9f4 | dark text #1f1f14 | purple #9b7fd9 | lavender #efebf8 | border #e6e3d8 | muted #7c7c6c */

        @page { margin: 30px 0 70px 0; }

        /* DejaVu Sans is used because it has the ₱ glyph */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #1f1f14;
            background-color: #f9f9f4;
            margin: 0;
        }
        .wrap { padding: 0 36px; }

        table { border-collapse: collapse; }
        .full { width: 100%; }
        .r { text-align: right; }
        .c { text-align: center; }

        /* ---------- Header ---------- */
        .brand { font-size: 22px; font-weight: bold; color: #1f1f14; margin: 0 0 4px; }
        .brand span { color: #9b7fd9; }
        .contact { font-size: 9.5px; color: #7c7c6c; line-height: 1.5; }
        .doc-title { font-size: 22px; font-weight: bold; color: #1f1f14; text-align: right; margin: 0 0 4px; }
        .doc-sub { font-size: 9.5px; color: #7c7c6c; text-align: right; }
        .rule { border: 0; border-top: 2px solid #9b7fd9; margin: 12px 0 0; }

        /* ---------- Section heading ---------- */
        .sec {
            background-color: #efebf8;
            border-left: 4px solid #9b7fd9;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 8px;
        }
        .sec-wrap { margin-top: 18px; }

        /* ---------- Stat cards ---------- */
        .cards { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
        .card {
            background-color: #ffffff;
            border: 1px solid #e6e3d8;
            border-radius: 8px;
            text-align: center;
            padding: 12px 6px;
            width: 25%;
        }
        .card-value { font-size: 17px; font-weight: bold; color: #1f1f14; }
        .card-label { font-size: 9.5px; color: #7c7c6c; margin-top: 3px; }

        /* ---------- Two column layout ---------- */
        .two { width: 100%; border-collapse: separate; border-spacing: 0; }
        .two td.col { width: 50%; vertical-align: top; }
        .two td.gap { width: 18px; }

        /* ---------- Data tables ---------- */
        .data { width: 100%; background-color: #ffffff; }
        .data th, .data td { border: 1px solid #e6e3d8; padding: 6px 8px; }
        .data th { background-color: #efebf8; color: #1f1f14; text-align: left; font-size: 10px; }
        .data tr { page-break-inside: avoid; }
        .data thead { display: table-header-group; }
        .data tr.total td, .data tr.total th { background-color: #efebf8; font-weight: bold; }
        .data td.num { text-align: right; }
        .data td.mid { text-align: center; }
        .data th.r { text-align: right; }
        .data th.mid { text-align: center; }
        .empty { text-align: center; color: #7c7c6c; padding: 12px; }

        /* ---------- Report information ---------- */
        .info {
            background-color: #efebf8;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 9.5px;
            color: #4b4b3f;
        }
        .info-title { font-weight: bold; font-size: 10.5px; color: #1f1f14; margin-bottom: 3px; }

        /* ---------- Footer (bawat page) ---------- */
        .footer { position: fixed; left: 36px; right: 36px; bottom: -48px; }
        .footer-line { border: 0; border-top: 2px solid #9b7fd9; margin: 0 0 6px; }
        .footer td { font-size: 9px; color: #7c7c6c; }
        .tag { font-style: italic; text-align: right; color: #1f1f14; }
    </style>
</head>
<body>

    {{-- ============ FOOTER (lalabas sa bawat page) ============ --}}
    <div class="footer">
        <hr class="footer-line">
        <table class="full">
            <tr>
                <td>RAN Serenity &bull; New Cabalan, Olangapo City &bull; +63 9691898835 &bull; ranserenity77@gmail.com</td>
                <td class="tag">Your trust, our commitment.</td>
            </tr>
        </table>
    </div>

    <div class="wrap">

        {{-- ============ HEADER ============ --}}
        <table class="full">
            <tr>
                <td style="vertical-align: top;">
                    <div class="brand">RAN <span>Serenity</span></div>
                    <div class="contact">
                        New Cabalan, Olangapo City<br>
                        ranserenity77@gmail.com &middot; +63 9691898835
                    </div>
                </td>
                <td style="vertical-align: top;">
                    <div class="doc-title">Monthly Report</div>
                    <div class="doc-sub">{{ $periodLabel }} &middot; Generated {{ $generatedAt->format('M d, Y g:i A') }}</div>
                </td>
            </tr>
        </table>
        <hr class="rule">

        @php
            // Principal = installments na nabayaran minus interest. Ang penalties ay hiwalay.
            $principal = max(0, (float) $summary['collected'] - (float) $summary['interest']);
            $totalCollected = (float) $summary['collected'] + (float) $summary['penalty'];

            // Optional: dagdag na stats galing sa controller ($overview). Kung wala, hindi ipapakita ang mga section na iyon.
            $ov = $overview ?? null;
            $n = fn ($group, $key) => number_format($ov[$group][$key] ?? 0);
            $has = fn ($group, $key) => $ov && isset($ov[$group]) && array_key_exists($key, $ov[$group]);
        @endphp

        {{-- ============ EXECUTIVE SUMMARY ============ --}}
        <div class="sec-wrap">
            <div class="sec">Executive Summary</div>
            <table class="cards">
                <tr>
                    <td class="card">
                        <div class="card-value">{{ number_format($summary['approved']) }}</div>
                        <div class="card-label">Applications Approved</div>
                    </td>
                    <td class="card">
                        <div class="card-value">₱{{ number_format($totalCollected, 2) }}</div>
                        <div class="card-label">Money Collected</div>
                    </td>
                    @if ($has('loans', 'ongoing') && $has('loans', 'completed'))
                        <td class="card">
                            <div class="card-value">{{ $n('loans', 'ongoing') }}</div>
                            <div class="card-label">Ongoing Loans</div>
                        </td>
                        <td class="card">
                            <div class="card-value">{{ $n('loans', 'completed') }}</div>
                            <div class="card-label">Completed Loans</div>
                        </td>
                    @else
                        <td class="card">
                            <div class="card-value">₱{{ number_format($summary['interest'], 2) }}</div>
                            <div class="card-label">Total Interest</div>
                        </td>
                        <td class="card">
                            <div class="card-value">₱{{ number_format($summary['penalty'], 2) }}</div>
                            <div class="card-label">Penalties Collected</div>
                        </td>
                    @endif
                </tr>
            </table>
        </div>

        {{-- ============ APPLICATION OVERVIEW + PAYMENT COLLECTION SUMMARY ============ --}}
        <div class="sec-wrap">
            <table class="two">
                <tr>
                    <td class="col">
                        @if ($has('applications', 'approved'))
                            <div class="sec">Application Overview</div>
                            <table class="data">
                                <thead>
                                    <tr><th>Status</th><th class="mid">Count</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td>Approved</td><td class="mid">{{ $n('applications', 'approved') }}</td></tr>
                                    @if ($has('applications', 'rejected'))<tr><td>Rejected</td><td class="mid">{{ $n('applications', 'rejected') }}</td></tr>@endif
                                    @if ($has('applications', 'pending'))<tr><td>Pending</td><td class="mid">{{ $n('applications', 'pending') }}</td></tr>@endif
                                    @if ($has('applications', 'on_hold'))<tr><td>On Hold</td><td class="mid">{{ $n('applications', 'on_hold') }}</td></tr>@endif
                                </tbody>
                            </table>
                        @else
                            <div class="sec">Loan Summary</div>
                            <table class="data">
                                <thead>
                                    <tr><th>Description</th><th class="r">Value</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td>Applications approved</td><td class="num">{{ number_format($summary['approved']) }}</td></tr>
                                    <tr><td>Total interest</td><td class="num">₱{{ number_format($summary['interest'], 2) }}</td></tr>
                                </tbody>
                            </table>
                        @endif
                    </td>
                    <td class="gap"></td>
                    <td class="col">
                        <div class="sec">Payment Collection Summary</div>
                        <table class="data">
                            <thead>
                                <tr><th>Description</th><th class="r">Amount</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Principal Collected</td><td class="num">₱{{ number_format($principal, 2) }}</td></tr>
                                <tr><td>Interest Collected</td><td class="num">₱{{ number_format($summary['interest'], 2) }}</td></tr>
                                <tr><td>Penalties Collected</td><td class="num">₱{{ number_format($summary['penalty'], 2) }}</td></tr>
                                <tr class="total"><td>Total Collected</td><td class="num">₱{{ number_format($totalCollected, 2) }}</td></tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- ============ LOAN STATUS OVERVIEW + PAYMENT MONITORING SUMMARY (kapag may $overview) ============ --}}
        @if ($has('loans', 'ongoing') || $has('payments', 'paid'))
            <div class="sec-wrap">
                <table class="two">
                    <tr>
                        <td class="col">
                            @if ($has('loans', 'ongoing'))
                                <div class="sec">Loan Status Overview</div>
                                <table class="data">
                                    <thead>
                                        <tr><th>Loan Status</th><th class="mid">Count</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>Ongoing Loans</td><td class="mid">{{ $n('loans', 'ongoing') }}</td></tr>
                                        @if ($has('loans', 'completed'))<tr><td>Completed Loans</td><td class="mid">{{ $n('loans', 'completed') }}</td></tr>@endif
                                        @if ($has('loans', 'fully_paid'))<tr><td>Fully Paid Loans</td><td class="mid">{{ $n('loans', 'fully_paid') }}</td></tr>@endif
                                    </tbody>
                                </table>
                            @endif
                        </td>
                        <td class="gap"></td>
                        <td class="col">
                            @if ($has('payments', 'paid'))
                                <div class="sec">Payment Monitoring Summary</div>
                                <table class="data">
                                    <thead>
                                        <tr><th>Payment Status</th><th class="mid">Count</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>Paid</td><td class="mid">{{ $n('payments', 'paid') }}</td></tr>
                                        @if ($has('payments', 'partial'))<tr><td>Partially Paid</td><td class="mid">{{ $n('payments', 'partial') }}</td></tr>@endif
                                        @if ($has('payments', 'unpaid'))<tr><td>Unpaid</td><td class="mid">{{ $n('payments', 'unpaid') }}</td></tr>@endif
                                        @if ($has('payments', 'overdue'))<tr><td>Overdue</td><td class="mid">{{ $n('payments', 'overdue') }}</td></tr>@endif
                                    </tbody>
                                </table>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- ============ MONTH BY MONTH (kapag "All months") ============ --}}
        @if ($month === 0)
            <div class="sec-wrap">
                <div class="sec">Month by Month — {{ $year }}</div>
                <table class="data">
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
                                <td class="num">{{ number_format($r['approved']) }}</td>
                                <td class="num">₱{{ number_format($r['collected'], 2) }}</td>
                                <td class="num">₱{{ number_format($r['interest'], 2) }}</td>
                                <td class="num">₱{{ number_format($r['penalty'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total">
                            <td>Total</td>
                            <td class="num">{{ number_format(collect($rows)->sum('approved')) }}</td>
                            <td class="num">₱{{ number_format(collect($rows)->sum('collected'), 2) }}</td>
                            <td class="num">₱{{ number_format(collect($rows)->sum('interest'), 2) }}</td>
                            <td class="num">₱{{ number_format(collect($rows)->sum('penalty'), 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ============ APPROVED APPLICATIONS ============ --}}
        <div class="sec-wrap">
            <div class="sec">Approved Applications</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Loan ID</th>
                        <th>Borrower</th>
                        <th class="r">Loan Amount</th>
                        <th class="mid">Term</th>
                        <th class="r">Interest Collected</th>
                        <th>Approved On</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($approvedList as $a)
                        <tr>
                            <td>{{ $a->loan_code }}</td>
                            <td>{{ $a->borrower_name }}</td>
                            <td class="num">₱{{ number_format($a->loan_amount, 2) }}</td>
                            <td class="mid">{{ $a->loan_term }} mo</td>
                            <td class="num">₱{{ number_format($a->interest_collected, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($a->approved_at)->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No approved applications for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ============ REPORT INFORMATION ============ --}}
        <div class="sec-wrap" style="page-break-inside: avoid;">
            <div class="info">
                <div class="info-title">Report Information</div>
                <table class="full">
                    <tr>
                        <td style="font-size: 9.5px; color: #4b4b3f;">Generated By: RAN Serenity Lending System</td>
                        <td style="font-size: 9.5px; color: #4b4b3f;">Reporting Period: {{ $periodLabel }}</td>
                        <td style="font-size: 9.5px; color: #4b4b3f;" class="r">Generated On: {{ $generatedAt->format('M d, Y g:i A') }}</td>
                    </tr>
                </table>
            </div>
        </div>

    </div>
</body>
</html>