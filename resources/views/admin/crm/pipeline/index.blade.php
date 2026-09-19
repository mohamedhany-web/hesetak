@extends('layouts.admin')

@section('title', 'Pipeline CRM - حصتك')
@section('page_title', 'لوحة مسار البيع')

@section('content')
@php
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $kpis = [
        ['label' => 'الإجمالي', 'value' => $totalLeads, 'icon' => 'fa-users', 'tone' => 'accent', 'note' => 'كل العملاء في المسار'],
        ['label' => 'مفتوح', 'value' => $openLeads, 'icon' => 'fa-folder-open', 'tone' => 'accent', 'note' => 'ما زال قيد المتابعة'],
        ['label' => 'بانتظار الدفع', 'value' => $paymentPending, 'icon' => 'fa-clock', 'tone' => 'metal', 'note' => 'مرحلة حساسة'],
        ['label' => 'مغلق ناجح', 'value' => $closedWon, 'icon' => 'fa-check-circle', 'tone' => 'muted', 'note' => 'صفقات مكتملة'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المبيعات · CRM · Pipeline</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">لوحة مسار البيع</h2>
            <p class="mt-1 text-sm text-muted">ملخص أعداد كل مرحلة — افتح القائمة التفصيلية عند الحاجة</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.crm.leads.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-list text-xs"></i>
                قائمة العملاء
            </a>
            <a href="{{ route('admin.crm.dashboard') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-chart-pie text-xs"></i>
                لوحة CRM
            </a>
        </div>
    </section>

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
            <h3 class="text-base font-semibold text-ink">مراحل المسار</h3>
            <p class="mt-0.5 text-xs text-muted">اضغط على أي مرحلة لفتح العملاء المصطفّين فيها فقط</p>
        </div>
        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">المرحلة</th>
                        <th class="px-4 py-3 text-start font-medium">العدد</th>
                        <th class="px-4 py-3 text-start font-medium">النسبة</th>
                        <th class="px-4 py-3 text-start font-medium w-48">التوزيع</th>
                        <th class="px-4 py-3 text-start font-medium">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($stagesSummary as $stage)
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink">{{ $stage['label'] }}</p>
                                <p class="mt-0.5 font-mono text-[11px] text-muted" dir="ltr">{{ $stage['status'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-base font-semibold tabular-nums text-ink">{{ number_format($stage['count']) }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink-soft">{{ number_format($stage['percent'], 1) }}%</td>
                            <td class="px-4 py-3">
                                <div class="h-2 overflow-hidden rounded-full bg-canvas-muted">
                                    <div class="h-full rounded-full bg-accent" style="width: {{ min(100, $stage['percent']) }}%"></div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.crm.leads.index', ['status' => $stage['status']]) }}"
                                   class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-3 text-xs font-medium text-ink transition hover:border-accent/30 hover:text-accent">
                                    عرض العملاء
                                    <i class="fas fa-arrow-left text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
</div>
@endsection
