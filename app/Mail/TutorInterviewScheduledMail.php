<?php

namespace App\Mail;

use App\Models\TutorApplication;
use App\Models\TutorInterview;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TutorInterviewScheduledMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public TutorApplication $application,
        public TutorInterview $interview,
        public string $emailSubject,
        public string $emailBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tutor-interview-scheduled',
            with: [
                'application' => $this->application,
                'interview' => $this->interview,
                'bodyText' => $this->emailBody,
                'joinUrl' => $this->interview->effectiveJoinUrl(),
            ],
        );
    }
}
