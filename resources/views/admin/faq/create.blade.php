@extends('layouts.admin')

@section('title', 'إضافة سؤال - حصتك')
@section('page_title', 'إضافة سؤال')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">محتوى الموقع · الأسئلة الشائعة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إضافة سؤال</h2>
            <p class="mt-1 text-sm text-muted">سؤال جديد يظهر للزوار في صفحة الأسئلة الشائعة</p>
        </div>
        <a href="{{ route('admin.faq.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
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

    <form action="{{ route('admin.faq.store') }}" method="POST" class="space-y-5">
        @csrf

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">السؤال والإجابة</h3>
                <p class="mt-0.5 text-xs text-muted">المحتوى الظاهر للزوار</p>
            </div>
            <div class="grid gap-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="question">السؤال <span class="text-danger">*</span></label>
                    <input id="question" type="text" name="question" value="{{ old('question') }}" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="answer">الإجابة <span class="text-danger">*</span></label>
                    <textarea id="answer" name="answer" rows="6" required class="{{ $areaClass }}">{{ old('answer') }}</textarea>
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
                    <input id="category" type="text" name="category" value="{{ old('category') }}" list="categories" placeholder="أدخل فئة أو اختر من القائمة" class="{{ $fieldClass }}">
                    <datalist id="categories">
                        @foreach($categories as $category)
                            <option value="{{ $category }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="order">الترتيب</label>
                    <input id="order" type="number" name="order" value="{{ old('order', 0) }}" min="0" class="{{ $fieldClass }}">
                </div>
                <div class="sm:col-span-2">
                    <input type="hidden" name="is_active" value="0">
                    <label class="inline-flex h-11 w-full cursor-pointer items-center gap-2 rounded-xl border border-line bg-canvas px-4 text-sm font-medium text-ink sm:w-auto">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') !== '0') class="size-4 rounded border-line text-accent focus:ring-accent/20">
                        <span>نشط ويظهر في الموقع</span>
                    </label>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-line px-4 py-4 sm:px-5">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                    <i class="fas fa-save text-xs"></i> حفظ السؤال
                </button>
                <a href="{{ route('admin.faq.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-6 text-sm font-medium text-ink hover:bg-canvas">إلغاء</a>
            </div>
        </article>
    </form>
</div>
@endsection
