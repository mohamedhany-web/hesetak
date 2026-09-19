@extends('layouts.admin')

@section('title', 'إدارة المصروفات')
@section('page_title', 'إدارة المصروفات')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $categoryLabels = [
        'operational' => 'تشغيلي',
        'marketing' => 'تسويق',
        'salaries' => 'رواتب',
        'utilities' => 'مرافق',
        'equipment' => 'معدات',
        'maintenance' => 'صيانة',
        'other' => 'أخرى',
    ];
    $statusBadges = [
        'pending' => ['label' => 'قيد المراجعة', 'classes' => 'bg-metal/15 text-metal'],
        'approved' => ['label' => 'موافق عليها', 'classes' => 'bg-accent-soft text-accent'],
        'rejected' => ['label' => 'مرفوضة', 'classes' => 'bg-canvas-muted text-muted'],
    ];
    $kpis = isset($stats) ? [
        ['label' => 'إجمالي المصروفات', 'value' => $stats['total'], 'icon' => 'fa-receipt', 'tone' => 'accent', 'note' => 'كل المصروفات المسجلة'],
        ['label' => 'قيد المراجعة', 'value' => $stats['pending'], 'icon' => 'fa-hourglass-half', 'tone' => 'metal', 'note' => 'بانتظار الموافقة أو الرفض'],
        ['label' => 'موافق عليها', 'value' => $stats['approved'], 'icon' => 'fa-check-circle', 'tone' => 'accent', 'note' => 'تمت الموافقة عليها'],
        ['label' => 'إجمالي المبلغ', 'value' => number_format($stats['total_amount'], 2), 'icon' => 'fa-money-bill-wave', 'tone' => 'muted', 'note' => 'المبالغ المعتمدة (' . currency_symbol() . ')', 'suffix' => ' ' . currency_symbol()],
    ] : [];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الحسابات · المصروفات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة المصروفات</h2>
            <p class="mt-1 text-sm text-muted">إدارة وتتبع جميع المصروفات والمدفوعات</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.expenses.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                مصروف جديد
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(isset($stats))
    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ $kpi['value'] }}@if(!empty($kpi['suffix']))<span class="text-sm font-normal text-muted">{{ $kpi['suffix'] }}</span>@endif</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>
    @endif

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">حسب الحالة، الفئة، أو بيانات المصروف</p>
        </div>
        <form method="GET" action="{{ route('admin.expenses.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
            <div class="xl:col-span-2">
                <label class="{{ $labelClass }}" for="search">{{ __('البحث') }}</label>
                <input id="search" type="search" name="search" value="{{ old('search', request('search')) }}" maxlength="255" placeholder="{{ __('رقم المصروف، العنوان، المرجع...') }}" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">{{ __('الحالة') }}</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">{{ __('جميع الحالات') }}</option>
                    <option value="pending" @selected(request('status') === 'pending')>{{ __('قيد المراجعة') }}</option>
                    <option value="approved" @selected(request('status') === 'approved')>{{ __('موافق عليها') }}</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>{{ __('مرفوضة') }}</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="category">{{ __('الفئة') }}</label>
                <select id="category" name="category" class="{{ $fieldClass }}">
                    <option value="">{{ __('جميع الفئات') }}</option>
                    @foreach($categoryLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-2 xl:col-span-4">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    {{ __('بحث') }}
                </button>
                @if(request()->anyFilled(['search', 'status', 'category']))
                    <a href="{{ route('admin.expenses.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
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
                <h3 class="text-base font-semibold text-ink">قائمة المصروفات</h3>
                <p class="mt-0.5 text-xs text-muted">من الأحدث إلى الأقدم</p>
            </div>
            @if(isset($expenses))
                <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($expenses->total()) }} مصروف</span>
            @endif
        </div>

        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">{{ __('رقم المصروف') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('العنوان') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('الفئة') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('المبلغ') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('التاريخ') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('الحالة') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('الإجراءات') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($expenses ?? [] as $expense)
                        @php $badge = $statusBadges[$expense->status] ?? null; @endphp
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3 font-medium tabular-nums text-ink">{{ $expense->expense_number }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $expense->title }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $expense->category_label }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums text-ink">{{ number_format($expense->amount, 2) }} <span class="text-xs font-normal text-muted">$</span></td>
                            <td class="px-4 py-3 text-xs tabular-nums text-muted">{{ $expense->expense_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                @if($badge)
                                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $badge['classes'] }}">{{ $expense->status_text }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <a href="{{ route('admin.expenses.show', $expense) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent" title="{{ __('عرض') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($expense->status == 'pending')
                                        <form action="{{ route('admin.expenses.approve', $expense) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-accent hover:border-accent/40" title="{{ __('موافقة') }}" onclick="return confirm('هل أنت متأكد من الموافقة على هذا المصروف؟')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.expenses.reject', $expense) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-rose-600 hover:border-rose-300 hover:bg-rose-50" title="{{ __('رفض') }}" onclick="return confirm('هل أنت متأكد من رفض هذا المصروف؟')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">{{ __('لا توجد مصروفات') }}</p>
                                <p class="mt-1 text-xs text-muted">لا توجد نتائج مطابقة للفلاتر الحالية.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($expenses) && $expenses->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $expenses->appends(request()->query())->links() }}</div>
        @endif
    </article>

    @if(isset($stats))
    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-3">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal">
                <i class="fas fa-hourglass-half text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">مبالغ قيد المراجعة</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($stats['pending_amount'], 2) }} <span class="text-sm font-normal text-muted">$</span></p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                <i class="fas fa-calendar text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">مصروفات هذا الشهر</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($stats['this_month'], 2) }} <span class="text-sm font-normal text-muted">$</span></p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-canvas-muted text-muted">
                <i class="fas fa-ban text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">مرفوضة</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($stats['rejected']) }}</p>
        </article>
    </section>
    @endif
</div>
@endsection
