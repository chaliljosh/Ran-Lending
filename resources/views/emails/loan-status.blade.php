@php
    $copy = [
        'approved' => [
            'label' => 'Approved',
            'badge' => '&#10003; Approved',
            'title' => 'Loan application approved',
            'lines' => [
                'Your loan application has been reviewed and approved by our admins.',
                'Our team will contact you about the next steps. You can also track your loan anytime in your borrower account.',
            ],
        ],
        'rejected' => [
            'label' => 'Not approved',
            'badge' => 'Not approved',
            'title' => 'Update on your loan application',
            'lines' => [
                'Thank you for applying with RAN Serenity. After careful review, we are unable to approve your loan application at this time.',
                'If you have questions about this decision, please contact us directly.',
            ],
        ],
        'on_hold' => [
            'label' => 'On hold',
            'badge' => 'On hold',
            'title' => 'Loan application on hold',
            'lines' => [
                'Your loan application is currently on hold pending additional review.',
                'You do not need to do anything right now. Our team may contact you for a follow-up, and we will let you know once there is an update.',
            ],
        ],
    ][$status] ?? null;

    // Parehong format ng ibang emails: LN- + unang 8 chars ng uuid, uppercase
    $loanId   = 'LN-' . strtoupper(substr($loan->uuid, 0, 8));
    $termInt  = (int) $loan->loan_term;
    $greetEnd = $status === 'approved' ? '!' : ',';

    // Inline only. Do NOT add a <style> block: an unclosed one makes Gmail hide the whole body.
    $f = 'font-family:Helvetica,Arial,sans-serif;';

    // Logo: public URL galing sa Supabase Storage (public bucket)
    $logoSrc = \Illuminate\Support\Facades\Storage::disk('supabase')->url('branding/ranlogo.png');

    // Mga kulay, kapareho ng payment received email. Isang lugar lang para madaling palitan.
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
    <title>{{ $copy['title'] ?? 'Loan application update' }}</title>
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

                    @if($copy)
                        {{-- Pamagat at mensahe --}}
                        <tr>
                            <td style="padding:36px 0 0;{{ $f }}font-size:14px;font-weight:400;letter-spacing:0.5px;color:{{ $purpleDark }};">
                                {{ $copy['title'] }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0 12px;{{ $f }}font-size:36px;line-height:1.15;font-weight:700;color:{{ $ink }};">
                                Hello, <span style="color:{{ $purple }};">{{ $borrowerName }}</span>{{ $greetEnd }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 16px;{{ $f }}font-size:16px;font-weight:400;line-height:1.6;color:{{ $muted }};">
                                @foreach($copy['lines'] as $text)
                                    <p style="margin:0 0 12px;">{{ $text }}</p>
                                @endforeach
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
                                                        <span style="display:inline-block;padding:5px 14px;background:{{ $lavender }};border-radius:999px;{{ $f }}font-size:13px;font-weight:400;color:{{ $purpleDark }};">{!! $copy['badge'] !!}</span>
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
                                                    <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Status</td>
                                                    <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $purpleDark }};">{{ $copy['label'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Loan amount</td>
                                                    <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">₱{{ number_format($loan->loan_amount, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Term</td>
                                                    <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">{{ $termInt }} {{ $termInt === 1 ? 'month' : 'months' }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Date applied</td>
                                                    <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">{{ \Carbon\Carbon::parse($loan->applied_at)->format('F j, Y') }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                </table>
                            </td>
                        </tr>
                    @endif

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
                            You are receiving this email because you applied for a loan with RAN Serenity.
                            This is an automated message, please do not reply.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>