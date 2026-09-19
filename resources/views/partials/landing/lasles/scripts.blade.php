<script>
(() => {
  const nav = document.getElementById('lasles-nav');
  const burger = document.getElementById('lasles-burger');
  const mobile = document.getElementById('lasles-mobile');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const onScroll = () => nav && nav.classList.toggle('is-scrolled', window.scrollY > 8);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  burger?.addEventListener('click', () => {
    if (!mobile) return;
    const open = !mobile.hasAttribute('hidden');
    if (open) {
      mobile.hidden = true;
      mobile.classList.remove('is-open');
      burger.setAttribute('aria-expanded', 'false');
    } else {
      mobile.hidden = false;
      mobile.classList.add('is-open');
      burger.setAttribute('aria-expanded', 'true');
    }
  });
  mobile?.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => {
    mobile.hidden = true;
    mobile.classList.remove('is-open');
    burger?.setAttribute('aria-expanded', 'false');
  }));

  if (reduceMotion) return;

  const revealNodes = [];
  document.querySelectorAll('main > section, .lasles-stats-wrap').forEach((el, i) => {
    if (el.classList.contains('lasles-hero') || el.classList.contains('lasles-path-hero')) return;
    el.classList.add('lasles-reveal');
    el.style.setProperty('--lasles-delay', `${Math.min(i * 35, 140)}ms`);
    revealNodes.push(el);
  });

  if (!revealNodes.length || !('IntersectionObserver' in window)) {
    revealNodes.forEach((el) => el.classList.add('is-in'));
    return;
  }

  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-in');
      io.unobserve(entry.target);
    });
  }, { rootMargin: '0px 0px -6% 0px', threshold: 0.1 });

  revealNodes.forEach((el) => io.observe(el));
})();
</script>
