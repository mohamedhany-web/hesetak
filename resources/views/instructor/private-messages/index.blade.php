@extends('layouts.app')

@section('title', __('instructor.pm_title'))
@section('page_title', __('instructor.pm_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $avatarFallback = \App\Models\User::placeholderAvatarUrl();
    $threadCount = method_exists($threads, 'total') ? $threads->total() : $threads->count();
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.pm_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.student_messages') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.pm_title') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.pm_subtitle') }}</p>
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(2,minmax(0,1fr))" aria-label="{{ __('instructor.pm_title') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-comments"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.pm_all_messages') }}</span>
                <span class="id-kpi__value">{{ number_format($threadCount) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-user-graduate"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.private_lessons') }}</span>
                <span class="id-kpi__value">1:1</span>
            </span>
        </article>
    </section>

    <section class="id-panel">
        <form method="GET" class="id-form" style="gap:12px">
            <div class="id-form-grid" style="align-items:end">
                <div class="id-field id-field--span2">
                    <label for="pm-q">{{ __('common.search') }}</label>
                    <input type="search" name="q" id="pm-q" value="{{ $searchQuery }}"
                           placeholder="{{ __('instructor.pm_search_placeholder') }}"
                           class="id-input">
                </div>
            </div>
            <div>
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    {{ __('common.search') }}
                </button>
            </div>
        </form>
    </section>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.pm_title') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.pm_all_messages') }}</h2>
        </header>

        <div class="id-list">
            @forelse($threads as $thread)
                @php
                    $student = $thread->student;
                    $name = $student?->name ?: __('instructor.pm_student_fallback');
                    $preview = optional($thread->messages->first())->body;
                    $avatar = ($student && $student->profile_image) ? $student->profile_image_url : $avatarFallback;
                @endphp
                <a href="{{ route('instructor.private-messages.show', $thread) }}" class="id-list__row" style="text-decoration:none;color:inherit">
                    <img src="{{ $avatar }}" alt="" class="id-thread-avatar" width="44" height="44">
                    <div class="id-list__body">
                        <div class="id-list__title">{{ $name }}</div>
                        <div class="id-list__meta">
                            {{ \Illuminate\Support\Str::limit($preview ?: __('instructor.pm_start_chat'), 80) }}
                        </div>
                    </div>
                    <span class="id-thread-time">{{ $thread->last_message_at?->diffForHumans() }}</span>
                    <i class="fas fa-chevron-{{ $locale === 'ar' ? 'left' : 'right' }} id-act__chev" aria-hidden="true"></i>
                </a>
            @empty
                <div class="id-empty">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-comments"></i></span>
                    <p>{{ __('instructor.pm_empty') }}</p>
                </div>
            @endforelse
        </div>

        @if(method_exists($threads, 'hasPages') && $threads->hasPages())
            <div class="id-pager">{{ $threads->links() }}</div>
        @endif
    </section>
</div>
@endsection
