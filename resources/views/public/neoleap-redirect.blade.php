<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>{{ app()->getLocale() === 'ar' ? 'جاري التحويل للدفع…' : 'Redirecting to payment…' }} — {{ __('landing.nav.brand') }}</title>
  <style>
    body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:"IBM Plex Sans Arabic",Tahoma,sans-serif;background:#f4f7fb;color:#16365f}
    .box{text-align:center;padding:2rem 1.25rem;max-width:26rem}
    .spin{width:2.25rem;height:2.25rem;border:3px solid #d7e2f0;border-top-color:#1E4E8C;border-radius:50%;margin:0 auto 1rem;animation:s .8s linear infinite}
    @keyframes s{to{transform:rotate(360deg)}}
    p{margin:.35rem 0;line-height:1.6}
    a{color:#1E4E8C;font-weight:700}
    noscript a{display:inline-block;margin-top:1rem;padding:.75rem 1.2rem;background:#1E4E8C;color:#fff;border-radius:.75rem;text-decoration:none}
  </style>
</head>
<body>
  <div class="box">
    <div class="spin" aria-hidden="true"></div>
    <p><strong>{{ app()->getLocale() === 'ar' ? 'جاري فتح صفحة الدفع الآمنة…' : 'Opening the secure payment page…' }}</strong></p>
    <p>{{ app()->getLocale() === 'ar' ? 'إذا لم يتم التحويل تلقائياً، اضغط الرابط بالأسفل.' : 'If you are not redirected, use the link below.' }}</p>
    <noscript>
      <a href="{{ $redirectUrl }}">{{ app()->getLocale() === 'ar' ? 'متابعة الدفع' : 'Continue to payment' }}</a>
    </noscript>
    <p style="margin-top:1.25rem"><a id="nl-fallback" href="{{ $redirectUrl }}">{{ app()->getLocale() === 'ar' ? 'متابعة الدفع الآن' : 'Continue to payment now' }}</a></p>
  </div>
  <form id="nl-form" method="get" action="{{ $paymentPageUrl }}" accept-charset="UTF-8">
    <input type="hidden" name="PaymentID" value="{{ $paymentId }}">
  </form>
  <script>
    (function () {
      var url = @json($redirectUrl);
      var form = document.getElementById('nl-form');
      try {
        if (form) {
          form.submit();
          return;
        }
      } catch (e) {}
      window.location.replace(url);
    })();
  </script>
</body>
</html>
