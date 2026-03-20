<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperación de Contraseña - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        $expirationMinutes = config('auth.passwords.users.expire', 60);

        return new Content(
            view: 'emails.reset-password',
            with: [
                'resetUrl' => $this->resetUrl,
                'expirationMinutes' => $expirationMinutes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
