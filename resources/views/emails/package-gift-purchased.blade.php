<x-mail::message>
# تم تأكيد إهداء الباقة

مرحباً {{ $gift->buyer?->name ?? 'عزيزي' }}،

تم دفع باقة **{{ $gift->servicePackage?->name ?? 'حصص' }}** كهدية إلى:

- البريد: {{ $gift->recipient_email }}
@if($gift->recipient_name)
- الاسم: {{ $gift->recipient_name }}
@endif

@if($gift->quoted_total)
المبلغ: **{{ number_format((float) $gift->quoted_total, 2) }} {{ $gift->currency ?: 'SAR' }}**
@endif

شكراً لثقتك في حصتك.

{{ config('app.name') }}
</x-mail::message>
