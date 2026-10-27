@extends('layouts.admin')

@section('title', 'إحصائيات الإشعارات')
@section('page_title', 'إحصائيات الإشعارات')

@section('content')
@php
    $overview = $stats['overview'] ?? [];
    $cards = [
        ['label' => 'إجمالي المُرسل', 'value' => $overview['total_sent'] ?? 0, 'icon' => 'fa-paper-plane'],
        ['label' => 'مقروء', 'value' => $overview['total_read'] ?? 0, 'icon' => 'fa-check-double'],
        ['label' => 'غير مقروء', 'value' => $overview['total_unread'] ?? 0, 'icon' => 'fa-envelope'],
        ['label' => 'أُرسل اليوم', 'value' => $overview['today_sent'] ?? 0, 'icon' => 'fa-calendar-day'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">التنبيهات</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">إحصائيات الإشعارات</h2>
            <p class="mt-1 text-sm text-muted">ملخص إشعاراتك المرسلة من لوحة الإدارة</p>
        </div>
        <a href="{{ route('admin.notifications.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm">رجوع للمركز</a>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($cards as $card)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <i class="fas {{ $card['icon'] }} text-accent text-sm"></i>
                <p class="mt-3 text-xs text-muted">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ number_format($card['value']) }}</p>
            </article>
        @endforeach
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        @foreach([
            'by_type' => 'حسب النوع',
            'by_priority' => 'حسب الأولوية',
            'by_target' => 'حسب الجمهور',
        ] as $key => $title)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-3">
                    <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-canvas text-xs text-muted">
                            <tr>
                                <th class="px-4 py-2 text-start font-medium">القيمة</th>
                                <th class="px-4 py-2 text-start font-medium">العدد</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($stats[$key] ?? [] as $row)
                                <tr>
                                    <td class="px-4 py-2 text-ink">{{ $row->type ?? $row->priority ?? $row->target_type ?? '—' }}</td>
                                    <td class="px-4 py-2 font-semibold tabular-nums">{{ number_format($row->count) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-4 py-6 text-center text-muted">لا بيانات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @endforeach
    </div>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-3">
            <h3 class="text-base font-semibold text-ink">النشاط الأخير (7 أيام)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-2 text-start font-medium">التاريخ</th>
                        <th class="px-4 py-2 text-start font-medium">عدد الإشعارات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($stats['recent_activity'] ?? [] as $row)
                        <tr>
                            <td class="px-4 py-2">{{ $row->date }}</td>
                            <td class="px-4 py-2 font-semibold tabular-nums">{{ number_format($row->count) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-center text-muted">لا نشاط مسجل</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</div>
@endsection
