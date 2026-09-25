@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $brand = config('app.name', 'حصتك');
    $name = $profile->user->name ?? __('public.instructor_fallback');
    $headline = $profile->headline_clean ?: __('public.instructor_fallback');
    $bioClean = $profile->bio_clean;
    $skills = $profile->skills_list ?? [];
    $experiences = $profile->experience_list ?? [];
    $instrPageTitle = $headline !== '' && $headline !== $name
        ? $name.' | '.$headline.' | '.$brand
        : $name.' | '.$brand;
    $instrPageDesc = \Illuminate\Support\Str::limit($bioClean ?: $headline, 160);
    $instrPageImg = ($profile->photo_url ?? null) ?: asset('images/og-image.jpg');
    $instrPageUrl = route('public.instructors.show', $profile->user);
    $weeklyCalendar = $weeklyCalendar ?? [];
    $canBook = (bool) ($canBook ?? false);
    $hasActivePackage = (bool) ($hasActivePackage ?? false);
    $unitsLeft = (int) ($unitsLeft ?? 0);
    $bookableSlots = $bookableSlots ?? collect();
    $packagesUrl = $packagesUrl ?? route('public.pricing');
    $introEmbedUrl = $introEmbedUrl ?? null;
    $introDirectVideo = $introDirectVideo ?? null;
    $hasIntroVideo = filled($introEmbedUrl) || filled($introDirectVideo);
    $oneToOneCourses = $oneToOneCourses ?? collect();
    $privateGroups = $privateGroups ?? collect();
    $groupCourses = $groupCourses ?? collect();
    $allOfferings = collect()
        ->merge($privateGroups)
        ->merge($oneToOneCourses)
        ->merge($groupCourses);
    $skillChips = [];
    $skillNotes = [];
    foreach ($skills as $skill) {
        $skill = trim((string) $skill);
        if ($skill === '') {
            continue;
        }
        if (mb_strlen($skill) > 42 || substr_count($skill, ' ') > 6) {
            $skillNotes[] = $skill;
        } else {
            $skillChips[] = $skill;
        }
    }
    $experienceSummary = count($experiences) > 0
        ? $experiences[0]
        : trim((string) ($profile->experience ?? ''));
    $heroPills = array_values(array_filter(array_unique(array_merge(
        ['1:1'],
        array_slice($skillChips, 0, 5)
    ))));
    $teachingYears = $teachingYears ?? collect();
    $curriculumTypeLabels = $curriculumTypeLabels ?? [];
    $trustItems = array_values(array_filter([
        [
            'num' => '1:1',
            'label' => $isRtl ? 'حصة فردية · معتمد' : 'Private · Verified',
        ],
        filled($experienceSummary) ? [
            'num' => $isRtl ? 'خبرة' : 'Exp.',
            'label' => \Illuminate\Support\Str::limit($experienceSummary, 36),
        ] : null,
        count($skillChips) > 0 ? [
            'num' => (string) count($skillChips),
            'label' => $isRtl ? 'تخصصات' : 'specialties',
        ] : null,
        count($curriculumTypeLabels) > 0 ? [
            'num' => (string) count($curriculumTypeLabels),
            'label' => $isRtl ? 'أنواع منهج' : 'curricula',
        ] : null,
    ]));
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $tpCss = public_path('css/landing/instructor-profile.css');
    $tpVer = is_file($tpCss) ? (string) filemtime($tpCss) : (string) time();
    $mcActive = 'instructors';
    $langSwitch = fn (string $lang) => request()->fullUrlWithQuery(array_merge(request()->query(), ['lang' => $lang]));
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $instrPageTitle }}</title>
  <meta name="description" content="{{ $instrPageDesc }}">
  <meta name="theme-color" content="#1E4E8C">
  <link rel="canonical" href="{{ $instrPageUrl }}">
  <meta property="og:type" content="profile">
  <meta property="og:url" content="{{ $instrPageUrl }}">
  <meta property="og:title" content="{{ $instrPageTitle }}">
  <meta property="og:description" content="{{ $instrPageDesc }}">
  <meta property="og:image" content="{{ $instrPageImg }}">
  <meta property="og:site_name" content="{{ $brand }}">
  @include('partials.favicon-links')
  @include('partials.seo-jsonld', ['jsonldType' => 'instructor', 'profile' => $profile])
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'instructor-profile']) }}?v={{ $tpVer }}">
  @include('partials.figma-capture-head')
</head>
<body class="mc-body mc-body--teacher-profile">
@include('partials.landing.mycourses.nav')

