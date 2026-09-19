@extends('layouts.admin')

@section('title', $academicSubject->name.' - مادة')
@section('page_title', $academicSubject->name)

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.academic-subjects.index', ['track' => $academicSubject->academic_year_id]) }}" class="hover:text-accent">المواد</a>
                · {{ $academicSubject->academicYear?->name ?? 'السنة' }}
            </p>
            <h2 class="mt-1 flex flex-wrap items-center gap-2 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">
                <span class="inline-flex size-9 items-center justify-center rounded-xl text-sm text-white" style="background: {{ $academicSubject->color ?: '#0B3D91' }}">
                    <i class="{{ $academicSubject->icon ?: 'fas fa-book' }}"></i>
                </span>
                {{ $academicSubject->name }}
            </h2>
            <p class="mt-1 font-mono text-sm text-muted">{{ $academicSubject->code }}
                ·
                <span class="{{ $academicSubject->is_active ? 'text-success' : 'text-danger' }}">
                    {{ $academicSubject->is_active ? 'نشطة' : 'موقوفة' }}
                </span>
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.academic-subjects.edit', $academicSubject) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink hover:border-accent/30 hover:text-accent">
                <i class="fas fa-pen text-xs"></i>
                تعديل المادة
            </a>
            <a href="{{ route('admin.academic-subjects.index', ['track' => $academicSubject->academic_year_id]) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm font-medium text-danger shadow-soft" role="alert">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-danger/10 text-danger"><i class="fas fa-exclamation text-sm"></i></span>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <section class="grid gap-3 sm:grid-cols-3">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">كورسات مربوطة</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $academicSubject->advancedCourses->count() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">السنة</p>
            <p class="mt-1 text-sm font-semibold text-ink">{{ $academicSubject->academicYear?->name ?? '—' }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الترتيب</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $academicSubject->order }}</p>
        </article>
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <article class="xl:col-span-2 overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">الكورسات داخل المادة</h3>
                <p class="mt-0.5 text-xs text-muted">كل كورس مرتبط بهذه المادة يظهر للطالب ضمن مسارها</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">الكورس</th>
                            <th class="px-4 py-3 text-start font-medium">المدرّب</th>
                            <th class="px-4 py-3 text-start font-medium">الحالة</th>
                            <th class="px-4 py-3 text-start font-medium">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($academicSubject->advancedCourses as $course)
                            <tr class="hover:bg-canvas/70">
                                <td class="px-4 py-3.5 font-medium text-ink">{{ $course->title }}</td>
                                <td class="px-4 py-3.5 text-ink-soft">{{ $course->instructor?->name ?? '—' }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $course->is_active ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                                        {{ $course->is_active ? 'نشط' : 'موقوف' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex flex-wrap gap-2">
                                        @if(Route::has('admin.advanced-courses.edit'))
                                            <a href="{{ route('admin.advanced-courses.edit', $course) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-[11px] font-medium text-ink hover:bg-canvas">تعديل</a>
                                        @endif
                                        <form method="POST" action="{{ route('admin.academic-subjects.detach-course', [$academicSubject, $course]) }}" onsubmit="return confirm('فك ربط هذا الكورس من المادة؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-press inline-flex h-8 items-center rounded-lg border border-danger/20 bg-danger/5 px-3 text-[11px] font-medium text-danger">فك الربط</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center text-sm text-muted">لا كورسات داخل هذه المادة بعد. استخدم النموذج الجانبي للربط.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <aside class="space-y-5">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">ربط كورس</h3>
                    <p class="mt-0.5 text-xs text-muted">انسب كورساً موجوداً لهذه المادة</p>
                </div>
                <form method="POST" action="{{ route('admin.academic-subjects.attach-course', $academicSubject) }}" class="space-y-4 p-4 sm:p-5">
                    @csrf
                    <div>
                        <label class="{{ $labelClass }}" for="course_id">الكورس <span class="text-danger">*</span></label>
                        <select id="course_id" name="course_id" required class="{{ $fieldClass }}">
                            <option value="">اختر كورساً…</option>
                            @foreach($availableCourses as $course)
                                <option value="{{ $course->id }}">
                                    {{ $course->title }}
                                    @if($course->academic_subject_id) (من مادة أخرى)@endif
                                </option>
                            @endforeach
                        </select>
                        @error('course_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent text-sm font-medium text-white">
                        <i class="fas fa-link text-xs"></i>
                        ربط بالمادة
                    </button>
                </form>
            </article>

            @if($academicSubject->description)
                <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft sm:p-5">
                    <h3 class="text-sm font-semibold text-ink">وصف المادة</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $academicSubject->description }}</p>
                </article>
            @endif
        </aside>
    </div>
</div>
@endsection
