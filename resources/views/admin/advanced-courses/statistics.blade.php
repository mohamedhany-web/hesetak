@extends('layouts.admin')

@section('title', 'إحصائيات البرنامج - ' . config('app.name'))
@section('page_title', 'إحصائيات البرنامج')

@section('content')
@php
    $advancedCourse->loadMissing(['enrollments', 'lessons', 'orders']);
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.advanced-courses.index') }}" class="hover:text-accent">{{ __('admin.courses_management') }}</a>
                <span class="mx-1">·</span>
                <a href="{{ route('admin.advanced-courses.show', $advancedCourse) }}" class="hover:text-accent">{{ Str::limit($advancedCourse->title, 36) }}</a>
            </p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إحصائيات البرنامج</h2>
            <p class="mt-1 text-sm text-muted">{{ $advancedCourse->title }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.advanced-courses.students', $advancedCourse) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm text-ink-soft hover:bg-accent-soft hover:text-accent">الطلاب</a>
            <a href="{{ route('admin.advanced-courses.show', $advancedCourse) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm text-ink-soft hover:bg-accent-soft hover:text-accent">العودة</a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">طلاب (إجمالي)</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ number_format($stats['students']['total'] ?? 0) }}</p>
            <p class="mt-1 text-[11px] text-muted">نشط {{ $stats['students']['active'] ?? 0 }} · مكتمل {{ $stats['students']['completed'] ?? 0 }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الدروس</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ number_format($stats['lessons']['total'] ?? 0) }}</p>
            <p class="mt-1 text-[11px] text-muted">مدة {{ number_format($stats['lessons']['total_duration'] ?? 0) }} د</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الطلبات</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ number_format($stats['orders']['total'] ?? 0) }}</p>
            <p class="mt-1 text-[11px] text-muted">مقبول {{ $stats['orders']['approved'] ?? 0 }} · معلّق {{ $stats['orders']['pending'] ?? 0 }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">متوسط التقدّم</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-accent">{{ number_format((float) ($stats['progress']['average'] ?? 0), 1) }}%</p>
            <p class="mt-1 text-[11px] text-muted">معدل الإكمال {{ number_format((float) ($stats['progress']['completion_rate'] ?? 0), 1) }}%</p>
        </article>
    </section>
</div>
@endsection
