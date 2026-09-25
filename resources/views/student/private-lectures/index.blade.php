@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_lessons'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $sessions = $sessions ?? collect();
    $threads = $threads ?? collect();
    $reception = $reception ?? null;
    $nextJoinable = $nextJoinable ?? null;
    $upcomingCount = $upcomingCount ?? 0;
    $searchQuery = $searchQuery ?? '';
    $avatarFallback = \App\Models\User::placeholderAvatarUrl();
    $tones = ['pink', 'blue', 'purple', 'orange'];
    $browseUrl = Route::has('student.learn.index')
        ? route('student.learn.index', ['tab' => 'private'])
        : (Route::has('public.courses') ? route('public.courses') : route('dashboard'));
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_lessons'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_lessons'), 'url' => null],
    ],
    'toolbarView' => 'student.private-lectures._index-toolbar',
    'toolbarData' => [
        'searchQuery' => $searchQuery,
        'sessionCount' => method_exists($sessions, 'total') ? $sessions->total() : $sessions->count(),
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

@if($reception && $reception->status === 'pending')
    <div class="st-flash st-flash--warn">{{ __('student_timeline.reception_pending') }}</div>
@endif

@if($nextJoinable)
    @php
        $dur = (int) ($nextJoinable->duration_minutes ?: 50);
        if ($dur !== 50) { $dur = 50; }
        $awaiting = $nextJoinable->isAwaitingTeacherStart();
        $canJoin = $nextJoinable->status === \App\Models\OneToOneSession::STATUS_SCHEDULED
            && $nextJoinable->classroomMeeting
            && \App\Services\OneToOneSessionUnlockService::canStudentJoin($nextJoinable, auth()->user());
        $joinHref = $canJoin
            ? route('student.classroom.room', $nextJoinable->classroomMeeting)
            : null;
        $ends = $nextJoinable->scheduled_at
            ? $nextJoinable->scheduled_at->copy()->addMinutes($dur)
            : null;
    @endphp
    <section class="st-join-hero" aria-label="{{ __('student_timeline.join_class_now') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">
                {{ $awaiting ? __('student_timeline.teacher_starting') : __('student_timeline.next_private_lesson') }}
            </p>
            <h2 class="st-join-hero__title">
                {{ $nextJoinable->course->title ?? __('student_timeline.private_lesson') }}
            </h2>
            <p class="st-join-hero__meta">
                @if($nextJoinable->instructor)
                    {{ $nextJoinable->instructor->name }} ·
                @endif
                <x-app-datetime :at="$nextJoinable->scheduled_at" :pattern="$isRtl ? 'l، d M · g:i A' : 'D, M j · g:i A'" />
                · {{ $dur }} {{ __('student_timeline.minutes') }}
            </p>
        </div>
        <div class="st-join-hero__actions">
            @if($joinHref)
                <a href="{{ $joinHref }}" class="st-pill st-pill--solid st-pill--lg">
                    <i class="fas fa-video" aria-hidden="true"></i>
                    {{ __('student_timeline.join_class_now') }}
                </a>
            @elseif($awaiting)
                <span class="st-pill st-pill--outline">{{ __('student_timeline.teacher_starting') }}</span>
            @endif
            @if(Route::has('student.private-messages.index'))
                <a href="{{ route('student.private-messages.index') }}" class="st-pill st-pill--outline">{{ __('student_timeline.nav_feed') }}</a>
            @endif
        </div>
    </section>
@endif

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.nav_lessons') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.upcoming_short') }}</p>
        <p class="st-stat-card__value">{{ $upcomingCount }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.nav_lessons') }}</p>
        <p class="st-stat-card__value">{{ method_exists($sessions, 'total') ? $sessions->total() : $sessions->count() }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.nav_feed') }}</p>
        <p class="st-stat-card__value">{{ $threads->count() }}</p>
        @if(Route::has('student.private-messages.index'))
            <p class="st-stat-card__hint"><a href="{{ route('student.private-messages.index') }}">{{ __('student_timeline.open_chats') }}</a></p>
        @endif
    </article>
</section>

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student_timeline.private_lessons_list') }}</h2>
        <p>{{ __('student_timeline.private_lessons_hint') }}</p>
    </div>
    <a href="{{ $browseUrl }}" class="st-pill st-pill--solid">{{ __('student_timeline.browse_teachers') }}</a>
</section>

<section class="st-lesson-list" aria-label="{{ __('student_timeline.private_lessons_list') }}">
    @forelse($sessions as $i => $session)
        @php
            $dur = (int) ($session->duration_minutes ?: 50);
            if ($dur !== 50) { $dur = 50; }
            $awaiting = $session->isAwaitingTeacherStart();
            $canJoin = $session->status === \App\Models\OneToOneSession::STATUS_SCHEDULED
                && $session->classroomMeeting
                && \App\Services\OneToOneSessionUnlockService::canStudentJoin($session, auth()->user());
            $lockReason = \App\Services\OneToOneSessionUnlockService::lockReason($session, auth()->user());
            $joinHref = $canJoin ? route('student.classroom.room', $session->classroomMeeting) : null;
            $recordingHref = ($session->classroomMeeting
                && $session->classroomMeeting->hasBrowserRecording()
                && (
                    $session->status === \App\Models\OneToOneSession::STATUS_COMPLETED
                    || $session->classroomMeeting->ended_at
                )
                && Route::has('student.classroom.recording'))
                ? route('student.classroom.recording', $session->classroomMeeting)
                : null;
            $ends = $session->scheduled_at ? $session->scheduled_at->copy()->addMinutes($dur) : null;
            $tone = $tones[$i % count($tones)];
            $instructor = $session->instructor;
            $avatar = ($instructor && $instructor->profile_image)
                ? $instructor->profile_image_url
                : $avatarFallback;
            $title = $session->course->title ?? __('student_timeline.private_lesson');
            $statusLabel = \App\Models\OneToOneSession::statusLabels()[$session->status] ?? $session->status;
        @endphp
        <article class="st-lesson-card st-lesson-card--{{ $tone }}">
            <div class="st-lesson-card__main">
                <img class="st-lesson-card__avatar" src="{{ $avatar }}" alt="" width="48" height="48">
                <div class="st-lesson-card__copy">
                    <div class="st-lesson-card__badges">
                        <span class="st-lesson-card__badge">{{ __('student_timeline.private_lesson') }}</span>
                        <span class="st-lesson-card__mins">{{ $dur }} {{ __('student_timeline.minutes') }}</span>
                    </div>
                    <h3>{{ $title }}</h3>
                    <p class="st-lesson-card__meta">
                        @if($instructor)
                            {{ $instructor->name }} ·
                        @endif
                        @if($session->scheduled_at)
                            <x-app-datetime :at="$session->scheduled_at" :pattern="$isRtl ? 'l، d M · g:i A' : 'D, M j · g:i A'" />
                        @else
                            {{ __('student_timeline.awaiting_schedule') }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="st-lesson-card__foot">
                @if($joinHref)
                    <a href="{{ $joinHref }}" class="st-pill st-pill--solid">
                        <i class="fas fa-video" aria-hidden="true"></i>
                        {{ __('student_timeline.join_now') }}
                    </a>
                @elseif($recordingHref ?? null)
                    <a href="{{ $recordingHref }}" class="st-pill st-pill--outline" target="_blank" rel="noopener">
                        <i class="fas fa-play-circle" aria-hidden="true"></i>
                        {{ __('student_timeline.watch_recording') }}
                    </a>
                @elseif($awaiting)
                    <span class="st-lesson-card__status is-warn">{{ __('student_timeline.teacher_starting') }}</span>
                @elseif($lockReason && $session->status === \App\Models\OneToOneSession::STATUS_SCHEDULED)
                    <span class="st-lesson-card__status is-warn" title="{{ $lockReason }}">مقفلة</span>
                @else
                    <span class="st-lesson-card__status">{{ $statusLabel }}</span>
                @endif
            </div>
        </article>
    @empty
        <div class="st-empty-panel">
            <h3>{{ __('student_timeline.no_private_lessons') }}</h3>
            <p>{{ __('student_timeline.no_private_lessons_hint') }}</p>
            <div class="st-biz-banner__actions">
                <a href="{{ $browseUrl }}" class="st-pill st-pill--solid">{{ __('student_timeline.browse_teachers') }}</a>
                <a href="{{ route('dashboard') }}" class="st-pill st-pill--outline">{{ __('student_timeline.school_gate') }}</a>
            </div>
        </div>
    @endforelse
</section>

@if(method_exists($sessions, 'hasPages') && $sessions->hasPages())
    <div class="st-pager">
        {{ $sessions->links() }}
    </div>
@endif
@endsection

@section('events')
<div class="st-events__top">
    <h2>{{ __('student_timeline.teacher_chats') }}</h2>
</div>

@if(Route::has('student.private-messages.index'))
    <a href="{{ route('student.private-messages.index') }}" class="st-event-card st-event-card--blue">
        <h3>{{ __('student_timeline.all_chats') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.messages_hint') }}</p>
    </a>
@endif

@forelse($threads->take(5) as $i => $thread)
    <a href="{{ route('student.private-messages.show', $thread) }}" class="st-event-card st-event-card--{{ ['pink','orange','blue'][$i % 3] }}">
        <h3>{{ $thread->instructor->name ?? __('student_timeline.teacher') }}</h3>
        <p class="st-event-card__sub">{{ $thread->subject ?: __('student_timeline.private_chat') }}</p>
        <div class="st-event-card__meta">
            <span>{{ $thread->last_message_at?->diffForHumans() ?: __('student_timeline.no_messages_yet') }}</span>
        </div>
    </a>
@empty
    <p class="st-events__empty">{{ __('student_timeline.no_threads') }}</p>
@endforelse

<div class="st-events__see">
    <a href="{{ route('dashboard') }}">{{ __('student_timeline.school_gate') }}</a>
</div>
@endsection
