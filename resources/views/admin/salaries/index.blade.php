@extends('layouts.admin')

@section('title', 'الماليات الخاصة بالمدربين')
@section('page_title', 'الماليات الخاصة بالمدربين')
@section('header', 'الماليات الخاصة بالمدربين')

@section('content')
@php
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $kpis = [
        ['label' => 'إجمالي مطلوب الدفع', 'value' => number_format($globalStats['pending_total'], 2), 'suffix' => currency_symbol(), 'icon' => 'fa-hourglass-half', 'tone' => 'metal', 'note' => ($globalStats['pending_count'] ?? 0) . ' مدفوعة'],
        ['label' => 'إجمالي تم الدفع', 'value' => number_format($globalStats['paid_total'], 2), 'suffix' => currency_symbol(), 'icon' => 'fa-check-circle', 'tone' => 'accent', 'note' => ($globalStats['paid_count'] ?? 0) . ' مدفوعة'],
        ['label' => 'عدد المدربين', 'value' => number_format($instructors->count()), 'suffix' => '', 'icon' => 'fa-chalkboard-teacher', 'tone' => 'muted', 'note' => 'مدربون لديهم اتفاقيات أو مدفوعات'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · الماليات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">الماليات الخاصة بالمدربين</h2>
            <p class="mt-1 text-sm text-muted">اختر مدرباً لعرض كل المطلوب دفعه والمدفوع، والدفع في أي وقت (مسبقاً أو لاحقاً)</p>
        </div>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">
                    {{ $kpi['value'] }}
                    @if($kpi['suffix'])
                        <span class="text-sm font-normal text-muted">{{ $kpi['suffix'] }}</span>
                    @endif
                </p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">المدربون</h3>
                <p class="mt-0.5 text-xs text-muted">ادخل إلى صفحة أي مدرب لرؤية جميع المطلوب دفعه والمدفوع والدفع عند الحاجة</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($instructors->count()) }} مدرب</span>
        </div>

        @if($instructors->count() > 0)
            <div class="admin-table-wrap overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">المدرب</th>
                            <th class="px-4 py-3 text-start font-medium">مطلوب الدفع</th>
                            <th class="px-4 py-3 text-start font-medium">تم الدفع</th>
                            <th class="px-4 py-3 text-start font-medium">إجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($instructors as $instructor)
                            @php $stats = $statsByInstructor->get($instructor->id); @endphp
                            <tr class="hover:bg-canvas/40">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-ink">{{ $instructor->name }}</p>
                                    @if($instructor->email)
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $instructor->email }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($stats && (float) $stats->pending_total > 0)
                                        <p class="font-semibold tabular-nums text-metal">{{ number_format($stats->pending_total, 2) }} <span class="text-xs font-normal text-muted">$</span></p>
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $stats->pending_count }} مدفوعة</p>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($stats && (float) $stats->paid_total > 0)
                                        <p class="font-semibold tabular-nums text-accent">{{ number_format($stats->paid_total, 2) }} <span class="text-xs font-normal text-muted">$</span></p>
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $stats->paid_count }} مدفوعة</p>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.salaries.instructor', $instructor) }}" class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent">
                                        <i class="fas fa-list"></i>
                                        عرض المطلوب والمدفوع
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-16 text-center">
                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                    <i class="fas fa-users"></i>
                </div>
                <p class="text-sm font-medium text-ink">لا يوجد مدربون لديهم اتفاقيات أو مدفوعات حالياً.</p>
                <p class="mt-1 text-xs text-muted">أي مدرب له اتفاقية أو مدفوعة (قيد المراجعة / مطلوب الدفع / تم الدفع) يظهر في القائمة.</p>
            </div>
        @endif
    </article>
</div>
@endsection
