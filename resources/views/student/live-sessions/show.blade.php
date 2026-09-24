@extends('layouts.student-timeline')

@section('title', $liveSession->title)

@section('content')
<section class="st-join-hero" aria-label="{{ $liveSession->title }}">
    <div class="st-join-hero__copy">
        <p class="st-join-hero__kicker">Live · Hissatak Meeting</p>
        <h1 class="st-join-hero__title">{{ $liveSession->title }}</h1>
        <p class="st-join-hero__meta">
            @if($liveSession->isLive())
                <span class="inline-flex items-center gap-1.5 text-red-600 font-semibold">
                    <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    بث مباشر الآن
                </span>
            @elseif($liveSession->isScheduled())
                مجدولة — {{ $liveSession->scheduled_at?->diffForHumans() }}
            @else
                منتهية
            @endif
        </p>
    </div>
    <div class="st-join-hero__actions">
        <a href="{{ route('student.live-sessions.index') }}" class="st-pill st-pill--outline st-pill--lg">
            <i class="fas fa-arrow-right"></i> جلسات البث
        </a>
    </div>
</section>

@if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 px-4 py-3 text-sm font-medium mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 text-red-900 px-4 py-3 text-sm font-medium mb-4">{{ session('error') }}</div>
@endif

<div class="st-class-card mb-5" style="min-height:auto;padding:20px 22px;">
    @if($liveSession->isLive())
    <form method="POST" action="{{ route('student.live-sessions.join', $liveSession) }}" class="m-0">
        @csrf
        <button type="submit" class="st-pill st-pill--solid st-pill--lg w-full sm:w-auto justify-center">
            <i class="fas fa-video"></i> انضم إلى البث الآن
        </button>
    </form>
    @endif

    <div class="grid sm:grid-cols-2 gap-4 mt-5">
        <div class="p-4 rounded-xl bg-[var(--st-surface-soft)] border border-[var(--st-border)]">
            <p class="text-xs font-semibold text-[var(--st-muted)] mb-1">المدرب</p>
            <p class="font-bold text-[var(--st-ink-strong)] m-0">{{ $liveSession->instructor?->name ?? '—' }}</p>
        </div>
        <div class="p-4 rounded-xl bg-[var(--st-surface-soft)] border border-[var(--st-border)]">
            <p class="text-xs font-semibold text-[var(--st-muted)] mb-1">الكورس</p>
            <p class="font-bold text-[var(--st-ink-strong)] m-0">{{ $liveSession->course?->title ?? 'جلسة عامة' }}</p>
        </div>
        <div class="p-4 rounded-xl bg-[var(--st-surface-soft)] border border-[var(--st-border)]">
            <p class="text-xs font-semibold text-[var(--st-muted)] mb-1">الموعد المجدول</p>
            <p class="font-bold text-[var(--st-ink-strong)] m-0"><x-app-datetime :at="$liveSession->scheduled_at" pattern="Y/m/d — H:i" /></p>
        </div>
        <div class="p-4 rounded-xl bg-[var(--st-surface-soft)] border border-[var(--st-border)]">
            <p class="text-xs font-semibold text-[var(--st-muted)] mb-1">الحالة</p>
            <p class="font-bold m-0">
                @if($liveSession->isLive())
                    <span class="text-red-600">مباشر</span>
                @elseif($liveSession->isScheduled())
                    <span class="text-sky-600">مجدولة</span>
                @else
                    <span class="text-[var(--st-muted)]">منتهية</span>
                @endif
            </p>
        </div>
    </div>

    @if($liveSession->description)
    <div class="pt-5 mt-5 border-t border-[var(--st-border)]">
        <h2 class="text-sm font-bold text-[var(--st-muted)] uppercase tracking-wide mb-2">وصف الجلسة</h2>
        <div class="text-sm text-[var(--st-ink)] leading-relaxed whitespace-pre-wrap">{{ $liveSession->description }}</div>
    </div>
    @endif
</div>

@if($liveSession->isScheduled())
<div class="rounded-xl border border-sky-200 bg-sky-50 p-5 mb-5">
    <p class="font-bold text-sky-900 m-0">لم يبدأ البث بعد</p>
    <p class="text-sm text-sky-800/90 mt-1 mb-0">الجلسة ستبدأ {{ $liveSession->scheduled_at?->diffForHumans() }}. عند بدء المدرب ستجد زر الانضمام هنا أو من قائمة «مباشر الآن».</p>
</div>
@endif

@if($liveSession->status === 'ended' && $liveSession->recordings && $liveSession->recordings->count() > 0)
<div class="st-class-card mb-5" style="min-height:auto;padding:20px 22px;">
    <h2 class="font-black text-[var(--st-ink-strong)] mb-4 flex items-center gap-2 m-0">
        <i class="fas fa-play-circle text-emerald-600"></i>
        تسجيلات الجلسة
    </h2>
    <ul class="space-y-2 m-0 p-0 list-none">
        @foreach($liveSession->recordings as $rec)
        <li>
            <a href="{{ route('student.live-recordings.show', $rec) }}" class="flex items-center justify-between gap-3 p-4 rounded-xl bg-[var(--st-surface-soft)] border border-[var(--st-border)] hover:border-emerald-300 transition-colors no-underline text-inherit">
                <span class="font-semibold text-[var(--st-ink-strong)]">{{ $rec->title ?? 'تسجيل #' . $rec->id }}</span>
                <span class="text-xs text-[var(--st-muted)] shrink-0">{{ $rec->duration_for_humans }}</span>
            </a>
        </li>
        @endforeach
    </ul>
    <a href="{{ route('student.live-recordings.index') }}" class="inline-flex items-center gap-2 mt-4 text-sm font-semibold text-emerald-600 hover:text-emerald-700 no-underline">
        كل التسجيلات
        <i class="fas fa-chevron-left text-xs"></i>
    </a>
</div>
@endif
@endsection
