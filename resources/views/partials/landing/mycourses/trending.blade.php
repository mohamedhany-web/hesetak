<section class="mc-section mc-section--compact" id="trending">
  <div class="mc-container dp-shell">
    @php $teachers = ($homeInstructors ?? collect())->take(4); @endphp
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ app()->getLocale() === 'ar' ? 'الأكثر تداولًا' : 'Trending' }}</p>
        <h2 class="mc-title">{{ $teachers->isEmpty()
            ? (app()->getLocale() === 'ar' ? 'اختر معلمك من الدليل' : 'Choose a teacher from the directory')
            : (app()->getLocale() === 'ar' ? 'معلمون معتمدون جاهزون للحجز' : 'Accredited teachers ready to book') }}</h2>
        <p class="mc-lead">{{ app()->getLocale() === 'ar' ? 'ملفات معتمدة جاهزة للحجز والمتابعة.' : 'Approved profiles ready to book and follow.' }}</p>
      </div>
      <a class="mc-link-more" href="{{ route('public.instructors.index') }}">{{ __('landing.mc.instructors.more') }} →</a>
    </div>
    @if($teachers->isEmpty())
      <div class="mc-empty">
        <p>{{ app()->getLocale() === 'ar' ? 'دليل المعلمين يُحدَّث الآن. تصفّح الصفحة الكاملة أو احجز من الباقات.' : 'The teacher directory is being updated. Open the full list or book from packages.' }}</p>
        <a class="mc-btn mc-btn--sm mc-btn--primary" href="{{ route('public.instructors.index') }}">{{ __('landing.mc.instructors.more') }}</a>
      </div>
    @else
      <div class="mc-teachers mc-teachers--dir">
        @foreach($teachers as $profile)
          @php
            $user = $profile->user;
            $name = $user?->name ?? __('public.instructor_fallback');
            $photo = $profile->photo_url;
            $headline = trim((string) ($profile->headline ?: ''));
            $skills = array_slice($profile->skills_list ?? [], 0, 2);
            $url = route('public.instructors.show', $user);
          @endphp
          <article class="mc-teacher mc-teacher--sm">
            <a href="{{ $url }}" class="mc-teacher__photo" tabindex="-1" aria-hidden="true">
              @if($photo)
                <img src="{{ $photo }}" width="240" height="240" alt="{{ $isRtl ?? app()->getLocale() === 'ar' ? 'صورة المعلم '.$name.' في منصة حصتك' : 'Photo of teacher '.$name.' on Hesetak' }}" loading="lazy" decoding="async">
              @else
                <div class="mc-teacher__photo-fallback" aria-hidden="true">{{ mb_substr($name, 0, 1) }}</div>
              @endif
            </a>
            <div class="mc-teacher__body">
              <h3><a href="{{ $url }}">{{ $name }}</a></h3>
              @if(count($skills) > 0)
                <p class="mc-teacher__subject">{{ implode('، ', $skills) }}</p>
              @elseif($headline !== '')
                <p class="mc-teacher__specialty">{{ \Illuminate\Support\Str::limit($headline, 48) }}</p>
              @endif
              <div class="mc-teacher__footer">
                <span class="mc-teacher__meta">{{ __('public.instructors_verified') }}</span>
                <a href="{{ $url }}" class="mc-btn mc-btn--sm mc-btn--secondary mc-teacher__cta">{{ __('landing.mc.instructors.card_cta') }}</a>
              </div>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>
