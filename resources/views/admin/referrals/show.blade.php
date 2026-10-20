@extends('layouts.admin')

@section('title', 'تفاصيل الإحالة - ' . config('app.name'))
@section('page_title', 'تفاصيل الإحالة')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · الإحالات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تفاصيل الإحالة</h2>
            <p class="mt-1 text-sm text-muted">معلومات تفصيلية عن الإحالة</p>
        </div>
        <a href="{{ route('admin.referrals.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            العودة
        </a>
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-user-check text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">معلومات المحيل</h3>
                        <p class="mt-0.5 text-xs text-muted">بيانات المستخدم المُحيل</p>
                    </div>
                </div>
            </div>
            <dl class="divide-y divide-line">
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">الاسم</dt>
                    <dd class="text-sm font-semibold text-ink">{{ $referral->referrer->name ?? 'غير معروف' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">رقم الهاتف</dt>
                    <dd class="text-sm font-semibold tabular-nums text-ink">{{ $referral->referrer->phone ?? 'N/A' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">البريد الإلكتروني</dt>
                    <dd class="text-sm font-semibold text-ink">{{ $referral->referrer->email ?? 'N/A' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">كود الإحالة</dt>
                    <dd class="font-mono text-sm font-bold text-accent">{{ $referral->referrer->referral_code ?? '-' }}</dd>
                </div>
            </dl>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                        <i class="fas fa-user-plus text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">معلومات المحال</h3>
                        <p class="mt-0.5 text-xs text-muted">بيانات المستخدم المحال</p>
                    </div>
                </div>
            </div>
            <dl class="divide-y divide-line">
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">الاسم</dt>
                    <dd class="text-sm font-semibold text-ink">{{ $referral->referred->name ?? 'غير معروف' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">رقم الهاتف</dt>
                    <dd class="text-sm font-semibold tabular-nums text-ink">{{ $referral->referred->phone ?? 'N/A' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">البريد الإلكتروني</dt>
                    <dd class="text-sm font-semibold text-ink">{{ $referral->referred->email ?? 'N/A' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">تاريخ التسجيل</dt>
                    <dd class="text-sm font-semibold text-ink">{{ $referral->referred->created_at->format('d/m/Y') }}</dd>
                </div>
            </dl>
        </article>
    </div>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-info-circle text-sm"></i>
                </span>
                <div>
                    <h3 class="text-base font-semibold text-ink">تفاصيل الإحالة</h3>
                    <p class="mt-0.5 text-xs text-muted">البرنامج، الحالة، والمبالغ</p>
                </div>
            </div>
        </div>
        <dl class="grid grid-cols-1 gap-0 sm:grid-cols-2">
            <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5">
                <dt class="text-sm text-muted">البرنامج</dt>
                <dd class="text-sm font-semibold text-ink">{{ $referral->referralProgram->name ?? '-' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5">
                <dt class="text-sm text-muted">كود الإحالة</dt>
                <dd class="font-mono text-sm font-bold text-ink">{{ $referral->referral_code ?? $referral->code ?? '-' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5">
                <dt class="text-sm text-muted">الحالة</dt>
                <dd>
                    <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold
                        @if($referral->status == 'completed') border-emerald-100 bg-emerald-50 text-emerald-700
                        @elseif($referral->status == 'pending') border-amber-100 bg-amber-50 text-amber-800
                        @else border-rose-100 bg-rose-50 text-rose-700
                        @endif">
                        <span class="size-1.5 rounded-full bg-current"></span>
                        @if($referral->status == 'completed') مكتملة
                        @elseif($referral->status == 'pending') قيد الانتظار
                        @else ملغاة
                        @endif
                    </span>
                </dd>
            </div>
            <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5">
                <dt class="text-sm text-muted">تاريخ الإحالة</dt>
                <dd class="text-sm font-semibold text-ink">{{ $referral->created_at->format('d/m/Y H:i') }}</dd>
            </div>
            @if($referral->completed_at)
                <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">تاريخ الإكمال</dt>
                    <dd class="text-sm font-semibold text-ink">{{ $referral->completed_at->format('d/m/Y H:i') }}</dd>
                </div>
            @endif
            <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5">
                <dt class="text-sm text-muted">الخصم المطبق</dt>
                <dd class="text-sm font-bold tabular-nums text-ink">{{ number_format($referral->discount_amount ?? 0, 2) }} {{ currency_symbol() }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5">
                <dt class="text-sm text-muted">عدد مرات استخدام الخصم</dt>
                <dd class="text-sm font-semibold text-ink">{{ $referral->discount_used_count ?? 0 }} / {{ $referral->referralProgram->max_discount_uses_per_referred ?? 1 }}</dd>
            </div>
            @if($referral->discount_expires_at)
                <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">انتهاء صلاحية الخصم</dt>
                    <dd class="text-sm font-semibold {{ $referral->discount_expires_at < now() ? 'text-rose-600' : 'text-ink' }}">
                        {{ $referral->discount_expires_at->format('d/m/Y H:i') }}
                    </dd>
                </div>
            @endif
            <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5">
                <dt class="text-sm text-muted">المكافأة (مالية)</dt>
                <dd class="text-sm font-bold tabular-nums text-emerald-700">{{ number_format($referral->reward_amount ?? 0, 2) }} {{ currency_symbol() }}</dd>
            </div>
            @if(($referral->reward_points ?? 0) > 0)
                <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">نقاط المكافأة</dt>
                    <dd class="text-sm font-bold tabular-nums text-amber-700">{{ number_format($referral->reward_points) }}</dd>
                </div>
            @endif
            @if($referral->autoCoupon)
                <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5 sm:col-span-2">
                    <dt class="text-sm text-muted">الكوبون التلقائي</dt>
                    <dd class="font-mono text-sm font-bold text-ink">{{ $referral->autoCoupon->code }}</dd>
                </div>
            @endif
        </dl>
    </article>

    @if($referral->invoice)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-file-invoice text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">الفاتورة المرتبطة</h3>
                        <p class="mt-0.5 font-mono text-xs text-muted">{{ $referral->invoice->invoice_number }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.invoices.show', $referral->invoice) }}"
                   class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-eye text-xs"></i>
                    عرض الفاتورة
                </a>
            </div>
        </article>
    @endif
</div>
@endsection
