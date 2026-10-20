@extends('layouts.admin')

@section('title', 'برامج الإحالات - ' . config('app.name'))
@section('page_title', 'برامج الإحالات')

@section('content')
@php
    $kpiCards = [
        ['label' => 'إجمالي البرامج', 'value' => number_format($stats['total']), 'icon' => 'fa-list'],
        ['label' => 'البرامج النشطة', 'value' => number_format($stats['active']), 'icon' => 'fa-check-circle'],
        ['label' => 'البرامج المعطلة', 'value' => number_format($stats['inactive']), 'icon' => 'fa-times-circle'],
        ['label' => 'نشطة ضمن الفترة', 'value' => number_format($stats['valid_now'] ?? 0), 'icon' => 'fa-calendar-check'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · الإحالات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">برامج الإحالات</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">إدارة برامج الإحالات ورصيد الحصص للمدعوين</p>
        </div>
        <a href="{{ route('admin.referral-programs.create') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
            <i class="fas fa-plus text-xs"></i>
            برنامج جديد
        </a>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-soft" role="alert">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700"><i class="fas fa-exclamation-circle text-sm"></i></span>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpiCards as $card)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas {{ $card['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-ink">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    <div class="rounded-2xl border border-line bg-accent-soft px-4 py-3 text-sm text-ink shadow-soft">
        <strong class="font-semibold text-ink">البرنامج الافتراضي:</strong>
        <span class="text-muted"> يُستخدم عند تسجيل مستخدم جديد برابط إحالة لتحديد قواعد الخصم والمكافأة. إن لم يُحدَّد برنامج افتراضي، يُختار أحدث برنامج </span>
        <em class="font-medium text-accent">نشط وصالح</em>
        <span class="text-muted"> تلقائياً.</span>
    </div>

    @if($programs->count() > 0)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                <div>
                    <h3 class="text-sm font-semibold text-ink">قائمة البرامج</h3>
                    <p class="text-xs text-muted"><span class="font-semibold tabular-nums text-accent">{{ $programs->total() }}</span> برنامج</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">اسم البرنامج</th>
                            <th class="px-4 py-3 text-start font-medium">الافتراضي</th>
                            <th class="px-4 py-3 text-start font-medium">عدد الإحالات</th>
                            <th class="px-4 py-3 text-start font-medium">الحد الأقصى</th>
                            <th class="px-4 py-3 text-start font-medium">الوضع</th>
                            <th class="px-4 py-3 text-start font-medium">مكافأة الحصص</th>
                            <th class="px-4 py-3 text-start font-medium">النطاق</th>
                            <th class="px-4 py-3 text-start font-medium">الحالة</th>
                            <th class="px-4 py-3 text-end font-medium">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($programs as $program)
                            <tr class="hover:bg-canvas/60">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-ink">{{ $program->name }}</p>
                                    @if($program->description)
                                        <p class="mt-0.5 text-xs text-muted">{{ \Illuminate\Support\Str::limit($program->description, 50) }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($program->is_default)
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-accent-soft px-2.5 py-1 text-[11px] font-semibold text-accent">
                                            <span class="size-1.5 rounded-full bg-current"></span>
                                            افتراضي
                                        </span>
                                    @else
                                        <form action="{{ route('admin.referral-programs.set-default', $program) }}" method="POST" class="inline" onsubmit="return confirm('تعيين هذا البرنامج كافتراضي لإحالات التسجيل؟');">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-accent hover:underline disabled:opacity-40" {{ !$program->is_active || !$program->isValid() ? 'disabled' : '' }}>تعيين افتراضي</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono tabular-nums text-ink">
                                    {{ number_format($program->referrals_count ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-muted">
                                    @if($program->max_referrals_per_user)
                                        <span class="font-mono tabular-nums text-ink">{{ (int) $program->max_referrals_per_user }}/مستخدم</span>
                                    @else
                                        <span class="font-semibold text-amber-700">غير محدود</span>
                                    @endif
                                    @if($program->expires_at)
                                        <p class="mt-0.5">ينتهي {{ $program->expires_at->format('Y-m-d') }}</p>
                                    @else
                                        <p class="mt-0.5 text-amber-700">بلا انتهاء</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-lg border border-line bg-canvas px-2.5 py-1 text-[11px] font-semibold text-ink">
                                        {{ $program->usesCredits() ? 'رصيد حصص' : 'خصم' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($program->usesCredits())
                                        <p class="font-semibold tabular-nums text-ink">مدعوّة {{ (int) $program->referred_credit_units }} · محيلة {{ (int) $program->referrer_credit_units }}</p>
                                    @elseif($program->referrer_reward_value)
                                        <p class="font-semibold tabular-nums text-ink">
                                            @if($program->referrer_reward_type == 'percentage')
                                                {{ number_format($program->referrer_reward_value, 0) }}%
                                            @elseif($program->referrer_reward_type == 'points')
                                                {{ number_format($program->referrer_reward_value, 0) }} نقطة
                                            @else
                                                {{ number_format($program->referrer_reward_value, 2) }} {{ currency_symbol() }}
                                            @endif
                                        </p>
                                    @else
                                        <span class="text-xs text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-muted">
                                    {{ $program->usesCredits() ? $program->creditScopeLabel() : (($program->discount_valid_days ?? '—').' يوم') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold
                                        @if($program->is_active && $program->isValid()) border-emerald-100 bg-emerald-50 text-emerald-700
                                        @else border-line bg-canvas text-muted
                                        @endif">
                                        <span class="size-1.5 rounded-full bg-current"></span>
                                        @if($program->is_active && $program->isValid()) نشط @else معطل @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.referral-programs.show', $program) }}"
                                           class="inline-flex size-9 items-center justify-center rounded-xl text-accent transition hover:bg-accent-soft"
                                           title="عرض">
                                            <i class="fas fa-eye text-sm"></i>
                                        </a>
                                        <a href="{{ route('admin.referral-programs.edit', $program) }}"
                                           class="inline-flex size-9 items-center justify-center rounded-xl text-amber-700 transition hover:bg-amber-50"
                                           title="تعديل">
                                            <i class="fas fa-edit text-sm"></i>
                                        </a>
                                        <form action="{{ route('admin.referral-programs.destroy', $program) }}"
                                              method="POST"
                                              onsubmit="return confirm('هل أنت متأكد من حذف هذا البرنامج؟');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex size-9 items-center justify-center rounded-xl text-rose-600 transition hover:bg-rose-50"
                                                    title="حذف">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($programs->hasPages())
                <div class="border-t border-line px-4 py-3">
                    {{ $programs->links() }}
                </div>
            @endif
        </article>
    @else
        <article class="rounded-2xl border border-line bg-surface p-12 text-center shadow-soft">
            <div class="mx-auto inline-flex size-16 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-gift text-2xl"></i>
            </div>
            <p class="mt-4 text-lg font-semibold text-ink">لا توجد برامج إحالات</p>
            <p class="mt-1 text-sm text-muted">ابدأ بإنشاء برنامج إحالات جديد</p>
            <a href="{{ route('admin.referral-programs.create') }}"
               class="btn-press mt-6 inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-plus text-xs"></i>
                إنشاء برنامج جديد
            </a>
        </article>
    @endif
</div>
@endsection
