@php
    $courses = $featuredCourses ?? collect();
@endphp
<section class="mc-section mc-section--muted" id="courses">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.courses.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.courses.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.courses.lead') }}</p>
      </div>
      <a class="mc-link-more" href="{{ route('public.courses') }}">{{ __('landing.mc.courses.more') }} →</a>
    </div>

    @if($courses->isEmpty())
      <div class="mc-empty">{{ __('landing.mc.courses.empty') }}</div>
    @else
      <div class="mc-grid mc-grid--3">
        @foreach($courses->take(6) as $course)
          @php
            $title = $course->title ?? $course->name ?? __('landing.mc.courses.untitled');
            $url = route('public.course.show', $course->id ?? $course);
            $price = $course->price ?? $course->sale_price ?? null;
            $cover = $course->thumbnail_url
                ?? $course->image_url
                ?? (isset($course->thumbnail) ? asset('storage/'.$course->thumbnail) : null);
          @endphp
          <a href="{{ $url }}" class="mc-card">
            <div class="mc-card__media">
              @if($cover)
                <img src="{{ $cover }}" alt="" loading="lazy" decoding="async">
              @endif
              @if(!empty($course->is_featured))
                <span class="mc-card__badge">{{ __('landing.mc.courses.featured') }}</span>
              @endif
            </div>
            <div class="mc-card__body">
              <h3 class="mc-card__title">{{ $title }}</h3>
              <div class="mc-card__meta">
                @if(!empty($course->instructor?->name))
                  <span>{{ $course->instructor->name }}</span>
                @endif
              </div>
              <div class="mc-card__foot">
                <span class="mc-card__price">
                  @if($price !== null && $price !== '')
                    {{ number_format((float) $price, 0) }} {{ currency_symbol() }}
                  @else
                    {{ __('landing.mc.courses.view') }}
                  @endif
                </span>
                @if(!empty($course->lessons_count))
                  <span class="mc-card__rating">{{ $course->lessons_count }} {{ app()->getLocale() === 'ar' ? 'درس' : 'lessons' }}</span>
                @endif
              </div>
            </div>
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>
