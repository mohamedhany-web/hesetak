<section class="mc-section mc-section--compact" id="instructors">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.instructors.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.instructors.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.instructors.lead') }}</p>
      </div>
      <a class="mc-link-more" href="{{ route('public.instructors.index') }}">{{ __('landing.mc.instructors.more') }} →</a>
    </div>
    @php $teachers = $homeInstructors ?? collect(); @endphp
    @if($teachers->isEmpty())
      <div class="mc-empty">{{ app()->getLocale() === 'ar' ? 'لا يوجد معلمون معتمدون للعرض حالياً.' : 'No approved teachers to show yet.' }}</div>
    @else
      <div class="mc-teachers">
        @foreach($teachers as $profile)
          @php
            $user = $profile->user;
            $name = $user?->name ?? '—';
            $photo = $profile->photo_url;
            $headline = trim((string) ($profile->headline ?: $profile->bio ?: $user?->bio ?: ''));
            $skills = trim((string) ($profile->skills ?? ''));
            $specialty = $skills !== '' ? $skills : ($headline !== '' ? \Illuminate\Support\Str::limit($headline, 80) : '');
            $experience = trim((string) ($profile->experience ?? ''));
          @endphp
          <article class="mc-teacher">
            <div class="mc-teacher__photo">
              @if($photo)
                <img src="{{ $photo }}" width="320" height="400" alt="{{ $name }}" loading="lazy" decoding="async">
              @else
                <div class="mc-teacher__photo-fallback" aria-hidden="true">{{ mb_substr($name, 0, 1) }}</div>
              @endif
            </div>
            <div class="mc-teacher__body">
              <h3>{{ $name }}</h3>
              @if($specialty !== '')
                <p class="mc-teacher__subject">{{ \Illuminate\Support\Str::limit($specialty, 72) }}</p>
              @endif
              @if($headline !== '' && $headline !== $specialty)
                <p class="mc-teacher__specialty">{{ \Illuminate\Support\Str::limit($headline, 110) }}</p>
              @endif
              @if($experience !== '')
                <p class="mc-teacher__meta">{{ \Illuminate\Support\Str::limit($experience, 60) }}</p>
              @endif
              <a href="{{ route('public.instructors.show', $user) }}" class="mc-btn mc-btn--sm mc-btn--secondary mc-teacher__cta">{{ __('landing.mc.instructors.card_cta') }}</a>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>
