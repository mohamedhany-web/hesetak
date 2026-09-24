<?php

namespace App\Mail;

use App\Models\PackageGift;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PackageGiftPurchasedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PackageGift $gift) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تم تأكيد إهداء الباقة — حصتك',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.package-gift-purchased',
            with: ['gift' => $this->gift],
        );
    }
}
