@php
    // Halaga ng installment na ito: ang na-record na bayad, o ang amount due kung wala pa
    $amount = (float) ($schedule->amount_paid ?? 0) > 0
        ? (float) $schedule->amount_paid
        : (float) $schedule->amount_due;

    // Parehong format ng payment received email: LN- + unang 8 chars ng uuid, uppercase
    $loanId   = 'LN-' . strtoupper(substr($loan->uuid, 0, 8));
    $dueDate  = \Carbon\Carbon::parse($schedule->due_date)->format('F j, Y');
    $paidDate = !empty($schedule->paid_at)
        ? \Carbon\Carbon::parse($schedule->paid_at)->format('F j, Y')
        : null;

    // Inline only. Do NOT add a <style> block: an unclosed one makes Gmail hide the whole body.
    $f = 'font-family:Helvetica,Arial,sans-serif;';

    // Logo: public URL galing sa Supabase Storage (public bucket)
    $logoSrc = \Illuminate\Support\Facades\Storage::disk('supabase')->url('branding/ranlogo.png');

    // Mga kulay, kapareho ng payment received email. Isang lugar lang para madaling palitan.
    $cream      = '#ffffff';   // background ng buong email (puti)
    $ink        = '#1c1b22';   // heading / dark text
    $muted      = '#6b6a75';   // mahinang text
    $line       = '#e6e4ee';   // borders / dividers
    $purple     = '#9b7bd6';   // purple para sa malalaking text lang
    $purpleDark = '#6b4bb8';   // purple para sa maliit na text (mas madaling basahin)
    $lavender   = '#efebf7';   // malabong lavender (badge at boxes)
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Proof of payment</title>
</head>
<body bgcolor="{{ $cream }}" style="margin:0;padding:0;background:{{ $cream }};{{ $f }}color:{{ $ink }};">

    {{-- Preview text na lumalabas sa tabi ng subject sa inbox --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#ffffff;font-size:1px;line-height:1px;">
        Your payment of &#8369;{{ number_format($amount, 2) }} for installment #{{ (int) $schedule->installment_no }} was recorded. The proof is attached.
    </div>

    {{-- Full width na puting background --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $cream }}">
        <tr>
            <td align="center" style="padding:0 20px 32px;">

                {{-- Nakasentro na column para hindi sobrang luwang sa malaking screen --}}
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
                            Proof of payment
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0 12px;{{ $f }}font-size:36px;line-height:1.15;font-weight:700;color:{{ $ink }};">
                            Hello, <span style="color:{{ $purple }};">{{ $displayName }}</span>!
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 28px;{{ $f }}font-size:16px;font-weight:400;line-height:1.6;color:{{ $muted }};">
                            We've recorded your payment of
                            <strong style="color:{{ $ink }};">&#8369;{{ number_format($amount, 2) }}</strong>
                            for installment <strong style="color:{{ $ink }};">#{{ (int) $schedule->installment_no }}</strong>.
                            Your proof of payment is shown below and is also attached to this email.
                        </td>
                    </tr>

                    {{-- Payment card --}}
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
                                                    <span style="display:inline-block;padding:5px 14px;background:{{ $lavender }};border-radius:999px;{{ $f }}font-size:13px;font-weight:400;color:{{ $purpleDark }};">&#10003; Paid</span>
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
                                    <td style="padding:8px 24px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Installment</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">#{{ (int) $schedule->installment_no }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Amount paid</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">&#8369;{{ number_format($amount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Due date</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">{{ $dueDate }}</td>
                                            </tr>
                                            @if($paidDate)
                                                <tr>
                                                    <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Date paid</td>
                                                    <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">{{ $paidDate }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Proof</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $purpleDark }};">Attached below</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                {{-- Proof image sa loob ng lavender box, kapareho ng "Next payment" box --}}
                                <tr>
                                    <td style="padding:0 16px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $lavender }}" style="background:{{ $lavender }};border-radius:14px;">
                                            <tr>
                                                <td style="padding:16px 16px 8px;{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">Proof of payment</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:0 16px 16px;">
                                                    <img src="{{ $message->embed($proofPath) }}" alt="Proof of payment" width="100%"
                                                        style="display:block;width:100%;max-width:100%;height:auto;border:1px solid {{ $line }};border-radius:10px;">
                                                </td>
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
                            If you did not make this payment, please contact us right away.
                            This is an automated message, please do not reply.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>