@extends('layouts.admin')

@section('title', 'حجوزات المجموعات - حصتك')
@section('page_title', 'حجوزات المجموعات')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المدرسة · حجوزات الفصول والكوهورتات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">حجوزات فصول المدرسة</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">صندوق طلبات الحجز والتسكين اليدوي في دفعات الفصول والمجموعات</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.tutoring-group-bookings.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white"><i class="fas fa-user-plus"></i> تسكين طالب</a>
            <a href="{{ route('admin.tutoring-groups.index', 'individual') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:border-accent/30 hover:text-accent">فردية</a>
            <a href="{{ route('admin.tutoring-groups.index', 'collective') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:border-accent/30 hover:text-accent">جماعية</a>
        </div>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'كيف تُدار الحجوزات؟',
        'body' => 'هنا تصل طلبات الحجز من الموقع أو التسكين اليدوي. راجع الحالة ثم ضع الطالب في الدفعة/الموعد المناسب.',
        'steps' => [
            'راجع الطلبات قيد المراجعة أولاً.',
            'أكّد الحجز بعد التأكد من المقعد والمدرب والموعد.',
            'للتسكين اليدوي من رصيد طالب استخدم زر «تسكين طالب».',
            'المجموعات الجماعية والفردية لها مسارات منفصلة في القوائم أعلاه.',
        ],
    ])

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الإجمالي</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($stats['total']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">قيد المراجعة</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($stats['pending']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">مؤكد</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($stats['confirmed']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">قادم مؤكد</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($stats['upcoming']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">مكتمل</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($stats['completed']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">أرصدة نشطة / قابلة للحجز</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($stats['credits_active']) }} / {{ number_format($stats['credits_bookable']) }}</p>
        </article>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">فلترة</h3>
        </div>
        <form method="GET" class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end sm:p-5">
            <div>
                <label class="{{ $labelClass }}">بحث</label>
                <input type="search" name="search" value="{{ request('search') }}" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">النوع</label>
                <select name="type" class="{{ $fieldClass }}">
                    <option value="">الكل</option>
                    <option value="individual" @selected(request('type')==='individual')>فردي</option>
                    <option value="collective" @selected(request('type')==='collective')>جماعي</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">الحالة</label>
                <select name="status" class="{{ $fieldClass }}">
                    <option value="">الكل</option>
                    <option value="pending" @selected(request('status')==='pending')>قيد المراجعة</option>
                    <option value="confirmed" @selected(request('status')==='confirmed')>مؤكد</option>
                    <option value="cancelled" @selected(request('status')==='cancelled')>ملغي</option>
                    <option value="completed" @selected(request('status')==='completed')>مكتمل</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">من</label>
                <input type="date" name="from" value="{{ request('from') }}" class="{{ $fieldClass }}">
            </div>
            <div class="flex gap-2">
                <button class="btn-press inline-flex h-11 items-center rounded-xl bg-accent px-5 text-sm font-medium text-white">تصفية</button>
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start">الفصل</th>
                        <th class="px-4 py-3 text-start">السنة</th>
                        <th class="px-4 py-3 text-start">الطالب</th>
                        <th class="px-4 py-3 text-start">المدرب</th>
                        <th class="px-4 py-3 text-start">المصدر</th>
                        <th class="px-4 py-3 text-start">الموعد</th>
                        <th class="px-4 py-3 text-start">الحالة</th>
                        <th class="px-4 py-3 text-start">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($bookings as $booking)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-ink">{{ $booking->tutoringGroup?->title }}</p>
                                <p class="text-[11px] text-muted">{{ $booking->tutoringGroup?->typeLabel() }}@if($booking->cohort) · {{ $booking->cohort->title }}@endif</p>
                            </td>
                            <td class="px-4 py-3 text-ink-soft">{{ $booking->tutoringGroup?->schoolYear?->name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <p class="text-ink">{{ $booking->contactName() }}</p>
                                <p class="text-[11px] text-muted">{{ $booking->contactPhone() ?: $booking->contactEmail() }}</p>
                            </td>
                            <td class="px-4 py-3 text-ink-soft">{{ $booking->instructor?->name }}</td>
                            <td class="px-4 py-3">
                                @if($booking->entitlement)
                                    <p class="text-xs font-medium text-accent">رصيد #{{ $booking->entitlement->id }}</p>
                                    <p class="text-[11px] text-muted">{{ $booking->order_id ? 'طلب #'.$booking->order_id : 'منح يدوي' }}</p>
                                @elseif($booking->order_id)
                                    <p class="text-xs text-ink">طلب #{{ $booking->order_id }}</p>
                                @else
                                    <span class="text-xs text-muted">اشتراك مباشر</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 tabular-nums text-ink">{{ $booking->starts_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ $booking->statusLabel() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.tutoring-group-bookings.show', $booking) }}" class="text-xs font-semibold text-accent">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center text-sm text-muted">لا توجد حجوزات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($bookings->hasPages())
            <div class="border-t border-line px-4 py-4">{{ $bookings->links() }}</div>
        @endif
    </article>
</div>
@endsection
