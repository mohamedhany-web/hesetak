@extends('layouts.admin')

@section('title', 'طلبات البرنامج - ' . config('app.name'))
@section('page_title', 'طلبات البرنامج')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-accent">{{ __('admin.dashboard') }}</a>
                <span class="mx-1">·</span>
                <a href="{{ route('admin.advanced-courses.index') }}" class="hover:text-accent">{{ __('admin.courses_management') }}</a>
                <span class="mx-1">·</span>
                <a href="{{ route('admin.advanced-courses.show', $advancedCourse) }}" class="hover:text-accent">{{ Str::limit($advancedCourse->title, 30) }}</a>
                <span class="mx-1">·</span>
                <span class="text-ink">الطلبات</span>
            </p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">طلبات التسجيل</h2>
            <p class="mt-1 truncate text-sm text-muted">{{ $advancedCourse->title }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.orders.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-list text-xs"></i>
                جميع الطلبات
            </a>
            <a href="{{ route('admin.advanced-courses.show', $advancedCourse) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm text-ink-soft hover:bg-accent-soft hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة للبرنامج
            </a>
        </div>
    </section>

    <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <div class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-graduation-cap text-lg"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="truncate text-sm font-semibold text-ink">{{ $advancedCourse->title }}</h3>
                    <p class="text-xs text-muted">
                        {{ $advancedCourse->category ?? '—' }} · {{ $advancedCourse->instructor?->name ?? '—' }}
                    </p>
                </div>
            </div>
            <div class="rounded-xl border border-line px-4 py-2 text-center">
                <div class="text-2xl font-semibold tabular-nums text-ink">{{ $orders->total() }}</div>
                <div class="text-xs font-medium text-muted">إجمالي الطلبات</div>
            </div>
        </div>
    </article>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="flex items-center gap-3">
                <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-amber-700">
                    <i class="fas fa-clock text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-semibold tabular-nums text-ink">{{ $orders->where('status', 'pending')->count() }}</p>
                    <p class="text-xs text-muted">معلّقة</p>
                </div>
            </div>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="flex items-center gap-3">
                <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-emerald-700">
                    <i class="fas fa-check text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-semibold tabular-nums text-ink">{{ $orders->where('status', 'approved')->count() }}</p>
                    <p class="text-xs text-muted">مقبولة</p>
                </div>
            </div>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="flex items-center gap-3">
                <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-rose-700">
                    <i class="fas fa-times text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-semibold tabular-nums text-ink">{{ $orders->where('status', 'rejected')->count() }}</p>
                    <p class="text-xs text-muted">مرفوضة</p>
                </div>
            </div>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="flex items-center gap-3">
                <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-shopping-cart text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-semibold tabular-nums text-ink">{{ $orders->total() }}</p>
                    <p class="text-xs text-muted">إجمالي</p>
                </div>
            </div>
        </article>
    </div>

    @if($orders->count() > 0)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-5 py-4">
                <h4 class="text-sm font-semibold text-ink">طلبات التسجيل</h4>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-[#f8faf9]">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted">الطالب</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted">طريقة الدفع</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted">المبلغ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted">الحالة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted">تاريخ الطلب</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line bg-surface">
                        @foreach($orders as $order)
                            @php
                                $statusClass = match($order->status) {
                                    'pending' => 'bg-amber-50 text-amber-800',
                                    'approved' => 'bg-emerald-50 text-emerald-700',
                                    default => 'bg-rose-50 text-rose-700',
                                };
                                $paymentLabel = match($order->payment_method) {
                                    'whatsapp' => 'واتساب',
                                    'bank_transfer' => 'تحويل بنكي',
                                    'cash' => 'كاش',
                                    default => $order->payment_method,
                                };
                            @endphp
                            <tr class="transition hover:bg-[#f8faf9]">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-sm font-semibold text-accent">
                                            {{ substr($order->user->name ?? '', 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-ink">{{ $order->user->name ?? '—' }}</div>
                                            <div class="max-w-[180px] truncate text-xs text-muted" dir="ltr">{{ $order->user->email ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-ink">{{ $paymentLabel }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold tabular-nums text-ink">{{ number_format($order->amount, 2) }} {{ currency_symbol() }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $statusClass }}">
                                        {{ $order->status_text }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm tabular-nums text-muted">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                           class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent transition hover:bg-accent-soft"
                                           title="عرض">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        @if($order->status == 'pending')
                                            <form action="{{ route('admin.orders.approve', $order) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" onclick="return confirm('هل تريد الموافقة على هذا الطلب؟');"
                                                        class="inline-flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100"
                                                        title="موافقة">
                                                    <i class="fas fa-check text-xs"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.orders.reject', $order) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" onclick="return confirm('هل تريد رفض هذا الطلب؟');"
                                                        class="inline-flex size-9 items-center justify-center rounded-xl bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                                                        title="رفض">
                                                    <i class="fas fa-times text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-line bg-[#f8faf9] px-5 py-4">
                {{ $orders->links() }}
            </div>
        </article>
    @else
        <article class="rounded-2xl border border-dashed border-line bg-surface px-6 py-14 text-center shadow-soft">
            <div class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-shopping-cart text-xl"></i>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-ink">لا توجد طلبات</h3>
            <p class="mt-1 text-sm text-muted">لم يتم تقديم أي طلبات تسجيل لهذا البرنامج بعد</p>
            <a href="{{ route('admin.orders.index') }}"
               class="btn-press mt-5 inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-list text-xs"></i>
                عرض جميع الطلبات
            </a>
        </article>
    @endif
</div>
@endsection
