@extends('layouts.admin')

@section('title', 'تعديل الاتفاقية - ' . config('app.name'))
@section('page_title', 'تعديل الاتفاقية')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الماليات · عقود المدربين</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل الاتفاقية</h2>
            <p class="mt-1 text-sm text-muted">رقم الاتفاقية: <span class="font-semibold tabular-nums text-ink">{{ $agreement->agreement_number }}</span></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.agreements.show', $agreement) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-eye text-xs"></i>
                عرض التفاصيل
            </a>
            <a href="{{ route('admin.agreements.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.agreements.update', $agreement) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-pen text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">بيانات الاتفاقية</h3>
                        <p class="mt-0.5 text-xs text-muted">تحديث المدرب، النوع، والأسعار</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">المدرب <span class="text-danger">*</span></label>
                    <select name="instructor_id" required class="{{ $fieldClass }}">
                        @foreach($instructors as $instructor)
                            <option value="{{ $instructor->id }}" {{ $agreement->instructor_id == $instructor->id ? 'selected' : '' }}>
                                {{ $instructor->name }} - {{ $instructor->phone }}
                            </option>
                        @endforeach
                    </select>
                    @error('instructor_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">نوع الاتفاقية <span class="text-danger">*</span></label>
                    <select name="type" id="type" required class="{{ $fieldClass }}">
                        @php $effectiveType = ($agreement->billing_type ?? '') === 'course_percentage' ? 'course_percentage' : $agreement->type; @endphp
                        <option value="course_price" {{ $effectiveType == 'course_price' ? 'selected' : '' }}>سعر للكورس كاملاً</option>
                        <option value="hourly_rate" {{ $effectiveType == 'hourly_rate' ? 'selected' : '' }}>سعر للساعة المسجلة</option>
                        <option value="monthly_salary" {{ $effectiveType == 'monthly_salary' ? 'selected' : '' }}>راتب شهري</option>
                        <option value="consultation_session" {{ $effectiveType == 'consultation_session' ? 'selected' : '' }}>استشارات</option>
                        <option value="course_percentage" {{ $effectiveType == 'course_percentage' ? 'selected' : '' }}>نسبة من الكورس</option>
                    </select>
                    @error('type')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div id="rate-field">
                    <label class="{{ $labelClass }}">السعر/المعدل {{ currency_unit() }} <span class="text-danger">*</span></label>
                    <input type="number" name="rate" id="rate" step="0.01" min="0"
                           value="{{ old('rate', $agreement->rate) }}" class="{{ $fieldClass }}" />
                    @error('rate')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div id="course-percentage-fields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5" style="display: none;">
                    <div>
                        <label class="{{ $labelClass }}">الكورس الأونلاين <span class="text-danger">*</span></label>
                        <select name="advanced_course_id" id="advanced_course_id" class="{{ $fieldClass }}">
                            <option value="">اختر المدرب أولاً ثم الكورس</option>
                            @foreach($advancedCourses ?? [] as $ac)
                                <option value="{{ $ac->id }}" data-instructor-id="{{ $ac->instructor_id ?? '' }}" {{ old('advanced_course_id', $agreement->advanced_course_id) == $ac->id ? 'selected' : '' }}>{{ $ac->title }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-muted">تظهر فقط الكورسات المُعيَّنة للمدرب المختار.</p>
                        @error('advanced_course_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">نسبة المدرب (%) <span class="text-danger">*</span></label>
                        <input type="number" name="course_percentage" id="course_percentage" step="0.01" min="0" max="100"
                               value="{{ old('course_percentage', $agreement->course_percentage) }}" class="{{ $fieldClass }}" />
                        @error('course_percentage')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">عنوان الاتفاقية <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $agreement->title) }}" required class="{{ $fieldClass }}" />
                    @error('title')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">تاريخ البدء <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date', $agreement->start_date->format('Y-m-d')) }}" required class="{{ $fieldClass }}" />
                    @error('start_date')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">تاريخ الانتهاء</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $agreement->end_date ? $agreement->end_date->format('Y-m-d') : '') }}" class="{{ $fieldClass }}" />
                    @error('end_date')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">الحالة <span class="text-danger">*</span></label>
                    <select name="status" required class="{{ $fieldClass }}">
                        <option value="draft" {{ $agreement->status == 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="active" {{ $agreement->status == 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="suspended" {{ $agreement->status == 'suspended' ? 'selected' : '' }}>معلق</option>
                        <option value="terminated" {{ $agreement->status == 'terminated' ? 'selected' : '' }}>منتهي</option>
                        <option value="completed" {{ $agreement->status == 'completed' ? 'selected' : '' }}>مكتمل</option>
                    </select>
                    @error('status')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">الوصف</label>
                    <textarea name="description" rows="3" class="{{ $areaClass }}">{{ old('description', $agreement->description) }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">شروط العقد</label>
                    <textarea name="terms" rows="5" class="{{ $areaClass }}">{{ old('terms', $agreement->terms) }}</textarea>
                    @error('terms')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">ملاحظات</label>
                    <textarea name="notes" rows="3" class="{{ $areaClass }}">{{ old('notes', $agreement->notes) }}</textarea>
                    @error('notes')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line px-4 py-4 sm:px-5">
                <a href="{{ route('admin.agreements.show', $agreement) }}"
                   class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                    إلغاء
                </a>
                <button type="submit"
                        class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-save text-xs"></i>
                    حفظ التغييرات
                </button>
            </div>
        </article>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const rateField = document.getElementById('rate-field');
    const rateInput = document.getElementById('rate');
    const coursePercentageBlock = document.getElementById('course-percentage-fields');
    const advancedCourseId = document.getElementById('advanced_course_id');
    const coursePercentageInput = document.getElementById('course_percentage');

    function filterCoursesByInstructor() {
        const instructorSelect = document.querySelector('select[name="instructor_id"]');
        if (!advancedCourseId || !instructorSelect) return;
        const selectedInstructor = instructorSelect.value;
        const options = advancedCourseId.querySelectorAll('option[data-instructor-id]');
        options.forEach(function(opt) {
            const show = !selectedInstructor || (opt.getAttribute('data-instructor-id') === selectedInstructor);
            opt.style.display = show ? '' : 'none';
            opt.disabled = !show;
            if (opt.value && !show) advancedCourseId.value = '';
        });
        if (advancedCourseId.value) {
            const chosen = advancedCourseId.querySelector('option:checked');
            if (chosen && chosen.disabled) advancedCourseId.value = '';
        }
    }

    function toggleTypeFields() {
        const type = typeSelect.value;
        const isPercentage = type === 'course_percentage';
        if (rateField) rateField.style.display = isPercentage ? 'none' : 'block';
        if (coursePercentageBlock) coursePercentageBlock.style.display = isPercentage ? 'grid' : 'none';
        if (rateInput) { rateInput.required = !isPercentage; }
        if (advancedCourseId) advancedCourseId.required = isPercentage;
        if (coursePercentageInput) coursePercentageInput.required = isPercentage;
        if (isPercentage) filterCoursesByInstructor();
    }
    typeSelect.addEventListener('change', toggleTypeFields);
    document.querySelector('select[name="instructor_id"]').addEventListener('change', function() {
        if (document.getElementById('type').value === 'course_percentage') filterCoursesByInstructor();
    });
    toggleTypeFields();
});
</script>
@endpush
@endsection
