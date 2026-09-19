@extends('layouts.admin')

@section('title', 'طلبات السحب - ' . config('app.name'))
@section('page_title', 'طلبات السحب')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $statCards = [
        [
            'label' => 'إجمالي الطلبات',
            'value' => number_format($stats['total']),
            'icon' => 'fas fa-money-bill-wave',
            'description' => 'جميع طلبات السحب المسجلة',
        ],
        [
            'label' => 'قيد الانتظار',
            'value' => number_format($stats['pending']),
            'icon' => 'fas fa-clock',
            'description' => 'بانتظار مراجعة الإدارة',
        ],
        [
            'label' => 'موافق عليها',
            'value' => number_format($stats['approved']),
            'icon' => 'fas fa-check-circle',
            'description' => 'تمت الموافقة ولم تُكمل بعد',
        ],
        [
            'label' => 'إجمالي المكتملة',
            'value' => number_format($stats['completed'], 2) . ' ' . currency_symbol(),
            'icon' => 'fas fa-check-double',
            'description' => 'مجموع المبالغ المكتملة',
        ],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المالية · طلبات السحب</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة طلبات السحب</h2>
            <p class="mt-1 text-sm text-muted">إدارة طلبات سحب الماديات من المدربين</p>
        </div>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statCards as $card)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="{{ $card['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $card['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ $card['value'] }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $card['description'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">ابحث برقم الطلب أو المدرب أو فلتر حسب الحالة</p>
        </div>
        <form method="GET" action="{{ route('admin.withdrawals.index') }}" id="filterForm" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-3 xl:items-end">
            <div>
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-muted">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input id="search" type="text" name="search" value="{{ htmlspecialchars(request('search') ?? '', ENT_QUOTES, 'UTF-8') }}" maxlength="255" placeholder="رقم الطلب، اسم المدرب" class="{{ $fieldClass }} ps-9" />
                </div>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="instructor_id">المدرب</label>
                <select id="instructor_id" name="instructor_id" class="{{ $fieldClass }}">
                    <option value="">جميع المدربين</option>
                    @foreach($instructors as $instructor)
                        <option value="{{ $instructor->id }}" {{ request('instructor_id') == $instructor->id ? 'selected' : '' }}>{{ htmlspecialchars($instructor->name, ENT_QUOTES, 'UTF-8') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">جميع الحالات</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>موافق عليه</option>
                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>قيد المعالجة</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>مرفوض</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-2 md:col-span-3">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-search text-xs"></i>
                    بحث
                </button>
                @if(request()->anyFilled(['search', 'instructor_id', 'status']))
                <a href="{{ route('admin.withdrawals.index') }}" class="btn-press inline-flex h-11 items-center justify-center rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent" title="مسح الفلتر">
                    <i class="fas fa-times"></i>
                </a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-ink">قائمة طلبات السحب</h3>
                <p class="mt-0.5 text-xs text-muted">
                    <span class="font-semibold text-accent">{{ $withdrawals->total() }}</span> طلب
                </p>
            </div>
        </div>

        @if ($withdrawals->count() > 0)
            <div class="admin-table-wrap overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">رقم الطلب</th>
                            <th class="px-4 py-3 text-start font-medium">المدرب</th>
                            <th class="px-4 py-3 text-start font-medium">المبلغ</th>
                            <th class="px-4 py-3 text-start font-medium">طريقة الدفع</th>
                            <th class="px-4 py-3 text-start font-medium">الحالة</th>
                            <th class="px-4 py-3 text-start font-medium">تاريخ الطلب</th>
                            <th class="px-4 py-3 text-center font-medium">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($withdrawals as $withdrawal)
                            <tr class="hover:bg-canvas/40">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-ink">{{ htmlspecialchars($withdrawal->request_number ?? '#' . $withdrawal->id, ENT_QUOTES, 'UTF-8') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-sm font-bold text-accent">
                                            {{ mb_substr($withdrawal->instructor->name ?? '', 0, 1, 'UTF-8') }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-ink">{{ htmlspecialchars($withdrawal->instructor->name ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}</p>
                                            <p class="text-[11px] text-muted">{{ htmlspecialchars($withdrawal->instructor->phone ?? '-', ENT_QUOTES, 'UTF-8') }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-lg font-semibold tabular-nums text-ink">{{ number_format($withdrawal->amount ?? 0, 2) }} <span class="text-xs font-normal text-muted">$</span></div>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $paymentMethodBadges = [
                                            'bank_transfer' => ['label' => 'تحويل بنكي', 'classes' => 'bg-accent-soft text-accent', 'icon' => 'fas fa-university'],
                                            'wallet' => ['label' => 'محفظة', 'classes' => 'bg-canvas-muted text-ink', 'icon' => 'fas fa-wallet'],
                                            'cash' => ['label' => 'نقدي', 'classes' => 'bg-emerald-50 text-emerald-700', 'icon' => 'fas fa-money-bill'],
                                        ];
                                        $method = $paymentMethodBadges[$withdrawal->payment_method] ?? ['label' => 'أخرى', 'classes' => 'bg-canvas-muted text-muted', 'icon' => 'fas fa-ellipsis-h'];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium {{ $method['classes'] }}">
                                        <i class="{{ $method['icon'] }} text-[10px]"></i>
                                        {{ $method['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusBadges = [
                                            'pending' => ['label' => 'قيد الانتظار', 'classes' => 'bg-canvas-muted text-muted'],
                                            'approved' => ['label' => 'موافق عليه', 'classes' => 'bg-amber-50 text-amber-700'],
                                            'processing' => ['label' => 'قيد المعالجة', 'classes' => 'bg-amber-50 text-amber-700'],
                                            'completed' => ['label' => 'مكتمل', 'classes' => 'bg-emerald-50 text-emerald-700'],
                                            'rejected' => ['label' => 'مرفوض', 'classes' => 'bg-rose-50 text-rose-700'],
                                            'cancelled' => ['label' => 'ملغي', 'classes' => 'bg-canvas-muted text-muted'],
                                        ];
                                        $status = $statusBadges[$withdrawal->status] ?? ['label' => $withdrawal->status, 'classes' => 'bg-canvas-muted text-muted'];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium {{ $status['classes'] }}">
                                        <span class="size-1.5 rounded-full bg-current"></span>
                                        {{ $status['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted">
                                    <p class="font-medium text-ink">{{ $withdrawal->created_at->format('Y-m-d') }}</p>
                                    <p>{{ $withdrawal->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('admin.withdrawals.show', $withdrawal) }}"
                                       class="btn-press inline-flex size-9 items-center justify-center rounded-xl border border-line text-ink hover:bg-accent-soft hover:text-accent"
                                       title="عرض">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($withdrawals->hasPages())
                <div class="border-t border-line px-4 py-3 sm:px-5">
                    {{ $withdrawals->appends(request()->query())->links() }}
                </div>
            @endif
        @else
            <div class="px-4 py-12 text-center sm:px-5">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-money-bill-wave text-2xl"></i>
                </div>
                <h3 class="text-base font-semibold text-ink">لا توجد طلبات سحب</h3>
                <p class="mx-auto mt-1 max-w-md text-sm text-muted">لم يتم تقديم أي طلبات سحب بعد</p>
            </div>
        @endif
    </article>
</div>

@push('scripts')
<script>
// حماية من XSS في البحث
const filterForm = document.getElementById('filterForm');
if (filterForm) {
    filterForm.addEventListener('submit', function(e) {
        // Sanitization يتم في الخادم
    });
}

// Sanitization للبحث
function sanitizeInput(input) {
    return input.replace(/[<>]/g, '');
}
</script>
@endpush
@endsection
