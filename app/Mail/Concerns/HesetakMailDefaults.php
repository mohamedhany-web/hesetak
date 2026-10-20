<?php

namespace App\Mail\Concerns;

use App\Support\EmailBranding;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Headers;

trait HesetakMailDefaults
{
    protected function hesetakFrom(): Address
    {
        return new Address(
            (string) config('mail.from.address'),
            EmailBranding::brandName()
        );
    }

    protected function hesetakReplyTo(): array
    {
        $from = (string) config('mail.from.address');

        return [new Address($from, EmailBranding::brandName())];
    }

    /**
     * رؤوس تقلّل التصنيف كـ spam وتوضح هوية المرسل.
     *
     * @param  array{entity?: string}&array<string, string>  $extra
     */
    protected function hesetakHeaders(array $extra = []): Headers
    {
        $entity = (string) ($extra['entity'] ?? uniqid('hesetak-', true));
        unset($extra['entity']);

        $unsubscribe = '<'.EmailBranding::notificationsUrl().'>';

        return new Headers(
            text: array_merge([
                'List-Unsubscribe' => $unsubscribe,
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                'X-Auto-Response-Suppress' => 'OOF, AutoReply',
                'X-Mailer' => EmailBranding::brandName().' Platform',
                'X-Entity-Ref-ID' => $entity,
            ], $extra)
        );
    }
}
