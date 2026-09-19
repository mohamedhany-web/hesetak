@extends('layouts.admin')

@section('title', 'تفاصيل المعاملة المالية - ' . config('app.name'))
@section('page_title', 'تفاصيل المعاملة المالية')

@section('content')
@php
    $statusBadges = [
        'completed' => ['label' => 'مكتملة', 'classes' => 'bg-accent-soft text-accent'],
        'pending' => ['label' => 'معلقة', 'classes' => 'bg-metal/15 text-metal'],
        'failed' => ['label' => 'فاشلة', 'classes' => 'bg-canvas-muted text-muted'],
        'cancelled' => ['label' => 'ملغاة', 'classes' => 'bg-canvas-muted text-muted'],
    ];
    $typeBadges = [
        'credit' => ['label' => 'إيراد', 'classes' => 'bg-accent-soft text-accent', 'icon' => 'fas fa-arrow-down'],
        'debit' => ['label' => 'مصروف', 'classes' => 'bg-metal/15 text-metal', 'icon' => 'fas fa-arrow-up'],
        'income' => ['label' => 'إيراد', 'classes' => 'bg-accent-soft text-accent', 'icon' => 'fas fa-arrow-down'],
        'expense' => ['label' => 'مصروف', 'classes' => 'bg-metal/15 text-metal', 'icon' => 'fas fa-arrow-up'],
        'transfer' => ['label' => 'تحويل', 'classes' => 'bg-metal/15 text-metal', 'icon' => 'fas fa-exchange-alt'],
        'refund' => ['label' => 'استرداد', 'classes' => 'bg-canvas-muted text-muted', 'icon' => 'fas fa-undo'],
        'deposit' => ['label' => 'إيداع', 'classes' => 'bg-accent-soft text-accent', 'icon' => 'fas fa-arrow-down'],
        'withdrawal' => ['label' => 'سحب', 'classes' => 'bg-metal/15 text-metal', 'icon' => 'fas fa-arrow-up'],
        'payment' => ['label' => 'دفع', 'classes' => 'bg-accent-soft text-accent', 'icon' => 'fas fa-credit-card'],
        'commission' => ['label' => 'عمولة', 'classes' => 'bg-canvas-muted text-muted', 'icon' => 'fas fa-percentage'],
    ];
    $typeMeta = $typeBadges[$transaction->type] ?? null;
    $statusMeta = $statusBadges[$transaction->status] ?? null;
    $isCredit = in_array($transaction->type, ['credit', 'income', 'deposit', 'payment']);
    $isDebit = in_array($transaction->type, ['debit', 'expense', 'withdrawal']);
    $amountTone = $isDebit ? 'text-metal' : ($isCredit ? 'text-accent' : 'text-ink');
    $invoiceStatusBadges = [
        'paid' => ['label' => 'مدفوعة', 'classes' => 'bg-accent-soft text-accent'],
        'pending' => ['label' => 'معلقة', 'classes' => 'bg-metal/15 text-metal'],
    ];
    $invoiceStatus = $invoiceStatusBadges[$transaction->invoice?->status ?? ''] ?? ['label' => 'متأخرة', 'classes' => 'bg-canvas-muted text-muted'];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · المعاملات · #{{ $transaction->transaction_number ?? $transaction->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">معاملة #{{ $transaction->transaction_number ?? $transaction->id }}</h2>
            <p class="mt-1 text-sm text-muted">
                <i class="fas fa-calendar-alt text-xs"></i>
                أُنشئت في {{ $transaction->created_at->format('Y-m-d H:i') }}
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.transactions.edit', $transaction) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-edit text-xs"></i>
                تعديل المعاملة
            </a>
            <a href="{{ route('admin.transactions.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة
            </a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $amountTone === 'text-accent' ? 'bg-accent-soft text-accent' : ($amountTone === 'text-metal' ? 'bg-metal/15 text-metal' : 'bg-canvas-muted text-muted') }}">
                <i class="fas fa-coins text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">المبلغ</p>
            <p class="mt-1 text-xl font-semibold tabular-nums {{ $amountTone }}">
                {{ $isDebit ? '-' : ($isCredit ? '+' : '') }}{{ number_format($transaction->amount, 2) }}
                <span class="text-sm font-normal text-muted">$</span>
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $typeMeta['classes'] ?? 'bg-canvas-muted text-muted' }}">
                <i class="{{ $typeMeta['icon'] ?? 'fas fa-exchange-alt' }} text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">النوع</p>
            <p class="mt-1">
                @if($typeMeta)
                    <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold {{ $typeMeta['classes'] }}">{{ $typeMeta['label'] }}</span>
                @else
                    <span class="text-sm font-semibold text-ink">{{ $transaction->type }}</span>
                @endif
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $statusMeta['classes'] ?? 'bg-canvas-muted text-muted' }}">
                <i class="fas fa-toggle-on text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الحالة</p>
            <p class="mt-1">
                @if($statusMeta)
                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold {{ $statusMeta['classes'] }}">{{ $statusMeta['label'] }}</span>
                @endif
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal">
                <i class="fas fa-money-bill text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">العملة</p>
            <p class="mt-1 text-sm font-semibold text-ink">{{ $transaction->currency ?? platform_currency() }}</p>
        </article>
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">معلومات المعاملة</h3>
                    <p class="mt-0.5 text-xs text-muted">التفاصيل المالية والزمنية</p>
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div>
                        <dt class="text-xs font-medium text-muted">المبلغ</dt>
                        <dd class="mt-1 text-lg font-semibold tabular-nums {{ $amountTone }}">
                            {{ $isDebit ? '-' : ($isCredit ? '+' : '') }}{{ number_format($transaction->amount, 2) }} $
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">الفئة</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $transaction->category ?? 'غير محدد' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">تاريخ الإنشاء</dt>
                        <dd class="mt-1 text-sm font-medium tabular-nums text-ink">{{ $transaction->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">آخر تحديث</dt>
                        <dd class="mt-1 text-sm font-medium tabular-nums text-ink">{{ $transaction->updated_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    @if($transaction->createdBy)
                    <div>
                        <dt class="text-xs font-medium text-muted">أنشأها</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $transaction->createdBy->name }}</dd>
                    </div>
                    @endif
                </dl>
            </article>

            @if($transaction->user)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">بيانات العميل</h3>
                    <p class="mt-0.5 text-xs text-muted">المستخدم المرتبط بالمعاملة</p>
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div>
                        <dt class="text-xs font-medium text-muted">الاسم</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $transaction->user->name ?? 'غير معروف' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">رقم الهاتف</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $transaction->user->phone ?? '—' }}</dd>
                    </div>
                    @if($transaction->user->email)
                    <div>
                        <dt class="text-xs font-medium text-muted">البريد الإلكتروني</dt>
                        <dd class="mt-1 break-all text-sm font-medium text-ink">{{ $transaction->user->email }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-muted">الدور</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">
                            @if($transaction->user->role == 'student') {{ __('admin.student_role_label') }}
                            @elseif($transaction->user->role == 'instructor') مدرب
                            @elseif($transaction->user->role == 'admin') إداري
                            @else {{ $transaction->user->role }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </article>
            @endif

            @if($transaction->description)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">الوصف</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-ink">{{ $transaction->description }}</p>
                </div>
            </article>
            @endif

            @if($transaction->metadata && is_array($transaction->metadata) && count($transaction->metadata) > 0)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">البيانات الإضافية</h3>
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    @foreach($transaction->metadata as $key => $value)
                    <div>
                        <dt class="text-xs font-medium text-muted">{{ $key }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}</dd>
                    </div>
                    @endforeach
                </dl>
            </article>
            @endif
        </div>

        <div class="space-y-5">
            @if($transaction->payment)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">الدفعة المرتبطة</h3>
                </div>
                <dl class="space-y-4 p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium text-muted">رقم الدفعة</dt>
                        <dd>
                            <a href="{{ route('admin.payments.show', $transaction->payment) }}" class="text-sm font-semibold text-accent transition hover:text-accent/80">
                                {{ $transaction->payment->payment_number }}
                                <i class="fas fa-external-link-alt text-xs"></i>
                            </a>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium text-muted">المبلغ</dt>
                        <dd class="text-sm font-semibold tabular-nums text-ink">{{ number_format($transaction->payment->amount, 2) }} $</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium text-muted">طريقة الدفع</dt>
                        <dd class="text-sm font-medium text-ink">{{ $transaction->payment->payment_method ?? '—' }}</dd>
                    </div>
                </dl>
            </article>
            @endif

            @if($transaction->invoice)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">الفاتورة المرتبطة</h3>
                </div>
                <dl class="space-y-4 p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium text-muted">رقم الفاتورة</dt>
                        <dd>
                            <a href="{{ route('admin.invoices.show', $transaction->invoice) }}" class="text-sm font-semibold text-accent transition hover:text-accent/80">
                                {{ $transaction->invoice->invoice_number }}
                                <i class="fas fa-external-link-alt text-xs"></i>
                            </a>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium text-muted">المبلغ الإجمالي</dt>
                        <dd class="text-sm font-semibold tabular-nums text-ink">{{ number_format($transaction->invoice->total_amount, 2) }} $</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium text-muted">الحالة</dt>
                        <dd>
                            <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold {{ $invoiceStatus['classes'] }}">{{ $invoiceStatus['label'] }}</span>
                        </dd>
                    </div>
                </dl>
            </article>
            @endif
        </div>
    </div>
</div>
@endsection
