@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_lessons'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $viewerTz = auth()->user()?->timezoneCode() ?? \App\Support\AppTimezone::academy();
    $tones = ['pink', 'blue', 'purple', 'orange'];
    $avatarFallback = \App\Models\User::placeholderAvatarUrl();
    $browseUrl = Route::has('student.learn.index')
        ? route('student.learn.index', ['tab' => 'private'])
        : route('dashboard');
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student.one_to_one_sessions_title'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_lessons'), 'url' => Route::has('student.private-lectures.index') ? route('student.private-lectures.index') : null],
        ['label' => __('student.one_to_one_sessions_nav'), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student.one_to_one_sessions_title') }}</h2>
        <p>{{ __('student.one_to_one_sessions_subtitle') }}</p>
    </div>
    <a href="{{ $browseUrl }}" class="st-pill st-pill--solid">{{ __('student_timeline.browse_teachers') }}</a>
</section>

<section class="st-lesson-list" aria-label="{{ __('student.one_to_one_sessions_title') }}">
    @forelse($sessions as $i => $session)
        @php
            $duration = (int) ($session->duration_minutes ?: 50);
            $canJoin = $session->status === \App\Models\OneToOneSession::STATUS_SCHEDULED && $session->classroomMeeting;
            $joinHref = $canJoin
                ? (Route::has('student.classroom.room')
                    ? route('student.classroom.room', $session->classroomMeeting)
                    : $session->joinUrl())
                : null;
            $recordingHref = ($session->classroomMeeting
                && $session->classroomMeeting->hasBrowserRecording()
                && (
                    $session->status === \App\Models\OneToOneSession::STATUS_COMPLETED
                    || $session->classroomMeeting->ended_at
                )
                && Route::has('student.classroom.recording'))
                ? route('student.classroom.recording', $session->classroomMeeting)
                : null;
            $tone = $tones[$i % count($tones)];
            $instructor = $session->instructor;
            $avatar = $instructor?->avatarDisplayUrl() ?? $avatarFallback;
            $title = $session->course->title ?? __('student_timeline.private_lesson');
        @endphp
        <article class="st-lesson-card st-lesson-card--{{ $tone }}">
            <div class="st-lesson-card__main">
                <img class="st-lesson-card__avatar" src="{{ $avatar }}" alt="" width="48" height="48">
                <div class="st-lesson-card__copy">
                    <div class="st-lesson-card__badges">
                        <span class="st-lesson-card__badge">{{ __('student.one_to_one_session_number', ['n' => $session->session_number]) }}</span>
                        <span class="st-lesson-card__mins">{{ $duration }} {{ __('student_timeline.minutes') }}</span>
                    </div>
                    <h3>{{ $title }}</h3>
                    <p class="st-lesson-card__meta">
                        @if($instructor)
                            {{ $instructor->name }} ·
                        @endif
                        @if($session->scheduled_at)
                            <x-app-datetime :at="$session->scheduled_at" :timezone="$viewerTz" :pattern="$isRtl ? 'l، d M · g:i A' : 'D, M j · g:i A'" />
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
                @endif
                <a href="{{ route('student.one-to-one-sessions.show', $session) }}" class="st-pill st-pill--outline">{{ __('public.view_details') }}</a>
            </div>
        </article>
    @empty
        <div class="st-empty-panel">
            <h3>{{ __('student.one_to_one_sessions_empty') }}</h3>
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
