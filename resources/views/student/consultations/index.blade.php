@extends('layouts.student-timeline')

@section('title', app()->getLocale() === 'ar' ? 'طلبات الاستشارة' : 'Consultations')
@section('page_title', app()->getLocale() === 'ar' ? 'الاستشارات' : 'Consultations')

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $title = $isRtl ? 'طلبات الاستشارة' : 'Consultation requests';
    $subtitle = $isRtl
        ? 'الدفع على حسابات المنصة، مراجعة الإدارة، ثم تحديد الموعد.'
        : 'Pay via platform accounts, admin verifies, then the session is scheduled.';
    $browseUrl = Route::has('public.instructors.index')
        ? route('public.instructors.index')
        : route('dashboard');
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => $title,
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => $title, 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

<section class="st-msg-intro">
    <div>
        <h2>{{ $title }}</h2>
        <p>{{ $subtitle }}</p>
    </div>
    <a href="{{ $browseUrl }}" class="st-pill st-pill--solid">
        <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
        {{ $isRtl ? 'تصفح المعلمين' : 'Browse teachers' }}
    </a>
</section>

<section class="st-lesson-list" aria-label="{{ $title }}">
    @forelse($requests as $r)
        @php
            $instructor = $r->instructor;
            $avatar = $instructor?->avatarDisplayUrl() ?? \App\Models\User::placeholderAvatarUrl();
        @endphp
        <article class="st-lesson-card st-lesson-card--blue">
            <div class="st-lesson-card__main">
                <img class="st-lesson-card__avatar" src="{{ $avatar }}" alt="" width="48" height="48">
                <div class="st-lesson-card__copy">
                    <div class="st-lesson-card__badges">
                        <span class="st-lesson-card__badge">{{ $r->statusLabel() }}</span>
                        <span class="st-lesson-card__mins">{{ number_format((float) $r->price_amount, 2) }} {{ currency_symbol() }}</span>
                    </div>
                    <h3>{{ $instructor->name ?? ($isRtl ? 'معلم' : 'Teacher') }}</h3>
                    <p class="st-lesson-card__meta">
                        @if($r->scheduled_at)
                            <x-app-datetime :at="$r->scheduled_at" pattern="Y-m-d · g:i A" />
                        @else
                            {{ $isRtl ? 'بانتظار تحديد الموعد' : 'Awaiting schedule' }}
                        @endif
                        · {{ (int) $r->duration_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}
                    </p>
                </div>
            </div>
            <div class="st-lesson-card__foot">
                <a href="{{ route('consultations.show', $r) }}" class="st-pill st-pill--outline">
                    {{ $isRtl ? 'التفاصيل' : 'Details' }}
                </a>
            </div>
        </article>
    @empty
        <div class="st-empty-panel">
            <span class="st-empty-panel__mark" aria-hidden="true"><i class="fas fa-comments-dollar"></i></span>
            <p>{{ $isRtl ? 'لا توجد طلبات استشارة بعد.' : 'No consultation requests yet.' }}</p>
            <a href="{{ $browseUrl }}" class="st-pill st-pill--solid">{{ $isRtl ? 'تصفح المعلمين' : 'Browse teachers' }}</a>
        </div>
    @endforelse
</section>

@if(method_exists($requests, 'links') && $requests->hasPages())
    <div class="st-pager">{{ $requests->links() }}</div>
@endif
@endsection
