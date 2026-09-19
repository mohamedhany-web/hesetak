@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $footer = \App\Services\PublicFooterSettings::payload();
  $waUrl = $footer['whatsapp_url'] ?? '#';
  $fawaterakActive = !empty($fawaterakUseGateway);
  $fawaterakMis = !empty($fawaterakMisconfigured);
  $fawaterakIntegration = $fawaterakIntegration ?? 'iframe';
  $paypalActive = !empty($paypalUseGateway);
  $paypalMis = !empty($paypalMisconfigured);
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'دفع آمن' : 'Secure payment' }}</p>
    <h1>{{ $isRtl ? 'إتمام الدفع' : 'Complete payment' }}</h1>
    <p class="mc-lead">{{ $packageTitle }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="max-width:680px">
    @if(session('error'))<div class="mc-empty" style="margin-bottom:1rem;color:#991b1b">{{ session('error') }}</div>@endif
    @if(session('info'))<div class="mc-empty" style="margin-bottom:1rem">{{ session('info') }}</div>@endif
    <article class="mc-card" style="padding:1.5rem">
      <div class="mc-card__foot" style="padding:1rem;background:var(--mc-surface-2);border-radius:12px;margin-bottom:1rem"><span>{{ $isRtl ? 'المبلغ' : 'Amount' }}</span><strong class="mc-card__price">{{ format_money((float) $order->amount) }}</strong></div>

      @if($fawaterakMis && !$paypalActive)
        <div class="mc-empty" style="color:#991b1b">{{ $isRtl ? 'تم تفعيل فواتيرك لكن الربط غير مكتمل على الخادم.' : 'Fawaterak is enabled but server credentials are incomplete.' }}</div>
      @elseif($fawaterakActive && $fawaterakIntegration === 'api')
        <h2>{{ $isRtl ? 'اختر وسيلة الدفع' : 'Choose a payment method' }}</h2>
        <div id="fawaterk-api-error" hidden class="mc-empty" style="color:#991b1b"></div>
        <div id="fawaterk-api-loading"><i class="fas fa-spinner fa-spin"></i> {{ $isRtl ? 'جاري التحميل...' : 'Loading...' }}</div>
        <div id="fawaterk-api-methods" hidden class="mc-grid" style="margin:1rem 0"></div>
        <div id="fawaterk-api-wallet-wrap" hidden class="mc-field"><label for="fawaterk-api-wallet">{{ $isRtl ? 'رقم المحفظة (إن لزم)' : 'Wallet number' }}</label><input type="text" id="fawaterk-api-wallet" class="mc-input" dir="ltr" placeholder="01xxxxxxxxx"></div>
        <div id="fawaterk-api-result" hidden class="mc-empty"></div>
        <button type="button" id="fawaterk-api-pay-btn" disabled class="mc-btn mc-btn--lg mc-btn--primary" style="width:100%">{{ $isRtl ? 'متابعة الدفع' : 'Continue payment' }}</button>
      @elseif($fawaterakActive)
        <h2>{{ $isRtl ? 'الدفع عبر فواتيرك' : 'Pay with Fawaterak' }}</h2>
        <div id="fawaterk-checkout-error" hidden class="mc-empty" style="color:#991b1b"></div>
        <div id="fawaterkDivId"></div>
      @endif

      @if($paypalActive)
        <form method="POST" action="{{ $paypalRoute ?? route('public.service-packages.custom.paypal', $order) }}" style="margin-top:1rem">
          @csrf
          <button type="submit" class="mc-btn mc-btn--lg mc-btn--outline" style="width:100%"><i class="fab fa-paypal"></i> {{ $isRtl ? 'الدفع عبر PayPal' : 'Pay with PayPal' }}</button>
        </form>
      @elseif(!$fawaterakActive && $paypalMis)
        <div class="mc-empty" style="color:#991b1b">{{ $isRtl ? 'تم تفعيل PayPal لكن بيانات الاتصال ناقصة.' : 'PayPal credentials are incomplete.' }}</div>
      @elseif(!$fawaterakActive && !$fawaterakMis)
        <div class="mc-empty">{{ $isRtl ? 'بوابة الدفع غير مفعلة حالياً.' : 'Payment gateway is unavailable.' }}</div>
      @endif

      <div style="display:flex;gap:.65rem;flex-wrap:wrap;margin-top:1rem">
        <a href="{{ route('public.service-packages.index') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $isRtl ? 'رجوع' : 'Back' }}</a>
        <a href="{{ $waUrl }}" class="mc-btn mc-btn--md mc-btn--outline" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> WhatsApp</a>
      </div>
    </article>
  </div>
</section>
@endsection

@push('scripts')
@if($fawaterakActive && !$fawaterakMis && $fawaterakIntegration === 'iframe')
<script>
(function(){
  var prepareUrl=@json($prepareRoute), token=document.querySelector('meta[name="csrf-token"]').content, err=document.getElementById('fawaterk-checkout-error');
  function fail(message){err.textContent=message;err.hidden=false}
  function load(src){return new Promise(function(ok,no){var s=document.createElement('script');s.src=src+(src.indexOf('?')>=0?'&':'?')+'_fk='+Date.now();s.async=true;s.onload=ok;s.onerror=no;document.head.appendChild(s)})}
  var fd=new FormData();fd.append('_token',token);
  fetch(prepareUrl,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd,credentials:'same-origin'})
    .then(function(r){return r.json().then(function(data){return {ok:r.ok,data:data}})})
    .then(function(res){if(!res.ok||res.data.mode!=='iframe')throw new Error((res.data&&res.data.message)||'Error');return load(res.data.pluginScriptUrl).then(function(){fawaterkCheckout(res.data.pluginConfig)})})
    .catch(function(e){fail(e.message||@json($isRtl ? 'تعذر تحميل فواتيرك.' : 'Could not load Fawaterak.'))});
})();
</script>
@endif
@if($fawaterakActive && !$fawaterakMis && $fawaterakIntegration === 'api')
<script>
(function(){
  var prepareUrl=@json($prepareRoute),methodsUrl=@json($methodsRoute),payUrl=@json($payRoute),token=document.querySelector('meta[name="csrf-token"]').content;
  var err=document.getElementById('fawaterk-api-error'),loading=document.getElementById('fawaterk-api-loading'),methods=document.getElementById('fawaterk-api-methods'),button=document.getElementById('fawaterk-api-pay-btn'),wallet=document.getElementById('fawaterk-api-wallet'),selected=null;
  function fail(message){err.textContent=message;err.hidden=false}
  var fd=new FormData();fd.append('_token',token);
  fetch(prepareUrl,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd,credentials:'same-origin'})
    .then(function(r){if(!r.ok)throw new Error('Error');return fetch(methodsUrl,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'})})
    .then(function(r){return r.json()}).then(function(data){loading.hidden=true;(data.data||[]).forEach(function(method){var el=document.createElement('button');el.type='button';el.className='mc-btn mc-btn--md mc-btn--outline';el.textContent=(document.documentElement.dir==='rtl'&&method.name_ar)?method.name_ar:(method.name_en||method.name_ar);el.onclick=function(){selected=method.paymentId;button.disabled=false};methods.appendChild(el)});methods.hidden=false;document.getElementById('fawaterk-api-wallet-wrap').hidden=false}).catch(function(){fail('Network error')});
  button.addEventListener('click',function(){if(!selected)return;button.disabled=true;var body={payment_method_id:selected};if(wallet.value.trim())body.mobile_wallet_number=wallet.value.trim();fetch(payUrl,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',body:JSON.stringify(body)}).then(function(r){return r.json()}).then(function(data){button.disabled=false;var payment=data.data&&data.data.payment_data;if(payment&&payment.redirectTo){window.location.href=payment.redirectTo;return}var result=document.getElementById('fawaterk-api-result');result.textContent=JSON.stringify(payment||data);result.hidden=false}).catch(function(){button.disabled=false;fail('Network error')})});
})();
</script>
@endif
@endpush
