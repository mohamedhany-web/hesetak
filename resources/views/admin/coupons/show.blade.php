@extends('layouts.admin')

@section('title', 'تفاصيل الكوبون: ' . $coupon->code . ' - ' . config('app.name'))
@section('page_title', 'تفاصيل الكوبون')

@section('content')
@php
    $isActive = $coupon->is_active && (!$coupon->expires_at || $coupon->expires_at >= now());
    $scopeLabel = match ($coupon->applicable_to ?? 'all') {
        'courses' => 'كورسات محددة',
        'specific' => 'كورسات محددة + مستخدمون (إن وُجد)',
        'subscriptions' => 'الاشتراكات فقط',
        default => 'جميع الكورسات',
    };
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · العروض والخصومات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px] font-mono">{{ $coupon->code }}</h2>
            <p class="mt-1 text-sm text-muted">{{ $coupon->title ?? $coupon->name }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold
                    {{ $isActive ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-line bg-canvas text-muted' }}">
                    <span class="size-1.5 rounded-full bg-current"></span>
                    {{ $isActive ? 'نشط' : 'منتهي أو غير نشط' }}
                </span>
                @if($coupon->is_public ?? true)
                    <span class="inline-flex items-center rounded-lg border border-accent/20 bg-accent-soft px-2.5 py-1 text-[11px] font-semibold text-accent">عام</span>
                @else
                    <span class="inline-flex items-center rounded-lg border border-line bg-canvas px-2.5 py-1 text-[11px] font-semibold text-muted">خاص</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.coupon-commissions.index', array_filter(['beneficiary_id' => $coupon->beneficiary_user_id])) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 text-sm font-medium text-amber-800 transition hover:bg-amber-100">
                <i class="fas fa-coins text-xs"></i> عمولات التسويق
            </a>
            <a href="{{ route('admin.coupons.edit', $coupon) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-edit text-xs"></i> تعديل
            </a>
            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="inline" onsubmit="return confirm('حذف هذا الكوبون؟');">
                @csrf @method('DELETE')
                <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-rose-200 px-4 text-sm font-medium text-rose-700 transition hover:bg-rose-50">حذف</button>
            </form>
            <a href="{{ route('admin.coupons.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i> رجوع
            </a>
        </div>
    </section>

    <div class="grid gap-5 md:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-percent text-sm"></i>
                    </span>
                    <h3 class="text-base font-semibold text-ink">تفاصيل الخصم</h3>
                </div>
            </div>
            <dl class="space-y-4 p-4 sm:p-5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">نوع الخصم</dt>
                    <dd class="font-semibold text-ink">{{ $coupon->discount_type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">القيمة</dt>
                    <dd class="font-semibold tabular-nums text-ink">{{ $coupon->discount_type === 'percentage' ? $coupon->discount_value.'%' : number_format($coupon->discount_value, 2) . ' ' . currency_symbol() }}</dd>
                </div>
                @if($coupon->minimum_amount)
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">الحد الأدنى للطلب</dt>
                        <dd class="font-mono tabular-nums text-ink">{{ number_format($coupon->minimum_amount, 2) }} $</dd>
                    </div>
                @endif
                @if($coupon->maximum_discount)
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">الحد الأقصى للخصم</dt>
                        <dd class="font-mono tabular-nums text-ink">{{ number_format($coupon->maximum_discount, 2) }} $</dd>
                    </div>
                @endif
            </dl>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-chart-line text-sm"></i>
                    </span>
                    <h3 class="text-base font-semibold text-ink">الاستخدام</h3>
                </div>
            </div>
            <dl class="space-y-4 p-4 sm:p-5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">عدد الاستخدامات</dt>
                    <dd class="font-mono font-semibold tabular-nums text-ink">
                        {{ $coupon->used_count ?? 0 }}
                        @if($coupon->usage_limit) / {{ $coupon->usage_limit }} @else <span class="text-muted">/ ∞</span> @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">الحد لكل مستخدم</dt>
                    <dd class="tabular-nums text-ink">{{ $coupon->usage_limit_per_user ?? 1 }}</dd>
                </div>
                @if($coupon->starts_at)
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">من</dt>
                        <dd class="tabular-nums text-ink">{{ $coupon->starts_at->format('Y-m-d') }}</dd>
                    </div>
                @endif
                @if($coupon->expires_at)
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">إلى</dt>
                        <dd class="tabular-nums text-ink">{{ $coupon->expires_at->format('Y-m-d') }}</dd>
                    </div>
                @endif
            </dl>
        </article>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-bullseye text-sm"></i>
                    </span>
                    <h3 class="text-base font-semibold text-ink">النطاق والمستخدمون</h3>
                </div>
            </div>
            <dl class="space-y-4 p-4 sm:p-5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">نطاق الكورسات</dt>
                    <dd class="font-semibold text-ink">{{ $scopeLabel }}</dd>
                </div>
                @if(isset($scopedCourses) && $scopedCourses->count() > 0)
                    <div>
                        <dt class="mb-2 text-muted">الكورسات المحددة</dt>
                        <dd class="space-y-1">
                            @foreach($scopedCourses as $sc)
                                <div class="rounded-lg bg-canvas px-2 py-1 text-xs font-mono text-ink">
                                    {{ $sc->title }} <span class="text-muted">#{{ $sc->id }}</span>
                                </div>
                            @endforeach
                        </dd>
                    </div>
                @endif
                @if(is_array($coupon->applicable_user_ids) && count($coupon->applicable_user_ids) > 0)
                    <div class="flex flex-wrap justify-between gap-4">
                        <dt class="text-muted">مستخدمون مسموح لهم</dt>
                        <dd class="font-mono text-xs text-ink">{{ implode(', ', $coupon->applicable_user_ids) }}</dd>
                    </div>
                @else
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">تقييد المستخدمين</dt>
                        <dd class="text-ink">لا يوجد (أي مستخدم يحقق الشروط)</dd>
                    </div>
                @endif
            </dl>
        </article>

        <article class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/40 shadow-soft">
            <div class="border-b border-amber-200 px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <i class="fas fa-coins text-sm"></i>
                    </span>
                    <h3 class="text-base font-semibold text-ink">عمولة التسويق</h3>
                </div>
            </div>
            @if($coupon->beneficiary_user_id && $coupon->commission_percent)
                <dl class="space-y-4 p-4 sm:p-5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">المستفيد</dt>
                        <dd class="font-semibold text-ink">
                            {{ $coupon->beneficiary->name ?? '—' }}
                            <span class="font-mono text-xs text-muted">#{{ $coupon->beneficiary_user_id }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">النسبة</dt>
                        <dd class="font-semibold tabular-nums text-ink">{{ $coupon->commission_percent }}%</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">القاعدة</dt>
                        <dd class="text-ink">{{ ($coupon->commission_on ?? 'final_paid') === 'original_price' ? 'السعر الأصلي' : 'المبلغ النهائي بعد الخصم' }}</dd>
                    </div>
                </dl>
            @else
                <p class="p-4 sm:p-5 text-sm text-muted">لا توجد عمولة مسجّلة لهذا الكوبون.</p>
            @endif
        </article>
    </div>

    @if($coupon->description)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">الوصف</h3>
            </div>
            <p class="p-4 sm:p-5 text-sm leading-relaxed text-ink-soft">{{ $coupon->description }}</p>
        </article>
    @endif

    @if($coupon->usages && $coupon->usages->count() > 0)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">سجل الاستخدامات</h3>
                <p class="mt-0.5 text-xs text-muted">{{ $coupon->usages->count() }} استخدام</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">المستخدم</th>
                            <th class="px-4 py-3 text-start font-medium">الطلب</th>
                            <th class="px-4 py-3 text-start font-medium">مبلغ الخصم</th>
                            <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($coupon->usages as $usage)
                            <tr class="hover:bg-canvas/60">
                                <td class="px-4 py-3 text-ink">{{ $usage->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono">
                                    @if($usage->order_id)
                                        <a href="{{ route('admin.orders.show', $usage->order_id) }}" class="font-medium text-accent hover:underline">#{{ $usage->order_id }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono tabular-nums text-ink">{{ number_format($usage->discount_amount ?? 0, 2) }} $</td>
                                <td class="px-4 py-3 tabular-nums text-muted">{{ $usage->created_at ? $usage->created_at->format('Y-m-d H:i') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif
</div>
@endsection
