@extends('emails.layouts.brand')

@php
    $emailTitle = $notification->title;
    $emailHeading = $notification->title;
    $emailEyebrow = 'إشعار من حصتك';
    $emailPreheader = \Illuminate\Support\Str::limit(strip_tags((string) $notification->message), 90, '…');
    $emailCtaUrl = $notification->action_url
        ? (str_starts_with((string) $notification->action_url, 'http')
            ? $notification->action_url
            : url($notification->action_url))
        : \App\Support\EmailBranding::notificationsUrl();
    $emailCtaLabel = $notification->action_text ?: 'عرض التفاصيل';
@endphp

@section('content')
    @if(!empty($recipientName))
        <p style="margin:0 0 12px;font-size:15px;line-height:1.7;color:#334155;">
            مرحباً {{ $recipientName }}،
        </p>
    @endif
    <p style="margin:0;font-size:15px;line-height:1.85;color:#1e293b;white-space:pre-wrap;">{{ $notification->message }}</p>
@endsection
