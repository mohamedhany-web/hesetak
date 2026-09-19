@extends('layouts.admin')

@section('title', 'إدارة المعاملات المالية - ' . config('app.name'))
@section('page_title', 'إدارة المعاملات المالية')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $kpis = [
        ['label' => 'إجمالي المعاملات', 'value' => $stats['total'] ?? 0, 'icon' => 'fa-exchange-alt', 'tone' => 'accent', 'note' => 'كل المعاملات المسجلة'],
        ['label' => 'مكتملة', 'value' => $stats['completed'] ?? 0, 'icon' => 'fa-check-circle', 'tone' => 'accent', 'note' => 'تمت بنجاح'],
        ['label' => 'معلقة', 'value' => $stats['pending'] ?? 0, 'icon' => 'fa-hourglass-half', 'tone' => 'metal', 'note' => 'في انتظار المعالجة'],
        ['label' => 'إجمالي المبلغ', 'value' => number_format($stats['total_amount'] ?? 0, 2) . ' ' . currency_symbol(), 'icon' => 'fa-coins', 'tone' => 'muted', 'note' => 'قيمة المكتملة', 'raw' => true],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $statusBadges = [
        'completed' => ['label' => 'مكتملة', 'classes' => 'bg-accent-soft text-accent'],
        'pending' => ['label' => 'معلقة', 'classes' => 'bg-metal/15 text-metal'],
        'failed' => ['label' => 'فاشلة', 'classes' => 'bg-canvas-muted text-muted'],
        'cancelled' => ['label' => 'ملغاة', 'classes' => 'bg-canvas-muted text-muted'],
    ];
    $typeBadges = [
        'credit' => ['label' => 'إيراد', 'classes' => 'bg-accent-soft text-accent', 'icon' => 'fas fa-arrow-down'],
        'debit' => ['label' => 'مصروف', 'classes' => 'bg-metal/15 text-metal', 'icon' => 'fas fa-arrow-up'],
        'income' => ['label' => 'إيراد', 'classes' => 'bg-accent-soft text-accent', 'icon' => 'fas fa-arrow-down'],
        'expense' => ['label' => 'مصروف', 'classes' => 'bg-metal/15 text-metal', 'icon' => 'fas fa-arrow-up'],
        'transfer' => ['label' => 'تحويل', 'classes' => 'bg-metal/15 text-metal', 'icon' => 'fas fa-exchange-alt'],
        'refund' => ['label' => 'استرداد', 'classes' => 'bg-canvas-muted text-muted', 'icon' => 'fas fa-undo'],
        'deposit' => ['label' => 'إيداع', 'classes' => 'bg-accent-soft text-accent', 'icon' => 'fas fa-arrow-down'],
        'withdrawal' => ['label' => 'سحب', 'classes' => 'bg-metal/15 text-metal', 'icon' => 'fas fa-arrow-up'],
        'payment' => ['label' => 'دفع', 'classes' => 'bg-accent-soft text-accent', 'icon' => 'fas fa-credit-card'],
        'commission' => ['label' => 'عمولة', 'classes' => 'bg-canvas-muted text-muted', 'icon' => 'fas fa-percentage'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحاسبة · المعاملات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة المعاملات المالية</h2>
            <p class="mt-1 text-sm text-muted">متابعة المعاملات والإيرادات والمصروفات</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.transactions.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                إضافة معاملة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">
                    @if(!empty($kpi['raw']))
                        {{ $kpi['value'] }}
                    @else
                        {{ number_format($kpi['value']) }}
                    @endif
                </p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">حسب النوع أو الحالة أو رقم المعاملة أو بيانات العميل</p>
        </div>
        <form method="GET" id="filterForm" action="{{ route('admin.transactions.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
            <div class="xl:col-span-2">
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}" maxlength="255" placeholder="رقم المعاملة، اسم العميل، هاتف…" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="type">النوع</label>
                <select id="type" name="type" class="{{ $fieldClass }}">
                    <option value="">جميع الأنواع</option>
                    <option value="credit" @selected(request('type') == 'credit')>إيراد</option>
                    <option value="debit" @selected(request('type') == 'debit')>مصروف</option>
                    <option value="income" @selected(request('type') == 'income')>إيراد</option>
                    <option value="expense" @selected(request('type') == 'expense')>مصروف</option>
                    <option value="transfer" @selected(request('type') == 'transfer')>تحويل</option>
                    <option value="refund" @selected(request('type') == 'refund')>استرداد</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">جميع الحالات</option>
                    <option value="completed" @selected(request('status') == 'completed')>مكتملة</option>
                    <option value="pending" @selected(request('status') == 'pending')>معلقة</option>
                    <option value="failed" @selected(request('status') == 'failed')>فاشلة</option>
                    <option value="cancelled" @selected(request('status') == 'cancelled')>ملغاة</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-2 xl:col-span-4">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    تطبيق
                </button>
                @if(request()->anyFilled(['search', 'type', 'status']))
                    <a href="{{ route('admin.transactions.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
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
                <h3 class="text-base font-semibold text-ink">قائمة المعاملات</h3>
                <p class="mt-0.5 text-xs text-muted">من الأحدث إلى الأقدم</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($transactions->total()) }} معاملة</span>
        </div>

        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">#</th>
                        <th class="px-4 py-3 text-start font-medium">رقم المعاملة</th>
                        <th class="px-4 py-3 text-start font-medium">العميل</th>
                        <th class="px-4 py-3 text-start font-medium">النوع</th>
                        <th class="px-4 py-3 text-start font-medium">المبلغ</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                        <th class="px-4 py-3 text-start font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($transactions as $transaction)
                        @php
                            $typeMeta = $typeBadges[$transaction->type] ?? ['label' => $transaction->type, 'classes' => 'bg-canvas-muted text-muted', 'icon' => 'fas fa-circle'];
                            $statusMeta = $statusBadges[$transaction->status] ?? ['label' => $transaction->status ?? '—', 'classes' => 'bg-canvas-muted text-muted'];
                            $isCredit = in_array($transaction->type, ['credit', 'income', 'deposit', 'payment']);
                            $isDebit = in_array($transaction->type, ['debit', 'expense', 'withdrawal']);
                        @endphp
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3 tabular-nums text-muted">{{ $transaction->id }}</td>
                            <td class="px-4 py-3 font-semibold text-ink">{{ $transaction->transaction_number ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink">{{ $transaction->user->name ?? '—' }}</p>
                                <p class="mt-0.5 text-[11px] text-muted">{{ $transaction->user->phone ?? $transaction->user->email ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium {{ $typeMeta['classes'] }}">
                                    <i class="{{ $typeMeta['icon'] ?? 'fas fa-circle' }} text-[10px]"></i>
                                    {{ $typeMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold tabular-nums {{ $isDebit ? 'text-metal' : ($isCredit ? 'text-accent' : 'text-ink') }}">
                                {{ $isDebit ? '-' : ($isCredit ? '+' : '') }}{{ number_format($transaction->amount, 2) }}
                                <span class="text-xs font-normal text-muted">$</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $statusMeta['classes'] }}">{{ $statusMeta['label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs tabular-nums text-muted">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent" title="عرض">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا توجد معاملات</p>
                                <p class="mt-1 text-xs text-muted">لم يتم تسجيل أي معاملات أو لا توجد نتائج للفلتر.</p>
                                <a href="{{ route('admin.transactions.create') }}" class="btn-press mt-4 inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                                    <i class="fas fa-plus text-xs"></i>
                                    إضافة معاملة
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $transactions->appends(request()->query())->links() }}</div>
        @endif
    </article>
</div>

@push('scripts')
<script>
(function() {
    var filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            var q = this.querySelector('input[name="search"]');
            if (q) q.value = (q.value || '').replace(/[<>'"&]/g, '').trim();
        });
    }
})();
</script>
@endpush
@endsection
