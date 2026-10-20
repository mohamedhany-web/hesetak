@extends('layouts.admin')

@section('title', 'إضافة كوبون جديد - ' . config('app.name'))
@section('page_title', 'إضافة كوبون جديد')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $hintClass = 'mt-1.5 text-xs text-muted';
    $errorClass = 'mt-1 text-xs font-medium text-danger';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · العروض والخصومات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إضافة كوبون جديد</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">تصميم واضح لإدخال بيانات الكوبون وتحديد نطاقه بدقة.</p>
        </div>
        <a href="{{ route('admin.coupons.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للقائمة
        </a>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 shadow-soft">
            <p class="text-sm font-semibold text-rose-800 mb-2 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i>
                يوجد أخطاء في البيانات المدخلة
            </p>
            <ul class="list-disc pr-5 text-sm text-rose-700 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.coupons.store') }}" method="POST" class="space-y-5">
        @csrf

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-sliders-h text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">البيانات الأساسية للكوبون</h3>
                        <p class="mt-0.5 text-xs text-muted">الكود، الخصم، والحدود</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">الكود <span class="text-danger">*</span></label>
                    <input type="text" name="code" required value="{{ old('code') }}" class="{{ $fieldClass }} uppercase font-mono" placeholder="WELCOME10">
                    <p class="{{ $hintClass }}">يُحفظ تلقائيًا بأحرف كبيرة ويُستخدم في صفحة الدفع.</p>
                    @error('code')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">العنوان <span class="text-danger">*</span></label>
                    <input type="text" name="title" required value="{{ old('title') }}" class="{{ $fieldClass }}" placeholder="مثال: خصم ترحيبي">
                    @error('title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">نوع الخصم <span class="text-danger">*</span></label>
                    <select name="discount_type" required class="{{ $fieldClass }}">
                        <option value="percentage" {{ old('discount_type', 'percentage') === 'percentage' ? 'selected' : '' }}>نسبة مئوية</option>
                        <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                    </select>
                    @error('discount_type')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">قيمة الخصم <span class="text-danger">*</span></label>
                    <input type="number" name="discount_value" step="0.01" min="0" required value="{{ old('discount_value') }}" class="{{ $fieldClass }}" placeholder="0.00">
                    @error('discount_value')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">الحد الأدنى للطلب {{ currency_unit() }}</label>
                    <input type="number" name="minimum_amount" step="0.01" min="0" value="{{ old('minimum_amount') }}" class="{{ $fieldClass }}" placeholder="اختياري">
                    @error('minimum_amount')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">الحد الأقصى للخصم {{ currency_unit() }}</label>
                    <input type="number" name="maximum_discount" step="0.01" min="0" value="{{ old('maximum_discount') }}" class="{{ $fieldClass }}" placeholder="اختياري">
                    <p class="{{ $hintClass }}">مهم عند اختيار خصم نسبة مئوية.</p>
                    @error('maximum_discount')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">الحد الأقصى لعدد الاستخدامات</label>
                    <input type="number" name="max_uses" min="1" value="{{ old('max_uses') }}" class="{{ $fieldClass }}" placeholder="اتركه فارغًا = غير محدود">
                    @error('max_uses')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">الحد لكل مستخدم</label>
                    <input type="number" name="usage_limit_per_user" min="1" value="{{ old('usage_limit_per_user', 1) }}" class="{{ $fieldClass }}">
                    @error('usage_limit_per_user')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">تاريخ البداية</label>
                    <input type="date" name="valid_from" value="{{ old('valid_from') }}" class="{{ $fieldClass }}">
                    @error('valid_from')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">تاريخ الانتهاء</label>
                    <input type="date" name="valid_until" value="{{ old('valid_until') }}" class="{{ $fieldClass }}">
                    @error('valid_until')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-align-right text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">الوصف</h3>
                        <p class="mt-0.5 text-xs text-muted">وصف داخلي لفريق الإدارة</p>
                    </div>
                </div>
            </div>
            <div class="p-4 sm:p-5">
                <textarea name="description" rows="3" class="{{ $areaClass }}" placeholder="وصف داخلي يساعد فريق الإدارة على تمييز الكوبون">{{ old('description') }}</textarea>
                @error('description')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-bullseye text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">نطاق الكوبون</h3>
                        <p class="mt-0.5 text-xs text-muted">الباقات أو الكورسات المحددة</p>
                    </div>
                </div>
            </div>
            <div class="space-y-5 p-4 sm:p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="applicable_to" value="subscriptions" class="sr-only coupon-scope" {{ old('applicable_to') === 'subscriptions' ? 'checked' : '' }}>
                        <div class="coupon-scope-card rounded-2xl border border-line bg-surface p-4 transition">
                            <p class="font-semibold text-ink mb-1">كوبون للباقات</p>
                            <p class="text-xs text-muted">يُطبَّق في صفحة دفع الاشتراك (Starter / Pro).</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="applicable_to" value="courses" class="sr-only coupon-scope" {{ old('applicable_to', 'courses') === 'courses' ? 'checked' : '' }}>
                        <div class="coupon-scope-card rounded-2xl border border-line bg-surface p-4 transition">
                            <p class="font-semibold text-ink mb-1">كوبون للكورسات</p>
                            <p class="text-xs text-muted">يُطبَّق في شراء كورسات محددة.</p>
                        </div>
                    </label>
                </div>
                @error('applicable_to')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror

                <div id="courseScopeWrap">
                    <label class="{{ $labelClass }}">الكورسات (عند اختيار «كوبون للكورسات»)</label>
                    <div class="max-h-56 overflow-y-auto rounded-xl border border-line bg-canvas p-3 space-y-2">
                        @forelse($courses ?? [] as $c)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="applicable_course_ids[]" value="{{ $c->id }}" {{ in_array($c->id, old('applicable_course_ids', []), true) ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent/20">
                                <span class="text-ink">{{ $c->title }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-muted">لا توجد كورسات في النظام.</p>
                        @endforelse
                    </div>
                    @error('applicable_course_ids')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/40 shadow-soft">
            <div class="border-b border-amber-200 px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <i class="fas fa-user-tag text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">كوبون تسويقي شخصي + عمولة</h3>
                        <p class="mt-0.5 text-xs text-muted">تقييد المستخدمين ومستفيد العمولة</p>
                    </div>
                </div>
            </div>
            <div class="space-y-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">معرّفات المستخدمين المسموح لهم (اختياري)</label>
                    <textarea name="applicable_user_ids_text" rows="2" class="{{ $areaClass }} font-mono text-sm" placeholder="مثال: 12, 45 أو سطر لكل رقم">{{ old('applicable_user_ids_text') }}</textarea>
                    <p class="{{ $hintClass }}">إن تركتها فارغة يمكن لأي مستخدم يملك الكود استخدامه (وفق الشروط). للتسويق المستهدف: أدخل معرّف الطالب وأزل خيار «ظاهر للجميع».</p>
                    @error('applicable_user_ids_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label class="{{ $labelClass }}">مستفيد العمولة (معرّف مستخدم)</label>
                        <input type="number" name="beneficiary_user_id" min="1" value="{{ old('beneficiary_user_id') }}" class="{{ $fieldClass }} font-mono" placeholder="فارغ = بدون عمولة">
                        @error('beneficiary_user_id')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">نسبة العمولة %</label>
                        <input type="number" name="commission_percent" step="0.01" min="0" max="100" value="{{ old('commission_percent') }}" class="{{ $fieldClass }}">
                        @error('commission_percent')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">احتساب العمولة من</label>
                        <select name="commission_on" class="{{ $fieldClass }}">
                            <option value="final_paid" {{ old('commission_on', 'final_paid') === 'final_paid' ? 'selected' : '' }}>المبلغ النهائي بعد الخصم</option>
                            <option value="original_price" {{ old('commission_on') === 'original_price' ? 'selected' : '' }}>السعر الأصلي قبل الخصم</option>
                        </select>
                        @error('commission_on')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="{{ $hintClass }}">تُسجَّل العمولة عند اعتماد الطلب من الإدارة، ثم من «عمولات كوبونات التسويق» يمكن إنشاء مصروف تسويقي؛ عند اعتماد المصروف تُحدَّث الحالة إلى مسوّى.</p>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-toggle-on text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">حالة الكوبون</h3>
                        <p class="mt-0.5 text-xs text-muted">التفعيل والظهور العام</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-6 p-4 sm:p-5">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent/20">
                    <span class="text-sm font-medium text-ink">كوبون نشط</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_public" value="1" {{ old('is_public', true) ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent/20">
                    <span class="text-sm font-medium text-ink">ظاهر للجميع (يمكن إدخال كوده من صفحة الدفع)</span>
                </label>
            </div>
        </article>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-save text-xs"></i> حفظ الكوبون
            </button>
            <a href="{{ route('admin.coupons.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line bg-surface px-6 text-sm font-medium text-ink transition hover:bg-accent-soft hover:text-accent">إلغاء</a>
        </div>
    </form>
</div>

<script>
    (function () {
        const radios = Array.from(document.querySelectorAll('.coupon-scope'));
        const wrap = document.getElementById('courseScopeWrap');

        function refresh() {
            const selected = radios.find(r => r.checked)?.value || 'courses';
            radios.forEach((r) => {
                const card = r.closest('label')?.querySelector('.coupon-scope-card');
                if (!card) return;
                if (r.checked) {
                    card.classList.add('border-accent', 'ring-2', 'ring-accent/20', 'bg-accent-soft');
                    card.classList.remove('border-line');
                } else {
                    card.classList.remove('border-accent', 'ring-2', 'ring-accent/20', 'bg-accent-soft');
                    card.classList.add('border-line');
                }
            });
            if (wrap) {
                const isCourses = selected === 'courses';
                wrap.classList.toggle('hidden', !isCourses);
                wrap.querySelectorAll('input[type="checkbox"]').forEach((c) => {
                    c.disabled = !isCourses;
                    if (!isCourses) c.checked = false;
                });
            }
        }

        radios.forEach(r => r.addEventListener('change', refresh));
        refresh();
    })();
</script>
@endsection
