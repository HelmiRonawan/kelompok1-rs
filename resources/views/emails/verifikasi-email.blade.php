<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 560px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: white;
            padding: 32px 40px;
            text-align: center;
        }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p  { margin: 6px 0 0; font-size: 13px; opacity: 0.85; }
        .body      { padding: 36px 40px; color: #333; }
        .body p    { font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
        .btn-container { text-align: center; margin: 28px 0; }
        .btn {
            display: inline-block;
            background-color: #1a73e8;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
        }
        .warning-box {
            background-color: #e8f5e9;
            border-left: 4px solid #43a047;
            padding: 14px 18px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 14px;
            color: #555;
        }
        .url-box {
            background-color: #f1f3f4;
            border-radius: 6px;
            padding: 12px 16px;
            word-break: break-all;
            font-size: 12px;
            color: #555;
            margin-top: 10px;
        }
        .footer {
            background-color: #f4f6f8;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #e8eaed;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Rumah Sakit — Verifikasi Email</h1>
        <p>Sistem Informasi Manajemen Rumah Sakit</p>
    </div>

    <div class="body">
        <p>Halo, <strong>{{ $namaUser }}</strong>!</p>

        <p>Terima kasih sudah mendaftar. Klik tombol di bawah untuk memverifikasi
            email kamu dan mengaktifkan akun:</p>

        <div style="text-align: center; margin: 28px 0;">
            <p>Masukkan kode OTP berikut di aplikasi:</p>
            <div style="
            display: inline-block;
            background: #1a73e8;
            color: white;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 12px;
            padding: 16px 32px;
            border-radius: 10px;
        ">
            {{ $otp }}
        </div>
    </div>

    <div class="warning-box">
        Kode OTP berlaku <strong>10 menit</strong>.
        Jangan berikan kode ini ke siapapun.
    </div>

        <p style="margin-top: 24px;">Jika kamu tidak merasa mendaftar, abaikan email ini.</p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} Rumah Sakit. Email ini dikirim otomatis, jangan dibalas.
    </div>
</div>
</body>
</html>
