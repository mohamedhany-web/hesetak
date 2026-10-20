{{ \App\Support\EmailBranding::brandName() }}

@if(!empty($recipientName))
مرحباً {{ $recipientName }}،
@endif

{{ $notification->title }}

{{ $notification->message }}

@php
    $url = $notification->action_url
        ? (str_starts_with((string) $notification->action_url, 'http')
            ? $notification->action_url
            : url($notification->action_url))
        : \App\Support\EmailBranding::notificationsUrl();
@endphp
{{ $notification->action_text ?: 'عرض التفاصيل' }}: {{ $url }}

—
{{ \App\Support\EmailBranding::brandName() }} · {{ \App\Support\EmailBranding::siteUrl() }}
هذه رسالة تلقائية من حسابك على المنصة.
