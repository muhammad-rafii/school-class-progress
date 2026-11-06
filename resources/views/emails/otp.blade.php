<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Kode OTP</title></head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <h2 style="margin:0 0 8px;font-size:20px;">Reset Password</h2>
        <p style="margin:0 0 20px;color:#475569;font-size:14px;line-height:1.5;">
            Halo {{ trim($nama) }}, gunakan kode OTP berikut untuk mengatur ulang password akun School Class Progress Anda.
        </p>
        <div style="text-align:center;margin:24px 0;">
            <span style="display:inline-block;font-size:34px;font-weight:700;letter-spacing:10px;padding:14px 22px;background:#eef2ff;color:#4338ca;border-radius:12px;">{{ $otp }}</span>
        </div>
        <p style="margin:0 0 8px;color:#475569;font-size:13px;">Kode berlaku selama <strong>{{ $menit }} menit</strong> dan hanya dapat dipakai sekali.</p>
        <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.5;">Jika Anda tidak merasa meminta reset password, abaikan email ini. Jangan bagikan kode ini kepada siapa pun.</p>
    </div>
</body>
</html>