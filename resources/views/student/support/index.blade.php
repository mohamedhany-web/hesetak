@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_support'))

@section('content')
@php
    $locale = app()->getLocale();
    $tickets = $tickets ?? collect();
    $inquiryCategories = $inquiryCategories ?? collect();
    $openCount = $tickets->filter(fn ($t) => ! in_array($t->status, ['closed', 'resolved'], true))->count();
    $priorityLabel = fn ($p) => match ($p) {
        'low' => __('student_timeline.support_priority_low'),
        'high' => __('student_timeline.support_priority_high'),
        'urgent' => __('student_timeline.support_priority_urgent'),
        default => __('student_timeline.support_priority_normal'),
    };
    $statusLabel = fn ($s) => match ($s) {
        'open' => __('student_timeline.support_status_open'),
        'in_progress', 'pending' => __('student_timeline.support_status_progress'),
        'resolved' => __('student_timeline.support_status_resolved'),
        'closed' => __('student_timeline.support_status_closed'),
        default => $s,
    };
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_support'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_support'), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

<section class="st-join-hero" aria-label="{{ __('student_timeline.nav_support') }}">
    <div class="st-join-hero__copy">
        <p class="st-join-hero__kicker">{{ __('student_timeline.support_kicker') }}</p>
        <h2 class="st-join-hero__title">{{ __('student_timeline.support_title') }}</h2>
        <p class="st-join-hero__meta">{{ __('student_timeline.support_lead') }}</p>
    </div>
</section>

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.nav_support') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.support_tickets') }}</p>
        <p class="st-stat-card__value">{{ method_exists($tickets, 'total') ? $tickets->total() : $tickets->count() }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.support_open') }}</p>
        <p class="st-stat-card__value">{{ $openCount }}</p>
    </article>
</section>

<div class="st-support-grid">
    <section class="st-order-panel" aria-label="{{ __('student_timeline.support_new') }}">
        <div class="st-order-panel__head">
            <h2>{{ __('student_timeline.support_new') }}</h2>
        </div>
        <div class="st-order-panel__body">
            @if($inquiryCategories->isEmpty())
                <div class="st-flash st-flash--err" style="margin:0">{{ __('student_timeline.support_no_categories') }}</div>
            @else
                <form action="{{ route('student.support.store') }}" method="POST" class="st-support-form">
                    @csrf
                    <label class="st-field st-field--full">
                        <span>{{ __('student_timeline.support_category') }}</span>
                        <select name="support_inquiry_category_id" required>
                            <option value="" disabled {{ old('support_inquiry_category_id') ? '' : 'selected' }}>{{ __('student_timeline.support_category_pick') }}</option>
                            @foreach($inquiryCategories as $cat)
                                <option value="{{ $cat->id }}" @selected((string) old('support_inquiry_category_id') === (string) $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('support_inquiry_category_id')<small class="st-field__err">{{ $message }}</small>@enderror
                    </label>
                    <label class="st-field st-field--full">
                        <span>{{ __('student_timeline.support_subject') }}</span>
                        <input type="text" name="subject" value="{{ old('subject') }}" required>
                        @error('subject')<small class="st-field__err">{{ $message }}</small>@enderror
                    </label>
                    <label class="st-field st-field--full">
                        <span>{{ __('student_timeline.support_priority') }}</span>
                        <select name="priority">
                            <option value="normal">{{ __('student_timeline.support_priority_normal') }}</option>
                            <option value="low">{{ __('student_timeline.support_priority_low') }}</option>
                            <option value="high">{{ __('student_timeline.support_priority_high') }}</option>
                            <option value="urgent">{{ __('student_timeline.support_priority_urgent') }}</option>
                        </select>
                    </label>
                    <label class="st-field st-field--full">
                        <span>{{ __('student_timeline.support_message') }}</span>
                        <textarea name="message" rows="6" required>{{ old('message') }}</textarea>
                        @error('message')<small class="st-field__err">{{ $message }}</small>@enderror
                    </label>
                    <button type="submit" class="st-pill st-pill--solid st-pill--lg" style="width:100%;justify-content:center">
                        {{ __('student_timeline.support_submit') }}
                    </button>
                </form>
            @endif
        </div>
    </section>

    <section class="st-order-panel" aria-label="{{ __('student_timeline.support_my_tickets') }}">
        <div class="st-order-panel__head">
            <h2>{{ __('student_timeline.support_my_tickets') }}</h2>
        </div>
        <div class="st-order-panel__body" style="padding:12px">
            @forelse($tickets as $i => $ticket)
                @php
                    $tones = ['blue', 'pink', 'orange', 'purple'];
                    $tone = $tones[$i % count($tones)];
                @endphp
                <article class="st-order-card st-order-card--{{ $tone }}" style="margin-bottom:10px">
                    <div class="st-order-card__main">
                        <div class="st-order-card__copy">
                            <div class="st-order-card__badges">
                                <span class="st-order-card__badge">{{ $statusLabel($ticket->status) }}</span>
                                <span class="st-order-card__badge is-pending">{{ $priorityLabel($ticket->priority) }}</span>
                                <span class="st-order-card__when">{{ optional($ticket->last_reply_at ?? $ticket->updated_at)->format('Y-m-d H:i') }}</span>
                            </div>
                            <h3>{{ $ticket->subject }}</h3>
                            <p class="st-order-card__meta">{{ $ticket->inquiryCategory->name ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="st-order-card__foot">
                        <a href="{{ route('student.support.show', $ticket) }}" class="st-pill st-pill--solid">{{ __('student_timeline.support_view') }}</a>
                    </div>
                </article>
            @empty
                <div class="st-empty-panel" style="box-shadow:none;border-style:dashed">
                    <h3>{{ __('student_timeline.support_empty') }}</h3>
                    <p>{{ __('student_timeline.support_empty_hint') }}</p>
                </div>
            @endforelse

            @if(method_exists($tickets, 'hasPages') && $tickets->hasPages())
                <div class="st-pager">{{ $tickets->links() }}</div>
            @endif
        </div>
    </section>
</div>
@endsection
