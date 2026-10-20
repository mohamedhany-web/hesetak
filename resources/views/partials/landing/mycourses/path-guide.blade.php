{{-- فرّق المسارات بوضوح: باقات الحصص ≠ كورسات مستقلة ≠ مناهج --}}
@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $active = $pathGuideActive ?? ''; // packages | courses | curricula | instructors
    $items = [
        [
            'id' => 'instructors',
            'title' => $isRtl ? 'حصص فردية 1:1' : '1:1 private lessons',
            'body' => $isRtl
                ? 'احجز معلماً معتمداً لمرحلة ومادة ابنك. الدفع عادة من رصيد باقة الحصص.'
                : 'Book an accredited teacher for your child’s grade and subject. You usually pay from a lesson-package balance.',
            'cta' => $isRtl ? 'ابحث عن معلم' : 'Find a teacher',
            'href' => route('public.instructors.index'),
        ],
        [
            'id' => 'packages',
            'title' => $isRtl ? 'باقات الحصص' : 'Lesson packages',
            'body' => $isRtl
                ? 'رصيد ساعات للحجز 1:1 فقط. ليست كورساً مسجّلاً. تقدر تغيّر المعلم بلا خسارة الرصيد.'
                : 'Hour credits for 1:1 booking only — not a recorded course. Switch teachers without losing credit.',
            'cta' => $isRtl ? 'اختر باقة حصص' : 'Choose a package',
            'href' => route('public.pricing'),
        ],
        [
            'id' => 'courses',
            'title' => $isRtl ? 'كورسات مستقلة' : 'Independent courses',
            'body' => $isRtl
                ? 'محتوى جاهز (قدرات، لغات، مهارات، قرآن) خارج متابعة المنهج المدرسي. اشتراك بالكورس نفسه.'
                : 'Ready-made content (aptitude, languages, skills, Quran) outside school follow-up. You subscribe to the course itself.',
            'cta' => $isRtl ? 'تصفّح الكورسات' : 'Browse courses',
            'href' => route('public.courses'),
        ],
        [
            'id' => 'curricula',
            'title' => $isRtl ? 'المناهج الرسمية' : 'Official curricula',
            'body' => $isRtl
                ? 'مسار متابعة مقرر المدرسة: مرحلة + مادة + نوع منهج، ثم معلم مطابق.'
                : 'School curriculum follow-up: grade + subject + curriculum type, then a matching teacher.',
            'cta' => $isRtl ? 'استكشف المناهج' : 'Explore curricula',
            'href' => route('public.curricula'),
        ],
    ];
@endphp
<section class="mc-path-guide" aria-labelledby="mc-path-guide-title">
  <div class="mc-container">
    <div class="mc-section-head mc-section-head--tight" style="margin-bottom:1rem">
      <div>
        <p class="mc-eyebrow">{{ $isRtl ? 'مهم قبل ما تختار' : 'Know before you choose' }}</p>
        <h2 class="mc-title" id="mc-path-guide-title">{{ $isRtl ? 'باقات الحصص غير الكورسات المستقلة' : 'Lesson packages are not independent courses' }}</h2>
        <p class="mc-lead">{{ $isRtl ? 'كل صفحة هدف واحد. اختر المسار المناسب ثم اضغط زرّه فقط.' : 'Each page has one job. Pick the right path, then use its button only.' }}</p>
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
