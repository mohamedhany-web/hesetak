@extends('layouts.admin')

@section('title', 'محافظ المنصة')
@section('page_title', 'محافظ المنصة (طلاب/معلمون)')

@section('content')
@php
    $tabs = [
        'students' => 'أرصدة باقات الطلاب',
        'instructors' => 'محافظ صرف المعلمين',
        'grant' => 'منح رصيد تسويقي',
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المالية · محافظ المنصة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">محافظ المنصة (طلاب / معلمون)</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">عرض أرصدة باقات الدروس الخاصة ومحافظ المعلمين دون تقييد بملكية الأدمن.</p>
        </div>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">باقات نشطة (دروس خاصة)</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($kpis['active_entitlements']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">وحدات متبقية</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($kpis['credits_remaining']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">محافظ معلمين</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($kpis['instructor_wallets']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">إجمالي أرصدة المعلمين</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($kpis['instructor_balance'], 2) }} {{ currency_symbol() }}</p>
        </article>
    </section>

    <section class="flex flex-wrap gap-2">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.platform-wallets.index', ['tab' => $key]) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl px-4 text-sm font-medium transition {{ $tab === $key ? 'bg-accent text-white' : 'border border-line bg-surface text-ink hover:bg-accent-soft hover:text-accent' }}">
                {{ $label }}
            </a>
        @endforeach
    </section>

    @if($tab === 'students')
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-3">
                <h3 class="text-sm font-semibold text-ink">أرصدة باقات الدروس الخاصة</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">الطالب</th>
                            <th class="px-4 py-3 text-start font-medium">الباقة</th>
                            <th class="px-4 py-3 text-start font-medium">المتبقي</th>
                            <th class="px-4 py-3 text-start font-medium">ينتهي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($studentCredits as $row)
                            <tr class="hover:bg-canvas/60">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-ink">{{ $row->user->name ?? '—' }}</p>
                                    <p class="text-xs text-muted">{{ $row->user->email ?? '' }}</p>
                                </td>
                                <td class="px-4 py-3 text-ink">{{ $row->servicePackage->name ?? $row->scope }}</td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-accent">{{ $row->unitsLeft() }} / {{ $row->units_total }}</td>
                                <td class="px-4 py-3 tabular-nums text-muted">{{ $row->expires_at?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-muted">لا توجد أرصدة نشطة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($studentCredits->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $studentCredits->links() }}</div>
            @endif
        </article>
    @elseif($tab === 'instructors')
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-3">
                <h3 class="text-sm font-semibold text-ink">محافظ المعلمين</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">المعلم</th>
                            <th class="px-4 py-3 text-start font-medium">المحفظة</th>
                            <th class="px-4 py-3 text-start font-medium">الرصيد</th>
                            <th class="px-4 py-3 text-start font-medium">معلّق</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($instructorWallets as $wallet)
                            <tr class="hover:bg-canvas/60">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-ink">{{ $wallet->user->name ?? '—' }}</p>
                                    <p class="text-xs text-muted">{{ $wallet->user->email ?? '' }}</p>
                                </td>
                                <td class="px-4 py-3 text-ink">{{ $wallet->name }}</td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-ink">{{ number_format((float) $wallet->balance, 2) }} {{ $wallet->currency }}</td>
                                <td class="px-4 py-3 tabular-nums text-muted">{{ number_format((float) $wallet->pending_balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-muted">لا توجد محافظ معلمين.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($instructorWallets->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $instructorWallets->links() }}</div>
            @endif
        </article>
    @else
        <article class="rounded-2xl border border-line bg-surface p-6 shadow-soft">
            <h3 class="text-base font-semibold text-ink">منح رصيد تسويقي للطالب</h3>
            <p class="mt-1 text-sm text-muted">انتقل إلى أداة منح رصيد المحفظة/الباقة الحالية.</p>
            @if(Route::has('admin.marketing.student-wallet-credit.create'))
                <a href="{{ route('admin.marketing.student-wallet-credit.create') }}"
                   class="btn-press mt-4 inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-gift"></i> فتح منح رصيد الطالب
                </a>
            @else
                <p class="mt-4 text-sm text-rose-700">مسار المنح غير متاح حالياً.</p>
            @endif
        </article>
    @endif
</div>
@endsection
