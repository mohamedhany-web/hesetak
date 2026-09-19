@extends('layouts.admin')

@section('title', 'حسابات المدربين - المحاسبة')
@section('page_title', 'حسابات المدربين')
@section('header', 'حسابات المدربين')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $kpis = [
        ['label' => 'عدد المدربين', 'value' => number_format($globalStats['instructors_count']), 'suffix' => '', 'icon' => 'fa-chalkboard-teacher', 'tone' => 'muted', 'note' => 'مدربون لديهم اتفاقيات أو مدفوعات'],
        ['label' => 'إجمالي مطلوب الدفع', 'value' => number_format($globalStats['pending_total'], 2), 'suffix' => currency_symbol(), 'icon' => 'fa-hourglass-half', 'tone' => 'metal', 'note' => 'مجموع المدفوعات المعلقة'],
        ['label' => 'إجمالي تم الدفع', 'value' => number_format($globalStats['paid_total'], 2), 'suffix' => currency_symbol(), 'icon' => 'fa-check-circle', 'tone' => 'accent', 'note' => 'مجموع المدفوعات المنفّذة'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · حسابات المدربين</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">حسابات المدربين</h2>
            <p class="mt-1 text-sm text-muted">رؤية كاملة: اتفاقيات، رواتب، مدفوعات، وأرباح نسبة الكورس لكل مدرب</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.accounting.reports') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-chart-pie text-xs"></i>
                التقارير المحاسبية
            </a>
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
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">بحث بالاسم أو البريد أو الهاتف</p>
        </div>
        <form method="GET" action="{{ route('admin.accounting.instructor-accounts.index') }}" class="flex flex-wrap items-end gap-3 p-4 sm:p-5">
            <div class="min-w-[16rem] flex-1">
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="بحث بالاسم أو البريد أو الهاتف..." class="{{ $fieldClass }}">
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-search text-xs"></i>
                    بحث
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.accounting.instructor-accounts.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-times text-xs"></i>
                        مسح
                    </a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">قائمة المدربين</h3>
                <p class="mt-0.5 text-xs text-muted">عرض الحساب الكامل لكل مدرب</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($instructors->count()) }} مدرب</span>
        </div>

        @if($instructors->count() > 0)
            <div class="admin-table-wrap overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">المدرب</th>
                            <th class="px-4 py-3 text-start font-medium">عدد الاتفاقيات</th>
                            <th class="px-4 py-3 text-start font-medium">مطلوب الدفع</th>
                            <th class="px-4 py-3 text-start font-medium">تم الدفع</th>
                            <th class="px-4 py-3 text-start font-medium">إجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($instructors as $instructor)
                            @php $stats = $statsByInstructor[$instructor->id] ?? []; @endphp
                            <tr class="hover:bg-canvas/40">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-ink">{{ $instructor->name }}</p>
                                    @if($instructor->email)
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $instructor->email }}</p>
                                    @endif
                                    @if($instructor->phone)
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $instructor->phone }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 tabular-nums text-ink-soft">{{ $stats['agreements_count'] ?? 0 }}</td>
                                <td class="px-4 py-3">
                                    @if(($stats['pending_total'] ?? 0) > 0)
                                        <p class="font-semibold tabular-nums text-metal">{{ number_format($stats['pending_total'], 2) }} <span class="text-xs font-normal text-muted">$</span></p>
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $stats['pending_count'] ?? 0 }} مدفوعة</p>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if(($stats['paid_total'] ?? 0) > 0)
                                        <p class="font-semibold tabular-nums text-accent">{{ number_format($stats['paid_total'], 2) }} <span class="text-xs font-normal text-muted">$</span></p>
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $stats['paid_count'] ?? 0 }} مدفوعة</p>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.accounting.instructor-accounts.show', $instructor) }}" class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                        عرض الحساب الكامل
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
                <p class="text-sm font-medium text-ink">لا يوجد مدربون لديهم اتفاقيات أو مدفوعات.</p>
            </div>
        @endif
    </article>
</div>
@endsection
