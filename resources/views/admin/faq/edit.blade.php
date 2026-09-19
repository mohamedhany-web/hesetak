@extends('layouts.admin')

@section('title', 'تعديل السؤال - حصتك')
@section('page_title', 'تعديل السؤال')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $isActive = (bool) old('is_active', $faq->is_active);
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">محتوى الموقع · الأسئلة الشائعة · #{{ $faq->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل السؤال</h2>
            <p class="mt-1 line-clamp-1 text-sm text-muted">{{ $faq->question }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.faq.show', $faq) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-eye text-xs"></i>
                عرض
            </a>
            <a href="{{ route('admin.faq.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
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
                <i class="fas fa-folder text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الفئة</p>
            <p class="mt-1 text-sm font-semibold text-ink">{{ $faq->category ?: 'غير محدد' }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal">
                <i class="fas fa-sort-numeric-down text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الترتيب</p>
            <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ $faq->order ?? 0 }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $isActive ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                <i class="fas fa-toggle-{{ $isActive ? 'on' : 'off' }} text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الحالة</p>
            <p class="mt-1">
                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $isActive ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                    {{ $isActive ? 'نشط' : 'غير نشط' }}
                </span>
            </p>
        </article>
    </section>

    <form action="{{ route('admin.faq.update', $faq) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">السؤال والإجابة</h3>
                <p class="mt-0.5 text-xs text-muted">المحتوى الظاهر للزوار</p>
            </div>
            <div class="grid gap-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="question">السؤال <span class="text-danger">*</span></label>
                    <input id="question" type="text" name="question" value="{{ old('question', $faq->question) }}" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="answer">الإجابة <span class="text-danger">*</span></label>
                    <textarea id="answer" name="answer" rows="6" required class="{{ $areaClass }}">{{ old('answer', $faq->answer) }}</textarea>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">التصنيف والنشر</h3>
                <p class="mt-0.5 text-xs text-muted">الفئة والترتيب وحالة الظهور</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="category">الفئة</label>
                    <input id="category" type="text" name="category" value="{{ old('category', $faq->category) }}" list="categories" placeholder="أدخل فئة أو اختر من القائمة" class="{{ $fieldClass }}">
                    <datalist id="categories">
                        @foreach($categories as $category)
                            <option value="{{ $category }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="order">الترتيب</label>
                    <input id="order" type="number" name="order" value="{{ old('order', $faq->order ?? 0) }}" min="0" class="{{ $fieldClass }}">
                </div>
                <div class="sm:col-span-2">
                    <input type="hidden" name="is_active" value="0">
                    <label class="inline-flex h-11 w-full cursor-pointer items-center gap-2 rounded-xl border border-line bg-canvas px-4 text-sm font-medium text-ink sm:w-auto">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $faq->is_active ? '1' : '0') === '1') class="size-4 rounded border-line text-accent focus:ring-accent/20">
                        <span>نشط ويظهر في الموقع</span>
                    </label>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-line px-4 py-4 sm:px-5">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                    <i class="fas fa-save text-xs"></i> حفظ التغييرات
                </button>
                <a href="{{ route('admin.faq.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-6 text-sm font-medium text-ink hover:bg-canvas">إلغاء</a>
            </div>
        </article>
    </form>
</div>
@endsection
