@extends('layouts.admin')

@section('title', 'سجل معاملات المحفظة')
@section('page_title', 'سجل معاملات المحفظة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $kpis = [
        ['label' => 'رصيد المحفظة', 'value' => number_format($wallet->balance, 2), 'icon' => 'fa-coins', 'tone' => 'accent', 'suffix' => ' ' . ($wallet->currency ?? currency_symbol())],
        ['label' => 'الرصيد المعلّق', 'value' => number_format($wallet->pending_balance ?? 0, 2), 'icon' => 'fa-hourglass-half', 'tone' => 'metal', 'suffix' => ' ' . ($wallet->currency ?? currency_symbol())],
        ['label' => 'عدد المعاملات', 'value' => $transactions->count(), 'icon' => 'fa-receipt', 'tone' => 'accent', 'suffix' => ''],
        ['label' => 'آخر عملية', 'value' => optional($transactions->first())->created_at?->format('Y-m-d H:i') ?? 'غير متوفر', 'icon' => 'fa-clock', 'tone' => 'muted', 'suffix' => '', 'small' => true],
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
            <p class="text-xs font-medium text-muted">المالية · المحافظ · المعاملات</p>
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
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">المعاملات الأخيرة</h3>
                <p class="mt-0.5 text-xs text-muted">تابع حركة المحفظة مع توضيح نوع العملية والملاحظات المرتبطة بها</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ $transactions->count() }} معاملة</span>
        </div>

        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                        <th class="px-4 py-3 text-start font-medium">النوع</th>
                        <th class="px-4 py-3 text-start font-medium">المبلغ</th>
                        <th class="px-4 py-3 text-start font-medium">الرصيد بعد العملية</th>
                        <th class="px-4 py-3 text-start font-medium">المرجع</th>
                        <th class="px-4 py-3 text-start font-medium">الملاحظات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3 text-xs tabular-nums text-muted">
                                {{ $transaction->created_at?->format('Y-m-d H:i') ?? 'غير معروف' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $transaction->type === 'deposit' ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                                    {{ $transaction->type === 'deposit' ? 'إيداع' : 'سحب' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold tabular-nums {{ $transaction->type === 'deposit' ? 'text-accent' : 'text-ink' }}">
                                {{ number_format($transaction->amount, 2) }} <span class="text-xs font-normal text-muted">{{ $wallet->currency ?? currency_symbol() }}</span>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-ink-soft">
                                {{ number_format($transaction->balance_after ?? 0, 2) }} <span class="text-xs text-muted">{{ $wallet->currency ?? currency_symbol() }}</span>
                            </td>
                            <td class="px-4 py-3 text-ink-soft">
                                {{ $transaction->reference_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-ink-soft">
                                {{ $transaction->notes ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا توجد معاملات مسجلة</p>
                                <p class="mt-1 text-xs text-muted">ستظهر العمليات فور تسجيلها من خلال الإيداعات أو السحوبات.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</div>
@endsection
