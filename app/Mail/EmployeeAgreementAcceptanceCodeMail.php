<?php

namespace App\Mail;

use App\Models\EmployeeAgreement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeAgreementAcceptanceCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmployeeAgreement $agreement,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'رمز تأكيد اتفاقية الموظف — '.$this->agreement->agreement_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-agreement-acceptance-code',
        );
    }
}
