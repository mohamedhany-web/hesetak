@extends('layouts.admin')

@section('title', 'بوابات الدفع')
@section('page_title', 'بوابات الدفع')
@section('header', 'بوابات الدفع')

@section('content')
@php
    $input = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $label = 'mb-1.5 block text-xs font-medium text-muted';
    $sectionHead = 'flex flex-wrap items-center gap-3 border-b border-line bg-canvas/60 px-5 py-4';
    $sectionBody = 'space-y-5 p-5 sm:p-6';
    $panel = 'overflow-hidden rounded-2xl border border-line bg-surface shadow-soft';
    $hubCount = (int) ($enabledCount ?? ((int) $fawaterakEnabled + (int) $stripeEnabled + (int) $kashierEnabled));
@endphp

<div class="space-y-5 pb-8">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0 max-w-3xl">
            <p class="text-xs font-medium text-muted">إدارة النظام · بوابات الدفع</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">مركز بوابات الدفع</h2>
            <p class="mt-2 text-sm leading-7 text-muted">
                شغّل أو أوقف كل بوابة من هنا. البوابة المفعّلة تظهر للطالب في صفحة الدفع.
                افتراضياً يُغلق الدفع اليدوي (رفع الإيصال) عند تفعيل أي بوابة — ويمكنكم الإبقاء عليه مع البوابات عبر الخيار أدناه (تحويل بنكي / فودافون كاش).
            </p>
        </div>
        <a href="{{ route('admin.system-settings.edit') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-sliders-h text-xs"></i>
            إعدادات النظام
        </a>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">مفعّل الآن</p>
            <p class="mt-1 text-lg font-semibold text-ink">{{ $hubCount }} من 3</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">فواتيرك</p>
            <p class="mt-1 text-sm font-semibold {{ $fawaterakEnabled && $fawaterakConfigured ? 'text-accent' : 'text-muted' }}">
                {{ $fawaterakEnabled && $fawaterakConfigured ? 'يعمل' : ($fawaterakEnabled ? 'مفعّل — المفاتيح ناقصة' : 'موقوف') }}
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">Stripe</p>
            <p class="mt-1 text-sm font-semibold {{ $stripeEnabled && $stripeConfigured ? 'text-accent' : 'text-muted' }}">
                {{ $stripeEnabled && $stripeConfigured ? 'يعمل' : ($stripeEnabled ? 'مفعّل — الربط ناقص' : 'موقوف') }}
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">كاشير</p>
            <p class="mt-1 text-sm font-semibold {{ $kashierEnabled && $kashierConfigured ? 'text-accent' : 'text-muted' }}">
                {{ $kashierEnabled && $kashierConfigured ? 'يعمل' : ($kashierEnabled ? 'مفعّل — الربط ناقص' : 'موقوف') }}
            </p>
        </article>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-danger/30 bg-surface px-4 py-3 text-sm text-danger shadow-soft" role="alert">
            <p class="font-semibold">تعذّر الحفظ</p>
            <ul class="mt-1 list-disc pr-5 text-xs leading-6">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.payment-gateways.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        {{-- Manual + fee --}}
        <article class="{{ $panel }}">
            <div class="{{ $sectionHead }}">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-receipt text-sm"></i></span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-ink">الدفع اليدوي مع البوابات</h3>
                    <p class="mt-0.5 text-xs leading-6 text-muted">تحويل بنكي / فودافون كاش ورفع إيصال — إلى جانب البوابات الإلكترونية.</p>
                </div>
                @if(! empty($blocksManual))
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-muted">موقوف حالياً</span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-semibold text-accent">متاح للطالب</span>
                @endif
            </div>
            <div class="{{ $sectionBody }}">
                <input type="hidden" name="allow_manual_checkout_with_gateways" value="0">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="allow_manual_checkout_with_gateways" value="1" class="mt-1 rounded border-line text-accent focus:ring-accent"
                           @checked((string) old('allow_manual_checkout_with_gateways', ! empty($allowManualWithGateways) ? '1' : '0') === '1')>
                    <span class="text-sm leading-7 text-muted">
                        <span class="mb-0.5 block font-semibold text-ink">السماح بالدفع اليدوي حتى مع بوابة إلكترونية مفعّلة</span>
                        بدون هذا الخيار يُخفى رفع الإيصال تلقائياً عند تفعيل فواتيرك أو Stripe أو كاشير.
                    </span>
                </label>

                <div class="border-t border-line pt-4">
                    <label for="payment_gateway_fee_percent" class="mb-1.5 block text-sm font-semibold text-ink">عمولة البوابات (تقديرية %)</label>
                    <p class="mb-2 text-xs leading-6 text-muted">نسبة من مبلغ العميل تُسجَّل كعمولة في المحاسبة لكل بوابات الدفع. اتركها فارغة أو 0 لإيقافها.</p>
                    <input type="text" name="payment_gateway_fee_percent" id="payment_gateway_fee_percent" inputmode="decimal"
                           value="{{ old('payment_gateway_fee_percent', $paymentGatewayFeePercent) }}"
                           class="{{ $input }} max-w-xs font-mono" dir="ltr" placeholder="مثال: 2.5">
                    @error('payment_gateway_fee_percent')
                        <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </article>

        {{-- Fawaterak --}}
        <article class="{{ $panel }}">
            <div class="{{ $sectionHead }}">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-credit-card text-sm"></i></span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-ink">فواتيرك (Fawaterak)</h3>
                    <p class="mt-0.5 text-xs leading-6 text-muted">بطاقات ومحافظ مصرية داخل صفحة إتمام الطلب.</p>
                </div>
                @if($fawaterakEnabled && $fawaterakConfigured)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-semibold text-accent">يعمل</span>
                @elseif($fawaterakEnabled)
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-metal/30 bg-canvas px-2.5 py-1 text-xs font-medium text-metal">مفعّل بدون مفاتيح</span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-muted">موقوف</span>
                @endif
            </div>
            <div class="{{ $sectionBody }}">
                <input type="hidden" name="fawaterak_gateway_enabled" value="0">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="fawaterak_gateway_enabled" value="1" class="mt-1 rounded border-line text-accent focus:ring-accent"
                           @checked((string) old('fawaterak_gateway_enabled', $fawaterakEnabled ? '1' : '0') === '1')>
                    <span class="text-sm leading-7 text-muted">
                        <span class="mb-0.5 block font-semibold text-ink">تشغيل فواتيرك</span>
                        يظهر إطار الدفع الرسمي في إتمام طلب الكورس والباقات.
                    </span>
                </label>

                <div class="rounded-xl border px-4 py-3 text-xs leading-7 sm:text-sm {{ $fawaterakConfigured ? 'border-accent/20 bg-accent-soft text-accent' : 'border-metal/30 bg-canvas text-ink' }}">
                    @if($fawaterakConfigured)
                        <i class="fas fa-check-circle ms-1"></i>
                        المفاتيح مضبوطة على الخادم (وضع التكامل: <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">{{ $fawaterakIntegration }}</code>).
                    @else
                        <i class="fas fa-exclamation-triangle ms-1 text-metal"></i>
                        أضف المفاتيح في <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">.env</code>
                        ثم <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">php artisan config:clear</code>.
                    @endif
                </div>

                <div class="rounded-xl border border-line bg-canvas px-4 py-3 text-xs leading-7 text-muted">
                    من لوحة فواتيرك: <strong class="text-ink">Integrations → Fawaterak</strong>
                    انسخ <span dir="ltr">Vendor / API Key</span> و <span dir="ltr">Provider Key</span>
                    وضعها في الخادم:
                    <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">FAWATERAK_VENDOR_KEY</code>
                    و
                    <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">FAWATERAK_PROVIDER_KEY</code>.
                </div>
            </div>
        </article>

        {{-- Stripe --}}
        <article class="{{ $panel }}">
            <div class="{{ $sectionHead }}">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fab fa-stripe-s text-sm"></i></span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-ink">Stripe Checkout</h3>
                    <p class="mt-0.5 text-xs leading-6 text-muted">بطاقات عالمية عبر Checkout المستضاف + Webhook لتأكيد الدفع. العملة الافتراضية: {{ platform_currency() }}.</p>
                </div>
                @if($stripeEnabled && $stripeConfigured)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-semibold text-accent">يعمل</span>
                @elseif($stripeEnabled)
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-metal/30 bg-canvas px-2.5 py-1 text-xs font-medium text-metal">مفعّل بدون مفاتيح</span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-muted">موقوف</span>
                @endif
            </div>
            <div class="{{ $sectionBody }}">
                <input type="hidden" name="stripe_gateway_enabled" value="0">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="stripe_gateway_enabled" value="1" class="mt-1 rounded border-line text-accent focus:ring-accent"
                           @checked((string) old('stripe_gateway_enabled', $stripeEnabled ? '1' : '0') === '1')>
                    <span class="text-sm leading-7 text-muted">
                        <span class="mb-0.5 block font-semibold text-ink">تشغيل Stripe</span>
                        يظهر زر «الدفع عبر Stripe» في إتمام طلب الكورس والباقات.
                    </span>
                </label>

                <div class="rounded-xl border border-metal/30 bg-canvas px-4 py-3 text-xs leading-7 text-ink">
                    <p class="font-semibold">تنبيه أهلية مهم</p>
                    <p class="mt-1 text-muted">
                        صفحة Stripe الرسمية للتوفر العالمي قد تدرج دولاً مثل الإمارات ولا تدرج السعودية ولا مصر لحساب التاجر المباشر.
                        تأكدوا من دولة تسجيل الكيان (أو مسار مثل Stripe Atlas) قبل الاعتماد على مدى / Apple Pay في الإنتاج، أو قيّموا بوابة إقليمية تدعم مدى.
                    </p>
                </div>

                <div class="rounded-xl border border-line bg-canvas px-4 py-3 text-xs leading-7 text-muted">
                    من <a href="https://dashboard.stripe.com/apikeys" class="font-semibold text-accent underline" target="_blank" rel="noopener">Stripe Dashboard → API keys</a>
                    انسخ Publishable و Secret (Test أو Live).
                    أضف Webhook على:
                    <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">{{ $stripeWebhookUrl }}</code>
                    — الأحداث: <span dir="ltr">checkout.session.completed</span> و <span dir="ltr">checkout.session.async_payment_succeeded</span>.
                    انسخ Signing secret والصقه أدناه.
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $label }}" for="stripe_mode">البيئة</label>
                        <select name="stripe_mode" id="stripe_mode" class="{{ $input }}">
                            <option value="test" @selected(old('stripe_mode', $stripeMode) === 'test')>Test (تجربة)</option>
                            <option value="live" @selected(old('stripe_mode', $stripeMode) === 'live')>Live (إنتاج)</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="stripe_currency">عملة Stripe</label>
                        <select name="stripe_currency" id="stripe_currency" class="{{ $input }}">
                            @foreach(['SAR' => 'SAR — ريال سعودي', 'USD' => 'USD — دولار', 'EUR' => 'EUR — يورو', 'GBP' => 'GBP — إسترليني', 'AED' => 'AED — درهم', 'EGP' => 'EGP — جنيه'] as $code => $labelText)
                                <option value="{{ $code }}" @selected(old('stripe_currency', $stripeCurrency) === $code)>{{ $labelText }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="stripe_publishable_key">Publishable Key</label>
                        <input type="text" name="stripe_publishable_key" id="stripe_publishable_key" dir="ltr" autocomplete="off"
                               value="{{ old('stripe_publishable_key', $stripePublishableKey) }}"
                               class="{{ $input }} font-mono" placeholder="pk_test_...">
                        @error('stripe_publishable_key')
                            <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="stripe_secret_key">Secret Key</label>
                        <input type="password" name="stripe_secret_key" id="stripe_secret_key" dir="ltr" autocomplete="new-password"
                               value="" class="{{ $input }} font-mono" placeholder="{{ $stripeHasSecret ? 'محفوظ — اتركه فارغاً للإبقاء على السر الحالي' : 'sk_test_...' }}">
                        <p class="mt-1.5 text-[11px] text-muted">يُشفَّر في قاعدة البيانات ولا يُعرض بعد الحفظ.</p>
                        @error('stripe_secret_key')
                            <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="stripe_webhook_secret">Webhook Signing Secret</label>
                        <input type="password" name="stripe_webhook_secret" id="stripe_webhook_secret" dir="ltr" autocomplete="new-password"
                               value="" class="{{ $input }} font-mono" placeholder="{{ $stripeHasWebhookSecret ? 'محفوظ — اتركه فارغاً للإبقاء على السر الحالي' : 'whsec_...' }}">
                        @error('stripe_webhook_secret')
                            <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-line pt-4">
                    <button type="submit" form="stripeTestForm" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl border border-line bg-canvas px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                        <i class="fas fa-plug"></i>
                        اختبار اتصال Stripe المحفوظ
                    </button>
                    <p class="text-xs text-muted">احفظ المفاتيح أولاً من زر الحفظ أسفل الصفحة، ثم اختبر.</p>
                </div>
            </div>
        </article>

        {{-- Kashier --}}
        <article class="{{ $panel }}">
            <div class="{{ $sectionHead }}">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-university text-sm"></i></span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-ink">كاشير (Kashier)</h3>
                    <p class="mt-0.5 text-xs leading-6 text-muted">تحويل الطالب لصفحة كاشير ثم تفعيل طلب الكورس بعد العودة.</p>
                </div>
                @if($kashierEnabled && $kashierConfigured)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-semibold text-accent">يعمل</span>
                @elseif($kashierEnabled)
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-metal/30 bg-canvas px-2.5 py-1 text-xs font-medium text-metal">مفعّل بدون مفاتيح</span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-muted">موقوف</span>
                @endif
            </div>
            <div class="{{ $sectionBody }}">
                <input type="hidden" name="kashier_gateway_enabled" value="0">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="kashier_gateway_enabled" value="1" class="mt-1 rounded border-line text-accent focus:ring-accent"
                           @checked((string) old('kashier_gateway_enabled', $kashierEnabled ? '1' : '0') === '1')>
                    <span class="text-sm leading-7 text-muted">
                        <span class="mb-0.5 block font-semibold text-ink">تشغيل كاشير</span>
                        يظهر زر الدفع عبر كاشير في إتمام طلب الكورس. كاشير يقبل روابط HTTPS فقط.
                    </span>
                </label>

                <div class="rounded-xl border border-line bg-canvas px-4 py-3 text-xs leading-7 text-muted">
                    من <a href="https://merchant.kashier.io/" class="font-semibold text-accent underline" target="_blank" rel="noopener">لوحة تاجر كاشير</a>
                    انسخ <strong class="text-ink">Merchant ID (MID)</strong> و<strong class="text-ink">API Key</strong> و<strong class="text-ink">Secret</strong>.
                    رابط العودة الافتراضي:
                    <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">{{ $kashierCallbackUrl }}</code>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $label }}" for="kashier_mode">البيئة</label>
                        <select name="kashier_mode" id="kashier_mode" class="{{ $input }}">
                            <option value="test" @selected(old('kashier_mode', $kashierMode) === 'test')>Test (تجربة)</option>
                            <option value="live" @selected(old('kashier_mode', $kashierMode) === 'live')>Live (إنتاج)</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="kashier_currency">العملة</label>
                        <select name="kashier_currency" id="kashier_currency" class="{{ $input }}">
                            @foreach(['SAR' => 'SAR — ريال سعودي', 'USD' => 'USD — دولار', 'EUR' => 'EUR — يورو', 'GBP' => 'GBP — إسترليني', 'EGP' => 'EGP — جنيه'] as $code => $labelText)
                                <option value="{{ $code }}" @selected(old('kashier_currency', $kashierCurrency) === $code)>{{ $labelText }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="kashier_mid">Merchant ID (MID)</label>
                        <input type="text" name="kashier_mid" id="kashier_mid" dir="ltr" autocomplete="off"
                               value="{{ old('kashier_mid', $kashierMid) }}"
                               class="{{ $input }} font-mono" placeholder="MID-...">
                        @error('kashier_mid')
                            <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}" for="kashier_api_key">API Key</label>
                        <input type="password" name="kashier_api_key" id="kashier_api_key" dir="ltr" autocomplete="new-password"
                               value="" class="{{ $input }} font-mono" placeholder="{{ $kashierHasApiKey ? 'محفوظ — اتركه فارغاً للإبقاء على المفتاح الحالي' : 'الصق API Key هنا' }}">
                        @error('kashier_api_key')
                            <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="kashier_secret">Secret</label>
                        <input type="password" name="kashier_secret" id="kashier_secret" dir="ltr" autocomplete="new-password"
                               value="" class="{{ $input }} font-mono" placeholder="{{ $kashierHasSecret ? 'محفوظ — اتركه فارغاً للإبقاء على السر الحالي' : 'الصق Secret هنا' }}">
                        @error('kashier_secret')
                            <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="kashier_merchant_redirect_url">رابط العودة (اختياري)</label>
                        <input type="url" name="kashier_merchant_redirect_url" id="kashier_merchant_redirect_url" dir="ltr"
                               value="{{ old('kashier_merchant_redirect_url', $kashierRedirectUrl) }}"
                               class="{{ $input }} font-mono" placeholder="{{ $kashierCallbackUrl }}">
                        @error('kashier_merchant_redirect_url')
                            <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </article>

        {{-- PayPal archived --}}
        <article class="{{ $panel }} opacity-95">
            <div class="{{ $sectionHead }}">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-canvas text-muted"><i class="fab fa-paypal text-sm"></i></span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-ink">PayPal <span class="text-xs font-medium text-muted">(مؤرشف)</span></h3>
                    <p class="mt-0.5 text-xs leading-6 text-muted">استُبدل بـ Stripe. المفاتيح تُحفظ للسجلات فقط ولن تظهر للطالب.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-muted">معطّل نهائياً من المركز</span>
            </div>
            <div class="{{ $sectionBody }}">
                <input type="hidden" name="paypal_gateway_enabled" value="0">
                <div class="rounded-xl border border-line bg-canvas px-4 py-3 text-xs leading-7 text-muted">
                    يمكن تحديث بيانات الربط أدناه للأرشيف أو اختبار الاتصال، لكن التفعيل للطالب مقفل. Webhook القديم:
                    <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">{{ $paypalWebhookUrl }}</code>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $label }}" for="paypal_mode">البيئة</label>
                        <select name="paypal_mode" id="paypal_mode" class="{{ $input }}">
                            <option value="sandbox" @selected(old('paypal_mode', $paypalMode) === 'sandbox')>Sandbox</option>
                            <option value="live" @selected(old('paypal_mode', $paypalMode) === 'live')>Live</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="paypal_currency">العملة</label>
                        <select name="paypal_currency" id="paypal_currency" class="{{ $input }}">
                            @foreach(['SAR' => 'SAR', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'EGP' => 'EGP'] as $code => $labelText)
                                <option value="{{ $code }}" @selected(old('paypal_currency', $paypalCurrency) === $code)>{{ $labelText }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="paypal_client_id">Client ID</label>
                        <input type="text" name="paypal_client_id" id="paypal_client_id" dir="ltr" autocomplete="off"
                               value="{{ old('paypal_client_id', $paypalClientId) }}"
                               class="{{ $input }} font-mono" placeholder="A21AAF...">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="paypal_client_secret">Secret</label>
                        <input type="password" name="paypal_client_secret" id="paypal_client_secret" dir="ltr" autocomplete="new-password"
                               value="" class="{{ $input }} font-mono" placeholder="{{ $paypalHasSecret ? 'محفوظ — اتركه فارغاً للإبقاء' : 'Secret' }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="paypal_webhook_id">Webhook ID</label>
                        <input type="text" name="paypal_webhook_id" id="paypal_webhook_id" dir="ltr" autocomplete="off"
                               value="{{ old('paypal_webhook_id', $paypalWebhookId) }}"
                               class="{{ $input }} font-mono" placeholder="WH-...">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-line pt-4">
                    <button type="submit" form="paypalTestForm" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl border border-line bg-canvas px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                        <i class="fas fa-plug"></i>
                        اختبار اتصال PayPal (أرشيف)
                    </button>
                </div>
            </div>
        </article>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white shadow-soft transition hover:bg-accent/90">
                <i class="fas fa-save"></i>
                حفظ تفعيل البوابات
            </button>
        </div>
    </form>

    <form id="paypalTestForm" method="post" action="{{ route('admin.payment-gateways.paypal.test') }}" class="hidden">@csrf</form>
    <form id="stripeTestForm" method="post" action="{{ route('admin.payment-gateways.stripe.test') }}" class="hidden">@csrf</form>
</div>
@endsection
