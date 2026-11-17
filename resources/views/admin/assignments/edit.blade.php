@extends('layouts.admin')

@section('title', 'تعديل الواجب: ' . $assignment->title . ' - ' . config('app.name'))
@section('page_title', 'تعديل الواجب')

@php
    $courseId = $assignment->advanced_course_id ?? $assignment->course_id;
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $textareaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-accent">لوحة التحكم</a>
                <span class="mx-1">·</span>
                <a href="{{ route('admin.assignments.index') }}" class="hover:text-accent">الواجبات</a>
                <span class="mx-1">·</span>
                <a href="{{ route('admin.assignments.by-course', $courseId) }}" class="hover:text-accent">{{ Str::limit($assignment->course?->title ?? '', 25) }}</a>
                <span class="mx-1">·</span>
                <a href="{{ route('admin.assignments.show', $assignment) }}" class="hover:text-accent truncate">{{ Str::limit($assignment->title, 25) }}</a>
                <span class="mx-1">·</span>
                <span class="text-ink">تعديل</span>
            </p>
            <h2 class="mt-1 truncate text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل الواجب</h2>
            <p class="mt-1 truncate text-sm text-muted">{{ $assignment->title }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.assignments.show', $assignment) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-eye text-xs"></i>
                عرض
            </a>
            <a href="{{ route('admin.assignments.by-course', $courseId) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm text-ink-soft hover:bg-accent-soft hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع لواجبات البرنامج
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">
            <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-soft">
            <i class="fas fa-exclamation-circle ml-1"></i> {{ session('error') }}
        </div>
    @endif

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-5 py-4">
            <h3 class="text-sm font-semibold text-ink">بيانات الواجب</h3>
            <p class="mt-0.5 text-xs text-muted">عدّل تفاصيل الواجب وحفظ التغييرات.</p>
        </div>

        <form action="{{ route('admin.assignments.update', $assignment) }}" method="POST" enctype="multipart/form-data" class="p-5 sm:p-6">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}" for="advanced_course_id">البرنامج <span class="text-rose-500">*</span></label>
                        <select name="advanced_course_id" id="advanced_course_id" required class="{{ $fieldClass }}">
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}" {{ old('advanced_course_id', $assignment->advanced_course_id ?? $assignment->course_id) == $c->id ? 'selected' : '' }}>{{ Str::limit($c->title, 50) }}</option>
                            @endforeach
                        </select>
                        @error('advanced_course_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="lesson_id">الدرس (اختياري)</label>
                        <select name="lesson_id" id="lesson_id" class="{{ $fieldClass }}">
                            <option value="">بدون درس محدد</option>
                            @foreach($lessons as $les)
                                <option value="{{ $les->id }}" {{ old('lesson_id', $assignment->lesson_id) == $les->id ? 'selected' : '' }}>{{ Str::limit($les->title, 40) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted">يتم تعبئة الدروس حسب البرنامج المختار</p>
                        @error('lesson_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="title">عنوان الواجب <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title', $assignment->title) }}" required
                           class="{{ $fieldClass }}"
                           placeholder="عنوان الواجب">
                    @error('title')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="description">الوصف</label>
                    <textarea name="description" id="description" rows="3" class="{{ $textareaClass }}"
                              placeholder="وصف مختصر">{{ old('description', $assignment->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="instructions">التعليمات</label>
                    <textarea name="instructions" id="instructions" rows="4" class="{{ $textareaClass }}"
                              placeholder="تعليمات للطلاب">{{ old('instructions', $assignment->instructions) }}</textarea>
                    @error('instructions')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}" for="due_date">تاريخ الاستحقاق</label>
                        <input type="datetime-local" name="due_date" id="due_date" value="{{ old('due_date', $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '') }}"
                               class="{{ $fieldClass }}">
                        @error('due_date')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="max_score">الدرجة الكلية <span class="text-rose-500">*</span></label>
                        <input type="number" name="max_score" id="max_score" value="{{ old('max_score', $assignment->max_score) }}" min="1" max="1000" required
                               class="{{ $fieldClass }}">
                        @error('max_score')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="allow_late_submission" id="allow_late_submission" value="1"
                           {{ old('allow_late_submission', $assignment->allow_late_submission) ? 'checked' : '' }}
                           class="size-4 rounded border-line text-accent focus:ring-accent/20">
                    <label for="allow_late_submission" class="text-sm font-medium text-ink">السماح بالتسليم المتأخر</label>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="status">الحالة <span class="text-rose-500">*</span></label>
                    <select name="status" id="status" class="{{ $fieldClass }}">
                        <option value="draft" {{ old('status', $assignment->status) == 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="published" {{ old('status', $assignment->status) == 'published' ? 'selected' : '' }}>منشور</option>
                        <option value="archived" {{ old('status', $assignment->status) == 'archived' ? 'selected' : '' }}>مؤرشف</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="resource_files">ملفات الواجب للطالب (إضافة)</label>
                    <input type="file" name="resource_files[]" id="resource_files" multiple
                           class="{{ $fieldClass }}"
                           accept=".pdf,.doc,.docx,.zip,.rar,.jpg,.jpeg,.png,.gif,.webp,.ppt,.pptx,.txt">
                    <p class="mt-1 text-xs text-muted">تُضاف إلى الملفات الحالية وتظهر للطالب كمادة الواجب.</p>
                    @error('resource_files')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    @error('resource_files.*')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-2 border-t border-line pt-5">
                <button type="submit" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-save text-xs"></i>
                    حفظ التعديلات
                </button>
                <a href="{{ route('admin.assignments.by-course', $courseId) }}" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                    <i class="fas fa-times text-xs"></i>
                    إلغاء
                </a>
            </div>
        </form>
    </article>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseSelect = document.getElementById('advanced_course_id');
    const lessonSelect = document.getElementById('lesson_id');
    if (!courseSelect || !lessonSelect) return;

    function clearLessonsExceptFirst() {
        while (lessonSelect.options.length > 1) {
            lessonSelect.remove(1);
        }
    }

    courseSelect.addEventListener('change', function() {
        const courseId = this.value;
        clearLessonsExceptFirst();
        if (!courseId) return;

        var opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'جاري التحميل...';
        opt.disabled = true;
        lessonSelect.appendChild(opt);
        lessonSelect.disabled = true;

        fetch('{{ url("/admin/courses") }}/' + courseId + '/lessons-list', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function(r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function(data) {
                opt.remove();
                var lessons = Array.isArray(data) ? data : (data.lessons || data.data || []);
                lessons.forEach(function(lesson) {
                    var o = document.createElement('option');
                    o.value = lesson.id;
                    o.textContent = lesson.title || ('درس ' + (lesson.order || ''));
                    lessonSelect.appendChild(o);
                });
                lessonSelect.disabled = false;
            })
            .catch(function() {
                opt.remove();
                var err = document.createElement('option');
                err.value = '';
                err.textContent = 'حدث خطأ';
                err.disabled = true;
                lessonSelect.appendChild(err);
                lessonSelect.disabled = false;
            });
    });
});
</script>
@endpush
@endsection
