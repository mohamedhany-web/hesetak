@extends('layouts.student-timeline')

@section('title', $instructor->name.' — '.__('student_timeline.learn_teacher_profile'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $profile = $profile ?? null;
    $canBook = !empty($can_book);
    $unitsLeft = (int) ($units_left ?? 0);
    $bookableSlots = $bookable_slots ?? collect();
    $weeklyCalendar = $weekly_calendar ?? [];
    $groupCourses = collect();
    $oneToOneCourses = $one_to_one_courses ?? collect();
    $packagesUrl = $packages_url ?? (Route::has('public.pricing') ? route('public.pricing') : route('dashboard'));
    $photoUrl = $photo_url ?: \App\Models\User::placeholderAvatarUrl();
    $headline = $profile?->headline_clean ?: '';
    $bio = $profile?->bio_clean ?: '';
    $skills = $profile?->skills_list ?? [];
    $experience = $profile?->experience_list ?? [];
    $teachingChips = $teaching_chips ?? [];
    $hasIntroVideo = !empty($has_intro_video);
    $introEmbedUrl = $intro_embed_url ?? null;
    $introDirectVideo = $intro_direct_video ?? null;
    $introThumb = $intro_video_thumb ?? $photoUrl;
    $consultationPrice = $consultation_price ?? null;
    $consultationDuration = $consultation_duration ?? null;
    $coursesCount = (int) ($courses_count ?? $oneToOneCourses->count());
    $allCourses = $oneToOneCourses->values();
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.learn_teacher_profile'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_learn'), 'url' => route('student.learn.index')],
        ['label' => $instructor->name, 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="st-flash st-flash--err">{{ $errors->first() }}</div>
@endif

{{-- Identity --}}
<section class="st-teacher-hero">
    <div class="st-teacher-hero__glow" aria-hidden="true"></div>
    <div class="st-teacher-hero__inner">
        <img class="st-teacher-hero__avatar" src="{{ $photoUrl }}" alt="" width="112" height="112">
        <div class="st-teacher-hero__copy">
            <p class="st-teacher-hero__kicker">{{ __('student_timeline.learn_teacher_kicker') }}</p>
            <h1>{{ $instructor->name }}</h1>
            @if($headline)
                <p class="st-teacher-hero__headline">{{ $headline }}</p>
            @endif
            @if(!empty($skills) || !empty($teachingChips))
                <div class="st-learn-chips st-learn-chips--light">
                    @foreach(array_slice(array_values(array_unique(array_merge($teachingChips, $skills))), 0, 8) as $chip)
                        <span>{{ $chip }}</span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="st-teacher-hero__credit {{ $canBook ? 'is-ok' : 'is-empty' }}">
            @if($canBook)
                <span>{{ __('student_timeline.learn_your_credit') }}</span>
                <strong>{{ $unitsLeft }}</strong>
                <small>{{ __('student_timeline.learn_units_sessions') }}</small>
            @else
                <span>{{ __('student_timeline.learn_need_package') }}</span>
                <a href="{{ $packagesUrl }}" class="st-pill st-pill--solid">{{ __('student_timeline.recharge_package') }}</a>
            @endif
        </div>
    </div>
</section>

{{-- Quick facts --}}
<section class="st-teacher-facts" aria-label="{{ __('student_timeline.learn_teacher_facts') }}">
    <article class="st-teacher-fact">
        <span class="st-teacher-fact__icon" aria-hidden="true"><i class="fas fa-graduation-cap"></i></span>
        <div>
            <strong>{{ $coursesCount }}</strong>
            <small>{{ __('student_timeline.learn_teacher_courses') }}</small>
        </div>
    </article>
    <article class="st-teacher-fact">
        <span class="st-teacher-fact__icon" aria-hidden="true"><i class="fas fa-lightbulb"></i></span>
        <div>
            <strong>{{ count($skills) }}</strong>
            <small>{{ __('student_timeline.learn_skills') }}</small>
        </div>
    </article>
    <article class="st-teacher-fact">
        <span class="st-teacher-fact__icon" aria-hidden="true"><i class="fas fa-calendar-week"></i></span>
        <div>
            <strong>{{ count($weeklyCalendar) }}</strong>
            <small>{{ __('student_timeline.learn_week_days') }}</small>
        </div>
    </article>
    @if(student_ui('show_consultations', false) && ($consultationDuration || $consultationPrice !== null))
        <article class="st-teacher-fact">
            <span class="st-teacher-fact__icon" aria-hidden="true"><i class="fas fa-comments"></i></span>
            <div>
                <strong>
                    @if($consultationDuration)
                        {{ $consultationDuration }}{{ __('student_timeline.learn_min_short') }}
                    @else
                        —
                    @endif
                </strong>
                <small>
                    @if($consultationPrice !== null)
                        {{ number_format($consultationPrice, 0) }} {{ __('student_timeline.learn_egp') }}
                    @else
                        {{ __('student_timeline.learn_consultation') }}
                    @endif
                </small>
            </div>
        </article>
    @endif
</section>

<div class="st-teacher-layout">
    <div class="st-teacher-main">
        @if($bio !== '')
            <section class="st-settings-block" aria-labelledby="stTeacherBio">
                <div class="st-settings-block__head">
                    <span class="st-settings-block__icon" aria-hidden="true"><i class="fas fa-quote-right"></i></span>
                    <div>
                        <h3 id="stTeacherBio">{{ __('student_timeline.learn_about_teacher') }}</h3>
                        <p>{{ __('student_timeline.learn_about_hint') }}</p>
                    </div>
                </div>
                <p class="st-teacher-bio">{{ $bio }}</p>
            </section>
        @endif

        @if(!empty($skills))
            <section class="st-settings-block" aria-labelledby="stTeacherSkills">
                <div class="st-settings-block__head">
                    <span class="st-settings-block__icon" aria-hidden="true"><i class="fas fa-star"></i></span>
                    <div>
                        <h3 id="stTeacherSkills">{{ __('student_timeline.learn_skills') }}</h3>
                        <p>{{ __('student_timeline.learn_skills_hint') }}</p>
                    </div>
                </div>
                <div class="st-learn-chips">
                    @foreach($skills as $skill)
                        <span>{{ $skill }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        @if(!empty($teachingChips))
            <section class="st-settings-block" aria-labelledby="stTeacherMeta">
                <div class="st-settings-block__head">
                    <span class="st-settings-block__icon" aria-hidden="true"><i class="fas fa-tags"></i></span>
                    <div>
                        <h3 id="stTeacherMeta">{{ __('student_timeline.learn_teaching_focus') }}</h3>
                        <p>{{ __('student_timeline.learn_teaching_focus_hint') }}</p>
                    </div>
                </div>
                <div class="st-learn-chips">
                    @foreach($teachingChips as $chip)
                        <span>{{ $chip }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        @if(!empty($experience))
            <section class="st-settings-block" aria-labelledby="stTeacherExp">
                <div class="st-settings-block__head">
                    <span class="st-settings-block__icon" aria-hidden="true"><i class="fas fa-medal"></i></span>
                    <div>
                        <h3 id="stTeacherExp">{{ __('student_timeline.learn_experience') }}</h3>
                        <p>{{ __('student_timeline.learn_experience_hint') }}</p>
                    </div>
                </div>
                <ul class="st-teacher-list">
                    @foreach($experience as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="st-settings-block" aria-labelledby="stTeacherBook">
            <div class="st-settings-block__head">
                <span class="st-settings-block__icon" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
                <div>
                    <h3 id="stTeacherBook">{{ __('student_timeline.learn_book_private') }}</h3>
                    <p>{{ __('student_timeline.learn_book_private_hint') }}</p>
                </div>
            </div>

            @if($canBook)
                @if($bookableSlots->isNotEmpty())
                    <form method="POST" action="{{ route('student.one-to-one-sessions.book-instructor', $instructor) }}" class="st-teacher-slots">
                        @csrf
                        @foreach($bookableSlots as $slot)
                            @php
                                $starts = is_array($slot) ? ($slot['starts_at'] ?? null) : null;
                                $label = is_array($slot) ? ($slot['label'] ?? null) : null;
                                if ($starts instanceof \Carbon\Carbon) {
                                    $value = $starts->toDateTimeString();
                                    $label = $label ?: $starts->locale($locale)->translatedFormat('D j M — g:i A');
                                } else {
                                    continue;
                                }
                            @endphp
                            <button type="submit" name="scheduled_at" value="{{ $value }}" class="st-teacher-slot">
                                <span>{{ $label }}</span>
                                <i class="fas fa-bolt" aria-hidden="true"></i>
                            </button>
                        @endforeach
                    </form>
                @else
                    <p class="st-learn-note">{{ __('student_timeline.learn_no_slots') }}</p>
                @endif
            @else
                <div class="st-teacher-cta">
                    <p>{{ __('student_timeline.learn_book_needs_credit') }}</p>
                    <a href="{{ $packagesUrl }}" class="st-pill st-pill--solid st-pill--lg">{{ __('student_timeline.recharge_package') }}</a>
                    <a href="{{ route('student.learn.index', ['tab' => 'private']) }}" class="st-pill st-pill--outline">{{ __('student_timeline.nav_learn') }}</a>
                </div>
            @endif
        </section>

        @if($allCourses->isNotEmpty())
            <section class="st-settings-block" aria-labelledby="stTeacherCourses">
                <div class="st-settings-block__head">
                    <span class="st-settings-block__icon" aria-hidden="true"><i class="fas fa-graduation-cap"></i></span>
                    <div>
                        <h3 id="stTeacherCourses">{{ __('student_timeline.learn_teacher_courses') }}</h3>
                        <p>{{ __('student_timeline.learn_teacher_courses_hint') }}</p>
                    </div>
                </div>
                <div class="st-teacher-courses">
                    @foreach($oneToOneCourses as $course)
                        <article class="st-teacher-course">
                            <span class="st-learn-badge is-ok">1:1</span>
                            <strong>{{ $course->title }}</strong>
                            <small>{{ (int) ($course->lessons_count ?? 0) }} {{ __('student_timeline.learn_lessons') }}</small>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <aside class="st-teacher-side">
        <section class="st-tvideo st-tvideo--side" aria-labelledby="stTeacherVideo">
            <div class="st-tvideo__head">
                <h2 id="stTeacherVideo">{{ __('student_timeline.learn_intro_video') }}</h2>
                <span class="st-tvideo__badge" aria-hidden="true"><i class="fas fa-play"></i></span>
            </div>
            <div class="st-tvideo__frame{{ $hasIntroVideo ? '' : ' is-empty' }}">
                @if($introEmbedUrl)
                    <iframe
                        src="{{ $introEmbedUrl }}"
                        title="{{ $instructor->name }} — {{ __('student_timeline.learn_intro_video') }}"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen; web-share"
                        allowfullscreen
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"
                    ></iframe>
                @elseif($introDirectVideo)
                    <video controls playsinline preload="metadata" poster="{{ $introThumb }}">
                        <source src="{{ $introDirectVideo }}">
                        {{ __('student_timeline.learn_video_unsupported') }}
                    </video>
                @else
                    <div class="st-tvideo__poster" style="--st-tvideo-poster: url('{{ $photoUrl }}')">
                        <div class="st-tvideo__poster-inner">
                            <span class="st-tvideo__play" aria-hidden="true"><i class="fas fa-video"></i></span>
                            <strong>{{ __('student_timeline.learn_no_video_title') }}</strong>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="st-teacher-week" aria-label="{{ __('student_timeline.learn_week_rhythm') }}">
            <h3>{{ __('student_timeline.learn_week_rhythm') }}</h3>
            @forelse($weeklyCalendar as $day)
                <div class="st-teacher-week__day">
                    <strong>{{ $day['label'] }}</strong>
                    <div class="st-learn-chips">
                        @foreach($day['times'] as $time)
                            <span>{{ $time }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="st-learn-note">{{ __('student_timeline.learn_no_week_rules') }}</p>
            @endforelse
        </section>

        <a href="{{ route('student.learn.index', ['tab' => 'private']) }}" class="st-event-card st-event-card--blue" style="display:block;text-decoration:none">
            <h3>{{ __('student_timeline.learn_pick_teacher') }}</h3>
            <p class="st-event-card__sub">{{ __('student_timeline.learn_teachers_hint') }}</p>
        </a>

        <a href="{{ $packagesUrl }}" class="st-event-card st-event-card--orange" style="display:block;text-decoration:none">
            <h3>{{ __('student_timeline.session_credits') }}</h3>
            <p class="st-event-card__sub">{{ __('student_timeline.browse_school') }}</p>
        </a>
    </aside>
</div>
@endsection

@section('events')
<div class="st-events__top">
    <h2>{{ __('student_timeline.quick_links') }}</h2>
</div>

<a href="{{ route('student.learn.index') }}" class="st-event-card st-event-card--blue">
    <h3>{{ __('student_timeline.nav_learn') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.learn_hint') }}</p>
</a>

@if($canBook)
    <div class="st-event-card st-event-card--green">
        <h3>{{ __('student_timeline.learn_credit_badge', ['count' => $unitsLeft]) }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.learn_book_private_hint') }}</p>
    </div>
@else
    <a href="{{ $packagesUrl }}" class="st-event-card st-event-card--orange">
        <h3>{{ __('student_timeline.recharge_package') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.learn_book_needs_credit') }}</p>
    </a>
@endif

<a href="{{ route('student.private-lectures.index') }}" class="st-event-card st-event-card--pink">
    <h3>{{ __('student_timeline.nav_lessons') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.learn_upcoming_private') }}</p>
</a>
@endsection
