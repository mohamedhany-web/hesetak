<section class="lasles-reviews" id="testimonials">
  <div class="lasles-container">
    <h2 class="lasles-section-title">
      {{ $isRtl ? 'موثوق من آلاف المعلمين السعداء' : 'Trusted by Thousands of Happy Customer' }}
    </h2>
    <p class="lasles-section-lead">
      {{ $isRtl
        ? 'هذه قصص معلمين انضموا بثقة واستفادوا من أدوات التشخيص والتطوير.'
        : 'These are the stories of our customers who have joined us with great pleasure when using this crazy feature.' }}
    </p>
    <div class="lasles-reviews__track">
      <article class="lasles-review is-active">
        <div class="lasles-review__top">
          <img src="{{ $img('avatar-1.png') }}" width="50" height="50" alt="">
          <div>
            <p class="lasles-review__name">Viezh Robert</p>
            <p class="lasles-review__loc">Warsaw, Poland</p>
          </div>
          <div class="lasles-review__rating">4.5 <img src="{{ $img('star.svg') }}" width="14" height="14" alt=""></div>
        </div>
        <p class="lasles-review__quote">
          “Wow... I am very happy to use this platform, it turned out to be more than my expectations and so far there have been no problems. {{ $brand }} always the best”.
        </p>
      </article>
      <article class="lasles-review">
        <div class="lasles-review__top">
          <img src="{{ $img('avatar-2.png') }}" width="50" height="50" alt="">
          <div>
            <p class="lasles-review__name">Yessica Christy</p>
            <p class="lasles-review__loc">Shanxi, China</p>
          </div>
          <div class="lasles-review__rating">4.5 <img src="{{ $img('star.svg') }}" width="14" height="14" alt=""></div>
        </div>
        <p class="lasles-review__quote">
          “I like it because I like to travel far and still can connect with high speed.”.
        </p>
      </article>
      <article class="lasles-review">
        <div class="lasles-review__top">
          <img src="{{ $img('avatar-3.png') }}" width="50" height="50" alt="">
          <div>
            <p class="lasles-review__name">Kim Young Jou</p>
            <p class="lasles-review__loc">Seoul, South Korea</p>
          </div>
          <div class="lasles-review__rating">4.5 <img src="{{ $img('star.svg') }}" width="14" height="14" alt=""></div>
        </div>
        <p class="lasles-review__quote">
          “This is very unusual for my practice that currently requires strong tools and professional support.”.
        </p>
      </article>
    </div>
    <div class="lasles-reviews__controls">
      <div class="lasles-dots" aria-hidden="true">
        <span class="is-on"></span><span></span><span></span><span></span>
      </div>
      <div class="lasles-reviews__arrows">
        <button type="button" aria-label="{{ $isRtl ? 'السابق' : 'Previous' }}"><img src="{{ $img('arrow-left.svg') }}" width="50" height="50" alt=""></button>
        <button type="button" aria-label="{{ $isRtl ? 'التالي' : 'Next' }}"><img src="{{ $img('arrow-right.svg') }}" width="50" height="50" alt=""></button>
      </div>
    </div>
  </div>
</section>
