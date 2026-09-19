@extends('layouts.admin')

@section('title', 'حجوزات الحصة المجانية - حصتك')
@section('page_title', 'حجوزات الحصة المجانية')

@section('content')
@php
    $kpis = [
        ['label' => 'الإجمالي', 'value' => $stats['total'], 'icon' => 'fa-inbox', 'tone' => 'accent'],
        ['label' => 'طلبات', 'value' => $stats['pending'] ?? 0, 'icon' => 'fa-paper-plane', 'tone' => 'metal'],
        ['label' => 'مؤكد', 'value' => $stats['confirmed'], 'icon' => 'fa-check-circle', 'tone' => 'accent'],
        ['label' => 'قادم', 'value' => $stats['upcoming'], 'icon' => 'fa-hourglass-half', 'tone' => 'metal'],
        ['label' => 'اليوم', 'value' => $stats['today'], 'icon' => 'fa-calendar-day', 'tone' => 'metal'],
        ['label' => 'مكتمل', 'value' => $stats['completed'], 'icon' => 'fa-flag-checkered', 'tone' => 'muted'],
        ['label' => 'ملغي', 'value' => $stats['cancelled'], 'icon' => 'fa-ban', 'tone' => 'danger'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
        'danger' => 'bg-danger/10 text-danger',
    ];
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">حصة مجانية لحاملي الباقة · من جدول المعلم أو طلب تنسيق · بدون خصم من الرصيد</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">حجوزات الحصة المجانية</h2>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.free-trial-bookings.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                توصيف يدوي
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($kpi['value']) }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">حدد الحالة أو نطاق التاريخ للوصول السريع للحجز</p>
        </div>
        <form method="get" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-5 md:items-end">
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}" for="search">بحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="اسم / بريد / هاتف / هدف" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">الكل</option>
                    <option value="pending" @selected(request('status')==='pending')>طلب / بانتظار</option>
                    <option value="confirmed" @selected(request('status')==='confirmed')>مؤكد</option>
                    <option value="completed" @selected(request('status')==='completed')>مكتمل</option>
                    <option value="cancelled" @selected(request('status')==='cancelled')>ملغي</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="from">من تاريخ</label>
                <input id="from" type="date" name="from" value="{{ request('from') }}" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="to">إلى تاريخ</label>
                <input id="to" type="date" name="to" value="{{ request('to') }}" class="{{ $fieldClass }}">
            </div>
            <div class="flex flex-wrap gap-2 md:col-span-5">
                <button type="submit" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    تصفية
                </button>
                @if(request()->anyFilled(['search', 'status', 'from', 'to']))
                    <a href="{{ route('admin.free-trial-bookings.index') }}" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-times text-xs"></i>
                        مسح الفلاتر
                    </a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-ink">سجل الحجوزات</h3>
                <p class="mt-0.5 text-xs text-muted">{{ number_format($bookings->total()) }} حجز</p>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="w-full min-w-[860px] text-right text-sm">
                <thead class="bg-[#f7f8fa] text-[11px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">#</th>
                        <th class="px-3 py-3 font-medium">الطالب</th>
                        <th class="px-3 py-3 font-medium">التواصل</th>
                        <th class="px-3 py-3 font-medium">الموعد</th>
                        <th class="px-3 py-3 font-medium">المعلم</th>
                        <th class="px-3 py-3 font-medium">الجدول</th>
                        <th class="px-3 py-3 font-medium">المدة</th>
                        <th class="px-3 py-3 font-medium">الحالة</th>
                        <th class="px-5 py-3 font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($bookings as $b)
                        @php
                            $badgeClass = match($b->status) {
                                'completed' => 'bg-accent-soft text-accent',
                                'cancelled' => 'bg-danger/10 text-danger',
                                'pending' => 'bg-amber-100 text-amber-800',
                                default => 'bg-metal/15 text-metal',
                            };
                            $statusLabel = match($b->status) {
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغي',
                                'pending' => 'طلب',
                                default => 'مؤكد',
                            };
                            $linked = $b->oneToOneSession;
                        @endphp
                        <tr class="transition hover:bg-[#f7f8fa]">
                            <td class="px-5 py-3 tabular-nums text-muted">{{ $b->id }}</td>
                            <td class="px-3 py-3">
                                <p class="font-semibold text-ink">{{ $b->name }}</p>
                                @if($b->goal)
                                    <p class="mt-0.5 text-xs text-muted">{{ $b->goalLabel('ar') }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-xs text-muted">
                                @if($b->email)<p><i class="fas fa-envelope ml-1 text-[10px]"></i>{{ $b->email }}</p>@endif
                                @if($b->phone)
                                    <p class="mt-0.5" dir="ltr">
                                        <i class="fas fa-phone ml-1 text-[10px]"></i>{{ $b->phone }}
                                        @if($b->whatsappUrl())
                                            <a href="{{ $b->whatsappUrl() }}" target="_blank" rel="noopener" class="mr-1 text-emerald-700 hover:underline" title="واتساب"><i class="fab fa-whatsapp"></i></a>
                                        @endif
                                    </p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 font-medium tabular-nums text-ink">
                                <x-app-datetime :at="$b->starts_at" :timezone="$b->timezone" pattern="Y-m-d H:i" />
                            </td>
                            <td class="px-3 py-3 text-sm text-ink">
                                {{ $b->instructor?->name ?: '—' }}
                            </td>
                            <td class="px-3 py-3 text-xs">
                                @if($linked)
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-accent-soft px-2 py-1 font-medium text-accent" title="لا تُخصم من الرصيد">
                                        <i class="fas fa-calendar-check text-[10px]"></i>
                                        في الجدول
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 tabular-nums text-muted">{{ $b->duration_minutes }} د</td>
                            <td class="px-3 py-3">
                                <span class="rounded-lg px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.free-trial-bookings.show', $b) }}"
                                       class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-accent-soft text-accent transition hover:bg-accent hover:text-white"
                                       title="عرض">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <form method="post" action="{{ route('admin.free-trial-bookings.destroy', $b) }}" onsubmit="return confirm('حذف هذا الحجز؟');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-danger/10 text-danger transition hover:bg-danger hover:text-white"
                                                title="حذف">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا توجد حجوزات</p>
                                <p class="mt-1 text-xs text-muted">حجوزات الطلاب ذوي الباقة أو التوصيف اليدوي تظهر هنا.</p>
                                <a href="{{ route('admin.free-trial-bookings.create') }}" class="mt-3 inline-flex text-sm font-semibold text-accent hover:underline">توصيف حصة مجانية الآن</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($bookings->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $bookings->withQueryString()->links() }}</div>
        @endif
    </article>
</div>
@endsection
