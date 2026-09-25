@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_feed'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $threads = $threads ?? collect();
    $searchQuery = $searchQuery ?? '';
    $avatarFallback = \App\Models\User::placeholderAvatarUrl();
    $tones = ['pink', 'blue', 'purple', 'orange'];
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_feed'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_feed'), 'url' => null],
    ],
    'toolbarView' => 'student.private-lectures._messages-toolbar',
    'toolbarData' => [
        'searchQuery' => $searchQuery,
        'threadCount' => method_exists($threads, 'total') ? $threads->total() : $threads->count(),
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

<section class="st-msg-intro" aria-label="{{ __('student_timeline.nav_feed') }}">
    <div>
        <h2>{{ __('student_timeline.messages_title') }}</h2>
        <p>{{ __('student_timeline.messages_hint') }}</p>
    </div>
    @if(Route::has('student.private-lectures.index'))
        <a href="{{ route('student.private-lectures.index') }}" class="st-pill st-pill--outline">{{ __('student_timeline.nav_lessons') }}</a>
    @endif
</section>

<section class="st-msg-list" aria-label="{{ __('student_timeline.messages_title') }}">
    @forelse($threads as $i => $thread)
        @php
            $instructor = $thread->instructor;
            $name = $instructor?->name ?: __('student_timeline.teacher');
            $avatar = ($instructor && $instructor->profile_image)
                ? $instructor->profile_image_url
                : $avatarFallback;
            $preview = optional($thread->messages->first())->body;
            $tone = $tones[$i % count($tones)];
        @endphp
        <a href="{{ route('student.private-messages.show', $thread) }}" class="st-msg-row st-msg-row--{{ $tone }}">
            <img class="st-msg-row__avatar" src="{{ $avatar }}" alt="" width="48" height="48">
            <div class="st-msg-row__body">
                <div class="st-msg-row__top">
                    <h3>{{ $name }}</h3>
                    <time datetime="{{ $thread->last_message_at?->toIso8601String() }}">
                        {{ $thread->last_message_at?->diffForHumans() ?: __('student_timeline.no_messages_yet') }}
                    </time>
                </div>
                <p class="st-msg-row__subject">{{ $thread->subject ?: __('student_timeline.private_chat') }}</p>
                @if($preview)
                    <p class="st-msg-row__preview">{{ \Illuminate\Support\Str::limit($preview, 90) }}</p>
                @else
                    <p class="st-msg-row__preview">{{ __('student_timeline.start_chat_hint') }}</p>
                @endif
            </div>
            <span class="st-msg-row__chev" aria-hidden="true">
                <i class="fas fa-chevron-{{ $isRtl ? 'left' : 'right' }}"></i>
            </span>
        </a>
    @empty
        <div class="st-empty-panel">
            <h3>{{ __('student_timeline.no_threads') }}</h3>
            <p>{{ __('student_timeline.no_threads_hint') }}</p>
            <div class="st-biz-banner__actions">
                @if(Route::has('student.private-lectures.index'))
                    <a href="{{ route('student.private-lectures.index') }}" class="st-pill st-pill--solid">{{ __('student_timeline.nav_lessons') }}</a>
                @endif
                <a href="{{ route('dashboard') }}" class="st-pill st-pill--outline">{{ __('student_timeline.school_gate') }}</a>
            </div>
        </div>
    @endforelse
</section>

@if(method_exists($threads, 'hasPages') && $threads->hasPages())
    <div class="st-pager">
        {{ $threads->links() }}
    </div>
@endif
@endsection

@section('events')
<div class="st-events__top">
    <h2>{{ __('student_timeline.quick_links') }}</h2>
</div>

<a href="{{ route('dashboard') }}" class="st-event-card st-event-card--blue">
    <h3>{{ __('student_timeline.school_gate') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.back_to_timeline') }}</p>
</a>

@if(Route::has('student.private-lectures.index'))
    <a href="{{ route('student.private-lectures.index') }}" class="st-event-card st-event-card--orange">
        <h3>{{ __('student_timeline.nav_lessons') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.open_private_lessons') }}</p>
    </a>
@endif

@if(Route::has('student.classes.index'))
    <a href="{{ route('student.classes.index') }}" class="st-event-card st-event-card--pink">
        <h3>{{ __('student_timeline.my_classes') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.classes_hint') }}</p>
    </a>
@endif
@endsection
