@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $g = 'landing.groups_page';
    $brand = config('app.name', 'حصتك');
    $footer = \App\Services\PublicFooterSettings::payload();
    $waUrl = $footer['whatsapp_url'] ?? '#';
    $fallbackImg = $group->isIndividual()
        ? 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=1200&q=80'
        : 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80';
    $thumb = $group->imageUrl() ?: $fallbackImg;
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ $group->title }} — {{ $brand }}</title>
  <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $group->description), 160) }}">
  <meta name="theme-color" content="#0B3D91">
  <link rel="canonical" href="{{ route('public.groups.show', $group->slug) }}">
  @include('partials.favicon-links')
  @include('partials.landing.head', ['landingCss' => ['theme', 'courses-catalog']])
  @include('partials.landing.groups-catalog-styles')
  <style>
    .gl-gs { padding: clamp(24px, 4vw, 40px) 0 64px; }
    .gl-gs-grid { display: grid; gap: 1.25rem; }
    @media (min-width: 960px) { .gl-gs-grid { grid-template-columns: 1.1fr .9fr; align-items: start; } }
    .gl-gs-card { background:#fff; border:1.5px solid #D7DDE6; border-radius:18px; overflow:hidden; box-shadow:0 12px 28px -20px rgba(11,61,145,.35); }
    .gl-gs-card__media { aspect-ratio:16/10; background:#E8EEF8; }
    .gl-gs-card__media img { width:100%; height:100%; object-fit:cover; display:block; }
    .gl-gs-card__body { padding:1.1rem 1.15rem 1.25rem; }
    .gl-gs-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .65rem; border-radius:999px; background:#0B3D91; color:#fff; font-size:.7rem; font-weight:800; }
    .gl-gs-badge--gold { background:linear-gradient(180deg,#FFD24D,#F5B800); color:#0B1220; }
    .gl-gs-card__body h1 { margin:.7rem 0 .35rem; font-family:Cairo,Tajawal,sans-serif; font-size:clamp(1.25rem,2.5vw,1.65rem); font-weight:900; color:#0B1220; }
    .gl-gs-meta { display:flex; flex-wrap:wrap; gap:.5rem .9rem; margin:.75rem 0; color:#5B6577; font-size:.82rem; font-weight:700; }
    .gl-gs-desc { color:#3A4454; font-size:.9rem; line-height:1.75; white-space:pre-line; }
    .gl-gs-price { margin-top:1rem; font-size:1.15rem; font-weight:900; color:#0B3D91; }
    .gl-gs-form { padding:1.1rem 1.15rem 1.25rem; }
    .gl-gs-form h2 { margin:0 0 .75rem; font-size:1.05rem; font-weight:900; color:#0B1220; }
    .gl-gs-label { display:block; margin-bottom:.35rem; font-size:.75rem; font-weight:700; color:#5B6577; }
    .gl-gs-input, .gl-gs-select, .gl-gs-area {
      width:100%; border:1.5px solid #D7DDE6; border-radius:12px; padding:.7rem .85rem;
      font-size:.9rem; background:#fff; color:#0B1220; margin-bottom:.85rem;
    }
    .gl-gs-slots { display:grid; gap:.45rem; max-height:220px; overflow:auto; margin-bottom:.85rem; }
    .gl-gs-slot {
      display:flex; align-items:center; gap:.55rem; padding:.55rem .7rem; border-radius:10px;
      border:1.5px solid #D7DDE6; cursor:pointer; font-size:.82rem; font-weight:700; color:#0B1220;
    }
    .gl-gs-slot:has(input:checked) { border-color:#0B3D91; background:#F0F5FF; }
    .gl-gs-alert { padding:.75rem 1rem; border-radius:12px; margin-bottom:1rem; font-size:.86rem; font-weight:700; }
    .gl-gs-alert--ok { background:#ECFDF5; color:#065F46; border:1px solid #A7F3D0; }
    .gl-gs-alert--err { background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; }
  </style>
</head>
<body class="sana-home sana-courses-page">
<div id="sana-scroll-progress"></div>
@include('partials.landing.navbar', ['navActive' => 'groups', 'navSolid' => true, 'navHero' => false])

<main class="sana-cat-page">
  <section class="sana-cat-hero" style="padding-bottom:1.25rem">
    <div class="sana-container sana-cat-hero__inner sana-reveal">
      <div class="sana-cat-hero__breadcrumb">
        <a href="{{ route('home') }}">{{ $isRtl ? 'الرئيسية' : 'Home' }}</a>
        <span>/</span>
        <a href="{{ route('public.groups') }}">{{ __($g.'.title') }}</a>
        <span>/</span>
        <a href="{{ $group->isIndividual() ? route('public.instructors.index') : route('public.groups.courses') }}">
          {{ $group->isIndividual() ? __($g.'.catalog_solo_title') : __($g.'.catalog_group_title') }}
        </a>
        <span>/</span>
        <span>{{ $group->title }}</span>
      </div>
    </div>
  </section>

  <div class="sana-container gl-gs">
    @if(session('success'))
      <div class="gl-gs-alert gl-gs-alert--ok sana-reveal">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="gl-gs-alert gl-gs-alert--err sana-reveal">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
      </div>
    @endif

    <div class="gl-gs-grid">
      <article class="gl-gs-card sana-reveal">
        <div class="gl-gs-card__media">
          <img src="{{ $thumb }}" alt="{{ $group->title }}">
        </div>
        <div class="gl-gs-card__body">
          <span class="gl-gs-badge {{ $group->isIndividual() ? 'gl-gs-badge--gold' : '' }}">
            {{ $group->typeLabel() }}
          </span>
          <h1>{{ $group->title }}</h1>
          <div class="gl-gs-meta">
            <span><i class="fas fa-chalkboard-user"></i> {{ $group->instructor?->name }}</span>
            <span><i class="fas fa-clock"></i> {{ $group->duration_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}</span>
            @if($group->isCollective())
              <span><i class="fas fa-users"></i> {{ $isRtl ? 'سعة' : 'capacity' }} {{ $group->capacity }}</span>
            @endif
          </div>
          @if($group->description)
            <div class="gl-gs-desc">{{ $group->description }}</div>
          @endif
          <div class="gl-gs-price">{{ $group->formattedPrice() }}</div>
        </div>
      </article>

      @if($group->isCollective() && ($cohorts ?? collect())->isNotEmpty())
        <article class="gl-gs-card sana-reveal" style="grid-column:1/-1">
          <div class="gl-gs-form">
            <h2>{{ $isRtl ? 'الدفعات المتاحة' : 'Available cohorts' }}</h2>
            <div style="display:grid;gap:.75rem;@media(min-width:768px){grid-template-columns:1fr 1fr}">
              @foreach($cohorts as $cohort)
                <div style="border:1.5px solid #D7DDE6;border-radius:14px;padding:1rem;background:{{ $cohort->isEnrollmentOpen() ? '#fff' : '#F8FAFC' }}">
                  <div style="display:flex;justify-content:space-between;gap:.5rem;align-items:flex-start">
                    <strong style="font-size:.95rem;color:#0B1220">{{ $cohort->title }}</strong>
                    <span style="font-size:.68rem;font-weight:800;padding:.2rem .55rem;border-radius:999px;background:#E8EEF8;color:#0B3D91">{{ $cohort->statusLabel() }}</span>
                  </div>
                  <p style="margin:.45rem 0;font-size:.8rem;color:#5B6577;font-weight:600">
                    {{ $cohort->starts_at?->timezone($cohort->timezone ?: 'Africa/Cairo')->format('Y-m-d H:i') ?: '—' }}
                    · {{ $cohort->scheduleSummary() ?: ($isRtl ? 'أيام حسب الجدول' : 'schedule TBD') }}
                    @if($cohort->sessions_count)
                      · {{ $cohort->sessions_count }} {{ $isRtl ? 'حصة' : 'sessions' }}
                    @endif
                  </p>
                  <p style="margin:0 0 .75rem;font-size:.78rem;font-weight:700;color:#5B6577">
                    {{ $cohort->enrolled_count }}/{{ $cohort->capacity }} · {{ $cohort->seatsLeft() }} {{ $isRtl ? 'متبقي' : 'left' }}
                  </p>
                  @if($cohort->isEnrollmentOpen())
                    @auth
                      <a href="{{ route('public.groups.checkout', ['slug' => $group->slug, 'cohort' => $cohort->id]) }}" class="sana-btn sana-btn--yellow" style="width:100%;justify-content:center;padding:.55rem 1rem;font-size:.8rem">
                        {{ $isRtl ? 'اشترك في الدفعة' : 'Join cohort' }}
                      </a>
                    @else
                      <a href="{{ route('login') }}" class="sana-btn sana-btn--yellow" style="width:100%;justify-content:center;padding:.55rem 1rem;font-size:.8rem">
                        {{ $isRtl ? 'سجّل للالتحاق' : 'Login to join' }}
                      </a>
                    @endauth
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        </article>
      @endif

      @if($group->isIndividual() && ($packages ?? collect())->isNotEmpty())
        <article class="gl-gs-card sana-reveal" style="grid-column:1/-1">
          <div class="gl-gs-form">
            <h2>{{ $isRtl ? 'باقات الحصص الفردية' : 'Private session packages' }}</h2>
            <div style="display:grid;gap:.75rem;grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
              @foreach($packages as $package)
                <div style="border:1.5px solid {{ $package->is_featured ? '#F5B800' : '#D7DDE6' }};border-radius:14px;padding:1rem;{{ $package->is_featured ? 'box-shadow:0 8px 24px rgba(245,184,0,.25)' : '' }}">
                  @if($package->is_featured)
                    <span style="font-size:.65rem;font-weight:900;color:#9A7200">{{ $isRtl ? 'الأكثر طلباً' : 'Popular' }}</span>
                  @endif
                  <strong style="display:block;margin:.25rem 0;font-size:.95rem">{{ $package->name }}</strong>
                  @if($package->formattedOriginalPrice())
                    <div style="text-decoration:line-through;color:#94A3B8;font-size:.8rem">{{ $package->formattedOriginalPrice() }}</div>
                  @endif
                  <div style="font-size:1.2rem;font-weight:900;color:#0B3D91">{{ $package->formattedPrice() }}</div>
                  @if($package->savingsPercent() > 0)
                    <div style="font-size:.72rem;font-weight:800;color:#059669">{{ $isRtl ? 'وفر' : 'Save' }} {{ $package->savingsPercent() }}%</div>
                  @endif
                  <p style="margin:.5rem 0 .85rem;font-size:.75rem;color:#5B6577;font-weight:600">
                    {{ $package->sessions_count }} {{ $isRtl ? 'حصة' : 'sessions' }} · {{ $package->duration_months }} {{ $isRtl ? 'شهر' : 'mo' }}
                  </p>
                  @auth
                    <a href="{{ route('public.groups.checkout', ['slug' => $group->slug, 'package' => $package->id]) }}" class="sana-btn sana-btn--yellow" style="width:100%;justify-content:center;padding:.55rem 1rem;font-size:.78rem">
                      {{ $isRtl ? 'اشترك الآن' : 'Subscribe' }}
                    </a>
                  @else
                    <a href="{{ route('login') }}" class="sana-btn sana-btn--yellow" style="width:100%;justify-content:center;padding:.55rem 1rem;font-size:.78rem">
                      {{ $isRtl ? 'سجّل للاشتراك' : 'Login to subscribe' }}
                    </a>
                  @endauth
                </div>
              @endforeach
            </div>
            <p style="margin:1rem 0 0;font-size:.8rem;color:#5B6577;font-weight:600">
              {{ $isRtl ? 'أو احجز موعداً للمراجعة بدون دفع (ضيف / طلب يدوي):' : 'Or request a review booking without payment:' }}
            </p>
          </div>
        </article>
      @endif

      @php $servicePackages = $servicePackages ?? collect(); @endphp
      @if($servicePackages->isNotEmpty())
        <article class="gl-gs-card sana-reveal" style="grid-column:1/-1">
          <div class="gl-gs-form">
            <h2>{{ $isRtl ? 'باقات مناسبة لهذا الفصل' : 'Packages for this class' }}</h2>
            <p style="margin:0 0 1rem;font-size:.82rem;color:#5B6577;font-weight:600">
              {{ $isRtl ? 'اشترِ رصيداً ثم احجز من الرصيد بالأسفل مباشرة.' : 'Buy credits, then book instantly from your balance below.' }}
            </p>
            <div style="display:grid;gap:.75rem;grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
              @foreach($servicePackages as $package)
                <div style="border:1.5px solid {{ $package->is_featured ? '#F5B800' : '#D7DDE6' }};border-radius:14px;padding:1rem">
                  <strong style="display:block;margin:.25rem 0;font-size:.95rem">{{ $package->name }}</strong>
                  <div style="font-size:1.15rem;font-weight:900;color:#0B3D91">{{ $package->formattedPrice() }}</div>
                  <p style="margin:.5rem 0 .85rem;font-size:.75rem;color:#5B6577;font-weight:600">
                    {{ $package->units_count }} {{ $isRtl ? 'حصة' : 'sessions' }}
                    · {{ $package->sessionMinutes() }} {{ $isRtl ? 'د' : 'min' }}
                    @if($package->schoolProgramLabel())
                      · {{ $package->schoolProgramLabel() }}
                    @endif
                  </p>
                  <a href="{{ route('public.service-packages.checkout', $package) }}" class="sana-btn sana-btn--yellow" style="width:100%;justify-content:center;padding:.55rem 1rem;font-size:.78rem">
                    {{ $isRtl ? 'اشترِ الباقة' : 'Buy package' }}
                  </a>
                </div>
              @endforeach
            </div>
            <p style="margin:1rem 0 0;font-size:.8rem;color:#5B6577;font-weight:600">
              <a href="{{ route('public.service-packages.index', array_filter([
                'year' => $group->academic_year_id,
                'subject' => $group->academic_subject_id,
                'scope' => 'tutoring_collective',
              ])) }}" style="color:#0B3D91;text-decoration:underline;font-weight:800">
                {{ $isRtl ? 'عرض كل الباقات المناسبة' : 'See all matching packages' }}
              </a>
            </p>
          </div>
        </article>
      @endif

      <article class="gl-gs-card sana-reveal">
        <form method="POST" action="{{ route('public.groups.book', $group->slug) }}" class="gl-gs-form">
          @csrf
          @php $hasCredit = auth()->check() && (int) ($creditUnits ?? 0) > 0; @endphp
          <h2>
            @if($hasCredit)
              {{ $isRtl ? 'احجز من رصيدك' : 'Book with your credits' }}
            @else
              {{ $isRtl ? 'حجز موعد للمراجعة' : 'Request a slot (review)' }}
            @endif
          </h2>

          @if($hasCredit)
            <div class="gl-gs-alert gl-gs-alert--ok" style="margin-bottom:.85rem">
              {{ $isRtl ? 'رصيدك المتاح لهذه الخدمة:' : 'Available credits:' }}
              <strong>{{ (int) $creditUnits }}</strong>
              {{ $isRtl ? 'حصة — سيتم التأكيد فوراً وإنشاء غرفة Live.' : 'sessions — instant confirm + Live room.' }}
            </div>
            <input type="hidden" name="use_credit" value="1">
          @elseif(auth()->check())
            <p style="margin:0 0 .85rem;font-size:.82rem;font-weight:700;color:#5B6577">
              {{ $isRtl ? 'لا يوجد رصيد حصص.' : 'No session credits.' }}
              <a href="{{ route('public.service-packages.index', array_filter([
                'year' => $group->academic_year_id,
                'subject' => $group->academic_subject_id,
                'scope' => $group->isCollective() ? 'tutoring_collective' : 'tutoring_individual',
              ])) }}" style="color:#0B3D91;text-decoration:underline">{{ $isRtl ? 'اشترِ باقة مناسبة' : 'Buy a matching package' }}</a>
              {{ $isRtl ? 'أو أرسل طلب مراجعة أدناه.' : 'or send a review request below.' }}
            </p>
          @endif

          @if($group->isCollective() && ($cohorts ?? collect())->filter->isEnrollmentOpen()->isNotEmpty())
            <label class="gl-gs-label">{{ $isRtl ? 'الدفعة (اختياري)' : 'Cohort (optional)' }}</label>
            <select name="cohort_id" class="gl-gs-select" @disabled($hasCredit)>
              <option value="">—</option>
              @foreach($cohorts->filter->isEnrollmentOpen() as $cohort)
                <option value="{{ $cohort->id }}" @selected((string)old('cohort_id') === (string)$cohort->id)>{{ $cohort->title }}</option>
              @endforeach
            </select>
          @endif

          @if($slots->isEmpty())
            <p style="color:#5B6577;font-size:.9rem;font-weight:700;margin:0 0 1rem">{{ __($g.'.no_slots') }}</p>
            <a href="{{ $waUrl }}" class="sana-btn sana-btn--wa" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> WhatsApp</a>
          @else
            <label class="gl-gs-label">{{ $isRtl ? 'اختر الموعد' : 'Pick a slot' }}</label>
            <div class="gl-gs-slots">
              @foreach($slots as $slot)
                <label class="gl-gs-slot">
                  <input type="radio" name="starts_at" value="{{ $slot['starts_at'] }}" required @checked(old('starts_at') === $slot['starts_at'])>
                  <span>{{ $slot['label'] }}</span>
                  @if($group->isCollective() && isset($slot['seats_left']))
                    <span style="margin-inline-start:auto;color:#5B6577;font-size:.72rem">{{ $slot['seats_left'] }} {{ $isRtl ? 'متبقي' : 'left' }}</span>
                  @endif
                </label>
              @endforeach
            </div>

            @guest
              <label class="gl-gs-label" for="guest_name">{{ $isRtl ? 'الاسم' : 'Name' }}</label>
              <input id="guest_name" class="gl-gs-input" type="text" name="guest_name" value="{{ old('guest_name') }}" required>

              <label class="gl-gs-label" for="guest_phone">{{ $isRtl ? 'الهاتف' : 'Phone' }}</label>
              <input id="guest_phone" class="gl-gs-input" type="text" name="guest_phone" value="{{ old('guest_phone') }}">

              <label class="gl-gs-label" for="guest_email">{{ $isRtl ? 'البريد' : 'Email' }}</label>
              <input id="guest_email" class="gl-gs-input" type="email" name="guest_email" value="{{ old('guest_email') }}">
            @else
              <input type="hidden" name="guest_name" value="{{ $user->name }}">
              <input type="hidden" name="guest_email" value="{{ $user->email }}">
              <input type="hidden" name="guest_phone" value="{{ $user->phone }}">
              <p style="margin:0 0 .85rem;font-size:.82rem;font-weight:700;color:#5B6577">
                {{ $isRtl ? 'الحجز باسم' : 'Booking as' }}: {{ $user->name }}
              </p>
            @endguest

            @unless($hasCredit)
              <label class="gl-gs-label" for="student_notes">{{ $isRtl ? 'ملاحظات (اختياري)' : 'Notes (optional)' }}</label>
              <textarea id="student_notes" name="student_notes" rows="3" class="gl-gs-area">{{ old('student_notes') }}</textarea>
            @endunless

            <button type="submit" class="sana-btn sana-btn--yellow" style="width:100%;justify-content:center">
              <i class="fas fa-calendar-check"></i>
              {{ $hasCredit
                ? ($isRtl ? 'تأكيد الحجز من الرصيد + Live' : 'Confirm with credit + Live')
                : __($g.'.book_cta') }}
            </button>
          @endif
        </form>
      </article>
    </div>
  </div>
</main>

@include('partials.landing.footer')
</body>
</html>
