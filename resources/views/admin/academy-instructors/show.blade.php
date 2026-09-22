@extends('layouts.admin')

@section('title', $instructor->name.' - مدرّب أكاديمية')
@section('page_title', $instructor->name)

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.academy-instructors.index') }}" class="hover:text-accent">مدربو الأكاديمية</a>
            </p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $instructor->name }}</h2>
            <p class="mt-1 text-sm text-muted">{{ $instructor->email }} @if($instructor->phone)· {{ $instructor->phone }}@endif</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(Route::has('admin.teachers.show'))
                <a href="{{ route('admin.teachers.show', $instructor) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">مركز التحكم</a>
            @endif
            <a href="{{ route('admin.academy-instructors.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink-soft">رجوع</a>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-danger/20 bg-danger/5 p-4 text-sm text-danger">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">كورسات</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ $courses->count() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">توصيفات</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ $assignments->where('status', 'active')->count() }}</p>
        </article>
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-5">

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">الكورسات</h3>
                </div>
                <div class="divide-y divide-line">
                    @forelse($courses as $course)
                        <div class="px-4 py-3 sm:px-5">
                            <p class="font-medium text-ink">{{ $course->title }}</p>
                            <p class="text-xs text-muted">
                                {{ $course->academicYear?->name ?? '—' }}
                                @if($course->academicSubject) · {{ $course->academicSubject->name }}@endif
                            </p>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-muted">لا كورسات مسندة.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <aside class="space-y-5">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4">
                    <h3 class="text-base font-semibold text-ink">توصيف يدوي لطالب</h3>
                    <p class="mt-0.5 text-xs text-muted">اربط طالباً بهذا المدرّب بنطاق محدد</p>
                </div>
                <form method="POST" action="{{ route('admin.academy-instructors.assignments.store') }}" class="space-y-3 p-4">
                    @csrf
                    <input type="hidden" name="instructor_id" value="{{ $instructor->id }}">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted">الطالب</label>
                        <select name="student_id" required class="{{ $fieldClass }}">
                            <option value="">اختر طالباً…</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }} — {{ $student->email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted">النطاق</label>
                        <select name="scope" class="{{ $fieldClass }}">
                            <option value="general">عام</option>
                            <option value="courses">كورسات</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted">السنة الأكاديمية (اختياري)</label>
                        <select name="academic_year_id" class="{{ $fieldClass }}">
                            <option value="">—</option>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted">ملاحظات</label>
                        <textarea name="notes" rows="3" class="{{ $areaClass }}" placeholder="سبب التوصيف أو جدول المتابعة…">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent text-sm font-medium text-white">
                        <i class="fas fa-user-plus text-xs"></i> حفظ التوصيف
                    </button>
                </form>
            </article>

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4">
                    <h3 class="text-base font-semibold text-ink">التوصيفات الحالية</h3>
                </div>
                <div class="divide-y divide-line max-h-[420px] overflow-y-auto">
                    @forelse($assignments as $assignment)
                        <div class="px-4 py-3 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-medium text-ink truncate">{{ $assignment->student?->name }}</p>
                                    <p class="text-[11px] text-muted">{{ $assignment->scopeLabel() }} · {{ $assignment->statusLabel() }}</p>
                                    @if($assignment->academicYear)
                                        <p class="text-[11px] text-muted">{{ $assignment->academicYear->name }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach(['active' => 'نشط', 'paused' => 'إيقاف', 'ended' => 'إنهاء'] as $st => $label)
                                    <form method="POST" action="{{ route('admin.academy-instructors.assignments.status', $assignment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $st }}">
                                        <button type="submit" class="rounded-lg border border-line px-2 py-1 text-[10px] font-semibold text-ink-soft hover:bg-canvas {{ $assignment->status === $st ? 'bg-accent-soft text-accent border-accent/30' : '' }}">{{ $label }}</button>
                                    </form>
                                @endforeach
                                <form method="POST" action="{{ route('admin.academy-instructors.assignments.destroy', $assignment) }}" onsubmit="return confirm('حذف التوصيف؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-danger/20 px-2 py-1 text-[10px] font-semibold text-danger">حذف</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-muted">لا توصيفات يدوية بعد.</p>
                    @endforelse
                </div>
            </article>
        </aside>
    </div>
</div>
@endsection