<main>
  {{-- Hero — contained profile (not homepage bleed) --}}
  <section class="mc-hero mc-tp-hero" aria-labelledby="mc-tp-title">
    <div class="mc-container">
      <nav class="mc-tp-crumb" aria-label="{{ $isRtl ? 'مسار التنقل' : 'Breadcrumb' }}">
        <a href="{{ url('/') }}">{{ $isRtl ? 'الرئيسية' : 'Home' }}</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('public.instructors.index') }}">{{ __('landing.nav.instructors') }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ $name }}</span>
      </nav>
    </div>
    <div class="mc-container mc-tp-hero__grid">
      <div class="mc-hero__copy mc-tp-hero__copy">
        <p class="mc-eyebrow">{{ __('public.instructors_verified') }} <strong class="mc-brand-word">{{ $brand }}</strong></p>
        <h1 class="mc-hero__title" id="mc-tp-title">{{ $name }}</h1>
        @if($headline !== '' && $headline !== $name)
          <p class="mc-hero__lead">{{ $headline }}</p>
        @else
          <p class="mc-hero__lead">{{ $isRtl ? 'احجز حصة فردية أونلاين مع معلم معتمد على حصتك.' : 'Book a private online session with an approved Hesetak teacher.' }}</p>
        @endif

        @if(count($heroPills) > 0)
          <div class="mc-cats mc-tp-hero__pills">
            @foreach($heroPills as $pill)
              <span class="mc-cat"><span class="mc-cat__dot" aria-hidden="true"></span>{{ $pill }}</span>
            @endforeach
          </div>
        @endif

        <div class="mc-hero__actions">
          <a href="#mc-tp-book" class="mc-btn mc-btn--lg mc-btn--secondary">{{ $isRtl ? 'احجز حصة مع هذا المعلم' : 'Book with this teacher' }}</a>
          <a href="{{ $packagesUrl }}" class="mc-btn mc-btn--lg mc-btn--soft">{{ $isRtl ? 'اشترك في باقة' : 'Get a package' }}</a>
          @if($hasIntroVideo)
            <button type="button" class="mc-btn mc-btn--lg mc-btn--outline" id="mcTpIntroOpen" aria-haspopup="dialog" aria-controls="mcTpIntroModal">
              <i class="fas fa-play" aria-hidden="true"></i>
              {{ $isRtl ? 'فيديو تعريفي' : 'Intro video' }}
            </button>
          @endif
        </div>
      </div>

      <figure class="mc-tp-hero__media">
        @if($profile->photo_url)
          <img src="{{ $profile->photo_url }}" width="640" height="640" alt="{{ $name }}" loading="eager" decoding="async">
        @else
          <div class="mc-tp-photo-fallback" aria-hidden="true">{{ mb_substr($name, 0, 1) }}</div>
        @endif
      </figure>
    </div>
  </section>

  {{-- Trust strip — same component as homepage stats --}}
  <section class="mc-trust" aria-label="{{ $isRtl ? 'لمحة سريعة' : 'Quick facts' }}">
    <div class="mc-container">
      <ul class="mc-trust__list" style="--mc-tp-trust-cols: {{ max(2, count($trustItems)) }}">
        @foreach($trustItems as $stat)
          <li class="mc-trust__item">
            <span class="mc-trust__num">{{ $stat['num'] }}</span>
            <span class="mc-trust__label">{{ $stat['label'] }}</span>
          </li>
        @endforeach
      </ul>
    </div>
  </section>

  @if($errors->any())
    <div class="mc-container" style="padding-top:1rem">
      <div class="mc-tp-alert is-err" role="alert">{{ $errors->first() }}</div>
    </div>
  @endif
  @if(session('success'))
    <div class="mc-container" style="padding-top:1rem">
      <div class="mc-tp-alert is-ok" role="status">{{ session('success') }}</div>
    </div>
  @endif

  {{-- About + booking — homepage section rhythm --}}
  <section class="mc-section mc-section--compact" id="about">
    <div class="mc-container mc-tp-layout">
      <div class="mc-tp-main">
        <div class="mc-section-head">
          <div>
            <p class="mc-eyebrow">{{ __('public.instructor_bio_title') }}</p>
            <h2 class="mc-title">{{ $isRtl ? 'تعرّف على أسلوب المعلم' : 'About this teacher' }}</h2>
            <p class="mc-lead">{{ $isRtl ? 'نبذة واضحة لولي الأمر قبل الحجز.' : 'A clear intro for parents before booking.' }}</p>
          </div>
        </div>
        <p class="mc-tp-text">{{ $bioClean ?: ($isRtl ? 'لا توجد نبذة منشورة بعد.' : 'No published bio yet.') }}</p>

        @if(count($curriculumTypeLabels) > 0 || $teachingYears->isNotEmpty())
          <div class="mc-tp-block">
            <p class="mc-eyebrow">{{ $isRtl ? 'المناهج والمراحل' : 'Curricula & stages' }}</p>
            <h3 class="mc-tp-sub">{{ $isRtl ? 'ماذا يغطّي هذا المعلم؟' : 'What this teacher covers' }}</h3>
            @if(count($curriculumTypeLabels) > 0)
              <div class="mc-cats" style="margin-top:0.75rem">
                @foreach($curriculumTypeLabels as $label)
                  <span class="mc-cat"><span class="mc-cat__dot" aria-hidden="true"></span>{{ $label }}</span>
                @endforeach
              </div>
            @endif
            @if($teachingYears->isNotEmpty())
              <div class="mc-dir-chips" style="margin-top:0.85rem">
                @foreach($teachingYears as $year)
                  <a class="mc-dir-chip" href="{{ route('public.curricula.show', $year) }}"><em>{{ $year->name }}</em></a>
                @endforeach
              </div>
            @endif
          </div>
        @endif

        @if($hasIntroVideo)
          <div class="mc-tp-block">
            <p class="mc-eyebrow">{{ $isRtl ? 'فيديو تعريفي' : 'Intro video' }}</p>
            <h3 class="mc-tp-sub">{{ $isRtl ? 'تعرّف على أسلوب الشرح قبل الحجز' : 'See their teaching style before booking' }}</h3>
            <button type="button" class="mc-btn mc-btn--md mc-btn--outline" id="mcTpIntroOpenInline" aria-haspopup="dialog" aria-controls="mcTpIntroModal">
              <i class="fas fa-play" aria-hidden="true"></i>
              {{ $isRtl ? 'تشغيل الفيديو التعريفي' : 'Play intro video' }}
            </button>
          </div>
        @endif

        @if(count($experiences) > 0 || filled($profile->experience) || count($skillChips) > 0 || count($skillNotes) > 0)
          <div class="mc-tp-block">
            <p class="mc-eyebrow">{{ $isRtl ? 'المؤهلات والمهارات' : 'Qualifications & skills' }}</p>
            <h3 class="mc-tp-sub">{{ $isRtl ? 'ماذا يقدّم للطالب؟' : 'What they bring to the student' }}</h3>

            @if(count($experiences) > 0 || filled($profile->experience))
              <ul class="mc-tp-list">
                @if(count($experiences) > 0)
                  @foreach($experiences as $item)
                    <li>{{ $item }}</li>
                  @endforeach
                @else
                  <li>{{ $profile->sanitizedText($profile->experience) }}</li>
                @endif
              </ul>
            @endif

            @if(count($skillChips) > 0)
              <div class="mc-cats" style="margin-top:1rem">
                @foreach($skillChips as $skill)
                  <a class="mc-cat" href="{{ route('public.instructors.index', ['skill' => $skill]) }}"><span class="mc-cat__dot" aria-hidden="true"></span>{{ $skill }}</a>
                @endforeach
              </div>
            @endif

            @if(count($skillNotes) > 0)
              <ul class="mc-tp-list" style="margin-top:1rem">
                @foreach($skillNotes as $note)
                  <li>{{ $note }}</li>
                @endforeach
              </ul>
            @endif
          </div>
        @endif
      </div>

      <aside class="mc-tp-aside" id="mc-tp-book">
        <article class="mc-package mc-package--recommended mc-tp-book">
          <span class="mc-package__badge">{{ $isRtl ? 'الخطوة التالية' : 'Next step' }}</span>
          <h3>{{ __('public.instructor_availability_title') }}</h3>
          <p class="mc-package__why">{{ $isRtl ? 'اشترك في باقة ثم احجز حصة تجريبية مجانية أو ثبّت جدولك من الرصيد.' : 'Subscribe to a package, then book a free trial or lock sessions from your credits.' }}</p>

          @if(!empty($weeklyCalendar))
            <div class="mc-tp-cal">
              @foreach($weeklyCalendar as $col)
                <div class="mc-tp-cal__day">
                  <span class="mc-tp-cal__label">{{ $col['label'] }}</span>
                  <div class="mc-tp-cal__times">
                    @foreach($col['times'] as $t)
                      <span>{{ $t }}</span>
                    @endforeach
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <p class="mc-tp-text mc-tp-text--muted">{{ $isRtl ? 'لم يُحدَّد جدول توافر بعد.' : 'No weekly availability published yet.' }}</p>
          @endif

          @if($hasActivePackage)
            @php
              $viewerTz = \App\Support\AppTimezone::forUser(auth()->user());
            @endphp
            @include('partials.landing.mycourses.instructor-free-session-form', [
                'profile' => $profile,
                'bookableSlots' => $bookableSlots,
                'viewerTz' => $viewerTz,
                'isRtl' => $isRtl,
            ])
          @endif

          @unless($canBook)
            @unless($hasActivePackage)
              <p class="mc-tp-alert">{{ $isRtl ? 'بعد الاشتراك في باقة: تحجز حصة تجريبية مجانية مع هذا المعلم، ثم تثبّت جدولك من الرصيد.' : 'After you subscribe: book a free trial with this teacher, then lock your schedule from credits.' }}</p>
              <div class="mc-tp-actions">
                <a href="{{ $packagesUrl }}" class="mc-btn mc-btn--md mc-btn--secondary">{{ $isRtl ? 'اشترك في باقة للحجز' : 'Subscribe to book' }}</a>
                @guest
                  <a href="{{ route('login', ['redirect' => $instrPageUrl]) }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $isRtl ? 'تسجيل الدخول' : 'Log in' }}</a>
                @endguest
                <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ __('public.all_instructors_link') }}</a>
              </div>
            @else
              <p class="mc-tp-alert">{{ $isRtl ? 'رصيد الباقة مستهلك حالياً — يمكنك حجز الحصة التجريبية المجانية أعلاه، أو تجديد الباقة لتثبيت مواعيد إضافية.' : 'Package credits are used up — you can still book the free trial above, or renew to lock more sessions.' }}</p>
              <div class="mc-tp-actions">
                <a href="{{ $packagesUrl }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $isRtl ? 'تجديد الباقة' : 'Renew package' }}</a>
              </div>
            @endunless
          @else
            <p class="mc-tp-alert is-ok">{{ $isRtl ? ('رصيدك المتاح: '.$unitsLeft.' حصة') : ('Available credits: '.$unitsLeft) }}</p>
            @if($bookableSlots->isNotEmpty())
              @php
                $clockTz = \App\Support\AppTimezone::forUser($profile->user);
                $viewerTz = \App\Support\AppTimezone::forUser(auth()->user());
                $weeklyOpts = $bookableSlots
                    ->map(function ($slot) use ($clockTz, $viewerTz) {
                        $starts = is_array($slot) ? ($slot['starts_at'] ?? null) : ($slot->starts_at ?? null);
                        if (! $starts instanceof \Carbon\Carbon) {
                            return null;
                        }
                        $clock = $starts->copy()->timezone($clockTz);
                        $viewer = $starts->copy()->timezone($viewerTz);

                        return [
                            'day' => (int) $clock->dayOfWeekIso,
                            'time' => $clock->format('H:i'),
                            'label' => $viewer->locale(app()->getLocale())->translatedFormat('l، g:i A'),
                        ];
                    })
                    ->filter()
                    ->unique(fn ($r) => $r['day'].'|'.$r['time'])
                    ->values();
              @endphp
              <div class="mc-tp-paid">
                <h4 class="mc-tp-paid__title">{{ $isRtl ? 'تثبيت مواعيد من رصيد الباقة' : 'Lock sessions from package credits' }}</h4>
                @include('partials.landing.mycourses.instructor-booking-form', [
                    'profile' => $profile,
                    'bookableSlots' => $bookableSlots,
                    'weeklyOpts' => $weeklyOpts,
                    'viewerTz' => $viewerTz,
                    'isRtl' => $isRtl,
                ])
              </div>
            @else
              <p class="mc-tp-text mc-tp-text--muted">{{ $isRtl ? 'لا توجد مواعيد مفتوحة خلال الأسابيع القادمة.' : 'No open slots in the coming weeks.' }}</p>
            @endif
          @endunless
        </article>
      </aside>
    </div>
  </section>

  @if($allOfferings->isNotEmpty())
    <section class="mc-section mc-section--muted mc-section--compact" id="offerings">
      <div class="mc-container">
        <div class="mc-section-head">
          <div>
            <p class="mc-eyebrow">{{ $isRtl ? 'مع هذا المعلم' : 'With this teacher' }}</p>
            <h2 class="mc-title">{{ $isRtl ? 'كورسات وعروض مرتبطة' : 'Linked courses & offers' }}</h2>
            <p class="mc-lead">{{ $isRtl ? 'اختر المسار المناسب بعد الحجز أو معه.' : 'Pick a related path alongside your booking.' }}</p>
          </div>
        </div>
        <div class="mc-tracks">
          @foreach($privateGroups as $group)
            <a href="#mc-tp-book" class="mc-track mc-track--featured">
              <span class="mc-track__badge">1:1</span>
              <span class="mc-track__icon" aria-hidden="true"><i class="fas fa-user"></i></span>
              <h3>{{ $group->title }}</h3>
              <p>{{ (int) $group->duration_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}، {{ $group->formattedPrice() }}</p>
              <span class="mc-track__cta">{{ $isRtl ? 'احجز حصة' : 'Book a session' }} →</span>
            </a>
          @endforeach
          @foreach($oneToOneCourses as $course)
            <a href="{{ route('public.course.show', $course->id) }}" class="mc-track">
              <span class="mc-track__icon" aria-hidden="true"><i class="fas fa-chalkboard"></i></span>
              <h3>{{ $course->title }}</h3>
              <p>
                {{ (int) ($course->lessons_count ?? 0) }} {{ $isRtl ? 'درس' : 'lessons' }}
                    @if(!empty($course->price))
                      ، {{ number_format((float) $course->price) }} {{ currency_symbol() }}
                    @endif
              </p>
              <span class="mc-track__cta">{{ $isRtl ? 'عرض الكورس' : 'View course' }} →</span>
            </a>
          @endforeach
          @foreach($groupCourses as $course)
            <a href="{{ route('public.course.show', $course->id) }}" class="mc-track">
              <span class="mc-track__icon" aria-hidden="true"><i class="fas fa-book-open"></i></span>
              <h3>{{ $course->title }}</h3>
              <p>{{ (int) ($course->lessons_count ?? 0) }} {{ $isRtl ? 'درس' : 'lessons' }}</p>
              <span class="mc-track__cta">{{ $isRtl ? 'عرض الكورس' : 'View course' }} →</span>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <section class="mc-section mc-section--compact">
    <div class="mc-container">
      <div class="mc-cta">
        <div>
          <h2>{{ $isRtl ? 'جاهز تبدأ مع '.$name.'؟' : 'Ready to start with '.$name.'?' }}</h2>
          <p>{{ $isRtl ? 'اشترك في باقة ثم احجز موعدك، أو تصفّح معلمين آخرين.' : 'Subscribe to a package then book, or browse more teachers.' }}</p>
        </div>
        <div class="mc-cta__actions">
          <a href="#mc-tp-book" class="mc-btn mc-btn--lg mc-btn--secondary">{{ $isRtl ? 'احجز الآن' : 'Book now' }}</a>
          <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ __('public.all_instructors_link') }}</a>
        </div>
      </div>
    </div>
  </section>
