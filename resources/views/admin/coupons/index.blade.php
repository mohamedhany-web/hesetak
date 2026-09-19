@extends('layouts.admin')

@section('title', 'الكوبونات والخصومات - ' . config('app.name'))
@section('page_title', 'الكوبونات والخصومات')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · العروض والخصومات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">الكوبونات والخصومات</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">إدارة أكواد الخصم والاستخدامات</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.marketing.student-wallet-credit.create') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-accent-soft hover:text-accent">
                <i class="fas fa-wallet text-xs"></i>
                رصيد محفظة طالب
            </a>
            <a href="{{ route('admin.coupons.create') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-plus text-xs"></i>
                إضافة كوبون جديد
            </a>
        </div>
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
        <section class="grid gap-3 sm:grid-cols-3">
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-ticket-alt text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">إجمالي الكوبونات</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $stats['total'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-check-circle text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">نشطة</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ $stats['active'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-clock text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">منتهية/غير نشطة</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ $stats['expired'] ?? 0 }}</p>
            </article>
        </section>
    @endif

    <form method="GET" action="{{ route('admin.coupons.index') }}" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-1">
                <label class="mb-1.5 block text-xs font-medium text-muted">بحث</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="كود أو عنوان..."
                       class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted">الحالة</label>
                <select name="status" class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    <option value="">الكل</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>منتهي</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-press inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-filter text-xs"></i> تطبيق
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.coupons.index') }}" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line text-muted transition hover:bg-accent-soft hover:text-accent" title="إعادة تعيين">
                        <i class="fas fa-times text-xs"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    @if(isset($coupons) && $coupons->count() > 0)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                <div>
                    <h3 class="text-sm font-semibold text-ink">قائمة الكوبونات</h3>
                    <p class="text-xs text-muted"><span class="font-semibold tabular-nums text-accent">{{ number_format($coupons->total()) }}</span> نتيجة</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">الكود</th>
                            <th class="px-4 py-3 text-start font-medium">العنوان</th>
                            <th class="px-4 py-3 text-start font-medium">نوع الخصم</th>
                            <th class="px-4 py-3 text-start font-medium">القيمة</th>
                            <th class="px-4 py-3 text-start font-medium">الاستخدامات</th>
                            <th class="px-4 py-3 text-start font-medium">الحالة</th>
                            <th class="px-4 py-3 text-end font-medium">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($coupons as $coupon)
                            <tr class="hover:bg-canvas/60">
                                <td class="px-4 py-3 font-mono font-semibold text-ink">{{ $coupon->code }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $coupon->title ?? $coupon->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-ink-soft">
                                    {{ $coupon->discount_type == 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت' }}
                                </td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-ink">
                                    {{ $coupon->discount_type == 'percentage' ? $coupon->discount_value . '%' : number_format($coupon->discount_value, 2) . ' ' . currency_symbol() }}
                                </td>
                                <td class="px-4 py-3 tabular-nums text-muted">
                                    <span title="استخدامات فعلية">{{ $coupon->used_count ?? ($coupon->usages_count ?? 0) }}</span>
                                    @if($coupon->usage_limit)
                                        / {{ $coupon->usage_limit }}
                                    @else
                                        <span class="text-muted">/ ∞</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $isActive = $coupon->is_active && (!$coupon->expires_at || $coupon->expires_at >= now());
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold
                                        {{ $isActive ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-line bg-canvas text-muted' }}">
                                        <span class="size-1.5 rounded-full bg-current"></span>
                                        {{ $isActive ? 'نشط' : 'منتهي' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ route('admin.coupons.show', $coupon) }}" class="inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink transition hover:bg-accent-soft hover:text-accent">عرض</a>
                                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink transition hover:bg-accent-soft hover:text-accent">تعديل</a>
                                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="inline" onsubmit="return confirm('حذف هذا الكوبون؟');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-rose-100 px-3 text-xs font-medium text-rose-700 transition hover:bg-rose-50">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-line bg-canvas/40 px-4 py-3">
                {{ $coupons->links() }}
            </div>
        </article>
    @else
        <article class="rounded-2xl border border-line bg-surface p-12 text-center shadow-soft">
            <div class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-ticket-alt text-2xl"></i>
            </div>
            <p class="mt-4 font-semibold text-ink">لا توجد كوبونات</p>
            <p class="mt-1 text-sm text-muted">أضف أول كوبون أو غيّر معايير البحث</p>
            <a href="{{ route('admin.coupons.create') }}" class="btn-press mt-5 inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-plus text-xs"></i> إضافة كوبون
            </a>
        </article>
    @endif
</div>
@endsection
