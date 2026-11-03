@php
    $amount  = (float) ($loan->loan_amount ?? 0);
    $monthly = (float) ($loan->monthly_payment ?? 0);
    $term    = (int) ($loan->loan_term ?? 0);

    // Parehong format ng proof of release email (galing sa Mailable; may fallback)
    $loanId = $loanCode ?? ('LN-' . strtoupper(substr($loan->uuid, 0, 8)));

    $appliedOn = \Carbon\Carbon::parse($loan->applied_at)
        ->timezone(config('app.timezone'))
        ->format('F j, Y');

    // Inline only. Do NOT add a <style> block: an unclosed one makes Gmail hide the whole body.
    $f = 'font-family:Helvetica,Arial,sans-serif;';

    // Logo: public URL galing sa Supabase Storage (public bucket)
    $logoSrc = \Illuminate\Support\Facades\Storage::disk('supabase')->url('branding/ranlogo.png');

    // Mga kulay, kapareho ng proof of release email.
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
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>We received your loan application</title>
</head>
<body bgcolor="{{ $cream }}" style="margin:0;padding:0;background:{{ $cream }};{{ $f }}color:{{ $ink }};">

    {{-- Preview text na lumalabas sa tabi ng subject sa inbox --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#ffffff;font-size:1px;line-height:1px;">
        Thank you for applying. Your application for &#8369;{{ number_format($amount, 2) }} is now under review.
    </div>

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
                            Loan application received
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0 12px;{{ $f }}font-size:36px;line-height:1.15;font-weight:700;color:{{ $ink }};">
                            Thank you, <span style="color:{{ $purple }};">{{ $displayName }}</span>!
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 28px;{{ $f }}font-size:16px;font-weight:400;line-height:1.6;color:{{ $muted }};">
                            Thank you for applying for a loan with us. We have received your application for
                            <strong style="color:{{ $ink }};">&#8369;{{ number_format($amount, 2) }}</strong>
                            and it is now under review.
                        </td>
                    </tr>

                    {{-- Application card --}}
                    <tr>
                        <td>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="background:#ffffff;border:1px solid {{ $line }};border-radius:18px;">

                                {{-- Application number + badge --}}
                                <tr>
                                    <td style="padding:24px 24px 8px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="{{ $f }}font-size:13px;font-weight:400;color:{{ $muted }};">Application number</td>
                                                <td align="right" rowspan="2" valign="top">
                                                    <span style="display:inline-block;padding:5px 14px;background:{{ $lavender }};border-radius:999px;{{ $f }}font-size:13px;font-weight:400;color:{{ $purpleDark }};">Under review</span>
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
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Loan amount</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">&#8369;{{ number_format($amount, 2) }}</td>
                                            </tr>
                                            @if($term > 0)
                                                <tr>
                                                    <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Loan term</td>
                                                    <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">{{ $term }} {{ $term === 1 ? 'month' : 'months' }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Monthly payment</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">&#8369;{{ number_format($monthly, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Purpose</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">{{ $purposeLabel }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Date applied</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">{{ $appliedOn }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                {{-- Susunod na hakbang sa loob ng lavender box --}}
                                <tr>
                                    <td style="padding:0 16px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $lavender }}" style="background:{{ $lavender }};border-radius:14px;">
                                            <tr>
                                                <td style="padding:16px 16px 4px;{{ $f }}font-size:15px;font-weight:700;color:{{ $ink }};">What happens next?</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:0 16px 16px;{{ $f }}font-size:15px;font-weight:400;line-height:1.6;color:{{ $muted }};">
                                                    Our team will review your application. You will receive another email once a decision has been made.
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
                            If you did not apply for this loan, please contact us right away.
                            This is an automated message, please do not reply.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>