@extends('layouts.admin')

@section('title', 'مواد المدرسة - حصتك')
@section('page_title', 'مواد المدرسة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $kpis = [
        ['label' => 'إجمالي المواد', 'value' => $summary['total'], 'icon' => 'fa-book', 'tone' => 'accent', 'note' => 'حسب التصفية الحالية'],
        ['label' => 'مواد نشطة', 'value' => $summary['active'], 'icon' => 'fa-check-circle', 'tone' => 'accent', 'note' => 'جاهزة لربط الكورسات'],
        ['label' => 'كورسات مربوطة', 'value' => $summary['courses'], 'icon' => 'fa-graduation-cap', 'tone' => 'metal', 'note' => 'داخل المواد المعروضة'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">إدارة المحتوى · المدرسة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">
                @if($currentTrack)
                    مواد {{ $currentTrack->name }}
                @else
                    مواد المدرسة
                @endif
            </h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">
                المادة طبقة تنظيمية داخل سنة المدرسة (أو عامة)، وتحتوي الكورسات والفصول المرتبطة بها.
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            @if($currentTrack)
                <a href="{{ route('admin.academic-years.edit', $currentTrack) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                    <i class="fas fa-arrow-right text-xs"></i>
                    رجوع للسنة
                </a>
            @endif
            <a href="{{ route('admin.academic-subjects.create', $currentTrack ? ['track' => $currentTrack->id] : []) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                مادة جديدة
            </a>
        </div>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'دور مواد المدرسة',
        'body' => 'المادة تقسّم محتوى السنة (مثل: لغة عربية، رياضيات). اربط الكورسات وفصول المدرسة بالمادة المناسبة ليسهل على الطالب والفريق التصفح.',
        'steps' => [
            'اختر السنة (أو ابدأ من صفحة السنوات).',
            'أنشئ المادة وفعّل حالتها.',
            'عند إنشاء كورس أو مجموعة جماعية اربطها بهذه المادة.',
        ],
    ])

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm font-medium text-danger shadow-soft" role="alert">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-danger/10 text-danger"><i class="fas fa-exclamation text-sm"></i></span>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">تصفية السنة</h3>
            <p class="mt-0.5 text-xs text-muted">اعرض مواد سنة محددة أو كل السنوات</p>
        </div>
        <form method="GET" action="{{ route('admin.academic-subjects.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3 sm:items-end sm:p-5">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-medium text-muted" for="track">سنة المدرسة</label>
                <select id="track" name="track" class="{{ $fieldClass }}" onchange="this.form.submit()">
                    <option value="">كل السنوات</option>
                    @foreach($tracks as $track)
                        <option value="{{ $track->id }}" @selected((string) request('track') === (string) $track->id)>{{ $track->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(request()->filled('track'))
                    <a href="{{ route('admin.academic-subjects.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-times text-xs"></i>
                        مسح
                    </a>
                @endif
            </div>
        </form>
    </article>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-3">
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

    @if($subjects->isEmpty())
        <article class="rounded-2xl border border-line bg-surface px-6 py-14 text-center shadow-soft">
            <span class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                <i class="fas fa-book text-xl"></i>
            </span>
            <h3 class="mt-4 text-lg font-semibold text-ink">لا توجد مواد بعد</h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted">أنشئ مادة داخل السنة ثم اربط الكورسات من صفحة المادة.</p>
            <a href="{{ route('admin.academic-subjects.create', $currentTrack ? ['track' => $currentTrack->id] : []) }}"
               class="btn-press mt-5 inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                إنشاء مادة
            </a>
        </article>
    @else
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                <div>
                    <h3 class="text-base font-semibold text-ink">قائمة المواد</h3>
                    <p class="mt-0.5 text-xs text-muted">{{ $subjects->count() }} مادة</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">المادة</th>
                            <th class="px-4 py-3 text-start font-medium">السنة</th>
                            <th class="px-4 py-3 text-start font-medium">الكورسات</th>
                            <th class="px-4 py-3 text-start font-medium">الحالة</th>
                            <th class="px-4 py-3 text-start font-medium">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($subjects as $subject)
                            <tr class="hover:bg-canvas/70">
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl text-white text-xs" style="background: {{ $subject->color ?: '#0B3D91' }}">
                                            <i class="{{ $subject->icon ?: 'fas fa-book' }}"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-ink truncate">{{ $subject->name }}</p>
                                            <p class="font-mono text-[11px] text-muted">{{ $subject->code }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-ink-soft">{{ $subject->academicYear?->name ?? '—' }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full border border-line bg-canvas px-2.5 py-1 text-[11px] font-bold tabular-nums text-ink-soft">
                                        {{ $subject->courses_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $subject->is_active ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                                        {{ $subject->is_active ? 'نشطة' : 'موقوفة' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('admin.academic-subjects.show', $subject) }}" class="btn-press inline-flex h-8 items-center rounded-lg bg-accent px-3 text-[11px] font-medium text-white">عرض</a>
                                        <a href="{{ route('admin.academic-subjects.edit', $subject) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-[11px] font-medium text-ink hover:bg-canvas">تعديل</a>
                                        <form method="POST" action="{{ route('admin.academic-subjects.toggle-status', $subject) }}">
                                            @csrf
                                            <button type="submit" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-[11px] font-medium text-ink-soft hover:bg-canvas">
                                                {{ $subject->is_active ? 'إيقاف' : 'تفعيل' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif
</div>
@endsection
