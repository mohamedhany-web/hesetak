@extends('layouts.admin')

@section('title', 'باقات الخدمات - حصتك')
@section('page_title', 'باقات الخدمات')

@section('content')
@php
    $kpis = [
        ['label' => 'إجمالي الباقات', 'value' => $stats['total'], 'icon' => 'fa-box'],
        ['label' => 'نشطة', 'value' => $stats['active'], 'icon' => 'fa-check'],
        ['label' => 'عامة', 'value' => $stats['global'], 'icon' => 'fa-globe'],
        ['label' => 'أرصدة صادرة', 'value' => $stats['sold'], 'icon' => 'fa-layer-group'],
    ];
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">التجارة · رصيد الحصص</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">باقات خدمات الطالب</h2>
            <p class="mt-1 text-sm text-muted">يدفع الطالب → يحصل رصيد حصص → يستهلك عند الإتمام → يشحن عند النفاد.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.service-packages.grant') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-accent/30 bg-accent/10 px-4 text-sm font-medium text-accent">
                <i class="fas fa-user-plus text-xs"></i> منح باقة يدوياً
            </a>
            <a href="{{ route('admin.service-packages.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i> باقة جديدة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm text-ink">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <div class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-center gap-2 text-xs text-muted"><i class="fas {{ $kpi['icon'] }}"></i>{{ $kpi['label'] }}</div>
                <p class="mt-2 text-2xl font-semibold text-ink">{{ $kpi['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <table class="min-w-full text-sm">
            <thead class="bg-canvas-muted text-xs text-muted">
                <tr>
                    <th class="px-4 py-3 text-start">الباقة</th>
                    <th class="px-4 py-3 text-start">النطاق</th>
                    <th class="px-4 py-3 text-start">الحصص</th>
                    <th class="px-4 py-3 text-start">مدة الحصة</th>
                    <th class="px-4 py-3 text-start">الصلاحية</th>
                    <th class="px-4 py-3 text-start">السعر</th>
                    <th class="px-4 py-3 text-start">سعر الحصة</th>
                    <th class="px-4 py-3 text-start">الحالة</th>
                    <th class="px-4 py-3 text-start">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($packages as $package)
                    <tr class="border-t border-line">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-ink">{{ $package->name }}</div>
                            @if($package->badge)<span class="text-xs text-accent">{{ $package->badge }}</span>@endif
                            @if($package->schoolProgramLabel())
                                <div class="text-xs text-muted"><i class="fas fa-school"></i> {{ $package->schoolProgramLabel() }}</div>
                            @endif
                            @if($package->tutoringGroup)<div class="text-xs text-muted">{{ $package->tutoringGroup->title }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-ink-soft">
                            @if($package->plan_type)
                                <div class="font-semibold text-ink">{{ $package->planLabel() }}</div>
                                <div class="text-xs text-muted">{{ $package->termLabel() }} · {{ $package->weeklySessionsTotal() }}/أسبوع</div>
                            @else
                                {{ $package->label() }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            {{ $package->units_count }} حصة
                            <span class="block text-xs text-muted">{{ $package->totalHoursLabel() }} إجمالاً</span>
                        </td>
                        <td class="px-4 py-3">{{ $package->sessionMinutes() }} دقيقة</td>
                        <td class="px-4 py-3 text-xs text-muted">{{ $package->validityLabel() }}</td>
                        <td class="px-4 py-3 font-semibold">
                            {{ $package->formattedPrice() }}
                            @if($package->savingsPercent() > 0)
                                <span class="block text-xs text-success">وفر {{ $package->savingsPercent() }}%</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $package->formattedPricePerUnit() }}</td>
                        <td class="px-4 py-3">{{ $package->is_active ? 'نشطة' : 'موقوفة' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.service-packages.grant', ['service_package_id' => $package->id]) }}" class="text-success hover:underline">منح</a>
                                <a href="{{ route('admin.service-packages.edit', $package) }}" class="text-accent hover:underline">تعديل</a>
                                <form method="POST" action="{{ route('admin.service-packages.toggle-status', $package) }}">@csrf<button class="text-ink-soft hover:underline">تبديل</button></form>
                                <form method="POST" action="{{ route('admin.service-packages.destroy', $package) }}" onsubmit="return confirm('حذف؟')">@csrf @method('DELETE')<button class="text-danger hover:underline">حذف</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-muted">لا توجد باقات بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
