@extends('layouts.admin')

@section('title', 'جلسات البث المباشر - حصتك')
@section('page_title', 'جلسات البث المباشر')

@section('content')
@php
    $kpis = [
        ['label' => 'إجمالي الجلسات', 'value' => $stats['total'], 'icon' => 'fa-list', 'tone' => 'muted', 'note' => 'كل الجلسات المسجّلة'],
        ['label' => 'مباشر الآن', 'value' => $stats['live'], 'icon' => 'fa-broadcast-tower', 'tone' => 'accent', 'note' => 'غرف قيد التشغيل'],
        ['label' => 'مجدولة', 'value' => $stats['scheduled'], 'icon' => 'fa-clock', 'tone' => 'metal', 'note' => 'بانتظار البدء'],
        ['label' => 'منتهية', 'value' => $stats['ended'], 'icon' => 'fa-check-circle', 'tone' => 'accent', 'note' => 'اكتملت'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">البث المباشر · LiveKit</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">جلسات البث المباشر</h2>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.live-sessions.instant') }}" class="inline">
                @csrf
                <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-video text-xs"></i>
                    ابدأ بثاً الآن
                </button>
            </form>
            <a href="{{ route('admin.live-sessions.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-calendar-plus text-xs"></i>
                جدولة جلسة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-danger/30 bg-surface px-4 py-3 text-sm font-medium text-danger shadow-soft" role="alert">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-danger/10 text-danger"><i class="fas fa-exclamation text-sm"></i></span>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($kpi['value']) }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">ابحث بالعنوان أو اسم الغرفة، أو صفِّ حسب الحالة والمضيف والكورس</p>
        </div>
        <form method="GET" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-5 md:items-end">
            <div class="xl:col-span-2">
                <label class="{{ $labelClass }}" for="search">بحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="اسم الجلسة أو الغرفة..." class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">الكل</option>
                    <option value="scheduled" @selected(request('status') === 'scheduled')>مجدولة</option>
                    <option value="live" @selected(request('status') === 'live')>مباشر</option>
                    <option value="ended" @selected(request('status') === 'ended')>منتهية</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>ملغاة</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="course_id">الكورس</label>
                <select id="course_id" name="course_id" class="{{ $fieldClass }}">
                    <option value="">الكل</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ Str::limit($course->title, 30) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="instructor_id">المضيف</label>
                <select id="instructor_id" name="instructor_id" class="{{ $fieldClass }}">
                    <option value="">الكل</option>
                    @foreach($instructors as $inst)
                        <option value="{{ $inst->id }}" @selected((string) request('instructor_id') === (string) $inst->id)>{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-2 xl:col-span-5">
                <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-search text-xs"></i> بحث
                </button>
                @if(request()->hasAny(['search', 'status', 'course_id', 'instructor_id']))
                    <a href="{{ route('admin.live-sessions.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm font-medium text-ink-soft hover:bg-canvas">مسح</a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-sm">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-start text-xs font-medium text-muted">
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">الجلسة</th>
                        <th class="px-4 py-3 font-medium">المضيف</th>
                        <th class="px-4 py-3 font-medium">الكورس</th>
                        <th class="px-4 py-3 text-center font-medium">الحالة</th>
                        <th class="px-4 py-3 text-center font-medium">الحضور</th>
                        <th class="px-4 py-3 font-medium">الموعد</th>
                        <th class="px-4 py-3 text-center font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-canvas/40 transition-colors">
                            <td class="px-4 py-3 tabular-nums text-muted">{{ $session->id }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.live-sessions.show', $session) }}" class="font-semibold text-ink hover:text-accent">{{ Str::limit($session->title, 40) }}</a>
                                <p class="mt-0.5 font-mono text-[11px] text-muted">{{ $session->room_name }}</p>
                            </td>
                            <td class="px-4 py-3 text-ink-soft">{{ $session->instructor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ Str::limit($session->course?->title ?? 'عامة', 25) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($session->status === 'live')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-accent-soft px-2.5 py-1 text-xs font-bold text-accent">
                                        <span class="size-1.5 rounded-full bg-accent animate-pulse"></span> مباشر
                                    </span>
                                @elseif($session->status === 'scheduled')
                                    <span class="inline-flex rounded-full bg-metal/15 px-2.5 py-1 text-xs font-medium text-metal">مجدولة</span>
                                @elseif($session->status === 'ended')
                                    <span class="inline-flex rounded-full bg-canvas-muted px-2.5 py-1 text-xs font-medium text-muted">منتهية</span>
                                @else
                                    <span class="inline-flex rounded-full bg-danger/10 px-2.5 py-1 text-xs font-medium text-danger">ملغاة</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center tabular-nums text-ink-soft">{{ $session->attendance_count }}</td>
                            <td class="px-4 py-3 text-xs text-muted">{{ $session->scheduled_at?->format('Y/m/d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    @if($session->status === 'live')
                                        <a href="{{ route('admin.live-sessions.room', $session) }}" class="btn-press inline-flex h-8 items-center gap-1 rounded-lg bg-accent px-2.5 text-xs font-semibold text-white" title="دخول الغرفة">
                                            <i class="fas fa-door-open"></i> دخول
                                        </a>
                                        <form method="POST" action="{{ route('admin.live-sessions.force-end', $session) }}" class="inline" onsubmit="return confirm('إنهاء البث؟')">
                                            @csrf
                                            <button type="submit" class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-danger/10 hover:text-danger" title="إنهاء"><i class="fas fa-stop text-xs"></i></button>
                                        </form>
                                    @elseif($session->status === 'scheduled')
                                        <form method="POST" action="{{ route('admin.live-sessions.start', $session) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-press inline-flex h-8 items-center gap-1 rounded-lg bg-accent px-2.5 text-xs font-semibold text-white" title="بدء والدخول">
                                                <i class="fas fa-play"></i> بدء
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.live-sessions.edit', $session) }}" class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-canvas hover:text-accent" title="تعديل"><i class="fas fa-edit text-xs"></i></a>
                                    @endif
                                    <a href="{{ route('admin.live-sessions.show', $session) }}" class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-canvas hover:text-accent" title="عرض"><i class="fas fa-eye text-xs"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-14 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-broadcast-tower"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا توجد جلسات بث بعد</p>
                                <p class="mt-1 text-xs text-muted">ابدأ بثاً فورياً الآن أو جدول جلسة لاحقاً</p>
                                <form method="POST" action="{{ route('admin.live-sessions.instant') }}" class="mt-4 inline">
                                    @csrf
                                    <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                                        <i class="fas fa-video text-xs"></i> ابدأ بثاً الآن
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $sessions->links() }}</div>
        @endif
    </article>
</div>
@endsection
