@extends('layouts.admin')

@section('title', 'تعديل المحفظة')
@section('page_title', 'تعديل المحفظة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المالية · المحافظ · تعديل</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $wallet->name ?? 'محفظة بدون اسم' }}</h2>
            <p class="mt-1 text-sm text-muted">
                <i class="fas fa-user-circle text-xs"></i>
                {{ $wallet->user?->name ?? 'غير مرتبط بمستخدم' }}
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.wallets.show', $wallet) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة للتفاصيل
            </a>
        </div>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                <i class="fas fa-coins text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الرصيد الحالي</p>
            <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($wallet->balance, 2) }} <span class="text-sm font-normal text-muted">{{ $wallet->currency ?? currency_symbol() }}</span></p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal">
                <i class="fas fa-tag text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">نوع المحفظة</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ $wallet->type_name ?? 'غير محدد' }}</p>
        </article>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft" x-data="{ walletType: '{{ old('type', $wallet->type) }}' }">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">بيانات المحفظة</h3>
            <p class="mt-0.5 text-xs text-muted">قم بتحديث معلومات المحفظة واحفظ التغييرات.</p>
        </div>

        <form action="{{ route('admin.wallets.update', $wallet) }}" method="POST" class="space-y-6 p-4 sm:p-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}" for="name">اسم المحفظة <span class="text-rose-500">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name', $wallet->name) }}" required class="{{ $fieldClass }}">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="type">نوع المحفظة <span class="text-rose-500">*</span></label>
                    <select id="type" name="type" x-model="walletType" required class="{{ $fieldClass }}">
                        <option value="vodafone_cash">فودافون كاش</option>
                        <option value="instapay">إنستا باي</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="cash">كاش</option>
                        <option value="other">أخرى</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="account_number">رقم الحساب/المحفظة</label>
                    <input id="account_number" type="text" name="account_number" value="{{ old('account_number', $wallet->account_number) }}" class="{{ $fieldClass }}">
                    @error('account_number')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="walletType === 'bank_transfer'" x-cloak>
                    <label class="{{ $labelClass }}" for="bank_name">اسم البنك</label>
                    <input id="bank_name" type="text" name="bank_name" value="{{ old('bank_name', $wallet->bank_name) }}" class="{{ $fieldClass }}">
                    @error('bank_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="account_holder">اسم صاحب الحساب</label>
                    <input id="account_holder" type="text" name="account_holder" value="{{ old('account_holder', $wallet->account_holder) }}" class="{{ $fieldClass }}">
                    @error('account_holder')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="balance">الرصيد الحالي</label>
                    <div class="relative">
                        <input id="balance" type="number" name="balance" value="{{ old('balance', $wallet->balance) }}" step="0.01" min="0" readonly class="{{ $fieldClass }} cursor-not-allowed bg-canvas-muted/50">
                        <span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-xs text-muted">غير قابل للتعديل</span>
                    </div>
                    <p class="mt-1 text-xs text-muted">لتعديل الرصيد استخدم صفحة المعاملات.</p>
                </div>
            </div>

            <div>
                <label class="{{ $labelClass }}" for="notes">ملاحظات</label>
                <textarea id="notes" name="notes" rows="4" class="{{ $areaClass }}">{{ old('notes', $wallet->notes) }}</textarea>
                @error('notes')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 rounded-xl border border-line bg-canvas/40 px-4 py-3">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $wallet->is_active) ? 'checked' : '' }} class="size-4 rounded border-line text-accent focus:ring-accent/20">
                <div>
                    <label for="is_active" class="text-sm font-semibold text-ink">تفعيل المحفظة</label>
                    <p class="text-xs text-muted">فعّل هذا الخيار للسماح باستخدام المحفظة.</p>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('admin.wallets.index') }}" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                    <i class="fas fa-arrow-right text-xs"></i>
                    إلغاء
                </a>
                <button type="submit" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                    <i class="fas fa-save text-xs"></i>
                    حفظ التغييرات
                </button>
            </div>
        </form>
    </article>
</div>
@endsection
