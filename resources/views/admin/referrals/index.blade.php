@extends('layouts.admin')

@section('title', 'الإحالات - ' . config('app.name'))
@section('page_title', 'إدارة الإحالات')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';

    $kpiCards = [
        ['label' => 'إجمالي الإحالات', 'value' => number_format($stats['total']), 'icon' => 'fa-users', 'note' => 'جميع الإحالات المسجلة'],
        ['label' => 'مكتملة', 'value' => number_format($stats['completed']), 'icon' => 'fa-check-circle', 'note' => 'تم الحصول على المكافأة', 'tone' => 'emerald'],
        ['label' => 'قيد الانتظار', 'value' => number_format($stats['pending']), 'icon' => 'fa-hourglass-half', 'note' => 'في انتظار الشراء', 'tone' => 'amber'],
        ['label' => 'إجمالي المكافآت', 'value' => number_format($stats['total_rewards'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-gift', 'note' => 'مكافآت من الإحالات'],
        ['label' => 'إجمالي الخصومات', 'value' => number_format($stats['total_discounts'], 2) . ' ' . currency_symbol(), 'icon' => 'fa-tag', 'note' => 'خصومات مطبقة'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · الإحالات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة الإحالات</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">عرض وإدارة جميع الإحالات والعمولات</p>
        </div>
        <a href="{{ route('admin.referral-programs.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-accent-soft hover:text-accent">
            <i class="fas fa-gift text-xs"></i>
            برامج الإحالات
        </a>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
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
                <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight {{ $valueClass }}">{{ $card['value'] }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $card['note'] }}</p>
            </article>
        @endforeach
    </section>

    <form method="GET" action="{{ route('admin.referrals.index') }}"
          class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-filter text-sm"></i>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-ink">فلترة وبحث الإحالات</h3>
                <p class="text-xs text-muted">تصفية حسب الحالة، البرنامج، أو التاريخ</p>
            </div>
        </div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <div>
                <label class="{{ $labelClass }}">البحث</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="اسم، هاتف، كود..."
                       class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">الحالة</label>
                <select name="status" class="{{ $fieldClass }}">
                    <option value="">جميع الحالات</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتملة</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">البرنامج</label>
                <select name="program_id" class="{{ $fieldClass }}">
                    <option value="">جميع البرامج</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">من تاريخ</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="{{ $fieldClass }}">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="btn-press inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-search text-xs"></i>
                    بحث
                </button>
                @if(request()->anyFilled(['search', 'status', 'program_id', 'date_from', 'date_to']))
                    <a href="{{ route('admin.referrals.index') }}"
                       class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line text-muted hover:bg-accent-soft hover:text-accent"
                       title="مسح الفلتر">
                        <i class="fas fa-times text-xs"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    @if($referrals->count() > 0)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                <div>
                    <h3 class="text-sm font-semibold text-ink">قائمة الإحالات</h3>
                    <p class="text-xs text-muted"><span class="font-semibold tabular-nums text-accent">{{ $referrals->total() }}</span> إحالة</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">المحيل</th>
                            <th class="px-4 py-3 text-start font-medium">المحال</th>
                            <th class="px-4 py-3 text-start font-medium">البرنامج</th>
                            <th class="px-4 py-3 text-start font-medium">كود الإحالة</th>
                            <th class="px-4 py-3 text-start font-medium">الحالة</th>
                            <th class="px-4 py-3 text-start font-medium">الخصم المطبق</th>
                            <th class="px-4 py-3 text-start font-medium">المكافأة</th>
                            <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                            <th class="px-4 py-3 text-end font-medium">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($referrals as $referral)
                            <tr class="hover:bg-canvas/60">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-sm font-bold text-accent">
                                            {{ mb_substr($referral->referrer->name ?? 'N', 0, 1, 'UTF-8') }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink">{{ $referral->referrer->name ?? 'غير معروف' }}</p>
                                            <p class="truncate text-xs text-muted tabular-nums">{{ $referral->referrer->phone ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-sm font-bold text-emerald-700">
                                            {{ mb_substr($referral->referred->name ?? 'N', 0, 1, 'UTF-8') }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink">{{ $referral->referred->name ?? 'غير معروف' }}</p>
                                            <p class="truncate text-xs text-muted tabular-nums">{{ $referral->referred->phone ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-ink">{{ $referral->referralProgram->name ?? '-' }}</td>
                                <td class="px-4 py-3 font-mono font-semibold text-ink">{{ $referral->referral_code ?? $referral->code ?? '-' }}</td>
                                <td class="px-4 py-3">
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
                                </td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-ink">
                                    {{ number_format($referral->discount_amount ?? 0, 2) }} $
                                </td>
                                <td class="px-4 py-3 font-bold tabular-nums text-emerald-700">
                                    {{ number_format($referral->reward_amount ?? 0, 2) }} $
                                </td>
                                <td class="px-4 py-3 text-muted">
                                    {{ $referral->created_at->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('admin.referrals.show', $referral) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl text-accent transition hover:bg-accent-soft"
                                       title="عرض">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($referrals->hasPages())
                <div class="border-t border-line px-4 py-3">
                    {{ $referrals->links() }}
                </div>
            @endif
        </article>
    @else
        <article class="rounded-2xl border border-line bg-surface p-12 text-center shadow-soft">
            <div class="mx-auto inline-flex size-16 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-user-friends text-2xl"></i>
            </div>
            <p class="mt-4 text-lg font-semibold text-ink">لا توجد إحالات</p>
            <p class="mt-1 text-sm text-muted">ابدأ بإنشاء برنامج إحالات لتفعيل نظام الإحالات</p>
            <a href="{{ route('admin.referral-programs.index') }}"
               class="btn-press mt-6 inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-gift text-xs"></i>
                إنشاء برنامج إحالات
            </a>
        </article>
    @endif
</div>
@endsection
