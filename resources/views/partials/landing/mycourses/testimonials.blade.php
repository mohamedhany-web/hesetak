<section class="mc-section mc-section--compact" id="testimonials">
  <div class="mc-container dp-shell">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.testimonials.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.testimonials.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.testimonials.lead') }}</p>
      </div>
    </div>
    @php $quotes = $homeTestimonials ?? collect(); @endphp
    @if($quotes->isEmpty())
      <div class="mc-empty">{{ app()->getLocale() === 'ar' ? 'لا توجد آراء معروضة حالياً.' : 'No testimonials to show yet.' }}</div>
    @else
      <div class="mc-quotes">
        @foreach($quotes as $t)
          @php
            $body = $t->body ?? '';
            $name = $t->author_name ?? '—';
            $role = $t->role_label ?? '';
            $photo = $t->publicImageUrl();
            $initial = mb_substr($name, 0, 1);
          @endphp
          @if($t->isImageType() && $photo)
            <blockquote class="mc-quote mc-quote--image">
              <img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" decoding="async">
              <div class="mc-quote__who">
                <strong>{{ $name }}</strong>
                @if($role !== '')<span>{{ $role }}</span>@endif
              </div>
            </blockquote>
          @else
            <blockquote class="mc-quote">
              <p>“{{ $body }}”</p>
              <div class="mc-quote__who">
                @if($photo)
                  <img class="mc-quote__avatar mc-quote__avatar--img" src="{{ $photo }}" width="42" height="42" alt="" loading="lazy">
                @else
                  <span class="mc-quote__avatar" aria-hidden="true">{{ $initial }}</span>
                @endif
                <div>
                  <strong>{{ $name }}</strong>
                  @if($role !== '')<span>{{ $role }}</span>@endif
                </div>
              </div>
            </blockquote>
          @endif
        @endforeach
      </div>
    @endif
  </div>
</section>
