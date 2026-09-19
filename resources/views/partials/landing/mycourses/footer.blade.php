@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
@endphp
<footer class="mc-footer">
  <div class="mc-container">
    <div class="mc-footer__grid">
      <div class="mc-footer__brand-block">
        <a href="{{ route('home') }}" class="mc-footer__brand">
          <img src="{{ asset('img/brand/hesetak-mark.png') }}" alt="" class="mc-brand__mark" width="36" height="36" decoding="async">
          {{ __('landing.nav.brand') }}
        </a>
        <p class="mc-footer__tagline">{{ __('landing.mc.footer.tagline') }}</p>
      </div>

      <div class="mc-footer__col mc-footer__col--desktop">
        <h4>{{ __('landing.mc.footer.explore') }}</h4>
        <ul>
          <li><a href="{{ route('public.instructors.index') }}">{{ __('landing.mc.nav.instructors') }}</a></li>
          <li><a href="{{ route('public.curricula') }}">{{ __('landing.mc.footer.curricula') }}</a></li>
          <li><a href="{{ route('public.courses') }}">{{ __('landing.mc.nav.courses') }}</a></li>
          <li><a href="{{ route('public.pricing') }}">{{ __('landing.mc.nav.pricing') }}</a></li>
        </ul>
      </div>
      <div class="mc-footer__col mc-footer__col--desktop">
        <h4>{{ __('landing.mc.footer.company') }}</h4>
        <ul>
          <li><a href="{{ route('public.for-students') }}">{{ __('landing.mc.footer.for_students') }}</a></li>
          <li><a href="{{ route('public.for-teachers') }}">{{ __('landing.mc.footer.for_teachers') }}</a></li>
          <li><a href="{{ route('public.how') }}">{{ __('landing.mc.footer.how') }}</a></li>
          <li><a href="{{ route('public.faq') }}">{{ __('landing.mc.footer.faq') }}</a></li>
          <li><a href="{{ route('public.about') }}">{{ __('landing.mc.nav.about') }}</a></li>
          <li><a href="{{ route('public.contact') }}">{{ __('landing.mc.nav.contact') }}</a></li>
        </ul>
      </div>
      <div class="mc-footer__col mc-footer__col--desktop">
        <h4>{{ __('landing.mc.footer.legal') }}</h4>
        <ul>
          <li><a href="{{ route('public.terms') }}">{{ __('landing.mc.footer.terms') }}</a></li>
          <li><a href="{{ route('public.privacy') }}">{{ __('landing.mc.footer.privacy') }}</a></li>
          <li><a href="{{ route('public.certificates.verify') }}">{{ app()->getLocale() === 'ar' ? 'تحقق من شهادة' : 'Verify certificate' }}</a></li>
        </ul>
      </div>
    </div>

    <nav class="mc-footer__mobile" aria-label="{{ $isRtl ? 'روابط سريعة' : 'Quick links' }}">
      <div class="mc-footer__mobile-primary">
        <a href="{{ route('public.instructors.index') }}">{{ __('landing.mc.nav.instructors') }}</a>
        <a href="{{ route('public.curricula') }}">{{ __('landing.mc.footer.curricula') }}</a>
        <a href="{{ route('public.courses') }}">{{ __('landing.mc.nav.courses') }}</a>
        <a href="{{ route('public.pricing') }}">{{ __('landing.mc.nav.pricing') }}</a>
        <a href="{{ route('public.for-teachers') }}">{{ __('landing.mc.nav.for_teachers') }}</a>
        <a href="{{ route('public.contact') }}">{{ __('landing.mc.nav.contact') }}</a>
      </div>
      <div class="mc-footer__mobile-legal">
        <a href="{{ route('public.how') }}">{{ __('landing.mc.footer.how') }}</a>
        <a href="{{ route('public.faq') }}">{{ __('landing.mc.footer.faq') }}</a>
        <a href="{{ route('public.about') }}">{{ __('landing.mc.nav.about') }}</a>
        <a href="{{ route('public.for-students') }}">{{ __('landing.mc.footer.for_students') }}</a>
        <a href="{{ route('public.terms') }}">{{ __('landing.mc.footer.terms') }}</a>
        <a href="{{ route('public.privacy') }}">{{ __('landing.mc.footer.privacy') }}</a>
        <a href="{{ route('public.certificates.verify') }}">{{ app()->getLocale() === 'ar' ? 'شهادة' : 'Certificate' }}</a>
      </div>
    </nav>

    <p class="mc-footer__copy">© {{ date('Y') }} {{ __('landing.nav.brand') }}. {{ __('landing.mc.footer.rights') }}</p>
  </div>
