@extends('layouts.admin')

@section('title', 'تفاصيل الفاتورة')
@section('page_title', 'تفاصيل الفاتورة')

@section('content')
@php
    $statusBadges = [
        'paid' => ['label' => 'مدفوعة', 'classes' => 'bg-accent-soft text-accent'],
        'pending' => ['label' => 'معلقة', 'classes' => 'bg-metal/15 text-metal'],
        'overdue' => ['label' => 'متأخرة', 'classes' => 'bg-canvas-muted text-muted'],
        'partial' => ['label' => 'مدفوعة جزئياً', 'classes' => 'bg-metal/15 text-metal'],
        'cancelled' => ['label' => 'ملغاة', 'classes' => 'bg-canvas-muted text-muted'],
        'refunded' => ['label' => 'مستردة', 'classes' => 'bg-canvas-muted text-muted'],
        'draft' => ['label' => 'مسودة', 'classes' => 'bg-canvas-muted text-muted'],
    ];
    $badge = $statusBadges[$invoice->status] ?? ['label' => $invoice->status, 'classes' => 'bg-canvas-muted text-muted'];
    $settlement = $invoice->settlementSummary();
    $paymentStatusLabels = [
        'completed' => 'مكتملة',
        'paid' => 'مدفوعة',
        'success' => 'ناجحة',
        'pending' => 'معلقة',
        'failed' => 'فاشلة',
        'cancelled' => 'ملغاة',
    ];
@endphp

