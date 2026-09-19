@extends('layouts.app')

@section('title', __('instructor.create_assignment') . ' - ' . config('app.name'))
@section('page_title', __('instructor.create_assignment'))

@section('content')
@php
    $locale = app()->getLocale();
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.create_assignment') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.assignments') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.create_assignment') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.add_assignment_for_course') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.assignments.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-panel">
        @include('instructor.assignments.create-form', ['courses' => $courses])
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseSelect = document.getElementById('advanced_course_id');
    if (!courseSelect) return;

    const newCourseSelect = courseSelect.cloneNode(true);
    courseSelect.parentNode.replaceChild(newCourseSelect, courseSelect);

    newCourseSelect.addEventListener('change', function() {
        const courseId = this.value;
        const lessonSelect = document.getElementById('lesson_id');
        if (!lessonSelect) return;

        while (lessonSelect.children.length > 1) lessonSelect.removeChild(lessonSelect.lastChild);
        if (!courseId) return;

        const loadingOption = document.createElement('option');
        loadingOption.value = '';
        loadingOption.textContent = @json(__('instructor.loading_text'));
        loadingOption.disabled = true;
        lessonSelect.appendChild(loadingOption);
        lessonSelect.disabled = true;

        fetch('/instructor/api/courses/' + courseId + '/lessons-list', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function(r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function(data) {
                loadingOption.remove();
                var lessons = Array.isArray(data) ? data : (data.lessons || []);
                if (lessons.length > 0) {
                    lessons.forEach(function(lesson) {
                        var opt = document.createElement('option');
                        opt.value = lesson.id;
                        opt.textContent = lesson.title || 'درس ' + (lesson.order || '');
                        lessonSelect.appendChild(opt);
                    });
                } else {
                    var noOpt = document.createElement('option');
                    noOpt.value = '';
                    noOpt.textContent = @json(__('instructor.no_lessons_in_course'));
                    noOpt.disabled = true;
                    lessonSelect.appendChild(noOpt);
                }
                lessonSelect.disabled = false;
            })
            .catch(function() {
                loadingOption.remove();
                var errOpt = document.createElement('option');
                errOpt.value = '';
                errOpt.textContent = @json(__('instructor.error_occurred'));
                errOpt.disabled = true;
                lessonSelect.appendChild(errOpt);
                lessonSelect.disabled = false;
            });
    });

    if (typeof updateGroupOptions === 'function') {
        updateGroupOptions(newCourseSelect.value);
    }
});
</script>
@endsection
