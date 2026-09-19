@php
  $locale = app()->getLocale();
  $isRtl = $locale === 'ar';
  $brand = config('app.name', 'حصتك');
  $footer = \App\Services\PublicFooterSettings::payload();
  $waUrl = $footer['whatsapp_url'] ?? '#';
  $fawaterakActive = !empty($fawaterakUseGateway);
  $fawaterakMis = !empty($fawaterakMisconfigured);
  $fawaterakIntegration = $fawaterakIntegration ?? 'iframe';
  $paypalActive = !empty($paypalUseGateway);
  $paypalMis = !empty($paypalMisconfigured);
  $amountLabel = format_money((float) $order->amount);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ $packageTitle }} — {{ $brand }}</title>
  <meta name="robots" content="noindex">
  <meta name="theme-color" content="#0B3D91">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @include('partials.favicon-links')
  @include('partials.landing.head', ['landingCss' => ['theme', 'courses-catalog', 'pricing']])
  <style>
    body.sana-pricing-page { padding-top: 0; }
    .gl-co { padding: clamp(24px, 4vw, 40px) 0 72px; max-width: 640px; margin: 0 auto; }
    .gl-co-card { background: #fff; border: 1.5px solid #D7DDE6; border-radius: 20px; box-shadow: 0 14px 34px -24px rgba(11,61,145,.4); overflow: hidden; }
    .gl-co-card__head { padding: 1.15rem 1.25rem; border-bottom: 1px dashed #E4E9F2; }
    .gl-co-card__head h1 { margin: 0; font-family: Cairo, Tajawal, sans-serif; font-weight: 900; color: #0B1220; font-size: 1.2rem; }
    .gl-co-card__body { padding: 1.15rem 1.25rem 1.35rem; }
    .gl-co-hint { font-size: .76rem; color: #5B6577; line-height: 1.7; margin: 0; }
    .gl-co-alert { padding: .75rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: .85rem; font-weight: 700; }
    .gl-co-alert--err { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
    .gl-co-alert--sky { background:#EEF4FF; color:#0B3D91; border:1px solid #C9D8F5; }
    .gl-co-alert--info { background:#F8FAFC; color:#334155; border:1px solid #E2E8F0; }
    .gl-co-total { margin-bottom: 1rem; padding: .9rem 1rem; border-radius: 14px; background: #F4F7FC; border: 1.5px solid #DCE5F5; display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
    .gl-co-total span { font-weight: 800; color: #5B6577; font-size: .85rem; }
    .gl-co-total strong { font-family: Cairo, sans-serif; font-size: 1.5rem; font-weight: 900; color: #0B3D91; }
    .gl-co-label { display: block; margin-bottom: .35rem; font-size: .76rem; font-weight: 800; color: #5B6577; }
    .gl-co-select { width: 100%; border: 1.5px solid #D7DDE6; border-radius: 12px; padding: .7rem .85rem; font-size: .9rem; background: #fff; color: #0B1220; margin-bottom: .95rem; font-weight: 700; }
    .hidden { display:none !important; }
  </style>
</head>
<body class="sana-home sana-courses-page sana-pricing-page">
@include('partials.landing.navbar', ['navActive' => 'packages', 'navSolid' => true, 'navHero' => false])

<main class="sana-cat-page">
  <section class="sana-cat-hero" style="padding-bottom:.5rem">
    <div class="sana-container sana-cat-hero__inner sana-reveal">
      <nav class="sana-cat-hero__breadcrumb" aria-label="breadcrumb">
        <a href="{{ route('home') }}">{{ $isRtl ? 'الرئيسية' : 'Home' }}</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('public.service-packages.index') }}">{{ $isRtl ? 'باقات الحصص' : 'Packages' }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ $isRtl ? 'الدفع' : 'Pay' }}</span>
      </nav>
      <h1 class="sana-cat-hero__title" style="margin-top:.6rem">{{ $isRtl ? 'إتمام الدفع' : 'Complete payment' }}</h1>
      <p class="sana-cat-hero__sub">{{ $packageTitle }}</p>
    </div>
  </section>

  <div class="sana-container gl-co">
    @if(session('error'))
      <div class="gl-co-alert gl-co-alert--err">{{ session('error') }}</div>
    @endif
    @if(session('info'))
      <div class="gl-co-alert gl-co-alert--sky">{{ session('info') }}</div>
    @endif

    <article class="gl-co-card sana-reveal">
      <div class="gl-co-card__head">
        <h1>{{ $isRtl ? 'بوابة فواتيرك' : 'Fawaterak checkout' }}</h1>
      </div>
      <div class="gl-co-card__body">
        <div class="gl-co-total">
          <span>{{ $isRtl ? 'المبلغ' : 'Amount' }}</span>
          <strong>{{ $amountLabel }}</strong>
        </div>

        @if($fawaterakMis && ! $paypalActive)
          <div class="gl-co-alert gl-co-alert--err">{{ $isRtl ? 'تم تفعيل فواتيرك لكن الربط غير مكتمل على الخادم.' : 'Fawaterak is enabled but server credentials are incomplete.' }}</div>
        @elseif($fawaterakActive && $fawaterakIntegration === 'api')
          <div class="gl-co-alert gl-co-alert--sky" style="display:flex;gap:.65rem"><i class="fas fa-lock"></i><div><strong>{{ $isRtl ? 'اختر وسيلة الدفع' : 'Choose a payment method' }}</strong></div></div>
          <div id="fawaterk-api-error" class="hidden gl-co-alert gl-co-alert--err"></div>
          <div id="fawaterk-api-loading" style="margin-bottom:1rem;font:700 .85rem Tajawal,sans-serif;color:#5B6577"><i class="fas fa-spinner fa-spin" style="color:#0B3D91"></i> {{ $isRtl ? 'جاري التحميل...' : 'Loading…' }}</div>
          <div id="fawaterk-api-methods" class="hidden" style="display:grid;gap:.55rem;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));margin-bottom:1rem"></div>
          <div id="fawaterk-api-wallet-wrap" class="hidden">
            <label class="gl-co-label" for="fawaterk-api-wallet">{{ $isRtl ? 'رقم المحفظة (إن لزم)' : 'Wallet number (if needed)' }}</label>
            <input type="text" id="fawaterk-api-wallet" dir="ltr" class="gl-co-select" placeholder="01xxxxxxxxx" autocomplete="tel">
          </div>
          <div id="fawaterk-api-result" class="hidden gl-co-alert gl-co-alert--info"></div>
          <button type="button" id="fawaterk-api-pay-btn" disabled class="sana-btn sana-btn--yellow" style="width:100%;justify-content:center">
            <i class="fas fa-lock"></i> {{ $isRtl ? 'متابعة الدفع' : 'Continue payment' }}
          </button>
        @elseif($fawaterakActive)
          <div class="gl-co-alert gl-co-alert--sky" style="display:flex;gap:.65rem"><i class="fas fa-lock"></i><div><strong>{{ $isRtl ? 'ادفع عبر فواتيرك' : 'Pay with Fawaterak' }}</strong></div></div>
          <div id="fawaterk-checkout-error" class="hidden gl-co-alert gl-co-alert--err"></div>
          <div id="fawaterkDivId"></div>
        @endif

        @if($paypalActive)
          @if($fawaterakActive)
            <p style="margin:1rem 0 .65rem;text-align:center;font:800 .8rem Tajawal,sans-serif;color:#5B6577">{{ $isRtl ? 'أو' : 'or' }}</p>
          @endif
          <form method="POST" action="{{ $paypalRoute ?? route('public.service-packages.custom.paypal', $order) }}">
            @csrf
            <button type="submit" class="sana-btn sana-btn--yellow" style="width:100%;justify-content:center;background:#003087;color:#fff;border:0">
              <i class="fab fa-paypal"></i>
              {{ $isRtl ? 'الدفع عبر PayPal' : 'Pay with PayPal' }}
            </button>
          </form>
        @elseif(! $fawaterakActive && $paypalMis)
          <div class="gl-co-alert gl-co-alert--err">{{ $isRtl ? 'تم تفعيل PayPal لكن بيانات الاتصال ناقصة.' : 'PayPal is enabled but credentials are missing.' }}</div>
        @elseif(! $fawaterakActive && ! $fawaterakMis)
          <div class="gl-co-alert gl-co-alert--info">{{ $isRtl ? 'بوابة فواتيرك غير مفعّلة.' : 'Fawaterak is not enabled.' }}</div>
        @endif

        <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
          <a href="{{ route('public.service-packages.index') }}" class="sana-btn sana-btn--white-outline" style="flex:1;justify-content:center">{{ $isRtl ? 'رجوع' : 'Back' }}</a>
          <a href="{{ $waUrl }}" class="sana-btn sana-btn--wa" style="flex:1;justify-content:center" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> WhatsApp</a>
        </div>
      </div>
    </article>
  </div>
</main>

@include('partials.landing.footer')
@php
  $landingJsFile = resource_path('js/landing/site.js');
  if (! is_file($landingJsFile)) { $landingJsFile = public_path('js/landing/site.js'); }
  $landingJsVer = is_file($landingJsFile) ? (string) filemtime($landingJsFile) : (string) time();
@endphp
<script src="{{ route('assets.landing.js', ['file' => 'site']) }}?v={{ $landingJsVer }}" defer></script>
@if($fawaterakActive && ! $fawaterakMis && $fawaterakIntegration === 'iframe')
<script>
(function(){
    var prepareUrl = @json($prepareRoute);
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = (meta && meta.getAttribute('content')) || @json(csrf_token());
    var errEl = document.getElementById('fawaterk-checkout-error');
    function showErr(msg) { if (!errEl) { alert(msg); return; } errEl.textContent = msg; errEl.classList.remove('hidden'); }
    function waitForFawaterkFn(resolve, reject) {
        window.requestAnimationFrame(function() {
            if (typeof fawaterkCheckout === 'function') resolve();
            else setTimeout(function(){ typeof fawaterkCheckout === 'function' ? resolve() : reject(new Error('no_fn')); }, 80);
        });
    }
    function loadScript(src) {
        var url = src + (src.indexOf('?') >= 0 ? '&' : '?') + '_fk=' + Date.now();
        return new Promise(function(resolve, reject) {
            var s = document.createElement('script');
            s.src = url; s.async = true;
            s.onload = function(){ waitForFawaterkFn(resolve, reject); };
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }
    function parseJsonSafe(t){ try { return JSON.parse(t); } catch(e){ return null; } }
    function run(){
        var fd = new FormData(); fd.append('_token', token);
        fetch(prepareUrl, { method:'POST', headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, body:fd, credentials:'same-origin' })
        .then(function(r){ return r.text().then(function(t){ return {ok:r.ok,status:r.status,data:parseJsonSafe(t)}; }); })
        .then(function(res){
            if (!res.data || !res.ok) { showErr((res.data && res.data.message) || 'Error'); return; }
            if (res.data.mode !== 'iframe') { showErr('Unexpected mode'); return; }
            return loadScript(res.data.pluginScriptUrl).then(function(){ fawaterkCheckout(res.data.pluginConfig); });
        })
        .catch(function(){ showErr(@json($isRtl ? 'تعذّر تحميل فواتيرك.' : 'Could not load Fawaterak.')); });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
})();
</script>
@endif
@if($fawaterakActive && ! $fawaterakMis && $fawaterakIntegration === 'api')
<script>
(function(){
    var prepareUrl = @json($prepareRoute);
    var methodsUrl = @json($methodsRoute);
    var payUrl = @json($payRoute);
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
    function showErr(msg){ if(!errEl){alert(msg);return;} errEl.textContent=msg; errEl.classList.remove('hidden'); }
    function parseJsonSafe(t){ try{return JSON.parse(t);}catch(e){return null;} }
    function renderMethods(list){
        methodsEl.innerHTML='';
        list.forEach(function(m){
            var id=m.paymentId;
            var name=(document.documentElement.getAttribute('dir')==='rtl'&&m.name_ar)?m.name_ar:(m.name_en||m.name_ar||('#'+id));
            var card=document.createElement('button');
            card.type='button';
            card.style.cssText='display:flex;align-items:center;gap:.65rem;padding:.75rem;border-radius:12px;border:2px solid #D7DDE6;background:#fff;text-align:start;cursor:pointer;font:700 .82rem Tajawal,sans-serif';
            card.textContent=name;
            card.addEventListener('click',function(){
                methodsEl.querySelectorAll('button').forEach(function(b){b.style.borderColor='#D7DDE6';});
                card.style.borderColor='#F5B800'; selectedId=id; if(payBtn) payBtn.disabled=false;
            });
            methodsEl.appendChild(card);
        });
        methodsEl.classList.remove('hidden');
        if(walletWrap) walletWrap.classList.remove('hidden');
    }
    function run(){
        var fd=new FormData(); fd.append('_token',token);
        fetch(prepareUrl,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd,credentials:'same-origin'})
        .then(function(r){return r.text().then(function(t){return {ok:r.ok,data:parseJsonSafe(t)};});})
        .then(function(res){
            if(!res.data||!res.ok){showErr((res.data&&res.data.message)||'Error');return;}
            return fetch(methodsUrl,{method:'GET',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
        })
        .then(function(r){ if(!r) return; return r.text().then(function(t){return {ok:r.ok,data:parseJsonSafe(t)};}); })
        .then(function(res){
            if(!res) return;
            if(loadEl) loadEl.classList.add('hidden');
            if(!res.ok||!res.data||res.data.status!=='success'||!Array.isArray(res.data.data)){ showErr((res.data&&res.data.message)||'Error'); return; }
            renderMethods(res.data.data);
        })
        .catch(function(){ if(loadEl) loadEl.classList.add('hidden'); showErr('Network error'); });
    }
    if(payBtn){
        payBtn.addEventListener('click',function(){
            if(!selectedId) return;
            payBtn.disabled=true;
            var body={payment_method_id:selectedId};
            var w=walletInput&&walletInput.value?walletInput.value.trim():'';
            if(w) body.mobile_wallet_number=w;
            fetch(payUrl,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',body:JSON.stringify(body)})
            .then(function(r){return r.text().then(function(t){return {ok:r.ok,data:parseJsonSafe(t)};});})
            .then(function(res){
                payBtn.disabled=false;
                if(!res.data||!res.ok){showErr((res.data&&res.data.message)||'Error');return;}
                var pd=res.data.data&&res.data.data.payment_data;
                if(pd&&pd.redirectTo){ window.location.href=pd.redirectTo; return; }
                if(resultEl){ resultEl.classList.remove('hidden'); resultEl.textContent=JSON.stringify(pd||res.data); }
            })
            .catch(function(){ payBtn.disabled=false; showErr('Network error'); });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',run); else run();
})();
</script>
@endif
</body>
</html>
