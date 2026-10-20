@extends('layouts.admin')

@section('title', __('admin.academic_years') . ' - حصتك')
@section('page_title', __('admin.academic_years'))

@section('content')
@php
    $kpis = [
        ['label' => 'إجمالي المراحل', 'value' => $summary['total_stages'], 'icon' => 'fa-layer-group', 'tone' => 'accent', 'note' => 'كل المراحل المسجّلة'],
        ['label' => 'مراحل نشطة', 'value' => $summary['active_stages'], 'icon' => 'fa-check-circle', 'tone' => 'accent', 'note' => 'ظاهرة في التسجيل والمطابقة'],
        ['label' => 'منشورة للعامة', 'value' => $summary['public_stages'], 'icon' => 'fa-globe', 'tone' => 'metal', 'note' => 'تظهر في صفحة المناهج'],
        ['label' => 'الصفوف', 'value' => $summary['grades'], 'icon' => 'fa-chalkboard', 'tone' => 'muted', 'note' => 'صفوف داخل المراحل'],
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
            <p class="text-xs font-medium text-muted">مسار المناهج · مطابقة الطالب</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ __('admin.academic_years') }}</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">
                المرحلة (ابتدائي / متوسط / ثانوي) تجمع الصفوف. الطالب يختار المرحلة ثم الصف عند إنشاء الحساب.
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.academic-years.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                مرحلة جديدة
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
        <div class="flex items-center gap-3 rounded-2xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm font-medium text-danger shadow-soft" role="alert">
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

    @if($years->count() > 0)
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            @foreach($years as $year)
                @php
                    $iconClass = $year->icon ?: 'fas fa-graduation-cap';
                    if (! str_contains($iconClass, 'fa-')) {
                        $iconClass = 'fas ' . ltrim($iconClass, ' ');
                    }
                    $color = $year->color ?: '#0B3D91';
                    $grades = $year->grades ?? collect();
                @endphp
                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft transition hover:border-accent/30">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line px-4 py-4 sm:px-5">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-xl text-white shadow-soft" style="background: {{ $color }}">
                                <i class="{{ $iconClass }} text-lg"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-base font-semibold text-ink">{{ $year->name }}</h3>
                                    @if($year->is_active)
                                        <span class="inline-flex items-center rounded-full bg-success/10 px-2 py-0.5 text-[11px] font-medium text-success">نشطة</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-danger/10 px-2 py-0.5 text-[11px] font-medium text-danger">موقوفة</span>
                                    @endif
                                    @if($year->is_public)
                                        <span class="inline-flex items-center rounded-full bg-accent-soft px-2 py-0.5 text-[11px] font-medium text-accent">عامة</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 font-mono text-xs uppercase tracking-wide text-muted">{{ $year->code }} · ترتيب {{ $year->order }}</p>
                                <p class="mt-2 text-sm text-muted line-clamp-2">
                                    {{ $year->description ? \Illuminate\Support\Str::limit($year->description, 140) : 'لا يوجد وصف — أضفه من التعديل.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div>
                            <p class="mb-2 text-xs font-medium text-muted">الصفوف ({{ $grades->count() }})</p>
                            @if($grades->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach($grades as $grade)
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-canvas px-2.5 py-1 text-[11px] font-medium {{ $grade->is_active ? 'text-ink-soft' : 'text-muted line-through' }}">
                                            {{ $grade->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-muted">لا صفوف بعد — أضفها من التعديل لتظهر في التسجيل.</p>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2 border-t border-line pt-4">
                            <a href="{{ route('admin.academic-years.show', $year->id) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-3.5 text-xs font-medium text-ink hover:bg-canvas">
                                <i class="fas fa-eye text-[10px]"></i>
                                عرض
                            </a>
                            <a href="{{ route('admin.academic-years.edit', $year->id) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-3.5 text-xs font-medium text-white">
                                <i class="fas fa-pen text-[10px]"></i>
                                تعديل
                            </a>
                            <form method="POST" action="{{ route('admin.academic-years.toggle-status', $year->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-3.5 text-xs font-medium text-ink-soft hover:bg-canvas">
                                    <i class="fas fa-power-off text-[10px]"></i>
                                    {{ $year->is_active ? 'إيقاف' : 'تفعيل' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.academic-years.destroy', $year->id) }}" class="inline" onsubmit="return confirm('حذف مرحلة «{{ $year->name }}» وصفوفها؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-danger/20 bg-danger/5 px-3.5 text-xs font-medium text-danger hover:bg-danger/10">
                                    <i class="fas fa-trash text-[10px]"></i>
                                    حذف
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <article class="rounded-2xl border border-line bg-surface px-6 py-14 text-center shadow-soft">
            <span class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                <i class="fas fa-layer-group text-xl"></i>
            </span>
            <h3 class="mt-4 text-lg font-semibold text-ink">لا توجد مراحل دراسية بعد</h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted">
                أنشئ مرحلة مثل الابتدائي أو الثانوي، ثم أضف صفوفها.
            </p>
            <a href="{{ route('admin.academic-years.create') }}" class="btn-press mt-5 inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                إنشاء مرحلة دراسية
            </a>
        </article>
    @endif
</div>
@endsection
