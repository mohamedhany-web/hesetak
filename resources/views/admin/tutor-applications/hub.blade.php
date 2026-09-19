@extends('layouts.admin')

@section('title', 'لوحة التوظيف - حصتك')
@section('page_title', 'توظيف المعلمين')

@section('content')
@php
    $kpis = [
        ['label' => 'مسودات', 'value' => $stats['draft'] ?? 0, 'href' => route('admin.tutor-applications.index', ['status' => 'draft']), 'icon' => 'fa-file-alt', 'tone' => 'muted'],
        ['label' => 'قيد المراجعة', 'value' => $stats['pending'], 'href' => route('admin.tutor-applications.index', ['status' => 'pending']), 'icon' => 'fa-inbox', 'tone' => 'metal'],
        ['label' => 'بانتظار التفعيل', 'value' => $stats['approved'], 'href' => route('admin.tutor-applications.index', ['status' => 'approved']), 'icon' => 'fa-user-check', 'tone' => 'accent'],
        ['label' => 'مفعّلون', 'value' => $stats['activated'], 'href' => route('admin.tutor-applications.activated'), 'icon' => 'fa-chalkboard-teacher', 'tone' => 'accent'],
        ['label' => 'مرفوض', 'value' => $stats['rejected'], 'href' => route('admin.tutor-applications.index', ['status' => 'rejected']), 'icon' => 'fa-ban', 'tone' => 'danger'],
        ['label' => 'معلمون نشطون', 'value' => $stats['instructors'], 'href' => route('admin.tutor-applications.activated'), 'icon' => 'fa-users', 'tone' => 'metal'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
        'danger' => 'bg-danger/10 text-danger',
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الإدارة · التوظيف</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">لوحة التوظيف</h2>
            <p class="mt-1 text-sm text-muted">مراجعة طلبات التقديم، ثم القبول، ثم التفعيل — التفعيل هو ما يفتح لوحة المعلم ويظهر الملف للطلاب.</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <button type="button" onclick="navigator.clipboard.writeText(@js($applyUrl))" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:text-accent">
                <i class="fas fa-copy text-xs"></i>
                نسخ اللينك
            </button>
            <a href="{{ $applyUrl }}" target="_blank" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-external-link-alt text-xs"></i>
                لينك التقديم العام
            </a>
        </div>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-3 sm:px-5">
            <p class="text-xs font-medium text-muted">رابط التقديم العام</p>
            <a href="{{ $applyUrl }}" class="mt-1 block break-all text-sm font-semibold text-accent underline" dir="ltr" target="_blank">{{ $applyUrl }}</a>
        </div>
    </article>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach($kpis as $kpi)
            <a href="{{ $kpi['href'] }}" class="rounded-2xl border border-line bg-surface p-4 shadow-soft transition hover:border-accent/30">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($kpi['value']) }}</p>
            </a>
        @endforeach
    </section>

    <div class="grid gap-5 lg:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                <div>
                    <h3 class="text-base font-semibold text-ink">طلبات بانتظار المراجعة</h3>
                    <p class="mt-0.5 text-xs text-muted">أحدث الطلبات الواردة</p>
                </div>
                <a href="{{ route('admin.tutor-applications.index', ['status' => 'pending']) }}" class="text-xs font-semibold text-accent hover:underline">الكل</a>
            </div>
            <ul class="divide-y divide-line">
                @forelse($recentPending as $app)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink">{{ $app->full_name }}</p>
                            <p class="mt-0.5 truncate text-xs text-muted">{{ $app->headline }} · {{ $app->created_at?->diffForHumans() }}</p>
                        </div>
                        <a href="{{ route('admin.tutor-applications.show', $app) }}" class="btn-press inline-flex h-8 shrink-0 items-center rounded-lg bg-accent-soft px-3 text-xs font-semibold text-accent hover:bg-accent hover:text-white">مراجعة</a>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center">
                        <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <p class="text-sm font-medium text-ink">لا توجد طلبات جديدة</p>
                    </li>
                @endforelse
            </ul>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                <div>
                    <h3 class="text-base font-semibold text-ink">بانتظار تفعيل الحساب</h3>
                    <p class="mt-0.5 text-xs text-muted">مقبولون جاهزون للتفعيل</p>
                </div>
                <a href="{{ route('admin.tutor-applications.index', ['status' => 'approved']) }}" class="text-xs font-semibold text-accent hover:underline">الكل</a>
            </div>
            <ul class="divide-y divide-line">
                @forelse($awaitingActivation as $app)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink">{{ $app->full_name }}</p>
                            <p class="mt-0.5 truncate text-xs text-muted" dir="ltr">{{ $app->email }}</p>
                        </div>
                        <a href="{{ route('admin.tutor-applications.show', $app) }}" class="btn-press inline-flex h-8 shrink-0 items-center rounded-lg bg-accent px-3 text-xs font-semibold text-white">تفعيل</a>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center">
                        <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-canvas-muted text-muted">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <p class="text-sm font-medium text-ink">لا يوجد طلبات بانتظار التفعيل</p>
                    </li>
                @endforelse
            </ul>
        </article>
    </div>

    <article class="rounded-2xl border border-dashed border-line bg-canvas px-4 py-5 sm:px-5">
        <p class="text-sm font-semibold text-ink">مسار العمل</p>
        <ol class="mt-2 list-decimal space-y-1.5 pr-5 text-sm leading-7 text-muted">
            <li>المتقدم يسجّل إيميل + كلمة مرور فيُنشأ حسابه ويُوجَّه لإكمال الملف التعريفي.</li>
            <li>يكمل الصورة والهوية والشهادات والفيديو ويرسل للمراجعة — بدون دخول للوحة المعلم.</li>
            <li>تراجع الإدارة الطلب من <span class="font-medium text-ink">مراجعة الطلبات</span> ثم قبول.</li>
            <li><span class="font-medium text-ink">تفعيل الحساب</span> يفتح لوحة المعلم ويظهر الملف للطلاب (بنفس الإيميل وكلمة المرور).</li>
        </ol>
    </article>
</div>
@endsection
