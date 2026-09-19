@extends('layouts.app')

@section('title', 'الحصص المجانية')
@section('page_title', 'الحصص المجانية')

@section('content')
@php
    $locale = app()->getLocale();
    $total = method_exists($bookings, 'total') ? $bookings->total() : $bookings->count();
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="الحصص المجانية">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.private_lessons') }}</p>
            <h2 class="id-hero__title">الحصص المجانية المحجوزة لك</h2>
            <p class="id-hero__meta">حصة مجانية لحاملي الباقة · تظهر أيضاً في جدولك</p>
        </div>
        <div class="id-hero__actions">
            @if(Route::has('instructor.one-to-one-sessions.index'))
                <a href="{{ route('instructor.one-to-one-sessions.index') }}" class="id-btn id-btn--gold">{{ __('instructor.o1o_title') }}</a>
            @endif
            @if(Route::has('instructor.calendar'))
                <a href="{{ route('instructor.calendar') }}" class="id-btn id-btn--ghost">{{ __('instructor.my_calendar') }}</a>
            @endif
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(2,minmax(0,1fr))" aria-label="إحصائيات">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-gift"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">إجمالي الحجوزات</span>
                <span class="id-kpi__value">{{ number_format($total) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">في هذه الصفحة</span>
                <span class="id-kpi__value">{{ number_format($bookings->count()) }}</span>
            </span>
        </article>
    </section>

    <section class="id-panel id-panel--wide" aria-label="الحصص المجانية">
        <header class="id-panel__head">
            <h2>قائمة الحجوزات</h2>
        </header>

        <div class="id-table-wrap">
            <table class="id-table">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الموعد</th>
                        <th>الغرض</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $b)
                        <tr>
                            <td>
                                <strong>{{ $b->name }}</strong>
                                <div class="muted" dir="ltr" style="margin-top:2px;font-size:12px">{{ $b->phone ?: ($b->email ?: '—') }}</div>
                            </td>
                            <td class="tabular-nums">
                                <x-app-datetime :at="$b->starts_at" :timezone="$b->timezone" pattern="Y-m-d · g:i A" />
                            </td>
                            <td><span class="muted">{{ $b->goalLabel() }}</span></td>
                            <td><span class="id-chip">{{ $b->status }}</span></td>
                            <td class="id-table__end">
                                <a href="{{ route('instructor.free-trial-bookings.show', $b) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-gift"></i></span>
                                    <p>لا توجد حصص مجانية معيّنة لك بعد.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bookings->hasPages())
            <div class="id-pager">{{ $bookings->links() }}</div>
        @endif
    </section>
</div>
@endsection
