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
    $enabledCount = (int) $fawaterakEnabled + (int) $paypalEnabled + (int) $kashierEnabled;
@endphp

<div class="space-y-5 pb-8">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0 max-w-3xl">
            <p class="text-xs font-medium text-muted">إدارة النظام · بوابات الدفع</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">مركز بوابات الدفع</h2>
            <p class="mt-2 text-sm leading-7 text-muted">
                شغّل أو أوقف كل بوابة من هنا. البوابة المفعّلة تظهر للطالب في صفحة الدفع؛ والمعطّلة تُخفى فوراً.
                الدفع اليدوي (رفع الإيصال) يُغلق تلقائياً إذا كانت أي بوابة إلكترونية مفعّلة.
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
            <p class="mt-1 text-lg font-semibold text-ink">{{ $enabledCount }} من 3</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">فواتيرك</p>
            <p class="mt-1 text-sm font-semibold {{ $fawaterakEnabled && $fawaterakConfigured ? 'text-accent' : 'text-muted' }}">
                {{ $fawaterakEnabled && $fawaterakConfigured ? 'يعمل' : ($fawaterakEnabled ? 'مفعّل — المفاتيح ناقصة' : 'موقوف') }}
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">PayPal</p>
            <p class="mt-1 text-sm font-semibold {{ $paypalEnabled && $paypalConfigured ? 'text-accent' : 'text-muted' }}">
                {{ $paypalEnabled && $paypalConfigured ? 'يعمل' : ($paypalEnabled ? 'مفعّل — الربط ناقص' : 'موقوف') }}
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
                        يظهر إطار الدفع الرسمي ويُعطّل التحويل اليدوي ورفع الإيصال طالما أي بوابة إلكترونية مفعّلة.
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
                    سجّل نطاق الـ IFrame بصيغة HTTPS بدون شرطة مائلة في النهاية (مثل <span dir="ltr">https://glottical.com</span>).
                </div>

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

        {{-- PayPal --}}
        <article class="{{ $panel }}">
            <div class="{{ $sectionHead }}">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fab fa-paypal text-sm"></i></span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-ink">PayPal</h3>
                    <p class="mt-0.5 text-xs leading-6 text-muted">تحويل الطالب لحساب PayPal ثم تفعيل الطلب بعد التأكيد.</p>
                </div>
                @if($paypalEnabled && $paypalConfigured)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-semibold text-accent">يعمل</span>
                @elseif($paypalEnabled)
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-metal/30 bg-canvas px-2.5 py-1 text-xs font-medium text-metal">مفعّل بدون مفاتيح</span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-muted">موقوف</span>
                @endif
            </div>
            <div class="{{ $sectionBody }}">
                <input type="hidden" name="paypal_gateway_enabled" value="0">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="paypal_gateway_enabled" value="1" class="mt-1 rounded border-line text-accent focus:ring-accent"
                           @checked((string) old('paypal_gateway_enabled', $paypalEnabled ? '1' : '0') === '1')>
                    <span class="text-sm leading-7 text-muted">
                        <span class="mb-0.5 block font-semibold text-ink">تشغيل PayPal</span>
                        يظهر زر «الدفع عبر PayPal» في إتمام طلب الكورس والباقات.
                    </span>
                </label>

                <div class="rounded-xl border border-line bg-canvas px-4 py-3 text-xs leading-7 text-muted">
                    <p class="font-semibold text-ink">من أين تجيب بيانات PayPal (ليست من داخل جلوتكال)</p>
                    <ol class="mt-2 list-decimal space-y-1 pr-5">
                        <li>افتح <a href="https://developer.paypal.com/dashboard/applications/sandbox" class="font-semibold text-accent underline" target="_blank" rel="noopener">PayPal Developer Dashboard</a> وسجّل الدخول بحساب التاجر.</li>
                        <li>من أعلى الصفحة اختر <strong class="text-ink">Sandbox</strong> للتجربة أو <strong class="text-ink">Live</strong> للإنتاج.</li>
                        <li>ادخل <strong class="text-ink">Apps &amp; Credentials</strong> ثم أنشئ تطبيقاً (Create App) من نوع REST API.</li>
                        <li>انسخ <strong class="text-ink">Client ID</strong> و<strong class="text-ink">Secret</strong> والصقهما أدناه.</li>
                        <li>من نفس التطبيق أضف Webhook على:
                            <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">{{ $paypalWebhookUrl }}</code>
                            — الأحداث: <span dir="ltr">CHECKOUT.ORDER.APPROVED</span> و <span dir="ltr">PAYMENT.CAPTURE.COMPLETED</span>.
                            انسخ Webhook ID إن ظهر.
                        </li>
                    </ol>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $label }}" for="paypal_mode">البيئة</label>
                        <select name="paypal_mode" id="paypal_mode" class="{{ $input }}">
                            <option value="sandbox" @selected(old('paypal_mode', $paypalMode) === 'sandbox')>Sandbox (تجربة)</option>
                            <option value="live" @selected(old('paypal_mode', $paypalMode) === 'live')>Live (إنتاج)</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="paypal_currency">عملة PayPal الافتراضية</label>
                        <select name="paypal_currency" id="paypal_currency" class="{{ $input }}">
                            @foreach(['SAR' => 'SAR — ريال سعودي', 'USD' => 'USD — دولار', 'EUR' => 'EUR — يورو', 'GBP' => 'GBP — إسترليني', 'EGP' => 'EGP — جنيه'] as $code => $labelText)
                                <option value="{{ $code }}" @selected(old('paypal_currency', $paypalCurrency) === $code)>{{ $labelText }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="paypal_client_id">Client ID</label>
                        <input type="text" name="paypal_client_id" id="paypal_client_id" dir="ltr" autocomplete="off"
                               value="{{ old('paypal_client_id', $paypalClientId) }}"
                               class="{{ $input }} font-mono" placeholder="A21AAF...">
                        @error('paypal_client_id')
                            <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="paypal_client_secret">Secret</label>
                        <input type="password" name="paypal_client_secret" id="paypal_client_secret" dir="ltr" autocomplete="new-password"
                               value="" class="{{ $input }} font-mono" placeholder="{{ $paypalHasSecret ? 'محفوظ — اتركه فارغاً للإبقاء على السر الحالي' : 'الصق Secret هنا' }}">
                        <p class="mt-1.5 text-[11px] text-muted">يُشفَّر في قاعدة البيانات ولا يُعرض بعد الحفظ.</p>
                        @error('paypal_client_secret')
                            <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="paypal_webhook_id">Webhook ID</label>
                        <input type="text" name="paypal_webhook_id" id="paypal_webhook_id" dir="ltr" autocomplete="off"
                               value="{{ old('paypal_webhook_id', $paypalWebhookId) }}"
                               class="{{ $input }} font-mono" placeholder="WH-...">
                        <p class="mt-1.5 text-[11px] text-muted">مطلوب للتحقق من توقيع الإشعارات. بدون هذا المعرّف لن يُقبل Webhook PayPal.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-line pt-4">
                    <button type="submit" form="paypalTestForm" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl border border-line bg-canvas px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                        <i class="fas fa-plug"></i>
                        اختبار اتصال PayPal المحفوظ
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
                    أو <a href="https://developers.kashier.io/" class="font-semibold text-accent underline" target="_blank" rel="noopener">وثائق المطوّر</a>
                    انسخ <strong class="text-ink">Merchant ID (MID)</strong> و<strong class="text-ink">API Key</strong> و<strong class="text-ink">Secret</strong>
                    لبيئة Test أو Live.
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
                        <p class="mt-1.5 text-[11px] text-muted">عند التبديل الصق مفاتيح نفس البيئة.</p>
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
                        <p class="mt-1.5 text-[11px] text-muted">يُشفَّر في قاعدة البيانات ولا يُعرض بعد الحفظ.</p>
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

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white shadow-soft transition hover:bg-accent/90">
                <i class="fas fa-save"></i>
                حفظ تفعيل البوابات
            </button>
        </div>
    </form>

    <form id="paypalTestForm" method="post" action="{{ route('admin.payment-gateways.paypal.test') }}" class="hidden">
        @csrf
    </form>
</div>
@endsection
