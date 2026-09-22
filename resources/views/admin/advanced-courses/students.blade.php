@extends('layouts.admin')

@section('title', 'طلاب البرنامج - ' . config('app.name'))
@section('page_title', 'طلاب البرنامج')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.advanced-courses.index') }}" class="hover:text-accent">{{ __('admin.courses_management') }}</a>
                <span class="mx-1">·</span>
                <a href="{{ route('admin.advanced-courses.show', $advancedCourse) }}" class="hover:text-accent">{{ Str::limit($advancedCourse->title, 36) }}</a>
            </p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">طلاب البرنامج</h2>
            <p class="mt-1 text-sm text-muted">{{ $advancedCourse->title }} · {{ $advancedCourse->enrollments->count() }} تسجيل</p>
        </div>
        <a href="{{ route('admin.advanced-courses.show', $advancedCourse) }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm text-ink-soft hover:bg-accent-soft hover:text-accent">
            العودة للبرنامج
        </a>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">الطالب</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">التقدم</th>
                        <th class="px-4 py-3 text-start font-medium">تاريخ التسجيل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($advancedCourse->enrollments as $enrollment)
                        <tr class="hover:bg-canvas/60">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink">{{ $enrollment->user?->name ?? '—' }}</p>
                                <p class="text-xs text-muted">{{ $enrollment->user?->email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg bg-canvas px-2 py-1 text-xs font-semibold text-ink">{{ $enrollment->status }}</span>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-ink">{{ number_format((float) ($enrollment->progress ?? 0), 0) }}%</td>
                            <td class="px-4 py-3 text-muted tabular-nums">{{ optional($enrollment->created_at)->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-muted">لا يوجد طلاب مسجّلون بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    @if($availableStudents->isNotEmpty())
        <article class="rounded-2xl border border-dashed border-line bg-surface p-5 shadow-soft">
            <h3 class="text-sm font-semibold text-ink">طلاب متاحون للتسجيل ({{ $availableStudents->count() }})</h3>
            <p class="mt-1 text-xs text-muted">التسجيل اليدوي يتم من الطلبات أو منح الاشتراك — القائمة أدناه للمراجعة فقط.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($availableStudents->take(24) as $student)
                    <span class="inline-flex rounded-lg border border-line px-2.5 py-1 text-xs text-ink">{{ $student->name }}</span>
                @endforeach
                @if($availableStudents->count() > 24)
                    <span class="text-xs text-muted">+{{ $availableStudents->count() - 24 }}</span>
                @endif
            </div>
        </article>
    @endif
</div>
@endsection
