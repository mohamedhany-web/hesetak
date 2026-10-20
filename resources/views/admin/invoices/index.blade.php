@extends('layouts.admin')

@section('title', 'إدارة الفواتير - ' . config('app.name'))
@section('page_title', 'إدارة الفواتير')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $kpis = [
        ['label' => 'إجمالي الفواتير', 'value' => $stats['total'] ?? 0, 'icon' => 'fa-file-invoice', 'tone' => 'accent', 'note' => 'كل الفواتير المسجلة'],
        ['label' => 'معلقة', 'value' => $stats['pending'] ?? 0, 'icon' => 'fa-hourglass-half', 'tone' => 'metal', 'note' => 'بإنتظار الدفع'],
        ['label' => 'مدفوعة', 'value' => $stats['paid'] ?? 0, 'icon' => 'fa-check-circle', 'tone' => 'accent', 'note' => 'تم دفعها'],
        ['label' => 'متأخرة', 'value' => $stats['overdue'] ?? 0, 'icon' => 'fa-exclamation-triangle', 'tone' => 'muted', 'note' => 'تجاوزت الاستحقاق'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $statusBadges = [
        'paid' => ['label' => 'مدفوعة', 'classes' => 'bg-accent-soft text-accent'],
        'pending' => ['label' => 'معلقة', 'classes' => 'bg-metal/15 text-metal'],
        'overdue' => ['label' => 'متأخرة', 'classes' => 'bg-canvas-muted text-muted'],
        'partial' => ['label' => 'مدفوعة جزئياً', 'classes' => 'bg-metal/15 text-metal'],
        'cancelled' => ['label' => 'ملغاة', 'classes' => 'bg-canvas-muted text-muted'],
        'refunded' => ['label' => 'مستردة', 'classes' => 'bg-canvas-muted text-muted'],
        'draft' => ['label' => 'مسودة', 'classes' => 'bg-canvas-muted text-muted'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · الفواتير</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة الفواتير</h2>
            <p class="mt-1 text-sm text-muted">متابعة الفواتير والمدفوعات وحالة الاستحقاق</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.invoices.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                إنشاء فاتورة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="alert">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-canvas-muted text-muted"><i class="fas fa-exclamation-circle text-sm"></i></span>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($kpi['value']) }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">حسب الحالة أو رقم الفاتورة أو بيانات العميل</p>
        </div>
        <form method="GET" id="filterForm" action="{{ route('admin.invoices.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-3 md:items-end">
            <div class="md:col-span-1">
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}" maxlength="255" placeholder="رقم الفاتورة، اسم العميل، هاتف…" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">جميع الحالات</option>
                    <option value="pending" @selected(request('status') === 'pending')>معلقة</option>
                    <option value="paid" @selected(request('status') === 'paid')>مدفوعة</option>
                    <option value="overdue" @selected(request('status') === 'overdue')>متأخرة</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>ملغاة</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    تطبيق
                </button>
                @if(request()->anyFilled(['search', 'status']))
                    <a href="{{ route('admin.invoices.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-times text-xs"></i>
                        مسح
                    </a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">قائمة الفواتير</h3>
                <p class="mt-0.5 text-xs text-muted">من الأحدث إلى الأقدم</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($invoices->total()) }} فاتورة</span>
        </div>

        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">رقم الفاتورة</th>
                        <th class="px-4 py-3 text-start font-medium">العميل</th>
                        <th class="px-4 py-3 text-start font-medium">المبلغ</th>
                        <th class="px-4 py-3 text-start font-medium">الاستحقاق</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($invoices as $invoice)
                        @php $badge = $statusBadges[$invoice->status] ?? ['label' => $invoice->status, 'classes' => 'bg-canvas-muted text-muted']; @endphp
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink">{{ $invoice->invoice_number }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink">{{ $invoice->user->name ?? '—' }}</p>
                                <p class="mt-0.5 text-[11px] text-muted">{{ $invoice->user->phone ?? $invoice->user->email ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3 font-semibold tabular-nums text-ink">
                                {{ format_money($invoice->total_amount) }}
                                <span class="text-xs font-normal text-muted">{{ currency_symbol() }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs tabular-nums text-muted">
                                {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '—' }}
                                @if($invoice->due_date && $invoice->due_date->isPast() && $invoice->status != 'paid')
                                    <span class="mt-0.5 block text-[11px] font-medium text-metal">متأخرة</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $badge['classes'] }}">{{ $badge['label'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent" title="عرض">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا توجد فواتير</p>
                                <p class="mt-1 text-xs text-muted">لم يتم إنشاء أي فواتير أو لا توجد نتائج للفلتر.</p>
                                <a href="{{ route('admin.invoices.create') }}" class="btn-press mt-4 inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                                    <i class="fas fa-plus text-xs"></i>
                                    إنشاء فاتورة
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $invoices->appends(request()->query())->links() }}</div>
        @endif
    </article>
</div>

@push('scripts')
<script>
(function() {
    var filterForm = document.getElementById('filterForm');
    if (!filterForm) return;
    filterForm.addEventListener('submit', function() {
        var q = this.querySelector('input[name="search"]');
        if (q) q.value = (q.value || '').replace(/[<>'"&]/g, '').trim();
    });
})();
</script>
@endpush
@endsection
