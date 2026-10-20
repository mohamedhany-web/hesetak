@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $brand = __('landing.nav.brand');
  $catalog = $catalog ?? [];
  $years = $catalog['years'] ?? collect();
  $subjects = $catalog['subjects'] ?? collect();
  $tracks = $catalog['tracks'] ?? [];
  $packages = $catalog['packages'] ?? collect();
  $selYear = $catalog['selected_year_id'] ?? null;
  $selSubject = $catalog['selected_subject_id'] ?? null;
  $selTrack = $catalog['selected_curriculum_type'] ?? null;
  $pkg = $selectedPackage;
  $selectedRow = $packages->first(fn ($row) => $pkg && (int) $row['id'] === (int) $pkg->id) ?? $packages->first();
  $returnUrl = $giftReturnUrl ?? url()->full();
  $loginUrl = route('login', ['redirect' => $returnUrl]);
  $registerUrl = route('register', ['redirect' => $returnUrl]);
@endphp

@section('content')
<div class="mc-gift">
  <section class="mc-dir-head mc-curr-head" aria-labelledby="mc-gift-title">
    <div class="mc-container">
      <nav class="mc-co-crumb" aria-label="{{ $isRtl ? 'مسار التنقل' : 'Breadcrumb' }}">
        <a href="{{ route('home') }}">{{ $isRtl ? 'الرئيسية' : 'Home' }}</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('public.pricing') }}">{{ __('public.pricing_page_title') }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ __('public.gift_kicker') }}</span>
      </nav>

      <div class="mc-dir-head__top">
        <div class="mc-dir-head__copy">
          <p class="mc-eyebrow">{{ __('public.gift_kicker') }}</p>
          <h1 id="mc-gift-title">
            {{ __('public.gift_title') }}
            <strong class="mc-brand-word">{{ $brand }}</strong>
          </h1>
          <p class="mc-curr-head__lead">{{ __('public.gift_lead') }}</p>
        </div>
        <ol class="mc-co-steps" aria-label="{{ $isRtl ? 'خطوات الإهداء' : 'Gift steps' }}">
          <li class="is-on"><span>1</span>{{ __('public.gift_step_pack') }}</li>
          <li><span>2</span>{{ __('public.gift_step_recipient') }}</li>
          <li><span>3</span>{{ __('public.gift_step_pay') }}</li>
        </ol>
      </div>
    </div>
  </section>

  <section class="mc-section mc-section--compact">
    <div class="mc-container">
      @if(session('error') || (isset($errors) && $errors->any()))
        <div class="mc-gift-alert" role="alert">{{ session('error') ?: $errors->first() }}</div>
      @endif

      <div class="mc-gift-layout">
        <form method="POST" action="{{ route('public.gift-package.store') }}" class="mc-gift-form">
          @csrf
          <input type="hidden" name="academic_year_id" value="{{ $selYear }}">
          <input type="hidden" name="curriculum_type" value="{{ $selTrack }}">
          <input type="hidden" name="academic_subject_id" value="{{ $selSubject }}">

          <article class="mc-gift-panel">
            <header class="mc-gift-panel__head">
              <p class="mc-gift-panel__step">1</p>
              <div>
                <h2>{{ __('public.gift_choose_pack') }}</h2>
                <p>{{ __('public.pricing_filter_hint') }}</p>
              </div>
            </header>

            <div class="mc-gift-filters">
              <label class="mc-field">
                <span>{{ __('public.pricing_filter_stage') }}</span>
                <select class="mc-input" data-gift-filter="year">
                  <option value="">{{ __('public.pricing_filter_all_stages') }}</option>
                  @foreach($years as $year)
                    <option value="{{ $year->id }}" @selected((int) $selYear === (int) $year->id)>{{ $year->name }}</option>
                  @endforeach
                </select>
              </label>
              <label class="mc-field">
                <span>{{ __('public.pricing_filter_track') }}</span>
                <select class="mc-input" data-gift-filter="curriculum_type">
                  <option value="">{{ __('public.pricing_filter_all_tracks') }}</option>
                  @foreach($tracks as $key => $meta)
                    <option value="{{ $key }}" @selected($selTrack === $key)>{{ $meta['label'] }}</option>
                  @endforeach
                </select>
              </label>
              @if($subjects->isNotEmpty())
                <label class="mc-field">
                  <span>{{ __('public.pricing_filter_subject') }}</span>
                  <select class="mc-input" data-gift-filter="subject">
                    <option value="">{{ __('public.pricing_filter_all_subjects') }}</option>
                    @foreach($subjects as $subject)
                      <option value="{{ $subject->id }}" @selected((int) $selSubject === (int) $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                  </select>
                </label>
              @endif
            </div>

            @if($packages->isEmpty())
              <div class="mc-empty">{{ __('public.gift_no_packages') }}</div>
            @else
              <div class="mc-gift-packs" role="radiogroup" aria-label="{{ __('public.gift_choose_pack') }}">
                @foreach($packages as $row)
                  @php $isOn = $pkg && (int) $pkg->id === (int) $row['id']; @endphp
                  <label class="mc-gift-pack {{ $isOn ? 'is-on' : '' }}">
                    <input type="radio" name="service_package_id" value="{{ $row['id'] }}" @checked($isOn) required>
                    <span class="mc-gift-pack__copy">
                      @if(!empty($row['badge']))
                        <span class="mc-gift-pack__badge">{{ $row['badge'] }}</span>
                      @endif
                      <strong>{{ $row['name'] }}</strong>
                      <em>
                        {{ (int) $row['units_count'] }} {{ __('public.gift_sessions') }}
                        @if(!empty($row['tagline']))
                          · {{ $row['tagline'] }}
                        @endif
                      </em>
                    </span>
                    <span class="mc-gift-pack__price">{{ format_money($row['display_price']) }}</span>
                  </label>
                @endforeach
              </div>
            @endif
          </article>

          @auth
          <article class="mc-gift-panel">
            <header class="mc-gift-panel__head">
              <p class="mc-gift-panel__step">2</p>
              <div>
                <h2>{{ __('public.gift_recipient') }}</h2>
                <p>{{ $isRtl ? 'نبعت التأكيد على هذا البريد بعد تفعيل الباقة.' : 'We send the confirmation to this email after activation.' }}</p>
              </div>
            </header>

            <div class="mc-gift-fields">
              <label class="mc-field">
                <span>{{ $isRtl ? 'بريد المستلم' : 'Recipient email' }}</span>
                <input id="recipient_email" type="email" name="recipient_email" required value="{{ old('recipient_email') }}" class="mc-input" dir="ltr" autocomplete="email" placeholder="{{ __('public.gift_email_ph') }}">
              </label>
              <label class="mc-field">
                <span>{{ $isRtl ? 'اسم المستلم' : 'Recipient name' }}</span>
                <input id="recipient_name" type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="mc-input" autocomplete="name" placeholder="{{ __('public.gift_name_ph') }}">
              </label>
              <label class="mc-field">
                <span>{{ $isRtl ? 'جوال المستلم (اختياري)' : 'Recipient phone (optional)' }}</span>
                <input id="recipient_phone" type="tel" name="recipient_phone" value="{{ old('recipient_phone') }}" class="mc-input" dir="ltr" autocomplete="tel" placeholder="{{ __('public.gift_phone_ph') }}">
              </label>
              <label class="mc-field mc-field--full">
                <span>{{ $isRtl ? 'رسالة قصيرة' : 'Short message' }}</span>
                <textarea id="message" name="message" rows="3" class="mc-input" placeholder="{{ __('public.gift_message_ph') }}">{{ old('message') }}</textarea>
              </label>
            </div>
          </article>
          @endauth

          <div class="mc-gift-actions">
            @auth
              <button type="submit" class="mc-btn mc-btn--lg mc-btn--secondary" @disabled($packages->isEmpty())>{{ __('public.gift_cta_pay') }}</button>
            @else
              <p class="mc-gift-login-hint">{{ __('public.gift_login_hint') }}</p>
              <a href="{{ $loginUrl }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ __('public.gift_cta_login') }}</a>
              <a href="{{ $registerUrl }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ __('public.gift_cta_register') }}</a>
            @endauth
            <a href="{{ route('public.pricing') }}" class="mc-gift-back">{{ __('public.gift_back_packages') }}</a>
          </div>
        </form>

        <aside class="mc-gift-aside" aria-labelledby="mc-gift-summary-title">
          <article class="mc-gift-summary">
            <p class="mc-gift-summary__kicker">{{ __('public.gift_summary') }}</p>
            <h2 id="mc-gift-summary-title">{{ $selectedRow['name'] ?? ($pkg->name ?? __('public.gift_choose_pack')) }}</h2>
            @if(!empty($selectedRow['units_count']))
              <p class="mc-gift-summary__hours">{{ (int) $selectedRow['units_count'] }} {{ __('public.gift_sessions') }}</p>
            @endif
            @if($quote)
              <dl class="mc-gift-summary__totals">
                <div>
                  <dt>{{ __('public.gift_per_session') }}</dt>
                  <dd>{{ format_money($quote['unit']) }}</dd>
                </div>
                <div>
                  <dt>{{ __('public.gift_total') }}</dt>
                  <dd>{{ format_money($quote['total']) }}</dd>
                </div>
              </dl>
            @endif
            <p class="mc-gift-summary__next-title">{{ __('public.gift_what_next') }}</p>
            <ol class="mc-gift-summary__next">
              <li>{{ __('public.gift_what_next_1') }}</li>
              <li>{{ __('public.gift_what_next_2') }}</li>
              <li>{{ __('public.gift_what_next_3') }}</li>
            </ol>
          </article>
        </aside>
      </div>
    </div>
  </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var fields = document.querySelectorAll('[data-gift-filter]');
  if (!fields.length) return;

  function applyFilters() {
    var url = new URL(window.location.href);
    fields.forEach(function (field) {
      var key = field.getAttribute('data-gift-filter');
      if (!key) return;
      if (field.value) url.searchParams.set(key, field.value);
      else url.searchParams.delete(key);
    });
    var selected = document.querySelector('input[name="service_package_id"]:checked');
    if (selected) url.searchParams.set('package', selected.value);
    window.location.assign(url.toString());
  }

  fields.forEach(function (field) {
    field.addEventListener('change', applyFilters);
  });

  document.querySelectorAll('input[name="service_package_id"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var url = new URL(window.location.href);
      url.searchParams.set('package', input.value);
      window.location.assign(url.toString());
    });
  });
})();
</script>
@endpush
