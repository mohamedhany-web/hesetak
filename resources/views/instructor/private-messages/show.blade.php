@extends('layouts.app')

@section('title', $thread->student?->name ?: __('instructor.pm_chat_fallback'))
@section('page_title', $thread->student?->name ?: __('instructor.pm_chat_fallback'))

@section('content')
@php
    $locale = app()->getLocale();
    $student = $thread->student;
    $name = $student?->name ?: __('instructor.pm_student_fallback');
    $avatarFallback = \App\Models\User::placeholderAvatarUrl();
    $avatar = ($student && $student->profile_image) ? $student->profile_image_url : $avatarFallback;
    $messages = $thread->messages->where('is_internal_note', false)->values();
    $meId = (int) auth()->id();
    $backHref = route('instructor.private-messages.index');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $name }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.pm_private_chat') }}</p>
            <h2 class="id-hero__title">{{ $name }}</h2>
            <p class="id-hero__meta">{{ $thread->subject ?: __('instructor.pm_private_chat') }}</p>
        </div>
        <div class="id-hero__actions">
            @if(Route::has('instructor.notifications.index'))
                <a href="{{ route('instructor.notifications.index') }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    {{ __('instructor.notifications') }}
                </a>
            @endif
            <a href="{{ $backHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.pm_all_messages') }}
            </a>
        </div>
    </section>

    @if($errors->any())
        <div class="id-alert id-alert--err">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="id-panel id-chat" aria-label="{{ __('instructor.pm_private_chat') }}">
        <header class="id-chat__head">
            <img src="{{ $avatar }}" alt="" class="id-chat__avatar" width="44" height="44">
            <div class="min-w-0">
                <p class="id-chat__name">{{ $name }}</p>
                <p class="id-chat__sub">{{ $thread->subject ?: __('instructor.pm_private_chat') }}</p>
            </div>
        </header>

        <div class="id-chat__body" id="pmChatBody">
            @forelse($messages as $msg)
                @php $mine = (int) $msg->sender_id === $meId; @endphp
                <div class="id-bubble {{ $mine ? 'id-bubble--me' : 'id-bubble--them' }}">
                    <p class="id-bubble__meta">
                        {{ $msg->sender->name ?? '' }} · {{ $msg->created_at?->diffForHumans() }}
                    </p>
                    <p class="id-bubble__text">{{ $msg->body }}</p>
                </div>
            @empty
                <div class="id-empty" style="border:0;background:transparent;padding:32px 8px">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-paper-plane"></i></span>
                    <p>{{ __('instructor.pm_empty_thread') }}</p>
                </div>
            @endforelse
        </div>

        <form method="post" action="{{ route('instructor.private-messages.send', $thread) }}" class="id-chat__compose">
            @csrf
            <div class="id-field" style="margin:0">
                <label for="pm-body">{{ __('instructor.pm_write_placeholder') }}</label>
                <textarea name="body" id="pm-body" rows="3" required maxlength="5000"
                          class="id-input" style="height:auto;min-height:88px;padding:10px 12px;resize:vertical"
                          placeholder="{{ __('instructor.pm_write_placeholder') }}">{{ old('body') }}</textarea>
            </div>
            <div>
                <button type="submit" class="id-btn id-btn--gold">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                    {{ __('instructor.pm_send') }}
                </button>
            </div>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var body = document.getElementById('pmChatBody');
    if (body) body.scrollTop = body.scrollHeight;
});
</script>
@endsection
