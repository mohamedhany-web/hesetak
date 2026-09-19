@extends('layouts.student-timeline')

@section('title', __('student.course_subscriptions_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $browseUrl = Route::has('public.courses') ? route('public.courses') : route('dashboard');
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student.course_subscriptions_title'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student.my_courses'), 'url' => route('my-courses.index')],
        ['label' => __('student.course_subscriptions_title'), 'url' => null],
    ],
])

<section class="st-join-hero" aria-label="{{ __('student.course_subscriptions_title') }}">
    <div class="st-join-hero__copy">
        <p class="st-join-hero__kicker">{{ __('student.my_courses') }}</p>
        <h2 class="st-join-hero__title">{{ __('student.course_subscriptions_title') }}</h2>
        <p class="st-join-hero__meta">{{ __('student.course_subscriptions_subtitle') }}</p>
    </div>
    <div class="st-join-hero__actions">
        <a href="{{ $browseUrl }}" class="st-pill st-pill--solid st-pill--lg">{{ __('student.browse_courses') }}</a>
        <a href="{{ route('my-courses.index') }}" class="st-pill st-pill--outline">{{ __('student_timeline.courses_back') }}</a>
    </div>
</section>

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.courses_stats') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.course_subscriptions_stat_total') }}</p>
        <p class="st-stat-card__value">{{ (int) $stats['total'] }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.course_subscriptions_stat_active') }}</p>
        <p class="st-stat-card__value">{{ (int) $stats['active'] }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.course_subscriptions_stat_soon') }}</p>
        <p class="st-stat-card__value">{{ (int) $stats['expiring_soon'] }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.course_subscriptions_stat_expired') }}</p>
        <p class="st-stat-card__value">{{ (int) $stats['expired'] }}</p>
    </article>
</section>

@if($enrollments->isEmpty())
    <div class="st-empty-panel">
        <h3>{{ __('student.course_subscriptions_empty_title') }}</h3>
        <p>{{ __('student.course_subscriptions_empty_desc') }}</p>
        <div class="st-biz-banner__actions">
            <a href="{{ $browseUrl }}" class="st-pill st-pill--solid">{{ __('student.browse_courses_btn') }}</a>
            <a href="{{ route('my-courses.index') }}" class="st-pill st-pill--outline">{{ __('student.my_courses') }}</a>
        </div>
    </div>
@else
    <section class="st-credit-list" aria-label="{{ __('student.course_subscriptions_title') }}">
        @foreach($enrollments as $enrollment)
            @php
                $course = $enrollment->course;
                if (! $course) {
                    continue;
                }
                $thumb = $course->thumbnail_url ?? null;
                $isActive = $enrollment->subscriptionIsActive();
                $isExpired = $enrollment->subscriptionIsExpired();
                $expiringSoon = $enrollment->subscriptionExpiringSoon(7);
                $daysLeft = $enrollment->daysUntilExpiry();
                $monthlyPrice = $course->effectiveMonthlyPrice();
                $tone = $isExpired ? 'orange' : ($expiringSoon ? 'pink' : 'blue');
                $status = $isExpired
                    ? __('student.course_subscriptions_status_expired')
                    : ($expiringSoon
                        ? __('student.course_subscriptions_status_soon')
                        : __('student.course_subscriptions_status_active'));
            @endphp
            <article class="st-credit-card st-credit-card--{{ $tone }} {{ $isExpired ? 'is-dim' : '' }}">
                <div class="st-credit-card__main">
                    <div class="st-credit-card__copy">
                        <div class="st-credit-card__badges">
                            <span class="st-credit-card__badge {{ $isActive && ! $isExpired ? 'is-ok' : '' }}">{{ $status }}</span>
                            @if($course->isOneToOne() && $course->instructor)
                                <span class="st-credit-card__badge">{{ __('student.course_subscriptions_one_to_one') }}</span>
                            @else
                                <span class="st-credit-card__badge">{{ __('student.course_subscriptions_group') }}</span>
                            @endif
                        </div>
                        <h3>{{ $course->title }}</h3>
                        <p class="st-credit-card__meta">
                            {{ __('student.course_subscriptions_activated') }}: {{ $enrollment->activated_at?->format('Y-m-d') ?? '—' }}
                            · {{ __('student.course_subscriptions_expires') }}: {{ $enrollment->expires_at?->format('Y-m-d') ?? '—' }}
                        </p>
                        <p class="st-credit-card__bookable">
                            <small>
                                @if($daysLeft === null)
                                    —
                                @elseif($daysLeft < 0)
                                    {{ __('student.course_subscriptions_expired_days', ['days' => abs($daysLeft)]) }}
                                @else
                                    {{ __('student.course_subscriptions_days_remaining', ['days' => $daysLeft]) }}
                                @endif
                                @if($monthlyPrice > 0)
                                    · {{ number_format($monthlyPrice, 0) }} {{ currency_symbol() }} / {{ __('public.per_month') }}
                                @endif
                            </small>
                        </p>
                    </div>
                    @if($thumb)
                        <img src="{{ $thumb }}" alt="" width="72" height="72" style="border-radius:14px;object-fit:cover;flex-shrink:0">
                    @endif
                </div>
                <div class="st-credit-card__foot">
                    @if($isActive)
                        <a href="{{ route('my-courses.learn', $course->id) }}" class="st-pill st-pill--solid">
                            <i class="fas fa-play" aria-hidden="true"></i>
                            {{ __('student.continue_learning') }}
                        </a>
                    @endif
                    @if($isExpired || $expiringSoon)
                        <a href="{{ $enrollment->renewalCheckoutUrl() }}" class="st-pill st-pill--solid">
                            <i class="fas fa-sync-alt" aria-hidden="true"></i>
                            {{ __('student.course_subscriptions_renew') }}
                        </a>
                    @endif
                    <a href="{{ route('my-courses.show', $course->id) }}" class="st-pill st-pill--outline">{{ __('student_timeline.courses_open') }}</a>
                </div>
            </article>
        @endforeach
    </section>
@endif

<p class="st-learn-note" style="margin:8px 0 24px">{{ __('student.course_subscriptions_footer') }}</p>
@endsection
