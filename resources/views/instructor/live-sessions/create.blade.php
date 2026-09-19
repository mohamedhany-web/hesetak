@extends('layouts.app')

@section('title', __('instructor.ls_create_title') . ' - ' . config('app.name'))
@section('page_title', __('instructor.ls_create_title'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $backHref = route('instructor.live-sessions.index');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.ls_create_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.ls_title') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.ls_create_title') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.ls_create_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ $backHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.ls_back_list') }}
            </a>
        </div>
    </section>

    @if($errors->any())
        <div class="id-alert id-alert--err">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <ul style="margin:0;padding-inline-start:18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="id-panel">
        <header class="id-panel__head">
            <h2>{{ __('instructor.ls_session_info') }}</h2>
        </header>

        <form method="POST" action="{{ route('instructor.live-sessions.store') }}" class="id-form">
            @csrf

            <div class="id-field">
                <label for="live_title">{{ __('instructor.ls_session_title') }} <span style="color:#B91C1C">*</span></label>
                <input type="text" name="title" id="live_title" value="{{ old('title') }}" required
                       class="id-input"
                       placeholder="{{ $isRtl ? 'مثال: مراجعة الوحدة الثانية — جلسة تفاعلية' : 'e.g. Unit 2 review — interactive session' }}">
                @error('title')
                    <p class="id-field__err">{{ $message }}</p>
                @enderror
            </div>

            <div class="id-field">
                <label for="course_id">{{ __('instructor.ls_course_optional') }}</label>
                <select name="course_id" id="course_id" class="id-input">
                    <option value="">{{ __('instructor.ls_general_session') }}</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                    @endforeach
                </select>
                <p class="id-field__hint">{{ __('instructor.ls_course_hint') }}</p>
                @error('course_id')
                    <p class="id-field__err">{{ $message }}</p>
                @enderror
            </div>

            <div class="id-form-grid">
                <div class="id-field">
                    @include('partials.timezone-select', [
                        'value' => old('timezone', auth()->user()?->timezoneCode()),
                        'class' => 'id-input',
                        'labelClass' => 'block text-[12px] font-extrabold text-[#3A4A63] mb-1.5',
                        'hint' => null,
                    ])
                </div>
                <div class="id-field">
                    <label for="scheduled_at">{{ __('instructor.ls_scheduled_at') }} <span style="color:#B91C1C">*</span></label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}" required class="id-input">
                    @error('scheduled_at')
                        <p class="id-field__err">{{ $message }}</p>
                    @enderror
                </div>
                <div class="id-field">
                    <label for="max_participants">{{ __('instructor.ls_max_participants') }}</label>
                    <input type="number" name="max_participants" id="max_participants" value="{{ old('max_participants', 100) }}" min="2" max="500" class="id-input">
                    @error('max_participants')
                        <p class="id-field__err">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="id-field">
                <label for="password">{{ __('instructor.ls_password_optional') }}</label>
                <input type="text" name="password" id="password" value="{{ old('password') }}" autocomplete="off"
                       class="id-input"
                       placeholder="{{ __('instructor.ls_password_ph') }}">
                @error('password')
                    <p class="id-field__err">{{ $message }}</p>
                @enderror
            </div>

            <div class="id-field">
                <label for="description">{{ __('instructor.ls_description') }}</label>
                <textarea name="description" id="description" rows="4"
                          class="id-input" style="height:auto;min-height:100px;padding:10px 12px;resize:vertical"
                          placeholder="{{ __('instructor.ls_description_ph') }}">{{ old('description') }}</textarea>
                @error('description')
                    <p class="id-field__err">{{ $message }}</p>
                @enderror
            </div>

            <div class="id-foot-actions">
                <a href="{{ $backHref }}" class="id-btn id-btn--outline">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    {{ __('instructor.ls_cancel') }}
                </a>
                <button type="submit" class="id-btn id-btn--gold">
                    <i class="fas fa-broadcast-tower" aria-hidden="true"></i>
                    {{ __('instructor.ls_create') }}
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
