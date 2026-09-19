@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $brand = config('app.name', 'حصتك');
    $itemTitle = $course->title ?? ($isRtl ? 'الكورس' : 'Course');
    $thumbUrl = $course->thumbnail_url ?? null;
    if (! $thumbUrl && ($course->thumbnail ?? null)) {
        $thumbUrl = storage_asset(str_replace('\\', '/', $course->thumbnail));
    }
    $isMonthlyCheckout = $course->isMonthlyBilling();
    $baseCoursePrice = (float) $course->effectiveCheckoutPrice();
    $studentBal = isset($studentWalletBalance) ? (float) $studentWalletBalance : 0;
    $checkoutHasWalletBalance = isset($studentWalletBalance) && (float) $studentWalletBalance > 0;
    $fawaterakActive = ! empty($fawaterakUseGateway);
    $fawaterakMis = ! empty($fawaterakMisconfigured);
    $fawaterakIntegration = $fawaterakIntegration ?? 'iframe';
    $paypalActive = ! empty($paypalUseGateway);
    $paypalMis = ! empty($paypalMisconfigured);
    $kashierActive = ! empty($kashierUseGateway);
    $kashierMis = ! empty($kashierMisconfigured);
    $anyOnlineGateway = $fawaterakActive || $paypalActive || $kashierActive;
    $canPayOnline = $anyOnlineGateway;
    $footer = \App\Services\PublicFooterSettings::payload();
    $waUrl = $footer['whatsapp_url'] ?? '#';
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $mcActive = 'courses';
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ $isRtl ? 'إتمام الاشتراك' : 'Checkout' }} · {{ $itemTitle }} — {{ $brand }}</title>
  <meta name="robots" content="noindex">
  <meta name="theme-color" content="#1E4E8C">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @include('partials.favicon-links')
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>[x-cloak]{display:none!important}.hidden{display:none!important}</style>
</head>
<body class="mc-body mc-body--checkout" x-data="{ isSubmitting: false }">
@include('partials.landing.mycourses.nav')

