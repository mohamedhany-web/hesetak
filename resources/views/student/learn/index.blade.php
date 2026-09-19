@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_learn'))

@section('content')
@php
    $locale = app()->getLocale();
    $tab = in_array($tab ?? 'private', ['private', 'mine'], true) ? ($tab ?? 'private') : 'private';
    $packagesUrl = $packages_url
        ?? (Route::has('public.pricing') ? route('public.pricing') : route('dashboard'));
    $privateUnits = (int) ($private_units ?? 0);
    $teachers = $teachers ?? collect();
    $entitlements = $entitlements ?? collect();
    $upcomingPrivate = $upcoming_private ?? collect();
    $filters = $filters ?? ['q' => '', 'subject_id' => null, 'year_id' => null, 'type' => '', 'bookable' => false];
    $filterSubjects = $filter_subjects ?? collect();
    $needsPackage = ($tab === 'private' && $privateUnits < 1)
        || ($tab === 'mine' && $privateUnits < 1 && $entitlements->isEmpty());
    $hasActiveFilters = ($filters['q'] ?? '') !== '' || ! empty($filters['subject_id']);
    $tabUrl = function (string $nextTab) use ($filters) {
        $params = ['tab' => $nextTab];
        if (($filters['q'] ?? '') !== '') {
            $params['q'] = $filters['q'];
        }
        if (! empty($filters['subject_id'])) {
            $params['subject_id'] = $filters['subject_id'];
        }

        return route('student.learn.index', $params);
    };
    $instructorsUrl = Route::has('public.instructors.index')
        ? route('public.instructors.index')
        : $tabUrl('private');
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_learn'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_learn'), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="st-flash st-flash--err">{{ $errors->first() }}</div>
@endif

<section class="st-learn-paths" aria-label="{{ __('student_timeline.learn_paths_title') }}">
    <a href="{{ $tabUrl('private') }}" class="st-learn-path {{ $tab === 'private' ? 'is-on' : '' }}">
        <span class="st-learn-path__icon" aria-hidden="true"><i class="fas fa-chalkboard-teacher"></i></span>
        <span class="st-learn-path__body">
            <strong>{{ __('student_timeline.learn_path_private') }}</strong>
            <small>{{ __('student_timeline.learn_path_private_hint') }}</small>
        </span>
    </a>
    <a href="{{ $tabUrl('mine') }}" class="st-learn-path {{ $tab === 'mine' ? 'is-on' : '' }}">
        <span class="st-learn-path__icon" aria-hidden="true"><i class="fas fa-wallet"></i></span>
        <span class="st-learn-path__body">
            <strong>{{ __('student_timeline.learn_tab_mine') }}</strong>
            <small>{{ __('student_timeline.learn_mine_hint') }}</small>
        </span>
    </a>
    <a href="{{ $instructorsUrl }}" class="st-learn-path">
        <span class="st-learn-path__icon" aria-hidden="true"><i class="fas fa-user-graduate"></i></span>
        <span class="st-learn-path__body">
            <strong>{{ __('student_timeline.browse_teachers') }}</strong>
            <small>{{ __('student_timeline.learn_path_private_hint') }}</small>
        </span>
    </a>
</section>

@if($needsPackage)
    <section class="st-join-hero" aria-label="{{ __('student_timeline.recharge_package') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">{{ __('student_timeline.learn_kicker') }}</p>
            <h2 class="st-join-hero__title">{{ __('student_timeline.learn_need_credit_title') }}</h2>
            <p class="st-join-hero__meta">{{ __('student_timeline.learn_need_credit_hint') }}</p>
        </div>
        <div class="st-join-hero__actions">
            <a href="{{ $packagesUrl }}" class="st-pill st-pill--solid st-pill--lg">{{ __('student_timeline.recharge_package') }}</a>
        </div>
    </section>
@else
    <section class="st-join-hero" aria-label="{{ __('student_timeline.nav_learn') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">{{ __('student_timeline.learn_kicker') }}</p>
            <h2 class="st-join-hero__title">{{ __('student_timeline.learn_ready_title', ['count' => $privateUnits]) }}</h2>
            <p class="st-join-hero__meta">{{ __('student_timeline.learn_ready_hint') }}</p>
        </div>
        <div class="st-join-hero__actions">
            @if($tab === 'private' && method_exists($teachers, 'isNotEmpty') && $teachers->isNotEmpty())
                <a href="{{ $teachers->first()['url'] }}" class="st-pill st-pill--solid st-pill--lg">{{ __('student_timeline.learn_pick_teacher') }}</a>
            @elseif($tab !== 'private')
                <a href="{{ $tabUrl('private') }}" class="st-pill st-pill--solid st-pill--lg">{{ __('student_timeline.learn_tab_private') }}</a>
            @endif
        </div>
    </section>
