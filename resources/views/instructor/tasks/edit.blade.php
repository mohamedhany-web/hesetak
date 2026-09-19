@extends('layouts.app')

@section('title', __('instructor.edit_task') . ' - ' . $task->title)
@section('page_title', __('instructor.edit_task'))

@section('content')
@php
    $locale = app()->getLocale();
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.edit_task') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.tasks_from_management') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.edit_task') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.update_status_or_details') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.tasks.show', $task) }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-panel">
        <form action="{{ route('instructor.tasks.update', $task) }}" method="POST" class="id-form">
            @csrf
            @method('PUT')
            <div class="id-form-grid">
                <div class="id-field id-field--span2">
                    <label for="title">{{ __('instructor.task_title_required') }}</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $task->title) }}" required class="id-input">
                    @error('title')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field id-field--span2">
                    <label for="description">{{ __('instructor.description') }}</label>
                    <textarea name="description" id="description" rows="4" class="id-input"
                              style="min-height:100px;padding-top:10px;padding-bottom:10px;resize:vertical">{{ old('description', $task->description) }}</textarea>
                    @error('description')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="priority">{{ __('instructor.priority') }}</label>
                    <select name="priority" id="priority" class="id-select">
                        <option value="low" @selected(old('priority', $task->priority) === 'low')>{{ __('instructor.low') }}</option>
                        <option value="medium" @selected(old('priority', $task->priority) === 'medium')>{{ __('instructor.medium') }}</option>
                        <option value="high" @selected(old('priority', $task->priority) === 'high')>{{ __('instructor.high') }}</option>
                        <option value="urgent" @selected(old('priority', $task->priority) === 'urgent')>{{ __('instructor.urgent') }}</option>
                    </select>
                </div>
                <div class="id-field">
                    <label for="status">{{ __('common.status') }}</label>
                    <select name="status" id="status" class="id-select">
                        <option value="pending" @selected(old('status', $task->status) === 'pending')>{{ __('instructor.pending') }}</option>
                        <option value="in_progress" @selected(old('status', $task->status) === 'in_progress')>{{ __('instructor.in_progress') }}</option>
                        <option value="completed" @selected(old('status', $task->status) === 'completed')>{{ __('instructor.completed') }}</option>
                        <option value="cancelled" @selected(old('status', $task->status) === 'cancelled')>{{ __('instructor.cancelled_lecture') }}</option>
                    </select>
                </div>
                <div class="id-field id-field--span2">
                    <label for="due_date">{{ __('instructor.due_date') }}</label>
                    <input type="datetime-local" name="due_date" id="due_date"
                           value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d\TH:i') : '') }}"
                           class="id-input">
                    @error('due_date')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="related_course_id">{{ __('instructor.course_optional') }}</label>
                    <select name="related_course_id" id="related_course_id" class="id-select">
                        <option value="">{{ __('instructor.none_option') }}</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) old('related_course_id', $task->related_course_id) === (string) $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="id-field">
                    <label for="related_lecture_id">{{ __('instructor.lecture_optional') }}</label>
                    <select name="related_lecture_id" id="related_lecture_id" class="id-select">
                        <option value="">{{ __('instructor.none_option') }}</option>
                        @foreach($lectures as $lecture)
                            <option value="{{ $lecture->id }}" @selected((string) old('related_lecture_id', $task->related_lecture_id) === (string) $lecture->id)>
                                {{ $lecture->title }} @if($lecture->scheduled_at) - {{ $lecture->scheduled_at->format('Y/m/d') }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="id-foot-actions">
                <a href="{{ route('instructor.tasks.show', $task) }}" class="id-btn id-btn--outline">{{ __('common.cancel') }}</a>
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-save" aria-hidden="true"></i>
                    {{ __('instructor.save_changes') }}
                </button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('related_course_id').addEventListener('change', function () {
    const courseId = this.value;
    const lectureSelect = document.getElementById('related_lecture_id');
    const noneLabel = @json(__('instructor.none_option'));
    lectureSelect.innerHTML = '<option value="">' + noneLabel + '</option>';
    if (courseId) {
        fetch(`{{ route('instructor.tasks.lectures') }}?course_id=${courseId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            data.forEach(lecture => {
                const opt = document.createElement('option');
                opt.value = lecture.id;
                opt.textContent = lecture.title + (lecture.scheduled_at ? ' - ' + lecture.scheduled_at : '');
                lectureSelect.appendChild(opt);
            });
        })
        .catch(() => {});
    }
});
</script>
@endpush
