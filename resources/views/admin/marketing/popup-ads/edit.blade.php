@extends('layouts.admin')

@section('title', 'تعديل الإعلان المنبثق')
@section('page_title', 'تعديل الإعلان المنبثق')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · الصفحة الرئيسية</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل الإعلان</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">{{ $popupAd->title }}</p>
        </div>
        <a href="{{ route('admin.popup-ads.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للقائمة
        </a>
    </section>

    <form action="{{ route('admin.popup-ads.update', $popupAd) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-bullhorn text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">محتوى الإعلان</h3>
                        <p class="mt-0.5 text-xs text-muted">العنوان، النص، وزر الدعوة</p>
                    </div>
                </div>
            </div>

            <div class="space-y-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">عنوان الإعلان <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $popupAd->title) }}" required
                           class="{{ $fieldClass }}">
                    @error('title')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">نص الإعلان <span class="text-danger">*</span></label>
                    <textarea name="body" rows="5" required class="{{ $areaClass }}">{{ old('body', $popupAd->body) }}</textarea>
                    @error('body')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">نص زر الدعوة (اختياري)</label>
                        <input type="text" name="cta_text" value="{{ old('cta_text', $popupAd->cta_text) }}" placeholder="مثال: ابدأ الآن"
                               class="{{ $fieldClass }}">
                        @error('cta_text')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">رابط الزر (اختياري)</label>
                        <input type="url" name="link_url" value="{{ old('link_url', $popupAd->link_url) }}" placeholder="https://..."
                               class="{{ $fieldClass }}" dir="ltr">
                        @error('link_url')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-clock text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">جدولة العرض</h3>
                        <p class="mt-0.5 text-xs text-muted">الفترة وعدد مرات الظهور</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">تاريخ بدء العرض <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $popupAd->starts_at->format('Y-m-d\TH:i')) }}" required
                           class="{{ $fieldClass }}" dir="ltr">
                    @error('starts_at')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">مدة العرض (أيام) <span class="text-danger">*</span></label>
                    <input type="number" name="duration_days" value="{{ old('duration_days', max(1, $popupAd->starts_at->diffInDays($popupAd->ends_at))) }}" min="1" max="365" required
                           class="{{ $fieldClass }}" dir="ltr">
                    @error('duration_days')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">عدد مرات ظهور الإعلان لكل زائر <span class="text-danger">*</span></label>
                    <input type="number" name="max_views_per_visitor" value="{{ old('max_views_per_visitor', $popupAd->max_views_per_visitor) }}" min="1" max="100" required
                           class="{{ $fieldClass }}" dir="ltr">
                    @error('max_views_per_visitor')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">الصفحة المستهدفة</label>
                    <select name="page_key" class="{{ $fieldClass }}">
                        @php $pk = old('page_key', $popupAd->page_key ?? 'home'); @endphp
                        <option value="home" @selected($pk === 'home')>الرئيسية</option>
                        <option value="pricing" @selected($pk === 'pricing')>الأسعار /pricing</option>
                        <option value="courses" @selected($pk === 'courses')>الكورسات /courses</option>
                        <option value="package_teachers" @selected($pk === 'package_teachers')>صفحة معلمي الباقة</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">تأخير الظهور (ثوانٍ)</label>
                    <input type="number" name="delay_seconds" value="{{ old('delay_seconds', $popupAd->delay_seconds ?? 30) }}" min="0" max="600"
                           class="{{ $fieldClass }}" dir="ltr">
                </div>
                <div class="flex items-center gap-2 sm:pt-6">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $popupAd->is_active) ? 'checked' : '' }}
                           class="size-4 rounded border-line text-accent focus:ring-accent/20">
                    <label for="is_active" class="text-sm font-medium text-ink">تفعيل الإعلان</label>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line px-4 py-4 sm:px-5">
                <a href="{{ route('admin.popup-ads.index') }}"
                   class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                    إلغاء
                </a>
                <button type="submit"
                        class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-save text-xs"></i>
                    حفظ التعديلات
                </button>
            </div>
        </article>
    </form>
</div>
@endsection
