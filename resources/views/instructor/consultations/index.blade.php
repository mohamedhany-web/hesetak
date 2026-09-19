@extends('layouts.app')

@section('title', __('instructor.cons_title'))
@section('page_title', __('instructor.cons_title'))

@section('content')
@php
    $total = method_exists($requests, 'total') ? $requests->total() : $requests->count();
    $scheduled = collect(method_exists($requests, 'items') ? $requests->items() : $requests)
        ->filter(fn ($r) => $r->scheduled_at && $r->scheduled_at >= now())
        ->count();
    $calHref = Route::has('instructor.calendar') ? route('instructor.calendar') : null;
    $coursesHref = Route::has('instructor.courses.index') ? route('instructor.courses.index') : null;
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.cons_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.student_consultations') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.cons_title') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.cons_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            @if($calHref)
                <a href="{{ $calHref }}" class="id-btn id-btn--gold">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    {{ __('instructor.cons_my_calendar') }}
                </a>
            @endif
            @if($coursesHref && instructor_ui('show_courses', false))
                <a href="{{ $coursesHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-book" aria-hidden="true"></i>
                    {{ __('instructor.courses') }}
                </a>
            @endif
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(2,minmax(0,1fr))" aria-label="{{ __('instructor.cons_title') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-comments-dollar"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.cons_title') }}</span>
                <span class="id-kpi__value">{{ number_format($total) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.cons_when') }}</span>
                <span class="id-kpi__value">{{ number_format($scheduled) }}</span>
            </span>
        </article>
    </section>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.cons_title') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.cons_title') }}</h2>
        </header>

        <div class="id-table-wrap">
            <table class="id-table">
                <thead>
                    <tr>
                        <th>{{ __('instructor.cons_student') }}</th>
                        <th>{{ __('instructor.cons_amount') }}</th>
                        <th>{{ __('instructor.cons_status') }}</th>
                        <th>{{ __('instructor.cons_when') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $r)
                        <tr>
                            <td><strong>{{ $r->student->name ?? '—' }}</strong></td>
                            <td class="tabular-nums">
                                <span class="muted">{{ number_format($r->price_amount, 2) }} {{ currency_symbol() }}</span>
                            </td>
                            <td><span class="id-chip id-chip--muted">{{ $r->statusLabel() }}</span></td>
                            <td class="tabular-nums">
                                @if($r->scheduled_at)
                                    <x-app-datetime :at="$r->scheduled_at" pattern="Y-m-d H:i" />
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td class="id-table__end">
                                <a href="{{ route('instructor.consultations.show', $r) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">
                                    {{ __('instructor.cons_details') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-comments-dollar"></i></span>
                                    <p>{{ __('instructor.cons_empty') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($requests, 'links') && $requests->hasPages())
            <div class="id-pager">{{ $requests->links() }}</div>
        @endif
    </section>
</div>
@endsection
