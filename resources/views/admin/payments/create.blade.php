@extends('layouts.admin')

@section('title', 'إضافة دفعة جديدة')
@section('page_title', 'إضافة دفعة جديدة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · المدفوعات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إضافة دفعة جديدة</h2>
            <p class="mt-1 text-sm text-muted">تسجيل دفعة جديدة وربطها بالعميل والفاتورة</p>
        </div>
        <a href="{{ route('admin.payments.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للقائمة
        </a>
    </section>

    <form action="{{ route('admin.payments.store') }}" method="POST" class="space-y-5">
        @csrf

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات الدفعة</h3>
                <p class="mt-0.5 text-xs text-muted">اختر العميل والفاتورة وأدخل تفاصيل المبلغ وطريقة الدفع</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-4 sm:p-5 md:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}" for="payment-client-search">العميل *</label>
                    <label for="payment-client-search" class="sr-only">بحث عن عميل بالاسم أو البريد</label>
                    <input type="search" id="payment-client-search" autocomplete="off" placeholder="بحث بالاسم أو البريد أو الجوال…"
                           class="{{ $fieldClass }} mb-2">
                    <select id="payment-user-id" name="user_id" required class="{{ $fieldClass }}">
                        <option value="">اختر العميل</option>
                        @foreach($users as $user)
                        @php
                            $searchHaystack = mb_strtolower(
                                trim($user->name.' '.($user->email ?? '').' '.($user->phone ?? '')),
                                'UTF-8'
                            );
                        @endphp
                        <option value="{{ $user->id }}" data-search="{{ e($searchHaystack) }}">{{ $user->name }} — {{ $user->email }} @if($user->phone) · {{ $user->phone }} @endif</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}">الفاتورة *</label>
                    <select name="invoice_id" required class="{{ $fieldClass }}">
                        @if($invoices->isEmpty())
                            <option value="" disabled selected>لا توجد فواتير مستحقة حاليًا</option>
                        @else
                            <option value="">اختر الفاتورة</option>
                            @foreach($invoices as $invoice)
                            <option value="{{ $invoice->id }}">
                                {{ $invoice->invoice_number }} · {{ $invoice->user->name }} · متبقي {{ number_format($invoice->remaining_amount, 2) }} {{ currency_symbol() }}
                            </option>
                            @endforeach
                        @endif
                    </select>
                    @if($invoices->isEmpty())
                        <p class="mt-2 text-xs text-metal">لا توجد فواتير بحاجة إلى دفع في الوقت الحالي.</p>
                    @endif
                </div>

                <div>
                    <label class="{{ $labelClass }}">المبلغ *</label>
                    <input type="number" name="amount" step="0.01" min="0" required value="{{ old('amount') }}" class="{{ $fieldClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">طريقة الدفع *</label>
                    <select name="payment_method" required class="{{ $fieldClass }}">
                        <option value="cash">نقدي</option>
                        <option value="card">بطاقة</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="online">دفع إلكتروني</option>
                        <option value="wallet">محفظة</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">ملاحظات</label>
                    <textarea name="notes" rows="3" class="{{ $areaClass }}">{{ old('notes') }}</textarea>
                </div>
            </div>
        </article>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                إضافة الدفعة
            </button>
            <a href="{{ route('admin.payments.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                إلغاء
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    var searchInput = document.getElementById('payment-client-search');
    var select = document.getElementById('payment-user-id');
    if (!searchInput || !select) return;
    var options = Array.prototype.slice.call(select.querySelectorAll('option'));
    function applyFilter() {
        var q = (searchInput.value || '').trim().toLowerCase();
        options.forEach(function (opt) {
            if (!opt.value) {
                opt.hidden = false;
                return;
            }
            if (opt.selected) {
                opt.hidden = false;
                return;
            }
            var hay = (opt.getAttribute('data-search') || '').toLowerCase();
            opt.hidden = q.length > 0 && hay.indexOf(q) === -1;
        });
    }
    searchInput.addEventListener('input', applyFilter);
    searchInput.addEventListener('search', applyFilter);
    select.addEventListener('change', applyFilter);
})();
</script>
@endpush
@endsection
