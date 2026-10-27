@php
  $locale = app()->getLocale();
  $isRtl = $locale === 'ar';
  $brand = __('landing.nav.brand');
  $mcCss = public_path('css/landing/mycourses.css');
  $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
  $mcActive = 'for-teachers';
  $phoneCountries = $phoneCountries ?? config('phone_countries.countries', []);
  $defaultCountry = $defaultCountry ?? (collect($phoneCountries)->firstWhere('code', config('phone_countries.default_country', 'SA')) ?: collect($phoneCountries)->first());
  $oldDial = old('country_code', $defaultCountry['dial_code'] ?? '+966');
  $pickedCountry = collect($phoneCountries)->firstWhere('dial_code', $oldDial) ?? $defaultCountry;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $isRtl ? 'قدّم كمعلم' : 'Apply as a teacher' }} · {{ $brand }}</title>
  <meta name="description" content="{{ $isRtl ? 'أنشئ حساب معلم على حصتك ثم أكمل ملفك للمراجعة والتفعيل.' : 'Create a teacher account on Hesetak, then complete your profile for review and activation.' }}">
  <meta name="theme-color" content="#1E4E8C">
  <link rel="canonical" href="{{ route('public.tutor.apply') }}">
  @include('partials.favicon-links')
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
  @include('partials.figma-capture-head')
</head>
<body class="mc-body mc-body--ta">
@include('partials.landing.mycourses.nav')

