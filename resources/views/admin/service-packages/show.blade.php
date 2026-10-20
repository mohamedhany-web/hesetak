@extends('layouts.admin')

@section('title', 'تفاصيل الباقة - حصتك')
@section('page_title', 'تفاصيل الباقة')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">باقات الحصص · #{{ $package->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">{{ $package->name }}</h2>
            <p class="mt-1 text-sm text-muted">هذه البيانات هي اللي تظهر في صفحة الباقات وفي الأكثر مبيعًا إن كانت مميزة.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('public.pricing') }}" target="_blank" rel="noopener" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm">عرض الموقع</a>
            <a href="{{ route('admin.service-packages.edit', $package) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">تعديل</a>
            <a href="{{ route('admin.service-packages.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm">رجوع</a>
        </div>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">السعر</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ $package->formattedPrice() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الحصص</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ $package->units_count }} · {{ $package->sessionMinutes() }} د</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">صفحة الباقات</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ $package->is_active ? 'ظاهرة' : 'مخفية' }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الأكثر مبيعًا</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ $package->is_featured ? 'على الرئيسية' : 'لا' }}</p>
        </article>
    </div>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">بيانات الواجهة</h3>
        </div>
        <dl class="divide-y divide-line text-sm">
            <div class="flex justify-between gap-6 px-4 py-3 sm:px-5"><dt class="text-muted">الشارة</dt><dd class="font-semibold text-ink">{{ $package->badge ?: '—' }}</dd></div>
            <div class="flex justify-between gap-6 px-4 py-3 sm:px-5"><dt class="text-muted">نبذة</dt><dd class="font-semibold text-ink">{{ $package->tagline ?: '—' }}</dd></div>
            <div class="flex justify-between gap-6 px-4 py-3 sm:px-5"><dt class="text-muted">الوصف</dt><dd class="max-w-xl text-end text-ink">{{ $package->description ?: '—' }}</dd></div>
            <div class="flex justify-between gap-6 px-4 py-3 sm:px-5"><dt class="text-muted">المرحلة</dt><dd class="font-semibold text-ink">{{ $package->academicYear->name ?? 'كل المراحل' }}</dd></div>
            <div class="flex justify-between gap-6 px-4 py-3 sm:px-5"><dt class="text-muted">المادة</dt><dd class="font-semibold text-ink">{{ $package->academicSubject->name ?? 'كل المواد' }}</dd></div>
            <div class="flex justify-between gap-6 px-4 py-3 sm:px-5"><dt class="text-muted">نوع المنهج</dt><dd class="font-semibold text-ink">{{ $trackLabel ?: 'كل المناهج' }}</dd></div>
            <div class="flex justify-between gap-6 px-4 py-3 sm:px-5"><dt class="text-muted">الصلاحية</dt><dd class="font-semibold text-ink">{{ $package->validityLabel() }}</dd></div>
            <div class="flex justify-between gap-6 px-4 py-3 sm:px-5"><dt class="text-muted">الترتيب</dt><dd class="font-semibold text-ink">{{ $package->sort_order }}</dd></div>
        </dl>
    </article>

    @if($package->featureList())
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">المزايا</h3>
            </div>
            <ul class="list-disc space-y-1 px-8 py-4 text-sm text-ink">
                @foreach($package->featureList() as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
        </article>
    @endif

    @if($package->giftList())
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">الهدايا</h3>
            </div>
            <ul class="list-disc space-y-1 px-8 py-4 text-sm text-ink">
                @foreach($package->giftList() as $gift)
                    <li>{{ $gift }}</li>
                @endforeach
            </ul>
        </article>
    @endif
</div>
@endsection
