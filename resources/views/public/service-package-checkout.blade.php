@extends('layouts.mycourses-public')

@php
  $locale = app()->getLocale();
  $isRtl = $locale === 'ar';
  $brand = config('app.name', 'حصتك');
  $footer = \App\Services\PublicFooterSettings::payload();
  $waUrl = $footer['whatsapp_url'] ?? '#';
  $perMonth = $package->sessionsPerMonth();
  $fawaterakActive = ! empty($fawaterakUseGateway);
  $fawaterakMis = ! empty($fawaterakMisconfigured);
  $fawaterakIntegration = $fawaterakIntegration ?? 'iframe';
  $paypalActive = ! empty($paypalUseGateway);
  $paypalMis = ! empty($paypalMisconfigured);
  $canPayOnline = ($fawaterakActive && ! $fawaterakMis) || $paypalActive;
  $mcCss = public_path('css/landing/mycourses.css');
  $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
  $mcActive = 'pricing';
  $langSwitch = fn (string $lang) => request()->fullUrlWithQuery(array_merge(request()->query(), ['lang' => $lang]));
  $features = collect($package->featureList())->filter()->take(6)->values();
  $gifts = collect($package->giftList())->filter()->take(4)->values();
  $user = auth()->user();
@endphp

@push('head')
<meta name="robots" content="noindex">
@endpush

