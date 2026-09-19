<section class="lasles-hero" id="about">
  <div class="lasles-container lasles-hero__grid">
    <div class="lasles-hero__copy">
      <h1 class="lasles-hero__title">
        @if($isRtl)
          طوّر ممارستك المهنية بسهولة مع <strong>{{ $brandAr }}.</strong>
        @else
          Want anything to be easy with <strong>{{ $brand }}.</strong>
        @endif
      </h1>
      <p class="lasles-hero__lead">
        @if($isRtl)
          منصة تساعد المعلم على تشخيص تحديات الممارسة الصفية، والوصول إلى ممارسات وأدوات وتحديات تطبيقية لتطوير أدائه وقياس تقدّمه.
        @else
          Provide a network for all your needs with ease and fun using <strong>{{ $brand }}</strong> discover interesting features from us.
        @endif
      </p>
      <div class="lasles-hero__actions">
        <a href="{{ route('register') }}" class="lasles-btn-primary">{{ $isRtl ? 'ابدأ الآن' : 'Get Started' }}</a>
        <a href="#path" class="lasles-btn-outline">{{ __('landing.nav.path') }}</a>
      </div>
    </div>
    <div class="lasles-hero__art">
      <img src="{{ $img('hero-illustration.svg') }}" width="611" height="382" alt="" decoding="async" fetchpriority="high">
    </div>
  </div>
</section>
