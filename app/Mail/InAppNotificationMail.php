<?php

namespace App\Mail;

use App\Mail\Concerns\HesetakMailDefaults;
use App\Models\Notification;
use App\Support\EmailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class InAppNotificationMail extends Mailable
{
    use HesetakMailDefaults;
    use Queueable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public string $recipientName = '',
    ) {}

    public function envelope(): Envelope
    {
        $title = trim((string) $this->notification->title);
        $subject = $title !== ''
            ? EmailBranding::brandName().' · '.$title
            : EmailBranding::brandName().' · إشعار جديد';

        return new Envelope(
            from: $this->hesetakFrom(),
            replyTo: $this->hesetakReplyTo(),
            subject: mb_substr($subject, 0, 180),
        );
    }

    public function headers(): Headers
    {
        return $this->hesetakHeaders([
            'entity' => 'notif-'.$this->notification->id,
        ]);
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.in-app-notification',
            text: 'emails.in-app-notification-text',
        );
    }
}
