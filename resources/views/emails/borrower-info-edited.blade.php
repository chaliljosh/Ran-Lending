@php
    $loanId = $loanCode ?? ('LN-' . strtoupper(substr($loan->uuid, 0, 8)));

    // Inline only. Do NOT add a <style> block: an unclosed one makes Gmail hide the whole body.
    $f = 'font-family:Helvetica,Arial,sans-serif;';

    $logoSrc = \Illuminate\Support\Facades\Storage::disk('supabase')->url('branding/ranlogo.png');

    $cream      = '#ffffff';
    $ink        = '#1c1b22';
    $muted      = '#6b6a75';
    $line       = '#e6e4ee';
    $purple     = '#9b7bd6';
    $purpleDark = '#6b4bb8';
    $lavender   = '#efebf7';
    $count      = count($changes);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Your information was updated</title>
</head>
<body bgcolor="{{ $cream }}" style="margin:0;padding:0;background:{{ $cream }};{{ $f }}color:{{ $ink }};">

    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#ffffff;font-size:1px;line-height:1px;">
        An admin corrected {{ $count }} {{ $count === 1 ? 'detail' : 'details' }} on your loan application.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $cream }}">
        <tr>
            <td align="center" style="padding:0 20px 32px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

                    {{-- Header --}}
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

                    {{-- Title and message --}}
                    <tr>
                        <td style="padding:36px 0 0;{{ $f }}font-size:14px;font-weight:400;letter-spacing:0.5px;color:{{ $purpleDark }};">
                            Information updated
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0 12px;{{ $f }}font-size:36px;line-height:1.15;font-weight:700;color:{{ $ink }};">
                            Hello, <span style="color:{{ $purple }};">{{ $displayName }}</span>!
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 28px;{{ $f }}font-size:16px;font-weight:400;line-height:1.6;color:{{ $muted }};">
                            While reviewing your loan application
                            <strong style="color:{{ $ink }};">{{ $loanId }}</strong>,
                            an admin corrected {{ $count === 1 ? 'one detail' : $count . ' details' }} in your information.
                            The changes are listed below.
                        </td>
                    </tr>

                    {{-- Changes card --}}
                    <tr>
                        <td>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="background:#ffffff;border:1px solid {{ $line }};border-radius:18px;">
                                <tr>
                                    <td style="padding:24px 24px 8px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="{{ $f }}font-size:13px;font-weight:400;color:{{ $muted }};">Application</td>
                                                <td align="right" rowspan="2" valign="top">
                                                    <span style="display:inline-block;padding:5px 14px;background:{{ $lavender }};border-radius:999px;{{ $f }}font-size:13px;font-weight:400;color:{{ $purpleDark }};">Updated</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top:4px;{{ $f }}font-size:26px;line-height:1.2;font-weight:700;letter-spacing:1px;color:{{ $ink }};">{{ $loanId }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:8px 24px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            @foreach ($changes as $c)
                                                <tr>
                                                    <td style="padding:14px 0;border-top:1px solid {{ $line }};">
                                                        <div style="{{ $f }}font-size:13px;font-weight:400;color:{{ $muted }};">{{ $c['label'] }}</div>
                                                        <div style="padding-top:4px;{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};text-decoration:line-through;">{{ $c['old'] }}</div>
                                                        <div style="padding-top:2px;{{ $f }}font-size:16px;font-weight:700;color:{{ $ink }};">{{ $c['new'] }}</div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>

                                {{-- Reason --}}
                                <tr>
                                    <td style="padding:0 16px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $lavender }}" style="background:{{ $lavender }};border-radius:14px;">
                                            <tr>
                                                <td style="padding:16px 16px 4px;{{ $f }}font-size:13px;font-weight:400;color:{{ $muted }};">Reason for the change</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:0 16px 16px;{{ $f }}font-size:15px;font-weight:400;line-height:1.5;color:{{ $ink }};">{{ $reason }}</td>
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
                            If any of this looks wrong, please contact us right away.
                            This is an automated message, please do not reply.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>