<main class="mc-co mc-cco">
  <section class="mc-co-top">
    <div class="mc-container">
      <nav class="mc-co-crumb" aria-label="breadcrumb">
        <a href="{{ route('home') }}">{{ __('public.home') }}</a>
        <span>/</span>
        <a href="{{ route('public.courses') }}">{{ __('landing.nav.courses') }}</a>
        <span>/</span>
        <a href="{{ route('public.course.show', $course->id) }}">{{ \Illuminate\Support\Str::limit($itemTitle, 28) }}</a>
        <span>/</span>
        <span>{{ __('public.checkout_breadcrumb_current') }}</span>
      </nav>

      <div class="mc-co-top__row">
        <div>
          <p class="mc-eyebrow">{{ $isRtl ? 'خطوة أخيرة' : 'Final step' }}</p>
          <h1 class="mc-co-title">{{ $isRtl ? 'أكمل اشتراكك في الكورس' : 'Complete your course enrollment' }}</h1>
          <p class="mc-co-lead">
            {{ $isRtl
              ? 'راجع الملخص، طبّق كوبوناً إن وُجد، ثم ادفع بأمان. يُفعَّل الوصول بعد نجاح الدفع.'
              : 'Review the summary, apply a coupon if you have one, then pay securely. Access unlocks after payment succeeds.' }}
          </p>
        </div>
        <ol class="mc-co-steps" aria-label="{{ $isRtl ? 'خطوات الاشتراك' : 'Enrollment steps' }}">
          <li class="is-done"><span>1</span>{{ $isRtl ? 'اختيار الكورس' : 'Choose course' }}</li>
          <li class="is-on"><span>2</span>{{ $isRtl ? 'الدفع' : 'Pay' }}</li>
          <li><span>3</span>{{ $isRtl ? 'ابدأ التعلم' : 'Start learning' }}</li>
        </ol>
      </div>
    </div>
  </section>

  <section class="mc-co-main">
    <div class="mc-container mc-co-grid">
      @if(session('error') || session('success') || session('info') || (isset($errors) && $errors->any()))
        <div class="mc-co-alerts">
          @if(session('error'))
            <div class="mc-co-alert is-err" role="alert">{{ session('error') }}</div>
          @endif
          @if(isset($errors) && $errors->any())
            <div class="mc-co-alert is-err" role="alert">{{ $errors->first() }}</div>
          @endif
          @if(session('success'))
            <div class="mc-co-alert is-ok" role="status">{{ session('success') }}</div>
          @endif
          @if(session('info'))
            <div class="mc-co-alert is-info" role="status">{{ session('info') }}</div>
          @endif
        </div>
      @endif

      <aside class="mc-co-summary" aria-labelledby="mc-cco-summary-title">
        <div class="mc-co-summary__card">
          <div class="mc-cco-item">
            @if($thumbUrl)
              <img src="{{ $thumbUrl }}" alt="">
            @else
              <div class="mc-cco-item__ph" aria-hidden="true"><i class="fas fa-graduation-cap"></i></div>
            @endif
            <div>
              <p class="mc-co-summary__kicker">{{ __('public.checkout_order_summary_title') }}</p>
              <h2 id="mc-cco-summary-title">{{ $course->title }}</h2>
              <p class="mc-co-summary__plan">
                {{ $course->instructor->name ?? '' }}
                @if($course->academicSubject)
                  · {{ $course->academicSubject->name }}
                @endif
              </p>
            </div>
          </div>

          <div id="checkout-pricing-summary"
               class="mc-cco-sums"
               data-base-price="{{ $baseCoursePrice }}"
               data-student-balance="{{ $studentBal }}"
               data-has-course="1"
               data-is-monthly="{{ $isMonthlyCheckout ? '1' : '0' }}">
            @if($isMonthlyCheckout)
              <p class="mc-co-alert is-info mc-cco-monthly">{{ __('public.checkout_monthly_notice') }}</p>
            @endif
            <div class="mc-cco-sum-row">
              <span>{{ $isMonthlyCheckout ? __('public.checkout_monthly_price_label') : __('public.checkout_base_price_label') }}</span>
              <strong id="sum-original">{{ format_money($baseCoursePrice) }}@if($isMonthlyCheckout)<small>/{{ __('public.per_month') }}</small>@endif</strong>
            </div>
            <div class="mc-cco-sum-row is-save hidden" id="sum-coupon-row">
              <span>{{ $isRtl ? 'خصم الكوبون' : 'Coupon' }}</span>
              <span id="sum-coupon">—</span>
            </div>
            <div class="mc-cco-sum-row is-wallet hidden" id="sum-wallet-row">
              <span>{{ $isRtl ? 'رصيد المحفظة' : 'Wallet' }}</span>
              <span id="sum-wallet">—</span>
            </div>
            <div class="mc-co-total mc-cco-due">
              <div>
                <span>{{ $isRtl ? 'المستحق الآن' : 'Due now' }}</span>
              </div>
              <strong id="sum-final">{{ format_money($baseCoursePrice) }}</strong>
            </div>
          </div>

          <ul class="mc-co-trust">
            @if($isMonthlyCheckout)
              <li><i class="fas fa-check" aria-hidden="true"></i>{{ __('public.checkout_benefit_monthly_access') }}</li>
            @else
              <li><i class="fas fa-check" aria-hidden="true"></i>{{ __('public.checkout_benefit_lifetime') }}</li>
            @endif
            <li><i class="fas fa-check" aria-hidden="true"></i>{{ __('public.checkout_benefit_support') }}</li>
            <li><i class="fas fa-bolt" aria-hidden="true"></i>{{ $isRtl ? 'تفعيل الوصول بعد نجاح الدفع' : 'Access after successful payment' }}</li>
          </ul>
        </div>
      </aside>

      <section class="mc-co-pay" aria-labelledby="mc-co-pay-title">
        <div class="mc-co-pay__card">
          <header class="mc-co-pay__head">
            <h2 id="mc-co-pay-title">{{ __('public.checkout_payment_section_title') }}</h2>
            @if($user)
              <p class="mc-co-pay__account">
                {{ $isRtl ? 'مسجّل كـ' : 'Signed in as' }}
                <strong>{{ $user->name }}</strong>
              </p>
            @endif
          </header>

          <div class="mc-co-pay__body">
            <div class="mc-cco-discount" id="checkout-discount-panel"
                 data-quote-url="{{ route('public.course.checkout.quote', $course->id) }}"
                 data-has-wallet="{{ $checkoutHasWalletBalance ? '1' : '0' }}">
              <h3>
                <i class="fas fa-tags" aria-hidden="true"></i>
                {{ $checkoutHasWalletBalance
                  ? ($isRtl ? 'كوبون ورصيد المحفظة' : 'Coupon & wallet')
                  : ($isRtl ? 'كوبون الخصم' : 'Discount coupon') }}
              </h3>
              <p>
                {{ $checkoutHasWalletBalance
                  ? ($isRtl ? 'الكوبون أولاً ثم رصيد المحفظة، ثم حدّث السعر.' : 'Coupon first, then wallet credit, then update price.')
                  : ($isRtl ? 'أدخل كوبوناً صالحاً إن وُجد، ثم حدّث السعر.' : 'Enter a valid coupon if you have one, then update the price.') }}
              </p>

              @if($isMonthlyCheckout)
                <label class="mc-cco-check">
                  <input type="checkbox" name="auto_renew" value="1" form="manual-checkout-form" {{ old('auto_renew', '1') ? 'checked' : '' }}>
                  <span>
                    <strong>{{ __('public.checkout_auto_renew_label') }}</strong>
                    <small>{{ __('public.checkout_auto_renew_hint') }}</small>
                  </span>
                </label>
              @endif

              @if($checkoutHasWalletBalance)
                <p class="mc-cco-balance">{{ $isRtl ? 'رصيدك:' : 'Balance:' }} {{ format_money($studentWalletBalance) }}</p>
              @endif

              <input type="hidden" id="checkout_currency" value="{{ platform_currency() }}">
              <div class="mc-cco-discount__fields {{ $checkoutHasWalletBalance ? 'has-wallet' : '' }}">
                <div class="mc-co-field">
                  <label for="checkout_coupon_code">{{ $isRtl ? 'كود الكوبون' : 'Coupon code' }}</label>
                  <input type="text" id="checkout_coupon_code" dir="ltr" autocomplete="off" placeholder="SAVE10">
                </div>
                @if($checkoutHasWalletBalance)
                  <div class="mc-co-field">
                    <label for="checkout_wallet_credit">{{ $isRtl ? 'من المحفظة' : 'From wallet' }}</label>
                    <input type="number" id="checkout_wallet_credit" step="0.01" min="0" value="0" max="{{ max(0, $studentWalletBalance ?? 0) }}">
                  </div>
                @endif
              </div>
              <div class="mc-cco-discount__actions">
                <button type="button" id="checkout_apply_pricing" class="mc-btn mc-btn--md mc-btn--soft">
                  <i class="fas fa-rotate" aria-hidden="true"></i>
                  {{ $isRtl ? 'تحديث السعر' : 'Update price' }}
                </button>
                <span id="checkout_pricing_msg" class="hidden"></span>
              </div>
            </div>

            @if($fawaterakMis && ! $paypalActive && ! $kashierActive)
              <div class="mc-co-alert is-err">
                <strong>{{ $isRtl ? 'إعدادات الدفع غير مكتملة' : 'Payment settings incomplete' }}</strong>
                <p>{{ $isRtl ? 'تم تفعيل فواتيرك لكن الربط غير مكتمل على الخادم.' : 'Fawaterak is enabled but server credentials are incomplete.' }}</p>
              </div>
            @elseif($paypalMis && ! $fawaterakActive && ! $kashierActive)
              <div class="mc-co-alert is-err">
                <strong>{{ $isRtl ? 'إعدادات PayPal غير مكتملة' : 'PayPal settings incomplete' }}</strong>
                <p>{{ $isRtl ? 'تم تفعيل PayPal لكن بيانات الاتصال ناقصة.' : 'PayPal is enabled but connection credentials are missing.' }}</p>
              </div>
            @elseif($fawaterakActive && $fawaterakIntegration === 'api')
              <div class="mc-co-callout">
                <i class="fas fa-lock" aria-hidden="true"></i>
                <div>
                  <strong>{{ $isRtl ? 'الدفع الإلكتروني' : 'Online payment' }}</strong>
                  <p>{{ $isRtl ? 'اختر وسيلة الدفع ثم تابع.' : 'Choose a payment method and continue.' }}</p>
                </div>
              </div>
              <div id="fawaterk-api-error" class="hidden mc-co-alert is-err"></div>
              <div id="fawaterk-api-loading" class="mc-co-loading"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> {{ $isRtl ? 'جاري تحميل وسائل الدفع…' : 'Loading payment methods…' }}</div>
              <div id="fawaterk-api-methods" class="mc-co-methods hidden"></div>
              <div id="fawaterk-api-wallet-wrap" class="mc-co-field hidden">
                <label for="fawaterk-api-wallet">{{ $isRtl ? 'رقم المحفظة' : 'Wallet number' }}</label>
                <input type="text" id="fawaterk-api-wallet" dir="ltr" placeholder="01xxxxxxxxx" autocomplete="tel">
              </div>
              <div id="fawaterk-api-result" class="hidden mc-co-alert is-info"></div>
              <button type="button" id="fawaterk-api-pay-btn" class="mc-btn mc-btn--lg mc-btn--secondary mc-co-pay-btn" disabled>
                <i class="fas fa-lock" aria-hidden="true"></i>
                {{ $isRtl ? 'متابعة الدفع' : 'Continue payment' }}
              </button>
            @elseif($fawaterakActive)
              <div class="mc-co-callout">
                <i class="fas fa-lock" aria-hidden="true"></i>
                <div>
                  <strong>{{ $isRtl ? 'الدفع عبر فواتيرك' : 'Pay with Fawaterak' }}</strong>
                  <p>{{ $isRtl ? 'اختر وسيلة الدفع داخل الإطار. بعد النجاح يُفعَّل الاشتراك تلقائياً.' : 'Choose a method below. Access activates automatically after success.' }}</p>
                </div>
              </div>
              <div id="fawaterk-checkout-error" class="hidden mc-co-alert is-err"></div>
              <div id="fawaterkDivId" class="mc-co-gateway"></div>
            @endif

            @if($kashierMis && ! $anyOnlineGateway && ! $fawaterakMis && ! $paypalMis)
              <div class="mc-co-alert is-err">
                <strong>{{ $isRtl ? 'إعدادات كاشير غير مكتملة' : 'Kashier settings incomplete' }}</strong>
                <p>{{ $isRtl ? 'تم تفعيل كاشير لكن بيانات الاتصال ناقصة.' : 'Kashier is enabled but connection credentials are missing.' }}</p>
              </div>
            @endif

            @if($paypalActive)
              @if($fawaterakActive)
                <p class="mc-co-or">{{ $isRtl ? 'أو' : 'or' }}</p>
              @endif
              <form method="POST" action="{{ route('public.course.checkout.paypal', $course->id) }}" id="paypal-checkout-form">
                @csrf
                <input type="hidden" name="coupon_code" id="paypal_coupon_code" value="">
                <input type="hidden" name="wallet_credit" id="paypal_wallet_credit" value="0">
                <input type="hidden" name="currency" id="paypal_currency" value="{{ platform_currency() }}">
                <button type="submit" class="mc-btn mc-btn--lg mc-btn--outline mc-co-paypal">
                  <i class="fab fa-paypal" aria-hidden="true"></i>
                  {{ $isRtl ? 'الدفع عبر PayPal' : 'Pay with PayPal' }}
                </button>
              </form>
            @endif

            @if($kashierActive)
              @if($fawaterakActive || $paypalActive)
                <p class="mc-co-or">{{ $isRtl ? 'أو' : 'or' }}</p>
              @endif
              <form method="POST" action="{{ route('public.course.checkout.kashier', $course->id) }}" id="kashier-checkout-form">
                @csrf
                <input type="hidden" name="coupon_code" id="kashier_coupon_code" value="">
                <input type="hidden" name="wallet_credit" id="kashier_wallet_credit" value="0">
                <input type="hidden" name="currency" id="kashier_currency" value="{{ platform_currency() }}">
                <button type="submit" class="mc-btn mc-btn--lg mc-btn--outline mc-co-pay-btn">
                  <i class="fas fa-university" aria-hidden="true"></i>
                  {{ $isRtl ? 'الدفع عبر كاشير' : 'Pay with Kashier' }}
                </button>
              </form>
            @endif

            @if(! $anyOnlineGateway && ! $fawaterakMis && ! $paypalMis && ! $kashierMis)
              <div class="mc-co-callout">
                <i class="fas fa-file-invoice" aria-hidden="true"></i>
                <div>
                  <strong>{{ $isRtl ? 'الدفع اليدوي' : 'Manual payment' }}</strong>
                  <p>{{ $isRtl ? 'ارفع إيصال التحويل، نراجع الطلب ثم نفعّل الاشتراك.' : 'Upload your transfer receipt — we review, then activate.' }}</p>
                </div>
              </div>
              <form action="{{ route('public.course.checkout.complete', $course->id) }}" method="POST" enctype="multipart/form-data" @submit="isSubmitting = true" x-data="{paymentMethod:'bank_transfer'}" id="manual-checkout-form" class="mc-cco-manual">
                @csrf
                <input type="hidden" name="coupon_code" id="form_coupon_code" value="{{ old('coupon_code', '') }}">
                <input type="hidden" name="wallet_credit" id="form_wallet_credit" value="{{ old('wallet_credit', '0') }}">
                <input type="hidden" name="currency" id="form_currency" value="{{ old('currency', platform_currency()) }}">

                <div class="mc-co-field">
                  <label for="mc-cco-method">{{ $isRtl ? 'طريقة الدفع' : 'Payment method' }}</label>
                  <select name="payment_method" id="mc-cco-method" x-model="paymentMethod" required>
                    <option value="bank_transfer">{{ $isRtl ? 'تحويل بنكي / محفظة' : 'Bank / wallet transfer' }}</option>
                    <option value="cash">{{ $isRtl ? 'دفع نقدي' : 'Cash' }}</option>
                    <option value="other">{{ $isRtl ? 'طريقة أخرى' : 'Other' }}</option>
                  </select>
                </div>

                <div class="mc-co-field" x-show="paymentMethod === 'bank_transfer'" x-cloak>
                  <label for="mc-cco-wallet">{{ $isRtl ? 'حساب التحويل' : 'Transfer account' }}</label>
                  <select name="wallet_id" id="mc-cco-wallet" :required="paymentMethod === 'bank_transfer'">
                    <option value="">{{ $isRtl ? 'اختر الحساب' : 'Select account' }}</option>
                    @foreach(($wallets ?? []) as $wallet)
                      <option value="{{ $wallet->id }}">{{ $wallet->name ?? ($isRtl ? 'حساب منصة' : 'Platform account') }} — {{ $wallet->account_number ?? $wallet->phone ?? '—' }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="mc-co-field">
                  <label for="mc-cco-proof">{{ $isRtl ? 'إيصال الدفع' : 'Payment proof' }}</label>
                  <input type="file" name="payment_proof" id="mc-cco-proof" accept="image/*" required>
                </div>

                <div class="mc-co-field">
                  <label for="mc-cco-notes">{{ $isRtl ? 'ملاحظات (اختياري)' : 'Notes (optional)' }}</label>
                  <textarea name="notes" id="mc-cco-notes" rows="3" placeholder="{{ $isRtl ? 'تفاصيل التحويل' : 'Transfer details' }}"></textarea>
                </div>

                <button type="submit" :disabled="isSubmitting" class="mc-btn mc-btn--lg mc-btn--secondary mc-co-pay-btn">
                  <i class="fas fa-file-upload" x-show="!isSubmitting" aria-hidden="true"></i>
                  <i class="fas fa-spinner fa-spin" x-show="isSubmitting" x-cloak aria-hidden="true"></i>
                  <span x-text="isSubmitting ? '{{ $isRtl ? 'جاري الإرسال…' : 'Submitting…' }}' : '{{ $isRtl ? 'إرسال الطلب' : 'Submit order' }}'"></span>
                </button>
              </form>
            @endif

            <div class="mc-co-secondary">
              <a href="{{ route('public.course.show', $course->id) }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $isRtl ? 'العودة للكورس' : 'Back to course' }}</a>
              <a href="{{ $waUrl }}" class="mc-btn mc-btn--md mc-btn--outline" target="_blank" rel="noopener">
                <i class="fab fa-whatsapp" aria-hidden="true"></i> {{ $isRtl ? 'مساعدة واتساب' : 'WhatsApp help' }}
              </a>
            </div>
          </div>
        </div>
      </section>
    </div>
  </section>
</main>

<div class="mc-co-dock" id="mc-co-dock">
  <div class="mc-co-dock__inner">
    <div>
      <span>{{ \Illuminate\Support\Str::limit($itemTitle, 24) }}</span>
      <strong id="mc-cco-dock-price">{{ format_money($baseCoursePrice) }}</strong>
    </div>
    <a href="#mc-co-pay-title" class="mc-btn mc-btn--md mc-btn--secondary">{{ $isRtl ? 'إلى الدفع' : 'Go to pay' }}</a>
  </div>
</div>
<script>
(function(){
  var dock = document.getElementById('mc-co-dock');
  var pay = document.getElementById('mc-co-pay-title');
  if (!dock || !pay || !('IntersectionObserver' in window)) return;
  var io = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) { dock.classList.toggle('is-away', e.isIntersecting); });
  }, { rootMargin: '-20% 0px -35% 0px', threshold: 0.05 });
  io.observe(pay);
})();
</script>

