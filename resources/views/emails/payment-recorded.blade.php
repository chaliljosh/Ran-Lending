@php
    $loanId  = 'LN-' . str_pad($loan->application_id, 6, '0', STR_PAD_LEFT);

    // Inline only. Do NOT add a <style> block: an unclosed one makes Gmail hide the whole body.
    $f = 'font-family:Helvetica,Arial,sans-serif;';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isFullyPaid ? 'Your loan is fully paid!' : 'Payment received' }}</title>
</head>
<body bgcolor="#ffffff" style="margin:0;padding:0;background:#ffffff;{{ $f }}color:#1f1f1f;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff">
        <tr>
            <td align="center" style="padding:40px 24px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

                    {{-- Brand --}}
                    <tr>
                        <td align="center" style="padding:0 0 36px;{{ $f }}font-size:28px;line-height:1.2;font-weight:700;color:#1f1f1f;">
                            Ran Serenity
                        </td>
                    </tr>

                    {{-- Greeting --}}
                    <tr>
                        <td style="padding:0 0 16px;{{ $f }}font-size:22px;line-height:1.3;font-weight:700;color:#1f1f1f;">
                            Hello, {{ $borrowerName }}!
                        </td>
                    </tr>

                    {{-- Message --}}
                    <tr>
                        <td style="{{ $f }}font-size:15px;line-height:1.6;color:#1f1f1f;">
                            @if($isFullyPaid)
                                <p style="margin:0 0 12px;">Congratulations! We've recorded your final installment, and your loan is now fully paid.</p>
                                <p style="margin:0 0 12px;">Thank you for staying on top of your payments. You're welcome to apply again anytime.</p>
                            @else
                                <p style="margin:0 0 12px;">We've recorded your payment for installment #{{ $installmentNo }}. Thank you!</p>
                                @if($nextDueDate)
                                    <p style="margin:0 0 12px;">Your next payment of ₱{{ number_format($nextDueAmount, 2) }} is due on {{ \Carbon\Carbon::parse($nextDueDate)->format('F j, Y') }}.</p>
                                @endif
                            @endif
                        </td>
                    </tr>

                    {{-- Loan reference + details --}}
                    <tr>
                        <td style="padding:12px 0 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#eff4ff" style="background:#eff4ff;border-radius:12px;">
                                <tr>
                                    <td align="center" style="padding:32px 24px 24px;{{ $f }}font-size:30px;line-height:1.2;font-weight:700;letter-spacing:4px;color:#4a7cf5;">
                                        {{ $loanId }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 24px 12px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;color:#5f6b7a;">Installment</td>
                                                <td align="right" style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;font-weight:700;color:#1f1f1f;">#{{ $installmentNo }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;color:#5f6b7a;">Amount paid</td>
                                                <td align="right" style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;font-weight:700;color:#1f1f1f;">₱{{ number_format($amountPaid, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;color:#5f6b7a;">Status</td>
                                                <td align="right" style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;font-weight:700;color:#1f1f1f;">{{ $isFullyPaid ? 'Loan completed' : 'Active' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:40px 0 0;{{ $f }}font-size:12px;line-height:1.5;color:#8a919c;">
                            You are receiving this email because you have an active loan with Ran Serenity.
                            This is an automated message, please do not reply.
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>