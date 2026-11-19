@extends('layouts.admin')

@section('title', 'مكتبة الفيديوهات')
@section('page_title', 'مكتبة الفيديوهات')

@section('content')
@php
    $field = 'h-10 w-full rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted"><a href="{{ route('admin.libraries.index') }}" class="hover:text-accent">المكتبات</a> · فيديو</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">مكتبة الفيديوهات</h2>
            <p class="mt-1 text-sm text-muted">عام من الإدارة للطلاب + فيديوهات المعلمين لطلابهم فقط (مرئية هنا بالكامل).</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.libraries.folders.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm">
                <i class="fas fa-folder text-xs"></i> المجلدات
            </a>
            <a href="{{ route('admin.libraries.videos.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i> إضافة فيديو عام
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
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-soft"><div class="text-xs text-muted">الإجمالي</div><div class="mt-1 text-2xl font-semibold">{{ $stats['total'] }}</div></div>
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-soft"><div class="text-xs text-muted">منشور</div><div class="mt-1 text-2xl font-semibold text-success">{{ $stats['published'] }}</div></div>
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-soft"><div class="text-xs text-muted">عام (أكاديمية)</div><div class="mt-1 text-2xl font-semibold">{{ $stats['general'] }}</div></div>
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-soft"><div class="text-xs text-muted">من المعلمين</div><div class="mt-1 text-2xl font-semibold">{{ $stats['teacher'] }}</div></div>
    </div>

    <form method="GET" class="grid gap-3 rounded-2xl border border-line bg-surface p-4 shadow-soft md:grid-cols-6">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="بحث بالعنوان" class="{{ $field }} md:col-span-2">
        <select name="folder_id" class="{{ $field }}">
            <option value="">كل المجلدات</option>
            <option value="none" @selected(request('folder_id') === 'none')>بدون مجلد</option>
            @foreach(($folders ?? []) as $folder)
                <option value="{{ $folder->id }}" @selected((string) request('folder_id') === (string) $folder->id)>{{ $folder->name_ar }}</option>
            @endforeach
        </select>
        <select name="audience" class="{{ $field }}">
            <option value="">كل الظهور</option>
            <option value="general" @selected(request('audience') === 'general')>عام</option>
            <option value="teacher_students" @selected(request('audience') === 'teacher_students')>طلاب المعلم</option>
        </select>
        <select name="source" class="{{ $field }}">
            <option value="">كل المصادر</option>
            <option value="link" @selected(request('source') === 'link')>رابط</option>
            <option value="file" @selected(request('source') === 'file')>ملف Cloudflare</option>
        </select>
        <select name="published" class="{{ $field }}">
            <option value="">كل النشر</option>
            <option value="1" @selected(request('published') === '1')>منشور</option>
            <option value="0" @selected(request('published') === '0')>مسودة</option>
        </select>
        <button class="btn-press h-10 rounded-xl bg-ink px-4 text-sm font-medium text-white md:col-span-6 md:w-40">تصفية</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <table class="min-w-full text-sm">
            <thead class="bg-canvas-muted text-xs text-muted">
                <tr>
                    <th class="px-4 py-3 text-start">العنوان</th>
                    <th class="px-4 py-3 text-start">الظهور</th>
                    <th class="px-4 py-3 text-start">المجلد</th>
                    <th class="px-4 py-3 text-start">المصدر</th>
                    <th class="px-4 py-3 text-start">النشر</th>
                    <th class="px-4 py-3 text-start">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($videos as $video)
                    <tr class="border-t border-line">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-ink">{{ $video->title }}</div>
                            <div class="text-xs text-muted">
                                @if($video->isTeacherPrivate() && $video->instructor)
                                    معلم: {{ $video->instructor->name }}
                                @elseif($video->creator)
                                    بواسطة {{ $video->creator->name }}
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $video->isTeacherPrivate() ? 'bg-warning/10 text-warning' : 'bg-accent/10 text-accent' }}">
                                {{ $video->audienceLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $video->folder?->displayName() ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $video->sourceLabel() }}</td>
                        <td class="px-4 py-3">
                            <button type="submit" form="toggle-{{ $video->id }}" class="rounded-full px-2.5 py-1 text-xs font-bold {{ $video->is_published ? 'bg-success/10 text-success' : 'bg-canvas-muted text-muted' }}">
                                {{ $video->is_published ? 'منشور' : 'مسودة' }}
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.libraries.videos.watch', $video) }}" class="text-accent hover:underline">مشاهدة</a>
                                <a href="{{ route('admin.libraries.videos.edit', $video) }}" class="text-accent hover:underline">تعديل</a>
                                <button type="submit" form="destroy-{{ $video->id }}" class="text-danger hover:underline" onclick="return confirm('حذف الفيديو؟')">حذف</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-muted">لا توجد فيديوهات بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $videos->links() }}</div>

    @foreach($videos as $video)
        <form id="toggle-{{ $video->id }}" method="POST" action="{{ route('admin.libraries.videos.toggle', $video) }}" class="hidden">@csrf</form>
        <form id="destroy-{{ $video->id }}" method="POST" action="{{ route('admin.libraries.videos.destroy', $video) }}" class="hidden">@csrf @method('DELETE')</form>
    @endforeach
</div>
@endsection
