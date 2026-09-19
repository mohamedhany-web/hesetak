@extends('layouts.admin')

@section('title', 'تحليلات المبيعات')
@section('page_title', 'تحليلات المبيعات')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $kpis = [
        ['label' => 'طلبات الفترة', 'value' => number_format($stats['orders_total']), 'icon' => 'fa-shopping-bag', 'tone' => 'accent', 'note' => 'كل الطلبات الجديدة'],
        ['label' => 'معلّقة الآن', 'value' => number_format($stats['pending']), 'icon' => 'fa-clock', 'tone' => 'metal', 'note' => 'بانتظار الاعتماد'],
        ['label' => 'إيراد الفترة', 'value' => number_format($stats['revenue_period'], 2), 'icon' => 'fa-coins', 'tone' => 'accent', 'note' => 'معتمد بالريال'],
        ['label' => 'إيراد الشهر', 'value' => number_format($stats['revenue_month'], 2), 'icon' => 'fa-calendar', 'tone' => 'muted', 'note' => 'من أول الشهر'],
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
            <p class="text-xs font-medium text-muted">المبيعات · التحليلات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">لوحة المبيعات</h2>
            <p class="mt-1 text-sm text-muted">إيرادات معتمدة، أداء المناديب، وأكثر الكورسات طلباً</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.crm.leads.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-users text-xs"></i>
                CRM Leads
            </a>
            <a href="{{ route('admin.orders.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-shopping-bag text-xs"></i>
                الطلبات
            </a>
        </div>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">الفترة</h3>
            <p class="mt-0.5 text-xs text-muted">حدّد المدى الزمني ثم حدّث المؤشرات</p>
        </div>
        <form method="GET" action="{{ route('admin.sales.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3 sm:items-end sm:p-5">
            <div>
                <label class="{{ $labelClass }}" for="from">من</label>
                <input id="from" type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="to">إلى</label>
                <input id="to" type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="{{ $fieldClass }}">
            </div>
            <button type="submit" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-sync-alt text-xs"></i>
                تحديث
            </button>
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

    @if($stats['conversion'] !== null)
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm text-ink shadow-soft">
            نسبة التحويل التقريبية (معتمد ÷ طلبات جديدة):
            <strong class="text-accent">{{ $stats['conversion'] }}%</strong>
        </div>
    @endif

    @if($unassignedPending > 0)
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm text-ink shadow-soft">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-metal/15 text-metal"><i class="fas fa-exclamation-triangle text-sm"></i></span>
            <p class="flex-1">يوجد <strong>{{ $unassignedPending }}</strong> طلب معلّق بدون مندوب مبيعات.</p>
            <a href="{{ route('admin.orders.index', ['status' => 'pending', 'sales_owner_id' => 'unassigned']) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent">
                عرضها
            </a>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <h3 class="text-base font-semibold text-ink">طلبات جديدة يومياً</h3>
            <div class="mt-4 flex h-40 items-end gap-1">
                @php $maxD = max($daily->max() ?: 0, 1); @endphp
                @forelse($daily as $day => $count)
                    <div class="group flex min-w-0 flex-1 flex-col items-center justify-end">
                        <span class="mb-1 text-[10px] tabular-nums text-muted">{{ $count }}</span>
                        <div class="mx-auto w-full max-w-[24px] rounded-t bg-accent/80 transition group-hover:bg-accent" style="height: {{ max(4, ($count / $maxD) * 100) }}%"></div>
                        <span class="mt-1 w-full truncate text-center text-[9px] text-muted" title="{{ $day }}">{{ \Illuminate\Support\Str::afterLast($day, '-') }}</span>
                    </div>
                @empty
                    <p class="w-full py-8 text-center text-sm text-muted">لا بيانات في هذه الفترة.</p>
                @endforelse
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">أكثر الكورسات إيراداً (معتمد)</h3>
            </div>
            <div class="admin-table-wrap overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">الكورس</th>
                            <th class="px-4 py-3 text-start font-medium">طلبات</th>
                            <th class="px-4 py-3 text-start font-medium">إيراد</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($topCourses as $row)
                            <tr class="hover:bg-canvas/40">
                                <td class="px-4 py-3 font-medium text-ink">{{ $row->course?->title ?? 'كورس #'.$row->advanced_course_id }}</td>
                                <td class="px-4 py-3 tabular-nums text-ink-soft">{{ number_format($row->order_count) }}</td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-accent">{{ number_format((float) $row->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-10 text-center text-sm text-muted">لا بيانات.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">أداء مناديب المبيعات</h3>
            <span class="text-xs text-muted">وظيفة «سيلز» فقط</span>
        </div>
        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">المندوب</th>
                        <th class="px-4 py-3 text-start font-medium">معلّقة مسندة</th>
                        <th class="px-4 py-3 text-start font-medium">صفقات معتمدة (الفترة)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($repStats as $rep)
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3 font-medium text-ink">{{ $rep->name }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink-soft">{{ number_format($rep->owned_pending) }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums text-accent">{{ number_format($rep->owned_won_period) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10 text-center text-sm text-muted">لا يوجد موظفون بوظيفة مبيعات أو لا بيانات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.orders.index') }}" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
            <i class="fas fa-shopping-bag text-xs"></i>
            إدارة الطلبات
        </a>
        @if(Route::has('admin.coupons.index'))
            <a href="{{ route('admin.coupons.index') }}" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-canvas">
                <i class="fas fa-ticket-alt text-xs"></i>
                الكوبونات
            </a>
        @endif
        @if(Route::has('admin.sales.leads.index'))
            <a href="{{ route('admin.sales.leads.index') }}" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-canvas">
                <i class="fas fa-user-plus text-xs"></i>
                عملاء المبيعات
            </a>
        @endif
    </div>
</div>
@endsection
