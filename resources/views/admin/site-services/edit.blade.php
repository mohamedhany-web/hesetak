@extends('layouts.admin')

@section('title', 'تعديل خدمة - حصتك')
@section('page_title', 'تعديل خدمة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $isActive = (bool) old('is_active', $siteService->is_active);
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">محتوى الموقع · خدمات الموقع · #{{ $siteService->id }}</p>
            <h2 class="mt-1 truncate text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل: {{ $siteService->name }}</h2>
            <p class="mt-1 text-sm text-muted">
                معاينة:
                <a href="{{ route('public.services.show', $siteService) }}" target="_blank" rel="noopener" class="text-accent hover:underline" dir="ltr">/services/{{ $siteService->slug }}</a>
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('public.services.show', $siteService) }}" target="_blank" rel="noopener" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-eye text-xs"></i>
                معاينة
            </a>
            <a href="{{ route('admin.site-services.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
        </div>
    </section>

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

    <section class="grid gap-3 sm:grid-cols-3">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                <i class="fas fa-sort-numeric-down text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الترتيب</p>
            <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ $siteService->sort_order }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $isActive ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                <i class="fas fa-toggle-{{ $isActive ? 'on' : 'off' }} text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الحالة الحالية</p>
            <p class="mt-1">
                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $isActive ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                    {{ $isActive ? 'نشط' : 'معطل' }}
                </span>
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal">
                <i class="fas fa-calendar-day text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">آخر تحديث</p>
            <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ $siteService->updated_at?->format('Y-m-d H:i') }}</p>
        </article>
    </section>

    <form action="{{ route('admin.site-services.update', $siteService) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات الخدمة</h3>
                <p class="mt-0.5 text-xs text-muted">الاسم والرابط والصورة الظاهرة للزوار</p>
            </div>
            <div class="grid gap-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="name">اسم الخدمة <span class="text-danger">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name', $siteService->name) }}" required maxlength="255" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="slug">الرابط في المتصفح <span class="font-normal text-muted">(اختياري)</span></label>
                    <input id="slug" type="text" name="slug" value="{{ old('slug', $siteService->slug) }}" dir="ltr" class="{{ $fieldClass }} font-mono">
                    <p class="mt-1.5 text-xs text-muted">اتركه فارغاً لإعادة توليد الرابط من الاسم.</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="image">صورة الخدمة</label>
                    @if($siteService->publicImageUrl())
                        <div class="mb-3 h-28 w-40 overflow-hidden rounded-xl border border-line bg-canvas">
                            <img src="{{ $siteService->publicImageUrl() }}" alt="" class="size-full object-cover">
                        </div>
                    @endif
                    <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
                           class="block w-full text-sm text-muted file:ml-4 file:rounded-xl file:border-0 file:bg-accent-soft file:px-4 file:py-2 file:text-sm file:font-medium file:text-accent hover:file:bg-accent hover:file:text-white">
                    <p class="mt-1.5 text-xs text-muted">اترك الحقل فارغاً للإبقاء على الصورة الحالية.</p>
                    @if($siteService->image_path)
                        <input type="hidden" name="remove_image" value="0">
                        <label class="mt-3 inline-flex cursor-pointer items-center gap-2 text-sm text-ink">
                            <input type="checkbox" name="remove_image" value="1" class="size-4 rounded border-line text-danger focus:ring-danger/20">
                            <span>حذف الصورة الحالية</span>
                        </label>
                    @endif
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">المحتوى</h3>
                <p class="mt-0.5 text-xs text-muted">المقدمة القصيرة وتفاصيل صفحة الخدمة</p>
            </div>
            <div class="grid gap-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="summary">مقدمة قصيرة</label>
                    <textarea id="summary" name="summary" rows="3" maxlength="2000" class="{{ $areaClass }}">{{ old('summary', $siteService->summary) }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="body">تفاصيل الخدمة <span class="text-danger">*</span></label>
                    <textarea id="body" name="body" rows="12" required class="{{ $areaClass }}">{{ old('body', $siteService->body) }}</textarea>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">العرض والنشر</h3>
                <p class="mt-0.5 text-xs text-muted">ترتيب الظهور وحالة التفعيل</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="sort_order">ترتيب العرض</label>
                    <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $siteService->sort_order) }}" min="0" class="{{ $fieldClass }}">
                </div>
                <div class="flex items-end">
                    <input type="hidden" name="is_active" value="0">
                    <label class="inline-flex h-11 w-full cursor-pointer items-center gap-2 rounded-xl border border-line bg-canvas px-4">
                        <input type="checkbox" name="is_active" value="1" @checked((string) old('is_active', $siteService->is_active ? '1' : '0') === '1') class="size-4 rounded border-line text-accent focus:ring-accent/20">
                        <span class="text-sm font-medium text-ink">نشط ويظهر في الموقع</span>
                    </label>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-line px-4 py-4 sm:px-5">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                    <i class="fas fa-save text-xs"></i> حفظ التعديلات
                </button>
                <a href="{{ route('admin.site-services.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-6 text-sm font-medium text-ink hover:bg-canvas">إلغاء</a>
            </div>
        </article>
    </form>
</div>
@endsection