<div class="space-y-5 invoice-print-wrapper">
    <section class="flex flex-wrap items-end justify-between gap-4" data-print-hide>
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · الفواتير · #{{ $invoice->invoice_number }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">فاتورة #{{ $invoice->invoice_number }}</h2>
            <p class="mt-1 text-sm text-muted">أُنشئت في {{ $invoice->created_at->format('Y-m-d') }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-edit text-xs"></i>
                تعديل الفاتورة
            </a>
            <button type="button" onclick="window.printInvoice()" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-print text-xs"></i>
                طباعة الفاتورة
            </button>
            <a href="{{ route('admin.invoices.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status" data-print-hide>
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="alert" data-print-hide>
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-canvas-muted text-muted"><i class="fas fa-exclamation-circle text-sm"></i></span>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs font-medium text-muted">إجمالي المستحق</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ format_money($settlement['due']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-accent-soft/40 p-4 shadow-soft">
            <p class="text-xs font-medium text-accent">اللي اتدفع / اللي أخدناه</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-accent">{{ format_money($settlement['paid']) }}</p>
        </article>
        <article class="rounded-2xl border border-line {{ $settlement['remaining'] > 0 ? 'bg-metal/10' : 'bg-canvas-muted/50' }} p-4 shadow-soft">
            <p class="text-xs font-medium {{ $settlement['remaining'] > 0 ? 'text-metal' : 'text-muted' }}">اللي لسه عليه</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $settlement['remaining'] > 0 ? 'text-ink' : 'text-muted' }}">{{ format_money($settlement['remaining']) }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div id="invoice-print-area" class="space-y-5 p-4 sm:p-5 lg:p-6">
            <div class="rounded-2xl border border-line bg-surface p-5 print-card">
                <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-ink">{{ config('app.name') }}</h3>
                        <p class="mt-2 text-sm text-muted">{{ config('services.platform.support_email') }} · {{ config('services.platform.support_phone') }}</p>
                    </div>
                    <div class="text-sm text-muted md:text-left">
                        <p><span class="text-muted">رقم الفاتورة:</span> <span class="font-semibold text-ink">#{{ $invoice->invoice_number }}</span></p>
                        <p><span class="text-muted">تاريخ الإنشاء:</span> <span class="font-semibold text-ink">{{ $invoice->created_at->format('Y-m-d') }}</span></p>
                        <p><span class="text-muted">تاريخ الطباعة:</span> <span class="font-semibold text-ink">{{ now()->format('Y-m-d H:i') }}</span></p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-line bg-canvas-muted/40 p-5 text-sm text-ink-soft print-strip">
                        <h4 class="text-base font-semibold text-ink">بيانات العميل</h4>
                        <dl class="mt-3 space-y-2">
                            <div class="flex justify-between gap-6"><dt class="text-muted">الاسم</dt><dd class="font-semibold text-ink">{{ $invoice->user->name ?? 'غير معروف' }}</dd></div>
                            <div class="flex justify-between gap-6"><dt class="text-muted">رقم الهاتف</dt><dd class="font-semibold text-ink">{{ $invoice->user->phone ?? '—' }}</dd></div>
                            <div class="flex justify-between gap-6"><dt class="text-muted">البريد الإلكتروني</dt><dd class="font-semibold text-ink">{{ $invoice->user->email ?? '—' }}</dd></div>
                            <div class="flex justify-between gap-6"><dt class="text-muted">نوع الفاتورة</dt><dd class="font-semibold text-ink">{{ $invoice->type }}</dd></div>
                        </dl>
                    </div>
                    <div class="rounded-2xl border border-line bg-canvas-muted/40 p-5 text-sm text-ink-soft print-strip">
                        <h4 class="text-base font-semibold text-ink">حالة الفاتورة</h4>
                        <dl class="mt-3 space-y-2">
                            <div class="flex justify-between gap-6"><dt class="text-muted">الحالة</dt><dd>
                                <span class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1 text-xs font-medium status-badge {{ $badge['classes'] }}" data-status="{{ $invoice->status }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $badge['label'] }}
                                </span>
                            </dd></div>
                            <div class="flex justify-between gap-6"><dt class="text-muted">تاريخ الاستحقاق</dt><dd class="font-semibold text-ink">{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '—' }}</dd></div>
                            <div class="flex justify-between gap-6"><dt class="text-muted">آخر تحديث</dt><dd class="font-semibold text-ink">{{ $invoice->updated_at->format('Y-m-d H:i') }}</dd></div>
                            <div class="flex justify-between gap-6"><dt class="text-muted">ملاحظات</dt><dd class="font-semibold text-ink">{{ $invoice->notes ?: '—' }}</dd></div>
                        </dl>
                    </div>
                </div>
            </div>

            <article class="overflow-hidden rounded-2xl border border-line bg-surface print-card">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">ملخص الرسوم</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <table class="w-full text-sm text-ink-soft">
                        <tbody class="divide-y divide-line">
                            <tr class="flex items-center justify-between py-3">
                                <td>المبلغ الفرعي</td>
                                <td class="font-semibold tabular-nums text-ink">{{ number_format($invoice->subtotal, 2) }} {{ currency_symbol() }}</td>
                            </tr>
                            @if ($invoice->tax_amount > 0)
                                <tr class="flex items-center justify-between py-3">
                                    <td>الضريبة</td>
                                    <td class="font-semibold tabular-nums text-ink">{{ number_format($invoice->tax_amount, 2) }} {{ currency_symbol() }}</td>
                                </tr>
                            @endif
                            @if ($invoice->discount_amount > 0)
                                <tr class="flex items-center justify-between py-3">
                                    <td>الخصم</td>
                                    <td class="font-semibold tabular-nums text-metal">-{{ number_format($invoice->discount_amount, 2) }} {{ currency_symbol() }}</td>
                                </tr>
                            @endif
                            <tr class="flex items-center justify-between py-3 text-base font-semibold text-ink">
                                <td>الإجمالي المستحق</td>
                                <td class="tabular-nums text-accent">{{ format_money($settlement['due']) }}</td>
                            </tr>
                            <tr class="flex items-center justify-between py-3 font-semibold text-accent">
                                <td>المدفوع</td>
                                <td class="tabular-nums">{{ format_money($settlement['paid']) }}</td>
                            </tr>
                            <tr class="flex items-center justify-between py-3 font-semibold {{ $settlement['remaining'] > 0 ? 'text-metal' : 'text-muted' }}">
                                <td>المتبقي عليه</td>
                                <td class="tabular-nums">{{ format_money($settlement['remaining']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            @if ($invoice->payments && $invoice->payments->count() > 0)
                <article class="overflow-hidden rounded-2xl border border-line bg-surface print-card">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">سجل المدفوعات</h3>
                    </div>
                    <div class="admin-table-wrap overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                                <tr>
                                    <th class="px-4 py-3 text-start font-medium">رقم الدفعة</th>
                                    <th class="px-4 py-3 text-center font-medium">تاريخ الدفع</th>
                                    <th class="px-4 py-3 text-center font-medium">الحالة</th>
                                    <th class="px-4 py-3 text-end font-medium">المبلغ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($invoice->payments as $payment)
                                    <tr class="hover:bg-canvas/40">
                                        <td class="px-4 py-3 font-semibold text-ink">{{ $payment->payment_number }}</td>
                                        <td class="px-4 py-3 text-center text-muted">{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d') : '—' }}</td>
                                        <td class="px-4 py-3 text-center text-muted">{{ $paymentStatusLabels[$payment->status] ?? $payment->status }}</td>
                                        <td class="px-4 py-3 text-end font-semibold tabular-nums text-ink">{{ format_money($payment->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @endif
        </div>
    </section>
</div>

@push('styles')
<style>
    .print-strip { border: 1px solid var(--color-line, rgba(226, 232, 240, 0.8)); }
    .status-badge[data-status="paid"] { background: color-mix(in srgb, var(--color-accent, #0B3D91) 12%, transparent) !important; color: var(--color-accent, #0B3D91) !important; }
    .status-badge[data-status="pending"] { background: color-mix(in srgb, var(--color-metal, #64748b) 15%, transparent) !important; color: var(--color-metal, #64748b) !important; }
    .status-badge[data-status="overdue"],
    .status-badge[data-status="unpaid"],
    .status-badge[data-status="cancelled"] { background: var(--color-canvas-muted, #f1f5f9) !important; color: var(--color-muted, #64748b) !important; }
</style>
@endpush

@push('scripts')
<script>
    window.printInvoice = function () {
        const printArea = document.getElementById('invoice-print-area');
        if (!printArea) return;

        const printWindow = window.open('', '_blank', 'width=900,height=1200');
        if (!printWindow) return;

        const headContent = document.head.cloneNode(true);
        const bodyContent = `
            <div class="invoice-print-container">
                <header class="invoice-print-header">
                    <div>
                        <h1>{{ config('app.name') }}</h1>
                        <p class="brand-meta">{{ config('services.platform.support_email') }} · {{ config('services.platform.support_phone') }}</p>
                    </div>
                    <div class="invoice-meta">
                        <p><span>رقم الفاتورة:</span> #${'{{ $invoice->invoice_number }}'}</p>
                        <p><span>تاريخ الإنشاء:</span> {{ $invoice->created_at->format('Y-m-d') }}</p>
                        <p><span>تاريخ الطباعة:</span> ${new Date().toLocaleString('ar-EG')}</p>
                    </div>
                </header>
                <main class="invoice-print-body">
                    ${printArea.innerHTML}
                </main>
                <footer class="invoice-print-footer">
                    <p>توقيع الإدارة</p>
                    <p class="signature-line"></p>
                </footer>
            </div>
        `;

        printWindow.document.open();
        printWindow.document.write('<!doctype html><html lang="ar" dir="rtl"></html>');
        printWindow.document.head.innerHTML = headContent.innerHTML;
        printWindow.document.body.innerHTML = bodyContent;

        const style = printWindow.document.createElement('style');
        style.innerHTML = `
            @page { size: A4 portrait; margin: 12mm 15mm; }
            body { font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif; color: #0f172a; background: #fff; }
            .invoice-print-container { max-width: 760px; margin: 0 auto; display: flex; flex-direction: column; min-height: 100vh; }
            .invoice-print-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; border-bottom: 1px solid #d8e3f0; padding-bottom: 16px; margin-bottom: 24px; }
            .invoice-meta p { margin: 0 0 4px 0; font-size: 12px; color: #334155; }
            .invoice-meta span { font-weight: 600; color: #0f172a; }
            .brand-label { text-transform: uppercase; letter-spacing: 4px; font-size: 11px; color: #64748b; margin-bottom: 4px; }
            .brand-meta { font-size: 12px; color: #475569; margin-top: 4px; }
            .invoice-print-body { flex: 1; display: flex; flex-direction: column; gap: 20px; }
            .invoice-print-body .print-card { border: 1px solid #d9e3f0; background: #ffffff; border-radius: 12px; padding: 18px 22px; }
            .invoice-print-body .print-strip { border: 1px solid #d9e3f0; background: #f8fafc; border-radius: 12px; padding: 18px 22px; }
            .invoice-print-body h3 { margin-top: 0; margin-bottom: 12px; font-size: 16px; }
            .invoice-print-body table { width: 100%; border-collapse: collapse; }
            .invoice-print-body thead { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #64748b; }
            .invoice-print-body tbody tr { border-bottom: 1px solid #eef2f7; }
            .invoice-print-body td, .invoice-print-body th { padding: 8px 0; font-size: 13px; }
            .invoice-print-footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #d8e3f0; text-align: left; font-size: 12px; color: #475569; }
            .signature-line { margin-top: 12px; width: 180px; height: 1px; background: #cbd5f5; }
        `;
        printWindow.document.head.appendChild(style);

        // wait for assets then print
        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }, 500);
    };
</script>
@endpush
@endsection
