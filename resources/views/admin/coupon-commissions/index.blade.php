@extends('layouts.admin')

@section('title', 'عمولات كوبونات التسويق - ' . config('app.name'))
@section('page_title', 'عمولات كوبونات التسويق')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · العروض والخصومات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">عمولات كوبونات التسويق</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">مستحقات مرتبطة بطلبات تم اعتمادها — سجّل مصروفاً تسويقياً ثم وافق عليه من المصروفات لإتمام التسوية</p>
        </div>
        <a href="{{ route('admin.coupons.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-ticket-alt text-xs"></i>
            الكوبونات
        </a>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">
            <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-soft">
            <i class="fas fa-exclamation-circle ml-1"></i> {{ session('error') }}
        </div>
    @endif

    @if(isset($stats))
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-clock text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">بانتظار مصروف</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-amber-700">{{ $stats['pending'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-file-invoice text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">مصروف معلق</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $stats['expense_pending'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-check-circle text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">مسوّاة</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ $stats['settled'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-coins text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">مبالغ غير مسوّاة {{ currency_unit() }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ format_money($stats['amount_pending'] ?? 0) }}</p>
            </article>
        </section>
    @endif

    <form method="GET" action="{{ route('admin.coupon-commissions.index') }}" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted">الحالة</label>
                <select name="status" class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    <option value="">الكل</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>بانتظار مصروف</option>
                    <option value="expense_pending" {{ request('status') === 'expense_pending' ? 'selected' : '' }}>مصروف معلق</option>
                    <option value="settled" {{ request('status') === 'settled' ? 'selected' : '' }}>مسوّاة</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted">معرّف المستفيد</label>
                <input type="number" name="beneficiary_id" min="1" value="{{ request('beneficiary_id') }}" placeholder="User ID"
                       class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-press inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-filter text-xs"></i> تطبيق
                </button>
                @if(request()->hasAny(['status', 'beneficiary_id']))
                    <a href="{{ route('admin.coupon-commissions.index') }}" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line text-muted transition hover:bg-accent-soft hover:text-accent" title="إعادة تعيين">
                        <i class="fas fa-times text-xs"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">قائمة المستحقات</h3>
                <p class="text-xs text-muted"><span class="font-semibold tabular-nums text-accent">{{ number_format($accruals->total()) }}</span> نتيجة</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">#</th>
                        <th class="px-4 py-3 text-start font-medium">الكوبون</th>
                        <th class="px-4 py-3 text-start font-medium">المستفيد</th>
                        <th class="px-4 py-3 text-start font-medium">الطلب</th>
                        <th class="px-4 py-3 text-start font-medium">القاعدة / النسبة</th>
                        <th class="px-4 py-3 text-start font-medium">العمولة</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-end font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($accruals as $row)
                        <tr class="hover:bg-canvas/60">
                            <td class="px-4 py-3 font-mono tabular-nums text-muted">{{ $row->id }}</td>
                            <td class="px-4 py-3">
                                <span class="font-mono font-semibold text-ink">{{ $row->coupon->code ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-ink">
                                {{ $row->beneficiary->name ?? '—' }}
                                <span class="text-xs font-mono text-muted">#{{ $row->beneficiary_user_id }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono">
                                @if($row->order_id)
                                    <a href="{{ route('admin.orders.show', $row->order_id) }}" class="font-medium text-accent hover:underline">#{{ $row->order_id }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono tabular-nums text-ink-soft">{{ number_format($row->base_amount_egp, 2) }} × {{ $row->commission_percent }}%</td>
                            <td class="px-4 py-3 font-mono font-semibold tabular-nums text-ink">{{ number_format($row->commission_amount_egp, 2) }} {{ currency_symbol() }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg border px-2.5 py-1 text-[11px] font-semibold
                                    @if($row->status === 'settled') border-emerald-100 bg-emerald-50 text-emerald-700
                                    @elseif($row->status === 'pending') border-amber-100 bg-amber-50 text-amber-800
                                    @elseif($row->status === 'expense_pending') border-line bg-accent-soft text-accent
                                    @else border-line bg-canvas text-muted @endif">
                                    {{ \App\Models\CouponCommissionAccrual::statusLabel($row->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if($row->status === \App\Models\CouponCommissionAccrual::STATUS_PENDING)
                                        <form method="POST" action="{{ route('admin.coupon-commissions.store-expense', $row) }}" onsubmit="return confirm('إنشاء مصروف تسويق معلق بهذا المبلغ؟');">
                                            @csrf
                                            <button type="submit" class="btn-press inline-flex h-8 items-center rounded-lg bg-amber-600 px-3 text-xs font-medium text-white hover:bg-amber-700">إنشاء مصروف</button>
                                        </form>
                                    @endif
                                    @if($row->expense_id)
                                        <a href="{{ route('admin.expenses.show', $row->expense_id) }}" class="inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink transition hover:bg-accent-soft hover:text-accent">المصروف</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-muted">
                                لا توجد مستحقات مسجّلة بعد. تُنشأ تلقائياً عند اعتماد طلب استخدم كوبوناً له مستفيد ونسبة عمولة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($accruals->hasPages())
            <div class="border-t border-line px-4 py-3">
                {{ $accruals->links() }}
            </div>
        @endif
    </article>
</div>
@endsection
