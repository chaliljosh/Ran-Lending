@php
    $f = 'font-family:Helvetica,Arial,sans-serif;';

    // Galing sa Mailable; default [] para hindi mag-error kung wala.
    $remarks = $remarks ?? [];
    $hasRemarks = !empty($remarks) && in_array($status, ['on_hold', 'rejected'], true);

    $copy = [
        'approved' => [
            'heading' => 'You\'re verified!',
            'body'    => 'Good news! Your account has been verified. You can now apply for a loan.',
            'accent'  => '#22c55e',
            'bg'      => '#eefdf3',
        ],
        'rejected' => [
            'heading' => 'Verification update',
            'body'    => 'We were unable to verify your account with the information provided. Please review your submitted details and try again, or contact support for help.',
            'body_with_remarks' => 'We were unable to verify your account with the information provided. Please review the notes below, update your details, and resubmit.',
            'accent'  => '#ef4444',
            'bg'      => '#fef2f2',
            'label'   => '#b91c1c',
            'action'  => 'Update Personal Details',
        ],
        'on_hold' => [
            'heading' => 'Verification needs attention',
            'body'    => 'Your account verification is currently on hold pending further review. We\'ll notify you once a final decision has been made.',
            'body_with_remarks' => 'Some details in your submission need an update before your verification can continue. Please review the notes below, fix them, and resubmit.',
            'accent'  => '#f59e0b',
            'bg'      => '#fffaeb',
            'label'   => '#b45309',
            'action'  => 'Update my details',
        ],
    ];

    $c = $copy[$status] ?? $copy['on_hold'];
    $bodyText = ($hasRemarks && isset($c['body_with_remarks'])) ? $c['body_with_remarks'] : $c['body'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $c['heading'] }}</title>
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
                            <p style="margin:0 0 12px;">{{ $bodyText }}</p>
                        </td>
                    </tr>

                    {{-- Status badge --}}
                    <tr>
                        <td style="padding:12px 0 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $c['bg'] }}" style="background:{{ $c['bg'] }};border-radius:12px;">
                                <tr>
                                    <td align="center" style="padding:28px 24px;{{ $f }}font-size:22px;line-height:1.2;font-weight:700;color:{{ $c['accent'] }};">
                                        {{ $c['heading'] }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Requested changes (on_hold / rejected lang) --}}
                    @if ($hasRemarks)
                    <tr>
                        <td style="padding:28px 0 0;">
                            <p style="margin:0 0 6px;{{ $f }}font-size:14px;line-height:1.4;font-weight:700;color:{{ $c['label'] }};">
                                Please fix the following, then resubmit:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                @foreach ($remarks as $remark)
                                <tr>
                                    <td width="34" valign="top"
                                        style="padding:14px 0;border-top:1px solid #e5e7eb;{{ $f }}font-size:13px;line-height:1.6;font-weight:700;color:#94a3b8;">
                                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td valign="top"
                                        style="padding:14px 0;border-top:1px solid #e5e7eb;{{ $f }}font-size:16px;line-height:1.5;font-weight:700;color:#0f172a;">
                                        {{ $remark }}
                                    </td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td colspan="2" style="border-top:1px solid #e5e7eb;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>

                            <p style="margin:14px 0 0;{{ $f }}font-size:14px;line-height:1.6;color:#64748b;">
                                Log in to your dashboard and tap
                                <strong style="color:#0f172a;">{{ $c['action'] }}</strong>
                                to make the changes.
                            </p>
                        </td>
                    </tr>
                    @endif

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:40px 0 0;{{ $f }}font-size:12px;line-height:1.5;color:#8a919c;">
                            You are receiving this email because you registered an account with Ran Serenity.
                            This is an automated message, please do not reply.
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>