@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $brand = __('landing.nav.brand');
    $p = __('hesetak_pages.course_show');
    $footer = \App\Services\PublicFooterSettings::payload();
    $waUrl = $footer['whatsapp_url'] ?? '#';

    $thumbUrl = $course->thumbnail_url;
    $introVideoUrl = trim((string) ($course->video_url ?? ''));
    $introEmbedUrl = \App\Helpers\VideoHelper::getEmbedUrl($introVideoUrl);
    $introDirectVideo = \App\Helpers\VideoHelper::getDirectVideoUrl($introVideoUrl);

    $checkoutPrice = (float) $course->effectiveCheckoutPrice();
    $isPaid = $checkoutPrice > 0 && ! (bool) ($course->is_free ?? false);
    $hasPromo = $isPaid && $course->hasPromotionalPrice();
    $listPrice = $hasPromo ? (float) $course->listPriceAmount() : 0.0;
    $savedAmount = $hasPromo ? max(0, $listPrice - $checkoutPrice) : 0;
    $discountPct = ($hasPromo && $listPrice > 0)
        ? (int) round((1 - ($checkoutPrice / $listPrice)) * 100)
        : 0;
    $isMonthly = $course->isMonthlyBilling();
    $isEnrolled = (bool) ($isEnrolled ?? false);
    $instructorApproved = (bool) ($instructorApproved ?? false);

    $categoryDisplay = $course->courseCategory?->name ?? '';
    $hours = (int) ($course->duration_hours ?? 0);
    $lessonsCount = (int) ($course->lessons_count ?? 0);
    $level = trim((string) ($course->level ?? ''));
    $learnPoints = $course->what_you_learn
        ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $course->what_you_learn) ?: [])))
        : [];

    $checkoutUrl = route('public.course.checkout', $course->id);
    $showUrl = route('public.course.show', $course->id);
    $learnUrl = route('my-courses.show', $course);
    $loginUrl = route('login', ['redirect' => $isPaid ? $checkoutUrl : $showUrl]);
    $registerUrl = route('register', ['redirect' => $isPaid ? $checkoutUrl : $showUrl]);

    $primaryLabel = $isEnrolled
        ? $p['cta_learn']
        : ($isPaid ? $p['cta_buy'] : $p['cta_free']);

    $priceLabel = $isPaid
        ? format_money($checkoutPrice, $checkoutPrice == floor($checkoutPrice) ? 0 : 2)
        : $p['free'];
    if ($isPaid && $isMonthly) {
        $priceLabel .= ' / '.__('public.per_month');
    }

    $courseDesc = \Illuminate\Support\Str::limit(strip_tags((string) ($course->description ?? '')), 160);
    $pageTitle = ($course->title ?? $brand).' | '.$brand;
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $mcActive = 'courses';
    $relatedCourses = $relatedCourses ?? collect();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ $pageTitle }}</title>
  <meta name="description" content="{{ $courseDesc }}">
  <meta name="theme-color" content="#1E4E8C">
  <link rel="canonical" href="{{ $showUrl }}">
  <meta property="og:type" content="article">
  <meta property="og:url" content="{{ $showUrl }}">
  <meta property="og:title" content="{{ $pageTitle }}">
  <meta property="og:description" content="{{ $courseDesc }}">
  <meta property="og:image" content="{{ $thumbUrl ?? asset('images/og-image.jpg') }}">
  <meta property="og:site_name" content="{{ $brand }}">
  @include('partials.seo-jsonld', ['jsonldType' => 'course', 'course' => $course])
  @include('partials.favicon-links')
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
</head>
<body class="mc-body mc-body--cd">
@include('partials.landing.mycourses.nav')

