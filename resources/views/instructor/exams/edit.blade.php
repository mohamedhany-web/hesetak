@extends('layouts.app')

@section('title', __('instructor.edit_exam'))
@section('page_title', __('instructor.edit_exam'))

@section('content')
@php
    $locale = app()->getLocale();
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.edit_exam') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.exams') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.edit_exam') }}</h2>
            <p class="id-hero__meta">{{ $exam->title }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.exams.show', $exam) }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <form action="{{ route('instructor.exams.update', $exam) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="id-detail-grid">
            <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
                <section class="id-panel">
                    <header class="id-panel__head">
                        <h2><i class="fas fa-info-circle" aria-hidden="true"></i> {{ __('instructor.exam_info') }}</h2>
                    </header>
                    <div class="id-form-grid">
                        <div class="id-field id-field--span2">
                            <label for="title">{{ __('instructor.title') }} <span style="color:#B91C1C">*</span></label>
                            <input type="text" name="title" id="title" value="{{ old('title', $exam->title) }}" required class="id-input"
                                   placeholder="{{ __('instructor.exam_title_placeholder') }}">
                            @error('title')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field">
                            <label for="advanced_course_id">{{ __('instructor.course_label') }}</label>
                            <select name="advanced_course_id" id="advanced_course_id" onchange="loadLessons()" class="id-select">
                                <option value="">{{ __('instructor.choose_course') }}</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" @selected(old('advanced_course_id', $exam->advanced_course_id) == $course->id)>{{ $course->title }}</option>
                                @endforeach
                            </select>
                            @error('advanced_course_id')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field">
                            <label for="course_lesson_id">{{ __('instructor.lesson_optional') }}</label>
                            <select name="course_lesson_id" id="course_lesson_id" class="id-select">
                                <option value="">{{ __('instructor.general_exam') }}</option>
                                @foreach($lessons as $lesson)
                                    <option value="{{ $lesson->id }}" @selected(old('course_lesson_id', $exam->course_lesson_id) == $lesson->id)>{{ $lesson->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="id-field id-field--span2">
                            <label for="description">{{ __('instructor.description') }}</label>
                            <textarea name="description" id="description" rows="3" class="id-input"
                                      style="min-height:88px;padding-top:10px;padding-bottom:10px;resize:vertical">{{ old('description', $exam->description) }}</textarea>
                        </div>
                        <div class="id-field id-field--span2">
                            <label for="instructions">{{ __('instructor.exam_instructions') }}</label>
                            <textarea name="instructions" id="instructions" rows="4" class="id-input"
                                      style="min-height:110px;padding-top:10px;padding-bottom:10px;resize:vertical">{{ old('instructions', $exam->instructions) }}</textarea>
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
                            <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" min="5" max="480" required class="id-input">
                        </div>
                        <div class="id-field">
                            <label for="total_marks">{{ __('instructor.total_marks') }} <span style="color:#B91C1C">*</span></label>
                            <input type="number" name="total_marks" id="total_marks" value="{{ old('total_marks', $exam->total_marks) }}" min="1" required class="id-input">
                        </div>
                        <div class="id-field">
                            <label for="passing_marks">{{ __('instructor.passing_marks') }} <span style="color:#B91C1C">*</span></label>
                            <input type="number" name="passing_marks" id="passing_marks" value="{{ old('passing_marks', $exam->passing_marks) }}" min="0" step="0.5" required class="id-input">
                        </div>
                        <div class="id-field">
                            <label for="attempts_allowed">{{ __('instructor.attempts_allowed') }} <span style="color:#B91C1C">*</span></label>
                            <input type="number" name="attempts_allowed" id="attempts_allowed" value="{{ old('attempts_allowed', $exam->attempts_allowed) }}" min="1" max="10" required class="id-input">
                            <p class="id-field__hint">1–10</p>
                        </div>
                        <div class="id-field">
                            <label for="start_time">{{ __('instructor.start_time') }}</label>
                            <input type="datetime-local" name="start_time" id="start_time" value="{{ old('start_time', $exam->start_time ? $exam->start_time->format('Y-m-d\TH:i') : '') }}" class="id-input">
                        </div>
                        <div class="id-field">
                            <label for="end_time">{{ __('instructor.end_time') }}</label>
                            <input type="datetime-local" name="end_time" id="end_time" value="{{ old('end_time', $exam->end_time ? $exam->end_time->format('Y-m-d\TH:i') : '') }}" class="id-input">
                        </div>
                    </div>
                </section>

                <section class="id-panel">
                    <header class="id-panel__head">
                        <h2><i class="fas fa-sliders-h" aria-hidden="true"></i> {{ __('instructor.display_settings') }}</h2>
                    </header>
                    <div class="id-check-row" style="margin-bottom:16px">
                        @foreach([
                            'randomize_questions' => __('instructor.randomize_questions'),
                            'randomize_options' => __('instructor.randomize_options'),
                            'show_results_immediately' => __('instructor.show_results_immediately'),
                            'show_correct_answers' => __('instructor.show_correct_answers'),
                            'show_explanations' => __('instructor.show_explanations'),
                            'allow_review' => __('instructor.allow_review'),
                            'is_active' => __('instructor.exam_active'),
                            'show_in_sidebar' => __('instructor.show_in_sidebar'),
                        ] as $name => $label)
                            <label class="id-check">
                                <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $exam->$name))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <div class="id-field" style="max-width:12rem">
                        <label for="sidebar_position">{{ __('instructor.sidebar_position') }}</label>
                        <input type="number" name="sidebar_position" id="sidebar_position" value="{{ old('sidebar_position', $exam->sidebar_position ?? 1) }}" min="1" max="10" class="id-input">
                    </div>
                </section>
            </div>

            <aside style="display:flex;flex-direction:column;gap:16px;min-width:0">
                <section class="id-panel">
                    <header class="id-panel__head">
                        <h2><i class="fas fa-lightbulb" aria-hidden="true"></i> {{ __('instructor.tips') }}</h2>
                    </header>
                    <ul style="margin:0;padding-inline-start:1.25rem;font-size:13px;font-weight:600;color:#6B7A93;line-height:1.7">
                        <li>{{ __('instructor.edit_exam_tip_1') }}</li>
                        <li>{{ __('instructor.edit_exam_tip_2') }}</li>
                        <li>{{ __('instructor.edit_exam_tip_3') }}</li>
                    </ul>
                </section>
                <section class="id-panel" style="display:flex;flex-direction:column;gap:8px">
                    <button type="submit" class="id-btn id-btn--navy" style="width:100%">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        {{ __('instructor.save_changes') }}
                    </button>
                    <a href="{{ route('instructor.exams.show', $exam) }}" class="id-btn id-btn--outline" style="width:100%">{{ __('common.cancel') }}</a>
                </section>
            </aside>
        </div>
    </form>
</div>

@push('scripts')
<script>
function loadLessons() {
    var courseId = document.getElementById('advanced_course_id').value;
    var lessonSelect = document.getElementById('course_lesson_id');
    lessonSelect.innerHTML = '<option value="">' + @json(__('instructor.general_exam')) + '</option>';
    if (courseId) {
        fetch('/instructor/api/courses/' + courseId + '/lessons-list')
            .then(function(r) { return r.json(); })
            .then(function(lessons) {
                (Array.isArray(lessons) ? lessons : []).forEach(function(lesson) {
                    var option = document.createElement('option');
                    option.value = lesson.id;
                    option.textContent = lesson.title;
                    @if($exam->course_lesson_id)
                    if (lesson.id == {{ $exam->course_lesson_id }}) option.selected = true;
                    @endif
                    lessonSelect.appendChild(option);
                });
            })
            .catch(function(e) { console.error('Error loading lessons:', e); });
    }
}
document.addEventListener('DOMContentLoaded', function() {
    var courseId = document.getElementById('advanced_course_id').value;
    if (courseId) loadLessons();
});
</script>
@endpush
@endsection
