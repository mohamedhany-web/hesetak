<?php

namespace App\Mail;

use App\Mail\Concerns\HesetakMailDefaults;
use App\Support\EmailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use HesetakMailDefaults;
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public bool $forSystemSettings = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->forSystemSettings
            ? EmailBranding::brandName().' · رمز تأكيد المصادقة الثنائية'
            : EmailBranding::brandName().' · رمز الدخول';

        return new Envelope(
            from: $this->hesetakFrom(),
            replyTo: $this->hesetakReplyTo(),
            subject: $subject,
        );
    }

    public function headers(): Headers
    {
        return $this->hesetakHeaders([
            'entity' => '2fa-'.now()->format('YmdHi'),
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.two-factor-code',
        );
    }
}
