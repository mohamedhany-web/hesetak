<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FamilyProgressReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public User $student,
        public array $payload,
    ) {}

    public function envelope(): Envelope
    {
        $period = $this->payload['period']['label'] ?? now()->format('Y-m');

        return new Envelope(
            subject: 'تقرير عائلة حصتك — '.$this->student->name.' ('.$period.')',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.family-progress-report',
            with: [
                'student' => $this->student,
                'payload' => $this->payload,
            ],
        );
    }
}
