@php
    $displayName = $name ?? ($firstName ?? 'there');
    $code = (string) $otp;

    $f = 'font-family:Helvetica,Arial,sans-serif;';

    $logoSrc = \Illuminate\Support\Facades\Storage::disk('supabase')->url('branding/ranlogo.png');

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
    <title>Your verification code</title>
</head>
<body bgcolor="{{ $cream }}" style="margin:0;padding:0;background:{{ $cream }};{{ $f }}color:{{ $ink }};">

    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#ffffff;font-size:1px;line-height:1px;">
        Your RAN Serenity verification code is {{ $code }}. It expires in 10 minutes.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $cream }}">
        <tr>
            <td align="center" style="padding:0 20px 32px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

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

                    <tr>
                        <td style="padding:36px 0 0;{{ $f }}font-size:14px;font-weight:400;letter-spacing:0.5px;color:{{ $purpleDark }};">
                            Email verification
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0 12px;{{ $f }}font-size:36px;line-height:1.15;font-weight:700;color:{{ $ink }};">
                            Hello, <span style="color:{{ $purple }};">{{ $displayName }}</span>!
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 28px;{{ $f }}font-size:16px;font-weight:400;line-height:1.6;color:{{ $muted }};">
                            Use the code below to verify your email and finish creating your account.
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="background:#ffffff;border:1px solid {{ $line }};border-radius:18px;">

                                <tr>
                                    <td style="padding:24px 24px 8px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="{{ $f }}font-size:13px;font-weight:400;color:{{ $muted }};">Verification code</td>
                                                <td align="right" valign="top">
                                                    <span style="display:inline-block;padding:5px 14px;background:{{ $lavender }};border-radius:999px;{{ $f }}font-size:13px;font-weight:400;color:{{ $purpleDark }};">One-time use</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:8px 16px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="{{ $lavender }}" style="background:{{ $lavender }};border-radius:14px;">
                                            <tr>
                                                <td style="padding:24px 16px;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            @foreach (str_split($code) as $i => $digit)
                                                                @if ($i > 0)
                                                                    <td width="2%" style="font-size:0;line-height:0;">&nbsp;</td>
                                                                @endif
                                                                <td width="15%" align="center" bgcolor="#ffffff" style="background:#ffffff;border:1px solid {{ $line }};border-radius:10px;padding:14px 0;{{ $f }}font-size:28px;line-height:1.2;font-weight:700;color:{{ $ink }};">{{ $digit }}</td>
                                                            @endforeach
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0 24px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $muted }};">Expires in</td>
                                                <td align="right" style="padding:14px 0;border-top:1px solid {{ $line }};{{ $f }}font-size:15px;font-weight:400;color:{{ $ink }};">10 minutes</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 0 0;{{ $f }}font-size:15px;font-weight:400;line-height:1.5;color:{{ $purpleDark }};">
                            Do not share this code with anyone.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0 0;{{ $f }}font-size:13px;font-weight:400;line-height:1.5;color:{{ $muted }};">
                            RAN Serenity will never ask you for your code.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 0 0;{{ $f }}font-size:12px;font-weight:400;line-height:1.5;color:{{ $muted }};">
                            If you did not request this code, you can safely ignore this email.
                            This is an automated message, please do not reply.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0 0;{{ $f }}font-size:12px;font-weight:400;line-height:1.5;color:{{ $muted }};">
                            &copy; {{ now()->year }} RAN Serenity Lending System
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>