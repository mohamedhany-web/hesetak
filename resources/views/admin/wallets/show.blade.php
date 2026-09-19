@extends('layouts.admin')

@section('title', 'تفاصيل المحفظة')
@section('page_title', 'تفاصيل المحفظة')

@section('content')
@php
    $kpis = [
        ['label' => 'الرصيد الحالي', 'value' => number_format($wallet->balance, 2), 'icon' => 'fa-coins', 'tone' => 'accent', 'note' => 'الرصيد المتاح حالياً بعد آخر حركة', 'suffix' => ' ' . ($wallet->currency ?? currency_symbol())],
        ['label' => 'إجمالي الإيداعات', 'value' => number_format($metrics['total_deposits'] ?? 0, 2), 'icon' => 'fa-arrow-down', 'tone' => 'accent', 'note' => 'جميع المبالغ المضافة منذ إنشاء المحفظة', 'suffix' => ' ' . ($wallet->currency ?? currency_symbol())],
        ['label' => 'إجمالي السحوبات', 'value' => number_format($metrics['total_withdrawals'] ?? 0, 2), 'icon' => 'fa-arrow-up', 'tone' => 'muted', 'note' => 'جميع المبالغ المسحوبة من المحفظة', 'suffix' => ' ' . ($wallet->currency ?? currency_symbol())],
        ['label' => 'صافي التدفقات', 'value' => number_format($metrics['net_flow'] ?? 0, 2), 'icon' => 'fa-balance-scale', 'tone' => 'metal', 'note' => 'الفرق بين الإيداعات والسحوبات', 'suffix' => ' ' . ($wallet->currency ?? currency_symbol())],
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
            <p class="text-xs font-medium text-muted">المالية · المحافظ · تفاصيل</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h2 class="text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $wallet->name ?? 'محفظة بدون اسم' }}</h2>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">
                    <i class="fas fa-tag text-[10px]"></i>
                    {{ $wallet->type_name }}
                </span>
                @if($wallet->is_active)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">
                        <span class="size-1.5 rounded-full bg-accent"></span>
                        نشطة
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-canvas-muted px-2.5 py-1 text-xs font-medium text-muted">
                        <span class="size-1.5 rounded-full bg-muted"></span>
                        غير نشطة
                    </span>
                @endif
            </div>
            <p class="mt-1 text-sm text-muted">
                حساب مرتبط بالطالب: {{ $wallet->user?->name ?? 'غير محدد' }} — {{ $wallet->user?->phone ?? 'بدون رقم' }}
            </p>
            @if($metrics['last_transaction_at'] ?? null)
                <p class="mt-1 text-xs text-muted">
                    آخر حركة {{ $metrics['last_transaction_at']->diffForHumans() }}
                    ({{ $metrics['last_transaction_type'] === 'deposit' ? 'إيداع' : 'سحب' }})
                </p>
            @else
                <p class="mt-1 text-xs text-muted">لا توجد تعاملات مسجلة بعد.</p>
            @endif
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.wallets.transactions', $wallet) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-receipt text-xs"></i>
                سجل المعاملات
            </a>
            <a href="{{ route('admin.wallets.reports', $wallet) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-chart-line text-xs"></i>
                تقارير مالية
            </a>
            <a href="{{ route('admin.wallets.edit', $wallet) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-edit text-xs"></i>
                تعديل البيانات
            </a>
            <form action="{{ route('admin.wallets.destroy', $wallet) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من إزالة هذه المحفظة؟ سيتم حذف المحفظة نهائياً.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-rose-600 transition hover:border-rose-300 hover:bg-rose-50">
                    <i class="fas fa-trash text-xs"></i>
                    إزالة المحفظة
                </button>
            </form>
        </div>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ $kpi['value'] }}{{ $kpi['suffix'] ?? '' }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                    <div>
                        <h3 class="text-base font-semibold text-ink">ملخص {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</h3>
                        <p class="mt-0.5 text-xs text-muted">نظرة سريعة على نشاط الشهر الحالي</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-lg bg-metal/15 px-2.5 py-1 text-xs font-medium text-metal">
                        <i class="fas fa-history text-[10px]"></i>
                        {{ $metrics['transactions_count'] }} معاملات إجمالية
                    </span>
                </div>
                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div class="rounded-xl border border-line bg-accent-soft/50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-accent">إيداعات الشهر</p>
                            <i class="fas fa-plus text-accent"></i>
                        </div>
                        <p class="mt-3 text-2xl font-semibold tabular-nums text-ink">
                            {{ number_format($metrics['current_month_deposits'] ?? 0, 2) }} <span class="text-sm font-normal text-muted">{{ $wallet->currency ?? currency_symbol() }}</span>
                        </p>
                    </div>
                    <div class="rounded-xl border border-line bg-canvas-muted/50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-muted">سحوبات الشهر</p>
                            <i class="fas fa-minus text-muted"></i>
                        </div>
                        <p class="mt-3 text-2xl font-semibold tabular-nums text-ink">
                            {{ number_format($metrics['current_month_withdrawals'] ?? 0, 2) }} <span class="text-sm font-normal text-muted">{{ $wallet->currency ?? currency_symbol() }}</span>
                        </p>
                    </div>
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">تفاصيل المحفظة</h3>
                    <p class="mt-0.5 text-xs text-muted">بيانات الحساب والرصيد المعلق</p>
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div>
                        <dt class="text-xs font-medium text-muted">صاحب الحساب</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $wallet->account_holder ?? $wallet->user?->name ?? 'غير محدد' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">رقم الحساب / المحفظة</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $wallet->account_number ?? 'غير متوفر' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">اسم البنك</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $wallet->bank_name ?? 'غير محدد' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">الرصيد المعلق</dt>
                        <dd class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ number_format($wallet->pending_balance ?? 0, 2) }} {{ $wallet->currency ?? currency_symbol() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">تاريخ الإنشاء</dt>
                        <dd class="mt-1 text-sm font-medium text-ink">{{ optional($wallet->created_at)->format('Y-m-d') ?? 'غير متوفر' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">آخر تحديث</dt>
                        <dd class="mt-1 text-sm font-medium text-ink">{{ optional($wallet->updated_at)->diffForHumans() ?? 'غير متوفر' }}</dd>
                    </div>
                </dl>
                @if($wallet->notes)
                    <div class="mx-4 mb-4 rounded-xl border border-line bg-accent-soft/30 px-4 py-3 sm:mx-5 sm:mb-5">
                        <p class="text-xs font-medium text-accent">ملاحظات إدارية</p>
                        <p class="mt-1 text-sm leading-relaxed text-ink">{{ $wallet->notes }}</p>
                    </div>
                @endif
            </article>
        </div>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">آخر المعاملات</h3>
                <p class="mt-0.5 text-xs text-muted">أحدث حركات المحفظة</p>
            </div>
            <div class="space-y-3 p-4 sm:p-5">
                @forelse($recentTransactions as $transaction)
                    <div class="flex items-start gap-3 rounded-xl border border-line bg-canvas/40 p-4 transition hover:border-accent/20">
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl {{ $transaction->type === 'deposit' ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                            <i class="fas {{ $transaction->type === 'deposit' ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-ink">
                                    {{ $transaction->type === 'deposit' ? 'إيداع' : 'سحب' }}
                                </p>
                                <p class="text-xs tabular-nums text-muted">
                                    {{ $transaction->created_at?->format('Y-m-d H:i') }}
                                </p>
                            </div>
                            <p class="mt-1 text-base font-semibold tabular-nums text-ink">{{ number_format($transaction->amount, 2) }} {{ $wallet->currency ?? currency_symbol() }}</p>
                            <p class="mt-1 text-xs text-muted">
                                الرصيد بعد العملية: {{ number_format($transaction->balance_after, 2) }} {{ $wallet->currency ?? currency_symbol() }}
                            </p>
                            @if($transaction->notes)
                                <p class="mt-2 text-xs leading-relaxed text-ink-soft">{{ $transaction->notes }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <p class="text-sm font-medium text-ink">لا توجد معاملات مسجلة</p>
                        <p class="mt-1 text-xs text-muted">لا توجد معاملات مسجلة لهذه المحفظة حتى الآن.</p>
                    </div>
                @endforelse
            </div>
            <div class="border-t border-line px-4 py-4 text-center sm:px-5">
                <a href="{{ route('admin.wallets.transactions', $wallet) }}" class="inline-flex items-center gap-2 text-sm font-medium text-accent hover:underline">
                    عرض سجل المعاملات بالكامل
                    <i class="fas fa-arrow-left text-xs"></i>
                </a>
            </div>
        </article>
    </div>
</div>
@endsection
