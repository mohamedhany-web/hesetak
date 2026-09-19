@extends('layouts.admin')

@section('title', 'إضافة خدمة - حصتك')
@section('page_title', 'إضافة خدمة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">محتوى الموقع · خدمات الموقع</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">خدمة جديدة</h2>
            <p class="mt-1 text-sm text-muted">يُنشأ الرابط تلقائياً من الاسم إن تُرك فارغاً</p>
        </div>
        <a href="{{ route('admin.site-services.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للقائمة
        </a>
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

    <form action="{{ route('admin.site-services.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات الخدمة</h3>
                <p class="mt-0.5 text-xs text-muted">الاسم والرابط والصورة الظاهرة للزوار</p>
            </div>
            <div class="grid gap-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="name">اسم الخدمة <span class="text-danger">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="slug">الرابط في المتصفح <span class="font-normal text-muted">(اختياري)</span></label>
                    <input id="slug" type="text" name="slug" value="{{ old('slug') }}" dir="ltr" placeholder="مثال: teacher-training" class="{{ $fieldClass }} font-mono">
                    <p class="mt-1.5 text-xs text-muted">فقط a-z و 0-9 وشرطة. يُترك فارغاً للإنشاء التلقائي.</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="image">صورة الخدمة <span class="font-normal text-muted">(اختياري)</span></label>
                    <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
                           class="block w-full text-sm text-muted file:ml-4 file:rounded-xl file:border-0 file:bg-accent-soft file:px-4 file:py-2 file:text-sm file:font-medium file:text-accent hover:file:bg-accent hover:file:text-white">
                    <p class="mt-1.5 text-xs text-muted">صورة للبطاقة وصفحة الخدمة.</p>
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
                    <label class="{{ $labelClass }}" for="summary">مقدمة قصيرة <span class="font-normal text-muted">(بطاقة القائمة)</span></label>
                    <textarea id="summary" name="summary" rows="3" maxlength="2000" class="{{ $areaClass }}">{{ old('summary') }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="body">تفاصيل الخدمة <span class="text-danger">*</span></label>
                    <textarea id="body" name="body" rows="12" required class="{{ $areaClass }}">{{ old('body') }}</textarea>
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
                    <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="{{ $fieldClass }}">
                </div>
                <div class="flex items-end">
                    <input type="hidden" name="is_active" value="0">
                    <label class="inline-flex h-11 w-full cursor-pointer items-center gap-2 rounded-xl border border-line bg-canvas px-4">
                        <input type="checkbox" name="is_active" value="1" @checked((string) old('is_active', '1') !== '0') class="size-4 rounded border-line text-accent focus:ring-accent/20">
                        <span class="text-sm font-medium text-ink">نشط ويظهر في الموقع</span>
                    </label>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-line px-4 py-4 sm:px-5">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                    <i class="fas fa-save text-xs"></i> حفظ الخدمة
                </button>
                <a href="{{ route('admin.site-services.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-6 text-sm font-medium text-ink hover:bg-canvas">إلغاء</a>
            </div>
        </article>
    </form>
</div>
@endsection
