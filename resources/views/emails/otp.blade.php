<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 40px; }
        .logo { text-align: center; margin-bottom: 24px; font-size: 1.5rem; font-weight: 700; }
        .otp-box { background: #f0f4ff; border-radius: 10px; padding: 20px; text-align: center; margin: 24px 0; }
        .otp-code { font-size: 2.5rem; font-weight: 700; color: #4a7cf7; letter-spacing: 8px; }
        .footer { text-align: center; color: #adb5bd; font-size: 0.8rem; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Ran Serenity</div>
        <h2>Hello, {{ $name }}!</h2>
        <p>Your one-time verification code is:</p>
        <div class="otp-box">
            <div class="otp-code">{{ $otp }}</div>
        </div>
        <p>This code expires in <strong>10 minutes</strong>. Do not share this with anyone.</p>
        <div class="footer">© 2025 Ran Serenity Lending System</div>
    </div>
</body>
</html>