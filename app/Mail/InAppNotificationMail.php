<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InAppNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public string $recipientName = '',
    ) {}

    public function envelope(): Envelope
    {
        $app = config('app.name', 'حصتك');

        return new Envelope(
            subject: '['.$app.'] '.$this->notification->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.in-app-notification',
        );
    }
}
