@extends('layouts.admin')

@section('title', $typeLabel.' - حصتك')
@section('page_title', $typeLabel)

@section('content')
@php
    $kpis = [
        ['label' => 'الإجمالي', 'value' => $stats['total'], 'icon' => 'fa-layer-group', 'tone' => 'accent', 'note' => 'كل المجموعات من هذا النوع'],
        ['label' => 'نشطة', 'value' => $stats['active'], 'icon' => 'fa-eye', 'tone' => 'accent', 'note' => 'تظهر للزوار'],
        ['label' => 'معطّلة', 'value' => $stats['inactive'], 'icon' => 'fa-eye-slash', 'tone' => 'muted', 'note' => 'مخفية عن الموقع'],
        ['label' => 'مميزة', 'value' => $stats['featured'], 'icon' => 'fa-star', 'tone' => 'metal', 'note' => 'أولوية في العرض'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحتوى · المجموعات · منفصل عن الكورسات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $typeLabel }}</h2>
            <p class="mt-1 text-sm text-muted">إنشاء وإدارة العروض الظاهرة للطلاب مع الحجز حسب جدول المدرب</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.tutor-work-schedules.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-calendar-week text-xs"></i>
                جداول المدربين
            </a>
            <a href="{{ route('admin.tutoring-groups.create', $type) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                مجموعة جديدة
            </a>
        </div>
    </section>

    @if(($type ?? '') === 'collective')
        @include('admin.partials.workflow-guide', [
            'title' => 'كيف تعمل فصول / المجموعات الجماعية؟',
            'body' => 'هذه الصفحة لإنشاء العرض الذي يراه الطالب على الموقع (مثل فصل سنة معينة). العرض وحده لا يكفي — لازم تضيف دفعات ثم تدير الفصل.',
            'steps' => [
                'أنشئ مجموعة جماعية جديدة وحدّد المدرب والظهور.',
                'من زر الدفعات: أنشئ دفعة (سعة + جدول + موعد بداية).',
                'افتح «الفصل» داخل الدفعة لتسجيل الطلاب وتوليد الحصص وغرف Live.',
                'راجع الحجوزات أو التسكين لوضع الطلاب في الدفعة المناسبة.',
            ],
        ])
    @else
        @include('admin.partials.workflow-guide', [
            'title' => 'كيف تعمل المجموعات الفردية؟',
            'body' => 'المسار الفردي منفصل عن فصول المدرسة: الطالب يشتري باقة، ثم يُسكَّن مع معلم حسب جدول التوفر.',
            'steps' => [
                'أنشئ مجموعة فردية ظاهرة للطلاب.',
                'أضف باقات (عدد حصص / مدة / سعر) من صفحة الباقات.',
                'حدّث جداول عمل المدربين حتى تظهر مواعيد صحيحة.',
                'من التسكين أو حجوزات المجموعات ثبّت الموعد مع الطالب.',
            ],
        ])
    @endif

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($kpi['value']) }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
        </div>
        <form method="GET" action="{{ route('admin.tutoring-groups.index', $type) }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-3 md:items-end">
            <div>
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="العنوان أو الرابط..." class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">كل الحالات</option>
                    <option value="active" @selected(request('status') === 'active')>نشط</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>معطل</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    تصفية
                </button>
                @if(request()->anyFilled(['search', 'status']))
                    <a href="{{ route('admin.tutoring-groups.index', $type) }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">مسح</a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">قائمة المجموعات</h3>
            <p class="mt-0.5 text-xs text-muted">{{ number_format($groups->total()) }} نتيجة</p>
        </div>
        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">المجموعة</th>
                        <th class="px-4 py-3 text-start font-medium">المدرب</th>
                        <th class="px-4 py-3 text-start font-medium">السعر</th>
                        <th class="px-4 py-3 text-start font-medium">السعة</th>
                        <th class="px-4 py-3 text-start font-medium">الحجوزات</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($groups as $group)
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink">{{ $group->title }}</p>
                                <p class="mt-0.5 font-mono text-[11px] text-muted" dir="ltr">{{ $group->slug }}</p>
                            </td>
                            <td class="px-4 py-3 text-ink-soft">{{ $group->instructor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink">{{ $group->formattedPrice() }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink">{{ $group->capacity }}</td>
                            <td class="px-4 py-3">
                                <span class="text-ink">{{ number_format($group->bookings_count) }}</span>
                                @if(($group->pending_bookings_count ?? 0) > 0)
                                    <span class="ms-1 text-xs text-metal">({{ $group->pending_bookings_count }} قيد المراجعة)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $group->is_active ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                                    {{ $group->is_active ? 'نشط' : 'معطل' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.tutoring-groups.edit', [$type, $group]) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent">تعديل</a>
                                    @if($type === 'collective')
                                        <a href="{{ route('admin.tutoring-groups.cohorts.index', $group) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent">دفعات</a>
                                    @else
                                        <a href="{{ route('admin.tutoring-groups.packages.index', $group) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent">باقات</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.tutoring-groups.toggle-status', [$type, $group]) }}">
                                        @csrf
                                        <button type="submit" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink-soft hover:border-accent/30 hover:text-accent">
                                            {{ $group->is_active ? 'إيقاف' : 'تفعيل' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.tutoring-groups.destroy', [$type, $group]) }}" onsubmit="return confirm('حذف هذه المجموعة؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-danger hover:bg-danger/5">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-users"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا توجد مجموعات بعد</p>
                                <a href="{{ route('admin.tutoring-groups.create', $type) }}" class="mt-3 inline-flex text-sm font-semibold text-accent">إنشاء أول مجموعة</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($groups->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $groups->links() }}</div>
        @endif
    </article>
</div>
@endsection
