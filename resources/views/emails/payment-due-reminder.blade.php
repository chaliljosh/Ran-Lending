@php
    // Petsa at "kailan" na text
    $dueDateText = \Carbon\Carbon::parse($dueDate)->format('F j, Y');

    if ($daysLeft <= 0) {
        $whenText = 'today';
    } elseif ($daysLeft === 1) {
        $whenText = 'tomorrow';
    } else {
        $whenText = 'in ' . $daysLeft . ' days';
    }

    // Inline only. Do NOT add a <style> block: an unclosed one makes Gmail hide the whole body.
    $f = 'font-family:Helvetica,Arial,sans-serif;';

    // Logo: public URL galing sa Supabase Storage (public bucket)
    $logoSrc = \Illuminate\Support\Facades\Storage::disk('supabase')->url('branding/ranlogo.png');

    // Mga kulay, kapareho ng ibang emails. Isang lugar lang para madaling palitan.
    $cream      = '#ffffff';
    $ink        = '#1c1b22';
    $muted      = '#6b6a75';
    $line       = '#e6e4ee';
    $purple     = '#9b7bd6';
    $purpleDark = '#6b4bb8';
    $lavender   = '#efebf7';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment reminder</title>
</head>
<body bgcolor="{{ $cream }}" style="margin:0;padding:0;background:{{ $cream }};{{ $f }}color:{{ $ink }};">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $cream }}">
        <tr>
            <td align="center" style="padding:0 20px 32px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

                    {{-- Header: logo + pangalan --}}
                    <tr>
                        <td style="border-bottom:1px solid {{ $line }};">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="52" style="padding:16px 12px 16px 0;">
                                        <img src="{{ $logoSrc }}" alt="" width="52" style="display:block;width:52px;height:auto;border:0;">
                                    </td>
                                    <td style="padding:16px 0;{{ $f }}font-size:22px;font-weight:700;color:{{ $ink }};">RAN Serenity</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Pamagat at mensahe --}}
                    <tr>
                        <td style="padding:36px 0 0;{{ $f }}font-size:14px;font-weight:400;letter-spacing:0.5px;color:{{ $purpleDark }};">
                            Payment reminder
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0 12px;{{ $f }}font-size:36px;line-height:1.15;font-weight:700;color:{{ $ink }};">
                            Hello, <span style="color:{{ $purple }};">{{ $borrowerName }}</span>!
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 28px;{{ $f }}font-size:16px;font-weight:400;line-height:1.6;color:{{ $muted }};">
                            This is a friendly reminder that your payment for installment #{{ $installmentNo }}
                            is due <strong style="color:{{ $ink }};">{{ $whenText }}</strong>.
                            Please pay on or before the due date to avoid late penalties.
                        </td>
                    </tr>

                    {{-- Loan card --}}
                    <tr>
                        <td>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="background:#ffffff;border:1px solid {{ $line }};border-radius:18px;">

                                {{-- Loan number + badge --}}
                                <tr>
                                    <td style="padding:24px 24px 8px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="{{ $f }}font-size:13px;font-weight:400;color:{{ $muted }};">Loan number</td>
                                                <td align="right" rowspan="2" valign="top">
                                                    <span style="display:inline-block;padding:5px 14px;background:{{ $lavender }};border-radius:999px;{{ $f }}font-size:13px;font-weight:400;color:{{ $purpleDark }};">Due soon</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top:4px;{{ $f }}font-size:26px;line-height:1.2;font-weight:700;letter-spacing:1px;color:{{ $ink }};">{{ $loanId }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                {{-- Mga detalye --}}
                                <tr>
                                    <td style="padding:8px 24px 0;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Installment</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">#{{ $installmentNo }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Due date</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">{{ $dueDateText }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                {{-- Halaga na babayaran, kapareho ng "Next payment" box ng payment received --}}
                                <tr>
                                    <td style="padding:8px 16px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $lavender }}" style="background:{{ $lavender }};border-radius:14px;">
                                            <tr>
                                                <td style="padding:18px 20px;">
                                                    <div style="{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">Amount due</div>
                                                    <div style="padding-top:2px;{{ $f }}font-size:13px;font-weight:400;color:{{ $muted }};">Due {{ $whenText }}</div>
                                                </td>
                                                <td align="right" style="padding:18px 20px;{{ $f }}font-size:24px;font-weight:700;color:{{ $ink }};">&#8369;{{ number_format($amount, 2) }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:28px 0 0;{{ $f }}font-size:15px;font-weight:400;line-height:1.5;color:{{ $purpleDark }};">
                            Thank you for trusting RAN Serenity!
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0 0;{{ $f }}font-size:13px;font-weight:400;line-height:1.5;color:{{ $muted }};">
                            Together towards your goals.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 0 0;{{ $f }}font-size:12px;font-weight:400;line-height:1.5;color:{{ $muted }};">
                            You are receiving this email because you have an active loan with RAN Serenity.
                            If you have already paid, please ignore this message.
                            This is an automated message, please do not reply.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>