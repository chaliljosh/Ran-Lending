@php
    // Halaga ng installment na ito: ang na-record na bayad, o ang amount due kung wala pa
    $amount = (float) ($schedule->amount_paid ?? 0) > 0
        ? (float) $schedule->amount_paid
        : (float) $schedule->amount_due;

    $loanCode = 'LN-' . str_pad($loan->application_id, 6, '0', STR_PAD_LEFT);
    $dueDate  = \Carbon\Carbon::parse($schedule->due_date)->format('F j, Y');
    $paidDate = !empty($schedule->paid_at)
        ? \Carbon\Carbon::parse($schedule->paid_at)->format('F j, Y')
        : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Proof of payment</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;-webkit-text-size-adjust:100%;">

    {{-- Preview text shown next to the subject in the inbox list --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#ffffff;font-size:1px;line-height:1px;">
        Your payment of &#8369;{{ number_format($amount, 2) }} for installment #{{ (int) $schedule->installment_no }} was recorded. The proof is attached.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;">
        <tr>
            <td align="center" style="padding:40px 20px 48px;">

                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:560px;">

                    {{-- Brand --}}
                    <tr>
                        <td align="center" style="padding:0 0 32px;font-size:30px;line-height:36px;font-weight:700;letter-spacing:-0.5px;color:#0f172a;">
                            Ran Serenity
                        </td>
                    </tr>

                    {{-- Greeting --}}
                    <tr>
                        <td style="padding:0 0 12px;font-size:24px;line-height:32px;font-weight:700;color:#0f172a;">
                            Hello, {{ $displayName }}!
                        </td>
                    </tr>

                    {{-- Message --}}
                    <tr>
                        <td style="padding:0 0 24px;font-size:16px;line-height:26px;color:#1f2937;">
                            Ran Serenity has recorded your payment of
                            <strong style="color:#0f172a;">&#8369;{{ number_format($amount, 2) }}</strong>
                            for installment <strong style="color:#0f172a;">#{{ (int) $schedule->installment_no }}</strong>.
                            The proof is shown below and is also attached to this email.
                        </td>
                    </tr>

                    {{-- Payment card --}}
                    <tr>
                        <td style="padding:0 0 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef3ff;border-radius:16px;">
                                <tr>
                                    <td align="center" style="padding:28px 24px 8px;font-size:12px;line-height:16px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#64748b;">
                                        Proof of payment
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:0 24px 20px;font-size:32px;line-height:40px;font-weight:700;letter-spacing:4px;color:#4a7cf7;">
                                        {{ $loanCode }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 24px 12px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid #d9e3fb;font-size:15px;line-height:22px;color:#64748b;">Installment</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid #d9e3fb;font-size:15px;line-height:22px;font-weight:700;color:#0f172a;">#{{ (int) $schedule->installment_no }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid #d9e3fb;font-size:15px;line-height:22px;color:#64748b;">Amount paid</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid #d9e3fb;font-size:15px;line-height:22px;font-weight:700;color:#0f172a;">&#8369;{{ number_format($amount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid #d9e3fb;font-size:15px;line-height:22px;color:#64748b;">Due date</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid #d9e3fb;font-size:15px;line-height:22px;font-weight:700;color:#0f172a;">{{ $dueDate }}</td>
                                            </tr>
                                            @if($paidDate)
                                                <tr>
                                                    <td style="padding:14px 0;border-top:1px solid #d9e3fb;font-size:15px;line-height:22px;color:#64748b;">Date paid</td>
                                                    <td align="right" style="padding:14px 0;border-top:1px solid #d9e3fb;font-size:15px;line-height:22px;font-weight:700;color:#0f172a;">{{ $paidDate }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid #d9e3fb;font-size:15px;line-height:22px;color:#64748b;">Proof</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid #d9e3fb;font-size:15px;line-height:22px;font-weight:700;color:#0f172a;">Attached below</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Proof image --}}
                    <tr>
                        <td style="padding:0 0 28px;">
                            <img src="{{ $message->embed($proofPath) }}" alt="Proof of payment" width="560"
                                style="display:block;width:100%;max-width:560px;height:auto;border:1px solid #e5e7eb;border-radius:12px;">
                        </td>
                    </tr>

                    {{-- Footer note --}}
                    <tr>
                        <td style="padding:20px 0 0;border-top:1px solid #e5e7eb;font-size:13px;line-height:20px;color:#6b7280;">
                            If you did not make this payment, please contact us right away.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>