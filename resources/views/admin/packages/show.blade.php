@extends('layouts.admin')

@section('title', $package->name . ' - ' . config('app.name'))
@section('page_title', 'تفاصيل الباقة')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الباقات والأسعار · برامج مسجّلة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ $package->name }}</h2>
            @if($package->trackLabel())
                <p class="mt-1 text-sm text-muted">المسار: {{ $package->trackLabel() }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.packages.edit', $package) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-edit text-xs"></i> تعديل
            </a>
            <a href="{{ route('admin.packages.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft">رجوع</a>
        </div>
    </section>

    <div class="grid gap-5 lg:grid-cols-3">
        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft lg:col-span-1">
            @if($package->thumbnail)
                <img src="{{ storage_asset($package->thumbnail) }}" alt="" class="mb-4 h-48 w-full rounded-xl object-cover border border-line">
            @else
                <div class="mb-4 flex h-48 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-box text-3xl"></i>
                </div>
            @endif
            <div class="space-y-3">
                <div>
                    <p class="text-xs font-medium text-muted">السعر</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $package->formattedPrice(2) }}</p>
                    @if($package->formattedOriginalPrice(2))
                        <p class="text-sm text-muted line-through">{{ $package->formattedOriginalPrice(2) }}</p>
                        <p class="text-sm font-medium text-emerald-700">خصم {{ $package->discount_percentage }}%</p>
                    @endif
                </div>
                @php $bundleSave = $package->coursesBundleSavings(); @endphp
                @if($bundleSave > 0)
                    <div class="rounded-xl border border-accent/20 bg-accent-soft/40 px-3 py-2 text-sm">
                        توفير مقابل مجموع البرامج:
                        <span class="font-semibold tabular-nums">{{ number_format($bundleSave, 2) }} {{ $package->currencyCode() }}</span>
                    </div>
                @endif
                <div class="flex flex-wrap gap-1">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $package->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ $package->is_active ? 'نشط' : 'معطّل' }}
                    </span>
                    @if($package->is_featured)
                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800">مميز</span>
                    @endif
                    @if($package->is_popular)
                        <span class="inline-flex rounded-full bg-accent-soft px-2.5 py-0.5 text-xs font-medium text-accent">الأكثر شعبية</span>
                    @endif
                </div>
            </div>
        </article>

        <div class="space-y-5 lg:col-span-2">
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-sm font-semibold text-ink">الوصف</h3>
                <p class="mt-2 whitespace-pre-line text-sm text-muted">{{ $package->description ?: 'لا يوجد وصف' }}</p>
                @if(filled($package->card_summary))
                    <h3 class="mt-5 text-sm font-semibold text-ink">نص البطاقة</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-muted">{{ $package->card_summary }}</p>
                @endif
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-line px-3 py-2">
                        <p class="text-xs text-muted">العملة</p>
                        <p class="mt-1 font-semibold text-ink">{{ $package->currencyCode() }}</p>
                    </div>
                    <div class="rounded-xl border border-line px-3 py-2">
                        <p class="text-xs text-muted">عدد البرامج</p>
                        <p class="mt-1 font-semibold tabular-nums text-ink">{{ $package->courses->count() }}</p>
                    </div>
                    <div class="rounded-xl border border-line px-3 py-2">
                        <p class="text-xs text-muted">مدة الصلاحية</p>
                        <p class="mt-1 font-semibold text-ink">{{ $package->duration_days ? $package->duration_days.' يوم' : 'دائمة' }}</p>
                    </div>
                </div>
            </article>

            @if($package->features && count($package->features) > 0)
                <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                    <h3 class="text-sm font-semibold text-ink">المميزات</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach($package->features as $feature)
                            <li class="flex items-start gap-2 text-sm text-ink">
                                <i class="fas fa-check-circle mt-0.5 text-accent"></i>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endif

            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-sm font-semibold text-ink">البرامج في الباقة ({{ $package->courses->count() }})</h3>
                @if($package->courses->count() > 0)
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach($package->courses as $course)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-line px-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-ink">{{ $course->title }}</p>
                                    <p class="mt-0.5 text-xs tabular-nums text-muted">
                                        @if((float) $course->price > 0)
                                            {{ number_format((float) $course->price, 2) }} {{ currency_symbol() }}
                                        @else
                                            مجاني
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ route('admin.advanced-courses.show', $course) }}" class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg border border-line text-muted hover:bg-accent-soft hover:text-accent">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-center text-sm text-muted py-6">لا توجد برامج في هذه الباقة</p>
                @endif
            </article>
        </div>
    </div>
</div>
@endsection
