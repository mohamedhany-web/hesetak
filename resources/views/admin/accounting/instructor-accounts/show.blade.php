@extends('layouts.admin')

@section('title', 'حساب المدرب - ' . $instructor->name)
@section('page_title', 'حساب المدرب - ' . $instructor->name)
@section('header', 'حساب المدرب - ' . $instructor->name)

@section('content')
@php
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $agreementStatusBadges = [
        'active' => ['label' => 'نشط', 'classes' => 'bg-accent-soft text-accent'],
        'draft' => ['label' => 'مسودة', 'classes' => 'bg-canvas-muted text-muted'],
        'completed' => ['label' => 'مكتمل', 'classes' => 'bg-accent-soft text-accent'],
    ];
    $paymentStatusBadges = [
        'pending' => ['label' => 'قيد المراجعة', 'classes' => 'bg-canvas-muted text-muted'],
        'approved' => ['label' => 'موافق عليه', 'classes' => 'bg-metal/15 text-metal'],
        'paid' => ['label' => 'مدفوع', 'classes' => 'bg-accent-soft text-accent'],
    ];
    $kpis = [
        ['label' => 'مطلوب الدفع', 'value' => number_format($totals['pending'], 2), 'suffix' => currency_symbol(), 'icon' => 'fa-hourglass-half', 'tone' => 'metal'],
        ['label' => 'تم الدفع (إجمالي)', 'value' => number_format($totals['paid'], 2), 'suffix' => currency_symbol(), 'icon' => 'fa-check-circle', 'tone' => 'accent'],
        ['label' => 'من تفعيلات الطلاب (نسبة الكورس)', 'value' => number_format($totals['from_activations'], 2), 'suffix' => currency_symbol(), 'icon' => 'fa-graduation-cap', 'tone' => 'accent'],
        ['label' => 'من الاستشارات', 'value' => number_format($totals['from_consultations'], 2), 'suffix' => currency_symbol(), 'icon' => 'fa-comments', 'tone' => 'muted'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · حسابات المدربين · {{ $instructor->name }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $instructor->name }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ $instructor->email ?? '' }}
                @if($instructor->phone) — {{ $instructor->phone }} @endif
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.accounting.instructor-accounts.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة إلى حسابات المدربين
            </a>
            <a href="{{ route('admin.salaries.instructor', $instructor) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-money-bill-wave text-xs"></i>
                الماليات والدفع
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
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">
                    {{ $kpi['value'] }}
                    <span class="text-sm font-normal text-muted">{{ $kpi['suffix'] }}</span>
                </p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">الاتفاقيات</h3>
            <p class="mt-0.5 text-xs text-muted">جميع اتفاقيات هذا المدرب (بما فيها نسبة من الكورس)</p>
        </div>

        @if($agreements->count() > 0)
            <div class="admin-table-wrap overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">رقم الاتفاقية</th>
                            <th class="px-4 py-3 text-start font-medium">العنوان</th>
                            <th class="px-4 py-3 text-start font-medium">النوع</th>
                            <th class="px-4 py-3 text-start font-medium">المبلغ / النسبة</th>
                            <th class="px-4 py-3 text-start font-medium">من - إلى</th>
                            <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($agreements as $agr)
                            @php $agrBadge = $agreementStatusBadges[$agr->status] ?? ['label' => $agr->status ?? '—', 'classes' => 'bg-canvas-muted text-muted']; @endphp
                            <tr class="hover:bg-canvas/40">
                                <td class="px-4 py-3 font-mono text-xs text-muted">{{ $agr->agreement_number ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium text-ink">{{ $agr->title ?? '—' }}</td>
                                <td class="px-4 py-3 text-ink-soft">
                                    @if(($agr->billing_type ?? '') === 'course_percentage')
                                        <span class="inline-flex rounded-lg bg-accent-soft px-2 py-1 text-xs font-medium text-accent">نسبة من الكورس</span>
                                        @if($agr->advancedCourse)
                                            <span class="mt-1 block text-[11px] text-muted">{{ $agr->advancedCourse->title }}</span>
                                        @endif
                                    @else
                                        {{ $agr->type_label ?? $agr->type }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-ink">
                                    @if(($agr->billing_type ?? '') === 'course_percentage')
                                        {{ number_format($agr->course_percentage ?? 0, 2) }}%
                                    @else
                                        {{ number_format((float) ($agr->rate ?? 0), 2) }} <span class="text-xs font-normal text-muted">$</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs tabular-nums text-muted">
                                    {{ $agr->start_date ? $agr->start_date->format('Y-m-d') : '—' }}
                                    @if($agr->end_date) — {{ $agr->end_date->format('Y-m-d') }} @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $agrBadge['classes'] }}">{{ $agrBadge['label'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-12 text-center text-sm text-muted">لا توجد اتفاقيات لهذا المدرب.</div>
        @endif
    </article>

    @if($activationPayments->isNotEmpty())
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">تفعيلات الطلاب (نسبة من الكورس)</h3>
                <p class="mt-0.5 text-xs text-muted">كل تفعيل لطالب في كورس مربوط باتفاقية نسبة — نسبة المدرب وحصته من مبلغ الشراء</p>
            </div>
            <div class="admin-table-wrap overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                            <th class="px-4 py-3 text-start font-medium">الطالب</th>
                            <th class="px-4 py-3 text-start font-medium">الكورس</th>
                            <th class="px-4 py-3 text-start font-medium">مبلغ الشراء ($)</th>
                            <th class="px-4 py-3 text-start font-medium">نسبة المدرب</th>
                            <th class="px-4 py-3 text-start font-medium">حصة المدرب ($)</th>
                            <th class="px-4 py-3 text-start font-medium">حالة الدفع</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($activationPayments as $p)
                            @php $actBadge = $paymentStatusBadges[$p->status] ?? ['label' => $p->status, 'classes' => 'bg-canvas-muted text-muted']; @endphp
                            <tr class="hover:bg-canvas/40">
                                <td class="px-4 py-3 text-xs tabular-nums text-muted">{{ $p->created_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium text-ink">{{ $p->enrollment?->student?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $p->course?->title ?? '—' }}</td>
                                <td class="px-4 py-3 tabular-nums text-ink-soft">{{ $p->enrollment ? number_format($p->enrollment->final_price ?? 0, 2) : '—' }}</td>
                                <td class="px-4 py-3 text-ink-soft">
                                    @if($p->agreement)
                                        {{ number_format($p->agreement->course_percentage ?? 0, 2) }}%
                                    @else — @endif
                                </td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-accent">{{ number_format($p->amount, 2) }} <span class="text-xs font-normal text-muted">$</span></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $actBadge['classes'] }}">{{ $actBadge['label'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-line bg-canvas/40 px-4 py-4 text-start sm:px-5">
                <span class="text-sm font-semibold tabular-nums text-ink">إجمالي أرباح التفعيلات: {{ number_format($activationPayments->sum('amount'), 2) }} $</span>
            </div>
        </article>
    @endif

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">سجل المدفوعات</h3>
                <p class="mt-0.5 text-xs text-muted">جميع المدفوعات (قيد المراجعة، موافق عليه، مدفوع)</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($payments->count()) }} مدفوعة</span>
        </div>

        @if($payments->count() > 0)
            <div class="admin-table-wrap overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">رقم المدفوعة</th>
                            <th class="px-4 py-3 text-start font-medium">الاتفاقية / الوصف</th>
                            <th class="px-4 py-3 text-start font-medium">النوع</th>
                            <th class="px-4 py-3 text-start font-medium">المبلغ</th>
                            <th class="px-4 py-3 text-start font-medium">الحالة</th>
                            <th class="px-4 py-3 text-start font-medium">إجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($payments as $p)
                            @php $pBadge = $paymentStatusBadges[$p->status] ?? ['label' => $p->status, 'classes' => 'bg-canvas-muted text-muted']; @endphp
                            <tr class="hover:bg-canvas/40">
                                <td class="px-4 py-3 font-mono text-xs text-muted">{{ $p->payment_number }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $p->agreement->title ?? $p->description ?? '—' }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $p->type_label ?? $p->type }}</td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-ink">{{ number_format($p->amount, 2) }} <span class="text-xs font-normal text-muted">$</span></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $pBadge['classes'] }}">{{ $pBadge['label'] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($p->status === 'approved')
                                        <a href="{{ route('admin.salaries.pay', $p) }}" class="btn-press inline-flex h-8 items-center gap-1 rounded-lg bg-accent px-3 text-xs font-medium text-white">دفع وتحويل</a>
                                    @elseif($p->status === 'paid' && $p->transfer_receipt_path)
                                        <a href="{{ storage_asset($p->transfer_receipt_path) }}" target="_blank" class="btn-press inline-flex h-8 items-center gap-1 rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent">إيصال</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-12 text-center text-sm text-muted">لا توجد مدفوعات مسجّلة.</div>
        @endif
    </article>
</div>
@endsection
