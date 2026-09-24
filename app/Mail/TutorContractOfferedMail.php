<?php

namespace App\Mail;

use App\Models\InstructorAgreement;
use App\Models\TutorApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TutorContractOfferedMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public TutorApplication $application,
        public InstructorAgreement $agreement,
        public string $emailSubject,
        public string $emailBody,
        public string $signUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tutor-contract-offered',
            with: [
                'application' => $this->application,
                'agreement' => $this->agreement,
                'bodyText' => $this->emailBody,
                'signUrl' => $this->signUrl,
            ],
        );
    }
}
