@extends('layouts.admin')

@section('title', 'مكتبة الملفات')
@section('page_title', 'مكتبة الملفات')

@section('content')
@php
    $filesStat = (int) ($stats['materials_total'] ?? 0) + (int) ($stats['manahij_items'] ?? 0);
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">التعليم · مكتبات الطلاب</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">مركز مكتبة الملفات</h2>
            <p class="mt-1 text-sm text-muted">الماتريال والمناهج التفاعلية فكرة واحدة للملفات — مع فيديوهات وهيكل كورسات بجانبها.</p>
        </div>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'مكتبة الملفات باختصار',
        'body' => 'الطالب يرى الماتريال والمناهج التفاعلية كملفات في مكتبة واحدة. المجلدات تنظّم، والفيديوهات وهيكل الكورسات مسارات منفصلة.',
        'steps' => [
            'ملفات الماتريال: ارفع PDF/HTML وحدد الظهور للطالب.',
            'ملفات المناهج التفاعلية: ارفع PPTX على Cloudflare R2 وحوّلها لشرائح.',
            'الفيديوهات: أدر التسجيلات واربطها بمجلدات.',
            'هيكل الكورسات: رتّب سنوات/مواد/عناصر المنهج داخل البرامج.',
        ],
    ])

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm text-ink">{{ session('success') }}</div>
    @endif

    <section class="rounded-2xl border border-accent/30 bg-accent/5 p-5 shadow-soft">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium text-accent">فكرة واحدة</p>
                <h3 class="mt-1 text-xl font-semibold text-ink">مكتبة الملفات</h3>
                <p class="mt-1 text-sm text-muted">{{ number_format($filesStat) }} ملف/منهج · ماتريال + مناهج تفاعلية على Cloudflare R2 عند الرفع.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.libraries.materials.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl bg-ink px-3 text-sm text-white">إدارة الماتريال</a>
                <a href="{{ route('admin.curriculum-library.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl bg-accent px-3 text-sm font-medium text-white">المناهج التفاعلية</a>
                <a href="{{ route('admin.libraries.folders.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-3 text-sm">المجلدات</a>
            </div>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <a href="{{ route('admin.libraries.materials.index') }}" class="rounded-xl border border-line bg-surface p-4 hover:border-accent/40">
                <div class="text-xs text-muted">ماتريال</div>
                <div class="mt-1 text-2xl font-semibold text-ink">{{ number_format($stats['materials_total']) }}</div>
                <p class="mt-1 text-xs text-muted">{{ $stats['materials_visible'] }} ظاهر</p>
            </a>
            <a href="{{ route('admin.curriculum-library.index') }}" class="rounded-xl border border-line bg-surface p-4 hover:border-accent/40">
                <div class="text-xs text-muted">مناهج تفاعلية</div>
                <div class="mt-1 text-2xl font-semibold text-ink">{{ number_format($stats['manahij_items'] ?? 0) }}</div>
                <p class="mt-1 text-xs text-muted">{{ ($stats['manahij_categories'] ?? 0) }} تصنيف · {{ ($stats['manahij_materials'] ?? 0) }} مادة</p>
            </a>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-3">
        <a href="{{ route('admin.libraries.videos.index') }}" class="group rounded-2xl border border-line bg-surface p-5 shadow-soft transition hover:border-accent/40 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-accent/10 text-accent"><i class="fas fa-film"></i></div>
                <span class="text-3xl font-semibold text-ink">{{ number_format($stats['videos_ready']) }}</span>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-ink group-hover:text-accent">مكتبة الفيديوهات</h3>
            <p class="mt-1 text-sm text-muted">فيديوهات أطفال ومسلسلات إسلامية</p>
            <p class="mt-3 text-xs font-medium text-ink-soft">{{ $stats['videos_published'] }} منشور · {{ $stats['video_folders'] ?? 0 }} مجلد</p>
        </a>
        <a href="{{ route('admin.libraries.folders.index') }}" class="group rounded-2xl border border-line bg-surface p-5 shadow-soft transition hover:border-accent/40 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-accent/10 text-accent"><i class="fas fa-folder-open"></i></div>
                <span class="text-3xl font-semibold text-ink">{{ number_format($stats['video_folders'] ?? 0) }}</span>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-ink group-hover:text-accent">مجلدات المكتبة</h3>
            <p class="mt-1 text-sm text-muted">تصنيف المحتوى + معلم × سنة</p>
        </a>
        <a href="{{ route('admin.libraries.curriculum.index') }}" class="group rounded-2xl border border-line bg-surface p-5 shadow-soft transition hover:border-accent/40 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-accent/10 text-accent"><i class="fas fa-sitemap"></i></div>
                <span class="text-3xl font-semibold text-ink">{{ number_format($stats['years']) }}</span>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-ink group-hover:text-accent">هيكل الكورسات</h3>
            <p class="mt-1 text-sm text-muted">سنوات ومواد ومناهج الكورسات المسجّلة</p>
            <p class="mt-3 text-xs font-medium text-ink-soft">{{ $stats['subjects'] }} مادة · {{ $stats['courses'] }} كورس</p>
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                <h3 class="font-semibold text-ink">أحدث الماتريال</h3>
                <a href="{{ route('admin.libraries.materials.create') }}" class="text-sm font-medium text-accent hover:underline">رفع ملف</a>
            </div>
            <div class="divide-y divide-line">
                @forelse($recentMaterials as $m)
                    <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <div class="truncate font-medium text-ink">{{ $m->title ?: $m->file_name }}</div>
                            <div class="truncate text-xs text-muted">{{ $m->lecture?->course?->title }} · {{ $m->lecture?->title }}</div>
                        </div>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $m->is_visible_to_student ? 'bg-success/10 text-success' : 'bg-canvas-muted text-muted' }}">
                            {{ $m->is_visible_to_student ? 'ظاهر' : 'مخفي' }}
                        </span>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-muted">لا ماتريال بعد.</p>
                @endforelse
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                <h3 class="font-semibold text-ink">أحدث فيديوهات المكتبة</h3>
                <a href="{{ route('admin.libraries.videos.create') }}" class="text-sm font-medium text-accent hover:underline">إضافة فيديو</a>
            </div>
            <div class="divide-y divide-line">
                @forelse($recentVideos as $v)
                    <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <div class="truncate font-medium text-ink">{{ $v->title }}</div>
                            <div class="truncate text-xs text-muted">{{ $v->folder?->displayName() ?: 'عام' }} · {{ $v->sourceLabel() }}</div>
                        </div>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $v->is_published ? 'bg-success/10 text-success' : 'bg-canvas-muted text-muted' }}">
                            {{ $v->is_published ? 'منشور' : 'مسودة' }}
                        </span>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-muted">لا فيديوهات بعد.</p>
                @endforelse
            </div>
        </article>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="text-xs text-muted">أقسام المناهج</div>
            <div class="mt-1 text-2xl font-semibold text-ink">{{ number_format($stats['sections']) }}</div>
        </div>
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="text-xs text-muted">عناصر المنهج</div>
            <div class="mt-1 text-2xl font-semibold text-ink">{{ number_format($stats['curriculum_items']) }}</div>
        </div>
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="text-xs text-muted">كورسات</div>
            <div class="mt-1 text-2xl font-semibold text-ink">{{ number_format($stats['courses']) }}</div>
        </div>
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="text-xs text-muted">المواد الدراسية</div>
            <div class="mt-1 text-2xl font-semibold text-ink">{{ number_format($stats['subjects']) }}</div>
        </div>
    </div>
</div>
@endsection
