@extends('layouts.admin')

@section('title', 'المواد')
@section('page_title', 'المواد')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">مواد عامة (قديم)</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">المواد</h2>
            <p class="mt-1 text-sm text-muted">للمراحل والمواد الدراسية استخدم قسم «المواد الدراسية».</p>
        </div>
        <div class="flex gap-2">
            @if(Route::has('admin.academic-subjects.index'))
                <a href="{{ route('admin.academic-subjects.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm">المواد الدراسية</a>
            @endif
            <a href="{{ route('admin.subjects.create') }}" class="btn-press inline-flex h-9 items-center rounded-xl bg-accent px-4 text-sm font-medium text-white">إضافة</a>
        </div>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <table class="min-w-full text-sm">
            <thead class="border-b border-line bg-canvas text-xs text-muted">
                <tr>
                    <th class="px-4 py-3 text-start">الاسم</th>
                    <th class="px-4 py-3 text-start">الكورسات</th>
                    <th class="px-4 py-3 text-start">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse($subjects as $subject)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-ink">{{ $subject->name }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $subject->courses_count ?? 0 }}</td>
                        <td class="px-4 py-3">{{ $subject->is_active ? 'نشطة' : 'معطّلة' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-10 text-center text-muted">لا مواد بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </article>
</div>
@endsection
