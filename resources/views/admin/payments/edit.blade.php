@extends('layouts.admin')

@section('title', 'تعديل الدفعة')
@section('page_title', 'تعديل الدفعة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · المدفوعات · {{ $payment->payment_number }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل الدفعة #{{ $payment->payment_number }}</h2>
            <p class="mt-1 text-sm text-muted">تحديث بيانات الدفعة والحالة وطريقة الدفع</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.payments.show', $payment) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-eye text-xs"></i>
                عرض التفاصيل
            </a>
            <a href="{{ route('admin.payments.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
        </div>
    </section>

    <form action="{{ route('admin.payments.update', $payment) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات الدفعة</h3>
                <p class="mt-0.5 text-xs text-muted">تعديل العميل والفاتورة والمبلغ وطريقة الدفع والحالة</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-4 sm:p-5 md:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}">العميل *</label>
                    <select name="user_id" required class="{{ $fieldClass }}">
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ $payment->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }} - {{ $user->phone }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}">الفاتورة</label>
                    <select name="invoice_id" required class="{{ $fieldClass }}">
                        @forelse($invoices as $invoice)
                            <option value="{{ $invoice->id }}" {{ $payment->invoice_id == $invoice->id ? 'selected' : '' }}>
                                {{ $invoice->invoice_number }} · {{ $invoice->user->name }} · متبقي {{ number_format($invoice->remaining_amount + ($payment->invoice_id === $invoice->id ? $payment->amount : 0), 2) }} $
                            </option>
                        @empty
                            <option value="" disabled selected>لا توجد فواتير متاحة</option>
                        @endforelse
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}">المبلغ *</label>
                    <input type="number" name="amount" step="0.01" min="0" required value="{{ old('amount', $payment->amount) }}" class="{{ $fieldClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">طريقة الدفع</label>
                    <select name="payment_method" required class="{{ $fieldClass }}">
                        <option value="cash" {{ $payment->payment_method == 'cash' ? 'selected' : '' }}>نقدي</option>
                        <option value="card" {{ $payment->payment_method == 'card' ? 'selected' : '' }}>بطاقة</option>
                        <option value="bank_transfer" {{ $payment->payment_method == 'bank_transfer' ? 'selected' : '' }}>تحويل بنكي</option>
                        <option value="online" {{ $payment->payment_method == 'online' ? 'selected' : '' }}>دفع إلكتروني</option>
                        <option value="wallet" {{ $payment->payment_method == 'wallet' ? 'selected' : '' }}>محفظة</option>
                        <option value="other" {{ $payment->payment_method == 'other' ? 'selected' : '' }}>أخرى</option>
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}">الحالة *</label>
                    <select name="status" required class="{{ $fieldClass }}">
                        <option value="pending" {{ $payment->status == 'pending' ? 'selected' : '' }}>معلقة</option>
                        <option value="completed" {{ $payment->status == 'completed' ? 'selected' : '' }}>مكتملة</option>
                        <option value="failed" {{ $payment->status == 'failed' ? 'selected' : '' }}>فاشلة</option>
                        <option value="cancelled" {{ $payment->status == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">ملاحظات</label>
                    <textarea name="notes" rows="3" class="{{ $areaClass }}">{{ old('notes', $payment->notes) }}</textarea>
                </div>
            </div>
        </article>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-save text-xs"></i>
                تحديث الدفعة
            </button>
            <a href="{{ route('admin.payments.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection
