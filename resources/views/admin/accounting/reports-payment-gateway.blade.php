@extends('layouts.admin')

@section('title', 'بوابة الدفع - التقارير المحاسبية - حصتك')
@section('page_title', 'مدفوعات بوابة الدفع')

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
        ['label' => 'عدد العمليات', 'value' => number_format($gatewaySummary['count']), 'icon' => 'fa-hashtag', 'tone' => 'accent', 'note' => 'مدفوعات أونلاين مكتملة', 'raw' => true],
        ['label' => 'إجمالي المحصّل (عميل)', 'value' => number_format($gatewaySummary['gross'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-money-bill-wave', 'tone' => 'accent', 'note' => 'ما دفعه العملاء', 'raw' => true],
        ['label' => 'عمولات البوابة (تقدير)', 'value' => number_format($gatewaySummary['fees'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-percentage', 'tone' => 'metal', 'note' => 'رسوم البوابة التقديرية', 'raw' => true],
        ['label' => 'صافي بعد العمولة', 'value' => number_format($gatewaySummary['net'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-chart-line', 'tone' => 'muted', 'note' => 'المحصّل ناقص العمولات', 'raw' => true],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.accounting.reports') }}" class="transition hover:text-accent">التقارير المحاسبية</a>
                <span class="mx-1">/</span>
                بوابة الدفع
            </p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">ملخص مدفوعات البوابة</h2>
            <p class="mt-1 text-sm text-muted">أونلاين ومكتملة — من {{ $startDate->format('Y-m-d') }} إلى {{ $endDate->format('Y-m-d') }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.accounting.reports.export', array_merge(request()->query(), ['type' => 'payment_gateway'])) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-file-excel text-xs"></i>
                تصدير Excel
            </a>
            <a href="{{ route('admin.accounting.reports') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة للملخص
            </a>
        </div>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">فلترة الفترة</h3>
            <p class="mt-0.5 text-xs text-muted">اختر فترة جاهزة أو حدّد نطاقاً مخصصاً</p>
        </div>
        <form method="GET" action="{{ route('admin.accounting.reports.payment-gateway') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
            <div>
                <label class="{{ $labelClass }}" for="period">الفترة</label>
                <select id="period" name="period" class="{{ $fieldClass }}" onchange="this.form.submit()">
                    <option value="day" {{ ($period ?? '') == 'day' ? 'selected' : '' }}>اليوم</option>
                    <option value="week" {{ ($period ?? '') == 'week' ? 'selected' : '' }}>هذا الأسبوع</option>
                    <option value="month" {{ ($period ?? '') == 'month' ? 'selected' : '' }}>هذا الشهر</option>
                    <option value="year" {{ ($period ?? '') == 'year' ? 'selected' : '' }}>هذه السنة</option>
                    <option value="all" {{ ($period ?? '') == 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="custom" {{ ($period ?? '') == 'custom' ? 'selected' : '' }}>مخصص</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="start_date">من تاريخ</label>
                <input id="start_date" type="date" name="start_date" value="{{ $startDate ? $startDate->format('Y-m-d') : '' }}" class="{{ $fieldClass }}" />
            </div>
            <div>
                <label class="{{ $labelClass }}" for="end_date">إلى تاريخ</label>
                <input id="end_date" type="date" name="end_date" value="{{ $endDate ? $endDate->format('Y-m-d') : '' }}" class="{{ $fieldClass }}" />
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    تطبيق
                </button>
            </div>
        </form>
    </article>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ $kpi['value'] }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    @if($byGateway->isNotEmpty())
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">حسب بوابة الدفع</h3>
                <p class="mt-0.5 text-xs text-muted">تفصيل المحصّل والعمولات والصافي لكل بوابة</p>
            </div>
            <div class="admin-table-wrap overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">البوابة</th>
                            <th class="px-4 py-3 text-start font-medium">العدد</th>
                            <th class="px-4 py-3 text-start font-medium">المحصّل</th>
                            <th class="px-4 py-3 text-start font-medium">العمولات</th>
                            <th class="px-4 py-3 text-start font-medium">الصافي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($byGateway as $g)
                            <tr class="hover:bg-canvas/40">
                                <td class="px-4 py-3 font-semibold text-ink">{{ $g->payment_gateway }}</td>
                                <td class="px-4 py-3 tabular-nums text-muted">{{ number_format($g->cnt) }}</td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-ink">{{ number_format($g->gross, 2) }}</td>
                                <td class="px-4 py-3 tabular-nums text-metal">{{ number_format($g->fees, 2) }}</td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-accent">{{ number_format($g->net, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">تفاصيل المدفوعات</h3>
                <p class="mt-0.5 text-xs text-muted">فواتيرك تظهر كـ <code class="rounded bg-canvas-muted px-1 py-0.5 text-[11px] text-ink">other</code>، كاشير كـ <code class="rounded bg-canvas-muted px-1 py-0.5 text-[11px] text-ink">kashier</code></p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($items->total()) }} دفعة</span>
        </div>

        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">رقم الدفعة</th>
                        <th class="px-4 py-3 text-start font-medium">العميل</th>
                        <th class="px-4 py-3 text-start font-medium">البوابة</th>
                        <th class="px-4 py-3 text-start font-medium">المحصّل</th>
                        <th class="px-4 py-3 text-start font-medium">العمولة</th>
                        <th class="px-4 py-3 text-start font-medium">الصافي</th>
                        <th class="px-4 py-3 text-start font-medium">الفاتورة</th>
                        <th class="px-4 py-3 text-start font-medium">تاريخ الدفع</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($items as $payment)
                        @php
                            $fee = (float) ($payment->gateway_fee_amount ?? 0);
                            $net = $payment->net_after_gateway_fee !== null
                                ? (float) $payment->net_after_gateway_fee
                                : round((float) $payment->amount - $fee, 2);
                        @endphp
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.payments.show', $payment) }}" class="font-semibold text-accent hover:text-accent">{{ $payment->payment_number }}</a>
                            </td>
                            <td class="px-4 py-3 text-ink-soft">{{ $payment->user->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-muted">{{ $payment->payment_gateway }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums text-ink">{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3 tabular-nums text-metal">{{ number_format($fee, 2) }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums text-accent">{{ number_format($net, 2) }}</td>
                            <td class="px-4 py-3">
                                @if($payment->invoice)
                                    <a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="text-accent hover:text-accent">{{ $payment->invoice->invoice_number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs tabular-nums text-muted">{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا توجد مدفوعات</p>
                                <p class="mt-1 text-xs text-muted">لا توجد مدفوعات بوابة في هذه الفترة.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $items->links() }}</div>
        @endif
    </article>
</div>
@endsection
