<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $agreement->title }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 12pt; color: #111; }
        h1 { font-size: 18pt; margin-bottom: 8px; }
        .meta { color: #444; margin-bottom: 16px; }
        .box { border: 1px solid #ccc; padding: 12px; margin: 12px 0; }
        .sig img { max-width: 220px; max-height: 90px; }
    </style>
</head>
<body>
    <h1>{{ $agreement->title }}</h1>
    <div class="meta">
        {{ $appName }} · رقم الاتفاقية: {{ $agreement->agreement_number }}<br>
        المعلم: {{ $agreement->tutorApplication?->full_name ?? $agreement->instructor?->name }}
    </div>
    <div class="box">
        <strong>تفاصيل الأجر</strong><br>
        النوع: {{ $agreement->billing_type }}<br>
        @if($agreement->salary_per_session) أجر الجلسة: {{ $agreement->salary_per_session }}<br>@endif
        @if($agreement->monthly_amount) الراتب الشهري: {{ $agreement->monthly_amount }}<br>@endif
        @if($agreement->rate) القيمة: {{ $agreement->rate }}<br>@endif
    </div>
    <div class="box">
        <strong>الشروط</strong>
        <div style="white-space: pre-wrap;">{{ $agreement->terms }}</div>
    </div>
    <div class="box sig">
        <strong>التوقيع الإلكتروني</strong><br>
        الاسم: {{ $signerName }}<br>
        التاريخ: {{ now()->format('Y-m-d H:i') }}<br>
        @if($signatureDataUri)
            <img src="{{ $signatureDataUri }}" alt="signature">
        @endif
    </div>
</body>
</html>