<main class="mc-ta">
  <div class="mc-container mc-ta__layout">
    <aside class="mc-ta-aside" aria-labelledby="mc-ta-aside-title">
      <p class="mc-eyebrow">{{ $isRtl ? 'للمعلمين' : 'For teachers' }}</p>
      <h1 id="mc-ta-aside-title">{{ $isRtl ? 'انضم إلى معلمي حصتك' : 'Join Hesetak teachers' }}</h1>
      <p class="mc-ta-aside__lead">
        {{ $isRtl
          ? 'حصص فردية أونلاين مع طلاب وأولياء أمور يبحثون عن معلم معتمد. التسجيل خطوتان، واللوحة تُفتح بعد تفعيل الإدارة.'
          : 'Online 1:1 lessons with students and parents looking for trusted teachers. Two steps to apply; the dashboard opens after admin activation.' }}
      </p>
      <ol class="mc-ta-steps" aria-label="{{ $isRtl ? 'خطوات التقديم' : 'Application steps' }}">
        <li class="is-on"><span>1</span>{{ $isRtl ? 'إنشاء الحساب' : 'Create account' }}</li>
        <li><span>2</span>{{ $isRtl ? 'بياناتك ومستنداتك' : 'Profile & documents' }}</li>
        <li><span>3</span>{{ $isRtl ? 'مراجعة ثم تفعيل' : 'Review then activate' }}</li>
      </ol>
      <ul class="mc-ta-aside__trust">
        <li><i class="fas fa-user-check" aria-hidden="true"></i>{{ $isRtl ? 'اعتماد قبل الظهور للطلاب' : 'Approved before students see you' }}</li>
        <li><i class="fas fa-chalkboard-user" aria-hidden="true"></i>{{ $isRtl ? 'حصص فردية وتقارير بعد الحصة' : '1:1 sessions with post-lesson reports' }}</li>
        <li><i class="fas fa-wallet" aria-hidden="true"></i>{{ $isRtl ? 'متابعة الدخل من لوحة المعلم' : 'Track earnings from your dashboard' }}</li>
      </ul>
    </aside>

    <section class="mc-ta-main" aria-labelledby="mc-ta-form-title">
      @if(session('success'))
        <div class="mc-ta-alert is-ok" role="status">{{ session('success') }}</div>
      @endif

      <div class="mc-ta-card">
        <header class="mc-ta-card__head">
          <p class="mc-ta-card__step">{{ $isRtl ? 'الخطوة 1 من 2' : 'Step 1 of 2' }}</p>
          <h2 id="mc-ta-form-title">{{ $isRtl ? 'أنشئ حساب الدخول' : 'Create your login' }}</h2>
          <p>{{ $isRtl
            ? 'الإيميل وكلمة المرور هما بيانات دخولك لاحقاً. بعد التسجيل تكمل الملف التعريفي.'
            : 'This email and password are your future login. After signup you complete your profile.' }}</p>
        </header>

        <form method="POST" action="{{ route('public.tutor.apply.register') }}" class="mc-ta-form">
          @csrf
          <div class="mc-ta-grid">
            <div class="mc-ta-field is-wide">
              <label for="full_name">{{ $isRtl ? 'الاسم الكامل' : 'Full name' }} <span class="mc-ta-req">*</span></label>
              <input class="mc-input" id="full_name" type="text" name="full_name" value="{{ old('full_name') }}" required autocomplete="name" placeholder="{{ $isRtl ? 'كما سيظهر للطلاب' : 'As students will see it' }}">
              @error('full_name')<p class="mc-ta-err">{{ $message }}</p>@enderror
            </div>

            <div class="mc-ta-field">
              <label for="email">{{ $isRtl ? 'البريد الإلكتروني' : 'Email' }} <span class="mc-ta-req">*</span></label>
              <input class="mc-input" id="email" type="email" name="email" value="{{ old('email') }}" required dir="ltr" autocomplete="email" placeholder="name@example.com">
              @error('email')<p class="mc-ta-err">{{ $message }}</p>@enderror
            </div>

            <div class="mc-ta-field is-wide">
              <label for="phone">{{ $isRtl ? 'الجوال / واتساب' : 'Phone / WhatsApp' }} <span class="mc-ta-req">*</span></label>
              <div class="mc-ta-phone" dir="ltr" style="display:flex;gap:.5rem;align-items:stretch">
                <select class="mc-select" id="country_code" name="country_code" required aria-label="{{ $isRtl ? 'كود الدولة' : 'Country code' }}" style="max-width:11rem;flex:0 0 auto">
                  @foreach($phoneCountries as $country)
                    <option
                      value="{{ $country['dial_code'] }}"
                      data-placeholder="{{ $country['placeholder'] ?? '' }}"
                      @selected(($pickedCountry['dial_code'] ?? '') === ($country['dial_code'] ?? ''))
                    >{{ $country['dial_code'] }} · {{ $isRtl ? ($country['name_ar'] ?? $country['name_en'] ?? '') : ($country['name_en'] ?? $country['name_ar'] ?? '') }}</option>
                  @endforeach
                </select>
                <input class="mc-input" id="phone" type="tel" name="phone" value="{{ old('phone') }}" required dir="ltr" autocomplete="tel-national" inputmode="numeric" maxlength="15" placeholder="{{ $pickedCountry['placeholder'] ?? '5xxxxxxxx' }}" style="flex:1">
              </div>
              <p class="mc-ta-help">{{ $isRtl ? 'اختر كود الدولة ثم أدخل الرقم المحلي بدون كود الدولة.' : 'Pick a country code, then enter the national number without the dial code.' }}</p>
              @error('country_code')<p class="mc-ta-err">{{ $message }}</p>@enderror
              @error('phone')<p class="mc-ta-err">{{ $message }}</p>@enderror
            </div>

            <div class="mc-ta-field">
              <label for="password">{{ $isRtl ? 'كلمة المرور' : 'Password' }} <span class="mc-ta-req">*</span></label>
              <input class="mc-input" id="password" type="password" name="password" required minlength="8" maxlength="72" pattern="(?=.*[A-Za-z\u0600-\u06FF])(?=.*\d).{8,}" autocomplete="new-password" title="{{ __('auth.password_rules_hint') }}">
              <p class="mc-ta-help">{{ __('auth.password_rules_hint') }}</p>
              @error('password')<p class="mc-ta-err">{{ $message }}</p>@enderror
            </div>

            <div class="mc-ta-field">
              <label for="password_confirmation">{{ $isRtl ? 'تأكيد كلمة المرور' : 'Confirm password' }} <span class="mc-ta-req">*</span></label>
              <input class="mc-input" id="password_confirmation" type="password" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password">
            </div>
          </div>

          <button type="submit" class="mc-btn mc-btn--lg mc-btn--secondary mc-ta-submit">
            {{ $isRtl ? 'إنشاء الحساب والمتابعة' : 'Create account & continue' }}
          </button>

          <p class="mc-ta-foot">
            {{ $isRtl ? 'لديك حساب معلم؟' : 'Already have a teacher account?' }}
            <a href="{{ route('login', ['redirect' => route('public.tutor.apply.profile')]) }}">{{ $isRtl ? 'تسجيل الدخول' : 'Log in' }}</a>
          </p>
        </form>
      </div>
    </section>
  </div>
</main>

@include('partials.landing.mycourses.footer')
</body>
</html>
