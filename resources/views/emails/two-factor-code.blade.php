@extends('emails.layouts.brand')

@php
    $emailTitle = 'رمز الدخول';
    $emailHeading = 'رمز المصادقة الثنائية';
    $emailEyebrow = 'أمان حسابك';
    $emailPreheader = 'رمز الدخول صالح لدقائق قليلة فقط.';
    $c = \App\Support\EmailBranding::colors();
@endphp

@section('content')
    @if(!empty($forSystemSettings))
        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#334155;">
            استخدم الرمز التالي لتأكيد تفعيل إلزام المصادقة الثنائية لحسابات الأدمن من إعدادات النظام:
        </p>
    @else
        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#334155;">
            استخدم الرمز التالي لإكمال تسجيل الدخول بأمان:
        </p>
    @endif
    <div style="text-align:center;margin:18px 0;padding:18px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:14px;">
        <div style="font-size:28px;letter-spacing:0.35em;font-weight:800;color:{{ $c['navy'] }};font-family:Consolas,Monaco,monospace;">{{ $code }}</div>
    </div>
    <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
        الرمز صالح لمدة 10 دقائق. إن لم تطلبه، تجاهل الرسالة وغيّر كلمة المرور فوراً.
    </p>
@endsection
