<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Statement of Account {{ $loanId }}</title>
    <style>
        @page { margin: 24px 32px 40px 32px; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5px;
            color: #1f2013;
            line-height: 1.35;
        }

        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }

        .muted { color: #6b6b60; }
        .right { text-align: right; }
        .bold  { font-weight: bold; }
        .green { color: #16a34a; }

        .brand     { font-size: 17px; font-weight: bold; color: #1f2013; }
        .brand span{ color: #9b7fd9; }
        .doc-title { font-size: 15px; font-weight: bold; letter-spacing: 1px; color: #9b7fd9; }
        .doc-sub   { font-size: 8px; color: #6b6b60; margin-top: 1px; }
        .header-rule { border-bottom: 2px solid #9b7fd9; margin: 6px 0 2px 0; }

        .section { margin-top: 10px; page-break-inside: avoid; }
        .section-title {
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 0.8px;
            color: #1f2013;
            text-transform: uppercase;
            border-bottom: 1px solid #e6e4dc;
            padding-bottom: 2px;
            margin-bottom: 4px;
        }
        .section-title .no { color: #9b7fd9; }

        .kv td { padding: 1.5px 0; }
        .kv td.k { color: #6b6b60; width: 32%; }
        .kv td.v { width: 18%; font-weight: bold; padding-right: 8px; }
        .kv2 td.k { width: 22%; }
        .kv2 td.v { width: 28%; }

        .ledger th {
            background: #efeaf8;
            border-top: 1px solid #d8cdf0;
            border-bottom: 1px solid #d8cdf0;
            padding: 4px 5px;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
        }
        .ledger td {
            padding: 3.5px 5px;
            border-bottom: 1px solid #efede6;
            font-size: 8.5px;
        }
        .ledger tr { page-break-inside: avoid; }
        .ledger thead { display: table-header-group; }
        .ledger tr.opening td { background: #f9f9f5; font-weight: bold; }
        .ledger th.r, .ledger td.r { text-align: right; }

        .totals td { padding: 2px 0; }
        .totals .final td {
            border-top: 2px solid #1f2013;
            padding-top: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .paid-box {
            border: 2px solid #16a34a;
            color: #16a34a;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 2px;
            padding: 5px 0;
            margin-top: 6px;
        }
        .due-box {
            border: 2px solid #dc2626;
            color: #dc2626;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 5px 0;
            margin-top: 6px;
        }
        .remaining-note { text-align: center; font-size: 8px; color: #6b6b60; margin-top: 3px; }

        .remarks {
            border: 1px solid #e6e4dc;
            background: #f9f9f5;
            padding: 6px 8px;
            margin-right: 14px;
        }

        .verify {
            border: 1px dashed #c4b5e8;
            background: #faf8fe;
            padding: 5px 8px;
        }

        .footer {
            position: fixed;
            bottom: -26px;
            left: 0;
            right: 0;
            border-top: 1px solid #e6e4dc;
            padding-top: 4px;
            font-size: 7px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- ===== Header ===== --}}
    <table>
        <tr>
            <td style="width: 55%;">
                <table>
                    <tr>
                        @if(!empty($logoPath))
                            <td style="width: 38px; vertical-align: middle;">
                                <img src="{{ $logoPath }}" style="height: 34px;">
                            </td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="brand">RAN <span>Serenity</span></div>
                            <div class="muted">
                                {{ $company['address'] }}<br>
                                {{ $company['email'] }} &middot; {{ $company['phone'] }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="right" style="width: 45%;">
                <div class="doc-title">STATEMENT OF ACCOUNT</div>
                <div class="doc-sub">Official Loan Account Statement</div>
            </td>
        </tr>
    </table>

    <div class="header-rule"></div>

    {{-- ===== 1. Borrower Information ===== --}}
    <div class="section">
        <div class="section-title"><span class="no">1.</span> Borrower Information</div>
        <table class="kv kv2">
            <tr>
                <td class="k">Name</td>
                <td class="v">{{ $borrowerName }}</td>
                <td class="k">Borrower ID</td>
                <td class="v">{{ $borrowerId }}</td>
            </tr>
            <tr>
                <td class="k">Email</td>
                <td class="v">{{ $borrowerEmail !== '' ? $borrowerEmail : '—' }}</td>
                <td class="k">Contact Number</td>
                <td class="v">{{ $borrowerPhone !== '' ? $borrowerPhone : '—' }}</td>
            </tr>
            <tr>
                <td class="k">Address</td>
                <td class="v" colspan="3">{{ $borrowerAddress !== '' ? $borrowerAddress : '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- ===== 2. Loan Summary ===== --}}
    <div class="section">
        <div class="section-title"><span class="no">2.</span> Loan Summary</div>
        <table class="kv kv2">
            <tr>
                <td class="k">Loan ID</td>
                <td class="v">{{ $loanId }}</td>
                <td class="k">Loan Term</td>
                <td class="v">{{ (int) $app->loan_term }} months</td>
            </tr>
            <tr>
                <td class="k">Loan Purpose</td>
                <td class="v">{{ $purposeLabel }}</td>
                <td class="k">Monthly Installment</td>
                <td class="v">₱{{ number_format($app->monthly_payment, 2) }}</td>
            </tr>
            <tr>
                <td class="k">Principal</td>
                <td class="v">₱{{ number_format($app->loan_amount, 2) }}</td>
                <td class="k">Release Date</td>
                <td class="v">{{ $releasedAt->format('M j, Y') }}</td>
            </tr>
            <tr>
                <td class="k">Interest ({{ rtrim(rtrim(number_format((float) $app->interest_rate, 2), '0'), '.') }}% / month)</td>
                <td class="v">₱{{ number_format($app->total_interest, 2) }}</td>
                <td class="k">Loan Status</td>
                <td class="v {{ $isSettled ? 'green' : '' }}">{{ $loanStatusLabel }}</td>
            </tr>
        </table>
    </div>

    {{-- ===== 3. Account Activity ===== --}}
    <div class="section" style="page-break-inside: auto;">
        <div class="section-title"><span class="no">3.</span> Account Activity</div>
        <table class="ledger">
            <thead>
                <tr>
                    <th style="width: 13%;">Date</th>
                    <th style="width: 12%;">Reference</th>
                    <th>Description</th>
                    <th class="r" style="width: 14%;">Charges</th>
                    <th class="r" style="width: 14%;">Payment</th>
                    <th class="r" style="width: 14%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="opening">
                    <td>{{ $openingDate->format('M j, Y') }}</td>
                    <td>{{ $loanId }}</td>
                    <td>Opening balance (principal + interest)</td>
                    <td class="r">₱{{ number_format($totalPayable, 2) }}</td>
                    <td class="r">—</td>
                    <td class="r">₱{{ number_format($totalPayable, 2) }}</td>
                </tr>

                @foreach($ledger as $row)
                    <tr>
                        <td>{{ $row['date']->format('M j, Y') }}</td>
                        <td>{{ $row['ref'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td class="r">{{ $row['charge'] > 0 ? '₱' . number_format($row['charge'], 2) : '—' }}</td>
                        <td class="r">{{ $row['payment'] > 0 ? '₱' . number_format($row['payment'], 2) : '—' }}</td>
                        <td class="r">₱{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ===== 4. Remarks (kaliwa) + Account Summary (kanan) ===== --}}
    <div class="section">
        <table>
            <tr>
                <td style="width: 52%;">
                    <div class="section-title"><span class="no">4.</span> Remarks</div>
                    <div class="remarks">
                        @if($isSettled)
                            This confirms that loan <span class="bold">{{ $loanId }}</span> issued to
                            <span class="bold">{{ $borrowerName }}</span> has been fully settled on
                            <span class="bold">{{ $settledAt->format('F j, Y') }}</span>. The account has a
                            balance of ₱0.00 and no further payment is required. Thank you for your trust.
                        @else
                            Thank you for your trust. Please contact us if you have questions about the balance shown on this statement.
                        @endif
                    </div>
                </td>
                <td style="width: 48%;">
                    <div class="section-title"><span class="no">5.</span> Account Summary</div>
                    <table class="totals">
                        <tr>
                            <td class="muted">Total Payable</td>
                            <td class="right">₱{{ number_format($totalPayable, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Penalties</td>
                            <td class="right">₱{{ number_format($totalPenalties, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Total Charges</td>
                            <td class="right">₱{{ number_format($totalCharges, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Total Payments Received</td>
                            <td class="right">- ₱{{ number_format($totalPayments, 2) }}</td>
                        </tr>
                        <tr class="final">
                            <td>Balance Due</td>
                            <td class="right {{ $isSettled ? 'green' : '' }}">₱{{ number_format($balance, 2) }}</td>
                        </tr>
                    </table>

                    @if($isSettled)
                        <div class="paid-box">PAID IN FULL</div>
                        <div class="remaining-note">₱0.00 remaining balance</div>
                    @else
                        <div class="due-box">BALANCE REMAINING</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- ===== 6. Document Verification ===== --}}
    <div class="section">
        <div class="section-title"><span class="no">6.</span> Document Verification</div>
        <div class="verify">
            <table class="kv kv2">
                <tr>
                    <td class="k">Generated On</td>
                    <td class="v">{{ $statementDate->format('F j, Y g:i A') }}</td>
                    <td class="k">Statement Reference</td>
                    <td class="v">{{ $statementRef }}</td>
                </tr>
                <tr>
                    <td class="k">Issued By</td>
                    <td class="v">{{ $company['name'] }} Loan Management System</td>
                    <td class="k">Document Type</td>
                    <td class="v">System-generated</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        {{ $company['name'] }} &middot; {{ $company['address'] }} &middot; {{ $company['email'] }} &middot;
        Statement of Account {{ $loanId }} &middot; Ref {{ $statementRef }} &middot;
        This is a system-generated document.
    </div>

</body>
</html>