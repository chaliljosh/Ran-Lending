@php
    $copy = [
        'approved' => [
            'label' => 'Approved',
            'title' => 'Your loan application has been approved',
            'lines' => [
                'Your loan application has been reviewed and approved by our admins.',
                'Our team will contact you about the next steps. You can also track your loan anytime in your borrower account.',
            ],
        ],
        'rejected' => [
            'label' => 'Not approved',
            'title' => 'Update on your loan application',
            'lines' => [
                'Thank you for applying with Ran Serenity. After careful review, we are unable to approve your loan application at this time.',
                'If you have questions about this decision, please contact us directly.',
            ],
        ],
        'on_hold' => [
            'label' => 'On hold',
            'title' => 'Your loan application is on hold',
            'lines' => [
                'Your loan application is currently on hold pending additional review.',
                'You do not need to do anything right now. Our team may contact you for a follow-up, and we will let you know once there is an update.',
            ],
        ],
    ][$status] ?? null;

    $loanId   = 'LN-' . str_pad($loan->application_id, 6, '0', STR_PAD_LEFT);
    $termInt  = (int) $loan->loan_term;
    $greetEnd = $status === 'approved' ? '!' : ',';

    // Inline only. Do NOT add a <style> block: an unclosed one makes Gmail hide the whole body.
    $f = 'font-family:Helvetica,Arial,sans-serif;';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $copy['title'] ?? 'Loan application update' }}</title>
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

                    @if($copy)
                        {{-- Greeting --}}
                        <tr>
                            <td style="padding:0 0 16px;{{ $f }}font-size:22px;line-height:1.3;font-weight:700;color:#1f1f1f;">
                                Hello, {{ $borrowerName }}{{ $greetEnd }}
                            </td>
                        </tr>

                        {{-- Message --}}
                        <tr>
                            <td style="{{ $f }}font-size:15px;line-height:1.6;color:#1f1f1f;">
                                @foreach($copy['lines'] as $line)
                                    <p style="margin:0 0 12px;">{{ $line }}</p>
                                @endforeach
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
                                                    <td style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;color:#5f6b7a;">Status</td>
                                                    <td align="right" style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;font-weight:700;color:#1f1f1f;">{{ $copy['label'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;color:#5f6b7a;">Loan amount</td>
                                                    <td align="right" style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;font-weight:700;color:#1f1f1f;">₱{{ number_format($loan->loan_amount, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;color:#5f6b7a;">Term</td>
                                                    <td align="right" style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;font-weight:700;color:#1f1f1f;">{{ $termInt }} {{ $termInt === 1 ? 'month' : 'months' }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;color:#5f6b7a;">Date applied</td>
                                                    <td align="right" style="padding:12px 0;border-top:1px solid #d6e2fb;{{ $f }}font-size:14px;font-weight:700;color:#1f1f1f;">{{ \Carbon\Carbon::parse($loan->applied_at)->format('F j, Y') }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        {{-- Button --}}
                        
                    @endif

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:40px 0 0;{{ $f }}font-size:12px;line-height:1.5;color:#8a919c;">
                            You are receiving this email because you applied for a loan with Ran Serenity.
                            This is an automated message, please do not reply.
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>