@include('partials.landing.mycourses.footer')
@include('public.partials.checkout-scripts')
@if(!empty($paypalUseGateway))
<script>
(function(){
    var form = document.getElementById('paypal-checkout-form');
    if (!form) return;
    form.addEventListener('submit', function(){
        var c = document.getElementById('checkout_coupon_code');
        var w = document.getElementById('checkout_wallet_credit');
        var pc = document.getElementById('paypal_coupon_code');
        var pw = document.getElementById('paypal_wallet_credit');
        var pcur = document.getElementById('paypal_currency');
        if (pc) pc.value = c ? (c.value || '').trim() : '';
        if (pw) pw.value = w && w.value !== '' ? w.value : '0';
        if (pcur) pcur.value = @json(platform_currency());
    });
})();
</script>
@endif
@if(!empty($kashierUseGateway))
<script>
(function(){
    var form = document.getElementById('kashier-checkout-form');
    if (!form) return;
    form.addEventListener('submit', function(){
        var c = document.getElementById('checkout_coupon_code');
        var w = document.getElementById('checkout_wallet_credit');
        var kc = document.getElementById('kashier_coupon_code');
        var kw = document.getElementById('kashier_wallet_credit');
        if (kc) kc.value = c ? (c.value || '').trim() : '';
        if (kw) kw.value = w && w.value !== '' ? w.value : '0';
    });
})();
</script>
@endif
</body>
</html>
