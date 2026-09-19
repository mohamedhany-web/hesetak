@extends('layouts.app')

@section('title', __('instructor.edit_assignment_title') . ' - ' . $assignment->title)
@section('page_title', __('instructor.edit_assignment_title'))

@section('content')
@php
    $locale = app()->getLocale();
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.edit_assignment_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.assignments') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.edit_assignment_title') }}</h2>
            <p class="id-hero__meta">{{ $assignment->title }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.assignments.show', $assignment) }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-panel">
        <form action="{{ route('instructor.assignments.update', $assignment) }}" method="POST" enctype="multipart/form-data" class="id-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="advanced_course_id" value="{{ $assignment->advanced_course_id ?? $assignment->course_id }}">

            <div class="id-form-grid">
                <div class="id-field id-field--span2">
                    <label for="title">{{ __('instructor.assignment_title_required') }} <span style="color:#B91C1C">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title', $assignment->title) }}" required class="id-input">
                    @error('title')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field id-field--span2">
                    <label for="description">{{ __('instructor.description') }}</label>
                    <textarea name="description" id="description" rows="3" class="id-input"
                              style="min-height:88px;padding-top:10px;padding-bottom:10px;resize:vertical">{{ old('description', $assignment->description) }}</textarea>
                    @error('description')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field id-field--span2">
                    <label for="instructions">{{ __('instructor.instructions_label') }}</label>
                    <textarea name="instructions" id="instructions" rows="4" class="id-input"
                              style="min-height:110px;padding-top:10px;padding-bottom:10px;resize:vertical">{{ old('instructions', $assignment->instructions) }}</textarea>
                    @error('instructions')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                @php
                    $resAtt = is_array($assignment->resource_attachments) ? $assignment->resource_attachments : [];
                @endphp
                @if(count($resAtt) > 0)
                    <div class="id-field id-field--span2">
                        <label>{{ __('instructor.current_attachments_remove') }}</label>
                        <div style="display:flex;flex-direction:column;gap:8px">
                            @foreach($resAtt as $idx => $att)
                                @php
                                    $p = is_array($att) ? ($att['path'] ?? '') : '';
                                    $url = $p ? (\App\Services\AssignmentFileStorage::publicUrl($p) ?? '#') : '#';
                                    $on = is_array($att) ? ($att['original_name'] ?? basename($p)) : '';
                                @endphp
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1.5px solid #E6EEF8;border-radius:12px">
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="id-link" style="overflow:hidden;text-overflow:ellipsis">{{ $on }}</a>
                                    <label class="id-check" style="flex-shrink:0">
                                        <input type="checkbox" name="remove_resource_indices[]" value="{{ $idx }}">
                                        {{ __('common.delete') }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="id-field id-field--span2">
                    <label>{{ __('instructor.add_new_attachments') }}</label>
                    <input type="file" name="resource_files[]" multiple accept=".pdf,.doc,.docx,.zip,.rar,.jpg,.jpeg,.png,.gif,.webp,.ppt,.pptx,.txt"
                           class="id-input" style="padding-top:10px;padding-bottom:10px">
                    @error('resource_files')<p class="id-field__err">{{ $message }}</p>@enderror
                    @error('resource_files.*')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="due_date">{{ __('instructor.due_date') }}</label>
                    <input type="datetime-local" name="due_date" id="due_date" value="{{ old('due_date', $assignment->due_date?->format('Y-m-d\TH:i')) }}" class="id-input">
                    @error('due_date')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="max_score">{{ __('instructor.total_score_label') }} <span style="color:#B91C1C">*</span></label>
                    <input type="number" name="max_score" id="max_score" value="{{ old('max_score', $assignment->max_score) }}" min="1" max="1000" required class="id-input">
                    @error('max_score')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field id-field--span2">
                    <label class="id-check" style="width:100%">
                        <input type="checkbox" name="allow_late_submission" value="1" @checked(old('allow_late_submission', $assignment->allow_late_submission))>
                        {{ __('instructor.allow_late_submission_label') }}
                    </label>
                </div>
                <div class="id-field id-field--span2">
                    <label for="status">{{ __('common.status') }} <span style="color:#B91C1C">*</span></label>
                    <select name="status" id="status" required class="id-select">
                        <option value="draft" @selected(old('status', $assignment->status) === 'draft')>{{ __('instructor.draft') }}</option>
                        <option value="published" @selected(old('status', $assignment->status) === 'published')>{{ __('instructor.published') }}</option>
                        <option value="archived" @selected(old('status', $assignment->status) === 'archived')>{{ __('instructor.archived') }}</option>
                    </select>
                    @error('status')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="id-foot-actions" style="margin-top:20px">
                <a href="{{ route('instructor.assignments.index') }}" class="id-btn id-btn--outline">{{ __('common.cancel') }}</a>
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-save" aria-hidden="true"></i>
                    {{ __('instructor.save_changes') }}
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