<main class="mc-cd">
  <div class="mc-container">
    @foreach(['success' => 'ok', 'info' => 'info', 'error' => 'err'] as $flashKey => $flashTone)
      @if(session($flashKey))
        <div class="mc-cd-flash mc-cd-flash--{{ $flashTone }}" data-flash>
          <p>{{ session($flashKey) }}</p>
          <button type="button" data-flash-close aria-label="{{ $isRtl ? 'إغلاق' : 'Close' }}">×</button>
        </div>
      @endif
    @endforeach

    <nav class="mc-cd-crumb" aria-label="breadcrumb">
      <a href="{{ route('home') }}">{{ $p['crumb_home'] }}</a>
      <span>/</span>
      <a href="{{ route('public.courses') }}">{{ $p['crumb_courses'] }}</a>
      <span>/</span>
      <span>{{ \Illuminate\Support\Str::limit($course->title ?? '', 42) }}</span>
    </nav>

    {{-- Decision stage: media + buy --}}
    <section class="mc-cd-stage" aria-labelledby="mc-cd-title">
      <div class="mc-cd-stage__media">
        @if($introEmbedUrl)
          <iframe src="{{ $introEmbedUrl }}" title="{{ $course->title }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen loading="lazy"></iframe>
        @elseif($introDirectVideo)
          <video src="{{ $introDirectVideo }}" controls playsinline preload="metadata" poster="{{ $thumbUrl }}"></video>
        @elseif($thumbUrl)
          <img src="{{ $thumbUrl }}" alt="{{ $course->title }}" width="960" height="540">
        @else
          <div class="mc-cd-stage__empty" aria-hidden="true">
            <i class="fas fa-graduation-cap"></i>
          </div>
        @endif
      </div>

      <div class="mc-cd-desk" id="mc-cd-buy">
        <p class="mc-cd-desk__kicker">
          {{ $categoryDisplay !== '' ? $categoryDisplay : $p['eyebrow'] }}
          @if(!empty($course->is_featured))
            <span>· {{ $p['featured'] }}</span>
          @endif
        </p>
        <h1 id="mc-cd-title">{{ $course->title }}</h1>

        @if($course->instructor)
          <p class="mc-cd-desk__teacher">
            <span>{{ $p['instructor'] }}</span>
            @if($instructorApproved)
              <a href="{{ route('public.instructors.show', $course->instructor) }}">{{ $course->instructor->name }}</a>
            @else
              <strong>{{ $course->instructor->name }}</strong>
            @endif
          </p>
        @endif

        <ul class="mc-cd-meta" aria-label="{{ $p['details'] }}">
          @if($hours > 0)
            <li><strong>{{ $hours }}</strong><span>{{ $isRtl ? 'ساعة' : 'hours' }}</span></li>
          @endif
          @if($lessonsCount > 0)
            <li>
              <strong>{{ $lessonsCount }}</strong>
              <span>{{ $isRtl ? 'درس' : 'lessons' }}</span>
            </li>
          @endif
          @if($level !== '')
            <li><strong>{{ $level }}</strong><span>{{ $p['level'] }}</span></li>
          @endif
          <li>
            <strong>{{ ! $isPaid ? $p['free'] : ($isMonthly ? ($isRtl ? 'شهري' : 'Monthly') : ($isRtl ? 'مرة واحدة' : 'Once')) }}</strong>
            <span>{{ $isRtl ? 'الاشتراك' : 'Billing' }}</span>
          </li>
        </ul>

        <div class="mc-cd-price">
          @if($isEnrolled)
            <p class="mc-cd-price__enrolled">{{ $p['enrolled_note'] }}</p>
          @else
            <div class="mc-cd-price__row">
              <strong>{{ $priceLabel }}</strong>
              @if($hasPromo)
                <s>{{ format_money($listPrice, $listPrice == floor($listPrice) ? 0 : 2) }}</s>
                @if($discountPct > 0)
                  <em>{{ __('hesetak_pages.course_show.discount', ['pct' => $discountPct]) }}</em>
                @endif
              @endif
            </div>
            @if($savedAmount > 0)
              <p class="mc-cd-price__save">{{ $isRtl ? 'وفّرت' : 'You save' }} {{ format_money($savedAmount, 0) }}</p>
            @endif
            <p class="mc-cd-price__note">{{ $p['access_note'] }}</p>
          @endif
        </div>

        <div class="mc-cd-actions">
          @auth
            @if($isEnrolled)
              <a href="{{ $learnUrl }}" class="mc-btn mc-btn--lg mc-btn--secondary mc-cd-actions__primary">{{ $p['cta_learn'] }}</a>
            @elseif($isPaid)
              <a href="{{ $checkoutUrl }}" class="mc-btn mc-btn--lg mc-btn--secondary mc-cd-actions__primary">{{ $p['cta_buy'] }}</a>
            @else
              <form action="{{ route('public.course.enroll.free', $course->id) }}" method="post" class="mc-cd-actions__form">
                @csrf
                <button type="submit" class="mc-btn mc-btn--lg mc-btn--secondary mc-cd-actions__primary">{{ $p['cta_free'] }}</button>
              </form>
            @endif
          @else
            <a href="{{ $registerUrl }}" class="mc-btn mc-btn--lg mc-btn--secondary mc-cd-actions__primary">{{ $primaryLabel }}</a>
            <a href="{{ $loginUrl }}" class="mc-cd-actions__login">{{ $p['cta_login'] }}</a>
          @endauth
        </div>

        <ul class="mc-cd-trust">
          <li><i class="fas fa-shield-halved" aria-hidden="true"></i>{{ $p['trust_secure'] }}</li>
          <li><i class="fas fa-bolt" aria-hidden="true"></i>{{ $p['trust_fast'] }}</li>
          <li><i class="fas fa-headset" aria-hidden="true"></i>{{ $p['trust_support'] }}</li>
        </ul>
      </div>
    </section>

    {{-- Outcomes first (decision support) --}}
    @if(count($learnPoints) > 0)
      <section class="mc-cd-block" aria-labelledby="mc-cd-learn">
        <header class="mc-cd-block__head">
          <h2 id="mc-cd-learn">{{ $p['learn'] }}</h2>
          <p>{{ $isRtl ? 'ما يخرج به الطالب من هذا المسار.' : 'What the student walks away with.' }}</p>
        </header>
        <ol class="mc-cd-outcomes">
          @foreach($learnPoints as $i => $point)
            <li>
              <span aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
              <p>{{ $point }}</p>
            </li>
          @endforeach
        </ol>
      </section>
    @endif

    {{-- Story --}}
    <section class="mc-cd-block mc-cd-story" aria-labelledby="mc-cd-about">
      <header class="mc-cd-block__head">
        <h2 id="mc-cd-about">{{ $p['about'] }}</h2>
      </header>
      <div class="mc-cd-story__grid">
        <div class="mc-cd-prose">
          <p>{{ $course->description ?: $courseDesc }}</p>
          @if($course->objectives)
            <h3>{{ $p['objectives'] }}</h3>
            <p>{{ $course->objectives }}</p>
          @endif
          @if($course->requirements)
            <h3>{{ $p['requirements'] }}</h3>
            <p>{{ $course->requirements }}</p>
          @endif
        </div>
        <aside class="mc-cd-facts" aria-label="{{ $p['details'] }}">
          <h3>{{ $p['details'] }}</h3>
          <dl>
            @if($categoryDisplay !== '')
              <div><dt>{{ $p['category'] }}</dt><dd>{{ $categoryDisplay }}</dd></div>
            @endif
            @if($hours > 0)
              <div><dt>{{ __('public.duration') }}</dt><dd>{{ __('hesetak_pages.course_show.hours', ['n' => $hours]) }}</dd></div>
            @endif
            <div>
              <dt>{{ __('public.lectures_count_label') }}</dt>
              <dd>
                @if($lessonsCount > 0)
                  {{ __('hesetak_pages.course_show.lessons', ['n' => $lessonsCount]) }}
                @else
                  {{ $p['lessons_zero'] }}
                @endif
              </dd>
            </div>
            @if($level !== '')
              <div><dt>{{ $p['level'] }}</dt><dd>{{ $level }}</dd></div>
            @endif
            <div>
              <dt>{{ $isRtl ? 'الاشتراك' : 'Billing' }}</dt>
              <dd>{{ ! $isPaid ? $p['free'] : ($isMonthly ? $p['billing_monthly'] : $p['billing_once']) }}</dd>
            </div>
          </dl>
          @unless($isEnrolled)
            <a href="#mc-cd-buy" class="mc-btn mc-btn--md mc-btn--outline mc-cd-facts__cta">{{ $primaryLabel }}</a>
          @endunless
        </aside>
      </div>
    </section>

    @if($course->instructor)
      <section class="mc-cd-teacher" aria-labelledby="mc-cd-teacher-title">
        <div class="mc-cd-teacher__inner">
          <div class="mc-cd-teacher__avatar" aria-hidden="true">
            {{ mb_substr($course->instructor->name, 0, 1) }}
          </div>
          <div>
            <p class="mc-cd-teacher__label">{{ $p['instructor'] }}</p>
            <h2 id="mc-cd-teacher-title">{{ $course->instructor->name }}</h2>
            <p>{{ $isRtl ? 'معلم معتمد على حصتك لهذا المسار المستقل.' : 'A Hesetak teacher for this independent course path.' }}</p>
          </div>
          @if($instructorApproved)
            <a href="{{ route('public.instructors.show', $course->instructor) }}" class="mc-btn mc-btn--md mc-btn--soft">
              {{ $isRtl ? 'ملف المعلم' : 'Teacher profile' }}
            </a>
          @endif
        </div>
      </section>
    @endif

    @if($relatedCourses->isNotEmpty())
      <section class="mc-cd-related" aria-labelledby="mc-cd-related-title">
        <div class="mc-cd-related__head">
          <h2 id="mc-cd-related-title">{{ $p['related'] }}</h2>
          <a href="{{ route('public.courses') }}">{{ $p['related_all'] }}</a>
        </div>
        <div class="mc-cd-related__grid">
          @foreach($relatedCourses as $related)
            @php
              $rUrl = route('public.course.show', $related->id);
              $rPay = method_exists($related, 'effectiveCheckoutPrice') ? (float) $related->effectiveCheckoutPrice() : (float) ($related->price ?? 0);
              $rFree = (bool) ($related->is_free ?? false) || $rPay <= 0;
            @endphp
            <a href="{{ $rUrl }}" class="mc-cd-related__card">
              @if($related->thumbnail_url)
                <img src="{{ $related->thumbnail_url }}" alt="" loading="lazy">
              @else
                <span class="mc-cd-related__ph" aria-hidden="true"></span>
              @endif
              <div>
                @if($related->courseCategory?->name)
                  <small>{{ $related->courseCategory->name }}</small>
                @endif
                <strong>{{ $related->title }}</strong>
                <span>
                  @if($rFree)
                    {{ $p['free'] }}
                  @else
                    {{ format_money($rPay, $rPay == floor($rPay) ? 0 : 2) }}
                  @endif
                </span>
              </div>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    <p class="mc-cd-alt">
      {{ $p['need_1to1'] }}
      <a href="{{ route('public.instructors.index') }}">{{ $p['need_1to1_cta'] }}</a>
      ·
      <a href="{{ $waUrl }}" target="_blank" rel="noopener">{{ $p['cta_wa'] }}</a>
    </p>
  </div>
