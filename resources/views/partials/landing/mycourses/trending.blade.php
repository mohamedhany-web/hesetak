<section class="mc-section mc-section--compact" id="trending">
  <div class="mc-container dp-shell">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ app()->getLocale() === 'ar' ? 'الأكثر تداولًا' : 'Trending' }}</p>
        <h2 class="mc-title">{{ app()->getLocale() === 'ar' ? 'معلمون يبحث عنهم الأهالي الآن' : 'Teachers families are exploring now' }}</h2>
        <p class="mc-lead">{{ app()->getLocale() === 'ar' ? 'ملفات معتمدة جاهزة للحجز والمتابعة.' : 'Approved profiles ready to book and follow.' }}</p>
      </div>
      <a class="mc-link-more" href="{{ route('public.instructors.index') }}">{{ __('landing.mc.instructors.more') }} →</a>
    </div>
    @php $teachers = ($homeInstructors ?? collect())->take(4); @endphp
    @if($teachers->isEmpty())
      <div class="mc-empty">{{ app()->getLocale() === 'ar' ? 'لا يوجد معلمون معتمدون للعرض حالياً.' : 'No approved teachers to show yet.' }}</div>
    @else
      <div class="mc-teachers mc-teachers--dir">
        @foreach($teachers as $profile)
          @php
            $user = $profile->user;
            $name = $user?->name ?? __('public.instructor_fallback');
            $photoFallbacks = [
                public_img_url('lasles/avatar-1.png'),
                public_img_url('lasles/avatar-2.png'),
                public_img_url('lasles/avatar-3.png'),
                public_img_url('mycourses/hero-saudi-student.png'),
            ];
            $photo = $profile->photo_url ?: $photoFallbacks[$loop->index % count($photoFallbacks)];
            $headline = trim((string) ($profile->headline ?: ''));
            $skills = array_slice($profile->skills_list ?? [], 0, 2);
            $url = route('public.instructors.show', $user);
          @endphp
          <article class="mc-teacher mc-teacher--sm">
            <a href="{{ $url }}" class="mc-teacher__photo" tabindex="-1" aria-hidden="true">
              <img src="{{ $photo }}" width="240" height="240" alt="" loading="{{ request()->boolean('figma') ? 'eager' : 'lazy' }}" decoding="async">
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
