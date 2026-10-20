@extends('layouts.admin')

@section('title', 'تعديل الكوبون - ' . config('app.name'))
@section('page_title', 'تعديل الكوبون')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $errorClass = 'mt-1 text-xs font-medium text-danger';
    $oldCourseIds = old('applicable_course_ids', $coupon->applicable_course_ids ?? []);
    $oldUserIdsText = old('applicable_user_ids_text', is_array($coupon->applicable_user_ids) && count($coupon->applicable_user_ids) ? implode(', ', $coupon->applicable_user_ids) : '');
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · العروض والخصومات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل الكوبون</h2>
            <p class="mt-1 font-mono text-sm text-muted">{{ $coupon->code }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.coupons.show', $coupon) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-eye text-xs"></i>
                عرض التفاصيل
            </a>
            <a href="{{ route('admin.coupons.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع
            </a>
        </div>
    </section>

    <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-sliders-h text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">البيانات الأساسية</h3>
                        <p class="mt-0.5 text-xs text-muted">الكود، الخصم، والحدود</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">الكود <span class="text-danger">*</span></label>
                    <input type="text" name="code" required value="{{ old('code', $coupon->code) }}" class="{{ $fieldClass }} uppercase font-mono">
                    @error('code')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">العنوان <span class="text-danger">*</span></label>
                    <input type="text" name="title" required value="{{ old('title', $coupon->title ?? $coupon->name) }}" class="{{ $fieldClass }}">
                    @error('title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">نوع الخصم <span class="text-danger">*</span></label>
                    <select name="discount_type" required class="{{ $fieldClass }}">
                        <option value="percentage" {{ old('discount_type', $coupon->discount_type) === 'percentage' ? 'selected' : '' }}>نسبة مئوية</option>
                        <option value="fixed" {{ old('discount_type', $coupon->discount_type) === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                    </select>
                    @error('discount_type')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">قيمة الخصم <span class="text-danger">*</span></label>
                    <input type="number" name="discount_value" step="0.01" min="0" required value="{{ old('discount_value', $coupon->discount_value) }}" class="{{ $fieldClass }}">
                    @error('discount_value')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">الحد الأدنى للطلب {{ currency_unit() }}</label>
                    <input type="number" name="minimum_amount" step="0.01" min="0" value="{{ old('minimum_amount', $coupon->minimum_amount) }}" class="{{ $fieldClass }}">
                    @error('minimum_amount')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">الحد الأقصى للخصم {{ currency_unit() }}</label>
                    <input type="number" name="maximum_discount" step="0.01" min="0" value="{{ old('maximum_discount', $coupon->maximum_discount) }}" class="{{ $fieldClass }}">
                    @error('maximum_discount')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">الحد الأقصى لعدد الاستخدامات</label>
                    <input type="number" name="max_uses" min="1" value="{{ old('max_uses', $coupon->usage_limit) }}" class="{{ $fieldClass }}" placeholder="فارغ = غير محدود">
                    @error('max_uses')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">الحد لكل مستخدم</label>
                    <input type="number" name="usage_limit_per_user" min="1" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? 1) }}" class="{{ $fieldClass }}">
                    @error('usage_limit_per_user')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">تاريخ البداية</label>
                    <input type="date" name="valid_from" value="{{ old('valid_from', $coupon->starts_at ? $coupon->starts_at->format('Y-m-d') : '') }}" class="{{ $fieldClass }}">
                    @error('valid_from')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">تاريخ الانتهاء</label>
                    <input type="date" name="valid_until" value="{{ old('valid_until', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '') }}" class="{{ $fieldClass }}">
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
                    </div>
                </div>
            </div>
            <div class="p-4 sm:p-5">
                <textarea name="description" rows="3" class="{{ $areaClass }}">{{ old('description', $coupon->description) }}</textarea>
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
                        <h3 class="text-base font-semibold text-ink">نطاق التطبيق (الكورسات)</h3>
                        <p class="mt-0.5 text-xs text-muted">تحديد الكورسات والاشتراكات</p>
                    </div>
                </div>
            </div>
            <div class="space-y-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">ينطبق على <span class="text-danger">*</span></label>
                    <select name="applicable_to" required class="{{ $fieldClass }} max-w-xl">
                        <option value="all" {{ old('applicable_to', $coupon->applicable_to ?? 'all') === 'all' ? 'selected' : '' }}>جميع الكورسات (صفحة دفع الكورس)</option>
                        <option value="courses" {{ old('applicable_to', $coupon->applicable_to ?? 'all') === 'courses' ? 'selected' : '' }}>كورسات محددة فقط</option>
                        <option value="specific" {{ old('applicable_to', $coupon->applicable_to ?? 'all') === 'specific' ? 'selected' : '' }}>كورسات محددة + تقييد مستخدمين (اختياري بالأسفل)</option>
                        <option value="subscriptions" {{ old('applicable_to', $coupon->applicable_to ?? 'all') === 'subscriptions' ? 'selected' : '' }}>الاشتراكات فقط (لا يُطبَّق على دفع الكورس)</option>
                    </select>
                    @error('applicable_to')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">الكورسات (عند اختيار «محددة»)</label>
                    <div class="max-h-56 overflow-y-auto rounded-xl border border-line bg-canvas p-3 space-y-2">
                        @forelse($courses ?? [] as $c)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="applicable_course_ids[]" value="{{ $c->id }}" {{ in_array($c->id, array_map('intval', (array) $oldCourseIds), true) ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent/20">
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
                    </div>
                </div>
            </div>
            <div class="space-y-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">معرّفات المستخدمين المسموح لهم (اختياري)</label>
                    <textarea name="applicable_user_ids_text" rows="2" class="{{ $areaClass }} font-mono text-sm" placeholder="مثال: 12, 45">{{ $oldUserIdsText }}</textarea>
                    @error('applicable_user_ids_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label class="{{ $labelClass }}">مستفيد العمولة (معرّف مستخدم)</label>
                        <input type="number" name="beneficiary_user_id" min="1" value="{{ old('beneficiary_user_id', $coupon->beneficiary_user_id) }}" class="{{ $fieldClass }} font-mono" placeholder="فارغ = بدون عمولة">
                        @error('beneficiary_user_id')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">نسبة العمولة %</label>
                        <input type="number" name="commission_percent" step="0.01" min="0" max="100" value="{{ old('commission_percent', $coupon->commission_percent) }}" class="{{ $fieldClass }}">
                        @error('commission_percent')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">احتساب العمولة من</label>
                        <select name="commission_on" class="{{ $fieldClass }}">
                            <option value="final_paid" {{ old('commission_on', $coupon->commission_on ?? 'final_paid') === 'final_paid' ? 'selected' : '' }}>المبلغ النهائي بعد الخصم</option>
                            <option value="original_price" {{ old('commission_on', $coupon->commission_on ?? 'final_paid') === 'original_price' ? 'selected' : '' }}>السعر الأصلي قبل الخصم</option>
                        </select>
                        @error('commission_on')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>
                </div>
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
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-6 p-4 sm:p-5">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent/20">
                    <span class="text-sm font-medium text-ink">كوبون نشط</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_public" value="1" {{ old('is_public', $coupon->is_public ?? true) ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent/20">
                    <span class="text-sm font-medium text-ink">ظاهر للجميع</span>
                </label>
            </div>
        </article>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-save text-xs"></i> حفظ التعديلات
            </button>
            <a href="{{ route('admin.coupons.show', $coupon) }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line bg-surface px-6 text-sm font-medium text-ink transition hover:bg-accent-soft hover:text-accent">عرض التفاصيل</a>
            <a href="{{ route('admin.coupons.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line bg-surface px-6 text-sm font-medium text-muted transition hover:bg-accent-soft hover:text-accent">إلغاء</a>
        </div>
    </form>
</div>
@endsection
