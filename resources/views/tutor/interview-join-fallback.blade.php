<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>المقابلة</title></head>
<body style="font-family:system-ui;padding:2rem;text-align:center">
<p>{{ $message }}</p>
@if(!empty($interview->external_url))
<p><a href="{{ $interview->external_url }}">فتح الرابط الخارجي</a></p>
@endif
</body>
</html>
