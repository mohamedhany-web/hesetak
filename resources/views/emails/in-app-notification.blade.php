<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notification->title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fc;font-family:Tahoma,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fc;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
                <tr>
                    <td style="background:linear-gradient(135deg,#0B3D91,#0997d9);padding:18px 22px;color:#fff;">
                        <div style="font-size:13px;opacity:.9;">{{ config('app.name', 'حصتك') }}</div>
                        <div style="font-size:18px;font-weight:700;margin-top:4px;">{{ $notification->title }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px;">
                        @if($recipientName)
                            <p style="margin:0 0 12px;font-size:14px;color:#475569;">مرحباً {{ $recipientName }}،</p>
                        @endif
                        <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#1e293b;white-space:pre-wrap;">{{ $notification->message }}</p>
                        @php
                            $url = $notification->action_url
                                ? (str_starts_with((string) $notification->action_url, 'http')
                                    ? $notification->action_url
                                    : url($notification->action_url))
                                : route('notifications');
                            $label = $notification->action_text ?: 'عرض الإشعار';
                        @endphp
                        <a href="{{ $url }}" style="display:inline-block;background:#0B3D91;color:#fff;text-decoration:none;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:700;">
                            {{ $label }}
                        </a>
                        <p style="margin:18px 0 0;font-size:12px;color:#94a3b8;">
                            وصلك هذا البريد لأن حسابك مرتبط بـ {{ $notification->user?->email }}.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
