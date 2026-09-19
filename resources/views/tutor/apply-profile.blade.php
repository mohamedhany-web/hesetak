@php
  $locale = app()->getLocale();
  $isRtl = $locale === 'ar';
  $brand = __('landing.nav.brand');
  $application = $application ?? null;
  $form = $form ?? null;
  $fields = $fields ?? collect();
  $oldAnswers = old('answers', []);
  $saved = is_array($application->answers ?? null) ? $application->answers : [];
  $mcCss = public_path('css/landing/mycourses.css');
  $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
  $mcActive = 'for-teachers';
  $inGroup = false;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $form->title ?? ($isRtl ? 'إكمال بيانات المعلم' : 'Complete teacher profile') }} · {{ $brand }}</title>
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

<main class="mc-ta">
  <div class="mc-container mc-ta__layout mc-ta__layout--profile">
    <aside class="mc-ta-aside" aria-labelledby="mc-ta-aside-title">
      <p class="mc-eyebrow">{{ $isRtl ? 'للمعلمين' : 'For teachers' }}</p>
      <h1 id="mc-ta-aside-title">{{ $isRtl ? 'أكمل ملفك للمراجعة' : 'Complete your profile' }}</h1>
      <p class="mc-ta-aside__lead">
        {{ $form->description ?: ($isRtl
          ? 'أدخل بياناتك الشخصية والمستندات بدقة. الإدارة تراجع الطلب قبل تفعيل لوحة المعلم.'
          : 'Enter personal details and documents carefully. Admin reviews before unlocking the instructor dashboard.') }}
      </p>
      <ol class="mc-ta-steps" aria-label="{{ $isRtl ? 'خطوات التقديم' : 'Application steps' }}">
        <li class="is-done"><span>1</span>{{ $isRtl ? 'إنشاء الحساب' : 'Create account' }}</li>
        <li class="is-on"><span>2</span>{{ $isRtl ? 'بياناتك ومستنداتك' : 'Profile & documents' }}</li>
        <li><span>3</span>{{ $isRtl ? 'مراجعة ثم تفعيل' : 'Review then activate' }}</li>
      </ol>
      @if($user ?? null)
        <div class="mc-ta-aside__account">
          <span>{{ $isRtl ? 'مسجّل كـ' : 'Signed in as' }}</span>
          <strong>{{ $user->name }}</strong>
          <em dir="ltr">{{ $user->email }}</em>
        </div>
      @endif
    </aside>

    <section class="mc-ta-main" aria-labelledby="mc-ta-form-title">
      @if(session('success'))
        <div class="mc-ta-alert is-ok" role="status">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="mc-ta-alert is-err" role="alert">{{ session('error') }}</div>
      @endif
      @error('form')
        <div class="mc-ta-alert is-err" role="alert">{{ $message }}</div>
      @enderror
      @error('intro_video')
        <div class="mc-ta-alert is-err" role="alert">{{ $message }}</div>
      @enderror
      @error('phone')
        <div class="mc-ta-alert is-err" role="alert">{{ $message }}</div>
      @enderror

      <div class="mc-ta-card">
        <header class="mc-ta-card__head">
          <p class="mc-ta-card__step">{{ $isRtl ? 'الخطوة 2 من 2' : 'Step 2 of 2' }}</p>
          <h2 id="mc-ta-form-title">{{ $form->title ?? ($isRtl ? 'بيانات المعلم' : 'Teacher details') }}</h2>
          <p>{{ $isRtl
            ? 'الحقول المطلوبة معلّمة بعلامة *. ارفع المستندات بصيغة واضحة.'
            : 'Required fields are marked *. Upload clear document files.' }}</p>
        </header>

        <form method="POST" action="{{ route('public.tutor.apply.profile.store') }}" enctype="multipart/form-data" class="mc-ta-form">
          @csrf

          @forelse($fields as $field)
            @if($field->isSection())
              @if($inGroup)
                  </div>
                </div>
                @php $inGroup = false; @endphp
              @endif
              <div class="mc-ta-section">
                <h3>{{ $field->label }}</h3>
                @if($field->help_text)
                  <p>{{ $field->help_text }}</p>
                @endif
              </div>
            @else
              @unless($inGroup)
                <div class="mc-ta-panel">
                  <div class="mc-ta-grid">
                @php $inGroup = true; @endphp
              @endunless
              @include('partials.landing.mycourses.tutor-apply-field', compact('field', 'saved', 'oldAnswers', 'application', 'isRtl'))
            @endif
          @empty
            <div class="mc-ta-alert is-info">
              {{ $isRtl
                ? 'لا توجد حقول نموذج منشورة حالياً. تواصل مع الإدارة.'
                : 'No published form fields yet. Please contact support.' }}
            </div>
          @endforelse

          @if($inGroup)
              </div>
            </div>
          @endif

          <button type="submit" class="mc-btn mc-btn--lg mc-btn--secondary mc-ta-submit">
            <i class="fas fa-paper-plane" aria-hidden="true"></i>
            {{ $isRtl ? 'إرسال للمراجعة' : 'Submit for review' }}
          </button>
          <p class="mc-ta-foot mc-ta-foot--muted">
            {{ $isRtl
              ? 'بعد الإرسال لن تُفتح لوحة المعلم إلا بعد مراجعة الإدارة وتفعيل الحساب.'
              : 'After submit, the instructor dashboard opens only after admin review and activation.' }}
          </p>
        </form>
      </div>
    </section>
  </div>
</main>

@include('partials.landing.mycourses.footer')
</body>
</html>
