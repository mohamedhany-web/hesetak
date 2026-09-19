<section class="lasles-features" id="features">
  <div class="lasles-container lasles-features__grid">
    <div class="lasles-features__art">
      <img src="{{ $img('features-illustration.png') }}" width="508" height="414" alt="" loading="lazy" decoding="async">
    </div>
    <div>
      <h2 class="lasles-section-title">
        {{ $isRtl ? 'نوفّر ميزات كثيرة يمكنك استخدامها' : 'We Provide Many Features You Can Use' }}
      </h2>
      <p class="lasles-section-lead">
        {{ $isRtl
          ? 'استكشف الميزات التي نوفّرها لدعم التشخيص والتطوير المهني وقياس التقدّم.'
          : 'You can explore the features that we provide with fun and have their own functions each feature.' }}
      </p>
      <ul class="lasles-checklist">
        <li><img src="{{ $img('check-green.svg') }}" width="24" height="24" alt="">{{ $isRtl ? 'تشخيص تحديات الممارسة الصفية.' : 'Powerfull online protection.' }}</li>
        <li><img src="{{ $img('check-green.svg') }}" width="24" height="24" alt="">{{ $isRtl ? 'ممارسات وأدوات تطبيقية.' : 'Internet without borders.' }}</li>
        <li><img src="{{ $img('check-green.svg') }}" width="24" height="24" alt="">{{ $isRtl ? 'تحديات لتطوير الأداء المهني.' : 'Supercharged VPN' }}</li>
        <li><img src="{{ $img('check-green.svg') }}" width="24" height="24" alt="">{{ $isRtl ? 'قياس واضح لتقدّمك.' : 'No specific time limits.' }}</li>
      </ul>
    </div>
  </div>
</section>
