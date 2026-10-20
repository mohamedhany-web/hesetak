<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نسخة موقعة من الاتفاقية</title>
</head>
<body style="font-family: Tahoma, Arial, sans-serif; direction: rtl; text-align: right; color: #111;">
    <p>تم توقيع اتفاقية الموظف <strong>{{ $agreement->agreement_number }}</strong> (الإصدار {{ $agreement->version }}).</p>
    <p>الموافق: {{ $agreement->signer_name }} · التاريخ: {{ $agreement->signed_at?->format('Y-m-d H:i') }}</p>
    <p>مرفق نسخة PDF من العقد الموقّع.</p>
    <p>{{ config('app.name') }}</p>
</body>
</html>
