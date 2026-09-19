@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $items = [
        [
            'featured' => true,
            'title' => $isRtl ? 'حصص فردية 1:1' : 'Private 1:1 lessons',
            'body' => $isRtl ? 'معلم معتمد، مواعيد مرنة، وتقرير تقدّم بعد كل حصة.' : 'Approved teacher, flexible timing, and a progress report after every session.',
            'cta' => $isRtl ? 'ابحث عن معلم' : 'Find a teacher',
            'href' => route('public.instructors.index'),
        ],
        [
            'featured' => false,
            'title' => $isRtl ? 'المناهج الرسمية' : 'Official curricula',
            'body' => $isRtl ? 'مسارات خليجية واضحة حسب المرحلة والمادة.' : 'Clear Gulf curriculum paths by grade and subject.',
            'cta' => $isRtl ? 'استكشف المناهج' : 'Explore curricula',
            'href' => route('public.curricula'),
        ],
        [
            'featured' => false,
            'title' => $isRtl ? 'كورسات مستقلة' : 'Independent courses',
            'body' => $isRtl ? 'مهارات وقدرات ولغات خارج المنهج الوطني.' : 'Skills, aptitude, and languages beyond the national curriculum.',
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
        <h2 class="mc-title" id="dp-mosaic-title">{{ $isRtl ? 'اختر مسارك التعليمي' : 'Choose your learning path' }}</h2>
        <p class="mc-lead">{{ $isRtl ? 'ثلاثة مسارات واضحة لولي الأمر والطالب.' : 'Three clear paths for parents and students.' }}</p>
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
  </div>
</section>
