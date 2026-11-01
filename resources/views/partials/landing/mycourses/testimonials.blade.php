<section class="mc-section mc-section--compact" id="testimonials">
  <div class="mc-container dp-shell">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.testimonials.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.testimonials.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.testimonials.lead') }}</p>
        <p class="mc-lead" style="margin-top:.35rem;font-size:.9rem;opacity:.85">
          {{ app()->getLocale() === 'ar' ? 'قابل للتعديل من لوحة الإدارة → آراء الرئيسية.' : 'Editable from Admin → Home testimonials.' }}
        </p>
      </div>
      <a class="mc-link-more" href="{{ route('public.contact') }}?topic=review">{{ app()->getLocale() === 'ar' ? 'شاركنا رأيك' : 'Share your review' }}</a>
    </div>
    @php $quotes = $homeTestimonials ?? collect(); @endphp
    @if($quotes->isEmpty())
      <div class="mc-empty">{{ app()->getLocale() === 'ar' ? 'لا توجد آراء معروضة حالياً.' : 'No testimonials to show yet.' }}</div>
    @else
      <div class="mc-quotes-carousel" data-mc-quotes-carousel tabindex="0" aria-roledescription="carousel">
        <div class="mc-quotes-carousel__track">
        @foreach($quotes as $t)
          @php
            $body = $t->body ?? '';
            $name = $t->author_name ?? '—';
            $role = $t->role_label ?? '';
            $photo = $t->publicImageUrl();
            $initial = mb_substr($name, 0, 1);
          @endphp
          <div class="mc-quotes-carousel__slide">
          @if($t->isImageType() && $photo)
            <blockquote class="mc-quote mc-quote--image">
              <div class="mc-quote__media">
                <img src="{{ $photo }}" alt="{{ $name }}" width="640" height="480" loading="lazy" decoding="async">
              </div>
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
          </div>
        @endforeach
        </div>
      </div>
      <script>
      (function () {
        var root = document.querySelector('[data-mc-quotes-carousel]');
        if (!root) return;
        var track = root.querySelector('.mc-quotes-carousel__track');
        if (!track) return;
        var slides = track.querySelectorAll('.mc-quotes-carousel__slide');
        if (slides.length < 2) return;
        var index = 0;
        var paused = false;
        function scrollToIndex(i) {
          index = (i + slides.length) % slides.length;
          var slide = slides[index];
          if (!slide) return;
          track.scrollTo({ left: slide.offsetLeft - track.offsetLeft, behavior: 'smooth' });
        }
        root.addEventListener('pointerdown', function () { paused = true; });
        root.addEventListener('focusin', function () { paused = true; });
        setInterval(function () {
          if (paused) return;
          scrollToIndex(index + 1);
        }, 4000);
      })();
      </script>
    @endif
  </div>
</section>
