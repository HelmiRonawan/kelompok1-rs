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
    public string $namaEmail;

    public function __construct(
        public User   $user,
        public string $plainToken
    ) {
        $this->namaEmail = $user->email;
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
