@extends('layouts.admin')

@section('title', 'نظام الاتفاقيات - ' . config('app.name'))
@section('page_title', 'نظام الاتفاقيات')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';

    $kpiCards = [
        ['label' => 'إجمالي الاتفاقيات', 'value' => number_format($stats['total']), 'icon' => 'fa-handshake', 'note' => 'كل العقود المسجّلة'],
        ['label' => 'اتفاقيات نشطة', 'value' => number_format($stats['active']), 'icon' => 'fa-check-circle', 'note' => 'سارية المفعول'],
        ['label' => 'مسودات', 'value' => number_format($stats['draft']), 'icon' => 'fa-file-alt', 'note' => 'قيد الإعداد'],
        ['label' => 'إجمالي المدفوعات', 'value' => format_money($stats['total_earned']), 'icon' => 'fa-money-bill-wave', 'note' => 'مدفوعات المدربين'],
    ];

    $typeBadges = [
        'course_price' => ['label' => 'سعر للكورس', 'badge' => 'bg-[#f2f5f4] text-accent border-line'],
        'hourly_rate' => ['label' => 'سعر للساعة', 'badge' => 'bg-canvas text-ink border-line'],
        'monthly_salary' => ['label' => 'راتب شهري', 'badge' => 'bg-accent-soft text-accent border-line'],
        'consultation_session' => ['label' => 'استشارات', 'badge' => 'bg-canvas text-ink border-line'],
    ];

    $statusBadges = [
        'draft' => ['label' => 'مسودة', 'badge' => 'border-line bg-canvas text-muted'],
        'active' => ['label' => 'نشط', 'badge' => 'border-emerald-100 bg-emerald-50 text-emerald-700'],
        'suspended' => ['label' => 'معلق', 'badge' => 'border-amber-100 bg-amber-50 text-amber-800'],
        'terminated' => ['label' => 'منتهي', 'badge' => 'border-rose-100 bg-rose-50 text-rose-700'],
        'completed' => ['label' => 'مكتمل', 'badge' => 'border-line bg-accent-soft text-accent'],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الماليات · عقود المدربين</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة اتفاقيات المدربين</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">إدارة عقود العمل وأنظمة الدفع للمدربين</p>
        </div>
        <a href="{{ route('admin.agreements.create') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
            <i class="fas fa-plus text-xs"></i>
            إضافة اتفاقية جديدة
        </a>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpiCards as $card)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas {{ $card['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-ink">{{ $card['value'] }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $card['note'] }}</p>
            </article>
        @endforeach
    </section>

    <form method="GET" action="{{ route('admin.agreements.index') }}" id="filterForm"
          class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-filter text-sm"></i>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-ink">البحث والفلترة</h3>
                <p class="text-xs text-muted">تصفية حسب المدرب، النوع، أو الحالة</p>
            </div>
        </div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="{{ $labelClass }}">البحث</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-muted">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input type="text" name="search"
                           value="{{ htmlspecialchars(request('search') ?? '', ENT_QUOTES, 'UTF-8') }}"
                           maxlength="255" placeholder="رقم الاتفاقية، اسم المدرب"
                           class="{{ $fieldClass }} ps-9" />
                </div>
            </div>
            <div>
                <label class="{{ $labelClass }}">المدرب</label>
                <select name="instructor_id" class="{{ $fieldClass }}">
                    <option value="">جميع المدربين</option>
                    @foreach($instructors as $instructor)
                        <option value="{{ $instructor->id }}" {{ request('instructor_id') == $instructor->id ? 'selected' : '' }}>
                            {{ htmlspecialchars($instructor->name, ENT_QUOTES, 'UTF-8') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">النوع</label>
                <select name="type" class="{{ $fieldClass }}">
                    <option value="">جميع الأنواع</option>
                    <option value="course_price" {{ request('type') == 'course_price' ? 'selected' : '' }}>سعر للكورس</option>
                    <option value="hourly_rate" {{ request('type') == 'hourly_rate' ? 'selected' : '' }}>سعر للساعة</option>
                    <option value="monthly_salary" {{ request('type') == 'monthly_salary' ? 'selected' : '' }}>راتب شهري</option>
                    <option value="consultation_session" {{ request('type') == 'consultation_session' ? 'selected' : '' }}>استشارات</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">الحالة</label>
                <select name="status" class="{{ $fieldClass }}">
                    <option value="">جميع الحالات</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>مسودة</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>معلق</option>
                    <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>منتهي</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-2 xl:col-span-4">
                <button type="submit"
                        class="btn-press inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a] sm:flex-none sm:px-6">
                    <i class="fas fa-search text-xs"></i>
                    بحث
                </button>
                @if(request()->anyFilled(['search', 'instructor_id', 'type', 'status']))
                    <a href="{{ route('admin.agreements.index') }}"
                       class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line text-muted hover:bg-accent-soft hover:text-accent"
                       title="مسح الفلتر">
                        <i class="fas fa-times text-xs"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">قائمة الاتفاقيات</h3>
                <p class="text-xs text-muted">
                    <span class="font-semibold tabular-nums text-accent">{{ $agreements->total() }}</span> اتفاقية
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">رقم الاتفاقية</th>
                        <th class="px-4 py-3 text-start font-medium">المدرب</th>
                        <th class="px-4 py-3 text-start font-medium">النوع</th>
                        <th class="px-4 py-3 text-start font-medium">السعر/المعدل</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">تاريخ البدء</th>
                        <th class="px-4 py-3 text-end font-medium">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($agreements as $agreement)
                        @php
                            $type = $typeBadges[$agreement->type] ?? ['label' => $agreement->type, 'badge' => 'bg-canvas text-ink border-line'];
                            $status = $statusBadges[$agreement->status] ?? ['label' => $agreement->status, 'badge' => 'border-line bg-canvas text-muted'];
                        @endphp
                        <tr class="hover:bg-canvas/60">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink">{{ htmlspecialchars($agreement->agreement_number ?? 'N/A', ENT_QUOTES, 'UTF-8') }}</p>
                                <p class="mt-0.5 truncate text-xs text-muted">{{ htmlspecialchars($agreement->title ?? '', ENT_QUOTES, 'UTF-8') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-sm font-bold text-accent">
                                        {{ mb_substr($agreement->instructor->name ?? '', 0, 1, 'UTF-8') }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-ink">{{ htmlspecialchars($agreement->instructor->name ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}</p>
                                        <p class="truncate text-xs text-muted tabular-nums">{{ htmlspecialchars($agreement->instructor->phone ?? '-', ENT_QUOTES, 'UTF-8') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $type['badge'] }}">
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                    {{ $type['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold tabular-nums text-ink">{{ number_format($agreement->rate ?? 0, 2) }} {{ currency_symbol() }}</p>
                                @if($agreement->type == 'hourly_rate')
                                    <p class="text-xs text-muted">للساعة</p>
                                @elseif($agreement->type == 'monthly_salary')
                                    <p class="text-xs text-muted">شهرياً</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $status['badge'] }}">
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                    {{ $status['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium tabular-nums text-ink">{{ $agreement->start_date ? $agreement->start_date->format('Y-m-d') : '-' }}</p>
                                @if($agreement->end_date)
                                    <p class="text-xs text-muted tabular-nums">حتى {{ $agreement->end_date->format('Y-m-d') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.agreements.show', $agreement) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-canvas hover:text-accent"
                                       title="عرض">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('admin.agreements.edit', $agreement) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-accent-soft hover:text-accent"
                                       title="تعديل">
                                        <i class="fas fa-pen text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="inline-flex size-12 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                                        <i class="fas fa-handshake text-lg"></i>
                                    </span>
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
            <div class="border-t border-line px-4 py-3">
                {{ $agreements->appends(request()->query())->links() }}
            </div>
        @endif
    </article>
</div>

@push('scripts')
<script>
const filterForm = document.getElementById('filterForm');
if (filterForm) {
    filterForm.addEventListener('submit', function(e) {
        // Sanitization يتم في الخادم
    });
}

function sanitizeInput(input) {
    return input.replace(/[<>]/g, '');
}
</script>
@endpush
@endsection
