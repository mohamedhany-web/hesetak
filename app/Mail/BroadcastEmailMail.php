<?php

namespace App\Mail;

use App\Mail\Concerns\HesetakMailDefaults;
use App\Models\EmailBroadcast;
use App\Models\EmailBroadcastRecipient;
use App\Support\EmailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class BroadcastEmailMail extends Mailable
{
    use HesetakMailDefaults;
    use Queueable, SerializesModels;

    public function __construct(
        public EmailBroadcast $broadcast,
        public EmailBroadcastRecipient $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->hesetakFrom(),
            replyTo: $this->hesetakReplyTo(),
            subject: mb_substr(EmailBranding::brandName().' · '.$this->broadcast->subject, 0, 180),
        );
    }

    public function headers(): Headers
    {
        return $this->hesetakHeaders([
            'entity' => 'broadcast-'.$this->broadcast->id.'-'.$this->recipient->id,
            'Precedence' => 'bulk',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.broadcast',
            with: [
                'broadcast' => $this->broadcast,
                'recipient' => $this->recipient,
            ],
        );
    }
}
