@extends('layouts.app')

@section('title', __('instructor.create_task') . ' - ' . config('app.name'))
@section('page_title', __('instructor.create_task'))

@section('content')
@php
    $locale = app()->getLocale();
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.create_task') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.tasks_from_management') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.create_task') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.create_task_desc') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.tasks.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-panel">
        <form action="{{ route('instructor.tasks.store') }}" method="POST" class="id-form">
            @csrf
            <div class="id-form-grid">
                <div class="id-field id-field--span2">
                    <label for="title">{{ __('instructor.task_title_required') }}</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required class="id-input"
                           placeholder="{{ __('instructor.task_title_placeholder') }}">
                    @error('title')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field id-field--span2">
                    <label for="description">{{ __('instructor.description') }}</label>
                    <textarea name="description" id="description" rows="4" class="id-input"
                              style="min-height:100px;padding-top:10px;padding-bottom:10px;resize:vertical"
                              placeholder="{{ __('instructor.task_description_placeholder') }}">{{ old('description') }}</textarea>
                    @error('description')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="priority">{{ __('instructor.priority') }} <span style="color:#B91C1C">*</span></label>
                    <select name="priority" id="priority" required class="id-select">
                        <option value="low" @selected(old('priority', 'medium') === 'low')>{{ __('instructor.low') }}</option>
                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>{{ __('instructor.medium') }}</option>
                        <option value="high" @selected(old('priority') === 'high')>{{ __('instructor.high') }}</option>
                        <option value="urgent" @selected(old('priority') === 'urgent')>{{ __('instructor.urgent') }}</option>
                    </select>
                    @error('priority')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="due_date">{{ __('instructor.due_date') }}</label>
                    <input type="datetime-local" name="due_date" id="due_date" value="{{ old('due_date') }}" class="id-input">
                    @error('due_date')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="related_course_id">{{ __('instructor.course_optional') }}</label>
                    <select name="related_course_id" id="related_course_id" class="id-select">
                        <option value="">{{ __('instructor.choose_course') }}</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) old('related_course_id') === (string) $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('related_course_id')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="related_lecture_id">{{ __('instructor.lecture_optional') }}</label>
                    <select name="related_lecture_id" id="related_lecture_id" class="id-select">
                        <option value="">{{ __('instructor.choose_lecture') }}</option>
                        @foreach($lectures as $lecture)
                            <option value="{{ $lecture->id }}" @selected((string) old('related_lecture_id') === (string) $lecture->id)>
                                {{ $lecture->title }} - {{ $lecture->scheduled_at->format('Y/m/d') }}
                            </option>
                        @endforeach
                    </select>
                    @error('related_lecture_id')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="id-slot-card">
                <span style="font-size:13px;font-weight:700;color:#3A4A63">{{ __('instructor.priority_preview') }}:</span>
                <span id="priority-preview" class="id-chip id-chip--muted" style="margin-inline-start:8px">{{ __('instructor.medium') }}</span>
            </div>

            <div class="id-foot-actions">
                <a href="{{ route('instructor.tasks.index') }}" class="id-btn id-btn--outline">{{ __('common.cancel') }}</a>
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-save" aria-hidden="true"></i>
                    {{ __('instructor.save_task') }}
                </button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const courseSelect = document.getElementById('related_course_id');
    const lectureSelect = document.getElementById('related_lecture_id');
    const prioritySelect = document.getElementById('priority');
    const priorityPreview = document.getElementById('priority-preview');
    const labels = {
        low: @json(__('instructor.low')),
        medium: @json(__('instructor.medium')),
        high: @json(__('instructor.high')),
        urgent: @json(__('instructor.urgent')),
    };
    const chooseLecture = @json(__('instructor.choose_lecture'));

    courseSelect.addEventListener('change', function () {
        const courseId = this.value;
        lectureSelect.innerHTML = '<option value="">' + chooseLecture + '</option>';
        if (courseId) {
            fetch(`{{ route('instructor.tasks.lectures') }}?course_id=${courseId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                data.forEach(lecture => {
                    const option = document.createElement('option');
                    option.value = lecture.id;
                    option.textContent = `${lecture.title} - ${new Date(lecture.scheduled_at).toLocaleDateString()}`;
                    lectureSelect.appendChild(option);
                });
            })
            .catch(() => {});
        }
    });

    function updatePriorityPreview() {
        const priority = prioritySelect.value;
        priorityPreview.textContent = labels[priority] || labels.medium;
        priorityPreview.className = 'id-chip ' + (
            priority === 'urgent' ? 'id-chip--rose' :
            priority === 'high' ? 'id-chip--warn' : 'id-chip--muted'
        );
    }
    prioritySelect.addEventListener('change', updatePriorityPreview);
    updatePriorityPreview();
});
</script>
@endpush
