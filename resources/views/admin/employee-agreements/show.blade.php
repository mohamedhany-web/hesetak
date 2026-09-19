@extends('layouts.admin')

@section('title', 'تفاصيل اتفاقية الموظف - ' . config('app.name'))
@section('page_title', 'تفاصيل اتفاقية الموظف')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $statusBadges = [
        'draft' => ['label' => 'مسودة', 'classes' => 'bg-canvas text-muted'],
        'active' => ['label' => 'نشط', 'classes' => 'bg-emerald-50 text-emerald-700'],
        'suspended' => ['label' => 'معلق', 'classes' => 'bg-amber-50 text-amber-700'],
        'terminated' => ['label' => 'منتهي', 'classes' => 'bg-rose-50 text-rose-700'],
        'completed' => ['label' => 'مكتمل', 'classes' => 'bg-accent-soft text-accent'],
    ];
    $status = $statusBadges[$employeeAgreement->status] ?? ['label' => $employeeAgreement->status, 'classes' => 'bg-canvas text-muted'];
    $statCards = [
        ['label' => 'الراتب الأساسي', 'value' => number_format($employeeAgreement->salary, 2) . ' ' . currency_symbol(), 'icon' => 'fas fa-money-bill-wave'],
        ['label' => 'إجمالي الخصومات', 'value' => number_format($stats['total_deductions'], 2) . ' ' . currency_symbol(), 'icon' => 'fas fa-minus-circle', 'tone' => 'rose'],
        ['label' => 'إجمالي المدفوعات', 'value' => number_format($stats['total_payments'], 2) . ' ' . currency_symbol(), 'icon' => 'fas fa-check-circle', 'tone' => 'emerald'],
        ['label' => 'الدفعات المعلقة', 'value' => $stats['pending_payments'], 'icon' => 'fas fa-clock', 'tone' => 'amber'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الموارد البشرية · اتفاقيات الموظفين</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h2 class="text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $employeeAgreement->title }}</h2>
                <span class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-semibold {{ $status['classes'] }}">
                    {{ $status['label'] }}
                </span>
            </div>
            <p class="mt-1 text-sm text-muted">
                رقم الاتفاقية: <span class="font-semibold text-ink">{{ $employeeAgreement->agreement_number }}</span>
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.employee-agreements.edit', $employeeAgreement) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-edit text-xs"></i>
                تعديل
            </a>
            <a href="{{ route('admin.employee-agreements.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع
            </a>
        </div>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statCards as $card)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="{{ $card['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $card['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight
                    @if(($card['tone'] ?? '') === 'rose') text-rose-600
                    @elseif(($card['tone'] ?? '') === 'emerald') text-emerald-600
                    @elseif(($card['tone'] ?? '') === 'amber') text-amber-600
                    @else text-ink
                    @endif">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">معلومات الاتفاقية</h3>
                    <p class="mt-0.5 text-xs text-muted">تفاصيل العقد والموظف</p>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium text-muted">الموظف</p>
                            <p class="mt-1 text-sm font-semibold text-ink">{{ $employeeAgreement->employee->name }}</p>
                            <p class="text-xs text-muted">{{ $employeeAgreement->employee->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted">الراتب</p>
                            <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ number_format($employeeAgreement->salary, 2) }} $</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted">الحالة</p>
                            <span class="mt-1 inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-semibold {{ $status['classes'] }}">
                                {{ $status['label'] }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted">تاريخ البدء</p>
                            <p class="mt-1 text-sm font-semibold text-ink">{{ $employeeAgreement->start_date->format('Y-m-d') }}</p>
                        </div>
                        @if($employeeAgreement->end_date)
                        <div>
                            <p class="text-xs font-medium text-muted">تاريخ الانتهاء</p>
                            <p class="mt-1 text-sm font-semibold text-ink">{{ $employeeAgreement->end_date->format('Y-m-d') }}</p>
                        </div>
                        @endif
                    </div>
                    @if($employeeAgreement->description)
                    <div>
                        <p class="text-xs font-medium text-muted">الوصف</p>
                        <p class="mt-1 text-sm text-ink">{{ $employeeAgreement->description }}</p>
                    </div>
                    @endif
                    @if($employeeAgreement->contract_terms)
                    <div>
                        <p class="text-xs font-medium text-muted">شروط العقد</p>
                        <div class="mt-1 text-sm text-ink whitespace-pre-line">{{ $employeeAgreement->contract_terms }}</div>
                    </div>
                    @endif
                    @if($employeeAgreement->agreement_terms)
                    <div>
                        <p class="text-xs font-medium text-muted">بنود الاتفاقية</p>
                        <div class="mt-1 text-sm text-ink whitespace-pre-line">{{ $employeeAgreement->agreement_terms }}</div>
                    </div>
                    @endif
                    @if($employeeAgreement->notes)
                    <div>
                        <p class="text-xs font-medium text-muted">ملاحظات</p>
                        <div class="mt-1 text-sm text-ink whitespace-pre-line">{{ $employeeAgreement->notes }}</div>
                    </div>
                    @endif
                </div>
            </article>

            @if($employeeAgreement->deductions->count() > 0)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">الخصومات</h3>
                    <p class="mt-0.5 text-xs text-muted">{{ $employeeAgreement->deductions->count() }} خصم مسجل</p>
                </div>
                <div class="admin-table-wrap overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-canvas text-xs text-muted">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium">رقم الخصم</th>
                                <th class="px-4 py-3 text-start font-medium">العنوان</th>
                                <th class="px-4 py-3 text-start font-medium">النوع</th>
                                <th class="px-4 py-3 text-start font-medium">المبلغ</th>
                                <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach($employeeAgreement->deductions as $deduction)
                            <tr class="hover:bg-canvas/40">
                                <td class="whitespace-nowrap px-4 py-3 text-ink">{{ $deduction->deduction_number }}</td>
                                <td class="px-4 py-3 text-ink">{{ $deduction->title }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-lg bg-canvas px-2.5 py-1 text-xs font-semibold text-ink">
                                        @if($deduction->type === 'tax') ضريبة
                                        @elseif($deduction->type === 'insurance') تأمين
                                        @elseif($deduction->type === 'loan') قرض
                                        @elseif($deduction->type === 'penalty') غرامة
                                        @else أخرى
                                        @endif
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold tabular-nums text-rose-600">{{ number_format($deduction->amount, 2) }} $</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $deduction->deduction_date->format('Y-m-d') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
            @endif

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex flex-col gap-4 border-b border-line px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div>
                        <h3 class="text-base font-semibold text-ink">سجل المدفوعات</h3>
                        <p class="mt-0.5 text-xs text-muted">دفعات الرواتب والتحويلات</p>
                    </div>
                    @if($employeeAgreement->status === 'active')
                    <button type="button" onclick="document.getElementById('new-payment-form').classList.toggle('hidden')"
                            class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                        <i class="fas fa-plus text-xs"></i>
                        إنشاء دفعة راتب
                    </button>
                    @endif
                </div>

                @if($employeeAgreement->status === 'active')
                <div id="new-payment-form" class="hidden border-b border-line bg-canvas/40 px-4 py-4 sm:px-5">
                    <form action="{{ route('admin.employee-agreements.payments.store', $employeeAgreement) }}" method="POST" class="flex flex-wrap items-end gap-4">
                        @csrf
                        <div>
                            <label class="{{ $labelClass }}">تاريخ الاستحقاق</label>
                            <input type="date" name="payment_date" value="{{ now()->endOfMonth()->format('Y-m-d') }}" required class="{{ $fieldClass }} w-auto min-w-[160px]">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">الخصومات ($)</label>
                            <input type="number" name="total_deductions" value="0" min="0" step="0.01" class="{{ $fieldClass }} w-28">
                        </div>
                        <div class="min-w-[200px] flex-1">
                            <label class="{{ $labelClass }}">ملاحظات</label>
                            <input type="text" name="notes" placeholder="اختياري" class="{{ $fieldClass }}">
                        </div>
                        <button type="submit" class="btn-press inline-flex h-11 items-center rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">إنشاء الدفعة</button>
                    </form>
                </div>
                @endif

                @if($employeeAgreement->payments->count() > 0)
                <div class="admin-table-wrap overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-canvas text-xs text-muted">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium">رقم الدفعة</th>
                                <th class="px-4 py-3 text-start font-medium">تاريخ الاستحقاق</th>
                                <th class="px-4 py-3 text-start font-medium">الراتب الأساسي</th>
                                <th class="px-4 py-3 text-start font-medium">الخصومات</th>
                                <th class="px-4 py-3 text-start font-medium">صافي الراتب</th>
                                <th class="px-4 py-3 text-start font-medium">الحالة</th>
                                <th class="px-4 py-3 text-start font-medium">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach($employeeAgreement->payments as $payment)
                            <tr class="hover:bg-canvas/40">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-ink">{{ $payment->payment_number }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $payment->payment_date->format('Y-m-d') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-ink">{{ number_format($payment->base_salary, 2) }} $</td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-rose-600">{{ number_format($payment->total_deductions, 2) }} $</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold tabular-nums text-emerald-600">{{ number_format($payment->net_salary, 2) }} $</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold
                                        @if($payment->status === 'paid') bg-emerald-50 text-emerald-700
                                        @elseif($payment->status === 'pending') bg-amber-50 text-amber-700
                                        @elseif($payment->status === 'overdue') bg-rose-50 text-rose-700
                                        @else bg-canvas text-muted
                                        @endif">
                                        @if($payment->status === 'paid') مدفوعة
                                        @elseif($payment->status === 'pending') معلقة
                                        @elseif($payment->status === 'overdue') متأخرة
                                        @else ملغاة
                                        @endif
                                    </span>
                                    @if($payment->status === 'paid' && $payment->transfer_receipt_path)
                                        <a href="{{ storage_asset($payment->transfer_receipt_path) }}" target="_blank" class="mt-1 block text-xs text-accent hover:underline"><i class="fas fa-receipt mr-1"></i>الإيصال</a>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if(in_array($payment->status, ['pending', 'overdue']))
                                        <button type="button" class="open-pay-modal text-sm font-medium text-accent hover:text-[#0d4f4a]" data-action="{{ route('admin.employee-agreements.payments.mark-paid', $payment) }}" data-payment-num="{{ $payment->payment_number }}">دفع ورفع إيصال</button>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="px-4 py-8 text-center text-sm text-muted sm:px-5">لا توجد مدفوعات حتى الآن. استخدم "إنشاء دفعة راتب" لإضافة دفعة ثم تنفيذ التحويل ورفع الإيصال.</div>
                @endif
            </article>
        </div>

        <div class="space-y-5">
            @php $emp = $employeeAgreement->employee; @endphp
            @if($emp && ($emp->bank_name || $emp->bank_account_number || $emp->bank_account_holder_name))
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">
                        <i class="fas fa-university text-accent mr-2"></i>البيانات البنكية للموظف
                    </h3>
                </div>
                <div class="space-y-2 p-4 text-sm sm:p-5">
                    @if($emp->bank_name)<p><span class="text-muted">البنك:</span> <span class="font-semibold text-ink">{{ $emp->bank_name }}</span></p>@endif
                    @if($emp->bank_branch)<p><span class="text-muted">الفرع:</span> <span class="text-ink">{{ $emp->bank_branch }}</span></p>@endif
                    @if($emp->bank_account_number)<p><span class="text-muted">رقم الحساب:</span> <span class="font-mono font-semibold text-ink">{{ $emp->bank_account_number }}</span></p>@endif
                    @if($emp->bank_account_holder_name)<p><span class="text-muted">اسم صاحب الحساب:</span> <span class="text-ink">{{ $emp->bank_account_holder_name }}</span></p>@endif
                    @if($emp->bank_iban)<p><span class="text-muted">الآيبان:</span> <span class="font-mono text-ink">{{ $emp->bank_iban }}</span></p>@endif
                </div>
            </article>
            @endif

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">معلومات إضافية</h3>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    <div>
                        <p class="text-xs font-medium text-muted">منشئ الاتفاقية</p>
                        <p class="mt-1 text-sm font-semibold text-ink">{{ $employeeAgreement->creator->name ?? 'غير محدد' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted">تاريخ الإنشاء</p>
                        <p class="mt-1 text-sm text-ink">{{ $employeeAgreement->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted">آخر تحديث</p>
                        <p class="mt-1 text-sm text-ink">{{ $employeeAgreement->updated_at->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            </article>
        </div>
    </div>

    {{-- نافذة دفع ورفع إيصال (خارج الجدول لضمان ظهورها بشكل سليم) --}}
    <div id="pay-receipt-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4" style="direction: rtl; background: rgba(0,0,0,0.5);">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-line bg-surface shadow-soft" onclick="event.stopPropagation()">
            <div class="border-b border-line p-6">
                <h4 class="text-lg font-semibold text-ink">تسجيل الدفع ورفع إيصال التحويل</h4>
                <p id="pay-modal-payment-num" class="mt-1 text-sm text-muted"></p>
            </div>
            <form id="pay-receipt-form" method="POST" enctype="multipart/form-data" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">إيصال التحويل *</label>
                        <input type="file" name="transfer_receipt" accept=".pdf,.jpg,.jpeg,.png" required class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink file:me-3 file:rounded-lg file:border-0 file:bg-accent-soft file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-accent">
                        <p class="mt-1 text-xs text-muted">PDF أو صورة، حجم أقصى 40 ميجابايت</p>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">ملاحظات (اختياري)</label>
                        <textarea name="notes" rows="2" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="btn-press flex-1 rounded-xl bg-accent py-2.5 text-sm font-semibold text-white hover:bg-[#0d4f4a]">تسجيل الدفع</button>
                    <button type="button" id="pay-modal-close" class="btn-press rounded-xl border border-line px-4 py-2.5 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            var modal = document.getElementById('pay-receipt-modal');
            var form = document.getElementById('pay-receipt-form');
            var paymentNumEl = document.getElementById('pay-modal-payment-num');
            document.querySelectorAll('.open-pay-modal').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    form.action = this.getAttribute('data-action');
                    var num = this.getAttribute('data-payment-num');
                    paymentNumEl.textContent = num ? 'الدفعة: ' + num : '';
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                });
            });
            document.getElementById('pay-modal-close').addEventListener('click', function() {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            });
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        })();
    </script>
</div>
@endsection
