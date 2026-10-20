@extends('layouts.admin')

@section('title', 'المصروفات - التقارير المحاسبية')
@section('page_title', 'المصروفات')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $statusBadges = [
        'approved' => ['label' => 'معتمد', 'classes' => 'bg-accent-soft text-accent'],
        'pending' => ['label' => 'معلق', 'classes' => 'bg-metal/15 text-metal'],
        'rejected' => ['label' => 'مرفوض', 'classes' => 'bg-canvas-muted text-muted'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.accounting.reports') }}" class="transition hover:text-accent">التقارير المحاسبية</a>
                <span class="mx-1">/</span>
                المصروفات
            </p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">المصروفات في الفترة</h2>
            <p class="mt-1 text-sm text-muted">من {{ $startDate->format('Y-m-d') }} إلى {{ $endDate->format('Y-m-d') }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.accounting.reports.export', array_merge(request()->query(), ['type' => 'expenses'])) }}"
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
        <form method="GET" action="{{ route('admin.accounting.reports.expenses') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
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

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">قائمة المصروفات</h3>
                <p class="mt-0.5 text-xs text-muted">من الأحدث إلى الأقدم</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($items->total()) }} مصروف</span>
        </div>

        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">رقم المصروف</th>
                        <th class="px-4 py-3 text-start font-medium">العنوان</th>
                        <th class="px-4 py-3 text-start font-medium">الفئة</th>
                        <th class="px-4 py-3 text-start font-medium">المبلغ</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($items as $expense)
                        @php $badge = $statusBadges[$expense->status] ?? ['label' => $expense->status, 'classes' => 'bg-canvas-muted text-muted']; @endphp
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.expenses.show', $expense) }}" class="font-semibold text-accent hover:text-accent">{{ $expense->expense_number ?? '—' }}</a>
                            </td>
                            <td class="px-4 py-3 text-ink-soft">{{ $expense->title ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ \App\Models\Expense::categoryLabel($expense->category ?? '') }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums text-ink">
                                {{ number_format($expense->amount, 2) }}
                                <span class="text-xs font-normal text-muted">{{ currency_symbol() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $badge['classes'] }}">{{ $badge['label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs tabular-nums text-muted">{{ $expense->expense_date ? $expense->expense_date->format('Y-m-d') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا توجد مصروفات</p>
                                <p class="mt-1 text-xs text-muted">لا توجد مصروفات في هذه الفترة.</p>
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
