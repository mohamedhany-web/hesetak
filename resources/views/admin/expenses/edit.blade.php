@extends('layouts.admin')

@section('title', 'تعديل المصروف')
@section('page_title', 'تعديل المصروف')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $fileClass = 'w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm text-ink file:me-3 file:rounded-lg file:border-0 file:bg-accent-soft file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-accent';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الحسابات · المصروفات · #{{ $expense->expense_number }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ __('تعديل المصروف') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ __('تحديث بيانات المصروف رقم:') }} {{ $expense->expense_number }}</p>
        </div>
        <a href="{{ route('admin.expenses.show', $expense) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            {{ __('العودة') }}
        </a>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-danger/20 bg-danger/5 p-4 text-sm text-danger shadow-soft">
            <p class="mb-2 font-semibold">يرجى تصحيح ما يلي:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.expenses.update', $expense) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات المصروف</h3>
                <p class="mt-0.5 text-xs text-muted">العنوان، الفئة، المبلغ، والتاريخ</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="title">{{ __('العنوان') }} <span class="text-danger">*</span></label>
                    <input id="title" type="text" name="title" value="{{ old('title', $expense->title) }}" required class="{{ $fieldClass }}" placeholder="{{ __('مثال: شراء معدات للقاعة') }}">
                    @error('title')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="category">{{ __('الفئة') }} <span class="text-danger">*</span></label>
                    <select id="category" name="category" required class="{{ $fieldClass }}">
                        <option value="">{{ __('اختر الفئة') }}</option>
                        <option value="operational" @selected(old('category', $expense->category) == 'operational')>{{ __('تشغيلي') }}</option>
                        <option value="marketing" @selected(old('category', $expense->category) == 'marketing')>{{ __('تسويق') }}</option>
                        <option value="salaries" @selected(old('category', $expense->category) == 'salaries')>{{ __('رواتب') }}</option>
                        <option value="utilities" @selected(old('category', $expense->category) == 'utilities')>{{ __('مرافق') }}</option>
                        <option value="equipment" @selected(old('category', $expense->category) == 'equipment')>{{ __('معدات') }}</option>
                        <option value="maintenance" @selected(old('category', $expense->category) == 'maintenance')>{{ __('صيانة') }}</option>
                        <option value="other" @selected(old('category', $expense->category) == 'other')>{{ __('أخرى') }}</option>
                    </select>
                    @error('category')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="amount">{{ __('المبلغ') }} <span class="text-danger">*</span> {{ currency_unit() }}</label>
                    <input id="amount" type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount', $expense->amount) }}" required class="{{ $fieldClass }}" placeholder="0.00">
                    @error('amount')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="expense_date">{{ __('تاريخ المصروف') }} <span class="text-danger">*</span></label>
                    <input id="expense_date" type="date" name="expense_date" value="{{ old('expense_date', optional($expense->expense_date)->format('Y-m-d')) }}" required class="{{ $fieldClass }}">
                    @error('expense_date')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">الدفع والمرفقات</h3>
                <p class="mt-0.5 text-xs text-muted">طريقة الدفع، المحفظة، المرجع، والإيصال</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="payment_method">{{ __('طريقة الدفع') }} <span class="text-danger">*</span></label>
                    <select id="payment_method" name="payment_method" required class="{{ $fieldClass }}">
                        <option value="">{{ __('اختر طريقة الدفع') }}</option>
                        <option value="cash" @selected(old('payment_method', $expense->payment_method) == 'cash')>{{ __('نقدي') }}</option>
                        <option value="bank_transfer" @selected(old('payment_method', $expense->payment_method) == 'bank_transfer')>{{ __('تحويل بنكي') }}</option>
                        <option value="card" @selected(old('payment_method', $expense->payment_method) == 'card')>{{ __('بطاقة') }}</option>
                        <option value="wallet" @selected(old('payment_method', $expense->payment_method) == 'wallet')>{{ __('محفظة إلكترونية') }}</option>
                        <option value="other" @selected(old('payment_method', $expense->payment_method) == 'other')>{{ __('أخرى') }}</option>
                    </select>
                    @error('payment_method')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div id="wallet_field" style="display: none;">
                    <label class="{{ $labelClass }}" for="wallet_id">{{ __('المحفظة الإلكترونية') }}</label>
                    <select id="wallet_id" name="wallet_id" class="{{ $fieldClass }}">
                        <option value="">{{ __('اختر محفظة') }}</option>
                        @foreach($wallets as $wallet)
                            <option value="{{ $wallet->id }}" @selected(old('wallet_id', $expense->wallet_id) == $wallet->id)>
                                {{ $wallet->name }} ({{ $wallet->type_name }})
                            </option>
                        @endforeach
                    </select>
                    @error('wallet_id')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="reference_number">{{ __('رقم المرجع') }} (اختياري)</label>
                    <input id="reference_number" type="text" name="reference_number" value="{{ old('reference_number', $expense->reference_number) }}" class="{{ $fieldClass }}" placeholder="{{ __('رقم الفاتورة، رقم الشيك، إلخ') }}">
                    @error('reference_number')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="attachment">{{ __('صورة الفاتورة/الإيصال') }} (اختياري)</label>
                    <input id="attachment" type="file" name="attachment" accept="image/*" class="{{ $fileClass }}">
                    <p class="mt-1 text-[11px] text-muted">{{ __('يُسمح بالصور فقط (JPEG, PNG, JPG) - الحد الأقصى 40 ميجابايت') }}</p>
                    @if($expense->attachment)
                        <p class="mt-1 text-xs text-accent">
                            {{ __('يوجد مرفق حالي وسيتم استبداله عند رفع ملف جديد.') }}
                        </p>
                    @endif
                    @error('attachment')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">تفاصيل إضافية</h3>
                <p class="mt-0.5 text-xs text-muted">الوصف والملاحظات (اختياري)</p>
            </div>
            <div class="space-y-4 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="description">{{ __('الوصف') }} (اختياري)</label>
                    <textarea id="description" name="description" rows="3" class="{{ $areaClass }}" placeholder="{{ __('وصف تفصيلي للمصروف...') }}">{{ old('description', $expense->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="notes">{{ __('ملاحظات') }} (اختياري)</label>
                    <textarea id="notes" name="notes" rows="2" class="{{ $areaClass }}" placeholder="{{ __('ملاحظات إضافية...') }}">{{ old('notes', $expense->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-line px-4 py-4 sm:px-5">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                    <i class="fas fa-save text-xs"></i>
                    {{ __('حفظ التعديلات') }}
                </button>
                <a href="{{ route('admin.expenses.show', $expense) }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-6 text-sm font-medium text-ink hover:bg-canvas">
                    <i class="fas fa-times text-xs"></i>
                    {{ __('إلغاء') }}
                </a>
            </div>
        </article>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethod = document.getElementById('payment_method');
    const walletField = document.getElementById('wallet_field');
    const walletId = document.getElementById('wallet_id');

    paymentMethod.addEventListener('change', function() {
        if (this.value === 'wallet' || this.value === 'bank_transfer') {
            walletField.style.display = 'block';
            if (this.value === 'wallet') {
                walletId.setAttribute('required', 'required');
            } else {
                walletId.removeAttribute('required');
            }
        } else {
            walletField.style.display = 'none';
            walletId.removeAttribute('required');
            walletId.value = '';
        }
    });

    if (paymentMethod.value === 'wallet' || paymentMethod.value === 'bank_transfer') {
        walletField.style.display = 'block';
        if (paymentMethod.value === 'wallet') {
            walletId.setAttribute('required', 'required');
        }
    }
});
</script>
@endpush
@endsection
