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
      <style>
        [data-mc-quotes-carousel] { position: relative; overflow: hidden; }
        [data-mc-quotes-carousel] .mc-quotes-carousel__track {
          display: flex;
          gap: 1rem;
          transition: transform .45s ease;
          will-change: transform;
        }
        [data-mc-quotes-carousel] .mc-quotes-carousel__slide {
          flex: 0 0 min(100%, 22rem);
          max-width: min(100%, 22rem);
        }
        [data-mc-quotes-carousel] .mc-quote__media {
          aspect-ratio: 1 / 1;
          width: 100%;
          overflow: hidden;
          background: var(--mc-navy-soft, #eef2f8);
        }
        [data-mc-quotes-carousel] .mc-quote__media img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          object-position: center;
          display: block;
        }
        [data-mc-quotes-carousel] .mc-quotes-carousel__nav {
          display: flex; gap: .5rem; justify-content: center; margin-top: 1rem;
        }
        [data-mc-quotes-carousel] .mc-quotes-carousel__nav button {
          inline-size: 2.25rem; block-size: 2.25rem; border-radius: 999px;
          border: 1px solid color-mix(in srgb, var(--mc-ink, #0b1b33) 18%, transparent);
          background: var(--mc-surface, #fff); cursor: pointer;
        }
      </style>
      <script>
      (function () {
        var root = document.querySelector('[data-mc-quotes-carousel]');
        if (!root) return;
        var track = root.querySelector('.mc-quotes-carousel__track');
        if (!track) return;
        var slides = Array.prototype.slice.call(track.querySelectorAll('.mc-quotes-carousel__slide'));
        if (slides.length < 2) return;
        var index = 0;
        var paused = false;
        var resumeTimer = null;
        var isRtl = (document.documentElement.getAttribute('dir') || getComputedStyle(document.documentElement).direction || 'rtl') === 'rtl';

        function slideStep() {
          var first = slides[0];
          if (!first) return 0;
          var gap = parseFloat(getComputedStyle(track).gap || '0') || 0;
          return first.getBoundingClientRect().width + gap;
        }

        function goTo(i) {
          index = (i + slides.length) % slides.length;
          var delta = slideStep() * index;
          // في RTL نحرّك للاتجاه المعاكس حتى تظهر الشريحة التالية
          var x = isRtl ? delta : -delta;
          track.style.transform = 'translate3d(' + x + 'px,0,0)';
        }

        function pauseBriefly() {
          paused = true;
          if (resumeTimer) clearTimeout(resumeTimer);
          resumeTimer = setTimeout(function () { paused = false; }, 8000);
        }

        var nav = document.createElement('div');
        nav.className = 'mc-quotes-carousel__nav';
        nav.setAttribute('aria-hidden', 'false');
        var prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.setAttribute('aria-label', isRtl ? 'السابق' : 'Previous');
        prevBtn.textContent = isRtl ? '›' : '‹';
        var nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.setAttribute('aria-label', isRtl ? 'التالي' : 'Next');
        nextBtn.textContent = isRtl ? '‹' : '›';
        prevBtn.addEventListener('click', function () { pauseBriefly(); goTo(index - 1); });
        nextBtn.addEventListener('click', function () { pauseBriefly(); goTo(index + 1); });
        nav.appendChild(prevBtn);
        nav.appendChild(nextBtn);
        root.appendChild(nav);

        root.addEventListener('pointerdown', pauseBriefly);
        root.addEventListener('focusin', pauseBriefly);
        window.addEventListener('resize', function () { goTo(index); });
        goTo(0);
        setInterval(function () {
          if (paused) return;
          goTo(index + 1);
        }, 4000);
      })();
      </script>
    @endif
  </div>
</section>
