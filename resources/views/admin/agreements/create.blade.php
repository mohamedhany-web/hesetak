@extends('layouts.admin')

@section('title', 'إضافة اتفاقية جديدة - ' . config('app.name'))
@section('page_title', 'إضافة اتفاقية جديدة')

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
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إضافة اتفاقية جديدة</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">إنشاء اتفاقية عمل جديدة مع أحد المدربين</p>
        </div>
        <a href="{{ route('admin.agreements.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للقائمة
        </a>
    </section>

    <form method="POST" action="{{ route('admin.agreements.store') }}" class="space-y-5">
        @csrf

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-plus-circle text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">بيانات الاتفاقية</h3>
                        <p class="mt-0.5 text-xs text-muted">المدرب، النوع، والأسعار</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">المدرب <span class="text-danger">*</span></label>
                    <select name="instructor_id" required class="{{ $fieldClass }}">
                        <option value="">اختر المدرب</option>
                        @forelse($instructors as $instructor)
                            <option value="{{ $instructor->id }}" {{ old('instructor_id') == $instructor->id ? 'selected' : '' }}>
                                {{ $instructor->name }}
                                @if($instructor->phone)
                                    - {{ $instructor->phone }}
                                @endif
                                @if($instructor->email)
                                    ({{ $instructor->email }})
                                @endif
                            </option>
                        @empty
                            <option value="" disabled>لا يوجد مدربين متاحين</option>
                        @endforelse
                    </select>
                    @error('instructor_id')
                        <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>
                    @enderror
                    @if($instructors->isEmpty())
                        <p class="mt-1 text-xs font-medium text-amber-700">
                            <i class="fas fa-exclamation-triangle ml-1"></i>
                            لا يوجد مدربين في النظام. يرجى إضافة مدربين أولاً.
                        </p>
                    @endif
                </div>

                <div>
                    <label class="{{ $labelClass }}">نوع الاتفاقية <span class="text-danger">*</span></label>
                    <select name="type" id="type" required class="{{ $fieldClass }}">
                        <option value="course_price" {{ old('type') == 'course_price' ? 'selected' : '' }}>سعر للكورس كاملاً</option>
                        <option value="hourly_rate" {{ old('type') == 'hourly_rate' ? 'selected' : '' }}>سعر للساعة المسجلة</option>
                        <option value="monthly_salary" {{ old('type') == 'monthly_salary' ? 'selected' : '' }}>راتب شهري</option>
                        <option value="consultation_session" {{ old('type') == 'consultation_session' ? 'selected' : '' }}>استشارات</option>
                        <option value="course_percentage" {{ old('type') == 'course_percentage' ? 'selected' : '' }}>نسبة من الكورس</option>
                    </select>
                    <p class="mt-1.5 text-xs text-muted">نسبة من الكورس: يُحتسب للمدرب نسبة من مبلغ كل تفعيل للطالب في الكورس الأونلاين.</p>
                    @error('type')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div id="rate-field">
                    <label class="{{ $labelClass }}">السعر/المعدل {{ currency_unit() }} <span class="text-danger">*</span></label>
                    <input type="number" name="rate" id="rate" step="0.01" min="0" value="{{ old('rate') }}"
                           class="{{ $fieldClass }}" placeholder="0.00" />
                    <p class="mt-1.5 text-xs text-muted" id="rate-help">المبلغ المحدد لكل كورس</p>
                    @error('rate')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div id="course-percentage-fields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5" style="display: none;">
                    <div>
                        <label class="{{ $labelClass }}">الكورس الأونلاين <span class="text-danger">*</span></label>
                        <select name="advanced_course_id" id="advanced_course_id" class="{{ $fieldClass }}">
                            <option value="">اختر المدرب أولاً ثم الكورس</option>
                            @foreach($advancedCourses ?? [] as $ac)
                                <option value="{{ $ac->id }}" data-instructor-id="{{ $ac->instructor_id ?? '' }}" {{ old('advanced_course_id') == $ac->id ? 'selected' : '' }}>{{ $ac->title }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-muted">تظهر فقط الكورسات المُعيَّنة للمدرب المختار.</p>
                        @error('advanced_course_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">نسبة المدرب (%) <span class="text-danger">*</span></label>
                        <input type="number" name="course_percentage" id="course_percentage" step="0.01" min="0" max="100"
                               value="{{ old('course_percentage') }}" class="{{ $fieldClass }}" placeholder="مثال: 30" />
                        <p class="mt-1.5 text-xs text-muted">من 0 إلى 100. تُحسب من مبلغ تفعيل الطالب في الكورس.</p>
                        @error('course_percentage')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">عنوان الاتفاقية <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="{{ $fieldClass }}" placeholder="مثال: اتفاقية عمل مع المدرب..." />
                    @error('title')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">تاريخ البدء <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required class="{{ $fieldClass }}" />
                    @error('start_date')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">تاريخ الانتهاء</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="{{ $fieldClass }}" />
                    @error('end_date')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">الحالة <span class="text-danger">*</span></label>
                    <select name="status" required class="{{ $fieldClass }}">
                        <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>معلق</option>
                    </select>
                    @error('status')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">الوصف</label>
                    <textarea name="description" rows="3" class="{{ $areaClass }}" placeholder="وصف مختصر للاتفاقية...">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">شروط العقد</label>
                    <textarea name="terms" rows="5" class="{{ $areaClass }}" placeholder="شروط وأحكام الاتفاقية...">{{ old('terms') }}</textarea>
                    @error('terms')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">ملاحظات</label>
                    <textarea name="notes" rows="3" class="{{ $areaClass }}" placeholder="ملاحظات إضافية...">{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line px-4 py-4 sm:px-5">
                <a href="{{ route('admin.agreements.index') }}"
                   class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                    إلغاء
                </a>
                <button type="submit"
                        class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-save text-xs"></i>
                    حفظ الاتفاقية
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
    const rateHelp = document.getElementById('rate-help');
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
        if (rateInput) {
            rateInput.required = !isPercentage;
            rateInput.value = isPercentage ? '0' : rateInput.value;
        }
        if (advancedCourseId) advancedCourseId.required = isPercentage;
        if (coursePercentageInput) coursePercentageInput.required = isPercentage;
        if (isPercentage) filterCoursesByInstructor();

        if (!isPercentage && rateHelp) {
            if (type === 'course_price') rateHelp.textContent = 'المبلغ المحدد لكل كورس';
            else if (type === 'hourly_rate') rateHelp.textContent = 'المبلغ المحدد لكل ساعة تدريس';
            else if (type === 'monthly_salary') rateHelp.textContent = 'الراتب الشهري الثابت';
        }
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
