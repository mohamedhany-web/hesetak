<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SupportTicketNewMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket
    ) {
        $this->ticket->loadMissing(['user', 'inquiryCategory']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[حصتك] تذكرة دعم فني جديدة: '.Str::limit($this->ticket->subject, 60),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-ticket-new',
        );
    }
}
