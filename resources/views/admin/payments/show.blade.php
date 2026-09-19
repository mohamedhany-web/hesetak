@extends('layouts.admin')

@section('title', 'تفاصيل الدفعة - ' . config('app.name'))
@section('page_title', 'تفاصيل الدفعة')

@section('content')
@php
    $statusBadges = [
        'completed' => ['label' => 'مكتملة', 'classes' => 'bg-accent-soft text-accent'],
        'pending' => ['label' => 'معلقة', 'classes' => 'bg-metal/15 text-metal'],
        'processing' => ['label' => 'قيد المعالجة', 'classes' => 'bg-accent-soft text-accent'],
        'failed' => ['label' => 'فاشلة', 'classes' => 'bg-canvas-muted text-muted'],
        'cancelled' => ['label' => 'ملغاة', 'classes' => 'bg-canvas-muted text-muted'],
        'refunded' => ['label' => 'مستردة', 'classes' => 'bg-canvas-muted text-muted'],
    ];
    $paymentMethodLabels = [
        'cash' => ['label' => 'نقدي', 'icon' => 'fas fa-money-bill'],
        'card' => ['label' => 'بطاقة', 'icon' => 'fas fa-credit-card'],
        'bank_transfer' => ['label' => 'تحويل بنكي', 'icon' => 'fas fa-university'],
        'online' => ['label' => 'دفع إلكتروني', 'icon' => 'fas fa-globe'],
        'wallet' => ['label' => 'محفظة', 'icon' => 'fas fa-wallet'],
        'other' => ['label' => 'أخرى', 'icon' => 'fas fa-ellipsis-h'],
    ];
    $statusMeta = $statusBadges[$payment->status] ?? null;
    $invoiceStatusBadges = [
        'paid' => ['label' => 'مدفوعة', 'classes' => 'bg-accent-soft text-accent'],
        'pending' => ['label' => 'معلقة', 'classes' => 'bg-metal/15 text-metal'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · المدفوعات · {{ $payment->payment_number }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تفاصيل الدفعة #{{ $payment->payment_number }}</h2>
            <p class="mt-1 text-sm text-muted">
                <i class="fas fa-calendar-alt text-xs"></i>
                أُنشئت في {{ $payment->created_at->format('Y-m-d H:i') }}
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.payments.edit', $payment) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-edit text-xs"></i>
                تعديل الدفعة
            </a>
            <a href="{{ route('admin.payments.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة للقائمة
            </a>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                    <div>
                        <h3 class="text-base font-semibold text-ink">معلومات الدفعة</h3>
                        <p class="mt-0.5 text-xs text-muted">المبلغ وطريقة الدفع والتواريخ</p>
                    </div>
                    @if($statusMeta)
                        <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $statusMeta['classes'] }}">{{ $statusMeta['label'] }}</span>
                    @endif
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div>
                        <dt class="text-xs font-medium text-muted">المبلغ</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ number_format($payment->amount, 2) }} <span class="text-sm font-normal text-muted">$</span></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">العملة</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $payment->currency ?? platform_currency() }}</dd>
                    </div>
                    @if(($payment->gateway_fee_amount ?? 0) > 0 || $payment->net_after_gateway_fee !== null)
                    <div>
                        <dt class="text-xs font-medium text-muted">عمولة البوابة (تقدير)</dt>
                        <dd class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ number_format((float) ($payment->gateway_fee_amount ?? 0), 2) }} <span class="text-xs font-normal text-muted">$</span></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">صافي بعد العمولة</dt>
                        <dd class="mt-1 text-sm font-semibold tabular-nums text-ink">
                            @php
                                $netShow = $payment->net_after_gateway_fee !== null
                                    ? (float) $payment->net_after_gateway_fee
                                    : round((float) $payment->amount - (float) ($payment->gateway_fee_amount ?? 0), 2);
                            @endphp
                            {{ number_format($netShow, 2) }} <span class="text-xs font-normal text-muted">$</span>
                        </dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-muted">طريقة الدفع</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center gap-2 rounded-lg bg-canvas-muted px-2.5 py-1 text-xs font-medium text-ink">
                                <i class="{{ $paymentMethodLabels[$payment->payment_method]['icon'] ?? 'fas fa-ellipsis-h' }} text-[10px] text-muted"></i>
                                {{ $paymentMethodLabels[$payment->payment_method]['label'] ?? $payment->payment_method }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">تاريخ الإنشاء</dt>
                        <dd class="mt-1 text-sm font-medium tabular-nums text-ink">{{ $payment->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    @if($payment->paid_at)
                    <div>
                        <dt class="text-xs font-medium text-muted">تاريخ الدفع</dt>
                        <dd class="mt-1 text-sm font-medium tabular-nums text-ink">{{ $payment->paid_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    @endif
                    @if($payment->processedBy)
                    <div>
                        <dt class="text-xs font-medium text-muted">معالج بواسطة</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $payment->processedBy->name }}</dd>
                    </div>
                    @endif
                    @if($payment->payment_gateway)
                    <div>
                        <dt class="text-xs font-medium text-muted">بوابة الدفع</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $payment->payment_gateway }}</dd>
                    </div>
                    @endif
                </dl>
            </article>

            @if($payment->user)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">بيانات العميل</h3>
                    <p class="mt-0.5 text-xs text-muted">معلومات العميل المرتبط بالدفعة</p>
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div>
                        <dt class="text-xs font-medium text-muted">الاسم</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $payment->user->name ?? 'غير معروف' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">رقم الهاتف</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $payment->user->phone ?? '—' }}</dd>
                    </div>
                    @if($payment->user->email)
                    <div>
                        <dt class="text-xs font-medium text-muted">البريد الإلكتروني</dt>
                        <dd class="mt-1 break-all text-sm font-medium text-ink">{{ $payment->user->email }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-muted">الدور</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">
                            @if($payment->user->role == 'student') {{ __('admin.student_role_label') }}
                            @elseif($payment->user->role == 'instructor') مدرب
                            @elseif($payment->user->role == 'admin') إداري
                            @else {{ $payment->user->role }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </article>
            @endif

            @if($payment->notes)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">ملاحظات</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <p class="text-sm leading-relaxed text-ink-soft">{{ $payment->notes }}</p>
                </div>
            </article>
            @endif
        </div>

        <div class="space-y-5">
            @if($payment->invoice)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">الفاتورة المرتبطة</h3>
                    <p class="mt-0.5 text-xs text-muted">تفاصيل الفاتورة المرتبطة بهذه الدفعة</p>
                </div>
                <dl class="space-y-4 p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium text-muted">رقم الفاتورة</dt>
                        <dd>
                            <a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="text-sm font-semibold text-accent transition hover:text-accent/80">
                                {{ $payment->invoice->invoice_number }}
                                <i class="fas fa-external-link-alt text-xs mr-1"></i>
                            </a>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium text-muted">المبلغ الإجمالي</dt>
                        <dd class="text-sm font-semibold tabular-nums text-ink">{{ number_format($payment->invoice->total_amount, 2) }} <span class="text-xs font-normal text-muted">$</span></dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium text-muted">الحالة</dt>
                        <dd>
                            @php
                                $invBadge = $invoiceStatusBadges[$payment->invoice->status] ?? ['label' => 'متأخرة', 'classes' => 'bg-canvas-muted text-muted'];
                            @endphp
                            <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $invBadge['classes'] }}">{{ $invBadge['label'] }}</span>
                        </dd>
                    </div>
                </dl>
            </article>
            @endif

            @if($payment->transactions && $payment->transactions->count() > 0)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">المعاملات المرتبطة</h3>
                    <p class="mt-0.5 text-xs text-muted">{{ $payment->transactions->count() }} معاملة</p>
                </div>
                <div class="divide-y divide-line">
                    @foreach($payment->transactions->take(3) as $transaction)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                        <a href="{{ route('admin.transactions.show', $transaction) }}" class="text-sm font-semibold text-accent transition hover:text-accent/80">
                            {{ $transaction->transaction_number ?? 'N/A' }}
                        </a>
                        <span class="text-sm tabular-nums text-muted">{{ number_format($transaction->amount, 2) }} $</span>
                    </div>
                    @endforeach
                    @if($payment->transactions->count() > 3)
                    <p class="px-4 py-3 text-xs text-muted sm:px-5">و {{ $payment->transactions->count() - 3 }} معاملة أخرى</p>
                    @endif
                </div>
            </article>
            @endif

            @if($payment->reference_number || $payment->transaction_id)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">مراجع إضافية</h3>
                </div>
                <dl class="space-y-4 p-4 sm:p-5">
                    @if($payment->reference_number)
                    <div>
                        <dt class="text-xs font-medium text-muted">رقم المرجع</dt>
                        <dd class="mt-1 font-mono text-sm text-ink">{{ $payment->reference_number }}</dd>
                    </div>
                    @endif
                    @if($payment->transaction_id)
                    <div>
                        <dt class="text-xs font-medium text-muted">رقم المعاملة</dt>
                        <dd class="mt-1 font-mono text-sm text-ink">{{ $payment->transaction_id }}</dd>
                    </div>
                    @endif
                </dl>
            </article>
            @endif
        </div>
    </div>
</div>
@endsection
