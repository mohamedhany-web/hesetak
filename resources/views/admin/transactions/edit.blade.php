@extends('layouts.admin')

@section('title', 'تعديل المعاملة')
@section('page_title', 'تعديل المعاملة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · المعاملات · #{{ $transaction->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل المعاملة</h2>
            <p class="mt-1 text-sm text-muted">{{ $transaction->transaction_number ?? 'معاملة #' . $transaction->id }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-eye text-xs"></i>
                عرض التفاصيل
            </a>
            <a href="{{ route('admin.transactions.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
        </div>
    </section>

    <form action="{{ route('admin.transactions.update', $transaction) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات المعاملة</h3>
                <p class="mt-0.5 text-xs text-muted">العميل، النوع، المبلغ، والحالة</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="user_id">العميل <span class="text-danger">*</span></label>
                    <select id="user_id" name="user_id" required class="{{ $fieldClass }}">
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ $transaction->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }} - {{ $user->phone }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="type">النوع <span class="text-danger">*</span></label>
                    <select id="type" name="type" required class="{{ $fieldClass }}">
                        <option value="deposit" {{ $transaction->type == 'deposit' ? 'selected' : '' }}>إيداع</option>
                        <option value="withdrawal" {{ $transaction->type == 'withdrawal' ? 'selected' : '' }}>سحب</option>
                        <option value="payment" {{ $transaction->type == 'payment' ? 'selected' : '' }}>دفع</option>
                        <option value="refund" {{ $transaction->type == 'refund' ? 'selected' : '' }}>استرداد</option>
                        <option value="commission" {{ $transaction->type == 'commission' ? 'selected' : '' }}>عمولة</option>
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="amount">المبلغ <span class="text-danger">*</span></label>
                    <input id="amount" type="number" name="amount" step="0.01" min="0" required value="{{ old('amount', $transaction->amount) }}" class="{{ $fieldClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="status">الحالة <span class="text-danger">*</span></label>
                    <select id="status" name="status" required class="{{ $fieldClass }}">
                        <option value="pending" {{ $transaction->status == 'pending' ? 'selected' : '' }}>معلقة</option>
                        <option value="completed" {{ $transaction->status == 'completed' ? 'selected' : '' }}>مكتملة</option>
                        <option value="failed" {{ $transaction->status == 'failed' ? 'selected' : '' }}>فاشلة</option>
                        <option value="cancelled" {{ $transaction->status == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                    </select>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">الوصف</h3>
                <p class="mt-0.5 text-xs text-muted">تفاصيل إضافية اختيارية</p>
            </div>
            <div class="p-4 sm:p-5">
                <label class="{{ $labelClass }}" for="description">الوصف</label>
                <textarea id="description" name="description" rows="3" class="{{ $areaClass }}">{{ old('description', $transaction->description) }}</textarea>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-line px-4 py-4 sm:px-5">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                    <i class="fas fa-save text-xs"></i>
                    تحديث المعاملة
                </button>
                <a href="{{ route('admin.transactions.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-6 text-sm font-medium text-ink hover:bg-canvas">
                    إلغاء
                </a>
            </div>
        </article>
    </form>
</div>
@endsection
