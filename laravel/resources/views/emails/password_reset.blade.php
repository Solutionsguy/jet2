<!DOCTYPE html>
<html>
<head>
    <style>
        .container { font-family: sans-serif; padding: 20px; color: #333; }
        .otp-box { background: #f4f4f4; padding: 15px; font-size: 24px; font-weight: bold; text-align: center; border-radius: 5px; margin: 20px 0; letter-spacing: 5px; }
        .footer { font-size: 12px; color: #777; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Password Reset Request</h2>
        <p>Hello,</p>
        <p>You are receiving this email because we received a password reset request for your account. Use the code below to reset your password:</p>
        
        <div class="otp-box">
            {{ $otp }}
        </div>
        
        <p>This code will expire in 15 minutes.</p>
        <p>If you did not request a password reset, no further action is required.</p>
        
        <div class="footer">
            Regards,<br>
            {{ config('app.name') }} Team
        </div>
    </div>
</body>
</html>
