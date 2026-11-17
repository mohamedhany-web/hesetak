@extends('layouts.admin')

@section('title', 'كتالوج صفحة الأسعار')
@section('page_title', 'كتالوج /pricing')

@section('content')
@php
    $field = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التجارة · صفحة الأسعار العامة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">كتالوج بطاقات /pricing</h2>
            <p class="mt-1 max-w-3xl text-sm text-muted">
                هذه هي نفس البطاقات التي يراها الزائر في
                <a href="{{ $pricingUrl }}" target="_blank" rel="noopener" class="text-accent underline">صفحة الأسعار</a>
                حسب المنهج والمرحلة. عدّل أي بطاقة بالكامل من هنا (الاسم، الوصف، السعر، الظهور، الصورة…).
            </p>
        </div>
        <a href="{{ route('admin.service-package-pricing-rules.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm text-ink-soft hover:bg-accent-soft hover:text-accent">
            <i class="fas fa-sliders text-xs"></i>
            قواعد 2 / 8 / مستعجلة
        </a>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.pricing-storefront.index') }}" class="grid gap-3 rounded-2xl border border-line bg-surface p-4 shadow-soft sm:grid-cols-3">
        <div>
            <label class="mb-1.5 block text-xs font-medium text-muted">المنهج</label>
            <select name="curriculum" class="{{ $field }}">
                <option value="">— الكل / الافتراضي —</option>
                @foreach($tracks as $key => $meta)
                    <option value="{{ $key }}" @selected(($selectedCurriculum ?? '') === $key)>{{ is_array($meta) ? ($meta['label'] ?? $key) : $meta }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-muted">المرحلة</label>
            <select name="year" class="{{ $field }}">
                <option value="">— اختر مرحلة —</option>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" @selected((int) ($selectedYearId ?? 0) === (int) $year->id)>
                        {{ $year->name }}@if($year->curriculum_type) · {{ $year->curriculum_type }}@endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-filter text-xs"></i> عرض البطاقات
            </button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-3 text-sm text-muted">
            @if($selectedYearName)
                المرحلة المعروضة: <strong class="text-ink">{{ $selectedYearName }}</strong>
                — عدد البطاقات: <strong class="text-ink">{{ $rows->count() }}</strong>
            @else
                اختر مرحلة لعرض بطاقات الأسعار كما في الموقع.
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-canvas-muted text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start">البطاقة على /pricing</th>
                        <th class="px-4 py-3 text-start">الربط</th>
                        <th class="px-4 py-3 text-start">خيارات السعر</th>
                        <th class="px-4 py-3 text-start">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="border-t border-line align-top">
                            <td class="px-4 py-3">
                                <div class="flex items-start gap-3">
                                    @if(!empty($row['cover']))
                                        <img src="{{ $row['cover'] }}" alt="" class="size-12 rounded-xl object-cover border border-line">
                                    @else
                                        <span class="inline-flex size-12 items-center justify-center rounded-xl bg-canvas-muted text-muted"><i class="fas fa-graduation-cap"></i></span>
                                    @endif
                                    <div>
                                        <div class="font-semibold text-ink">{{ $row['title'] }}</div>
                                        @if($row['badge'])
                                            <span class="text-xs text-accent">{{ $row['badge'] }}</span>
                                        @endif
                                        <p class="mt-1 max-w-md text-xs text-muted">{{ $row['description'] }}</p>
                                        <p class="mt-1 text-[11px] text-muted">مادة #{{ $row['subject_id'] ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($row['linked'])
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">باقة مربوطة #{{ $row['package_id'] }}</span>
                                @elseif($row['package_id'])
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">باقة عامة احتياطية #{{ $row['package_id'] }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">بدون باقة — تُنشأ عند الحفظ</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $unitHint = collect($row['options'] ?? [])
                                        ->first(fn ($o) => ($o['kind'] ?? '') !== 'urgent');
                                    $unitShown = (float) ($unitHint['unit_price'] ?? 0);
                                @endphp
                                @if($unitShown > 0)
                                    <div class="mb-1.5 text-xs font-semibold text-ink">
                                        سعر الحصة: {{ number_format($unitShown, 2) }} {{ currency_symbol() }}
                                    </div>
                                @endif
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach(($row['options'] ?? []) as $opt)
                                        <span class="rounded-lg border border-line px-2 py-1 text-[11px] text-ink-soft">
                                            {{ $opt['label'] ?? '' }}:
                                            <strong class="text-ink">{{ number_format((float) ($opt['total'] ?? 0), 2) }}</strong>
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($row['subject_id'])
                                    <a href="{{ route('admin.pricing-storefront.edit', ['subject' => $row['subject_id'], 'year' => $selectedYearId, 'curriculum' => $selectedCurriculum]) }}"
                                       class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-3 text-xs font-medium text-white">
                                        <i class="fas fa-pen text-[10px]"></i> تعديل كامل
                                    </a>
                                @else
                                    <span class="text-xs text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-muted">
                                لا توجد بطاقات لهذه المرحلة. تأكد من وجود مواد نشطة مرتبطة بالمرحلة من إدارة المواد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
