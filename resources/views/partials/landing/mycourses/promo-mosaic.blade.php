@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $items = [
        [
            'featured' => true,
            'title' => $isRtl ? 'اختر معلم يناسب ولدكم' : 'Choose a teacher for your child',
            'body' => $isRtl
                ? 'تعلموا مع معلمين معتمدين + اختاروا المواعيد اللي تناسبكم + احجزوا حصصكم بكل سهولة.'
                : 'Learn with accredited teachers, pick times that fit you, and book lessons easily.',
            'cta' => $isRtl ? 'اختاروا المعلم المناسب' : 'Choose the right teacher',
            'href' => route('public.instructors.index'),
        ],
        [
            'featured' => false,
            'title' => $isRtl ? 'المناهج الدراسية الرسمية' : 'Official school curricula',
            'body' => $isRtl
                ? 'نوع المنهج + مرحلة + مادة ثم المعلم المناسب لمقرر ولدكم الدراسي.'
                : 'Curriculum type + grade + subject, then the right teacher for your child’s syllabus.',
            'cta' => $isRtl ? 'اكتشفوا المناهج' : 'Discover curricula',
            'href' => route('public.curricula'),
        ],
        [
            'featured' => false,
            'title' => $isRtl ? 'كورسات أونلاين مستقلة' : 'Independent online courses',
            'body' => $isRtl
                ? 'محتوى تعليمي متنوع (قرآن، قدرات، لغات، مهارات) منفصل عن المنهج الدراسي يناسب تطوير اهتماماتكم وأهدافكم.'
                : 'Diverse learning content (Qur’an, aptitude, languages, skills) separate from school curricula.',
            'cta' => $isRtl ? 'تصفحوا الكورسات' : 'Browse courses',
            'href' => route('public.courses'),
        ],
    ];
@endphp
<section class="dp-mosaic" aria-labelledby="dp-mosaic-title">
  <div class="mc-container dp-shell">
    <div class="mc-section-head mc-section-head--tight" style="margin-bottom:1rem">
      <div>
        <p class="mc-eyebrow">{{ $isRtl ? 'ابدأ من هنا' : 'Start here' }}</p>
        <h2 class="mc-title" id="dp-mosaic-title">{{ $isRtl ? 'جميع المسارات الدراسية' : 'All learning paths' }}</h2>
        <p class="mc-lead">{{ $isRtl ? 'باقات الحصص بالساعات: رصيد لحصص فردية (1:1). الكورسات التدريبية: محتوى تعليمي خارج المنهج الدراسي.' : 'Hour packages: credits for 1:1 lessons. Training courses: learning content beyond the school curriculum.' }}</p>
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
        {{ $isRtl ? 'اكتشفوا باقات الحصص، وابدؤوا التعلم' : 'Discover lesson packages and start learning' }}
      </a>
      <a href="{{ route('public.courses') }}" class="mc-btn mc-btn--md mc-btn--outline">
        {{ $isRtl ? 'اختاروا كورساتكم الحين' : 'Choose your courses now' }}
      </a>
    </div>
  </div>
</section>
