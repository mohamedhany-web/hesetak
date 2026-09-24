<?php

namespace App\Mail;

use App\Models\PackageGift;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PackageGiftReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PackageGift $gift,
        public bool $recipientIsNew = false,
        public ?string $resetUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'وصلك هدية باقة تعليمية — حصتك',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.package-gift-received',
            with: [
                'gift' => $this->gift,
                'recipientIsNew' => $this->recipientIsNew,
                'resetUrl' => $this->resetUrl,
            ],
        );
    }
}
