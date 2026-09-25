@extends('layouts.student-timeline')

@section('title', __('student_timeline.chat_with', ['name' => $thread->instructor->name ?? __('student_timeline.teacher')]))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $instructor = $thread->instructor;
    $instructorName = $instructor?->name ?: __('student_timeline.teacher');
    $avatarFallback = \App\Models\User::placeholderAvatarUrl();
    $instructorAvatar = ($instructor && $instructor->profile_image)
        ? $instructor->profile_image_url
        : $avatarFallback;
    $messages = $thread->messages->where('is_internal_note', false)->values();
    $meId = (int) auth()->id();
    $otherThreads = $otherThreads ?? collect();
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => $instructorName,
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_feed'), 'url' => route('student.private-messages.index')],
        ['label' => $instructorName, 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="st-flash st-flash--err">{{ $errors->first() }}</div>
@endif

<section class="st-chat" aria-label="{{ __('student_timeline.chat_with', ['name' => $instructorName]) }}">
    <header class="st-chat__head">
        <img class="st-chat__avatar" src="{{ $instructorAvatar }}" alt="" width="44" height="44">
        <div class="st-chat__who">
            <h2>{{ $instructorName }}</h2>
            <p>{{ $thread->subject ?: __('student_timeline.private_chat') }}</p>
        </div>
        <a href="{{ route('student.private-messages.index') }}" class="st-pill st-pill--outline">{{ __('student_timeline.all_chats') }}</a>
    </header>

    <div class="st-chat__stream" id="stChatStream">
        @forelse($messages as $msg)
            @php $mine = (int) $msg->sender_id === $meId; @endphp
            <div class="st-chat__bubble {{ $mine ? 'is-mine' : 'is-theirs' }}">
                <p class="st-chat__meta">
                    {{ $msg->sender->name ?? '' }}
                    · {{ $msg->created_at?->timezone(config('app.timezone'))->translatedFormat($isRtl ? 'd M · g:i A' : 'M j · g:i A') }}
                </p>
                <p class="st-chat__body">{{ $msg->body }}</p>
            </div>
        @empty
            <div class="st-chat__empty">
                <p>{{ __('student_timeline.start_chat_hint') }}</p>
            </div>
        @endforelse
    </div>

    <form method="post" action="{{ route('student.private-messages.send', $thread) }}" class="st-chat__composer">
        @csrf
        <label class="sr-only" for="stChatBody">{{ __('student_timeline.write_message') }}</label>
        <textarea id="stChatBody" name="body" rows="2" required maxlength="5000" placeholder="{{ __('student_timeline.write_message') }}">{{ old('body') }}</textarea>
        <button type="submit" class="st-pill st-pill--solid st-pill--lg">
            <i class="fas fa-paper-plane" aria-hidden="true"></i>
            {{ __('student_timeline.send') }}
        </button>
    </form>
</section>
@endsection

@section('events')
<div class="st-events__top">
    <h2>{{ __('student_timeline.other_chats') }}</h2>
</div>

<a href="{{ route('student.private-messages.index') }}" class="st-event-card st-event-card--blue">
    <h3>{{ __('student_timeline.all_chats') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.back_to_inbox') }}</p>
</a>

@forelse($otherThreads as $i => $other)
    <a href="{{ route('student.private-messages.show', $other) }}" class="st-event-card st-event-card--{{ ['pink','orange','blue'][$i % 3] }}">
        <h3>{{ $other->instructor->name ?? __('student_timeline.teacher') }}</h3>
        <p class="st-event-card__sub">{{ $other->subject ?: __('student_timeline.private_chat') }}</p>
        <div class="st-event-card__meta">
            <span>{{ $other->last_message_at?->diffForHumans() ?: __('student_timeline.no_messages_yet') }}</span>
        </div>
    </a>
@empty
    <p class="st-events__empty">{{ __('student_timeline.no_other_chats') }}</p>
@endforelse
@endsection

@push('scripts')
<script>
(function () {
    var stream = document.getElementById('stChatStream');
    if (stream) stream.scrollTop = stream.scrollHeight;
})();
</script>
@endpush
