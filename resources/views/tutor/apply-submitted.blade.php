@php
  $locale = app()->getLocale();
  $isRtl = $locale === 'ar';
  $brand = __('landing.nav.brand');
  $application = $application ?? null;
  $waitStatus = $waitStatus ?? ($application->status ?? 'pending');
  $isApproved = $waitStatus === \App\Models\TutorApplication::STATUS_APPROVED;
  $mcCss = public_path('css/landing/mycourses.css');
  $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
  $mcActive = 'for-teachers';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
  <title>{{ $isApproved
    ? ($isRtl ? 'بانتظار تفعيل الإدارة' : 'Waiting for admin activation')
    : ($isRtl ? 'طلبك قيد المراجعة' : 'Application under review') }} · {{ $brand }}</title>
  <meta name="robots" content="noindex">
  <meta name="theme-color" content="#1E4E8C">
  @include('partials.favicon-links')
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
</head>
<body class="mc-body mc-body--ta">
@include('partials.landing.mycourses.nav')

<main class="mc-ta mc-ta--status">
  <div class="mc-container">
    @if(session('success'))
      <div class="mc-ta-alert is-ok" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="mc-ta-alert is-err" role="alert">{{ session('error') }}</div>
    @endif

    <section class="mc-ta-status" aria-labelledby="mc-ta-status-title">
      <div class="mc-ta-status__icon" aria-hidden="true">
        <i class="fas fa-hourglass-half"></i>
      </div>
      <p class="mc-eyebrow">{{ $isRtl ? 'توظيف المعلمين' : 'Teacher hiring' }}</p>
      <h1 id="mc-ta-status-title">
        {{ $isApproved
          ? ($isRtl ? 'تم قبول طلبك، بانتظار التفعيل' : 'Accepted, waiting for activation')
          : ($isRtl ? 'طلبك قيد المراجعة' : 'Your application is under review') }}
      </h1>
      <p class="mc-ta-status__lead">
        {{ $isApproved
          ? ($isRtl
            ? 'قبلنا ملفك. لوحة المعلم تُفتح بعد تفعيل الإدارة. استخدم نفس الإيميل وكلمة المرور عند العودة.'
            : 'Your profile was accepted. The dashboard opens after admin activation. Use the same email and password when you return.')
          : ($isRtl
            ? 'استلمنا بياناتك. لن تدخل لوحة المعلم حتى تراجع الإدارة الطلب ثم تفعّل الحساب.'
            : 'We received your details. You cannot open the instructor dashboard until admin review and activation.') }}
      </p>

      @if($application)
        <div class="mc-ta-status__meta">
          <span>{{ $application->full_name }}</span>
          <span dir="ltr">{{ $application->email }}</span>
          <em>{{ $isApproved ? ($isRtl ? 'بانتظار التفعيل' : 'Awaiting activation') : ($isRtl ? 'قيد المراجعة' : 'Pending review') }}</em>
        </div>
      @endif

      <ol class="mc-ta-steps mc-ta-steps--vertical">
        <li class="is-done"><span>1</span><div><strong>{{ $isRtl ? 'تم إنشاء الحساب' : 'Account created' }}</strong><p>{{ $isRtl ? 'يمكنك تسجيل الدخول لمتابعة الحالة.' : 'You can log in to check status.' }}</p></div></li>
        <li class="{{ $isApproved ? 'is-done' : 'is-on' }}"><span>2</span><div><strong>{{ $isRtl ? 'مراجعة الإدارة' : 'Admin review' }}</strong><p>{{ $isRtl ? 'نراجع البيانات والمستندات قبل القبول.' : 'We review details and documents before approval.' }}</p></div></li>
        <li class="{{ $isApproved ? 'is-on' : '' }}"><span>3</span><div><strong>{{ $isRtl ? 'تفعيل لوحة المعلم' : 'Dashboard activation' }}</strong><p>{{ $isRtl ? 'بعد التفعيل يظهر ملفك للطلاب وتُفتح اللوحة.' : 'After activation your profile appears and the dashboard unlocks.' }}</p></div></li>
      </ol>

      <div class="mc-ta-status__actions">
        <a href="{{ route('home') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $isRtl ? 'العودة للرئيسية' : 'Back home' }}</a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="mc-btn mc-btn--md mc-btn--outline">{{ $isRtl ? 'تسجيل الخروج' : 'Log out' }}</button>
        </form>
      </div>
    </section>
  </div>
</main>

@include('partials.landing.mycourses.footer')
</body>
</html>
