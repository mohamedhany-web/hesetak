@extends('layouts.admin')

@section('title', 'طلب سحب #' . ($withdrawal->request_number ?? $withdrawal->id) . ' - ' . config('app.name'))
@section('page_title', 'تفاصيل طلب السحب')

@section('content')
@php
    $fieldClass = 'w-full rounded-xl border border-line bg-surface px-3 py-2.5 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $statusBadges = [
        'pending' => ['label' => 'قيد المراجعة', 'classes' => 'bg-canvas-muted text-muted'],
        'approved' => ['label' => 'موافق عليه', 'classes' => 'bg-amber-50 text-amber-700'],
        'rejected' => ['label' => 'مرفوض', 'classes' => 'bg-rose-50 text-rose-700'],
        'processing' => ['label' => 'قيد المعالجة', 'classes' => 'bg-amber-50 text-amber-700'],
        'completed' => ['label' => 'مكتمل', 'classes' => 'bg-emerald-50 text-emerald-700'],
        'cancelled' => ['label' => 'ملغي', 'classes' => 'bg-canvas-muted text-muted'],
    ];
    $status = $statusBadges[$withdrawal->status] ?? ['label' => $withdrawal->status, 'classes' => 'bg-canvas-muted text-muted'];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المالية · طلبات السحب · {{ $withdrawal->request_number ?? '#' . $withdrawal->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">طلب سحب {{ $withdrawal->request_number ?? '#' . $withdrawal->id }}</h2>
            <p class="mt-1 text-sm text-muted">
                <i class="fas fa-calendar-alt text-xs"></i>
                {{ $withdrawal->created_at->format('Y-m-d H:i') }}
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.withdrawals.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة للقائمة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="alert">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-700"><i class="fas fa-exclamation-circle text-sm"></i></span>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                    <div>
                        <h3 class="text-base font-semibold text-ink">تفاصيل الطلب</h3>
                        <p class="mt-0.5 text-xs text-muted">المبلغ وطريقة الاستلام والحالة</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium {{ $status['classes'] }}">
                        <span class="size-1.5 rounded-full bg-current"></span>
                        {{ $status['label'] }}
                    </span>
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div>
                        <dt class="text-xs font-medium text-muted">المبلغ</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ number_format($withdrawal->amount, 2) }} <span class="text-sm font-normal text-muted">{{ currency_symbol() }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">طريقة الاستلام</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $withdrawal->payment_method_label }}</dd>
                    </div>
                </dl>
                @if($withdrawal->notes)
                    <div class="border-t border-line px-4 py-4 sm:px-5">
                        <p class="text-xs font-medium text-amber-700">ملاحظات المدرب</p>
                        <p class="mt-1 text-sm text-ink">{{ $withdrawal->notes }}</p>
                    </div>
                @endif
            </article>

            @if($withdrawal->payment_method === 'bank_transfer' && ($withdrawal->bank_name || $withdrawal->account_number || $withdrawal->account_holder_name || $withdrawal->iban))
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                            <i class="fas fa-university text-sm"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-ink">بيانات التحويل البنكي</h3>
                            <p class="mt-0.5 text-xs text-muted">معلومات الحساب البنكي للمدرب</p>
                        </div>
                    </div>
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    @if($withdrawal->bank_name)
                        <div>
                            <dt class="text-xs font-medium text-muted">البنك</dt>
                            <dd class="mt-1 text-sm font-semibold text-ink">{{ $withdrawal->bank_name }}</dd>
                        </div>
                    @endif
                    @if($withdrawal->account_holder_name)
                        <div>
                            <dt class="text-xs font-medium text-muted">صاحب الحساب</dt>
                            <dd class="mt-1 text-sm font-semibold text-ink">{{ $withdrawal->account_holder_name }}</dd>
                        </div>
                    @endif
                    @if($withdrawal->account_number)
                        <div>
                            <dt class="text-xs font-medium text-muted">رقم الحساب</dt>
                            <dd class="mt-1 font-mono text-sm font-semibold text-ink">{{ $withdrawal->account_number }}</dd>
                        </div>
                    @endif
                    @if($withdrawal->iban)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-muted">الآيبان</dt>
                            <dd class="mt-1 font-mono text-sm font-semibold text-ink">{{ $withdrawal->iban }}</dd>
                        </div>
                    @endif
                </dl>
            </article>
            @endif

            @if($withdrawal->admin_notes || $withdrawal->processed_by)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                            <i class="fas fa-user-cog text-sm"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-ink">معالجة الإدارة</h3>
                            <p class="mt-0.5 text-xs text-muted">سجل المراجعة والملاحظات الإدارية</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    @if($withdrawal->admin_notes)
                        <div>
                            <p class="text-xs font-medium text-muted">ملاحظات الإدارة</p>
                            <p class="mt-1 rounded-xl border border-line bg-canvas/40 p-3 text-sm text-ink">{{ $withdrawal->admin_notes }}</p>
                        </div>
                    @endif
                    @if($withdrawal->processedBy)
                        <div>
                            <p class="text-xs font-medium text-muted">تمت المعالجة بواسطة</p>
                            <p class="mt-1 text-sm font-semibold text-ink">{{ $withdrawal->processedBy->name }}</p>
                        </div>
                    @endif
                    @if($withdrawal->processed_at)
                        <div>
                            <p class="text-xs font-medium text-muted">تاريخ المعالجة</p>
                            <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ $withdrawal->processed_at->format('Y-m-d H:i') }}</p>
                        </div>
                    @endif
                </div>
            </article>
            @endif
        </div>

        <div class="space-y-5">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                            <i class="fas fa-chalkboard-teacher text-sm"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-ink">المدرب</h3>
                            <p class="mt-0.5 text-xs text-muted">بيانات التواصل</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 sm:p-5">
                    @if($withdrawal->instructor)
                        <p class="font-semibold text-ink">{{ $withdrawal->instructor->name }}</p>
                        @if($withdrawal->instructor->phone)
                            <p class="mt-2 text-sm text-muted"><i class="fas fa-phone ml-1 text-xs"></i> {{ $withdrawal->instructor->phone }}</p>
                        @endif
                        @if($withdrawal->instructor->email)
                            <p class="mt-1 break-all text-sm text-muted"><i class="fas fa-envelope ml-1 text-xs"></i> {{ $withdrawal->instructor->email }}</p>
                        @endif
                    @else
                        <p class="text-sm text-muted">—</p>
                    @endif
                </div>
            </article>

            @if($withdrawal->status === 'pending')
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                            <i class="fas fa-tasks text-sm"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-ink">إجراءات</h3>
                            <p class="mt-0.5 text-xs text-muted">موافقة أو رفض الطلب</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-5 p-4 sm:p-5">
                    <form action="{{ route('admin.withdrawals.approve', $withdrawal) }}" method="POST" class="space-y-3">
                        @csrf
                        <label class="{{ $labelClass }}">ملاحظات (اختياري)</label>
                        <textarea name="admin_notes" rows="2" class="{{ $fieldClass }}" placeholder="ملاحظات عند الموافقة"></textarea>
                        <button type="submit" class="btn-press inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                            <i class="fas fa-check"></i>
                            موافقة
                        </button>
                    </form>
                    <form action="{{ route('admin.withdrawals.reject', $withdrawal) }}" method="POST" class="space-y-3">
                        @csrf
                        <label class="{{ $labelClass }}">ملاحظات (اختياري)</label>
                        <textarea name="admin_notes" rows="2" class="{{ $fieldClass }}" placeholder="سبب الرفض إن أردت"></textarea>
                        <button type="submit" class="btn-press inline-flex w-full items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-700"
                                onclick="return confirm('هل أنت متأكد من رفض طلب السحب؟');">
                            <i class="fas fa-times"></i>
                            رفض
                        </button>
                    </form>
                </div>
            </article>
            @endif

            @if($withdrawal->status === 'approved')
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line bg-amber-50/50 px-4 py-4 sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                            <i class="fas fa-check-double text-sm"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-ink">إكمال الطلب</h3>
                            <p class="mt-0.5 text-xs text-muted">تأكيد تنفيذ التحويل</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 sm:p-5">
                    <form action="{{ route('admin.withdrawals.complete', $withdrawal) }}" method="POST" class="space-y-3">
                        @csrf
                        <label class="{{ $labelClass }}">ملاحظات (اختياري)</label>
                        <textarea name="admin_notes" rows="2" class="{{ $fieldClass }}" placeholder="ملاحظات عند الإكمال"></textarea>
                        <button type="submit" class="btn-press inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-700">
                            <i class="fas fa-check-double"></i>
                            تم التحويل / إكمال
                        </button>
                    </form>
                </div>
            </article>
            @endif
        </div>
    </div>
</div>
@endsection
