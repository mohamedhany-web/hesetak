@extends('layouts.admin')

@section('title', 'اتفاقيات التقسيط')
@section('page_title', 'اتفاقيات التقسيط')

@section('content')
@php
    $agreements = $agreements ?? collect();
    $total = $agreements->total() ?? $agreements->count();
    $activeCount = ($agreements instanceof \Illuminate\Pagination\LengthAwarePaginator)
        ? $agreements->where('status', \App\Models\InstallmentAgreement::STATUS_ACTIVE)->count()
        : $agreements->where('status', \App\Models\InstallmentAgreement::STATUS_ACTIVE)->count();
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $statusBadgeMap = [
        \App\Models\InstallmentAgreement::STATUS_ACTIVE => 'bg-accent-soft text-accent',
        \App\Models\InstallmentAgreement::STATUS_OVERDUE => 'bg-metal/15 text-metal',
        \App\Models\InstallmentAgreement::STATUS_COMPLETED => 'bg-accent-soft text-accent',
        \App\Models\InstallmentAgreement::STATUS_CANCELLED => 'bg-canvas-muted text-muted',
    ];
    $kpis = [
        ['label' => 'اتفاقيات نشطة', 'value' => number_format($agreements->where('status', \App\Models\InstallmentAgreement::STATUS_ACTIVE)->count()), 'icon' => 'fa-bolt', 'tone' => 'accent', 'note' => 'الانتقالات الحالية التي تتطلب متابعة دورية'],
        ['label' => 'إجمالي المبالغ الممولة', 'value' => number_format($agreements->sum('total_amount'), 2) . ' ' . currency_symbol(), 'icon' => 'fa-coins', 'tone' => 'metal', 'note' => 'القيمة الإجمالية التي تغطيها جميع الاتفاقيات'],
        ['label' => 'دفعات مقدمة', 'value' => number_format($agreements->sum('deposit_amount'), 2) . ' ' . currency_symbol(), 'icon' => 'fa-hand-holding-usd', 'tone' => 'accent', 'note' => 'إجمالي المبالغ المحصلة كدفعات مقدمة'],
        ['label' => 'اتفاقيات متأخرة', 'value' => number_format($agreements->where('status', \App\Models\InstallmentAgreement::STATUS_OVERDUE)->count()), 'icon' => 'fa-exclamation-circle', 'tone' => 'muted', 'note' => 'الاتفاقيات التي تحتاج تدخلاً بسبب تأخر السداد'],
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
            <p class="text-xs font-medium text-muted">المالية · التقسيط</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة اتفاقيات التقسيط</h2>
            <p class="mt-1 text-sm text-muted">راقب اتفاقيات التقسيط للطلاب، حالات السداد، والمبالغ المتبقية لتضمن متابعة دقيقة لكل خطة</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.installments.plans.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-layer-group text-xs"></i>
                إدارة الخطط
            </a>
            <a href="{{ route('admin.installments.agreements.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                إنشاء اتفاقية جديدة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

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

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">تصفية الاتفاقيات حسب الحالة</p>
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-4 p-4 sm:p-5">
            <div class="min-w-[200px] flex-1">
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">كل الحالات</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-filter text-xs"></i>
                تصفية
            </button>
            @if(request()->filled('status'))
                <a href="{{ route('admin.installments.agreements.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                    <i class="fas fa-times text-xs"></i>
                    مسح
                </a>
            @endif
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">جميع الاتفاقيات</h3>
                <p class="mt-0.5 text-xs text-muted">تتبع حالة الطلاب، المبالغ المتبقية، وجداول السداد</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($total) }} اتفاقية</span>
        </div>

        @if($agreements->count())
            <div class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2">
                @foreach($agreements as $agreement)
                    @php
                        $badgeClasses = $statusBadgeMap[$agreement->status] ?? 'bg-canvas-muted text-muted';
                    @endphp
                    <div class="flex flex-col gap-4 rounded-2xl border border-line bg-canvas/30 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink">{{ $agreement->student->name ?? 'طالب غير معروف' }}</p>
                                <p class="mt-1 text-xs text-accent">{{ $agreement->course->title ?? 'خطة عامة' }}</p>
                                <p class="mt-0.5 text-[11px] text-muted">بداية {{ optional($agreement->start_date)->format('Y-m-d') }}</p>
                            </div>
                            <span class="inline-flex shrink-0 rounded-lg px-2.5 py-1 text-xs font-medium {{ $badgeClasses }}">
                                {{ $statuses[$agreement->status] ?? $agreement->status }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-[11px] font-medium uppercase text-muted">إجمالي الاتفاقية</p>
                                <p class="mt-1 font-semibold tabular-nums text-ink">{{ number_format($agreement->total_amount ?? 0, 2) }} <span class="text-xs font-normal text-muted">$</span></p>
                            </div>
                            <div>
                                <p class="text-[11px] font-medium uppercase text-muted">دفعة مقدمة</p>
                                <p class="mt-1 font-semibold tabular-nums text-ink">{{ number_format($agreement->deposit_amount ?? 0, 2) }} <span class="text-xs font-normal text-muted">$</span></p>
                            </div>
                            <div>
                                <p class="text-[11px] font-medium uppercase text-muted">عدد الأقساط</p>
                                <p class="mt-1 font-medium text-ink">{{ $agreement->installments_count }} دفعة</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-medium uppercase text-muted">القسط التالي</p>
                                <p class="mt-1 font-medium tabular-nums text-ink">
                                    {{ optional($agreement->payments->where('status', \App\Models\InstallmentPayment::STATUS_PENDING)->sortBy('due_date')->first())->due_date?->format('Y-m-d') ?? '—' }}
                                </p>
                            </div>
                        </div>

                        @if($agreement->notes)
                            <p class="text-xs leading-relaxed text-muted">{{ $agreement->notes }}</p>
                        @endif

                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.installments.agreements.show', $agreement) }}" class="btn-press inline-flex h-8 flex-1 items-center justify-center gap-2 rounded-lg bg-accent px-3 text-xs font-medium text-white">
                                <i class="fas fa-eye"></i>
                                عرض التفاصيل
                            </a>
                            <a href="{{ route('admin.installments.agreements.edit', $agreement) }}" class="btn-press inline-flex size-8 items-center justify-center rounded-lg border border-line text-ink transition hover:border-accent/30 hover:text-accent" title="تعديل">
                                <i class="fas fa-edit text-xs"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($agreements instanceof \Illuminate\Pagination\LengthAwarePaginator && $agreements->hasPages())
                <div class="border-t border-line px-4 py-4 sm:px-5">{{ $agreements->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="px-4 py-16 text-center sm:px-5">
                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                    <i class="fas fa-folder-open"></i>
                </div>
                <p class="text-sm font-medium text-ink">لا توجد أي اتفاقيات تقسيط بعد</p>
                <p class="mt-1 text-xs text-muted">ابدأ بإضافة أول اتفاقية لربط الطلاب بخطط التقسيط المتاحة.</p>
                <a href="{{ route('admin.installments.agreements.create') }}" class="btn-press mt-4 inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-plus text-xs"></i>
                    إنشاء اتفاقية جديدة
                </a>
            </div>
        @endif
    </article>
</div>
@endsection
