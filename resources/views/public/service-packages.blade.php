@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $planMatrix = $planMatrix ?? [];
  $years = $years ?? collect();
  $privateTermMonths = $privateTermMonths ?? [1, 3];
  $privateWeeklyOptions = $privateWeeklyOptions ?? [1, 2, 3, 4];
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'باقات حصتك' : 'Hesetak packages' }}</p>
    <h1>{{ $isRtl ? 'اختر رصيد الحصص المناسب' : 'Choose the right lesson package' }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'قارن عدد الحصص والمدة، ثم استخدم الرصيد لحجز دروس فردية مع معلم مناسب.' : 'Compare lessons and validity, then book 1:1 tutoring.' }}</p>
  </div>
</section>

<section class="mc-section" id="plans">
  <div class="mc-container">
    @if($years->isNotEmpty())
      <div class="mc-filters">
        <a href="{{ route('public.service-packages.index') }}" class="mc-chip {{ empty($yearId) ? 'is-on' : '' }}">{{ $isRtl ? 'كل المراحل' : 'All stages' }}</a>
        @foreach($years->take(8) as $year)<a href="{{ route('public.service-packages.index', ['year' => $year->id]) }}" class="mc-chip {{ (string)$yearId === (string)$year->id ? 'is-on' : '' }}">{{ $year->name }}</a>@endforeach
      </div>
    @endif

    @if(empty($planMatrix))
      <div class="mc-empty"><p>{{ $isRtl ? 'لا توجد باقات نشطة حالياً.' : 'No active packages right now.' }}</p><a href="{{ route('public.contact') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'تواصل معنا' : 'Contact us' }}</a></div>
    @else
      <div class="mc-packages">
        @foreach($planMatrix as $planType => $plan)
          @php $defaultTerm = $plan['terms']->get(3) ?: $plan['terms']->first(); @endphp
          <article class="mc-package {{ $plan['featured'] ? 'mc-package--recommended' : '' }}" data-plan-card>
            @if($plan['featured'])<span class="mc-package__badge">{{ $isRtl ? 'الأفضل قيمة' : 'Best value' }}</span>@endif
            <h3>{{ $plan['label'] }}</h3>
            @if($plan['tagline'])<p class="mc-package__why">{{ $plan['tagline'] }}</p>@endif
            <div class="mc-filters" style="margin:0">
              @foreach($plan['terms'] as $months => $termPackage)
                <button type="button" class="mc-chip {{ $termPackage->is($defaultTerm) ? 'is-on' : '' }}" data-term data-price="{{ $termPackage->formattedPrice() }}" data-href="{{ route('public.service-packages.checkout', $termPackage) }}">{{ $termPackage->termLabel() }}</button>
              @endforeach
            </div>
            <p class="mc-package__hours" data-price>{{ $defaultTerm->formattedPrice() }}</p>
            <p class="mc-package__price">{{ $defaultTerm->units_count }} {{ $isRtl ? 'حصة' : 'sessions' }} · {{ $defaultTerm->validityLabel() }}</p>
            <ul>@foreach($plan['features'] as $feature)<li>{{ $feature }}</li>@endforeach</ul>
            <a href="{{ route('public.service-packages.checkout', $defaultTerm) }}" class="mc-btn mc-btn--lg mc-btn--primary" data-checkout>{{ $isRtl ? 'اشترِ الباقة' : 'Buy package' }}</a>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>

@if($privateRule)
<section class="mc-section mc-section--muted" id="private-builder">
  <div class="mc-container">
    <div class="mc-section-head"><h2>{{ $isRtl ? 'خصّص باقة حصص فردية' : 'Build a private lesson package' }}</h2><p>{{ $isRtl ? 'اختر المدة وعدد الحصص الأسبوعية، ثم احصل على السعر من قواعد المنصة.' : 'Choose term and weekly lessons to calculate your package.' }}</p></div>
    <form method="POST" action="{{ route('public.service-packages.custom.store') }}" id="private-builder-form" class="mc-detail">
      @csrf
      <input type="hidden" name="pricing_rule_id" value="{{ $privateRule->id }}">
      <div class="mc-detail__panel" style="padding:1.25rem">
        <div class="mc-field"><label>{{ $isRtl ? 'مدة الاشتراك' : 'Term' }}</label><div class="mc-filters">@foreach($privateTermMonths as $months)<label class="mc-chip"><input type="radio" name="term_months" value="{{ $months }}" @checked($months === 1) required> {{ $months }} {{ $isRtl ? 'شهر' : 'month' }}</label>@endforeach</div></div>
        <div class="mc-field"><label>{{ $isRtl ? 'حصص أسبوعياً' : 'Lessons per week' }}</label><div class="mc-filters">@foreach($privateWeeklyOptions as $weekly)<label class="mc-chip"><input type="radio" name="weekly_sessions" value="{{ $weekly }}" @checked($weekly === 2) required> {{ $weekly }}</label>@endforeach</div></div>
      </div>
      <aside class="mc-detail__panel" style="padding:1.25rem">
        <p>{{ $isRtl ? 'إجمالي الباقة' : 'Package total' }}</p><strong id="pv-total" class="mc-package__hours">−</strong>
        <p id="pv-details" style="color:var(--mc-muted)"></p>
        @auth
          @if(!empty($fawaterakUseGateway))<button type="submit" class="mc-btn mc-btn--lg mc-btn--primary" style="width:100%">{{ $isRtl ? 'متابعة للدفع' : 'Continue to payment' }}</button>
          @else<a href="{{ \App\Services\PublicFooterSettings::payload()['whatsapp_url'] ?? '#' }}" class="mc-btn mc-btn--lg mc-btn--outline" target="_blank" rel="noopener">{{ $isRtl ? 'تواصل للشراء' : 'Contact to buy' }}</a>@endif
        @else
          <a href="{{ route('login') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ $isRtl ? 'سجّل للطلب' : 'Login to order' }}</a>
        @endauth
      </aside>
    </form>
  </div>
</section>
@endif
@endsection

@push('scripts')
<script>
(function(){
  document.querySelectorAll('[data-plan-card]').forEach(function(card){card.querySelectorAll('[data-term]').forEach(function(button){button.addEventListener('click',function(){card.querySelectorAll('[data-term]').forEach(function(item){item.classList.toggle('is-on',item===button)});card.querySelector('[data-price]').textContent=button.dataset.price;card.querySelector('[data-checkout]').href=button.dataset.href})})});
  var form=document.getElementById('private-builder-form');if(!form)return;
  function refresh(){var term=form.querySelector('[name="term_months"]:checked'),weekly=form.querySelector('[name="weekly_sessions"]:checked'),rule=form.querySelector('[name="pricing_rule_id"]');if(!term||!weekly)return;var url=new URL(@json(route('public.service-packages.custom.quote')),location.origin);url.searchParams.set('pricing_rule_id',rule.value);url.searchParams.set('term_months',term.value);url.searchParams.set('weekly_sessions',weekly.value);fetch(url,{headers:{Accept:'application/json'}}).then(function(r){return r.json()}).then(function(data){document.getElementById('pv-total').textContent=Number(data.amount).toFixed(2)+' '+@json(currency_symbol());document.getElementById('pv-details').textContent=data.sessions+' '+@json($isRtl ? 'حصة' : 'sessions')})}
  form.querySelectorAll('input[type=radio]').forEach(function(input){input.addEventListener('change',refresh)});refresh();
})();
</script>
@endpush
