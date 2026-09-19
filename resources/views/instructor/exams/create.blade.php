@extends('layouts.app')

@section('title', __('instructor.create_exam_new') . ' - ' . config('app.name'))
@section('page_title', __('instructor.create_exam_new'))

@section('content')
@php
    $locale = app()->getLocale();
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.create_exam_new') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.exams') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.create_exam_new') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.add_exam_to_course') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.exams.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <form action="{{ route('instructor.exams.store') }}" method="POST" class="id-form" style="display:flex;flex-direction:column;gap:16px">
        @csrf

        <section class="id-panel">
            <header class="id-panel__head">
                <h2><i class="fas fa-info-circle" aria-hidden="true"></i> {{ __('instructor.exam_info') }}</h2>
            </header>
            <div class="id-form-grid">
                <div class="id-field id-field--span2">
                    <label for="title">{{ __('instructor.title') }} <span style="color:#B91C1C">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required class="id-input"
                           placeholder="{{ __('instructor.exam_title_placeholder') }}">
                    @error('title')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="advanced_course_id">{{ __('instructor.online_course') }} <span style="color:#B91C1C">*</span></label>
                    <select name="advanced_course_id" id="advanced_course_id" onchange="loadLessons()" class="id-select">
                        <option value="">{{ __('instructor.choose_course') }}</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(old('advanced_course_id') == $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('advanced_course_id')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field" id="lesson-wrap">
                    <label for="course_lesson_id">{{ __('instructor.lesson_optional') }}</label>
                    <select name="course_lesson_id" id="course_lesson_id" class="id-select">
                        <option value="">{{ __('instructor.general_exam') }}</option>
                    </select>
                </div>
                <div class="id-field id-field--span2">
                    <label for="description">{{ __('instructor.description') }}</label>
                    <textarea name="description" id="description" rows="3" class="id-input"
                              style="min-height:88px;padding-top:10px;padding-bottom:10px;resize:vertical"
                              placeholder="{{ __('instructor.description_placeholder') }}">{{ old('description') }}</textarea>
                </div>
                <div class="id-field id-field--span2">
                    <label for="instructions">{{ __('instructor.exam_instructions') }}</label>
                    <textarea name="instructions" id="instructions" rows="4" class="id-input"
                              style="min-height:110px;padding-top:10px;padding-bottom:10px;resize:vertical"
                              placeholder="{{ __('instructor.instructions_placeholder') }}">{{ old('instructions') }}</textarea>
                </div>
            </div>
        </section>

        <section class="id-panel">
            <header class="id-panel__head">
                <h2><i class="fas fa-clock" aria-hidden="true"></i> {{ __('instructor.time_and_marks') }}</h2>
            </header>
            <div class="id-form-grid">
                <div class="id-field">
                    <label for="duration_minutes">{{ __('instructor.duration_minutes') }} ({{ __('instructor.minute_unit') }}) <span style="color:#B91C1C">*</span></label>
                    <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', 60) }}" min="5" max="480" required class="id-input">
                </div>
                <div class="id-field">
                    <label for="total_marks">{{ __('instructor.total_marks') }} <span style="color:#B91C1C">*</span></label>
                    <input type="number" name="total_marks" id="total_marks" value="{{ old('total_marks', 100) }}" min="1" required class="id-input">
                </div>
                <div class="id-field">
                    <label for="passing_marks">{{ __('instructor.passing_marks') }} <span style="color:#B91C1C">*</span></label>
                    <input type="number" name="passing_marks" id="passing_marks" value="{{ old('passing_marks', 60) }}" min="0" step="0.5" required class="id-input">
                </div>
                <div class="id-field">
                    <label for="attempts_allowed">{{ __('instructor.attempts_allowed') }} <span style="color:#B91C1C">*</span></label>
                    <input type="number" name="attempts_allowed" id="attempts_allowed" value="{{ old('attempts_allowed', 1) }}" min="1" max="10" required class="id-input">
                </div>
                <div class="id-field">
                    <label for="start_time">{{ __('instructor.start_time') }}</label>
                    <input type="datetime-local" name="start_time" id="start_time" value="{{ old('start_time') }}" class="id-input">
                </div>
                <div class="id-field">
                    <label for="end_time">{{ __('instructor.end_time') }}</label>
                    <input type="datetime-local" name="end_time" id="end_time" value="{{ old('end_time') }}" class="id-input">
                </div>
            </div>
        </section>

        <section class="id-panel">
            <header class="id-panel__head">
                <h2><i class="fas fa-sliders-h" aria-hidden="true"></i> {{ __('instructor.display_settings') }}</h2>
            </header>
            <div class="id-check-row" style="margin-bottom:16px">
                @foreach([
                    ['randomize_questions', __('instructor.randomize_questions'), false],
                    ['randomize_options', __('instructor.randomize_options'), false],
                    ['show_results_immediately', __('instructor.show_results_immediately'), true],
                    ['show_correct_answers', __('instructor.show_correct_answers'), false],
                    ['show_explanations', __('instructor.show_explanations'), false],
                    ['allow_review', __('instructor.allow_review'), true],
                    ['is_active', __('instructor.exam_active'), true],
                    ['show_in_sidebar', __('instructor.show_in_sidebar'), true],
                ] as $opt)
                    <label class="id-check">
                        <input type="checkbox" name="{{ $opt[0] }}" value="1" @checked(old($opt[0], $opt[2]))>
                        {{ $opt[1] }}
                    </label>
                @endforeach
            </div>
            <div class="id-field" style="max-width:12rem">
                <label for="sidebar_position">{{ __('instructor.sidebar_position') }}</label>
                <input type="number" name="sidebar_position" id="sidebar_position" value="{{ old('sidebar_position', 1) }}" min="1" max="10" class="id-input">
            </div>
        </section>

        <div class="id-alert id-alert--info" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px">
            <span><strong>{{ __('instructor.tips') }}:</strong> {{ __('instructor.tip_after_create_exam') }}</span>
            <div class="id-foot-actions" style="margin:0">
                <a href="{{ route('instructor.exams.index') }}" class="id-btn id-btn--outline">{{ __('common.cancel') }}</a>
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-save" aria-hidden="true"></i>
                    {{ __('instructor.create_exam_btn') }}
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function loadLessons() {
    const courseId = document.getElementById('advanced_course_id').value;
    const lessonSelect = document.getElementById('course_lesson_id');
    lessonSelect.innerHTML = '<option value="">' + @json(__('instructor.general_exam')) + '</option>';
    if (courseId) {
        fetch(`/instructor/api/courses/${courseId}/lessons-list`)
            .then(response => response.json())
            .then(lessons => {
                (Array.isArray(lessons) ? lessons : []).forEach(lesson => {
                    const option = document.createElement('option');
                    option.value = lesson.id;
                    option.textContent = lesson.title || ('درس ' + (lesson.order || ''));
                    lessonSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading lessons:', error));
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const courseId = document.getElementById('advanced_course_id').value;
    if (courseId) loadLessons();
});
</script>
@endpush
@endsection
