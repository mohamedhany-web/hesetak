@extends('layouts.admin')

@section('title', 'تفاصيل برنامج الإحالات - ' . config('app.name'))
@section('page_title', 'تفاصيل برنامج الإحالات')

@section('content')
@php
    $kpiCards = [
        ['label' => 'إجمالي الإحالات', 'value' => number_format($stats['total_referrals']), 'icon' => 'fa-users'],
        ['label' => 'مكتملة', 'value' => number_format($stats['completed_referrals']), 'icon' => 'fa-check-circle', 'tone' => 'emerald'],
        ['label' => 'قيد الانتظار', 'value' => number_format($stats['pending_referrals']), 'icon' => 'fa-hourglass-half', 'tone' => 'amber'],
        ['label' => 'إجمالي الخصومات', 'value' => number_format($stats['total_discount_given'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-tag'],
        ['label' => 'إجمالي المكافآت', 'value' => number_format($stats['total_rewards_given'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-gift', 'tone' => 'emerald'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · الإحالات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $referralProgram->name }}</h2>
            @if($referralProgram->description)
                <p class="mt-1 max-w-2xl text-sm text-muted">{{ $referralProgram->description }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            @if($referralProgram->is_default)
                <span class="inline-flex items-center gap-1.5 rounded-xl border border-line bg-accent-soft px-3 py-2 text-xs font-semibold text-accent">
                    <span class="size-1.5 rounded-full bg-current"></span>
                    البرنامج الافتراضي للتسجيل
                </span>
            @elseif($referralProgram->is_active && $referralProgram->isValid())
                <form action="{{ route('admin.referral-programs.set-default', $referralProgram) }}" method="POST" onsubmit="return confirm('تعيين هذا البرنامج كافتراضي؟');">
                    @csrf
                    <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-accent transition hover:bg-accent-soft">
                        تعيين كافتراضي
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.referral-programs.edit', $referralProgram) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-pen text-xs"></i>
                تعديل
            </a>
            <a href="{{ route('admin.referral-programs.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة
            </a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach($kpiCards as $card)
            @php
                $valueClass = match ($card['tone'] ?? 'ink') {
                    'amber' => 'text-amber-700',
                    'emerald' => 'text-emerald-700',
                    default => 'text-ink',
                };
            @endphp
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas {{ $card['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">{{ $card['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight {{ $valueClass }}">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-info-circle text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">تفاصيل البرنامج</h3>
                        <p class="mt-0.5 text-xs text-muted">قواعد الخصم والمكافآت</p>
                    </div>
                </div>
            </div>
            <dl class="divide-y divide-line">
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">افتراضي للتسجيل</dt>
                    <dd class="text-sm font-semibold {{ $referralProgram->is_default ? 'text-accent' : 'text-muted' }}">{{ $referralProgram->is_default ? 'نعم' : 'لا' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">الحالة</dt>
                    <dd>
                        <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold
                            @if($referralProgram->is_active && $referralProgram->isValid()) border-emerald-100 bg-emerald-50 text-emerald-700
                            @else border-line bg-canvas text-muted
                            @endif">
                            <span class="size-1.5 rounded-full bg-current"></span>
                            @if($referralProgram->is_active && $referralProgram->isValid()) نشط @else معطل @endif
                        </span>
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">وضع المكافأة</dt>
                    <dd class="text-sm font-semibold text-ink">{{ $referralProgram->usesCredits() ? 'رصيد حصص' : 'خصم / كوبون' }}</dd>
                </div>
                @if($referralProgram->usesCredits())
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                        <dt class="text-sm text-muted">نطاق الرصيد</dt>
                        <dd class="text-sm font-semibold text-ink">{{ $referralProgram->creditScopeLabel() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                        <dt class="text-sm text-muted">حصص المدعوّة</dt>
                        <dd class="text-sm font-bold tabular-nums text-ink">{{ (int) $referralProgram->referred_credit_units }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                        <dt class="text-sm text-muted">حصص المحيلة</dt>
                        <dd class="text-sm font-bold tabular-nums text-ink">{{ (int) $referralProgram->referrer_credit_units }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                        <dt class="text-sm text-muted">حصص مُنحت للمدعوّات</dt>
                        <dd class="text-sm font-semibold tabular-nums text-emerald-700">{{ number_format($stats['total_credits_referred'] ?? 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                        <dt class="text-sm text-muted">حصص مُنحت للمحيلات</dt>
                        <dd class="text-sm font-semibold tabular-nums text-emerald-700">{{ number_format($stats['total_credits_referrer'] ?? 0) }}</dd>
                    </div>
                @else
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">نوع الخصم للمحال</dt>
                    <dd class="text-sm font-semibold text-ink">{{ $referralProgram->discount_type == 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">قيمة الخصم</dt>
                    <dd class="text-sm font-bold tabular-nums text-ink">
                        @if($referralProgram->discount_type == 'percentage')
                            {{ number_format($referralProgram->discount_value, 0) }}%
                        @else
                            {{ number_format($referralProgram->discount_value, 2) }} $
                        @endif
                    </dd>
                </div>
                @if($referralProgram->maximum_discount)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                        <dt class="text-sm text-muted">الحد الأقصى للخصم</dt>
                        <dd class="text-sm font-semibold tabular-nums text-ink">{{ number_format($referralProgram->maximum_discount, 2) }} $</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">مدة صلاحية الخصم</dt>
                    <dd class="text-sm font-semibold text-ink">{{ $referralProgram->discount_valid_days }} يوم</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">الحد الأقصى لاستخدام الخصم</dt>
                    <dd class="text-sm font-semibold text-ink">{{ $referralProgram->max_discount_uses_per_referred }} مرة</dd>
                </div>
                @if($referralProgram->referrer_reward_value)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                        <dt class="text-sm text-muted">مكافأة المحيل</dt>
                        <dd class="text-sm font-bold text-emerald-700">
                            @if($referralProgram->referrer_reward_type == 'percentage')
                                {{ number_format($referralProgram->referrer_reward_value, 0) }}%
                            @elseif($referralProgram->referrer_reward_type == 'points')
                                {{ number_format($referralProgram->referrer_reward_value, 0) }} نقطة
                            @else
                                {{ number_format($referralProgram->referrer_reward_value, 2) }} $
                            @endif
                        </dd>
                    </div>
                @endif
                @endif
            </dl>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-list text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">آخر الإحالات</h3>
                        <p class="mt-0.5 text-xs text-muted">أحدث 10 إحالات لهذا البرنامج</p>
                    </div>
                </div>
            </div>
            @if($referralProgram->referrals->count() > 0)
                <div class="max-h-96 space-y-3 overflow-y-auto p-4 sm:p-5">
                    @foreach($referralProgram->referrals()->latest()->take(10)->get() as $referral)
                        <div class="rounded-xl border border-line bg-canvas p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-ink">{{ $referral->referred->name ?? 'غير معروف' }}</p>
                                    <p class="mt-0.5 text-xs text-muted">محال من: {{ $referral->referrer->name ?? 'غير معروف' }}</p>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold
                                    @if($referral->status == 'completed') border-emerald-100 bg-emerald-50 text-emerald-700
                                    @else border-amber-100 bg-amber-50 text-amber-800
                                    @endif">
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                    {{ $referral->status == 'completed' ? 'مكتملة' : 'قيد الانتظار' }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs text-muted">
                                <span>
                                    @if($referralProgram->usesCredits())
                                        حصص: <span class="font-semibold tabular-nums text-ink">{{ (int) $referral->referred_units_granted + (int) $referral->referrer_units_granted }}</span>
                                    @else
                                        الخصم: <span class="font-semibold tabular-nums text-ink">{{ number_format($referral->discount_amount ?? 0, 2) }} $</span>
                                    @endif
                                </span>
                                <span>{{ $referral->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-line px-4 py-3 text-center sm:px-5">
                    <a href="{{ route('admin.referrals.index', ['program_id' => $referralProgram->id]) }}"
                       class="text-sm font-medium text-accent hover:underline">
                        عرض جميع الإحالات <i class="fas fa-arrow-left mr-1 text-xs"></i>
                    </a>
                </div>
            @else
                <div class="p-8 text-center">
                    <div class="mx-auto inline-flex size-12 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-user-friends text-lg"></i>
                    </div>
                    <p class="mt-3 text-sm text-muted">لا توجد إحالات لهذا البرنامج</p>
                </div>
            @endif
        </article>
    </div>
</div>
@endsection
