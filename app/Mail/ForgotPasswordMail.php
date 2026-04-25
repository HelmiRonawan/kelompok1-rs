<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;
    public string $namaUser;
    public string $expiredInfo;

    public function __construct(
        public User   $user,
        public string $plainToken
    ) {
        // URL ini mengarah ke frontend (web/mobile) yang punya halaman reset form
        // Frontend lalu POST ke /api/auth/reset-password dengan token + email + password baru
        $frontendUrl    = config('app.frontend_url', config('app.url'));
        $this->resetUrl = "{$frontendUrl}/reset-password?token={$plainToken}&email={$user->email}";

        $this->namaUser   = $user->nama_lengkap;
        $this->expiredInfo = '15 menit';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[RS] Reset Password Akun Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.forgot-password',
        );
    }
}
