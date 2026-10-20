<!DOCTYPE html>
<html lang="ar" dir="rtl" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>{{ $emailTitle ?? ($emailHeading ?? \App\Support\EmailBranding::brandName()) }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
@php
    $c = \App\Support\EmailBranding::colors();
    $brand = \App\Support\EmailBranding::brandName();
    $logo = \App\Support\EmailBranding::logoUrl();
    $site = \App\Support\EmailBranding::siteUrl();
    $support = \App\Support\EmailBranding::supportUrl();
    $preheader = trim((string) ($emailPreheader ?? ''));
@endphp
<body style="margin:0;padding:0;background:{{ $c['bg'] }};-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
@if($preheader !== '')
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        {{ $preheader }}
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
    </div>
@endif
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:{{ $c['bg'] }};margin:0;padding:0;width:100%;">
    <tr>
        <td align="center" style="padding:28px 12px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;">
                {{-- Brand header --}}
                <tr>
                    <td align="center" style="padding:0 0 18px;">
                        <a href="{{ $site }}" style="text-decoration:none;display:inline-block;">
                            <img src="{{ $logo }}" alt="{{ $brand }}" width="160" height="auto" style="display:block;width:160px;max-width:70%;height:auto;border:0;outline:none;text-decoration:none;" />
                        </a>
                        <div style="font-family:Tahoma,'Segoe UI',Arial,sans-serif;font-size:13px;font-weight:700;color:{{ $c['navy'] }};letter-spacing:0.02em;margin-top:10px;">
                            {{ $brand }}
                        </div>
                    </td>
                </tr>

                {{-- Card --}}
                <tr>
                    <td style="background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 8px 24px rgba(21,42,74,0.06);">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="height:5px;background:linear-gradient(90deg,{{ $c['navyDeep'] }},{{ $c['navy'] }},{{ $c['gold'] }});font-size:0;line-height:0;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="padding:28px 26px 8px;font-family:Tahoma,'Segoe UI',Arial,sans-serif;color:{{ $c['text'] }};direction:rtl;text-align:right;">
                                    @isset($emailEyebrow)
                                        <div style="display:inline-block;font-size:11px;font-weight:700;color:{{ $c['gold'] }};background:rgba(201,149,42,0.12);padding:5px 12px;border-radius:999px;margin-bottom:12px;">
                                            {{ $emailEyebrow }}
                                        </div>
                                    @endisset
                                    @isset($emailHeading)
                                        <h1 style="margin:0 0 14px;font-size:22px;line-height:1.45;font-weight:800;color:{{ $c['navyDeep'] }};">
                                            {{ $emailHeading }}
                                        </h1>
                                    @endisset
                                    @yield('content')
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:8px 26px 28px;font-family:Tahoma,'Segoe UI',Arial,sans-serif;direction:rtl;text-align:right;">
                                    @isset($emailCtaUrl)
                                        <a href="{{ $emailCtaUrl }}" style="display:inline-block;background:{{ $c['navy'] }};color:#ffffff !important;text-decoration:none;padding:13px 22px;border-radius:12px;font-size:14px;font-weight:700;border:1px solid {{ $c['navyDeep'] }};">
                                            {{ $emailCtaLabel ?? 'فتح المنصة' }}
                                        </a>
                                        <div style="margin-top:14px;font-size:12px;line-height:1.6;color:{{ $c['muted'] }};">
                                            أو انسخ الرابط:<br>
                                            <a href="{{ $emailCtaUrl }}" style="color:{{ $c['navy'] }};word-break:break-all;">{{ $emailCtaUrl }}</a>
                                        </div>
                                    @endisset
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td align="center" style="padding:22px 10px 8px;font-family:Tahoma,'Segoe UI',Arial,sans-serif;font-size:12px;line-height:1.7;color:{{ $c['muted'] }};direction:rtl;">
                        <div style="font-weight:700;color:{{ $c['navy'] }};margin-bottom:4px;">{{ $brand }}</div>
                        <div>دروس خصوصية أونلاين · مناهج · كورسات</div>
                        <div style="margin-top:8px;">
                            <a href="{{ $site }}" style="color:{{ $c['navy'] }};text-decoration:none;margin:0 6px;">الموقع</a>
                            ·
                            <a href="{{ $support }}" style="color:{{ $c['navy'] }};text-decoration:none;margin:0 6px;">تواصل معنا</a>
                            ·
                            <a href="{{ \App\Support\EmailBranding::notificationsUrl() }}" style="color:{{ $c['navy'] }};text-decoration:none;margin:0 6px;">الإشعارات</a>
                        </div>
                        <div style="margin-top:12px;font-size:11px;color:#94a3b8;max-width:480px;">
                            وصلك هذا البريد لأن لديك حساباً على {{ $brand }}. الرسالة من {{ config('mail.from.address') }}.
                            لست بحاجة للرد على هذا البريد.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
