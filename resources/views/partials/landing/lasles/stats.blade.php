<section class="lasles-container lasles-stats-wrap" aria-label="{{ $isRtl ? 'إحصائيات' : 'Stats' }}">
  <div class="lasles-stats">
    <div class="lasles-stats__item">
      <div class="lasles-stats__icon"><img src="{{ $img('icon-user.svg') }}" width="24" height="24" alt=""></div>
      <div>
        <p class="lasles-stats__num">90+</p>
        <p class="lasles-stats__label">{{ $isRtl ? 'معلمون' : 'Users' }}</p>
      </div>
    </div>
    <div class="lasles-stats__divider" aria-hidden="true"></div>
    <div class="lasles-stats__item">
      <div class="lasles-stats__icon"><img src="{{ $img('icon-location.svg') }}" width="24" height="24" alt=""></div>
      <div>
        <p class="lasles-stats__num">30+</p>
        <p class="lasles-stats__label">{{ $isRtl ? 'ممارسات' : 'Locations' }}</p>
      </div>
    </div>
    <div class="lasles-stats__divider" aria-hidden="true"></div>
    <div class="lasles-stats__item">
      <div class="lasles-stats__icon"><img src="{{ $img('icon-server.svg') }}" width="24" height="24" alt=""></div>
      <div>
        <p class="lasles-stats__num">50+</p>
        <p class="lasles-stats__label">{{ $isRtl ? 'أدوات' : 'Servers' }}</p>
      </div>
    </div>
  </div>
</section>
