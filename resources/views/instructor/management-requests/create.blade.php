@extends('layouts.app')

@section('title', __('instructor.submit_request_title') . ' - ' . config('app.name'))
@section('page_title', __('instructor.submit_request_title'))

@section('content')
@php
    $locale = app()->getLocale();
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.submit_new_request_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.my_requests_to_management') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.submit_new_request_title') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.submit_request_desc') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.management-requests.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-panel">
        <header class="id-panel__head">
            <h2>{{ __('instructor.new_request') }}</h2>
        </header>

        <form action="{{ route('instructor.management-requests.store') }}" method="POST" class="id-form">
            @csrf
            <div class="id-field">
                <label for="subject">{{ __('instructor.request_subject_required') }}</label>
                <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required class="id-input"
                       placeholder="{{ __('instructor.subject_placeholder') }}">
                @error('subject')<p class="id-field__err">{{ $message }}</p>@enderror
            </div>
            <div class="id-field">
                <label for="message">{{ __('instructor.request_details_required') }}</label>
                <textarea name="message" id="message" rows="6" required class="id-input"
                          style="min-height:140px;padding-top:10px;padding-bottom:10px;resize:vertical"
                          placeholder="{{ __('instructor.message_placeholder') }}">{{ old('message') }}</textarea>
                @error('message')<p class="id-field__err">{{ $message }}</p>@enderror
            </div>

            <div class="id-foot-actions">
                <a href="{{ route('instructor.management-requests.index') }}" class="id-btn id-btn--outline">{{ __('common.cancel') }}</a>
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                    {{ __('instructor.send_request') }}
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
