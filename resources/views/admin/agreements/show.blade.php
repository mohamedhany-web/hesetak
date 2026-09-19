@extends('layouts.admin')

@section('title', 'تفاصيل الاتفاقية - ' . config('app.name'))
@section('page_title', 'تفاصيل الاتفاقية')

@section('content')
@php
    $statusBadge = match ($agreement->status) {
        'active' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
        'draft' => 'border-line bg-canvas text-muted',
        'suspended' => 'border-amber-100 bg-amber-50 text-amber-800',
        'terminated' => 'border-rose-100 bg-rose-50 text-rose-700',
        'completed' => 'border-line bg-accent-soft text-accent',
        default => 'border-line bg-canvas text-muted',
    };

    $paymentStatusBadge = fn ($status) => match ($status) {
        'paid' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
        'approved' => 'border-amber-100 bg-amber-50 text-amber-800',
        default => 'border-line bg-canvas text-muted',
    };

    $kpiCards = [
        ['label' => 'إجمالي المدفوعات', 'value' => number_format($stats['total_earned'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-money-bill-wave', 'tone' => 'ink'],
        ['label' => 'معلق', 'value' => number_format($stats['pending_amount'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-clock', 'tone' => 'amber'],
        ['label' => 'إجمالي الدفعات', 'value' => number_format($stats['total_payments']), 'icon' => 'fa-receipt', 'tone' => 'ink'],
        ['label' => 'مدفوع', 'value' => number_format($stats['paid_payments']), 'icon' => 'fa-check-circle', 'tone' => 'emerald'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الماليات · عقود المدربين</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $agreement->title }}</h2>
            <p class="mt-1 text-sm text-muted">
                رقم الاتفاقية: <span class="font-semibold tabular-nums text-ink">{{ $agreement->agreement_number }}</span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.agreements.edit', $agreement) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-pen text-xs"></i>
                تعديل
            </a>
            <a href="{{ route('admin.agreements.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع
            </a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($kpiCards as $card)
            @php
                $valueClass = match ($card['tone']) {
                    'amber' => 'text-amber-700',
                    'emerald' => 'text-emerald-700',
                    default => 'text-ink',
                };
            @endphp
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas {{ $card['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">{{ $card['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight {{ $valueClass }}">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">معلومات الاتفاقية</h3>
                    <p class="mt-0.5 text-xs text-muted">تفاصيل العقد والمدرب</p>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium text-muted">المدرب</p>
                            <p class="mt-1 text-sm font-semibold text-ink">{{ $agreement->instructor->name }}</p>
                            <p class="text-xs text-muted tabular-nums">{{ $agreement->instructor->phone }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted">نوع الاتفاقية</p>
                            <p class="mt-1 text-sm font-semibold text-ink">{{ $agreement->type_label }}</p>
                        </div>
                        @if(($agreement->billing_type ?? '') === 'course_percentage')
                            <div>
                                <p class="text-xs font-medium text-muted">الكورس الأونلاين</p>
                                <p class="mt-1 text-sm font-semibold text-ink">{{ $agreement->advancedCourse?->title ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted">نسبة المدرب</p>
                                <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ number_format($agreement->course_percentage ?? 0, 2) }}%</p>
                            </div>
                        @else
                            <div>
                                <p class="text-xs font-medium text-muted">السعر/المعدل</p>
                                <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ number_format($agreement->rate, 2) }} $</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs font-medium text-muted">الحالة</p>
                            <p class="mt-1">
                                <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $statusBadge }}">
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                    {{ $agreement->status_label }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted">تاريخ البدء</p>
                            <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ $agreement->start_date->format('Y-m-d') }}</p>
                        </div>
                        @if($agreement->end_date)
                            <div>
                                <p class="text-xs font-medium text-muted">تاريخ الانتهاء</p>
                                <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ $agreement->end_date->format('Y-m-d') }}</p>
                            </div>
                        @endif
                    </div>
                    @if($agreement->description)
                        <div>
                            <p class="text-xs font-medium text-muted">الوصف</p>
                            <p class="mt-1 text-sm text-ink">{{ $agreement->description }}</p>
                        </div>
                    @endif
                    @if($agreement->terms)
                        <div>
                            <p class="text-xs font-medium text-muted">شروط العقد</p>
                            <div class="mt-1 whitespace-pre-line text-sm text-ink">{{ $agreement->terms }}</div>
                        </div>
                    @endif
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">سجل المدفوعات</h3>
                    <p class="mt-0.5 text-xs text-muted">جميع الدفعات المرتبطة بهذه الاتفاقية</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-canvas text-xs text-muted">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium">رقم الدفعة</th>
                                <th class="px-4 py-3 text-start font-medium">النوع</th>
                                <th class="px-4 py-3 text-start font-medium">المبلغ</th>
                                <th class="px-4 py-3 text-start font-medium">الحالة</th>
                                <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($agreement->payments as $payment)
                                <tr class="hover:bg-canvas/60">
                                    <td class="px-4 py-3 font-medium tabular-nums text-ink">{{ $payment->payment_number }}</td>
                                    <td class="px-4 py-3">
                                        <p class="text-ink">{{ $payment->type_label ?? $payment->type }}</p>
                                        @if($payment->type === 'course_activation' && $payment->enrollment)
                                            <p class="mt-0.5 text-xs text-muted">الطالب: {{ $payment->enrollment->student->name ?? '—' }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-semibold tabular-nums text-ink">{{ number_format($payment->amount, 2) }} $</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $paymentStatusBadge($payment->status) }}">
                                            <span class="size-1.5 rounded-full bg-current"></span>
                                            {{ $payment->status_label ?? $payment->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs tabular-nums text-muted">{{ $payment->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-sm text-muted">لا توجد مدفوعات</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>

        <aside class="space-y-5">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">إجراءات سريعة</h3>
                    <p class="mt-0.5 text-xs text-muted">إدارة حالة الاتفاقية</p>
                </div>
                <div class="space-y-3 p-4 sm:p-5">
                    <a href="{{ route('admin.agreements.edit', $agreement) }}"
                       class="btn-press flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 py-2.5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                        <i class="fas fa-pen text-xs"></i>
                        تعديل الاتفاقية
                    </a>
                    @if($agreement->status == 'active')
                        <form method="POST" action="{{ route('admin.agreements.update', $agreement) }}" class="w-full">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="suspended">
                            <button type="submit"
                                    class="btn-press flex w-full items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-800 hover:bg-amber-100">
                                <i class="fas fa-pause text-xs"></i>
                                تعليق الاتفاقية
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        </aside>
    </div>
</div>
@endsection
