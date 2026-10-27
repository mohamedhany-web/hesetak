@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $items = [
        [
            'featured' => true,
            'title' => $isRtl ? 'حصص فردية 1:1' : 'Private 1:1 lessons',
            'body' => $isRtl ? 'معلم معتمد + مواعيد مرنة. ادفع بباقة حصص (رصيد ساعات) ثم احجز.' : 'Accredited teacher + flexible timing. Pay with a lesson package (hour credits), then book.',
            'cta' => $isRtl ? 'ابحث عن معلم' : 'Find a teacher',
            'href' => route('public.instructors.index'),
        ],
        [
            'featured' => false,
            'title' => $isRtl ? 'متابعة المناهج الرسمية' : 'Follow the official curriculum',
            'body' => $isRtl ? 'مرحلة + مادة + نوع منهج، ثم معلم مطابق لمقرر المدرسة.' : 'Grade + subject + curriculum type, then a teacher matched to the school syllabus.',
            'cta' => $isRtl ? 'استكشف المناهج' : 'Explore curricula',
            'href' => route('public.curricula'),
        ],
        [
            'featured' => false,
            'title' => $isRtl ? 'كورسات مستقلة' : 'Independent courses',
            'body' => $isRtl ? 'محتوى جاهز خارج المنهج المدرسي (قدرات، لغات، مهارات). هذا غير باقات الحصص.' : 'Ready-made content outside school (aptitude, languages, skills). This is not a lesson package.',
            'cta' => $isRtl ? 'تصفّح الكورسات' : 'Browse courses',
            'href' => route('public.courses'),
        ],
    ];
@endphp
<section class="dp-mosaic" aria-labelledby="dp-mosaic-title">
  <div class="mc-container dp-shell">
    <div class="mc-section-head mc-section-head--tight" style="margin-bottom:1rem">
      <div>
        <p class="mc-eyebrow">{{ $isRtl ? 'ابدأ من هنا' : 'Start here' }}</p>
        <h2 class="mc-title" id="dp-mosaic-title">{{ $isRtl ? 'اختر مساراً واحداً بوضوح' : 'Pick one clear learning path' }}</h2>
        <p class="mc-lead">{{ $isRtl ? 'باقات الحصص = رصيد لحصص 1:1. الكورسات المستقلة = محتوى منفصل باشتراك.' : 'Lesson packages = credits for 1:1. Independent courses = separate subscribed content.' }}</p>
      </div>
    </div>
    <div class="dp-mosaic__grid">
      @foreach($items as $item)
        <a href="{{ $item['href'] }}" class="dp-mosaic__card {{ !empty($item['featured']) ? 'dp-mosaic__card--featured' : '' }}">
          <h3>{{ $item['title'] }}</h3>
          <p>{{ $item['body'] }}</p>
          <span class="dp-mosaic__cta">{{ $item['cta'] }} →</span>
        </a>
      @endforeach
    </div>
    <div style="margin-top:1.25rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:center">
      <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--md mc-btn--secondary">
        {{ $isRtl ? 'جاهز تشتري رصيد حصص؟ افتح باقات الحصص' : 'Ready to buy hour credits? Open lesson packages' }}
      </a>
      <a href="{{ route('public.pricing') }}#mc-pricing-storefront" class="mc-btn mc-btn--md mc-btn--outline">
        {{ $isRtl ? 'عرض باقات الحصص المعلنة' : 'See listed lesson packages' }}
      </a>
    </div>
  </div>
</section>
