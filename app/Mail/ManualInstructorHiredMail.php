<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManualInstructorHiredMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $instructor,
        public ?string $temporaryPassword = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تم تعيينك معلماً في '.config('app.name', 'حصتك'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.manual-instructor-hired',
            with: [
                'instructor' => $this->instructor,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => url('/login'),
            ],
        );
    }
}
