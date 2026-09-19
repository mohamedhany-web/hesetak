@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $g = 'landing.groups_page';
    $brand = config('app.name', 'حصتك');
    $trialUrl = route('home').'?open_trial=1';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ $year->name }} — {{ $brand }}</title>
  <meta name="description" content="{{ $year->tagline ?: $year->description }}">
  <meta name="theme-color" content="#0B3D91">
  <link rel="canonical" href="{{ route('public.school.year', $year->slug) }}">
  @include('partials.favicon-links')
  @include('partials.landing.head', ['landingCss' => ['theme']])
  <style>
    .gl-sy { background: var(--bg, #F4F7FC); }
    .gl-sy-hero {
      padding: clamp(96px, 12vw, 120px) 0 48px;
      background: linear-gradient(175deg, #051F4D 0%, #072A66 42%, #0B3D91 100%);
      color: #fff;
    }
    .gl-sy-hero h1 {
      margin: 8px 0 10px;
      font-family: Cairo, Tajawal, sans-serif;
      font-size: clamp(1.6rem, 3.5vw, 2.3rem);
      font-weight: 900;
      color: #F5B800;
    }
    .gl-sy-hero p { margin: 0; max-width: 46ch; line-height: 1.75; color: rgba(255,255,255,.88); font-weight: 600; }
    .gl-sy-body { padding: clamp(32px, 5vw, 56px) 0; }
    .gl-sy-grid { display: grid; gap: 14px; }
    @media (min-width: 768px) { .gl-sy-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1100px) { .gl-sy-grid { grid-template-columns: repeat(3, 1fr); } }
    .gl-sy-card {
      background: #fff; border: 1.5px solid #D7DDE6; border-radius: 16px; padding: 1.15rem;
      box-shadow: 0 10px 28px -22px rgba(11,61,145,.3);
      height: 100%;
      display: flex; flex-direction: column;
    }
    .gl-sy-card h2 { margin: 0 0 .35rem; font-size: 1.05rem; font-weight: 900; color: #0B1220; }
    .gl-sy-card .meta { margin: 0 0 .85rem; font-size: .8rem; color: #5B6577; font-weight: 600; }
    .gl-sy-card .actions { margin-top: auto; display:flex; flex-wrap:wrap; gap:8px; }
    .gl-sy-cohorts { list-style: none; margin: 0 0 1rem; padding: 0; display: grid; gap: 8px; }
    .gl-sy-cohorts li {
      display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px;
      padding: 10px 12px; border-radius: 12px; background: #F4F7FC; border: 1px solid #E4E9F2;
      font-size: .8rem; font-weight: 700; color: #0B1220;
    }
    .gl-sy-empty {
      text-align: center; padding: 2rem 1rem; border-radius: 16px; background: #fff;
      border: 1.5px dashed #D7DDE6; color: #5B6577; font-weight: 700;
    }
    .gl-sy-hero .sana-container { max-width: 1200px; }
    .gl-sy-body .sana-container { max-width: 1200px; }
  </style>
</head>
<body class="sana-home gl-sy">
@include('partials.landing.navbar', ['navActive' => 'groups', 'navSolid' => false, 'navHero' => true])
<main>
  <section class="gl-sy-hero">
    <div class="sana-container">
      <p style="margin:0 0 8px;font-size:.8rem;font-weight:700;opacity:.9">
        <a href="{{ route('public.groups') }}" style="color:#fff;text-decoration:none">{{ __('landing.nav.groups') }}</a>
        · {{ $isRtl ? 'السنة الدراسية' : 'School year' }} {{ $year->level_number }}
      </p>
      <h1>{{ $year->name }}</h1>
      <p>{{ $year->description ?: $year->tagline }}</p>
      <div style="margin-top:20px;display:flex;flex-wrap:wrap;gap:10px">
        <a href="{{ $trialUrl }}" class="sana-btn sana-btn--yellow"><i class="fas fa-clipboard-check"></i> {{ __($g.'.years_cta') }}</a>
        <a href="{{ route('public.groups') }}#years" class="sana-btn sana-btn--white-outline">{{ __($g.'.cta_secondary') }}</a>
      </div>
    </div>
  </section>

  <section class="gl-sy-body">
    <div class="sana-container">
      <h2 style="margin:0 0 16px;font-family:Cairo,Tajawal,sans-serif;font-weight:900;font-size:1.25rem;color:#0B1220">
        {{ $isRtl ? 'الفصول المتاحة لهذه السنة' : 'Available classes for this year' }}
      </h2>

      @if ($classes->isEmpty())
        <div class="gl-sy-empty">
          <p style="margin:0 0 12px">{{ $isRtl ? 'لا توجد فصول مفتوحة لهذه السنة حالياً. ابدأ باختبار تحديد المستوى وسنوجّهك.' : 'No open classes for this year yet. Start with a free placement test.' }}</p>
          <a href="{{ $trialUrl }}" class="sana-btn sana-btn--yellow">{{ __($g.'.cta_trial') }}</a>
        </div>
      @else
        <div class="gl-sy-grid">
          @foreach ($classes as $class)
            <article class="gl-sy-card">
              <h2>{{ $class->title }}</h2>
              <p class="meta">
                @if($class->instructor) {{ $isRtl ? 'المعلم:' : 'Teacher:' }} {{ $class->instructor->name }} · @endif
                @if($class->schoolSubject) {{ $class->schoolSubject->name }} · @endif
                {{ (int) $class->duration_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}
              </p>
              @php $openCohorts = $class->cohorts->filter(fn ($c) => $c->isEnrollmentOpen()); @endphp
              @if ($openCohorts->isNotEmpty())
                <ul class="gl-sy-cohorts">
                  @foreach ($openCohorts->take(4) as $cohort)
                    <li>
                      <span>{{ $cohort->title }}</span>
                      <span>{{ $cohort->seatsLeft() }} {{ $isRtl ? 'مقعد' : 'seats' }}</span>
                    </li>
                  @endforeach
                </ul>
              @endif
              <div class="actions">
                <a href="{{ route('public.groups.show', $class->slug) }}" class="sana-btn sana-btn--yellow" style="padding:.55rem 1rem;font-size:.82rem">
                  {{ __($g.'.details_cta') }}
                </a>
                @if ($openCohorts->isNotEmpty())
                  <a href="{{ route('public.groups.show', $class->slug) }}" class="sana-btn sana-btn--white-outline" style="padding:.55rem 1rem;font-size:.82rem;border-color:#0B3D91;color:#0B3D91">
                    {{ __($g.'.book_cta') }}
                  </a>
                @endif
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  @php $servicePackages = $servicePackages ?? collect(); @endphp
  @if($servicePackages->isNotEmpty())
    <section class="gl-sy-body" style="padding-top:0">
      <div class="sana-container">
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:end;margin-bottom:16px">
          <div>
            <h2 style="margin:0 0 6px;font-family:Cairo,Tajawal,sans-serif;font-weight:900;font-size:1.25rem;color:#0B1220">
              {{ $isRtl ? 'باقات حصص هذه السنة' : 'Session packages for this year' }}
            </h2>
            <p style="margin:0;color:#5B6577;font-size:.85rem;font-weight:600">
              {{ $isRtl ? 'اشترِ رصيداً ثم احجز أي فصل مناسب من هذه السنة.' : 'Buy credits, then book any matching class in this year.' }}
            </p>
          </div>
          <a href="{{ route('public.service-packages.index', ['year' => $year->id, 'scope' => 'tutoring_collective']) }}" class="sana-btn sana-btn--white-outline" style="border-color:#0B3D91;color:#0B3D91;padding:.55rem 1rem;font-size:.82rem">
            {{ $isRtl ? 'كل باقات السنة' : 'All year packages' }}
          </a>
        </div>
        <div class="gl-sy-grid">
          @foreach($servicePackages->take(6) as $package)
            <article class="gl-sy-card">
              <h2>{{ $package->name }}</h2>
              <p class="meta">
                @if($package->isCommercialPlan())
                  {{ $package->planLabel() }} · {{ $package->termLabel() }}
                  · {{ $package->weeklySessionsTotal() }} {{ $isRtl ? 'أسبوعيًا' : '/week' }}
                @else
                  {{ $package->units_count }} {{ $isRtl ? 'حصة' : 'sessions' }}
                  · {{ $package->sessionMinutes() }} {{ $isRtl ? 'دقيقة' : 'min' }}
                  · {{ $package->label() }}
                @endif
                @if($package->academicSubject)
                  · {{ $package->academicSubject->name }}
                @endif
              </p>
              @if($package->savingsVsMonthlyLabel())
                <p style="margin:0 0 .5rem;font-size:.75rem;font-weight:800;color:#047857">{{ $package->savingsVsMonthlyLabel() }}</p>
              @endif
              <p style="margin:0 0 1rem;font-size:1.25rem;font-weight:900;color:#0B3D91">{{ $package->formattedPrice() }}</p>
              <div class="actions">
                <a href="{{ route('public.service-packages.checkout', $package) }}" class="sana-btn sana-btn--yellow" style="padding:.55rem 1rem;font-size:.82rem">
                  {{ $isRtl ? 'اشترِ الباقة' : 'Buy package' }}
                </a>
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </section>
  @else
    <section class="gl-sy-body" style="padding-top:0">
      <div class="sana-container">
        <div class="gl-sy-empty">
          <p style="margin:0 0 12px">{{ $isRtl ? 'لا توجد باقات مربوطة بهذه السنة بعد. يمكنك تصفح الباقات العامة أو تخصيص باقتك.' : 'No packages linked to this year yet. Browse general packages or build your own.' }}</p>
          <a href="{{ route('public.service-packages.index', ['year' => $year->id]) }}" class="sana-btn sana-btn--yellow">{{ $isRtl ? 'باقات الحصص' : 'Session packages' }}</a>
        </div>
      </div>
    </section>
  @endif
</main>
@include('partials.landing.footer')
</body>
</html>
