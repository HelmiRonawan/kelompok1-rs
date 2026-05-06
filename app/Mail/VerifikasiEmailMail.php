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
    public string $namaEmail;
    public string $otp;

    public function __construct(
        public User   $user,
        public string $plainToken
    ) {
        $this->namaEmail     = $user->email;
        $this->otp           = $plainToken;
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
