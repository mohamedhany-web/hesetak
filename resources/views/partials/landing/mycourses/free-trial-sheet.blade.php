{{-- Marketing bottom sheet: يوجّه لصفحة المعلمين (بدون حجز مواعيد تجريبية من الإدارة) --}}
@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $autoOpenPromo = (bool) ($autoOpenPromo ?? $autoOpenTrial ?? false);
    $promoTeachers = collect($trialInstructors ?? $homeInstructors ?? [])->take(3)->values();
    $instructorsUrl = route('public.instructors.index');
@endphp

<div
  id="mc-promo-sheet"
  class="mc-trial-sheet"
  hidden
  data-auto-open="{{ $autoOpenPromo ? '1' : '0' }}"
  aria-hidden="true"
>
  <button type="button" class="mc-trial-sheet__backdrop" data-mc-promo-close tabindex="-1" aria-label="{{ __('landing.academy.promo_sheet_close') }}"></button>

  <div class="mc-trial-sheet__panel mc-promo-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="mc-promo-title">
    <div class="mc-trial-sheet__grab" aria-hidden="true"></div>

    <header class="mc-trial-sheet__head">
      <div>
        <p class="mc-trial-sheet__eyebrow">{{ __('landing.academy.promo_sheet_eyebrow') }}</p>
        <h2 id="mc-promo-title">{{ __('landing.academy.promo_sheet_title') }}</h2>
        <p class="mc-trial-sheet__sub">{{ __('landing.academy.promo_sheet_sub') }}</p>
      </div>
      <button type="button" class="mc-trial-sheet__x" data-mc-promo-close aria-label="{{ __('landing.academy.promo_sheet_close') }}">×</button>
    </header>

    <div class="mc-trial-sheet__body mc-promo-sheet__body">
      @if($promoTeachers->isNotEmpty())
        <ul class="mc-promo-sheet__teachers">
          @foreach($promoTeachers as $profile)
            @php
              $user = $profile->user ?? null;
              $name = $user?->name ?? __('public.instructor_fallback');
              $photo = $profile->photo_url ?? '';
              $headline = trim((string) ($profile->headline_clean ?? $profile->headline ?? ''));
              $url = $user ? route('public.instructors.show', $user) : $instructorsUrl;
            @endphp
            <li>
              <a href="{{ $url }}" class="mc-promo-sheet__teacher">
                @if($photo)
                  <img src="{{ $photo }}" alt="" width="48" height="48" loading="lazy" decoding="async">
                @else
                  <span class="mc-promo-sheet__avatar" aria-hidden="true">{{ mb_substr($name, 0, 1) }}</span>
                @endif
                <span class="mc-promo-sheet__meta">
                  <strong>{{ $name }}</strong>
                  @if($headline !== '')
                    <span>{{ \Illuminate\Support\Str::limit($headline, 42) }}</span>
                  @endif
                </span>
                <span class="mc-promo-sheet__go" aria-hidden="true">{{ $isRtl ? '‹' : '›' }}</span>
              </a>
            </li>
          @endforeach
        </ul>
      @endif

      <a href="{{ $instructorsUrl }}" class="mc-btn mc-btn--lg mc-btn--secondary mc-promo-sheet__cta">
        {{ __('landing.academy.promo_sheet_cta') }}
      </a>
    </div>

    <footer class="mc-trial-sheet__foot">
      <button type="button" class="mc-trial-sheet__dismiss" data-mc-promo-close data-mc-promo-dismiss>
        {{ __('landing.academy.promo_sheet_dismiss') }}
      </button>
    </footer>
  </div>
</div>

<script>
(function () {
  var root = document.getElementById('mc-promo-sheet');
  if (!root) return;

  var STORAGE_KEY = 'hesetak_promo_sheet_dismissed';

  function openSheet() {
    root.hidden = false;
    root.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('mc-trial-open');
    requestAnimationFrame(function () {
      root.classList.add('is-open');
    });
  }

  function closeSheet(persistDismiss) {
    root.classList.remove('is-open');
    document.documentElement.classList.remove('mc-trial-open');
    window.setTimeout(function () {
      root.hidden = true;
      root.setAttribute('aria-hidden', 'true');
    }, 780);
    if (persistDismiss) {
      try { sessionStorage.setItem(STORAGE_KEY, '1'); } catch (e) {}
    }
  }

  root.querySelectorAll('[data-mc-promo-close]').forEach(function (el) {
    el.addEventListener('click', function () {
      closeSheet(el.hasAttribute('data-mc-promo-dismiss'));
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && root.classList.contains('is-open')) closeSheet(false);
  });

  var shouldAuto = root.getAttribute('data-auto-open') === '1';
  var params = new URLSearchParams(window.location.search);
  if (params.get('open_trial') === '1' || params.get('open_promo') === '1') shouldAuto = true;
  var dismissed = false;
  try { dismissed = sessionStorage.getItem(STORAGE_KEY) === '1'; } catch (e) {}
  if (shouldAuto && !dismissed) {
    window.setTimeout(openSheet, 1800);
  }
})();
</script>
