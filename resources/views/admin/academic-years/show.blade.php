@extends('layouts.admin')

@section('title', $academicYear->name . ' - حصتك')
@section('page_title', $academicYear->name)

@section('content')
@php
    $iconClass = $academicYear->icon ?: 'fas fa-graduation-cap';
    if (! str_contains($iconClass, 'fa-')) {
        $iconClass = 'fas ' . ltrim($iconClass, ' ');
    }
    $color = $academicYear->color ?: '#0B3D91';
    $grades = $academicYear->grades ?? collect();
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">إدارة المحتوى · المراحل · عرض</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $academicYear->name }}</h2>
            <p class="mt-1 text-sm text-muted">{{ $academicYear->tagline ?: 'مرحلة دراسية وصفوفها المعروضة في التسجيل.' }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.academic-years.edit', $academicYear->id) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-pen text-xs"></i>
                تعديل
            </a>
            <a href="{{ route('admin.academic-years.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
        </div>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl text-white" style="background: {{ $color }}">
                <i class="{{ $iconClass }} text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الرمز</p>
            <p class="mt-1 font-mono text-lg font-semibold text-ink">{{ $academicYear->code }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="mt-1 text-xs text-muted">الصفوف</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $grades->count() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="mt-1 text-xs text-muted">الحالة</p>
            <p class="mt-1 text-lg font-semibold {{ $academicYear->is_active ? 'text-success' : 'text-danger' }}">{{ $academicYear->is_active ? 'نشطة' : 'موقوفة' }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="mt-1 text-xs text-muted">الظهور العام</p>
            <p class="mt-1 text-lg font-semibold text-ink">{{ $academicYear->is_public ? 'منشورة' : 'داخلية' }}</p>
        </article>
    </section>

    @if($academicYear->description)
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft sm:p-5">
            <h3 class="text-base font-semibold text-ink">الوصف</h3>
            <p class="mt-2 text-sm leading-relaxed text-muted">{{ $academicYear->description }}</p>
        </article>
    @endif

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">الصفوف</h3>
            <p class="mt-0.5 text-xs text-muted">تظهر للطالب بعد اختيار هذه المرحلة في إنشاء الحساب.</p>
        </div>
        <div class="p-4 sm:p-5">
            @if($grades->isEmpty())
                <p class="text-sm text-muted">لا صفوف في هذه المرحلة بعد.</p>
            @else
                <ol class="space-y-2">
                    @foreach($grades as $grade)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-line bg-canvas px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-ink">{{ $grade->name }}</p>
                                <p class="mt-0.5 text-xs text-muted">ترتيب {{ $grade->order }}</p>
                            </div>
                            @if($grade->is_active)
                                <span class="inline-flex items-center rounded-full bg-success/10 px-2 py-0.5 text-[11px] font-medium text-success">ظاهر</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-canvas-muted px-2 py-0.5 text-[11px] font-medium text-muted">مخفي</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </article>
</div>
@endsection
