@php
  $locale = app()->getLocale();
  $isRtl = $locale === 'ar';
  $brand = config('app.name', 'حصتك');
  $planMatrix = $planMatrix ?? [];
  $years = $years ?? collect();
  $selectedYear = $selectedYear ?? null;
  $yearId = $yearId ?? null;
  $privateRule = $privateRule ?? null;
  $privateTermMonths = $privateTermMonths ?? [1, 3];
  $privateWeeklyOptions = $privateWeeklyOptions ?? [1, 2, 3, 4];
  $fawaterakUseGateway = $fawaterakUseGateway ?? false;
  $fawaterakMisconfigured = $fawaterakMisconfigured ?? false;
  $footer = \App\Services\PublicFooterSettings::payload();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ $isRtl ? 'خطط الاشتراك' : 'Subscription plans' }} — {{ $brand }}</title>
  <meta name="description" content="{{ $isRtl ? 'School و Private و Premier — اختر المدة 1 أو 3 أو 6 أشهر بالريال السعودي مع إبراز الوفر.' : 'School, Private and Premier plans — choose 1, 3 or 6 months in SAR with clear savings.' }}">
  <meta name="theme-color" content="#0B3D91">
  <link rel="canonical" href="{{ route('public.service-packages.index') }}">
  @include('partials.favicon-links')
  @include('partials.landing.head', ['landingCss' => ['theme', 'courses-catalog', 'pricing']])
  <style>
    body.sana-pricing-page { padding-top: 0; }
    .gl-pl-grid { display:grid; gap:1.25rem; grid-template-columns:1fr; align-items:stretch; }
    @media (min-width:900px) { .gl-pl-grid { grid-template-columns:repeat(3,1fr); } }

    .gl-pl-card {
      position:relative; display:flex; flex-direction:column;
      background:#fff; border:1.5px solid #D7DDE6; border-radius:22px;
      box-shadow:0 16px 36px -26px rgba(11,61,145,.45); overflow:hidden;
    }
    .gl-pl-card.is-featured { border-color:#F5B800; box-shadow:0 22px 44px -20px rgba(245,184,0,.45); }
    .gl-pl-card__ribbon {
      position:absolute; inset-inline-end:-42px; top:18px; transform:rotate(45deg);
      background:linear-gradient(180deg,#FFD24D,#F5B800); color:#0B1220;
      font-size:.68rem; font-weight:900; padding:.28rem 2.8rem;
    }
    .gl-pl-card__head { padding:1.25rem 1.25rem 1rem; border-bottom:1px dashed #E4E9F2; }
    .gl-pl-icon {
      width:42px; height:42px; border-radius:14px; display:flex; align-items:center; justify-content:center;
      background:#EEF3FF; color:#0B3D91; font-size:1.05rem; margin-bottom:.75rem;
    }
    .gl-pl-card.is-featured .gl-pl-icon { background:#FFF6D6; color:#9A7200; }
    .gl-pl-name { margin:0; font-family:Cairo,Tajawal,sans-serif; font-size:1.35rem; font-weight:900; color:#0B1220; }
    .gl-pl-tag { margin:.45rem 0 0; font-size:.84rem; line-height:1.7; color:#5B6577; font-weight:600; }

    .gl-pl-terms {
      display:grid; grid-template-columns:repeat(3,1fr); gap:.45rem; margin-top:1rem;
    }
    .gl-pl-term {
      border:1.5px solid #D7DDE6; background:#F8FAFC; border-radius:12px;
      padding:.55rem .35rem; text-align:center; cursor:pointer; font-weight:800; font-size:.78rem; color:#5B6577;
      transition:border-color .15s ease, background .15s ease, color .15s ease;
    }
    .gl-pl-term.is-active {
      border-color:#0B3D91; background:#EEF3FF; color:#0B3D91;
    }
    .gl-pl-card.is-featured .gl-pl-term.is-active {
      border-color:#F5B800; background:#FFF8E1; color:#7A5C00;
    }
    .gl-pl-term small { display:block; font-size:.65rem; font-weight:700; opacity:.8; margin-top:.15rem; }

    .gl-pl-price { margin-top:1rem; display:flex; align-items:flex-end; gap:.4rem; flex-wrap:wrap; }
    .gl-pl-price__now { font-family:Cairo,sans-serif; font-size:2.1rem; font-weight:900; color:#0B3D91; line-height:1; direction:ltr; }
    .gl-pl-card.is-featured .gl-pl-price__now { color:#9A7200; }
    .gl-pl-price__cur { font-size:.85rem; font-weight:800; color:#5B6577; }
    .gl-pl-price__old { font-size:.85rem; color:#94A3B8; text-decoration:line-through; direction:ltr; }
    .gl-pl-save {
      margin-top:.65rem; display:none; align-items:center; gap:.35rem;
      background:#ECFDF5; color:#047857; border:1px solid #A7F3D0;
      border-radius:10px; padding:.45rem .65rem; font-size:.74rem; font-weight:800;
    }
    .gl-pl-save.is-on { display:inline-flex; }
    .gl-pl-meta {
      margin-top:.7rem; font-size:.78rem; font-weight:800; color:#5B6577;
    }

    .gl-pl-body { padding:1rem 1.25rem 1.25rem; display:flex; flex-direction:column; flex:1; gap:.85rem; }
    .gl-pl-features { list-style:none; margin:0; padding:0; display:grid; gap:.45rem; }
    .gl-pl-features li {
      display:flex; gap:.5rem; align-items:flex-start;
      font-size:.82rem; line-height:1.55; color:#0B1220; font-weight:650;
    }
    .gl-pl-features i { color:#0B3D91; margin-top:.2rem; font-size:.75rem; }
    .gl-pl-gift {
      margin-top:auto; border-radius:14px; background:#F4F7FC; border:1px solid #E4E9F2; padding:.85rem;
    }
    .gl-pl-gift strong { display:block; font-size:.78rem; color:#0B3D91; margin-bottom:.4rem; }
    .gl-pl-gift ul { margin:0; padding:0; list-style:none; display:grid; gap:.3rem; }
    .gl-pl-gift li { font-size:.75rem; color:#5B6577; font-weight:700; display:flex; gap:.4rem; }
    .gl-pl-gift i { color:#047857; }

    .gl-pl-cta { margin-top:.85rem; }
    .gl-pl-note { margin:.55rem 0 0; font-size:.72rem; color:#5B6577; text-align:center; font-weight:700; }

    .gl-pl-compare { overflow-x:auto; border:1.5px solid #D7DDE6; border-radius:18px; background:#fff; }
    .gl-pl-compare table { width:100%; border-collapse:collapse; min-width:640px; font-size:.84rem; }
    .gl-pl-compare th, .gl-pl-compare td { padding:.75rem 1rem; text-align:start; border-bottom:1px solid #EEF2F8; }
    .gl-pl-compare thead th { background:#F4F7FC; color:#0B3D91; font-weight:900; font-size:.78rem; }
    .gl-pl-compare tbody tr:last-child td { border-bottom:0; }
    .gl-pl-yes { color:#047857; font-weight:900; }
    .gl-pl-no { color:#94A3B8; font-weight:800; }

    .gl-pl-year {
      display:flex; flex-wrap:wrap; gap:.6rem; justify-content:center; margin:0 auto 1.25rem; max-width:820px;
    }
    .gl-pl-year a, .gl-pl-year span {
      display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .75rem; border-radius:999px;
      border:1.5px solid #D7DDE6; background:#fff; color:#0B3D91; font-size:.78rem; font-weight:800; text-decoration:none;
    }
    .gl-pl-year a.is-active { background:#0B3D91; color:#fff; border-color:#0B3D91; }

    .gl-pl-steps { display:grid; gap:.9rem; grid-template-columns:1fr; }
    @media (min-width:800px) { .gl-pl-steps { grid-template-columns:repeat(4,1fr); } }
    .gl-pl-step { background:#fff; border:1.5px solid #D7DDE6; border-radius:16px; padding:1rem; text-align:center; }
    .gl-pl-step__n {
      width:34px; height:34px; margin:0 auto .55rem; border-radius:50%;
      background:#0B3D91; color:#fff; font-weight:900; display:flex; align-items:center; justify-content:center;
    }
    .gl-pl-step strong { display:block; color:#0B1220; margin-bottom:.25rem; }
    .gl-pl-step span { font-size:.8rem; color:#5B6577; line-height:1.65; }

    /* Private custom builder */
    .gl-pv { display:grid; gap:1.15rem; align-items:start; }
    @media (min-width:980px) { .gl-pv { grid-template-columns:minmax(0,1.25fr) minmax(300px,.75fr); } }
    .gl-pv-panel {
      background:#fff; border:1.5px solid #D7DDE6; border-radius:20px;
      padding:1.15rem 1.2rem 1.3rem; box-shadow:0 14px 34px -26px rgba(11,61,145,.45);
    }
    .gl-pv-label { display:block; margin:0 0 .55rem; font-size:.78rem; font-weight:800; color:#5B6577; }
    .gl-pv-choices { display:grid; gap:.55rem; grid-template-columns:repeat(2,1fr); }
    .gl-pv-choices.is-4 { grid-template-columns:repeat(4,1fr); }
    @media (max-width:520px) { .gl-pv-choices.is-4 { grid-template-columns:repeat(2,1fr); } }
    .gl-pv-choice {
      position:relative; border:1.5px solid #D7DDE6; background:#F8FAFC; border-radius:14px;
      padding:.85rem .6rem; text-align:center; cursor:pointer; font-weight:800; color:#5B6577;
    }
    .gl-pv-choice input { position:absolute; opacity:0; pointer-events:none; }
    .gl-pv-choice.is-on, .gl-pv-choice:has(input:checked) {
      border-color:#0B3D91; background:#EEF3FF; color:#0B3D91;
    }
    .gl-pv-choice strong { display:block; font-size:.95rem; }
    .gl-pv-choice small { display:block; margin-top:.2rem; font-size:.68rem; font-weight:700; opacity:.85; }
    .gl-pv-summary__price { font-family:Cairo,sans-serif; font-size:2rem; font-weight:900; color:#0B3D91; direction:ltr; }
    .gl-pv-summary__old { color:#94A3B8; text-decoration:line-through; direction:ltr; font-size:.85rem; margin-inline-start:.35rem; }
    .gl-pv-summary__save {
      display:none; margin-top:.55rem; padding:.4rem .65rem; border-radius:10px;
      background:#ECFDF5; color:#047857; font-size:.74rem; font-weight:800; border:1px solid #A7F3D0;
    }
    .gl-pv-summary__save.is-on { display:inline-flex; align-items:center; gap:.35rem; }
    .gl-pv-lines { list-style:none; margin:.9rem 0 0; padding:0; display:grid; gap:.45rem; }
    .gl-pv-lines li { display:flex; justify-content:space-between; gap:.75rem; font-size:.82rem; border-bottom:1px solid #F1F4F9; padding:.4rem 0; }
    .gl-pv-lines li:last-child { border-bottom:0; }
    .gl-pv-lines span { color:#5B6577; font-weight:700; }
    .gl-pv-lines strong { color:#0B1220; font-weight:900; }
    .gl-pv-pay { display:grid; gap:.45rem; margin-top:.85rem; }
    .gl-pv-select { width:100%; height:42px; border:1.5px solid #D7DDE6; border-radius:12px; padding:0 .75rem; font-weight:700; background:#fff; }
  </style>
</head>
<body class="sana-home sana-courses-page sana-pricing-page">
<div id="sana-scroll-progress"></div>
@include('partials.landing.navbar', ['navActive' => 'packages', 'navSolid' => true, 'navHero' => false])

<main class="sana-cat-page">
  <section class="sana-cat-hero" style="padding-bottom:1.1rem">
    <div class="sana-container sana-cat-hero__inner sana-reveal">
      <nav class="sana-cat-hero__breadcrumb" aria-label="breadcrumb">
        <a href="{{ route('home') }}">{{ $isRtl ? 'الرئيسية' : 'Home' }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ $isRtl ? 'خطط الاشتراك' : 'Plans' }}</span>
      </nav>
      <h1 class="sana-cat-hero__title" style="margin-top:.75rem">
        {{ $isRtl ? 'اختر خطتك' : 'Choose your' }}
        <span class="hl">{{ $isRtl ? 'School · Private · Premier' : 'School · Private · Premier' }}</span>
      </h1>
      <p class="sana-cat-hero__sub">
        {{ $isRtl
          ? 'ثلاث خطط جاهزة، أو خصّص باقتك الخاصة: اختر شهر أو 3 أشهر وعدد الحصص الأسبوعية الثابت.'
          : 'Three ready plans, or build your private pack: pick 1 or 3 months and a fixed weekly session count.' }}
      </p>
      <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.6rem;justify-content:center">
        <a href="#plans" class="sana-btn sana-btn--yellow"><i class="fas fa-box-open"></i> {{ $isRtl ? 'الخطط الجاهزة' : 'Ready plans' }}</a>
        @if($privateRule)
          <a href="#private-builder" class="sana-btn sana-btn--white-outline"><i class="fas fa-sliders"></i> {{ $isRtl ? 'خصص باقتك الخاصة' : 'Build private pack' }}</a>
        @endif
      </div>
    </div>
  </section>

  @if(session('success') || session('error'))
    <div class="sana-container" style="padding-top:1rem">
      @if(session('success'))
        <div style="background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0;padding:.75rem 1rem;border-radius:12px;margin-bottom:.75rem;font-weight:700">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div style="background:#FEF2F2;color:#991B1B;border:1px solid #FECACA;padding:.75rem 1rem;border-radius:12px;font-weight:700">{{ session('error') }}</div>
      @endif
    </div>
  @endif

  <section class="sana-section" id="plans" style="padding-top:clamp(24px,4vw,40px)">
    <div class="sana-container">
      @if($years->isNotEmpty())
        <div class="gl-pl-year sana-reveal">
          <span><i class="fas fa-school"></i> {{ $isRtl ? 'برنامج المدرسة:' : 'School program:' }}</span>
          <a href="{{ route('public.service-packages.index') }}" @class(['is-active' => ! $yearId])>{{ $isRtl ? 'كل الخطط' : 'All plans' }}</a>
          @foreach($years->take(8) as $year)
            <a href="{{ route('public.service-packages.index', ['year' => $year->id]) }}" @class(['is-active' => (string)$yearId === (string)$year->id])>{{ $year->name }}</a>
          @endforeach
          @if($selectedYear)
            <a href="{{ route('public.school.year', $selectedYear->slug) }}"><i class="fas fa-door-open"></i> {{ $isRtl ? 'فصول السنة' : 'Year classes' }}</a>
          @endif
        </div>
      @endif

      @if(empty($planMatrix))
        <p style="text-align:center;padding:3rem 1rem;color:#5B6577;font-weight:700">
          {{ $isRtl ? 'لا توجد خطط نشطة حالياً.' : 'No active plans right now.' }}
        </p>
      @else
        <div class="gl-pl-grid">
          @foreach($planMatrix as $planType => $plan)
            @php
              $defaultTerm = $plan['terms']->get(3) ?: $plan['terms']->first();
              $defaultMonths = (int) ($defaultTerm->term_months ?? 1);
            @endphp
            <article @class(['gl-pl-card sana-reveal', 'is-featured' => $plan['featured']]) data-plan="{{ $planType }}">
              @if($plan['featured'])
                <span class="gl-pl-card__ribbon">{{ $isRtl ? 'الأفضل قيمة' : 'Best value' }}</span>
              @endif

              <div class="gl-pl-card__head">
                <div class="gl-pl-icon"><i class="fas {{ $plan['icon'] }}"></i></div>
                <h2 class="gl-pl-name">{{ $plan['label'] }}</h2>
                @if($plan['tagline'])
                  <p class="gl-pl-tag">{{ $plan['tagline'] }}</p>
                @endif

                <div class="gl-pl-terms" role="tablist" aria-label="{{ $isRtl ? 'مدة الاشتراك' : 'Term length' }}">
                  @foreach([1,3,6] as $months)
                    @php $termPkg = $plan['terms']->get($months); @endphp
                    @if($termPkg)
                      <button
                        type="button"
                        class="gl-pl-term {{ $months === $defaultMonths ? 'is-active' : '' }}"
                        data-term="{{ $months }}"
                        data-package-id="{{ $termPkg->id }}"
                        data-price="{{ number_format((float)$termPkg->price, 0, '.', '') }}"
                        data-original="{{ $termPkg->original_price ? number_format((float)$termPkg->original_price, 0, '.', '') : '' }}"
                        data-save="{{ $termPkg->savingsVsMonthlyLabel() }}"
                        data-checkout="{{ route('public.service-packages.checkout', $termPkg) }}"
                        data-units="{{ $termPkg->units_count }}"
                        data-weekly="{{ $termPkg->weeklySessionsTotal() }}"
                      >
                        {{ $months === 1 ? ($isRtl ? 'شهر' : '1 mo') : ($isRtl ? $months.' أشهر' : $months.' mo') }}
                        <small>{{ currency_symbol() }}{{ number_format((float)$termPkg->price, 0) }}</small>
                      </button>
                    @endif
                  @endforeach
                </div>

                <div class="gl-pl-price">
                  <span class="gl-pl-price__now"><span data-el="price">{{ number_format((float)$defaultTerm->price, 0) }}</span></span>
                  <span class="gl-pl-price__cur">{{ currency_symbol() }}</span>
                  <span class="gl-pl-price__old" data-el="original" @style(['display:none' => ! $defaultTerm->original_price || (float)$defaultTerm->original_price <= (float)$defaultTerm->price])>
                    <span data-el="original-val">{{ $defaultTerm->original_price ? number_format((float)$defaultTerm->original_price, 0) : '' }}</span>
                  </span>
                </div>
                <div class="gl-pl-save {{ $defaultTerm->savingsVsMonthlyLabel() ? 'is-on' : '' }}" data-el="save">
                  <i class="fas fa-tag"></i>
                  <span data-el="save-text">{{ $defaultTerm->savingsVsMonthlyLabel() }}</span>
                </div>
                <p class="gl-pl-meta" data-el="meta">
                  {{ $defaultTerm->weeklySessionsTotal() }} {{ $isRtl ? 'حصص أسبوعياً' : 'sessions / week' }}
                  · {{ $defaultTerm->units_count }} {{ $isRtl ? 'حصة في المدة' : 'sessions in term' }}
                </p>
              </div>

              <div class="gl-pl-body">
                <ul class="gl-pl-features">
                  @foreach($plan['features'] as $feature)
                    <li><i class="fas fa-check"></i><span>{{ $feature }}</span></li>
                  @endforeach
                  <li>
                    <i class="fas fa-calendar-week"></i>
                    <span>
                      @if($plan['weekly_group'] > 0 && $plan['weekly_private'] > 0)
                        {{ $isRtl ? $plan['weekly_group'].' جماعي + '.$plan['weekly_private'].' فردي أسبوعياً' : $plan['weekly_group'].' group + '.$plan['weekly_private'].' private / week' }}
                      @elseif($plan['weekly_group'] > 0)
                        {{ $isRtl ? $plan['weekly_group'].' حصص فصل (مدرسة) أسبوعياً' : $plan['weekly_group'].' school class sessions / week' }}
                      @else
                        {{ $isRtl ? $plan['weekly_private'].' حصص فردية أسبوعياً' : $plan['weekly_private'].' private sessions / week' }}
                      @endif
                    </span>
                  </li>
                </ul>

                @if(!empty($plan['gifts']))
                  <div class="gl-pl-gift">
                    <strong><i class="fas fa-gift"></i> {{ $isRtl ? 'هدية مع الاشتراك' : 'Included with subscription' }}</strong>
                    <ul>
                      @foreach($plan['gifts'] as $gift)
                        <li><i class="fas fa-check-circle"></i><span>{{ $gift }}</span></li>
                      @endforeach
                    </ul>
                  </div>
                @endif

                <div class="gl-pl-cta">
                  <a href="{{ route('public.service-packages.checkout', $defaultTerm) }}" class="sana-btn {{ $plan['featured'] ? 'sana-btn--yellow' : 'sana-btn--purple' }}" style="width:100%;justify-content:center" data-el="cta">
                    <i class="fas fa-cart-shopping"></i>
                    {{ $isRtl ? 'اشترك الآن' : 'Subscribe now' }}
                  </a>
                  <p class="gl-pl-note">{{ $isRtl ? 'يُفعَّل الرصيد بعد تأكيد الدفع. العائلة تختار الفصل حسب المواعيد المتاحة.' : 'Credits activate after payment approval. Families pick a class by available schedule.' }}</p>
                </div>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  @if(count($planMatrix) >= 2)
    <section class="sana-section" style="padding-top:8px">
      <div class="sana-container">
        <div class="sana-head sana-head--center sana-reveal" style="margin-bottom:18px">
          <span class="sana-head__eyebrow">{{ $isRtl ? 'مقارنة سريعة' : 'Quick compare' }}</span>
          <h2 class="sana-head__title">{{ $isRtl ? 'المزايا حسب الخطة' : 'Features by plan' }}</h2>
          <span class="sana-head__line"></span>
        </div>
        <div class="gl-pl-compare sana-reveal">
          <table>
            <thead>
              <tr>
                <th>{{ $isRtl ? 'المزايا' : 'Features' }}</th>
                @foreach($planMatrix as $plan)
                  <th>{{ $plan['label'] }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @php
                $rows = [
                  ['key' => 'weekly', 'label' => $isRtl ? 'الحصص الأسبوعية' : 'Weekly sessions'],
                  ['key' => 'type', 'label' => $isRtl ? 'نوع الدراسة' : 'Study type'],
                  ['key' => 'class', 'label' => $isRtl ? 'اختيار الفصل' : 'Choose class'],
                  ['key' => 'teacher', 'label' => $isRtl ? 'اختيار المعلم' : 'Choose teacher'],
                  ['key' => 'schedule', 'label' => $isRtl ? 'اختيار المواعيد' : 'Schedule'],
                  ['key' => 'community', 'label' => $isRtl ? 'مجتمع طلابي' : 'Student community'],
                  ['key' => 'libraries', 'label' => $isRtl ? 'المكتبة التفاعلية + الفيديوهات' : 'Interactive + video libraries'],
                ];
              @endphp
              @foreach($rows as $row)
                <tr>
                  <td><strong>{{ $row['label'] }}</strong></td>
                  @foreach($planMatrix as $type => $plan)
                    <td>
                      @if($row['key'] === 'weekly')
                        {{ ($plan['weekly_group'] + $plan['weekly_private']) }} {{ $isRtl ? 'حصص' : 'sessions' }}
                      @elseif($row['key'] === 'type')
                        @if($type === 'school') {{ $isRtl ? 'فصل تعليمي' : 'Class' }}
                        @elseif($type === 'private') {{ $isRtl ? 'فردي 1:1' : '1:1 private' }}
                        @else {{ $isRtl ? 'مدرسة + فردي' : 'School + private' }}
                        @endif
                      @elseif($row['key'] === 'class')
                        <span @class([$plan['weekly_group'] > 0 ? 'gl-pl-yes' : 'gl-pl-no'])>{{ $plan['weekly_group'] > 0 ? '✓' : '—' }}</span>
                      @elseif($row['key'] === 'teacher')
                        <span @class([$plan['weekly_private'] > 0 ? 'gl-pl-yes' : 'gl-pl-no'])>{{ $plan['weekly_private'] > 0 ? '✓' : '—' }}</span>
                      @elseif($row['key'] === 'schedule')
                        @if($type === 'school') {{ $isRtl ? 'حسب الفصل' : 'By class' }}
                        @elseif($type === 'private') {{ $isRtl ? 'حسب اختيار الطالب' : 'Student choice' }}
                        @else {{ $isRtl ? 'الاثنين معاً' : 'Both' }}
                        @endif
                      @elseif($row['key'] === 'community')
                        <span @class([$plan['community'] ? 'gl-pl-yes' : 'gl-pl-no'])>{{ $plan['community'] ? '✓' : '—' }}</span>
                      @elseif($row['key'] === 'libraries')
                        <span @class([$plan['libraries'] ? 'gl-pl-yes' : 'gl-pl-no'])>{{ $plan['libraries'] ? '✓' : '—' }}</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </section>
  @endif

  @if($privateRule)
    <section class="sana-section sana-section--soft" id="private-builder">
      <div class="sana-container">
        <div class="sana-head sana-head--center sana-reveal" style="margin-bottom:20px">
          <span class="sana-head__eyebrow">{{ $isRtl ? 'Private Sessions' : 'Private Sessions' }}</span>
          <h2 class="sana-head__title">{{ $isRtl ? 'خصص باقتك الخاصة' : 'Build your private pack' }}</h2>
          <span class="sana-head__line"></span>
          <p class="sana-head__sub">
            {{ $isRtl
              ? 'اختر مدة الاشتراك (شهر أو 3 أشهر) وعدد الحصص الأسبوعية الثابت. السعر يُحسب فوراً من قواعد الإدارة بالريال السعودي.'
              : 'Choose 1 or 3 months and a fixed weekly session count. Price is calculated instantly from admin SAR rules.' }}
          </p>
        </div>

        <form method="POST" action="{{ route('public.service-packages.custom.store') }}" id="private-builder-form" class="gl-pv sana-reveal">
          @csrf
          <input type="hidden" name="pricing_rule_id" value="{{ $privateRule->id }}">

          <div class="gl-pv-panel">
            <div style="margin-bottom:1.1rem">
              <span class="gl-pv-label">1) {{ $isRtl ? 'مدة الاشتراك' : 'Subscription term' }}</span>
              <div class="gl-pv-choices">
                @foreach($privateTermMonths as $months)
                  <label class="gl-pv-choice {{ $months === 1 ? 'is-on' : '' }}">
                    <input type="radio" name="term_months" value="{{ $months }}" @checked($months === 1) required>
                    <strong>{{ $months === 1 ? ($isRtl ? 'شهر' : '1 month') : ($isRtl ? '3 أشهر' : '3 months') }}</strong>
                    <small>{{ $months === 1 ? ($isRtl ? 'تجربة مرنة' : 'Flexible start') : ($isRtl ? 'قيمة أفضل' : 'Better value') }}</small>
                  </label>
                @endforeach
              </div>
            </div>

            <div style="margin-bottom:1.1rem">
              <span class="gl-pv-label">2) {{ $isRtl ? 'حصص أسبوعياً (ثابت)' : 'Sessions per week (fixed)' }}</span>
              <div class="gl-pv-choices is-4">
                @foreach($privateWeeklyOptions as $weekly)
                  <label class="gl-pv-choice {{ $weekly === 2 ? 'is-on' : '' }}">
                    <input type="radio" name="weekly_sessions" value="{{ $weekly }}" @checked($weekly === 2) required>
                    <strong>{{ $weekly }}</strong>
                    <small>{{ $isRtl ? 'حصة/أسبوع' : '/ week' }}</small>
                  </label>
                @endforeach
              </div>
            </div>

            <div>
              <span class="gl-pv-label">3) {{ $isRtl ? 'الدفع' : 'Payment' }}</span>
              <div class="gl-pv-pay">
                @if(!empty($fawaterakUseGateway))
                  <p style="margin:0;font-size:.8rem;color:#0B3D91;font-weight:800;line-height:1.65">
                    <i class="fas fa-lock"></i>
                    {{ $isRtl ? 'بعد تأكيد الباقة ستنتقل مباشرة لبوابة فواتيرك لإتمام الدفع.' : 'After confirming, you will continue to Fawaterak to complete payment.' }}
                  </p>
                @elseif(!empty($fawaterakMisconfigured))
                  <p style="margin:0;font-size:.8rem;color:#991B1B;font-weight:800;line-height:1.65">
                    {{ $isRtl ? 'تم تفعيل فواتيرك لكن الربط غير مكتمل على الخادم.' : 'Fawaterak is enabled but server credentials are incomplete.' }}
                  </p>
                @else
                  <p style="margin:0;font-size:.8rem;color:#5B6577;font-weight:800;line-height:1.65">
                    {{ $isRtl ? 'بوابة فواتيرك غير مفعّلة حالياً. تواصل معنا عبر واتساب.' : 'Fawaterak is not enabled right now. Contact us on WhatsApp.' }}
                  </p>
                @endif
              </div>
              <p style="margin:.7rem 0 0;font-size:.74rem;color:#5B6577;font-weight:700">
                {{ $isRtl ? 'سعر الحصة الأساسي:' : 'Base session price:' }}
                <span style="direction:ltr;display:inline-block">{{ number_format((float) $privateRule->price_per_session, 2) }} {{ currency_symbol() }}</span>
                · {{ $privateRule->session_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}
              </p>
            </div>
          </div>

          <aside class="gl-pv-panel">
            <p style="margin:0;font-size:.75rem;font-weight:800;color:#5B6577">{{ $isRtl ? 'ملخص باقتك' : 'Your pack summary' }}</p>
            <div style="margin-top:.55rem" class="gl-pv-summary__price"><span id="pv-total">0.00</span> <small style="font-size:.85rem;color:#5B6577">{{ currency_symbol() }}</small></div>
            <div id="pv-old-wrap" style="display:none;margin-top:.25rem">
              <span class="gl-pv-summary__old"><span id="pv-old">0.00</span></span>
            </div>
            <div class="gl-pv-summary__save" id="pv-save"><i class="fas fa-tag"></i> <span id="pv-save-text"></span></div>

            <ul class="gl-pv-lines">
              <li><span>{{ $isRtl ? 'المدة' : 'Term' }}</span><strong id="pv-term">—</strong></li>
              <li><span>{{ $isRtl ? 'حصص أسبوعياً' : 'Weekly' }}</span><strong id="pv-weekly">—</strong></li>
              <li><span>{{ $isRtl ? 'إجمالي الحصص' : 'Total sessions' }}</span><strong id="pv-sessions">—</strong></li>
              <li><span>{{ $isRtl ? 'صلاحية الرصيد' : 'Validity' }}</span><strong id="pv-days">—</strong></li>
              <li><span>{{ $isRtl ? 'سعر الحصة بعد الخصم' : 'Final / session' }}</span><strong id="pv-unit">—</strong></li>
            </ul>

            @auth
              @if(!empty($fawaterakUseGateway))
                <button type="submit" class="sana-btn sana-btn--yellow" style="width:100%;justify-content:center;margin-top:1rem" id="pv-submit">
                  <i class="fas fa-lock"></i> {{ $isRtl ? 'ادفع عبر فواتيرك' : 'Pay with Fawaterak' }}
                </button>
              @else
                <a href="{{ $footer['whatsapp_url'] ?? '#' }}" class="sana-btn sana-btn--wa" style="width:100%;justify-content:center;margin-top:1rem" target="_blank" rel="noopener">
                  <i class="fab fa-whatsapp"></i> {{ $isRtl ? 'تواصل للشراء' : 'Contact to buy' }}
                </a>
              @endif
            @else
              <a href="{{ route('login') }}" class="sana-btn sana-btn--yellow" style="width:100%;justify-content:center;margin-top:1rem">
                <i class="fas fa-right-to-bracket"></i> {{ $isRtl ? 'سجّل للطلب' : 'Login to order' }}
              </a>
            @endauth
            <p style="margin:.65rem 0 0;font-size:.72rem;color:#5B6577;text-align:center;font-weight:700">
              {{ $isRtl ? 'بعد التفعيل تختار المعلم والمواعيد من الحصص الخاصة.' : 'After activation, pick your teacher and schedule from private sessions.' }}
            </p>
          </aside>
        </form>
      </div>
    </section>
  @endif

  <section class="sana-section sana-section--soft">
    <div class="sana-container">
      <div class="sana-head sana-head--center sana-reveal" style="margin-bottom:18px">
        <span class="sana-head__eyebrow">{{ $isRtl ? 'كيف يعمل' : 'How it works' }}</span>
        <h2 class="sana-head__title">{{ $isRtl ? 'من الاشتراك إلى الحصة' : 'From subscribe to class' }}</h2>
        <span class="sana-head__line"></span>
      </div>
      <div class="gl-pl-steps">
        @foreach([
          [$isRtl ? 'اختر الخطة والمدة' : 'Pick plan & term', $isRtl ? 'School أو Private أو Premier ثم شهر / 3 / 6.' : 'School, Private or Premier, then 1 / 3 / 6 months.'],
          [$isRtl ? 'ادفع بالريال السعودي' : 'Pay in SAR', $isRtl ? 'يُراجع الدفع وتُفعَّل أرصدة الحصص.' : 'Payment is reviewed and session credits activate.'],
          [$isRtl ? 'اختر الفصل أو المعلم' : 'Pick class or teacher', $isRtl ? 'المدرسة: فصل حسب المواعيد. الخاص: معلم ومواعيد.' : 'School: class by schedule. Private: teacher & slots.'],
          [$isRtl ? 'احضر Live وجدّد' : 'Attend Live & renew', $isRtl ? 'الحصة عبر البث المباشر، والتجديد بنفس الخطة.' : 'Join live sessions, renew with the same plan.'],
        ] as $i => $step)
          <article class="gl-pl-step sana-reveal">
            <div class="gl-pl-step__n">{{ $i+1 }}</div>
            <strong>{{ $step[0] }}</strong>
            <span>{{ $step[1] }}</span>
          </article>
        @endforeach
      </div>
    </div>
  </section>
</main>

@include('partials.landing.footer')

<script>
(() => {
  const currencySymbol = @json(currency_symbol());
  const isRtlJs = @json((bool) $isRtl);
  const weekLabel = @json($isRtl ? 'حصص أسبوعياً' : 'sessions / week');
  const termLabel = @json($isRtl ? 'حصة في المدة' : 'sessions in term');

  document.querySelectorAll('.gl-pl-card').forEach((card) => {
    const buttons = card.querySelectorAll('.gl-pl-term');
    const priceEl = card.querySelector('[data-el="price"]');
    const originalWrap = card.querySelector('[data-el="original"]');
    const originalVal = card.querySelector('[data-el="original-val"]');
    const saveBox = card.querySelector('[data-el="save"]');
    const saveText = card.querySelector('[data-el="save-text"]');
    const meta = card.querySelector('[data-el="meta"]');
    const cta = card.querySelector('[data-el="cta"]');

    const apply = (btn) => {
      buttons.forEach((b) => b.classList.toggle('is-active', b === btn));
      if (priceEl) priceEl.textContent = btn.dataset.price || '0';
      const original = btn.dataset.original || '';
      if (originalWrap && originalVal) {
        if (original && Number(original) > Number(btn.dataset.price || 0)) {
          originalWrap.style.display = '';
          originalVal.textContent = original;
        } else {
          originalWrap.style.display = 'none';
        }
      }
      const save = btn.dataset.save || '';
      if (saveBox && saveText) {
        if (save) {
          saveBox.classList.add('is-on');
          saveText.textContent = save;
        } else {
          saveBox.classList.remove('is-on');
          saveText.textContent = '';
        }
      }
      if (meta) {
        meta.textContent = `${btn.dataset.weekly || 0} ${weekLabel} · ${btn.dataset.units || 0} ${termLabel}`;
      }
      if (cta && btn.dataset.checkout) {
        cta.setAttribute('href', btn.dataset.checkout);
      }
    };

    buttons.forEach((btn) => btn.addEventListener('click', () => apply(btn)));
  });

  const form = document.getElementById('private-builder-form');
  if (form) {
    const quoteUrl = @json(route('public.service-packages.custom.quote'));
    const isRtlJs = @json($isRtl);
    const els = {
      total: document.getElementById('pv-total'),
      oldWrap: document.getElementById('pv-old-wrap'),
      old: document.getElementById('pv-old'),
      save: document.getElementById('pv-save'),
      saveText: document.getElementById('pv-save-text'),
      term: document.getElementById('pv-term'),
      weekly: document.getElementById('pv-weekly'),
      sessions: document.getElementById('pv-sessions'),
      days: document.getElementById('pv-days'),
      unit: document.getElementById('pv-unit'),
    };

    const syncChoiceStyles = () => {
      form.querySelectorAll('.gl-pv-choice').forEach((label) => {
        const input = label.querySelector('input');
        label.classList.toggle('is-on', !!(input && input.checked));
      });
    };

    const refresh = async () => {
      syncChoiceStyles();
      const term = form.querySelector('input[name="term_months"]:checked')?.value;
      const weekly = form.querySelector('input[name="weekly_sessions"]:checked')?.value;
      const ruleId = form.querySelector('input[name="pricing_rule_id"]')?.value;
      if (!term || !weekly || !ruleId) return;

      try {
        const url = new URL(quoteUrl, window.location.origin);
        url.searchParams.set('pricing_rule_id', ruleId);
        url.searchParams.set('term_months', term);
        url.searchParams.set('weekly_sessions', weekly);
        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'quote failed');

        if (els.total) els.total.textContent = Number(data.amount).toFixed(2);
        if (els.old && els.oldWrap) {
          if (Number(data.discount_amount) > 0) {
            els.oldWrap.style.display = '';
            els.old.textContent = Number(data.original_amount).toFixed(2);
          } else {
            els.oldWrap.style.display = 'none';
          }
        }
        if (els.save && els.saveText) {
          if (Number(data.discount_percent) > 0) {
            els.save.classList.add('is-on');
            els.saveText.textContent = isRtlJs
              ? `وفر ${Number(data.discount_percent)}% (${Number(data.discount_amount).toFixed(2)} ${currencySymbol})`
              : `Save ${Number(data.discount_percent)}% (${Number(data.discount_amount).toFixed(2)} ${currencySymbol})`;
          } else {
            els.save.classList.remove('is-on');
          }
        }
        if (els.term) els.term.textContent = Number(term) === 1 ? (isRtlJs ? 'شهر' : '1 month') : (isRtlJs ? '3 أشهر' : '3 months');
        if (els.weekly) els.weekly.textContent = `${weekly} ${isRtlJs ? 'حصة' : 'sessions'}`;
        if (els.sessions) els.sessions.textContent = String(data.sessions);
        if (els.days) els.days.textContent = `${data.duration_days} ${isRtlJs ? 'يوم' : 'days'}`;
        if (els.unit) els.unit.textContent = `${Number(data.final_price_per_session).toFixed(2)} ${currencySymbol}`;
      } catch (e) {
        if (els.total) els.total.textContent = '—';
      }
    };

    form.querySelectorAll('input[name="term_months"], input[name="weekly_sessions"]').forEach((input) => {
      input.addEventListener('change', refresh);
    });
    refresh();
  }
})();
</script>
</body>
</html>
