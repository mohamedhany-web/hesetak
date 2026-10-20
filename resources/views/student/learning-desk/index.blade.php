@extends('layouts.student-timeline')

@section('title', app()->getLocale() === 'ar' ? 'حصصي' : 'My lessons')

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $viewerTz = auth()->user()?->timezoneCode() ?? \App\Support\AppTimezone::academy();
@endphp

@include('partials.student-timeline-top', [
    'locale' => app()->getLocale(),
    'pageTitle' => $isRtl ? 'حصصي' : 'My lessons',
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => $isRtl ? 'حصصي' : 'My lessons', 'url' => null],
    ],
])

@if($children->isNotEmpty())
    <form method="GET" class="st-msg-intro" style="margin-bottom:1rem">
        <label>
            <span>{{ $isRtl ? 'الابن' : 'Child' }}</span>
            <select name="child" onchange="this.form.submit()">
                @foreach($children as $child)
                    <option value="{{ $child->id }}" @selected((int) ($student?->id) === (int) $child->id)>{{ $child->name }}</option>
                @endforeach
            </select>
        </label>
    </form>
@endif

@if(! $student)
    <section class="st-event-card st-event-card--blue">
        <h3>{{ $isRtl ? 'أضف ابناً أولاً' : 'Add a child first' }}</h3>
        <p class="st-event-card__sub">{{ $isRtl ? 'من الملف الشخصي يمكنك ربط الأبناء، ثم تظهر حصصهم هنا.' : 'Link children from your profile, then their lessons appear here.' }}</p>
        <a href="{{ route('profile') }}" class="st-pill st-pill--solid">{{ $isRtl ? 'الملف الشخصي' : 'Profile' }}</a>
    </section>
@else
    <section class="st-msg-intro">
        <div>
            <h2>{{ $isRtl ? 'القادم' : 'Upcoming' }}</h2>
            <p>{{ $student->name }}</p>
        </div>
    </section>
    <section class="st-lesson-list">
        @forelse($upcoming as $session)
            @php
                $recording = ($session->classroomMeeting && $session->classroomMeeting->hasBrowserRecording() && Route::has('student.classroom.recording'))
                    ? route('student.classroom.recording', $session->classroomMeeting)
                    : null;
                $join = ($session->status === \App\Models\OneToOneSession::STATUS_SCHEDULED && $session->classroomMeeting && Route::has('student.classroom.room'))
                    ? route('student.classroom.room', $session->classroomMeeting)
                    : null;
            @endphp
            <article class="st-lesson-card st-lesson-card--blue">
                <div class="st-lesson-card__copy">
                    <h3>{{ $session->course->title ?? ($isRtl ? 'حصة فردية' : 'Private lesson') }}</h3>
                    <p class="st-lesson-card__meta">
                        {{ $session->instructor->name ?? '' }}
                        @if($session->scheduled_at)
                            · <x-app-datetime :at="$session->scheduled_at" :timezone="$viewerTz" />
                        @else
                            · {{ $isRtl ? 'بانتظار الموعد' : 'Waiting for a time' }}
                        @endif
                    </p>
                </div>
                <div class="st-lesson-card__actions">
                    @if($join)<a class="st-pill st-pill--solid" href="{{ $join }}">{{ $isRtl ? 'دخول' : 'Join' }}</a>@endif
                    @if($recording)<a class="st-pill st-pill--outline" href="{{ $recording }}">{{ $isRtl ? 'التسجيل' : 'Recording' }}</a>@endif
                    @if(auth()->id() === (int) $session->student_id)
                        <a class="st-pill st-pill--outline" href="{{ route('student.one-to-one-sessions.show', $session) }}">{{ $isRtl ? 'التفاصيل' : 'Details' }}</a>
                    @endif
                </div>
            </article>
        @empty
            <p class="st-event-card__sub">{{ $isRtl ? 'لا توجد حصص قادمة.' : 'No upcoming lessons.' }}</p>
        @endforelse
    </section>

    <section class="st-msg-intro"><div><h2>{{ $isRtl ? 'السابق والتسجيلات' : 'Past and recordings' }}</h2></div></section>
    <section class="st-lesson-list">
        @forelse($past as $session)
            @php
                $recording = ($session->classroomMeeting && $session->classroomMeeting->hasBrowserRecording() && Route::has('student.classroom.recording'))
                    ? route('student.classroom.recording', $session->classroomMeeting)
                    : null;
            @endphp
            <article class="st-lesson-card st-lesson-card--purple">
                <div class="st-lesson-card__copy">
                    <h3>{{ $session->course->title ?? ($isRtl ? 'حصة فردية' : 'Private lesson') }}</h3>
                    <p class="st-lesson-card__meta">{{ $isRtl ? $session->statusLabel() : ucfirst(str_replace('_', ' ', (string) $session->status)) }} @if($session->scheduled_at) · <x-app-datetime :at="$session->scheduled_at" :timezone="$viewerTz" /> @endif</p>
                </div>
                @if($recording)
                    <a class="st-pill st-pill--solid" href="{{ $recording }}">{{ $isRtl ? 'مشاهدة التسجيل' : 'Watch recording' }}</a>
                @endif
            </article>
        @empty
            <p class="st-event-card__sub">{{ $isRtl ? 'لا توجد حصص سابقة بعد.' : 'No past lessons yet.' }}</p>
        @endforelse
    </section>

    <section class="st-msg-intro">
        <div><h2>{{ $isRtl ? 'الواجبات' : 'Homework' }}</h2></div>
        <a href="{{ route('student.assignments.index') }}" class="st-pill st-pill--outline">{{ $isRtl ? 'كل الواجبات' : 'All homework' }}</a>
    </section>
    <section class="st-lesson-list">
        @forelse($assignments as $assignment)
            @php $submission = $assignment->my_submission; @endphp
            <article class="st-lesson-card st-lesson-card--orange">
                <div class="st-lesson-card__copy">
                    <h3>{{ $assignment->title }}</h3>
                    <p class="st-lesson-card__meta">
                        {{ $assignment->course->title ?? '' }}
                        · {{ $submission ? ($isRtl ? 'تم التسليم' : 'Submitted') : ($isRtl ? 'بانتظار التسليم' : 'Not submitted') }}
                    </p>
                </div>
                <a class="st-pill st-pill--solid" href="{{ route('student.assignments.show', $assignment) }}">{{ $isRtl ? 'فتح' : 'Open' }}</a>
            </article>
        @empty
            <p class="st-event-card__sub">{{ $isRtl ? 'لا واجبات منشورة حالياً.' : 'No published homework right now.' }}</p>
        @endforelse
    </section>
@endif
@endsection
