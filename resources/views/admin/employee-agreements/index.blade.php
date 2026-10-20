@extends('layouts.admin')

@section('title', 'اتفاقيات الموظفين - ' . config('app.name'))
@section('page_title', 'اتفاقيات الموظفين')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $statCards = [
        [
            'label' => 'إجمالي الاتفاقيات',
            'value' => number_format($stats['total']),
            'icon' => 'fas fa-handshake',
            'description' => 'جميع اتفاقيات العمل المسجلة',
        ],
        [
            'label' => 'اتفاقيات نشطة',
            'value' => number_format($stats['active']),
            'icon' => 'fas fa-check-circle',
            'description' => 'عقود سارية المفعول',
        ],
        [
            'label' => 'مسودات',
            'value' => number_format($stats['draft']),
            'icon' => 'fas fa-file-alt',
            'description' => 'بانتظار التفعيل',
        ],
        [
            'label' => 'إجمالي الرواتب',
            'value' => format_money($stats['total_salary']),
            'icon' => 'fas fa-money-bill-wave',
            'description' => currency_symbol() . ' — مجموع الرواتب الشهرية',
        ],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الموارد البشرية · اتفاقيات الموظفين</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة اتفاقيات الموظفين</h2>
            <p class="mt-1 text-sm text-muted">إدارة عقود العمل والرواتب للموظفين</p>
        </div>
        <a href="{{ route('admin.employee-agreements.create') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
            <i class="fas fa-plus text-xs"></i>
            إضافة اتفاقية جديدة
        </a>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                <i class="fas fa-check text-sm"></i>
            </span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-soft">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                <i class="fas fa-exclamation-circle text-sm"></i>
            </span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

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
            <p class="mt-0.5 text-xs text-muted">ابحث برقم الاتفاقية أو اسم الموظف</p>
        </div>
        <form method="GET" action="{{ route('admin.employee-agreements.index') }}" id="filterForm" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-3 xl:items-end">
            <div>
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-muted">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input id="search" type="text" name="search" value="{{ htmlspecialchars(request('search') ?? '', ENT_QUOTES, 'UTF-8') }}" maxlength="255" placeholder="رقم الاتفاقية، اسم الموظف" class="{{ $fieldClass }} ps-9" />
                </div>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="employee_id">الموظف</label>
                <select id="employee_id" name="employee_id" class="{{ $fieldClass }}">
                    <option value="">جميع الموظفين</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ htmlspecialchars($employee->name, ENT_QUOTES, 'UTF-8') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">جميع الحالات</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>مسودة</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>معلق</option>
                    <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>منتهي</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-2 md:col-span-3">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-search text-xs"></i>
                    بحث
                </button>
                @if(request()->anyFilled(['search', 'employee_id', 'status']))
                <a href="{{ route('admin.employee-agreements.index') }}"
                   class="btn-press inline-flex h-11 items-center justify-center rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent"
                   title="مسح الفلتر">
                    <i class="fas fa-times"></i>
                </a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-ink">قائمة الاتفاقيات</h3>
                <p class="mt-0.5 text-xs text-muted">
                    <span class="font-semibold text-accent">{{ $agreements->total() }}</span> اتفاقية
                </p>
            </div>
        </div>

        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">رقم الاتفاقية</th>
                        <th class="px-4 py-3 text-start font-medium">الموظف</th>
                        <th class="px-4 py-3 text-start font-medium">الراتب</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">تاريخ البدء</th>
                        <th class="px-4 py-3 text-center font-medium">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($agreements as $agreement)
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-ink">{{ htmlspecialchars($agreement->agreement_number ?? 'N/A', ENT_QUOTES, 'UTF-8') }}</div>
                                <div class="mt-0.5 text-xs text-muted">{{ htmlspecialchars($agreement->title ?? '', ENT_QUOTES, 'UTF-8') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-sm font-bold text-accent">
                                        {{ mb_substr($agreement->employee->name ?? '', 0, 1, 'UTF-8') }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-ink">{{ htmlspecialchars($agreement->employee->name ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}</p>
                                        <p class="text-[11px] text-muted">{{ htmlspecialchars($agreement->employee->email ?? '-', ENT_QUOTES, 'UTF-8') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold tabular-nums text-ink">{{ number_format($agreement->salary ?? 0, 2) }} {{ currency_symbol() }}</div>
                                <div class="text-xs text-muted">شهرياً</div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusBadges = [
                                        'draft' => ['label' => 'مسودة', 'classes' => 'bg-canvas text-muted border-line'],
                                        'active' => ['label' => 'نشط', 'classes' => 'bg-emerald-50 text-emerald-700 border-emerald-100'],
                                        'suspended' => ['label' => 'معلق', 'classes' => 'bg-amber-50 text-amber-700 border-amber-100'],
                                        'terminated' => ['label' => 'منتهي', 'classes' => 'bg-rose-50 text-rose-700 border-rose-100'],
                                        'completed' => ['label' => 'مكتمل', 'classes' => 'bg-accent-soft text-accent border-line'],
                                    ];
                                    $status = $statusBadges[$agreement->status] ?? ['label' => $agreement->status, 'classes' => 'bg-canvas text-muted border-line'];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs font-semibold {{ $status['classes'] }}">
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                    {{ $status['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted">
                                <div class="font-medium text-ink">{{ $agreement->start_date ? $agreement->start_date->format('Y-m-d') : '-' }}</div>
                                @if($agreement->end_date)
                                    <div class="mt-0.5">حتى {{ $agreement->end_date->format('Y-m-d') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.employee-agreements.show', $agreement) }}"
                                       class="btn-press inline-flex size-9 items-center justify-center rounded-xl border border-line text-accent hover:bg-accent-soft"
                                       title="عرض">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                    <a href="{{ route('admin.employee-agreements.edit', $agreement) }}"
                                       class="btn-press inline-flex size-9 items-center justify-center rounded-xl border border-line text-ink hover:bg-accent-soft hover:text-accent"
                                       title="تعديل">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                    <form action="{{ route('admin.employee-agreements.destroy', $agreement) }}" method="POST" class="inline-block" onsubmit="return confirm('هل أنت متأكد من حذف هذه الاتفاقية؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-press inline-flex size-9 items-center justify-center rounded-xl border border-line text-rose-600 hover:bg-rose-50" title="حذف">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                        <i class="fas fa-user-tie text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-ink">لا توجد اتفاقيات</p>
                                        <p class="mt-1 text-sm text-muted">ابدأ بإنشاء اتفاقية جديدة</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($agreements->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">
                {{ $agreements->appends(request()->query())->links() }}
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
</script>
@endpush
@endsection