</main>

@unless($isEnrolled)
  <div class="mc-cd-dock" id="mc-cd-dock" role="region" aria-label="{{ $primaryLabel }}">
    <div class="mc-cd-dock__inner">
      <div>
        <span>{{ \Illuminate\Support\Str::limit($course->title ?? '', 26) }}</span>
        <strong>{{ $priceLabel }}</strong>
      </div>
      @auth
        @if($isPaid)
          <a href="{{ $checkoutUrl }}" class="mc-btn mc-btn--md mc-btn--secondary">{{ $p['cta_buy'] }}</a>
        @else
          <form action="{{ route('public.course.enroll.free', $course->id) }}" method="post">
            @csrf
            <button type="submit" class="mc-btn mc-btn--md mc-btn--secondary">{{ $p['cta_free'] }}</button>
          </form>
        @endif
      @else
        <a href="{{ $registerUrl }}" class="mc-btn mc-btn--md mc-btn--secondary">{{ $primaryLabel }}</a>
      @endauth
    </div>
  </div>
  <script>
  (function(){
    var dock = document.getElementById('mc-cd-dock');
    var buy = document.getElementById('mc-cd-buy');
    if (!dock || !buy || !('IntersectionObserver' in window)) return;
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){ dock.classList.toggle('is-away', e.isIntersecting); });
    }, { rootMargin: '-12% 0px -40% 0px', threshold: 0.15 });
    io.observe(buy);
  })();
  </script>
@endunless

@include('partials.landing.mycourses.footer')
<script>
(function () {
  document.querySelectorAll('[data-flash-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var box = btn.closest('[data-flash]');
      if (box) box.remove();
    });
  });
})();
</script>
</body>
</html>