@section('content')
<div class="mc-co mc-body--checkout">
  <section class="mc-co-top">
    <div class="mc-container">
      <nav class="mc-co-crumb" aria-label="{{ $isRtl ? 'مسار التنقل' : 'Breadcrumb' }}">
        <a href="{{ route('home') }}">{{ $isRtl ? 'الرئيسية' : 'Home' }}</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('public.pricing') }}">{{ $isRtl ? 'الباقات' : 'Packages' }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ $package->name }}</span>
      </nav>

      <div class="mc-co-top__row">
        <div>
          <p class="mc-eyebrow">{{ $isRtl ? 'خطوة أخيرة' : 'Final step' }}</p>
          <h1 class="mc-co-title">{{ $isRtl ? 'أكمل شراء باقتك' : 'Complete your package' }}</h1>
          <p class="mc-co-lead">
            {{ $isRtl
              ? 'راجع الملخص، ادفع بأمان، ويُضاف رصيد الحصص لحسابك تلقائياً بعد نجاح الدفع.'
              : 'Review the summary, pay securely, and session credits are added automatically after payment succeeds.' }}
          </p>
        </div>
        <ol class="mc-co-steps" aria-label="{{ $isRtl ? 'خطوات الشراء' : 'Checkout steps' }}">
          <li class="is-done"><span>1</span>{{ $isRtl ? 'اختيار الباقة' : 'Choose pack' }}</li>
          <li class="is-on"><span>2</span>{{ $isRtl ? 'الدفع' : 'Pay' }}</li>
          <li><span>3</span>{{ $isRtl ? 'تفعيل الرصيد' : 'Credits live' }}</li>
        </ol>
      </div>
    </div>
  </section>

  <section class="mc-co-main">
    <div class="mc-container mc-co-grid">
      @if(session('error') || session('info') || (isset($errors) && $errors->any()))
        <div class="mc-co-alerts">
          @if(session('error'))
            <div class="mc-co-alert is-err" role="alert">{{ session('error') }}</div>
          @endif
          @if(session('info'))
            <div class="mc-co-alert is-info" role="status">{{ session('info') }}</div>
          @endif
          @if(isset($errors) && $errors->any())
            <div class="mc-co-alert is-err" role="alert">{{ $errors->first() }}</div>
          @endif
        </div>
      @endif

      {{-- Order summary --}}
      <aside class="mc-co-summary" aria-labelledby="mc-co-summary-title">
        <div class="mc-co-summary__card">
          <header class="mc-co-summary__head">
            <p class="mc-co-summary__kicker">{{ $isRtl ? 'ملخص الطلب' : 'Order summary' }}</p>
            <h2 id="mc-co-summary-title">{{ $package->name }}</h2>
            @if($package->isCommercialPlan())
              <p class="mc-co-summary__plan">{{ $package->planLabel() }} · {{ $package->termLabel() }}</p>
            @elseif($package->tagline)
              <p class="mc-co-summary__plan">{{ $package->tagline }}</p>
            @endif
          </header>

          <ul class="mc-co-facts">
            @if($package->isCommercialPlan())
              <li>
                <span>{{ $isRtl ? 'حصص أسبوعياً' : 'Weekly sessions' }}</span>
                <strong>{{ $package->weeklySessionsTotal() }}</strong>
              </li>
            @endif
            <li>
              <span>{{ $isRtl ? 'عدد الحصص' : 'Sessions' }}</span>
              <strong>{{ $package->units_count }}</strong>
            </li>
            <li>
              <span>{{ $isRtl ? 'مدة الحصة' : 'Session length' }}</span>
              <strong>{{ $package->sessionMinutes() }} {{ $isRtl ? 'د' : 'min' }}</strong>
            </li>
            <li>
              <span>{{ $isRtl ? 'إجمالي التعلم' : 'Total learning' }}</span>
              <strong>{{ $package->totalHoursLabel() }}</strong>
            </li>
            <li>
              <span>{{ $isRtl ? 'سعر الحصة' : 'Per session' }}</span>
              <strong>{{ $package->formattedPricePerUnit() }}</strong>
            </li>
            <li>
              <span>{{ $isRtl ? 'صلاحية الرصيد' : 'Validity' }}</span>
              <strong>
                {{ $package->validityLabel() }}
                @if($perMonth)
                  <small>{{ $isRtl ? '≈' : '~' }} {{ rtrim(rtrim(number_format($perMonth, 1), '0'), '.') }} {{ $isRtl ? 'حصة/شهر' : '/mo' }}</small>
                @endif
              </strong>
            </li>
            <li>
              <span>{{ $isRtl ? 'تُستخدم في' : 'Valid for' }}</span>
              <strong>{{ $package->label() }}</strong>
            </li>
          </ul>

          @if($features->isNotEmpty())
            <ul class="mc-co-perks">
              @foreach($features as $feature)
                <li><i class="fas fa-check" aria-hidden="true"></i><span>{{ $feature }}</span></li>
              @endforeach
            </ul>
          @endif

          @if($gifts->isNotEmpty())
            <div class="mc-co-gifts">
              <p>{{ $isRtl ? 'هدايا مع الباقة' : 'Included extras' }}</p>
              <ul>
                @foreach($gifts as $gift)
                  <li>{{ $gift }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="mc-co-total">
            <div>
              <span>{{ $isRtl ? 'الإجمالي' : 'Total' }}</span>
              @if($package->formattedOriginalPrice())
                <p class="mc-co-total__old">{{ $package->formattedOriginalPrice() }}</p>
              @endif
              @if($package->savingsVsMonthlyLabel())
                <p class="mc-co-total__save">{{ $package->savingsVsMonthlyLabel() }}</p>
              @elseif($package->savingsAmount() > 0)
                <p class="mc-co-total__save">{{ $isRtl ? 'وفّرت' : 'You save' }} {{ format_money($package->savingsAmount()) }} ({{ $package->savingsPercent() }}%)</p>
              @endif
            </div>
            <strong>{{ $package->formattedPrice() }}</strong>
          </div>

          <ul class="mc-co-trust">
            <li><i class="fas fa-shield-halved" aria-hidden="true"></i>{{ $isRtl ? 'دفع آمن عبر البوابة' : 'Secure gateway payment' }}</li>
            <li><i class="fas fa-bolt" aria-hidden="true"></i>{{ $isRtl ? 'تفعيل الرصيد بعد نجاح الدفع' : 'Credits after successful pay' }}</li>
            <li><i class="fas fa-chalkboard-user" aria-hidden="true"></i>{{ $isRtl ? 'احجز مع أي معلم معتمد مناسب' : 'Book with any matching teacher' }}</li>
          </ul>
        </div>
      </aside>

      {{-- Payment --}}
      <section class="mc-co-pay" aria-labelledby="mc-co-pay-title">
        <div class="mc-co-pay__card">
          <header class="mc-co-pay__head">
            <h2 id="mc-co-pay-title">{{ $isRtl ? 'ادفع الآن' : 'Pay now' }}</h2>
            @if($user)
              <p class="mc-co-pay__account">
                {{ $isRtl ? 'مسجّل كـ' : 'Signed in as' }}
                <strong>{{ $user->name }}</strong>
              </p>
            @endif
          </header>

          <div class="mc-co-pay__body">
            @if($fawaterakMis && ! $paypalActive)
              <div class="mc-co-alert is-err">
                <strong>{{ $isRtl ? 'إعدادات الدفع غير مكتملة' : 'Payment settings incomplete' }}</strong>
                <p>{{ $isRtl ? 'تم تفعيل فواتيرك لكن الربط غير مكتمل على الخادم.' : 'Fawaterak is enabled but server credentials are incomplete.' }}</p>
              </div>
            @elseif($fawaterakActive && $fawaterakIntegration === 'api')
              <div class="mc-co-callout">
                <i class="fas fa-lock" aria-hidden="true"></i>
                <div>
                  <strong>{{ $isRtl ? 'الدفع عبر فواتيرك' : 'Pay with Fawaterak' }}</strong>
                  <p>{{ $isRtl ? 'اختر وسيلة الدفع ثم تابع. بعد النجاح يُفعَّل رصيد الحصص تلقائياً.' : 'Choose a method and continue. Credits activate automatically after success.' }}</p>
                </div>
              </div>
              <div id="fawaterk-api-error" class="mc-co-alert is-err" hidden></div>
              <div id="fawaterk-api-loading" class="mc-co-loading"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> {{ $isRtl ? 'جاري تحميل وسائل الدفع…' : 'Loading payment methods…' }}</div>
              <div id="fawaterk-api-methods" class="mc-co-methods" hidden></div>
              <div id="fawaterk-api-wallet-wrap" class="mc-co-field" hidden>
                <label for="fawaterk-api-wallet">{{ $isRtl ? 'رقم المحفظة (إن لزم)' : 'Wallet number (if required)' }}</label>
                <input type="text" id="fawaterk-api-wallet" dir="ltr" placeholder="01xxxxxxxxx" autocomplete="tel">
              </div>
              <div id="fawaterk-api-result" class="mc-co-alert is-info" hidden></div>
              <button type="button" id="fawaterk-api-pay-btn" class="mc-btn mc-btn--lg mc-btn--secondary mc-co-pay-btn" disabled>
                <i class="fas fa-lock" aria-hidden="true"></i>
                {{ $isRtl ? 'متابعة الدفع' : 'Continue payment' }} · {{ $package->formattedPrice() }}
              </button>
            @elseif($fawaterakActive)
              <div class="mc-co-callout">
                <i class="fas fa-lock" aria-hidden="true"></i>
                <div>
                  <strong>{{ $isRtl ? 'الدفع عبر فواتيرك' : 'Pay with Fawaterak' }}</strong>
                  <p>{{ $isRtl ? 'اختر وسيلة الدفع داخل الإطار. بعد النجاح يُفعَّل رصيد الحصص تلقائياً.' : 'Choose a method below. Credits activate automatically after success.' }}</p>
                </div>
              </div>
              <div id="fawaterk-checkout-error" class="mc-co-alert is-err" hidden></div>
              <div id="fawaterkDivId" class="mc-co-gateway"></div>
            @endif

            @if($paypalActive)
              @if($fawaterakActive)
                <p class="mc-co-or">{{ $isRtl ? 'أو' : 'or' }}</p>
              @endif
              <form method="POST" action="{{ route('public.service-packages.paypal', $package) }}">
                @csrf
                <button type="submit" class="mc-btn mc-btn--lg mc-btn--outline mc-co-paypal">
                  <i class="fab fa-paypal" aria-hidden="true"></i>
                  {{ $isRtl ? 'الدفع عبر PayPal' : 'Pay with PayPal' }} · {{ $package->formattedPrice() }}
                </button>
              </form>
            @elseif(! $fawaterakActive && $paypalMis)
              <div class="mc-co-alert is-err">
                <strong>{{ $isRtl ? 'إعدادات PayPal غير مكتملة' : 'PayPal settings incomplete' }}</strong>
                <p>{{ $isRtl ? 'تم تفعيل PayPal لكن بيانات الاتصال ناقصة.' : 'PayPal is enabled but connection data is missing.' }}</p>
              </div>
            @elseif(! $canPayOnline)
              <div class="mc-co-offline">
                <div class="mc-co-offline__icon" aria-hidden="true"><i class="fab fa-whatsapp"></i></div>
                <h3>{{ $isRtl ? 'أكمل الشراء عبر واتساب' : 'Finish via WhatsApp' }}</h3>
                <p>
                  {{ $isRtl
                    ? 'الدفع الإلكتروني غير مفعّل الآن. راسلنا باسم الباقة وسنُفعّل رصيد الحصص بعد التأكيد.'
                    : 'Online payment is off for now. Message us with the package name and we will activate credits after confirmation.' }}
                </p>
                <p class="mc-co-offline__pkg">{{ $package->name }} · {{ $package->formattedPrice() }}</p>
                <a href="{{ $waUrl }}" class="mc-btn mc-btn--lg mc-btn--secondary mc-co-pay-btn" target="_blank" rel="noopener">
                  <i class="fab fa-whatsapp" aria-hidden="true"></i>
                  {{ $isRtl ? 'إتمام الشراء واتساب' : 'Complete on WhatsApp' }}
                </a>
              </div>
            @endif

            <div class="mc-co-secondary">
              <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $isRtl ? 'باقة أخرى' : 'Other packages' }}</a>
              @if($canPayOnline)
                <a href="{{ $waUrl }}" class="mc-btn mc-btn--md mc-btn--outline" target="_blank" rel="noopener">
                  <i class="fab fa-whatsapp" aria-hidden="true"></i> {{ $isRtl ? 'استفسار واتساب' : 'WhatsApp help' }}
                </a>
              @endif
            </div>

            <p class="mc-co-note">
              {{ $isRtl
                ? 'تُخصم حصة واحدة عند اكتمال الدرس، وليس عند الحجز. يمكنك متابعة الرصيد من لوحة الطالب.'
                : 'One credit is deducted when the lesson completes, not at booking. Track balance from your student dashboard.' }}
            </p>
          </div>
        </div>
      </section>
    </div>
  </section>
