@extends('layouts.admin')

@section('title', 'التقارير المحاسبية - حصتك')
@section('page_title', 'التقارير المحاسبية')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $mainKpis = [
        ['label' => 'إجمالي الإيرادات', 'value' => number_format($stats['total_revenue'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-arrow-down', 'tone' => 'accent', 'note' => 'مدفوعات ومقبوضات الفترة', 'raw' => true],
        ['label' => 'إجمالي المصروفات', 'value' => number_format($stats['total_expenses'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-arrow-up', 'tone' => 'muted', 'note' => 'مصروفات وتكاليف الفترة', 'raw' => true],
        ['label' => 'الربح الصافي', 'value' => number_format($stats['net_profit'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-chart-line', 'tone' => $stats['net_profit'] >= 0 ? 'accent' : 'muted', 'note' => 'الإيرادات ناقص المصروفات', 'raw' => true],
        ['label' => 'نسبة الربحية', 'value' => ($stats['total_revenue'] > 0 ? number_format(($stats['net_profit'] / $stats['total_revenue']) * 100, 2) : '0') . '%', 'icon' => 'fa-percentage', 'tone' => 'metal', 'note' => 'من إجمالي الإيرادات', 'raw' => true],
    ];
    $secondaryKpis = [
        ['label' => 'محافظ المنصة', 'value' => $stats['wallet_stats']['total_wallets'], 'icon' => 'fa-wallet', 'tone' => 'accent', 'note' => $stats['wallet_stats']['active_wallets'] . ' نشطة · ' . number_format($stats['wallet_stats']['total_balance'], 2) . ' ' . currency_symbol()],
        ['label' => 'الطلبات (الفترة)', 'value' => $stats['order_stats']['total_orders'], 'icon' => 'fa-shopping-cart', 'tone' => 'metal', 'note' => $stats['order_stats']['approved_orders'] . ' معتمدة · ' . $stats['order_stats']['pending_orders'] . ' معلقة'],
    ];
    $sectionLinks = [
        ['route' => 'admin.accounting.reports.invoices', 'icon' => 'fa-file-invoice', 'tone' => 'accent', 'title' => 'الفواتير', 'note' => $stats['total_invoices'] . ' فاتورة في الفترة'],
        ['route' => 'admin.accounting.reports.payments', 'icon' => 'fa-money-bill-wave', 'tone' => 'metal', 'title' => 'المدفوعات', 'note' => $stats['total_payments'] . ' دفعة في الفترة'],
        ['route' => 'admin.accounting.reports.transactions', 'icon' => 'fa-exchange-alt', 'tone' => 'muted', 'title' => 'المعاملات المالية', 'note' => $stats['total_transactions'] . ' معاملة في الفترة'],
        ['route' => 'admin.accounting.reports.expenses', 'icon' => 'fa-receipt', 'tone' => 'muted', 'title' => 'المصروفات', 'note' => 'عرض المصروفات في الفترة'],
        ['route' => 'admin.accounting.reports.wallets', 'icon' => 'fa-wallet', 'tone' => 'accent', 'title' => 'محافظ المنصة', 'note' => $stats['wallet_stats']['total_wallets'] . ' محفظة'],
        ['route' => 'admin.accounting.reports.orders', 'icon' => 'fa-shopping-cart', 'tone' => 'metal', 'title' => 'الطلبات', 'note' => $stats['order_stats']['total_orders'] . ' طلب في الفترة'],
        ['route' => 'admin.accounting.reports.payment-gateway', 'icon' => 'fa-credit-card', 'tone' => 'accent', 'title' => 'بوابة الدفع', 'note' => 'مدفوعات أونلاين، عمولات، صافي'],
    ];
    $query = request()->only(['period', 'start_date', 'end_date']);
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · التقارير</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">التقارير المحاسبية</h2>
            <p class="mt-1 text-sm text-muted">تقارير شاملة عن جميع العمليات المالية</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.accounting.reports.export', array_merge(request()->all(), ['type' => 'all'])) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-file-excel text-xs"></i>
                تصدير Excel شامل
            </a>
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button"
                        class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                    <i class="fas fa-download text-xs"></i>
                    تصدير محدد
                    <i class="fas fa-chevron-down text-[10px]"></i>
                </button>
                <div x-show="open"
                     @click.away="open = false"
                     x-transition
                     class="absolute left-0 z-50 mt-2 w-56 rounded-2xl border border-line bg-surface p-2 shadow-soft">
                    <div class="space-y-1">
                        <a href="{{ route('admin.accounting.reports.export', array_merge(request()->all(), ['type' => 'summary'])) }}"
                           class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm text-ink transition hover:bg-canvas/60">
                            <i class="fas fa-chart-pie w-4 text-muted"></i>
                            الملخص المالي
                        </a>
                        <a href="{{ route('admin.accounting.reports.export', array_merge(request()->all(), ['type' => 'invoices'])) }}"
                           class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm text-ink transition hover:bg-canvas/60">
                            <i class="fas fa-file-invoice w-4 text-muted"></i>
                            الفواتير
                        </a>
                        <a href="{{ route('admin.accounting.reports.export', array_merge(request()->all(), ['type' => 'payments'])) }}"
                           class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm text-ink transition hover:bg-canvas/60">
                            <i class="fas fa-money-bill-wave w-4 text-muted"></i>
                            المدفوعات
                        </a>
                        <a href="{{ route('admin.accounting.reports.export', array_merge(request()->all(), ['type' => 'transactions'])) }}"
                           class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm text-ink transition hover:bg-canvas/60">
                            <i class="fas fa-exchange-alt w-4 text-muted"></i>
                            المعاملات
                        </a>
                        <a href="{{ route('admin.accounting.reports.export', array_merge(request()->all(), ['type' => 'expenses'])) }}"
                           class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm text-ink transition hover:bg-canvas/60">
                            <i class="fas fa-receipt w-4 text-muted"></i>
                            المصروفات
                        </a>
                        <a href="{{ route('admin.accounting.reports.export', array_merge(request()->all(), ['type' => 'wallets'])) }}"
                           class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm text-ink transition hover:bg-canvas/60">
                            <i class="fas fa-wallet w-4 text-muted"></i>
                            المحافظ
                        </a>
                        <a href="{{ route('admin.accounting.reports.export', array_merge(request()->all(), ['type' => 'orders'])) }}"
                           class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm text-ink transition hover:bg-canvas/60">
                            <i class="fas fa-shopping-cart w-4 text-muted"></i>
                            الطلبات
                        </a>
                        <a href="{{ route('admin.accounting.reports.export', array_merge(request()->all(), ['type' => 'payment_gateway'])) }}"
                           class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm text-ink transition hover:bg-canvas/60">
                            <i class="fas fa-credit-card w-4 text-muted"></i>
                            بوابة الدفع
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">فلترة الفترة الزمنية</h3>
            <p class="mt-0.5 text-xs text-muted">اختر فترة جاهزة أو حدّد نطاقاً مخصصاً</p>
        </div>
        <form method="GET" action="{{ route('admin.accounting.reports') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
            <div>
                <label class="{{ $labelClass }}" for="period">الفترة الزمنية</label>
                <select id="period" name="period" class="{{ $fieldClass }}" onchange="this.form.submit()">
                    <option value="day" {{ $period == 'day' ? 'selected' : '' }}>اليوم</option>
                    <option value="week" {{ $period == 'week' ? 'selected' : '' }}>هذا الأسبوع</option>
                    <option value="month" {{ $period == 'month' ? 'selected' : '' }}>هذا الشهر</option>
                    <option value="year" {{ $period == 'year' ? 'selected' : '' }}>هذه السنة</option>
                    <option value="all" {{ $period == 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="custom" {{ $period == 'custom' ? 'selected' : '' }}>مخصص</option>
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
                    تطبيق الفلترة
                </button>
            </div>
        </form>
    </article>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($mainKpis as $kpi)
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

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2">
        @foreach($secondaryKpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($kpi['value']) }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
                @if($kpi['label'] === 'الطلبات (الفترة)')
                    <p class="mt-2 text-[11px] text-muted">مبالغ معتمدة: <strong class="text-ink">{{ number_format($stats['order_stats']['approved_amount'], 2) }} $</strong></p>
                @endif
            </article>
        @endforeach
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">الفواتير</h3>
            </div>
            <div class="space-y-0 divide-y divide-line p-4 sm:p-5">
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-muted">إجمالي الفواتير</span>
                    <span class="text-lg font-semibold tabular-nums text-ink">{{ number_format($stats['total_invoices']) }}</span>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-muted">مدفوعة</span>
                    <span class="text-lg font-semibold tabular-nums text-accent">{{ number_format($stats['paid_invoices']) }}</span>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-muted">معلقة</span>
                    <span class="text-lg font-semibold tabular-nums text-metal">{{ number_format($stats['pending_invoices']) }}</span>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">المدفوعات</h3>
            </div>
            <div class="space-y-0 divide-y divide-line p-4 sm:p-5">
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-muted">إجمالي المدفوعات</span>
                    <span class="text-lg font-semibold tabular-nums text-ink">{{ number_format($stats['total_payments']) }}</span>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-muted">مكتملة</span>
                    <span class="text-lg font-semibold tabular-nums text-accent">{{ number_format($stats['completed_payments']) }}</span>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">المعاملات</h3>
            </div>
            <div class="p-4 sm:p-5">
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-muted">إجمالي المعاملات</span>
                    <span class="text-lg font-semibold tabular-nums text-ink">{{ number_format($stats['total_transactions']) }}</span>
                </div>
            </div>
        </article>
    </div>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">عرض التفاصيل حسب القسم</h3>
            <p class="mt-0.5 text-xs text-muted">كل قسم في صفحة منفصلة مع ترقيم الصفحات وتصدير Excel — من {{ $startDate->format('Y-m-d') }} إلى {{ $endDate->format('Y-m-d') }}</p>
        </div>
        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 sm:p-5 lg:grid-cols-3">
            @foreach($sectionLinks as $link)
                <a href="{{ route($link['route'], $query) }}"
                   class="btn-press flex items-center gap-4 rounded-2xl border border-line bg-surface p-4 transition hover:border-accent/30 hover:bg-canvas/40">
                    <div class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl {{ $toneClass[$link['tone']] }}">
                        <i class="fas {{ $link['icon'] }} text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-ink">{{ $link['title'] }}</p>
                        <p class="mt-0.5 text-xs text-muted">{{ $link['note'] }}</p>
                    </div>
                    <i class="fas fa-chevron-left shrink-0 text-xs text-muted"></i>
                </a>
            @endforeach
        </div>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">تفاصيل الإيرادات</h3>
        </div>
        <div class="grid grid-cols-1 gap-6 p-4 sm:p-5 md:grid-cols-2">
            <div>
                <h4 class="mb-4 text-sm font-semibold text-ink">حسب طريقة الدفع</h4>
                <div class="space-y-3">
                    @forelse($revenueReports['from_payments'] as $item)
                        <div class="flex items-center justify-between rounded-xl border border-line bg-canvas/40 p-3">
                            <div>
                                <p class="text-sm font-medium text-ink">{{ $item->payment_method }}</p>
                                <p class="text-xs text-muted">{{ $item->count }} دفعة</p>
                            </div>
                            <p class="text-sm font-semibold tabular-nums text-accent">{{ number_format($item->total, 2) }} $</p>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-muted">لا توجد بيانات</p>
                    @endforelse
                </div>
            </div>
            <div>
                <h4 class="mb-4 text-sm font-semibold text-ink">حسب الفئة</h4>
                <div class="space-y-3">
                    @forelse($revenueReports['from_transactions'] as $item)
                        <div class="flex items-center justify-between rounded-xl border border-line bg-canvas/40 p-3">
                            <div>
                                <p class="text-sm font-medium text-ink">{{ $item->category ?? 'غير محدد' }}</p>
                                <p class="text-xs text-muted">{{ $item->count }} معاملة</p>
                            </div>
                            <p class="text-sm font-semibold tabular-nums text-accent">{{ number_format($item->total, 2) }} $</p>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-muted">لا توجد بيانات</p>
                    @endforelse
                </div>
            </div>
        </div>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">تفاصيل المصروفات</h3>
        </div>
        <div class="grid grid-cols-1 gap-6 p-4 sm:p-5 md:grid-cols-2">
            <div>
                <h4 class="mb-4 text-sm font-semibold text-ink">حسب الفئة</h4>
                <div class="space-y-3">
                    @forelse($expenseReports['from_expenses'] as $item)
                        <div class="flex items-center justify-between rounded-xl border border-line bg-canvas/40 p-3">
                            <div>
                                <p class="text-sm font-medium text-ink">{{ \App\Models\Expense::categoryLabel($item->category) }}</p>
                                <p class="text-xs text-muted">{{ $item->count }} مصروف</p>
                            </div>
                            <p class="text-sm font-semibold tabular-nums text-ink">{{ number_format($item->total, 2) }} $</p>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-muted">لا توجد بيانات</p>
                    @endforelse
                </div>
            </div>
            <div>
                <h4 class="mb-4 text-sm font-semibold text-ink">من المعاملات</h4>
                <div class="space-y-3">
                    @forelse($expenseReports['from_transactions'] as $item)
                        <div class="flex items-center justify-between rounded-xl border border-line bg-canvas/40 p-3">
                            <div>
                                <p class="text-sm font-medium text-ink">{{ $item->category ?? 'غير محدد' }}</p>
                                <p class="text-xs text-muted">{{ $item->count }} معاملة</p>
                            </div>
                            <p class="text-sm font-semibold tabular-nums text-ink">{{ number_format($item->total, 2) }} $</p>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-muted">لا توجد بيانات</p>
                    @endforelse
                </div>
            </div>
        </div>
    </article>

    @if(count($monthlyData['months']) > 0)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">الإيرادات والمصروفات الشهرية</h3>
            </div>
            <div class="space-y-4 p-4 sm:p-5">
                @foreach($monthlyData['months'] as $index => $month)
                    <div>
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-medium text-ink">{{ \Carbon\Carbon::parse($month . '-01')->format('Y-m') }}</span>
                            <div class="flex flex-wrap items-center gap-4 text-xs">
                                <span class="text-accent">إيرادات: {{ number_format($monthlyData['revenues'][$index], 2) }} $</span>
                                <span class="text-muted">مصروفات: {{ number_format($monthlyData['expenses'][$index], 2) }} $</span>
                                <span class="font-semibold text-ink">صافي: {{ number_format($monthlyData['revenues'][$index] - $monthlyData['expenses'][$index], 2) }} $</span>
                            </div>
                        </div>
                        <div class="relative h-8 overflow-hidden rounded-full bg-canvas-muted">
                            <div class="absolute inset-0 flex">
                                <div class="bg-accent" style="width: {{ $monthlyData['revenues'][$index] > 0 ? min(($monthlyData['revenues'][$index] / max($monthlyData['revenues'][$index], $monthlyData['expenses'][$index])) * 100, 100) : 0 }}%"></div>
                                <div class="bg-metal/30" style="width: {{ $monthlyData['expenses'][$index] > 0 ? min(($monthlyData['expenses'][$index] / max($monthlyData['revenues'][$index], $monthlyData['expenses'][$index])) * 100, 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    @endif
</div>
@endsection
