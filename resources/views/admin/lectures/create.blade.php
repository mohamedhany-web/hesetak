@extends('layouts.admin')

@section('title', 'إضافة محاضرة جديدة')
@section('page_title', 'إضافة محاضرة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $textareaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحتوى · المحاضرات المباشرة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إضافة محاضرة جديدة</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">إنشاء محاضرة جديدة وربطها ببرنامج ومحاضر</p>
        </div>
        <a href="{{ $preselectedCourseId ? route('admin.lectures.by-course', $preselectedCourseId) : route('admin.lectures.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm text-ink-soft hover:bg-accent-soft hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            {{ $preselectedCourseId ? 'العودة لمحاضرات البرنامج' : 'العودة للمحاضرات' }}
        </a>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-5 py-4">
            <h3 class="text-sm font-semibold text-ink">بيانات المحاضرة</h3>
        </div>

        <form action="{{ route('admin.lectures.store') }}" method="POST" enctype="multipart/form-data" class="p-5 sm:p-6">
            @csrf

            <div class="space-y-5">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}" for="course_id">البرنامج <span class="text-rose-600">*</span></label>
                        <select name="course_id" id="course_id" required class="{{ $fieldClass }}">
                            <option value="">اختر البرنامج</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('course_id', $preselectedCourseId ?? null) == $course->id ? 'selected' : '' }}>{{ Str::limit($course->title, 60) }}</option>
                            @endforeach
                        </select>
                        @error('course_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="instructor_id">المحاضر <span class="text-rose-600">*</span></label>
                        <select name="instructor_id" id="instructor_id" required class="{{ $fieldClass }}">
                            <option value="">اختر المحاضر</option>
                            @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}" {{ old('instructor_id') == $instructor->id ? 'selected' : '' }}>{{ $instructor->name }}</option>
                            @endforeach
                        </select>
                        @error('instructor_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="title">عنوان المحاضرة <span class="text-rose-600">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                           class="{{ $fieldClass }}"
                           placeholder="مثال: المحاضرة الأولى - المقدمة">
                    @error('title')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="description">الوصف</label>
                    <textarea name="description" id="description" rows="3"
                              class="{{ $textareaClass }}"
                              placeholder="وصف مختصر عن المحاضرة">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @include('partials.timezone-select', [
                        'value' => old('timezone', auth()->user()?->timezoneCode()),
                        'class' => $fieldClass,
                        'labelClass' => $labelClass,
                    ])
                    <div>
                        <label class="{{ $labelClass }}" for="scheduled_at">تاريخ ووقت المحاضرة <span class="text-rose-600">*</span></label>
                        <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}" required
                               class="{{ $fieldClass }}">
                        @error('scheduled_at')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="duration_minutes">مدة المحاضرة (دقيقة)</label>
                        <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', 60) }}" min="1"
                               class="{{ $fieldClass }}"
                               placeholder="60">
                        @error('duration_minutes')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="recording_url">رابط فيديو المحاضرة (Bunny / اختياري)</label>
                    <input type="url" name="recording_url" id="recording_url" value="{{ old('recording_url') }}"
                           class="{{ $fieldClass }}"
                           placeholder="https://…mediadelivery.net/…">
                    @error('recording_url')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="material_files">مرفقات المحاضرة (اختياري)</label>
                    <input type="file" name="material_files[]" id="material_files" multiple
                           class="{{ $fieldClass }}"
                           accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.png,.jpg,.jpeg">
                    <p class="mt-1 text-xs text-muted">يمكن رفع أكثر من ملف — نفس خيارات المعلم.</p>
                    @error('material_files')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    @error('material_files.*')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="notes">ملاحظات</label>
                    <textarea name="notes" id="notes" rows="3"
                              class="{{ $textareaClass }}"
                              placeholder="ملاحظات إضافية">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-line bg-[#f8faf9] p-4">
                    <p class="text-xs font-medium text-muted">خيارات المحاضرة</p>
                    <div class="mt-3 flex flex-wrap gap-x-6 gap-y-3">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="has_attendance_tracking" value="1" {{ old('has_attendance_tracking', true) ? 'checked' : '' }}
                                   class="size-4 rounded border-line text-accent focus:ring-accent/20">
                            <span class="text-sm text-ink">تتبع الحضور</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="has_assignment" value="1" {{ old('has_assignment') ? 'checked' : '' }}
                                   class="size-4 rounded border-line text-accent focus:ring-accent/20">
                            <span class="text-sm text-ink">يوجد واجب</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="has_evaluation" value="1" {{ old('has_evaluation') ? 'checked' : '' }}
                                   class="size-4 rounded border-line text-accent focus:ring-accent/20">
                            <span class="text-sm text-ink">يوجد تقييم للمحاضر</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-line pt-5">
                    <a href="{{ route('admin.lectures.index') }}"
                       class="btn-press inline-flex h-10 items-center rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                        إلغاء
                    </a>
                    <button type="submit"
                            class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                        <i class="fas fa-save text-xs"></i>
                        حفظ المحاضرة
                    </button>
                </div>
            </div>
        </form>
    </article>
</div>
@endsection