</div>

<div class="mc-co-dock" id="mc-co-dock">
  <div class="mc-co-dock__inner">
    <div>
      <span>{{ $package->name }}</span>
      <strong>{{ $package->formattedPrice() }}</strong>
    </div>
    @if($canPayOnline)
      <a href="#mc-co-pay-title" class="mc-btn mc-btn--md mc-btn--secondary">{{ $isRtl ? 'إلى الدفع' : 'Go to pay' }}</a>
    @else
      <a href="{{ $waUrl }}" class="mc-btn mc-btn--md mc-btn--secondary" target="_blank" rel="noopener">
        <i class="fab fa-whatsapp" aria-hidden="true"></i> {{ $isRtl ? 'واتساب' : 'WhatsApp' }}
      </a>
    @endif
  </div>
</div>
<script>
(function(){
  var dock = document.getElementById('mc-co-dock');
  var pay = document.getElementById('mc-co-pay-title');
  if (!dock || !pay || !('IntersectionObserver' in window)) return;
  var io = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) {
      dock.classList.toggle('is-away', e.isIntersecting);
    });
  }, { rootMargin: '-20% 0px -35% 0px', threshold: 0.05 });
  io.observe(pay);
})();
</script>

@if($fawaterakActive && ! $fawaterakMis && $fawaterakIntegration === 'iframe')
<script>
(function(){
    var prepareUrl = @json(route('public.service-packages.fawaterak.prepare', $package));
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = (meta && meta.getAttribute('content')) || @json(csrf_token());
    var errEl = document.getElementById('fawaterk-checkout-error');
    function showErr(msg) {
        if (!errEl) { alert(msg); return; }
        errEl.textContent = msg;
        errEl.hidden = false;
    }
    function waitForFawaterkFn(resolve, reject) {
        window.requestAnimationFrame(function() {
            if (typeof fawaterkCheckout === 'function') { resolve(); }
            else {
                setTimeout(function() {
                    if (typeof fawaterkCheckout === 'function') resolve();
                    else reject(new Error('no_fn'));
                }, 80);
            }
        });
    }
    function loadScriptTag(url) {
        return new Promise(function(resolve, reject) {
            var s = document.createElement('script');
            s.src = url; s.async = true;
            s.onload = function() { waitForFawaterkFn(resolve, reject); };
            s.onerror = function() { reject(new Error('network')); };
            document.head.appendChild(s);
        });
    }
    function loadScriptViaBlob(url) {
        return fetch(url, { credentials: 'same-origin', cache: 'no-store' })
            .then(function(r) {
                if (!r.ok) throw new Error('fetch ' + r.status);
                return r.text();
            })
            .then(function(code) {
                if (!code || code.trim().indexOf('<') === 0) throw new Error('not_js');
                var blob = new Blob([code], { type: 'application/javascript' });
                var blobUrl = URL.createObjectURL(blob);
                return new Promise(function(resolve, reject) {
                    var s = document.createElement('script');
                    s.onload = function() { URL.revokeObjectURL(blobUrl); waitForFawaterkFn(resolve, reject); };
                    s.onerror = function() { URL.revokeObjectURL(blobUrl); reject(new Error('blob_load')); };
                    s.src = blobUrl;
                    document.head.appendChild(s);
                });
            });
    }
    function loadScript(src) {
        var sep = src.indexOf('?') >= 0 ? '&' : '?';
        var url = src + sep + '_fk=' + Date.now();
        return loadScriptTag(url).catch(function() { return loadScriptViaBlob(url); });
    }
    function parseJsonSafe(text) { try { return JSON.parse(text); } catch (e) { return null; } }
    function run() {
        var fd = new FormData();
        fd.append('_token', token);
        fetch(prepareUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(r) {
            return r.text().then(function(text) {
                return { ok: r.ok, status: r.status, data: parseJsonSafe(text) };
            });
        })
        .then(function(res) {
            if (res.status === 401) { showErr(@json($isRtl ? 'انتهت الجلسة. سجّل الدخول ثم أعد فتح الصفحة.' : 'Session expired. Sign in and reopen this page.')); return; }
            if (res.status === 419) { showErr(@json($isRtl ? 'انتهت صلاحية الجلسة. حدّث الصفحة.' : 'Session expired. Refresh the page.')); return; }
            if (!res.data || !res.ok) {
                showErr((res.data && res.data.message) || @json($isRtl ? 'تعذّر تجهيز الطلب.' : 'Could not prepare checkout.'));
                return;
            }
            if (res.data.mode === 'completed' && res.data.redirect) {
                window.location.href = res.data.redirect;
                return;
            }
            if (res.data.mode !== 'iframe' || !res.data.pluginScriptUrl || !res.data.pluginConfig) {
                showErr(@json($isRtl ? 'استجابة غير متوقعة من بوابة الدفع.' : 'Unexpected payment gateway response.'));
                return;
            }
            return loadScript(res.data.pluginScriptUrl).then(function() {
                fawaterkCheckout(res.data.pluginConfig);
            });
        })
        .catch(function() {
            showErr(@json($isRtl ? 'تعذّر تحميل بوابة فواتيرك. حدّث الصفحة أو جرّب متصفحاً آخر.' : 'Could not load Fawaterak. Refresh or try another browser.'));
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
    else run();
})();
</script>
@endif
@if($fawaterakActive && ! $fawaterakMis && $fawaterakIntegration === 'api')
<script>
(function(){
    var prepareUrl = @json(route('public.service-packages.fawaterak.prepare', $package));
    var methodsUrl = @json(route('public.service-packages.fawaterak.methods', $package));
    var payUrl = @json(route('public.service-packages.fawaterak.pay', $package));
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = (meta && meta.getAttribute('content')) || @json(csrf_token());
    var errEl = document.getElementById('fawaterk-api-error');
    var loadEl = document.getElementById('fawaterk-api-loading');
    var methodsEl = document.getElementById('fawaterk-api-methods');
    var payBtn = document.getElementById('fawaterk-api-pay-btn');
    var resultEl = document.getElementById('fawaterk-api-result');
    var walletWrap = document.getElementById('fawaterk-api-wallet-wrap');
    var walletInput = document.getElementById('fawaterk-api-wallet');
    var selectedId = null;
    function showErr(msg) {
        if (!errEl) { alert(msg); return; }
        errEl.textContent = msg;
        errEl.hidden = false;
    }
    function parseJsonSafe(text) { try { return JSON.parse(text); } catch (e) { return null; } }
    function renderMethods(list) {
        if (!methodsEl) return;
        methodsEl.innerHTML = '';
        list.forEach(function(m) {
            var id = m.paymentId;
            var name = (document.documentElement.getAttribute('dir') === 'rtl' && m.name_ar) ? m.name_ar : (m.name_en || m.name_ar || ('#' + id));
            var card = document.createElement('button');
            card.type = 'button';
            card.className = 'mc-co-method';
            card.setAttribute('data-pid', String(id));
            if (m.logo && typeof m.logo === 'string') {
                var img = document.createElement('img');
                img.src = m.logo; img.alt = ''; img.loading = 'lazy';
                card.appendChild(img);
            }
            var title = document.createElement('span');
            title.textContent = name;
            card.appendChild(title);
            card.addEventListener('click', function() {
                methodsEl.querySelectorAll('.mc-co-method').forEach(function(b) { b.classList.remove('is-on'); });
                card.classList.add('is-on');
                selectedId = id;
                if (payBtn) payBtn.disabled = false;
            });
            methodsEl.appendChild(card);
        });
        methodsEl.hidden = false;
        if (walletWrap) walletWrap.hidden = false;
    }
    function showPaymentResult(pd) {
        if (!resultEl || !pd) return;
        resultEl.hidden = false;
        if (pd.redirectTo) { window.location.href = pd.redirectTo; return; }
        var html = '';
        if (pd.fawryCode) html += '<p><strong>رمز فوري:</strong> <span dir="ltr">' + pd.fawryCode + '</span></p>';
        if (pd.expireDate) html += '<p>{{ $isRtl ? "ينتهي" : "Expires" }}: ' + pd.expireDate + '</p>';
        if (!html) html = '<pre dir="ltr">' + JSON.stringify(pd, null, 2) + '</pre>';
        resultEl.innerHTML = html;
    }
    function run() {
        var fd = new FormData();
        fd.append('_token', token);
        fetch(prepareUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(r) { return r.text().then(function(t) { return { ok: r.ok, status: r.status, data: parseJsonSafe(t) }; }); })
        .then(function(res) {
            if (res.status === 401 || res.status === 419) { showErr(@json($isRtl ? 'انتهت الجلسة. حدّث الصفحة.' : 'Session expired. Refresh.')); return; }
            if (!res.data || !res.ok) { showErr((res.data && res.data.message) || @json($isRtl ? 'تعذّر تجهيز الطلب.' : 'Could not prepare checkout.')); return; }
            if (res.data.mode !== 'api') { showErr(@json($isRtl ? 'الخادم ليس في وضع API.' : 'Server is not in API mode.')); return; }
            return fetch(methodsUrl, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
        })
        .then(function(r) {
            if (!r) return;
            return r.text().then(function(t) { return { ok: r.ok, data: parseJsonSafe(t) }; });
        })
        .then(function(res) {
            if (!res) return;
            if (loadEl) loadEl.hidden = true;
            if (!res.ok || !res.data || res.data.status !== 'success' || !Array.isArray(res.data.data)) {
                showErr((res.data && res.data.message) || @json($isRtl ? 'تعذّر جلب وسائل الدفع.' : 'Could not load payment methods.'));
                return;
            }
            renderMethods(res.data.data);
        })
        .catch(function() {
            if (loadEl) loadEl.hidden = true;
            showErr(@json($isRtl ? 'تعذّر الاتصال بالخادم.' : 'Could not reach the server.'));
        });
    }
    if (payBtn) {
        payBtn.addEventListener('click', function() {
            if (!selectedId) return;
            if (errEl) errEl.hidden = true;
            payBtn.disabled = true;
            var body = { payment_method_id: selectedId };
            var w = walletInput && walletInput.value ? walletInput.value.trim() : '';
            if (w) body.mobile_wallet_number = w;
            fetch(payUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(body)
            })
            .then(function(r) { return r.text().then(function(t) { return { ok: r.ok, data: parseJsonSafe(t) }; }); })
            .then(function(res) {
                payBtn.disabled = false;
                if (!res.data || !res.ok) {
                    showErr((res.data && res.data.message) || @json($isRtl ? 'تعذّر بدء الدفع.' : 'Could not start payment.'));
                    return;
                }
                var pd = res.data.data && res.data.data.payment_data;
                showPaymentResult(pd);
            })
            .catch(function() {
                payBtn.disabled = false;
                showErr(@json($isRtl ? 'تعذّر الاتصال بالخادم.' : 'Could not reach the server.'));
            });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
    else run();
})();
</script>
@endif
@endsection
