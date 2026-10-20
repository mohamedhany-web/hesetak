<?php

namespace App\Mail;

use App\Models\EmployeeAgreement;
use App\Services\EmployeeAgreementService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeAgreementSignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmployeeAgreement $agreement,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'نسخة موقعة من اتفاقية الموظف — '.$this->agreement->agreement_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-agreement-signed',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        try {
            $binary = app(EmployeeAgreementService::class)->pdfBinary($this->agreement, false);
        } catch (\Throwable) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $binary, $this->agreement->agreement_number.'-v'.$this->agreement->version.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
