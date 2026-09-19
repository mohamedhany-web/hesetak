@extends('layouts.admin')

@section('title', 'إعدادات النظام')
@section('page_title', 'إعدادات النظام')
@section('header', 'إعدادات النظام')

@section('content')
@php
    $input = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $label = 'mb-1.5 block text-xs font-medium text-muted';
    $sectionHead = 'flex flex-wrap items-center gap-3 border-b border-line bg-canvas/60 px-5 py-4';
    $sectionBody = 'space-y-5 p-5 sm:p-6';
    $panel = 'overflow-hidden rounded-2xl border border-line bg-surface shadow-soft';
@endphp

<div class="space-y-5 pb-8">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0 max-w-3xl">
            <p class="text-xs font-medium text-muted">لوحة التحكم · إعدادات المنصة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">مركز إعدادات النظام</h2>
            <p class="mt-2 text-sm leading-7 text-muted">
                ضبط الفوتر والسوشيال، شعار اللوحة، و<span class="font-medium text-ink">المصادقة الثنائية لحسابات الأدمن</span>.
                بوابات الدفع (فواتيرك وPayPal وكاشير) تُدار من
                <a href="{{ route('admin.payment-gateways.index') }}" class="font-medium text-accent underline">مركز بوابات الدفع</a>
                في إدارة النظام.
                اترك أي حقل فوتر فارغاً واحفظ لاستعادة الافتراضي لهذا الحقل.
            </p>
        </div>
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-external-link-alt text-xs"></i>
            معاينة الموقع
        </a>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                <i class="fas fa-image text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">شعار اللوحة</p>
            <p class="mt-1 text-sm font-semibold text-ink">{{ $adminPanelLogoUrl ? 'مرفوع' : 'افتراضي (حصتك)' }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $onlineGatewaysEnabledCount > 0 ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                <i class="fas fa-credit-card text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">بوابات الدفع</p>
            <p class="mt-1">
                <a href="{{ route('admin.payment-gateways.index') }}" class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $onlineGatewaysEnabledCount > 0 ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                    {{ $onlineGatewaysEnabledCount > 0 ? $onlineGatewaysEnabledCount.' مفعّلة' : 'إدارة التفعيل' }}
                </a>
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $adminTwoFactorRequired ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                <i class="fas fa-shield-alt text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">المصادقة الثنائية</p>
            <p class="mt-1">
                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $adminTwoFactorRequired ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                    {{ $adminTwoFactorRequired ? 'مفعّل' : 'غير مفعّل' }}
                </span>
            </p>
        </article>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('info'))
        <div class="flex items-center gap-3 rounded-2xl border border-accent/20 bg-accent-soft px-4 py-3 text-sm font-medium text-accent shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-surface text-accent"><i class="fas fa-info-circle text-sm"></i></span>
            <p>{{ session('info') }}</p>
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-danger/20 bg-danger/5 p-4 text-sm text-danger shadow-soft">
            <p class="mb-2 font-semibold">يرجى تصحيح ما يلي:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('admin.system-settings.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        {{-- 1) Logo --}}
        <article class="{{ $panel }}">
            <div class="{{ $sectionHead }}">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-sm font-semibold text-accent">1</span>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-ink">شعار لوحة التحكم وأيقونة الموقع</h3>
                    <p class="mt-0.5 text-xs leading-6 text-muted">يظهر في الشريط الجانبي، النافبار العام، الشريط العلوي للأدمن، وكـ favicon.</p>
                </div>
            </div>
            <div class="{{ $sectionBody }}">
                <div class="rounded-xl border border-line bg-canvas px-4 py-3 text-xs leading-6 text-muted">
                    <strong class="text-ink">محلياً:</strong> نفّذ <code class="rounded bg-surface px-1 text-[10px]" dir="ltr">php artisan storage:link</code>
                    وتأكد أن <code class="rounded bg-surface px-1 text-[10px]" dir="ltr">APP_URL</code> يطابق المتصفح.
                    <br>
                    <strong class="text-ink">Cloudflare R2:</strong>
                    <code class="rounded bg-surface px-1 text-[10px]" dir="ltr">ADMIN_BRANDING_DISK=r2</code>
                    مع مفاتيح AWS ثم <code class="rounded bg-surface px-1 text-[10px]" dir="ltr">php artisan config:clear</code>.
                </div>
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="shrink-0">
                        @if($adminPanelLogoUrl)
                            <div class="flex size-24 items-center justify-center rounded-2xl border border-dashed border-line bg-canvas p-2">
                                <img src="{{ $adminPanelLogoUrl }}" alt="" class="max-h-full max-w-full object-contain">
                            </div>
                        @else
                            <div class="flex size-24 items-center justify-center rounded-2xl bg-accent text-3xl font-semibold text-white">G</div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 space-y-3">
                        <label class="{{ $label }}">رفع شعار جديد</label>
                        <input type="file" name="admin_panel_logo" accept="image/jpeg,image/png,image/webp,image/gif"
                               class="block w-full text-sm text-muted file:me-4 file:rounded-xl file:border-0 file:bg-accent-soft file:px-4 file:py-2 file:text-sm file:font-medium file:text-accent hover:file:bg-accent hover:file:text-white">
                        <p class="text-xs text-muted">JPG, PNG, WebP, GIF — حتى 2 ميغابايت. يُفضّل مربع بخلفية شفافة أو فاتحة.</p>
                        @if($adminPanelLogoUrl)
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-danger">
                                <input type="checkbox" name="remove_admin_panel_logo" value="1" class="rounded border-line text-danger focus:ring-accent" @checked(old('remove_admin_panel_logo'))>
                                <span>حذف الشعار الحالي والعودة للحرف الافتراضي</span>
                            </label>
                        @endif
                    </div>
                </div>
            </div>
        </article>

        {{-- R2 --}}
        <article class="{{ $panel }}">
            <div class="{{ $sectionHead }}">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-metal"><i class="fas fa-cloud text-sm"></i></span>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-ink">رابط عرض الصور (Cloudflare R2)</h3>
                    <p class="mt-0.5 text-xs leading-6 text-muted">للشعار، السلايدر، صور الكورسات، وكل ملفات المنصة.</p>
                </div>
            </div>
            <div class="{{ $sectionBody }}">
                <div>
                    <label class="{{ $label }}" for="r2_public_url">R2 Public URL</label>
                    <input type="url" id="r2_public_url" name="r2_public_url" value="{{ old('r2_public_url', $r2PublicUrl) }}"
                           class="{{ $input }} font-mono" dir="ltr" placeholder="https://pub-xxxxxxxx.r2.dev">
                    <p class="mt-2 text-xs leading-6 text-muted">
                        من Cloudflare: R2 → Bucket → Public access → انسخ رابط <code class="rounded bg-canvas px-1 text-[10px]" dir="ltr">r2.dev</code>.
                        لا تستخدم <code class="rounded bg-canvas px-1 text-[10px]" dir="ltr">cloudflarestorage.com</code>.
                    </p>
                    @error('r2_public_url')
                        <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </article>

        {{-- Footer --}}
        <article class="{{ $panel }}">
            <div class="{{ $sectionHead }}">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-sm font-semibold text-accent">2</span>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-ink">فوتر الموقع العام</h3>
                    <p class="mt-0.5 text-xs leading-6 text-muted">يُعرض في الصفحة الرئيسية والصفحات العامة.</p>
                </div>
            </div>
            <div class="{{ $sectionBody }} space-y-8">
                <section class="space-y-4">
                    <h4 class="border-b border-line pb-2 text-sm font-semibold text-ink">الهوية والنص التعريفي</h4>
                    <div>
                        <label class="{{ $label }}">السطر تحت اسم حصتك</label>
                        <input type="text" name="footer_brand_tagline" value="{{ old('footer_brand_tagline', $values['footer_brand_tagline']) }}"
                               class="{{ $input }}" placeholder="{{ $defaults['footer_brand_tagline'] }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">فقرة تعريفية قصيرة</label>
                        <textarea name="footer_blurb" rows="3" class="min-h-[96px] w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                                  placeholder="{{ $defaults['footer_blurb'] }}">{{ old('footer_blurb', $values['footer_blurb']) }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $label }}">السطر بجانب حقوق النشر</label>
                        <input type="text" name="footer_bottom_tagline" value="{{ old('footer_bottom_tagline', $values['footer_bottom_tagline']) }}"
                               class="{{ $input }}" placeholder="{{ $defaults['footer_bottom_tagline'] }}">
                    </div>
                </section>

                <section class="space-y-4">
                    <h4 class="border-b border-line pb-2 text-sm font-semibold text-ink">التواصل</h4>
                    <div>
                        <label class="{{ $label }}">البريد الإلكتروني</label>
                        <input type="email" name="footer_email" value="{{ old('footer_email', $values['footer_email']) }}"
                               class="{{ $input }}" dir="ltr" placeholder="{{ $defaults['footer_email'] }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">رقم الهاتف</label>
                        <input type="text" name="footer_phone" value="{{ old('footer_phone', $values['footer_phone']) }}"
                               class="{{ $input }}" dir="ltr" placeholder="{{ $defaults['footer_phone'] }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">رابط واتساب</label>
                        <input type="url" name="footer_whatsapp_url" value="{{ old('footer_whatsapp_url', $values['footer_whatsapp_url']) }}"
                               class="{{ $input }}" dir="ltr" placeholder="{{ $defaults['footer_whatsapp_url'] }}">
                    </div>
                </section>

                <section class="space-y-4">
                    <h4 class="border-b border-line pb-2 text-sm font-semibold text-ink">وسائل التواصل الاجتماعي</h4>
                    <p class="text-xs text-muted">تظهر الأيقونة في الفوتر فقط عند ملء الرابط.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach([
                            'social_facebook_url' => 'Facebook',
                            'social_x_url' => 'X (Twitter)',
                            'social_instagram_url' => 'Instagram',
                            'social_youtube_url' => 'YouTube',
                            'social_linkedin_url' => 'LinkedIn',
                            'social_tiktok_url' => 'TikTok',
                            'social_telegram_url' => 'Telegram',
                            'social_snapchat_url' => 'Snapchat',
                        ] as $field => $socialLabel)
                            <div>
                                <label class="{{ $label }}">{{ $socialLabel }}</label>
                                <input type="url" name="{{ $field }}" value="{{ old($field, $values[$field]) }}"
                                       class="{{ $input }}" dir="ltr" placeholder="https://">
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </article>

        <article class="{{ $panel }}">
            <div class="{{ $sectionHead }}">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-credit-card text-sm"></i></span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-ink">بوابات الدفع</h3>
                    <p class="mt-0.5 text-xs leading-6 text-muted">تشغيل وإيقاف فواتيرك وPayPal وكاشير صار من صفحة مستقلة تحت إدارة النظام.</p>
                </div>
            </div>
            <div class="{{ $sectionBody }}">
                <p class="text-sm leading-7 text-muted">
                    حالياً:
                    فواتيرك {{ $fawaterakGatewayEnabled ? 'مفعّلة' : 'موقوفة' }}،
                    PayPal {{ $paypalGatewayEnabled ? 'مفعّل' : 'موقوف' }}،
                    كاشير {{ $kashierGatewayEnabled ? 'مفعّل' : 'موقوف' }}.
                </p>
                <a href="{{ route('admin.payment-gateways.index') }}" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white shadow-soft transition hover:bg-accent/90">
                    <i class="fas fa-sliders-h"></i>
                    فتح مركز بوابات الدفع
                </a>
            </div>
        </article>
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white shadow-soft transition hover:bg-accent/90">
                <i class="fas fa-save"></i>
                حفظ كل الإعدادات
            </button>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line bg-surface px-5 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-external-link-alt"></i>
                معاينة الموقع العام
            </a>
        </div>
    </form>

    {{-- 3) 2FA — خارج النموذج الرئيسي --}}
    <article class="{{ $panel }}">
        <div class="{{ $sectionHead }}">
            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-sm font-semibold text-accent">3</span>
            <div class="min-w-0 flex-1">
                <h3 class="text-base font-semibold text-ink">المصادقة الثنائية للمنصة</h3>
                <p class="mt-0.5 text-xs leading-6 text-muted">إلزام رمز البريد بعد كلمة المرور لحسابات المدير العام والأدمن فقط.</p>
            </div>
            @if($adminTwoFactorRequired)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-semibold text-accent">
                    <i class="fas fa-shield-alt"></i> مفعّل
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-muted">غير مفعّل</span>
            @endif
        </div>
        <div class="{{ $sectionBody }}">
            @if($errors->has('two_factor'))
                <div class="rounded-xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm font-medium text-danger">
                    {{ $errors->first('two_factor') }}
                </div>
            @endif

            <div class="rounded-xl border border-metal/25 bg-canvas px-4 py-3 text-sm leading-7 text-ink">
                <i class="fas fa-exclamation-triangle ms-1 text-metal"></i>
                تأكد أن إعدادات البريد تعمل قبل التفعيل. يمكن ضبط
                <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">ADMIN_2FA_REQUIRED</code>
                في البيئة عند أول تشغيل.
            </div>

            <div class="rounded-xl border border-accent/20 bg-accent-soft px-4 py-3 text-sm leading-7 text-accent">
                <i class="fas fa-info-circle ms-1"></i>
                لا يُفعّل الإلزام إلا بعد إرسال الرمز ثم تأكيده في الصفحة التالية.
            </div>

            @if(!$admin2faAppliesToCurrentUserRole)
                <div class="rounded-xl border border-line bg-canvas px-4 py-3 text-sm leading-7 text-muted">
                    <i class="fas fa-user-shield ms-1 text-accent"></i>
                    دور حسابك الحالي (<strong class="text-ink">{{ auth()->user()->role }}</strong>) ليس ضمن الأدوار الملزَمة.
                    الإلزام ينطبق على <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">super_admin</code> و
                    <code class="rounded bg-surface px-1 text-[11px]" dir="ltr">admin</code> فقط.
                </div>
            @endif

            @if(!$adminTwoFactorRequired)
                <p class="text-sm leading-7 text-muted">اضغط الزر لإرسال رمز تحقق إلى بريدك، ثم أكّد التفعيل في الصفحة التالية.</p>
                <form method="post" action="{{ route('admin.system-settings.two-factor.enable-request') }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white transition hover:bg-accent/90">
                        <i class="fas fa-paper-plane"></i>
                        تفعيل إلزام المصادقة الثنائية
                    </button>
                </form>
            @else
                <p class="text-sm leading-7 text-muted">الإلزام مفعّل. لتعطيله أدخل كلمة مرور حسابك.</p>
                <form method="post" action="{{ route('admin.system-settings.two-factor.disable') }}" class="max-w-md space-y-4">
                    @csrf
                    <div>
                        <label class="{{ $label }}">كلمة المرور</label>
                        <input type="password" name="password" required autocomplete="current-password" class="{{ $input }}">
                        @error('password')
                            <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-danger/30 bg-danger/5 px-5 text-sm font-medium text-danger transition hover:bg-danger/10">
                        <i class="fas fa-power-off"></i>
                        تعطيل إلزام المصادقة الثنائية
                    </button>
                </form>
            @endif
        </div>
    </article>
</div>
@endsection
