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

    public string $otp;
    public string $namaUser;

    public function __construct(
        public User   $user,
        public string $plainToken
    ) {
        $this->namaUser = $user->nama_lengkap;
        $this->otp      = $plainToken;
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