@endif

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.learn_total_credits') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.credits_private') }}</p>
        <p class="st-stat-card__value">{{ $privateUnits }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.learn_upcoming_private') }}</p>
        <p class="st-stat-card__value">{{ method_exists($upcomingPrivate, 'count') ? $upcomingPrivate->count() : 0 }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.learn_active_credits') }}</p>
        <p class="st-stat-card__value">{{ method_exists($entitlements, 'count') ? $entitlements->count() : 0 }}</p>
    </article>
</section>

<nav class="st-learn-tabs" aria-label="{{ __('student_timeline.learn_tabs') }}">
    <a href="{{ $tabUrl('private') }}" class="st-learn-tab {{ $tab === 'private' ? 'is-on' : '' }}">{{ __('student_timeline.learn_tab_private') }}</a>
    <a href="{{ $tabUrl('mine') }}" class="st-learn-tab {{ $tab === 'mine' ? 'is-on' : '' }}">{{ __('student_timeline.learn_tab_mine') }}</a>
</nav>

@if($tab === 'private')
    <form class="st-learn-toolbar" method="get" action="{{ route('student.learn.index') }}" role="search">
        <input type="hidden" name="tab" value="private">
        @if(request('lang'))
            <input type="hidden" name="lang" value="{{ request('lang') }}">
        @endif

        <label class="st-learn-toolbar__search">
            <span class="visually-hidden">{{ __('student_timeline.learn_search') }}</span>
            <i class="fas fa-search" aria-hidden="true"></i>
            <input
                type="search"
                name="q"
                value="{{ $filters['q'] }}"
                placeholder="{{ __('student_timeline.learn_search_teachers') }}"
                autocomplete="off"
            >
        </label>

        <label class="st-learn-toolbar__select">
            <span class="visually-hidden">{{ __('student_timeline.learn_filter_subject') }}</span>
            <select name="subject_id" aria-label="{{ __('student_timeline.learn_filter_subject') }}">
                <option value="">{{ __('student_timeline.learn_filter_subject_all') }}</option>
                @foreach($filterSubjects as $subject)
                    <option value="{{ $subject->id }}" @selected((int) ($filters['subject_id'] ?? 0) === (int) $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="st-pill st-pill--solid">{{ __('student_timeline.learn_filter_apply') }}</button>

        @if($hasActiveFilters)
            <a href="{{ route('student.learn.index', ['tab' => 'private']) }}" class="st-pill st-pill--ghost">{{ __('student_timeline.learn_filter_clear') }}</a>
        @endif
    </form>

    <section class="st-msg-intro">
        <div>
            <h2>{{ __('student_timeline.learn_teachers_title') }}</h2>
            <p>
                {{ __('student_timeline.learn_teachers_simple') }}
                @if(method_exists($teachers, 'total'))
                    · {{ __('student_timeline.learn_results_count', ['count' => $teachers->total()]) }}
                @endif
            </p>
        </div>
    </section>

    <section class="st-learn-list" aria-label="{{ __('student_timeline.learn_teachers_title') }}">
        @forelse($teachers as $teacher)
            <a href="{{ $teacher['url'] }}" class="st-learn-row-card">
                <img src="{{ $teacher['photo'] }}" alt="" width="56" height="56" loading="lazy">
                <div class="st-learn-row-card__copy">
                    <h3>{{ $teacher['name'] }}</h3>
                    <p>{{ $teacher['headline'] ? \Illuminate\Support\Str::limit($teacher['headline'], 70) : __('student_timeline.learn_teacher_fallback') }}</p>
                </div>
                <span class="st-learn-row-card__cta">
                    {{ $teacher['can_book'] ? __('student_timeline.learn_book_now') : __('student_timeline.learn_view_teacher') }}
                    <i class="fas fa-chevron-{{ $locale === 'ar' ? 'left' : 'right' }}" aria-hidden="true"></i>
                </span>
            </a>
        @empty
            <div class="st-learn-empty">
                {{ $hasActiveFilters ? __('student_timeline.learn_no_filter_results') : __('student_timeline.learn_no_teachers') }}
            </div>
        @endforelse
    </section>

    @if(method_exists($teachers, 'hasPages') && $teachers->hasPages())
        <div class="st-pager">{{ $teachers->links() }}</div>
    @endif
@else
    <section class="st-msg-intro">
        <div>
            <h2>{{ __('student_timeline.learn_mine_title') }}</h2>
            <p>{{ __('student_timeline.learn_mine_hint') }}</p>
        </div>
    </section>

    <section class="st-learn-mine-grid">
        <div class="st-learn-panel">
            <h3>{{ __('student_timeline.learn_active_credits') }}</h3>
            @forelse($entitlements as $ent)
                <div class="st-learn-row">
                    <div>
                        <strong>{{ $ent->servicePackage?->name ?? __('student_timeline.nav_progress') }}</strong>
                        <small>{{ max(0, (int) $ent->units_total - (int) $ent->units_used) }} {{ __('student_timeline.learn_units_left') }}</small>
                    </div>
                </div>
            @empty
                <p class="st-learn-note">{{ __('student_timeline.learn_no_credits') }}</p>
            @endforelse
        </div>

        <div class="st-learn-panel">
            <h3>{{ __('student_timeline.learn_upcoming_private') }}</h3>
            @forelse($upcomingPrivate as $session)
                <a href="{{ route('student.one-to-one-sessions.show', $session) }}" class="st-learn-row st-learn-row--link">
                    <div>
                        <strong>{{ $session->instructor?->name ?? '—' }}</strong>
                        <small>
                            @if($session->scheduled_at)
                                {{ $session->scheduled_at->locale($locale)->translatedFormat('D j M · H:i') }}
                            @else
                                {{ __('student_timeline.learn_pending_schedule') }}
                            @endif
                        </small>
                    </div>
                </a>
            @empty
                <p class="st-learn-note">{{ __('student_timeline.learn_no_private_upcoming') }}</p>
            @endforelse
        </div>
    </section>
@endif
@endsection

@section('events')
<div class="st-events__top">
    <h2>{{ __('student_timeline.quick_links') }}</h2>
</div>

<a href="{{ $tabUrl('private') }}" class="st-event-card st-event-card--orange">
    <h3>{{ __('student_timeline.learn_path_private') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.learn_path_private_hint') }}</p>
</a>

<a href="{{ $packagesUrl }}" class="st-event-card st-event-card--blue">
    <h3>{{ __('student_timeline.session_credits') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.browse_school') }}</p>
</a>

<a href="{{ $instructorsUrl }}" class="st-event-card st-event-card--green">
    <h3>{{ __('student_timeline.browse_teachers') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.learn_teachers_simple') }}</p>
</a>
@endsection