</footer>
<script>
(function () {
  var toggle = document.getElementById('mc-nav-toggle');
  var drawer = document.getElementById('mc-nav-drawer');
  var nav = document.getElementById('mc-nav');
  if (!toggle || !drawer) return;

  var lastFocus = null;
  var closeTimer = null;
  var ANIM_MS = 420;

  function setOpen(open) {
    if (closeTimer) {
      clearTimeout(closeTimer);
      closeTimer = null;
    }

    if (open) {
      drawer.hidden = false;
      void drawer.offsetWidth;
      drawer.classList.add('is-open');
      drawer.setAttribute('aria-hidden', 'false');
      toggle.setAttribute('aria-expanded', 'true');
      document.body.classList.add('mc-drawer-open');
      if (nav) nav.classList.add('is-drawer-open');
      lastFocus = document.activeElement;
      var closeBtn = drawer.querySelector('[data-mc-drawer-close].mc-drawer__close');
      if (closeBtn) closeBtn.focus();
      return;
    }

    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('mc-drawer-open');
    if (nav) nav.classList.remove('is-drawer-open');
    closeTimer = setTimeout(function () {
      drawer.hidden = true;
      closeTimer = null;
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus();
      }
    }, ANIM_MS);
  }

  toggle.addEventListener('click', function () {
    setOpen(!drawer.classList.contains('is-open'));
  });

  drawer.querySelectorAll('[data-mc-drawer-close]').forEach(function (el) {
    el.addEventListener('click', function () { setOpen(false); });
  });

  drawer.querySelectorAll('.mc-drawer__nav a, .mc-drawer__actions a').forEach(function (link) {
    link.addEventListener('click', function () { setOpen(false); });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
      setOpen(false);
    }
  });

  window.addEventListener('resize', function () {
    if (window.matchMedia('(min-width: 960px)').matches && drawer.classList.contains('is-open')) {
      setOpen(false);
    }
  });

  window.addEventListener('pageshow', function () {
    if (drawer.classList.contains('is-open')) setOpen(false);
  });
})();

(function () {
  var menus = document.querySelectorAll('[data-mc-user-menu]');
  if (!menus.length) return;

  function closeMenu(menu) {
    var btn = menu.querySelector('.mc-user-menu__btn');
    var panel = menu.querySelector('.mc-user-menu__panel');
    if (!btn || !panel) return;
    menu.classList.remove('is-open');
    btn.setAttribute('aria-expanded', 'false');
    panel.hidden = true;
  }

  function openMenu(menu) {
    menus.forEach(function (other) {
      if (other !== menu) closeMenu(other);
    });
    var btn = menu.querySelector('.mc-user-menu__btn');
    var panel = menu.querySelector('.mc-user-menu__panel');
    if (!btn || !panel) return;
    menu.classList.add('is-open');
    btn.setAttribute('aria-expanded', 'true');
    panel.hidden = false;
  }

  menus.forEach(function (menu) {
    var btn = menu.querySelector('.mc-user-menu__btn');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (menu.classList.contains('is-open')) closeMenu(menu);
      else openMenu(menu);
    });
  });

  document.addEventListener('click', function (e) {
    menus.forEach(function (menu) {
      if (!menu.contains(e.target)) closeMenu(menu);
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    menus.forEach(closeMenu);
  });
})();
</script>
