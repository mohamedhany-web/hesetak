@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $g = 'landing.groups_page';
    $brand = config('app.name', 'حصتك');
    $trialUrl = route('home').'?open_trial=1';
    $schoolYears = $schoolYears ?? collect();
    $schoolSubjects = $schoolSubjects ?? collect();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ __($g.'.meta_title') }} — {{ $brand }}</title>
  <meta name="description" content="{{ __($g.'.meta_desc') }}">
  <meta name="theme-color" content="#0B3D91">
  <link rel="canonical" href="{{ route('public.groups') }}">
  @include('partials.favicon-links')
  @include('partials.landing.head', ['landingCss' => ['theme']])
  <style>
    .gl-sch{background:var(--bg,#F4F7FC)}
    .gl-sch-hero{
      padding:clamp(96px,12vw,120px) 0 clamp(36px,5vw,52px);
      background:linear-gradient(175deg,#051F4D 0%,#072A66 42%,#0B3D91 100%);
      color:#fff;
    }
    .gl-sch-hero__inner{max-width:40rem}
    .gl-sch-kicker{
      display:inline-flex;align-items:center;gap:8px;margin:0 0 14px;padding:7px 14px;border-radius:999px;
      background:rgba(7,24,58,.55);border:1px solid rgba(255,255,255,.14);font:700 .78rem Tajawal,sans-serif;
    }
    .gl-sch-kicker i{color:#F5B800}
    .gl-sch-hero h1{
      margin:0 0 12px;font:900 clamp(1.55rem,3.8vw,2.35rem)/1.28 Cairo,Tajawal,sans-serif;color:#F5B800;
    }
    .gl-sch-hero p{margin:0 0 20px;font:600 .95rem/1.75 Tajawal,sans-serif;color:rgba(255,255,255,.9)}
    .gl-sch-actions{display:flex;flex-wrap:wrap;gap:10px}
    .gl-sch-sec{padding:clamp(36px,5vw,56px) 0}
    .gl-sch-sec--white{background:#fff}
    .gl-sch-head{margin:0 0 1.15rem}
    .gl-sch-head h2{margin:0 0 .35rem;font:900 clamp(1.2rem,2.6vw,1.55rem)/1.3 Cairo,Tajawal,sans-serif;color:#0B1220}
    .gl-sch-head p{margin:0;font:600 .88rem/1.6 Tajawal,sans-serif;color:#5B6577;max-width:36rem}
    .gl-sch-flow{display:grid;gap:10px;margin-bottom:1.5rem}
    @media(min-width:768px){.gl-sch-flow{grid-template-columns:repeat(3,1fr)}}
    .gl-sch-flow__item{
      background:#fff;border:1.5px solid #D7DDE6;border-radius:16px;padding:1rem 1.05rem;
      box-shadow:0 10px 28px -22px rgba(11,61,145,.28);
    }
    .gl-sch-flow__n{
      display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:28px;padding:0 8px;
      border-radius:999px;background:#0B3D91;color:#fff;font:900 .72rem Tajawal,sans-serif;margin-bottom:.55rem;
    }
    .gl-sch-flow__item h3{margin:0 0 .25rem;font:900 .9rem Tajawal,sans-serif;color:#0B1220}
    .gl-sch-flow__item p{margin:0;font:600 .78rem/1.55 Tajawal,sans-serif;color:#5B6577}
    .gl-sch-years{display:grid;gap:12px;grid-template-columns:repeat(2,1fr)}
    @media(min-width:768px){.gl-sch-years{grid-template-columns:repeat(3,1fr)}}
    .gl-sch-year{
      display:block;text-decoration:none!important;color:inherit;background:#fff;border:1.5px solid #D7DDE6;
      border-radius:16px;padding:1.05rem 1rem 1.1rem;box-shadow:0 10px 28px -22px rgba(11,61,145,.3);
      transition:transform .15s ease,border-color .15s ease;
    }
    .gl-sch-year:hover{transform:translateY(-2px);border-color:rgba(11,61,145,.35)}
    .gl-sch-year__num{
      display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;
      margin-bottom:.55rem;border-radius:10px;background:#E8EEF8;color:#0B3D91;font:900 .78rem Tajawal,sans-serif;
    }
    .gl-sch-year h3{margin:0 0 .3rem;font:900 .92rem/1.35 Tajawal,sans-serif;color:#0B1220}
    .gl-sch-year p{margin:0;font:600 .76rem/1.5 Tajawal,sans-serif;color:#5B6577}
    .gl-sch-year__cta{margin-top:.65rem;font:800 .75rem Tajawal,sans-serif;color:#0B3D91}
    .gl-sch-subjects{display:flex;flex-wrap:wrap;gap:.45rem}
    .gl-sch-sub{
      display:inline-flex;align-items:center;gap:8px;padding:.55rem .85rem;border-radius:999px;
      background:#F4F7FC;border:1.5px solid #D7DDE6;font:800 .78rem Tajawal,sans-serif;color:#0B1220;
    }
    .gl-sch-sub i{color:#0B3D91}
    .gl-sch-band{
      border-radius:18px;padding:clamp(1.25rem,3vw,1.85rem);text-align:center;color:#fff;
      background:linear-gradient(145deg,#051F4D 0%,#0B3D91 55%,#1A56B0 100%);
      box-shadow:0 18px 44px -18px rgba(11,61,145,.45);
    }
    .gl-sch-band h2{margin:0 0 .4rem;font:900 clamp(1.15rem,2.4vw,1.45rem)/1.35 Cairo,Tajawal,sans-serif}
    .gl-sch-band p{margin:0 auto 1rem;max-width:32rem;font:600 .88rem/1.65 Tajawal,sans-serif;color:rgba(255,255,255,.88)}
  </style>
</head>
<body class="sana-home gl-sch">
<div id="sana-scroll-progress"></div>
@include('partials.landing.navbar', ['navActive' => 'groups', 'navSolid' => false, 'navHero' => true])

<main>
  <section class="gl-sch-hero">
    <div class="sana-container">
      <div class="gl-sch-hero__inner sana-reveal">
        <p class="gl-sch-kicker"><i class="fas fa-school"></i> {{ __($g.'.kicker') }}</p>
        <h1>{{ __($g.'.title') }}</h1>
        <p>{{ __($g.'.intro') }}</p>
        <div class="gl-sch-actions">
          <a href="#years" class="sana-btn sana-btn--yellow sana-btn--lg">
            <i class="fas fa-layer-group"></i> {{ $isRtl ? 'اختر السنة' : 'Choose a year' }}
          </a>
          <a href="{{ $trialUrl }}" class="sana-btn sana-btn--white-outline sana-btn--lg">
            <i class="fas fa-clipboard-check"></i> {{ __($g.'.cta_primary') }}
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="gl-sch-sec" id="start">
    <div class="sana-container">
      <div class="gl-sch-head sana-reveal">
        <h2>{{ $isRtl ? 'كيف تبدأ؟' : 'How it works' }}</h2>
        <p>{{ $isRtl ? 'ثلاث خطوات واضحة — بدون تشتيت.' : 'Three clear steps — no clutter.' }}</p>
      </div>
      <div class="gl-sch-flow sana-reveal">
        <article class="gl-sch-flow__item">
          <span class="gl-sch-flow__n">1</span>
          <h3>{{ $isRtl ? 'اختبار تحديد المستوى' : 'Free level check' }}</h3>
          <p>{{ $isRtl ? 'نحجز لك تجربة مجانية لنعرف أين يبدأ طفلك.' : 'Book a free trial so we place your child correctly.' }}</p>
        </article>
        <article class="gl-sch-flow__item">
          <span class="gl-sch-flow__n">2</span>
          <h3>{{ $isRtl ? 'اختر السنة المناسبة' : 'Pick the right year' }}</h3>
          <p>{{ $isRtl ? 'Islamic Foundations 1–6 حسب العمر والمستوى.' : 'Islamic Foundations 1–6 by age and level.' }}</p>
        </article>
        <article class="gl-sch-flow__item">
          <span class="gl-sch-flow__n">3</span>
          <h3>{{ $isRtl ? 'انضم للفصل' : 'Join the class' }}</h3>
          <p>{{ $isRtl ? 'حصص مباشرة مع معلم المدرسة ومتابعة واضحة.' : 'Live school sessions with clear follow-up.' }}</p>
        </article>
      </div>
    </div>
  </section>

  <section class="gl-sch-sec gl-sch-sec--white" id="years">
    <div class="sana-container">
      <div class="gl-sch-head sana-reveal">
        <h2>{{ __($g.'.years_title') }}</h2>
        <p>{{ $isRtl ? 'اضغط على السنة لترى المواد والفصول المتاحة.' : 'Tap a year to see subjects and open classes.' }}</p>
      </div>
      <div class="gl-sch-years sana-reveal">
        @forelse ($schoolYears as $year)
          <a href="{{ route('public.school.year', $year->slug) }}" class="gl-sch-year">
            <span class="gl-sch-year__num">{{ str_pad((string) $year->level_number, 2, '0', STR_PAD_LEFT) }}</span>
            <h3>{{ $year->name }}</h3>
            @if ($year->tagline)
              <p>{{ $year->tagline }}</p>
            @endif
            <span class="gl-sch-year__cta">
              @if (($year->open_classes_count ?? 0) > 0)
                {{ $isRtl ? ($year->open_classes_count.' فصل متاح · عرض') : ($year->open_classes_count.' open · View') }}
              @else
                {{ $isRtl ? 'عرض السنة ←' : 'View year →' }}
              @endif
            </span>
          </a>
        @empty
          <p style="grid-column:1/-1;font:700 .9rem Tajawal,sans-serif;color:#5B6577">{{ $isRtl ? 'السنوات ستظهر قريباً.' : 'Years will appear soon.' }}</p>
        @endforelse
      </div>
    </div>
  </section>

  @if($schoolSubjects->isNotEmpty())
  <section class="gl-sch-sec" id="subjects">
    <div class="sana-container">
      <div class="gl-sch-head sana-reveal">
        <h2>{{ $isRtl ? 'ماذا يدرس الطالب؟' : 'What students learn' }}</h2>
        <p>{{ __($g.'.subjects_sub') }}</p>
      </div>
      <div class="gl-sch-subjects sana-reveal">
        @foreach ($schoolSubjects as $subject)
          <span class="gl-sch-sub"><i class="fas {{ $subject->faIcon() }}"></i> {{ $subject->name }}</span>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  <section class="gl-sch-sec" style="padding-top:0">
    <div class="sana-container">
      <div class="gl-sch-band sana-reveal">
        <h2>{{ $isRtl ? 'جاهز لوضع طفلك في السنة المناسبة؟' : 'Ready to place your child?' }}</h2>
        <p>{{ __($g.'.cta_sub') }}</p>
        <div class="gl-sch-actions" style="justify-content:center">
          <a href="{{ $trialUrl }}" class="sana-btn sana-btn--yellow sana-btn--lg">
            <i class="fas fa-clipboard-check"></i> {{ __($g.'.cta_trial') }}
          </a>
          <a href="#years" class="sana-btn sana-btn--white-outline sana-btn--lg">
            <i class="fas fa-layer-group"></i> {{ $isRtl ? 'تصفّح السنوات' : 'Browse years' }}
          </a>
        </div>
      </div>
    </div>
  </section>
</main>

@include('partials.landing.footer')
</body>
</html>
