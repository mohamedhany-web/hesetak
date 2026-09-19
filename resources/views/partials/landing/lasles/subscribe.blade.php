<section class="lasles-container lasles-subscribe-wrap">
  <div class="lasles-subscribe">
    <div>
      <h2 class="lasles-subscribe__title">
        {{ $isRtl ? 'اشترك الآن واحصل على ميزات خاصة!' : 'Subscribe Now for Get Special Features!' }}
      </h2>
      <p class="lasles-subscribe__lead">{{ $isRtl ? 'لنستكشف معًا.' : "Let's subscribe with us and find the fun." }}</p>
    </div>
    <a href="{{ route('register') }}" class="lasles-btn-primary">{{ $isRtl ? 'اشترك الآن' : 'Subscribe Now' }}</a>
  </div>
</section>
