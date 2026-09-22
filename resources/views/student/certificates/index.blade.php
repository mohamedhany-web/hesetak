@extends('layouts.student-timeline')

@section('title', __('student.my_certificates_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $certificates = $certificates ?? collect();
    $stats = $stats ?? ['total' => 0, 'issued' => 0];
    $tones = ['blue', 'pink', 'orange', 'purple'];
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_certificates'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_certificates'), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif

<section class="st-join-hero {{ $certificates->count() ? '' : 'st-join-hero--muted' }}" aria-label="{{ __('student.my_certificates_title') }}">
    <div class="st-join-hero__copy">
        <p class="st-join-hero__kicker">{{ __('student_timeline.nav_certificates') }}</p>
        <h2 class="st-join-hero__title">
            @if($certificates->count())
                {{ __('student.my_certificates_title') }}
            @else
                {{ __('student.no_certificates') }}
            @endif
        </h2>
        <p class="st-join-hero__meta">{{ __('student.certificates_subtitle') }}</p>
    </div>
    <div class="st-join-hero__actions">
        <a href="{{ route('my-courses.index') }}" class="st-pill st-pill--solid st-pill--lg">
            <i class="fas fa-book-open" aria-hidden="true"></i>
            {{ __('student.view_my_courses') }}
        </a>
    </div>
</section>

<section class="st-stats st-stats--classes" aria-label="{{ __('student.my_certificates_title') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.total_certificates') }}</p>
        <p class="st-stat-card__value">{{ $stats['total'] ?? 0 }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.issued_label') }}</p>
        <p class="st-stat-card__value">{{ $stats['issued'] ?? 0 }}</p>
    </article>
</section>

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student.my_certificates_title') }}</h2>
        <p>{{ __('student.certificates_subtitle') }}</p>
    </div>
</section>

@if($certificates->count() > 0)
    <div class="st-cert-grid" aria-label="{{ __('student.my_certificates_title') }}">
        @foreach($certificates as $i => $certificate)
            @php $tone = $tones[$i % count($tones)]; @endphp
            <a href="{{ route('student.certificates.show', $certificate) }}" class="st-cert-card st-cert-card--{{ $tone }}">
                <div class="st-cert-card__media" aria-hidden="true">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="st-cert-card__body">
                    <h3>{{ $certificate->title ?? $certificate->course_name ?? __('student.completion_certificate') }}</h3>
                    @if($certificate->course)
                        <p>{{ $certificate->course->title }}</p>
                    @endif
                    <div class="st-learn-chips">
                        <span>
                            <i class="fas fa-calendar" aria-hidden="true"></i>
                            {{ ($certificate->issued_at ? $certificate->issued_at->format('Y-m-d') : ($certificate->issue_date ? $certificate->issue_date->format('Y-m-d') : '—')) }}
                        </span>
                        @if($certificate->certificate_number)
                            <span>#{{ substr((string) $certificate->certificate_number, -6) }}</span>
                        @endif
                    </div>
                    <span class="st-pill st-pill--solid">
                        {{ __('student.view_certificate') }}
                        <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
    @if(method_exists($certificates, 'hasPages') && $certificates->hasPages())
        <div class="st-pager">{{ $certificates->links() }}</div>
    @endif
@else
    <div class="st-empty-panel">
        <h3>{{ __('student.no_certificates') }}</h3>
        <p>{{ __('student.no_certificates_desc') }}</p>
        <div class="st-biz-banner__actions">
            <a href="{{ route('my-courses.index') }}" class="st-pill st-pill--solid">{{ __('student.view_my_courses') }}</a>
        </div>
    </div>
@endif
@endsection
