@extends('layouts.admin')

@section('title', 'تقارير المحفظة')
@section('page_title', 'تقارير المحفظة')

@section('content')
@php
    $fieldClass = 'h-11 rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $kpis = [
        ['label' => 'الرصيد الحالي', 'value' => number_format($wallet->balance, 2), 'icon' => 'fa-coins', 'tone' => 'accent', 'suffix' => ' ' . ($wallet->currency ?? currency_symbol())],
        ['label' => 'الرصيد المعلق', 'value' => number_format($wallet->pending_balance ?? 0, 2), 'icon' => 'fa-hourglass-half', 'tone' => 'metal', 'suffix' => ' ' . ($wallet->currency ?? currency_symbol())],
        ['label' => 'عدد التقارير', 'value' => $reports->count(), 'icon' => 'fa-file-alt', 'tone' => 'accent', 'suffix' => ''],
        ['label' => 'آخر تحديث', 'value' => $wallet->updated_at?->format('Y-m-d H:i') ?? 'غير متوفر', 'icon' => 'fa-clock', 'tone' => 'muted', 'suffix' => '', 'small' => true],
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
            <p class="text-xs font-medium text-muted">المالية · المحافظ · التقارير</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $wallet->name ?? 'محفظة بدون اسم' }}</h2>
            <p class="mt-1 text-sm text-muted">
                <i class="fas fa-user-circle text-xs"></i>
                {{ $wallet->user?->name ?? 'غير مرتبط بمستخدم' }}
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.wallets.show', $wallet) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة للتفاصيل
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
                <p class="mt-1 {{ !empty($kpi['small']) ? 'text-sm font-semibold' : 'text-xl font-semibold tabular-nums' }} tracking-tight text-ink">{{ $kpi['value'] }}{{ $kpi['suffix'] ?? '' }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">إنشاء تقرير جديد</h3>
            <p class="mt-0.5 text-xs text-muted">حدد نطاق التاريخ ثم أنشئ تقريراً مفصلاً للمحفظة</p>
        </div>
        <form action="{{ route('admin.wallets.generate-report', $wallet) }}" method="POST" class="flex flex-col gap-4 p-4 sm:flex-row sm:flex-wrap sm:items-end sm:p-5">
            @csrf
            <div>
                <label class="{{ $labelClass }}" for="from">من</label>
                <input id="from" type="date" name="from" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="to">إلى</label>
                <input id="to" type="date" name="to" class="{{ $fieldClass }}">
            </div>
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-file-export text-xs"></i>
                إنشاء تقرير
            </button>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">التقارير المحفوظة</h3>
                <p class="mt-0.5 text-xs text-muted">سجل التقارير المالية السابقة للمحفظة</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ $reports->count() }} تقرير</span>
        </div>

        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                        <th class="px-4 py-3 text-start font-medium">العنوان</th>
                        <th class="px-4 py-3 text-start font-medium">إجمالي الإيداعات</th>
                        <th class="px-4 py-3 text-start font-medium">إجمالي السحوبات</th>
                        <th class="px-4 py-3 text-start font-medium">الرصيد النهائي</th>
                        <th class="px-4 py-3 text-start font-medium">الفرق</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($reports as $report)
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3 text-xs tabular-nums text-muted">
                                {{ $report->created_at?->format('Y-m-d H:i') ?? 'غير معروف' }}
                            </td>
                            <td class="px-4 py-3 font-medium text-ink">
                                {{ $report->title ?? 'تقرير بدون عنوان' }}
                            </td>
                            <td class="px-4 py-3 font-semibold tabular-nums text-accent">
                                {{ number_format($report->total_deposits ?? 0, 2) }} <span class="text-xs font-normal text-muted">{{ $wallet->currency ?? currency_symbol() }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold tabular-nums text-ink">
                                {{ number_format($report->total_withdrawals ?? 0, 2) }} <span class="text-xs font-normal text-muted">{{ $wallet->currency ?? currency_symbol() }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold tabular-nums text-ink">
                                {{ number_format($report->ending_balance ?? 0, 2) }} <span class="text-xs font-normal text-muted">{{ $wallet->currency ?? currency_symbol() }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold tabular-nums {{ ($report->difference ?? 0) == 0 ? 'text-muted' : (($report->difference ?? 0) > 0 ? 'text-accent' : 'text-ink') }}">
                                {{ number_format($report->difference ?? 0, 2) }} <span class="text-xs font-normal text-muted">{{ $wallet->currency ?? currency_symbol() }}</span>
                                @if($report->notes)
                                    <div class="mt-1 text-xs font-normal text-muted">{{ $report->notes }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا توجد تقارير محفوظة</p>
                                <p class="mt-1 text-xs text-muted">استخدم النموذج أعلاه لإنشاء تقرير جديد.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</div>
@endsection
