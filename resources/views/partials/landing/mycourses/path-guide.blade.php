{{-- فرّق المسارات بوضوح: باقات الحصص ≠ كورسات مستقلة ≠ مناهج --}}
@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $active = $pathGuideActive ?? ''; // packages | courses | curricula | instructors
    $items = [
        [
            'id' => 'instructors',
            'title' => $isRtl ? 'المعلم المناسب لحصتكم الخاصة الفردية' : 'The right teacher for your private lesson',
            'body' => $isRtl
                ? 'احجز معلم خصوصي معتمد يناسب المرحلة الدراسية والمادة لولدكم.'
                : 'Book an accredited private teacher matched to your child’s school stage and subject.',
            'cta' => $isRtl ? 'ابحث عن معلم خصوصي' : 'Find a private teacher',
            'href' => route('public.instructors.index'),
        ],
        [
            'id' => 'packages',
            'title' => $isRtl ? 'باقات الحصص الفردية أونلاين' : 'Private 1:1 online lesson packages',
            'body' => $isRtl
                ? 'رصيد ساعات مخصص لحجز الحصص الفردية (1:1) أونلاين، مو للكورسات المسجّلة.'
                : 'Hour credits for booking private 1:1 online lessons — not for recorded courses.',
            'cta' => $isRtl ? 'اختر باقة حصص' : 'Choose a package',
            'href' => route('public.pricing'),
        ],
        [
            'id' => 'courses',
            'title' => $isRtl ? 'الكورسات المستقلة أونلاين' : 'Independent online courses',
            'body' => $isRtl
                ? 'كورسات أونلاين جاهزة في القدرات واللغات والمهارات والقرآن. خارج المناهج الدراسية باشتراك مستقل لكل كورس.'
                : 'Ready online courses in aptitude, languages, skills, and Qur’an — outside school curricula, with a separate subscription per course.',
            'cta' => $isRtl ? 'تصفح الكورسات المستقلة' : 'Browse independent courses',
            'href' => route('public.courses'),
        ],
        [
            'id' => 'curricula',
            'title' => $isRtl ? 'حصص المناهج الدراسية الرسمية' : 'Official school curriculum lessons',
            'body' => $isRtl
                ? 'متابعة المنهج الدراسي لولدكم باختيار المرحلة والصف والمادة ونوع المنهج، ثم احجزوا مع معلم متخصص يناسب مقرره الدراسي.'
                : 'Follow your child’s school curriculum by picking stage, grade, subject, and curriculum type — then book a specialist teacher for the syllabus.',
            'cta' => $isRtl ? 'اكتشف المناهج الدراسية' : 'Discover school curricula',
            'href' => route('public.curricula'),
        ],
    ];
@endphp
<section class="mc-path-guide" aria-labelledby="mc-path-guide-title">
  <div class="mc-container">
    <div class="mc-section-head mc-section-head--tight" style="margin-bottom:1rem">
      <div>
        <p class="mc-eyebrow">{{ __('public.pricing_path_guide_eyebrow') }}</p>
        <h2 class="mc-title" id="mc-path-guide-title">{{ __('public.pricing_path_guide_title') }}</h2>
        <p class="mc-lead">{{ __('public.pricing_path_guide_lead') }}</p>
      </div>
    </div>
    <div class="mc-curr-split mc-path-guide__grid">
      @foreach($items as $item)
        <article class="mc-curr-split__card {{ $active === $item['id'] ? 'is-active' : '' }}">
          <h3>{{ $item['title'] }}</h3>
          <p>{{ $item['body'] }}</p>
          @if($active !== $item['id'])
            <a href="{{ $item['href'] }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $item['cta'] }}</a>
          @else
            <p class="mc-path-guide__here">{{ $isRtl ? 'أنت هنا الآن' : 'You are here' }}</p>
          @endif
        </article>
      @endforeach
    </div>
  </div>
</section>