</main>

@if($hasIntroVideo)
  <div class="mc-tp-modal" id="mcTpIntroModal" hidden>
    <div class="mc-tp-modal__backdrop" data-close-intro></div>
    <div class="mc-tp-modal__dialog" role="dialog" aria-modal="true" aria-label="{{ $isRtl ? 'فيديو تعريفي' : 'Intro video' }}">
      <button type="button" class="mc-tp-modal__close" data-close-intro aria-label="{{ $isRtl ? 'إغلاق' : 'Close' }}"><i class="fas fa-times"></i></button>
      <div class="mc-tp-video">
        @if($introEmbedUrl)
          <iframe src="{{ $introEmbedUrl }}" title="{{ $name }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
        @elseif($introDirectVideo)
          <video controls playsinline preload="metadata" poster="{{ $profile->photo_url }}">
            <source src="{{ $introDirectVideo }}">
          </video>
        @endif
      </div>
    </div>
  </div>
  <script>
  (function () {
    var openBtn = document.getElementById('mcTpIntroOpen');
    var modal = document.getElementById('mcTpIntroModal');
    if (!openBtn || !modal) return;
    function open() { modal.hidden = false; document.body.style.overflow = 'hidden'; }
    function close() {
      modal.hidden = true;
      document.body.style.overflow = '';
      var v = modal.querySelector('video');
      if (v) { try { v.pause(); } catch (e) {} }
    }
    openBtn.addEventListener('click', open);
    document.getElementById('mcTpIntroOpenInline')?.addEventListener('click', open);
    modal.querySelectorAll('[data-close-intro]').forEach(function (el) { el.addEventListener('click', close); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.hidden) close();
    });
  })();
  </script>
@endif

@include('partials.landing.mycourses.footer')
</body>
</html>
