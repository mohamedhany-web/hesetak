@extends('layouts.admin')

@section('title', 'باقات '.$group->title.' - حصتك')
@section('page_title', 'باقات المجموعة')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">إدارة المحتوى · مجموعات فردية · باقات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $group->title }}</h2>
            <p class="mt-1 text-sm text-muted">الباقات = الأسعار وعدد الحصص التي يختارها الطالب أو فريق التسكين</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.tutoring-groups.edit', [$type, $group]) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:text-accent">تعديل المجموعة</a>
            <a href="{{ route('admin.tutoring-groups.packages.create', $group) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i> باقة جديدة
            </a>
        </div>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'دور الباقات في المسار الفردي',
        'body' => 'بدون باقة نشطة لا يستطيع الطالب الحجز ولا يظهر رصيد للتسكين. أنشئ باقة واحدة على الأقل ثم راجع جداول المدربين.',
        'steps' => [
            'أضف باقة (مدة بالأشهر + عدد الحصص + السعر).',
            'فعّل الظهور أو التمييز إن أردت إبرازها في الموقع.',
            'بعد شراء الطالب أو منح الرصيد: استخدم التسكين لتثبيت المواعيد.',
        ],
    ])

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($packages as $package)
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="text-base font-semibold text-ink">{{ $package->name }}</h3>
                    @if($package->is_featured)
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">مميزة</span>
                    @endif
                </div>
                <p class="mt-2 text-2xl font-bold text-accent">{{ $package->formattedPrice() }}</p>
                @if($package->formattedOriginalPrice())
                    <p class="text-sm text-muted line-through">{{ $package->formattedOriginalPrice() }}</p>
                    <p class="text-xs font-semibold text-emerald-600">وفر {{ $package->savingsPercent() }}%</p>
                @endif
                <ul class="mt-3 space-y-1 text-xs text-muted">
                    <li>{{ $package->duration_months }} شهر · {{ $package->sessions_count }} حصة</li>
                    <li>{{ $package->sessions_per_month }} حصص/شهر · {{ number_format((float)$package->hourly_rate,0) }} {{ $package->currency }}/ساعة</li>
                    <li>{{ $package->is_active ? 'نشطة' : 'متوقفة' }}</li>
                </ul>
                <div class="mt-4 flex gap-3 text-sm">
                    <a href="{{ route('admin.tutoring-groups.packages.edit', [$group, $package]) }}" class="text-accent hover:underline">تعديل</a>
                    <form method="POST" action="{{ route('admin.tutoring-groups.packages.destroy', [$group, $package]) }}" onsubmit="return confirm('حذف الباقة؟');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-danger hover:underline">حذف</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-dashed border-line bg-canvas px-4 py-12 text-center text-muted">لا توجد باقات بعد.</div>
        @endforelse
    </div>
    @if($packages->hasPages())
        <div>{{ $packages->links() }}</div>
    @endif
</div>
@endsection
