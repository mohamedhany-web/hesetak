<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $agreement->title }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 11pt; color: #111; line-height: 1.55; }
        h1 { font-size: 16pt; margin: 0 0 6px; }
        h2 { font-size: 12pt; margin: 16px 0 6px; }
        .meta { color: #444; margin-bottom: 14px; font-size: 10pt; }
        .box { border: 1px solid #ccc; padding: 10px 12px; margin: 10px 0; }
        .row { margin: 3px 0; }
        .muted { color: #666; font-size: 9pt; }
        .pre { white-space: pre-wrap; }
        .sig { margin-top: 18px; }
    </style>
</head>
<body>
    <h1>{{ $agreement->title }}</h1>
    <div class="meta">
        {{ $appName }} · اتفاقية موظف<br>
        رقم الاتفاقية: {{ $agreement->agreement_number }} · الإصدار: {{ $agreement->version }}
    </div>

    <div class="box">
        <strong>أطراف العقد</strong>
        <div class="row">الطرف الأول (المنصة): {{ $appName }}</div>
        <div class="row">الطرف الثاني (الموظف): {{ $agreement->employee?->name }}</div>
        <div class="row">البريد: {{ $agreement->employee?->email ?? '—' }}</div>
        <div class="row">الجوال: {{ $agreement->employee?->phone ?? '—' }}</div>
        <div class="row">المسمى الوظيفي: {{ $agreement->employee?->employeeJob?->name ?? '—' }}</div>
    </div>

    <div class="box">
        <strong>الأجر والمدة</strong>
        <div class="row">الراتب الشهري: {{ number_format((float) $agreement->salary, 2) }} {{ $currency }}</div>
        <div class="row">تاريخ البدء: {{ optional($agreement->start_date)->format('Y-m-d') }}</div>
        <div class="row">تاريخ الانتهاء: {{ $agreement->end_date ? $agreement->end_date->format('Y-m-d') : 'غير محدد' }}</div>
        <div class="row">الحالة: {{ $agreement->status }}</div>
    </div>

    @if($agreement->description)
    <h2>الوصف</h2>
    <div class="pre">{{ $agreement->description }}</div>
    @endif

    <h2>شروط العقد</h2>
    <div class="box pre">{{ $agreement->contract_terms }}</div>

    <h2>بنود الاتفاقية</h2>
    <div class="box pre">{{ $agreement->agreement_terms }}</div>

    @if($agreement->notes)
    <h2>ملاحظات</h2>
    <div class="pre">{{ $agreement->notes }}</div>
    @endif

    <div class="box sig">
        <strong>الموافقة الإلكترونية</strong>
        @php
            $signedAtDisplay = $signedAt ?? $agreement->signed_at;
        @endphp
        @if($signerName && $signedAtDisplay)
            <div class="row">الموافق: {{ $signerName }}</div>
            <div class="row">تاريخ الموافقة: {{ \Illuminate\Support\Carbon::parse($signedAtDisplay)->format('Y-m-d H:i') }}</div>
            <div class="muted">تم التأكيد برمز OTP مرسل إلى بريد الموظف وحُفظت نسخة PDF موقعة.</div>
        @elseif($signerName)
            <div class="row">الموافق: {{ $signerName }}</div>
            <div class="row">تاريخ الموافقة: {{ now()->format('Y-m-d H:i') }}</div>
            <div class="muted">تم التأكيد برمز OTP مرسل إلى بريد الموظف وحُفظت نسخة PDF موقعة.</div>
        @else
            <div class="muted">مسودة / غير موقّعة بعد — هذه معاينة فقط.</div>
        @endif
    </div>
</body>
</html>
