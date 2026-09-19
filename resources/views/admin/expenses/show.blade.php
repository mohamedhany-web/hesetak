@extends('layouts.admin')

@section('title', 'تفاصيل المصروف #' . $expense->expense_number)
@section('page_title', 'تفاصيل المصروف #' . $expense->expense_number)

@section('content')
@php
    $statusTone = match ($expense->status) {
        'pending' => ['badge' => 'bg-metal/15 text-metal', 'icon' => 'fa-hourglass-half', 'label' => __('قيد الانتظار')],
        'approved' => ['badge' => 'bg-accent-soft text-accent', 'icon' => 'fa-check-circle', 'label' => __('موافق عليه')],
        default => ['badge' => 'bg-canvas-muted text-muted', 'icon' => 'fa-times-circle', 'label' => __('مرفوض')],
    };
    $paymentMethods = [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'card' => 'بطاقة',
        'wallet' => 'محفظة إلكترونية',
        'other' => 'أخرى',
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الحسابات · المصروفات · #{{ $expense->expense_number }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ __('تفاصيل المصروف') }} #{{ $expense->expense_number }}</h2>
            <p class="mt-1 text-sm text-muted">
                <i class="fas fa-calendar-alt text-xs"></i>
                {{ $expense->created_at->format('d/m/Y - H:i') }}
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.expenses.edit', $expense) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-edit text-xs"></i>
                {{ __('تعديل') }}
            </a>
            <a href="{{ route('admin.expenses.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                {{ __('العودة') }}
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            {{-- معلومات المصروف --}}
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">{{ __('معلومات المصروف') }}</h3>
                    <p class="mt-0.5 text-xs text-muted">البيانات الأساسية للمصروف</p>
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div class="rounded-xl border border-line bg-canvas/40 p-4">
                        <dt class="text-xs font-medium text-muted">{{ __('العنوان') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $expense->title }}</dd>
                    </div>
                    <div class="rounded-xl border border-line bg-canvas/40 p-4">
                        <dt class="text-xs font-medium text-muted">{{ __('الفئة') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $expense->category_label ?? $expense->category }}</dd>
                    </div>
                    <div class="rounded-xl border border-line bg-canvas/40 p-4">
                        <dt class="text-xs font-medium text-muted">{{ __('المبلغ') }}</dt>
                        <dd class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($expense->amount, 2) }} <span class="text-sm font-normal text-muted">{{ $expense->currency }}</span></dd>
                    </div>
                    <div class="rounded-xl border border-line bg-canvas/40 p-4">
                        <dt class="text-xs font-medium text-muted">{{ __('تاريخ المصروف') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $expense->expense_date->format('d/m/Y') }}</dd>
                    </div>
                    <div class="rounded-xl border border-line bg-canvas/40 p-4">
                        <dt class="text-xs font-medium text-muted">{{ __('طريقة الدفع') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $paymentMethods[$expense->payment_method] ?? $expense->payment_method }}</dd>
                    </div>
                    <div class="rounded-xl border border-line bg-canvas/40 p-4">
                        <dt class="text-xs font-medium text-muted">{{ __('رقم المرجع') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $expense->reference_number ?? '—' }}</dd>
                    </div>
                </dl>

                @if($expense->description)
                <div class="border-t border-line px-4 py-4 sm:px-5">
                    <p class="text-xs font-medium text-muted">{{ __('الوصف') }}</p>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-ink-soft">{{ $expense->description }}</p>
                </div>
                @endif

                @if($expense->notes)
                <div class="border-t border-line px-4 py-4 sm:px-5">
                    <p class="text-xs font-medium text-muted">{{ __('ملاحظات') }}</p>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-ink-soft">{{ $expense->notes }}</p>
                </div>
                @endif
            </article>

            {{-- حالة المصروف --}}
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">{{ __('حالة المصروف') }}</h3>
                    <p class="mt-0.5 text-xs text-muted">الموافقة والتتبع</p>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold {{ $statusTone['badge'] }}">
                            <i class="fas {{ $statusTone['icon'] }} text-xs"></i>
                            {{ $statusTone['label'] }}
                        </span>

                        @if($expense->approved_at)
                        <p class="text-sm text-muted">
                            <i class="fas fa-user-check text-accent text-xs"></i>
                            {{ __('تمت الموافقة بواسطة:') }}
                            <span class="font-semibold text-ink">{{ $expense->approvedBy->name ?? 'غير محدد' }}</span>
                            <span class="ms-2">في {{ $expense->approved_at->format('d/m/Y - H:i') }}</span>
                        </p>
                        @endif
                    </div>

                    @if($expense->createdBy)
                    <p class="text-sm text-muted">
                        <i class="fas fa-user-plus text-accent text-xs"></i>
                        {{ __('أنشأ بواسطة:') }}
                        <span class="font-semibold text-ink">{{ $expense->createdBy->name ?? 'غير محدد' }}</span>
                    </p>
                    @endif
                </div>
            </article>

            {{-- المرفق --}}
            @if($expense->attachment)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">{{ __('صورة الإيصال/الفاتورة') }}</h3>
                    <p class="mt-0.5 text-xs text-muted">اضغط على الصورة لعرضها بحجم أكبر</p>
                </div>
                <div class="p-4 text-center sm:p-5">
                    <div class="inline-block rounded-xl border border-line bg-canvas/40 p-2">
                        @php
                            $imagePath = 'storage/' . $expense->attachment;
                            $fullPath = storage_path('app/public/' . $expense->attachment);
                            $imageExists = file_exists($fullPath);
                            $imageUrl = asset($imagePath);
                        @endphp
                        @if($imageExists)
                        <img src="{{ $imageUrl }}"
                             alt="مرفق المصروف"
                             class="max-w-full cursor-pointer rounded-lg shadow-soft transition hover:shadow-md"
                             onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='block';"
                             onclick="openImageModal(this.src)">
                        <div class="hidden rounded-lg border border-line bg-canvas-muted p-4">
                            <p class="flex items-center justify-center gap-2 text-sm text-muted">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>الصورة غير متوفرة حالياً</span>
                            </p>
                        </div>
                        @else
                        <div class="rounded-lg border border-line bg-canvas-muted p-4">
                            <p class="flex items-center justify-center gap-2 text-sm text-muted">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>المرفق غير موجود. محاولة العرض عبر Route بديل.</span>
                            </p>
                            <img src="{{ route('storage.fallback', ['path' => $expense->attachment]) }}"
                                 alt="مرفق المصروف (بديل)"
                                 class="mt-4 max-w-full cursor-pointer rounded-lg shadow-soft transition hover:shadow-md"
                                 onerror="this.onerror=null; this.style.display='none'; this.previousElementSibling.style.display='block';"
                                 onclick="openImageModal(this.src)">
                        </div>
                        @endif
                    </div>
                </div>
            </article>
            @endif

            {{-- الترابطات --}}
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">{{ __('الترابطات') }}</h3>
                    <p class="mt-0.5 text-xs text-muted">المعاملات والمحافظ والفواتير المرتبطة</p>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    @if($expense->transaction)
                    <div class="rounded-xl border border-line bg-canvas/40 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="inline-flex size-10 items-center justify-center rounded-xl bg-accent-soft text-accent">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-ink">{{ __('المعاملة المالية المرتبطة') }}</p>
                                    <p class="text-xs text-muted">#{{ $expense->transaction->transaction_number ?? $expense->transaction->id }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.transactions.show', $expense->transaction) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent">
                                {{ __('عرض') }}
                                <i class="fas fa-external-link-alt text-[10px]"></i>
                            </a>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-3 border-t border-line pt-3 text-xs">
                            <div>
                                <dt class="text-muted">{{ __('المبلغ:') }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ number_format($expense->transaction->amount, 2) }} {{ $expense->transaction->currency }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">{{ __('النوع:') }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">debit (مصروف)</dd>
                            </div>
                            <div>
                                <dt class="text-muted">{{ __('الحالة:') }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">
                                    @if($expense->transaction->status === 'completed')
                                        <span class="text-accent">مكتملة</span>
                                    @elseif($expense->transaction->status === 'pending')
                                        <span class="text-metal">معلقة</span>
                                    @else
                                        <span class="text-muted">ملغاة</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted">{{ __('التاريخ:') }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ $expense->transaction->created_at->format('d/m/Y') }}</dd>
                            </div>
                        </dl>
                    </div>
                    @endif

                    @if($expense->wallet)
                    <div class="rounded-xl border border-line bg-canvas/40 p-4">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex size-10 items-center justify-center rounded-xl bg-metal/15 text-metal">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-ink">{{ __('المحفظة المستخدمة') }}</p>
                                <p class="text-xs text-muted">{{ $expense->wallet->name ?? $expense->wallet->type_name }}</p>
                            </div>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-3 border-t border-line pt-3 text-xs">
                            <div>
                                <dt class="text-muted">{{ __('النوع:') }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ $expense->wallet->type_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">{{ __('رقم الحساب:') }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ $expense->wallet->account_number }}</dd>
                            </div>
                            @if($expense->wallet->bank_name)
                            <div>
                                <dt class="text-muted">{{ __('اسم البنك:') }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ $expense->wallet->bank_name }}</dd>
                            </div>
                            @endif
                            @if($expense->wallet->account_holder)
                            <div>
                                <dt class="text-muted">{{ __('صاحب الحساب:') }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ $expense->wallet->account_holder }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                    @endif

                    @if($expense->invoice)
                    <div class="rounded-xl border border-line bg-canvas/40 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="inline-flex size-10 items-center justify-center rounded-xl bg-canvas-muted text-muted">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-ink">{{ __('الفاتورة المرتبطة') }}</p>
                                    <p class="text-xs text-muted">#{{ $expense->invoice->invoice_number }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.invoices.show', $expense->invoice) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent">
                                {{ __('عرض') }}
                                <i class="fas fa-external-link-alt text-[10px]"></i>
                            </a>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-3 border-t border-line pt-3 text-xs">
                            <div>
                                <dt class="text-muted">{{ __('المبلغ:') }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ number_format($expense->invoice->total_amount, 2) }} {{ $expense->invoice->currency ?? platform_currency() }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">{{ __('الحالة:') }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">
                                    @if($expense->invoice->status === 'paid')
                                        <span class="text-accent">مدفوعة</span>
                                    @elseif($expense->invoice->status === 'pending')
                                        <span class="text-metal">معلقة</span>
                                    @else
                                        <span class="text-muted">متأخرة</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                    @endif

                    @if(!$expense->transaction && !$expense->wallet && !$expense->invoice)
                    <div class="rounded-xl border border-line bg-canvas/40 p-6 text-center">
                        <p class="text-sm text-muted">
                            <i class="fas fa-info-circle"></i>
                            {{ __('لا توجد ترابطات مرتبطة بهذا المصروف') }}
                        </p>
                    </div>
                    @endif
                </div>
            </article>
        </div>

        {{-- الشريط الجانبي --}}
        <div class="space-y-5">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">{{ __('معلومات سريعة') }}</h3>
                </div>
                <div class="divide-y divide-line">
                    <div class="flex items-center justify-between px-4 py-3 sm:px-5">
                        <span class="text-sm text-muted">{{ __('رقم المصروف') }}</span>
                        <span class="font-semibold tabular-nums text-ink">#{{ $expense->expense_number }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3 sm:px-5">
                        <span class="text-sm text-muted">{{ __('تاريخ الإنشاء') }}</span>
                        <span class="font-semibold tabular-nums text-ink">{{ $expense->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3 sm:px-5">
                        <span class="text-sm text-muted">{{ __('آخر تحديث') }}</span>
                        <span class="font-semibold tabular-nums text-ink">{{ $expense->updated_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </article>

            @if($expense->status === 'pending')
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">{{ __('الإجراءات') }}</h3>
                    <p class="mt-0.5 text-xs text-muted">موافقة أو رفض المصروف</p>
                </div>
                <div class="space-y-3 p-4 sm:p-5">
                    <form action="{{ route('admin.expenses.approve', $expense) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                            <i class="fas fa-check text-xs"></i>
                            {{ __('الموافقة على المصروف') }}
                        </button>
                    </form>
                    <form action="{{ route('admin.expenses.reject', $expense) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-canvas">
                            <i class="fas fa-times text-xs"></i>
                            {{ __('رفض المصروف') }}
                        </button>
                    </form>
                </div>
            </article>
            @endif
        </div>
    </div>
</div>

{{-- Modal لعرض الصورة بحجم أكبر --}}
<div id="imageModal" class="fixed inset-0 z-50 hidden overflow-y-auto" onclick="closeImageModal()">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="fixed inset-0 bg-black/75 transition-opacity" onclick="closeImageModal()"></div>
        <div class="relative w-full max-w-4xl rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <button type="button" onclick="closeImageModal()" class="absolute start-4 top-4 text-muted transition hover:text-ink">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <img id="modalImage" src="" alt="صورة مكبرة" class="w-full rounded-lg">
        </div>
    </div>
</div>

@push('scripts')
<script>
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
</script>
@endpush
@endsection
