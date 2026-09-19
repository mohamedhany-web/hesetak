@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'باقة جديدة' : 'تعديل باقة').' - حصتك')
@section('page_title', $mode === 'create' ? 'باقة جديدة' : 'تعديل باقة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">{{ $group->title }} · باقات</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">{{ $mode === 'create' ? 'إنشاء باقة' : 'تعديل الباقة' }}</h2>
        </div>
        <a href="{{ route('admin.tutoring-groups.packages.index', $group) }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft">رجوع</a>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'إعداد الباقة',
        'body' => 'الباقة تحدد ماذا يشتري الطالب (مدة + عدد حصص + سعر). بعد الحفظ تظهر في العرض الفردي وتُستخدم في التسكين.',
        'steps' => [
            'حدد الأشهر وعدد الحصص وسعر الساعة أو السعر النهائي.',
            'راجع السعر المقترح تلقائياً وعدّله إن لزم.',
            'فعّل الباقة ليظهر للطالب أو لفريق التسكين.',
        ],
    ])

    <div class="rounded-2xl border border-accent/20 bg-accent-soft/40 px-4 py-3 text-sm text-ink">
        <strong>حساب تلقائي:</strong> السعر الأصلي = سعر الساعة × حصص/شهر × عدد الأشهر.
        السعر المقترح حالياً: <span class="font-bold text-accent tabular-nums">{{ number_format($suggestedPrice, 0) }}</span>
    </div>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.tutoring-groups.packages.store', $group) : route('admin.tutoring-groups.packages.update', [$group, $package]) }}" class="space-y-5">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}" for="name">اسم الباقة</label>
                    <input id="name" name="name" value="{{ old('name', $package->name) }}" class="{{ $fieldClass }}" required>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="duration_months">عدد الأشهر</label>
                    <input type="number" id="duration_months" name="duration_months" value="{{ old('duration_months', $package->duration_months) }}" min="1" max="24" class="{{ $fieldClass }}" required>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="sessions_per_month">حصص شهرياً</label>
                    <input type="number" id="sessions_per_month" name="sessions_per_month" value="{{ old('sessions_per_month', $package->sessions_per_month) }}" min="1" class="{{ $fieldClass }}" required>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="hourly_rate">سعر الساعة</label>
                    <input type="number" step="0.01" id="hourly_rate" name="hourly_rate" value="{{ old('hourly_rate', $package->hourly_rate) }}" min="0" class="{{ $fieldClass }}" required>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="price">السعر النهائي (اختياري — للخصم)</label>
                    <input type="number" step="0.01" id="price" name="price" value="{{ old('price', $package->price) }}" min="0" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="currency">العملة</label>
                    <input id="currency" name="currency" value="{{ old('currency', $package->currency ?: platform_currency()) }}" class="{{ $fieldClass }}" dir="ltr">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="sort_order">الترتيب</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $package->sort_order ?? 0) }}" class="{{ $fieldClass }}">
                </div>
                <div class="flex flex-wrap items-center gap-4 md:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active ?? true))> نشطة</label>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $package->is_featured ?? false))> مميزة</label>
                </div>
            </div>
        </article>

        <button type="submit" class="btn-press inline-flex h-11 items-center rounded-xl bg-accent px-6 text-sm font-medium text-white">حفظ الباقة</button>
    </form>
</div>
@endsection
