@php
    // Parehong format ng admin page: LN- + unang 8 chars ng uuid, uppercase (hal. LN-34782CDF)
    $loanId = 'LN-' . strtoupper(substr($loan->uuid, 0, 8));

    // Inline only. Do NOT add a <style> block: an unclosed one makes Gmail hide the whole body.
    $f = 'font-family:Helvetica,Arial,sans-serif;';

    // Logo: public URL galing sa Supabase Storage (public bucket). Gumagana sa local at production,
    // at walang attachment chip sa Gmail. I-upload muna ang file sa 'branding/ranlogo.png'.
    $logoSrc = \Illuminate\Support\Facades\Storage::disk('supabase')->url('branding/ranlogo.png');

    // Mga kulay, kapareho ng landing page. Isang lugar lang para madaling palitan.
    $cream      = '#ffffff';   // background ng buong email (puti)
    $ink        = '#1c1b22';   // heading / dark text
    $muted      = '#6b6a75';   // mahinang text
    $line       = '#e6e4ee';   // borders / dividers
    $purple     = '#9b7bd6';   // "Simple Lending." purple (malalaking text lang)
    $purpleDark = '#6b4bb8';   // purple para sa maliit na text (mas madaling basahin)
    $lavender   = '#efebf7';   // malabong lavender (badge at payment box)
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isFullyPaid ? 'Your loan is fully paid!' : 'Payment received' }}</title>
</head>
<body bgcolor="{{ $cream }}" style="margin:0;padding:0;background:{{ $cream }};{{ $f }}color:{{ $ink }};">

    {{-- Full width na cream background --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $cream }}">
        <tr>
            <td align="center" style="padding:0 20px 32px;">

                {{-- Nakasentro na column para hindi sobrang luwang sa malaking screen --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

                    {{-- Header: logo + pangalan, parang navbar ng landing page --}}
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
                            {{ $isFullyPaid ? 'Loan fully paid' : 'Payment received' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0 12px;{{ $f }}font-size:36px;line-height:1.15;font-weight:700;color:{{ $ink }};">
                            Hello, <span style="color:{{ $purple }};">{{ $borrowerName }}</span>!
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 28px;{{ $f }}font-size:16px;font-weight:400;line-height:1.6;color:{{ $muted }};">
                            @if($isFullyPaid)
                                Congratulations! We've recorded your final installment, and your loan is now fully paid.
                                Thank you for staying on top of your payments. You're welcome to apply again anytime.
                            @else
                                We've recorded your payment for installment #{{ $installmentNo }}.
                                Thank you for keeping up with your loan!
                            @endif
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
                                                    <span style="display:inline-block;padding:5px 14px;background:{{ $lavender }};border-radius:999px;{{ $f }}font-size:13px;font-weight:400;color:{{ $purpleDark }};">&#10003; {{ $isFullyPaid ? 'Completed' : 'Paid' }}</span>
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
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Amount paid</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">₱{{ number_format($amountPaid, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Status</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $purpleDark }};">{{ $isFullyPaid ? 'Loan completed' : 'Active' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                {{-- Susunod na bayad: kapareho ng "Est. Monthly Payment" box ng landing page --}}
                                @if(!$isFullyPaid && $nextDueDate)
                                    <tr>
                                        <td style="padding:8px 16px 16px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $lavender }}" style="background:{{ $lavender }};border-radius:14px;">
                                                <tr>
                                                    <td style="padding:18px 20px;">
                                                        <div style="{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">Next payment</div>
                                                        <div style="padding-top:2px;{{ $f }}font-size:13px;font-weight:400;color:{{ $muted }};">Due on {{ \Carbon\Carbon::parse($nextDueDate)->format('F j, Y') }}</div>
                                                    </td>
                                                    <td align="right" style="padding:18px 20px;{{ $f }}font-size:24px;font-weight:700;color:{{ $ink }};">₱{{ number_format($nextDueAmount, 2) }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @else
                                    <tr><td style="padding:0 0 12px;font-size:1px;line-height:1px;">&nbsp;</td></tr>
                                @endif

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
                            This is an automated message, please do not reply.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>