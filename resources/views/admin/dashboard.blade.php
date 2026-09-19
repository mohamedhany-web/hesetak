@extends('layouts.admin')

@section('title', 'لوحة الإدارة - حصتك')
@section('page_title', 'لوحة الإدارة')

@section('content')
@php
    $ds = $dashboardShow ?? [];
    $hasAnyDashboardWidget = collect($ds)->filter(fn ($v) => (bool) $v)->isNotEmpty();
    $trendPct = function (?array $trend): ?float {
        if (! is_array($trend) || ! array_key_exists('percent', $trend)) {
            return null;
        }
        return (float) $trend['percent'];
    };
    $spark = function (?float $pct): array {
        $base = [42, 48, 45, 52, 58, 55, 62];
        if ($pct === null) {
            return $base;
        }
        $factor = max(0.55, min(1.15, 0.85 + ($pct / 100)));
        return array_map(fn ($v) => (int) round($v * $factor), $base);
    };
    $ordersPending = (int) data_get($salesSection, 'orders_pending', 0);
    $recentOrders = collect(data_get($salesSection, 'recent_orders', []));
    $currency = currency_symbol();

    $studentsTotal = (int) data_get($metrics, 'students.total', data_get($stats, 'total_students', 0));
    $coursesTotal = (int) data_get($metrics, 'courses.total', data_get($stats, 'total_courses', 0));
    $enrollmentsTotal = (int) data_get($metrics, 'enrollments.total', data_get($stats, 'total_enrollments', 0));
    $usersTotal = (int) data_get($metrics, 'users.total', data_get($stats, 'total_users', 0));
    $funnelBase = max(1, $usersTotal ?: $studentsTotal);
    $funnelSteps = [
        ['label' => '1. المستخدمون', 'count' => $usersTotal ?: $studentsTotal, 'pct' => 100, 'opacity' => '0.35', 'accent' => false],
        ['label' => '2. الطلاب', 'count' => $studentsTotal, 'pct' => round(($studentsTotal / $funnelBase) * 100, 1), 'opacity' => '0.45', 'accent' => false],
        ['label' => '3. الكورسات النشطة', 'count' => $coursesTotal, 'pct' => round(($coursesTotal / $funnelBase) * 100, 1), 'opacity' => '0.55', 'accent' => false],
        ['label' => '4. طلبات معلّقة', 'count' => $ordersPending, 'pct' => round(($ordersPending / $funnelBase) * 100, 1), 'opacity' => '0.65', 'accent' => false],
        ['label' => '5. اشتراكات نشطة', 'count' => $enrollmentsTotal, 'pct' => round(($enrollmentsTotal / $funnelBase) * 100, 1), 'opacity' => '1', 'accent' => true],
    ];
    $conversionPct = $usersTotal > 0 ? round(($enrollmentsTotal / max(1, $usersTotal)) * 100, 1) : 0;

    $alertCount = count($quickActions ?? []);
    $pendingInvoicesCount = (int) data_get($metrics, 'pending_invoices.total', data_get($stats, 'pending_invoices', 0));
    $weekTotal = collect($weeklyActivity ?? [])->sum('count');
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">مرحباً، {{ auth()->user()->name }} · نظرة حسب صلاحيات دورك</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">نظرة تشغيلية لليوم</h2>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            @if(! empty($ds['sales_section']) && Route::has('admin.orders.index'))
                <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="btn-press inline-flex h-9 items-center rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    الطلبات المعلّقة@if($ordersPending > 0) ({{ number_format($ordersPending) }})@endif
                </a>
            @endif
            @if(! empty($ds['courses_metric']) && Route::has('admin.courses.create'))
                <a href="{{ route('admin.courses.create') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-canvas">
                    كورس جديد
                </a>
            @endif
        </div>
    </section>

    @if(isset($dashboardShow) && ! $hasAnyDashboardWidget)
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm text-ink shadow-soft">
            لا توجد بطاقات إحصائية لعرضها حالياً. اطلب من المسؤول إسناد الصلاحيات المناسبة لدورك.
        </div>
    @endif

    {{-- KPI — مطابق site: grid gap-3 sm:2 xl:4 --}}
    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @if(! empty($ds['monthly_revenue']) || ! empty($ds['revenue_total']))
            @php
                $revTrend = $trendPct($metrics['monthly_revenue']['trend'] ?? null);
                $bars = $spark($revTrend);
                $revValue = ! empty($ds['monthly_revenue'])
                    ? ($metrics['monthly_revenue']['current'] ?? $stats['monthly_revenue'] ?? 0)
                    : ($stats['total_revenue'] ?? 0);
            @endphp
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-start justify-between gap-3">
                    <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-chart-line text-sm"></i>
                    </div>
                    @if($revTrend !== null)
                        <span class="inline-flex items-center gap-0.5 text-[11px] font-semibold tabular-nums {{ $revTrend >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $revTrend >= 0 ? '+' : '' }}{{ number_format($revTrend, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="mt-3 text-xs text-muted">{{ ! empty($ds['monthly_revenue']) ? 'إيراد هذا الشهر' : 'إجمالي الإيراد' }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format((float) $revValue, 0) }} {{ $currency }}</p>
                <div class="mt-2 flex h-8 items-end gap-0.5">
                    @foreach($bars as $i => $h)
                        <span class="w-2 rounded-t {{ $i === count($bars) - 1 ? 'bg-accent' : 'bg-accent/30' }}" style="height:{{ $h }}%"></span>
                    @endforeach
                </div>
                <p class="mt-1 text-[11px] text-muted">مقارنة بالفترة السابقة</p>
            </article>
        @endif

        @if(! empty($ds['students_metric']))
            @php $st = $metrics['students'] ?? []; $stTrend = $trendPct($st['trend'] ?? null); $bars = $spark($stTrend); @endphp
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-start justify-between gap-3">
                    <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-user-graduate text-sm"></i>
                    </div>
                    @if($stTrend !== null)
                        <span class="inline-flex items-center gap-0.5 text-[11px] font-semibold tabular-nums {{ $stTrend >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $stTrend >= 0 ? '+' : '' }}{{ number_format($stTrend, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="mt-3 text-xs text-muted">الطلاب</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($st['total'] ?? 0) }}</p>
                <div class="mt-2 flex h-8 items-end gap-0.5">
                    @foreach($bars as $i => $h)
                        <span class="w-2 rounded-t {{ $i === count($bars) - 1 ? 'bg-accent' : 'bg-accent/30' }}" style="height:{{ $h }}%"></span>
                    @endforeach
                </div>
                <p class="mt-1 text-[11px] text-muted">هذا الشهر: {{ number_format($st['new_this_month'] ?? 0) }}</p>
            </article>
        @elseif(! empty($ds['users_metric']))
            @php $us = $metrics['users'] ?? []; $usTrend = $trendPct($us['trend'] ?? null); $bars = $spark($usTrend); @endphp
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-start justify-between gap-3">
                    <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-users text-sm"></i>
                    </div>
                    @if($usTrend !== null)
                        <span class="inline-flex items-center gap-0.5 text-[11px] font-semibold tabular-nums {{ $usTrend >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $usTrend >= 0 ? '+' : '' }}{{ number_format($usTrend, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="mt-3 text-xs text-muted">المستخدمون</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($us['total'] ?? 0) }}</p>
                <div class="mt-2 flex h-8 items-end gap-0.5">
                    @foreach($bars as $i => $h)
                        <span class="w-2 rounded-t {{ $i === count($bars) - 1 ? 'bg-accent' : 'bg-accent/30' }}" style="height:{{ $h }}%"></span>
                    @endforeach
                </div>
                <p class="mt-1 text-[11px] text-muted">هذا الشهر: {{ number_format($us['new_this_month'] ?? 0) }}</p>
            </article>
        @endif

        @if(! empty($ds['courses_metric']))
            @php $co = $metrics['courses'] ?? []; $coTrend = $trendPct($co['trend'] ?? null); $bars = $spark($coTrend); @endphp
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-start justify-between gap-3">
                    <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-graduation-cap text-sm"></i>
                    </div>
                    @if($coTrend !== null)
                        <span class="inline-flex items-center gap-0.5 text-[11px] font-semibold tabular-nums {{ $coTrend >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $coTrend >= 0 ? '+' : '' }}{{ number_format($coTrend, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="mt-3 text-xs text-muted">الكورسات</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($co['total'] ?? 0) }}</p>
                <div class="mt-2 flex h-8 items-end gap-0.5">
                    @foreach($bars as $i => $h)
                        <span class="w-2 rounded-t {{ $i === count($bars) - 1 ? 'bg-accent' : 'bg-accent/30' }}" style="height:{{ $h }}%"></span>
                    @endforeach
                </div>
                <p class="mt-1 text-[11px] text-muted">جديد هذا الشهر: {{ number_format($co['new_this_month'] ?? 0) }}</p>
            </article>
        @endif

        @if(! empty($ds['enrollments_metric']))
            @php $en = $metrics['enrollments'] ?? []; $enTrend = $trendPct($en['trend'] ?? null); $bars = $spark($enTrend); @endphp
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-start justify-between gap-3">
                    <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-user-plus text-sm"></i>
                    </div>
                    @if($enTrend !== null)
                        <span class="inline-flex items-center gap-0.5 text-[11px] font-semibold tabular-nums {{ $enTrend >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $enTrend >= 0 ? '+' : '' }}{{ number_format($enTrend, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="mt-3 text-xs text-muted">الاشتراكات النشطة</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($en['total'] ?? 0) }}</p>
                <div class="mt-2 flex h-8 items-end gap-0.5">
                    @foreach($bars as $i => $h)
                        <span class="w-2 rounded-t {{ $i === count($bars) - 1 ? 'bg-accent' : 'bg-accent/30' }}" style="height:{{ $h }}%"></span>
                    @endforeach
                </div>
                <p class="mt-1 text-[11px] text-muted">هذا الشهر: {{ number_format($en['new_this_month'] ?? 0) }}</p>
            </article>
        @elseif(! empty($ds['instructors_metric']))
            @php $ins = $metrics['instructors'] ?? []; $insTrend = $trendPct($ins['trend'] ?? null); $bars = $spark($insTrend); @endphp
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-start justify-between gap-3">
                    <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-chalkboard-teacher text-sm"></i>
                    </div>
                    @if($insTrend !== null)
                        <span class="inline-flex items-center gap-0.5 text-[11px] font-semibold tabular-nums {{ $insTrend >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $insTrend >= 0 ? '+' : '' }}{{ number_format($insTrend, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="mt-3 text-xs text-muted">المدربون</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($ins['total'] ?? 0) }}</p>
                <div class="mt-2 flex h-8 items-end gap-0.5">
                    @foreach($bars as $i => $h)
                        <span class="w-2 rounded-t {{ $i === count($bars) - 1 ? 'bg-accent' : 'bg-accent/30' }}" style="height:{{ $h }}%"></span>
                    @endforeach
                </div>
                <p class="mt-1 text-[11px] text-muted">هذا الشهر: {{ number_format($ins['new_this_month'] ?? 0) }}</p>
            </article>
        @endif
    </section>

    {{-- نشاط + ملخص جانبي — مثل xl:[1.55fr_0.95fr] --}}
    <section class="grid gap-5 xl:grid-cols-[1.55fr_0.95fr]">
        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft md:p-6">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-ink">نشاط المنصة · 7 أيام</h3>
                    <p class="mt-1 text-xs text-muted">سجل النشاط اليومي على الأكاديمية</p>
                </div>
                <div class="text-start">
                    <p class="text-xl font-semibold tabular-nums text-ink">{{ number_format($weekTotal) }}</p>
                    <p class="mt-0.5 text-xs font-semibold text-muted">حدث هذا الأسبوع</p>
                </div>
            </div>
            @if(! empty($ds['activity_feed']) && collect($weeklyActivity ?? [])->isNotEmpty())
                @php
                    $week = collect($weeklyActivity);
                    $max = max(1, (int) $week->max('count'));
                @endphp
                <div class="flex h-36 items-end gap-1.5 sm:gap-2">
                    @foreach($week as $day)
                        @php $h = max(8, (int) round(((int) $day->count / $max) * 100)); @endphp
                        <div class="flex min-w-0 flex-1 flex-col items-center gap-1.5">
                            <span class="w-full max-w-[28px] rounded-t-lg bg-accent/80" style="height:{{ $h }}%" title="{{ $day->count }}"></span>
                            <span class="text-[10px] tabular-nums text-muted">{{ \Illuminate\Support\Carbon::parse($day->date)->format('d') }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex items-center gap-4 text-[11px] text-muted">
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-accent"></span>نشاط يومي</span>
                </div>
            @else
                <div class="flex h-36 items-center justify-center rounded-xl bg-canvas text-sm text-muted">
                    لا توجد بيانات نشاط للعرض
                </div>
            @endif
        </article>

        <div class="grid gap-5">
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-ink">لمحة سريعة</h3>
                        <p class="mt-1 text-xs text-muted">مؤشرات الأكاديمية الآن</p>
                    </div>
                    <i class="fas fa-clock text-sm text-muted"></i>
                </div>
                <ul class="space-y-3 text-sm">
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-muted">طلاب</span>
                        <span class="font-semibold tabular-nums text-ink">{{ number_format($studentsTotal) }}</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-muted">كورسات</span>
                        <span class="font-semibold tabular-nums text-ink">{{ number_format($coursesTotal) }}</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-muted">اشتراكات نشطة</span>
                        <span class="font-semibold tabular-nums text-ink">{{ number_format($enrollmentsTotal) }}</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-muted">طلبات معلّقة</span>
                        <span class="font-semibold tabular-nums text-ink">{{ number_format($ordersPending) }}</span>
                    </li>
                </ul>
            </article>
        </div>
    </section>

    {{-- قمع + تنبيهات — مطابق site lg:[1.2fr_1fr] --}}
    @if($hasAnyDashboardWidget)
    <section class="grid gap-5 lg:grid-cols-[1.2fr_1fr]">
        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft md:p-6">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-ink">قمع الأكاديمية</h3>
                    <p class="mt-1 text-xs text-muted">من المستخدم حتى الاشتراك النشط · نسب نسبية</p>
                </div>
                <span class="rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($conversionPct, 1) }}% تحويل</span>
            </div>
            <div class="space-y-3">
                @foreach($funnelSteps as $step)
                    @php $barW = max(2, min(100, (float) $step['pct'])); @endphp
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                            <span class="font-medium text-ink">{{ $step['label'] }}</span>
                            <span class="shrink-0 tabular-nums text-muted">{{ number_format($step['count']) }} · {{ number_format($step['pct'], 1) }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-[#eef1f5]">
                            <div class="h-full rounded-full {{ $step['accent'] ? 'bg-accent' : 'bg-ink' }}" style="width:{{ $barW }}%;opacity:{{ $step['accent'] ? '1' : $step['opacity'] }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft md:p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-ink">تنبيهات تحتاج إجراء</h3>
                <span class="text-xs text-muted">{{ $alertCount }}</span>
            </div>
            <div class="space-y-3">
                @forelse(($quickActions ?? []) as $action)
                    <a href="{{ $action['route'] }}" class="block rounded-xl border border-line bg-[#f7f8fa] px-4 py-3 transition hover:border-accent/30 hover:bg-accent-soft/40">
                        <div class="flex items-center justify-between gap-3">
                            <p class="min-w-0 truncate text-sm font-semibold text-ink">{{ $action['title'] }}</p>
                            @php
                                $prio = ((int) ($action['count'] ?? 0)) > 0 ? 'عاجل' : 'معلومة';
                                $prioClass = ((int) ($action['count'] ?? 0)) > 0 ? 'bg-metal/15 text-metal' : 'bg-canvas-muted text-muted';
                            @endphp
                            <span class="shrink-0 rounded-lg px-2 py-0.5 text-[10px] font-medium {{ $prioClass }}">{{ $prio }}</span>
                        </div>
                        <p class="mt-1.5 text-xs leading-6 text-muted">{{ $action['meta'] ?? ($action['cta'] ?? '') }} · {{ number_format($action['count'] ?? 0) }}</p>
                    </a>
                @empty
                    <div class="rounded-xl border border-line bg-[#f7f8fa] px-4 py-6 text-center text-sm text-muted">
                        لا توجد تنبيهات عاجلة حالياً
                    </div>
                @endforelse
            </div>
        </article>
    </section>
    @endif

    {{-- طلبات + قوائم جانبية --}}
    <section class="grid gap-5 xl:grid-cols-[1.55fr_0.95fr]">
        @if(! empty($ds['sales_section']))
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-ink">تدفق الطلبات</h3>
                        <p class="mt-0.5 text-xs text-muted">آخر الطلبات مع الحالة والمبلغ</p>
                    </div>
                    @if(Route::has('admin.orders.index'))
                        <a href="{{ route('admin.orders.index') }}" class="btn-press shrink-0 rounded-xl px-3 py-1.5 text-sm text-accent hover:bg-accent-soft">فتح الطابور</a>
                    @endif
                </div>
                <div class="admin-table-wrap">
                    <table class="w-full min-w-[740px] text-right text-sm">
                        <thead class="bg-[#f7f8fa] text-[11px] uppercase tracking-wide text-muted">
                            <tr>
                                <th class="px-5 py-3 font-medium">الطلب</th>
                                <th class="px-3 py-3 font-medium">الطالب</th>
                                <th class="px-3 py-3 font-medium">الكورس</th>
                                <th class="px-3 py-3 font-medium">الحالة</th>
                                <th class="px-3 py-3 font-medium">المبلغ</th>
                                <th class="px-5 py-3 font-medium">الوقت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                @php
                                    $status = (string) ($order->status ?? '');
                                    $badgeClass = match ($status) {
                                        'approved', 'completed' => 'bg-accent-soft text-accent',
                                        'pending' => 'bg-metal/15 text-metal',
                                        'rejected', 'cancelled' => 'bg-danger/10 text-danger',
                                        default => 'bg-canvas-muted text-ink-soft',
                                    };
                                    $statusLabel = match ($status) {
                                        'approved' => 'موافق عليه',
                                        'pending' => 'معلّق',
                                        'rejected' => 'مرفوض',
                                        'cancelled' => 'ملغى',
                                        'completed' => 'مكتمل',
                                        default => $status ?: '—',
                                    };
                                @endphp
                                <tr class="border-t border-line/70 transition hover:bg-[#f9fafb]">
                                    <td class="px-5 py-3.5">
                                        @if(Route::has('admin.orders.show'))
                                            <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold tabular-nums text-ink hover:text-accent">#{{ $order->id }}</a>
                                        @else
                                            <span class="font-semibold tabular-nums text-ink">#{{ $order->id }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3.5 text-ink-soft">{{ $order->user->name ?? '—' }}</td>
                                    <td class="px-3 py-3.5 text-muted">{{ \Illuminate\Support\Str::limit($order->course->title ?? '—', 28) }}</td>
                                    <td class="px-3 py-3.5"><span class="rounded-lg px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                                    <td class="px-3 py-3.5 font-semibold tabular-nums text-ink">{{ number_format((float) ($order->amount ?? 0), 0) }} {{ $currency }}</td>
                                    <td class="px-5 py-3.5 text-xs text-muted">{{ optional($order->created_at)->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm text-muted">لا توجد طلبات بعد</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @else
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft md:p-6">
                <h3 class="text-base font-semibold text-ink">ملخص سريع</h3>
                <p class="mt-2 text-sm leading-7 text-muted">لا تملك صلاحية عرض قسم المبيعات. تُعرض الأقسام الأخرى حسب دورك.</p>
            </article>
        @endif

        <div class="space-y-5">
            @if(! empty($ds['recent_courses']) && $recent_courses)
                <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                    <div class="mb-4 flex items-center justify-between gap-2">
                        <h3 class="text-base font-semibold text-ink">أحدث الكورسات</h3>
                        @if(Route::has('admin.courses.index'))
                            <a href="{{ route('admin.courses.index') }}" class="text-xs font-medium text-accent hover:underline">الكل</a>
                        @endif
                    </div>
                    <ul class="space-y-2.5">
                        @foreach($recent_courses->take(5) as $i => $course)
                            <li class="flex items-center gap-3 rounded-xl border border-transparent px-2 py-2 hover:border-line hover:bg-[#f7f8fa]">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-[#eef1f5] text-[11px] font-bold tabular-nums text-ink">{{ $i + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-ink">{{ $course->title }}</p>
                                    <p class="text-[11px] text-muted">{{ $course->academicSubject->name ?? '—' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endif

            @if(! empty($ds['recent_users']) && $recent_users)
                <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                    <div class="mb-4 flex items-center justify-between gap-2">
                        <h3 class="text-base font-semibold text-ink">أحدث المستخدمين</h3>
                        @if(Route::has('admin.users.index'))
                            <a href="{{ route('admin.users.index') }}" class="text-xs font-medium text-accent hover:underline">الكل</a>
                        @endif
                    </div>
                    <ul class="space-y-2.5">
                        @foreach($recent_users->take(5) as $user)
                            <li class="flex items-center gap-3 rounded-xl border border-transparent px-2 py-2 hover:border-line hover:bg-[#f7f8fa]">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-semibold text-accent">{{ mb_substr($user->name ?? '؟', 0, 1) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-ink">{{ $user->name }}</p>
                                    <p class="truncate text-[11px] text-muted">{{ $user->email ?? $user->phone }}</p>
                                </div>
                                <span class="shrink-0 rounded-lg bg-canvas px-2 py-0.5 text-[10px] text-muted">{{ $user->role }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endif

            @if(! empty($ds['invoices_panel']) && isset($pending_invoices) && $pending_invoices->isNotEmpty())
                <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                    <div class="mb-4 flex items-center justify-between gap-2">
                        <h3 class="text-base font-semibold text-ink">فواتير معلّقة</h3>
                        @if(Route::has('admin.invoices.index'))
                            <a href="{{ route('admin.invoices.index', ['status' => 'pending']) }}" class="text-xs font-medium text-accent hover:underline">الكل</a>
                        @endif
                    </div>
                    <ul class="space-y-2.5">
                        @foreach($pending_invoices->take(4) as $invoice)
                            <li class="flex items-center justify-between gap-3 rounded-xl bg-[#f7f8fa] px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-ink">{{ $invoice->user->name ?? '—' }}</p>
                                    <p class="text-[11px] text-muted">#{{ $invoice->id }}</p>
                                </div>
                                <span class="shrink-0 text-sm font-semibold tabular-nums text-ink">{{ number_format((float) ($invoice->total_amount ?? 0), 0) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endif
        </div>
    </section>

    @if(! empty($ds['payments_panel']) && isset($recent_payments) && $recent_payments->isNotEmpty())
        <section class="rounded-2xl border border-line bg-surface p-5 shadow-soft md:p-6">
            <div class="mb-4 flex items-center justify-between gap-2">
                <h3 class="text-base font-semibold text-ink">آخر المدفوعات</h3>
                @if(Route::has('admin.payments.index'))
                    <a href="{{ route('admin.payments.index') }}" class="text-xs font-medium text-accent hover:underline">عرض الكل</a>
                @endif
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                @foreach($recent_payments->take(5) as $payment)
                    <div class="min-w-0 rounded-xl border border-line bg-canvas px-4 py-3">
                        <p class="truncate text-sm font-semibold text-ink">{{ $payment->user->name ?? '—' }}</p>
                        <p class="mt-1 text-lg font-semibold tabular-nums text-accent">{{ number_format((float) ($payment->amount ?? 0), 0) }} <span class="text-xs font-normal text-muted">{{ $currency }}</span></p>
                        <p class="mt-1 text-[11px] text-muted">{{ optional($payment->created_at)->diffForHumans() }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- شريط الحالة — مطابق site bg-ink strip --}}
    <section class="admin-status-strip rounded-2xl border border-line bg-ink p-4 text-white">
        <div class="px-2 py-1">
            <p class="text-[11px] text-white/45">طلبات معلّقة</p>
            <p class="mt-1 text-sm font-semibold tabular-nums">{{ number_format($ordersPending) }}</p>
        </div>
        <div class="px-2 py-1">
            <p class="text-[11px] text-white/45">فواتير معلّقة</p>
            <p class="mt-1 text-sm font-semibold tabular-nums">{{ number_format($pendingInvoicesCount) }}</p>
        </div>
        <div class="px-2 py-1">
            <p class="text-[11px] text-white/45">اشتراكات نشطة</p>
            <p class="mt-1 text-sm font-semibold tabular-nums">{{ number_format($enrollmentsTotal) }}</p>
        </div>
        <div class="px-2 py-1">
            <p class="text-[11px] text-white/45">نشاط 7 أيام</p>
            <p class="mt-1 text-sm font-semibold tabular-nums">{{ number_format($weekTotal) }} حدث</p>
        </div>
    </section>
</div>
@endsection
