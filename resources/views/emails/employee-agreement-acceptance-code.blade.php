<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>رمز تأكيد الاتفاقية</title>
</head>
<body style="font-family: Tahoma, Arial, sans-serif; direction: rtl; text-align: right; color: #111;">
    <p>مرحباً {{ $agreement->employee?->name }}،</p>
    <p>رمز تأكيد الموافقة على اتفاقية الموظف <strong>{{ $agreement->agreement_number }}</strong> (الإصدار {{ $agreement->version }}):</p>
    <p style="font-size: 28px; letter-spacing: 6px; font-weight: bold;">{{ $code }}</p>
    <p>صالح لمدة 30 دقيقة. إن لم تطلب هذا الرمز فتجاهل الرسالة.</p>
    <p>{{ config('app.name') }}</p>
</body>
</html>
