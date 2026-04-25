<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifikasiEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verifikasiUrl;
    public string $namaUser;

    public function __construct(
        public User   $user,
        public string $plainToken
    ) {
        $frontendUrl         = config('app.frontend_url', config('app.url'));
        $this->verifikasiUrl = "{$frontendUrl}/verify-email?token={$plainToken}&email={$user->email}";
        $this->namaUser      = $user->nama_lengkap;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[RS] Verifikasi Email Akun Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verifikasi-email',
        );
    }
}
