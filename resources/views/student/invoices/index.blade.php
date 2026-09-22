@extends('layouts.student-timeline')

@section('title', __('student.invoices_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $invoices = $invoices ?? collect();
    $paidCount = $invoices->where('status', 'paid')->count();
    $pendingCount = $invoices->filter(fn ($inv) => $inv->status !== 'paid')->count();
    $tones = ['blue', 'pink', 'orange', 'purple'];
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_invoices'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_invoices'), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

<section class="st-join-hero {{ $invoices->count() ? '' : 'st-join-hero--muted' }}" aria-label="{{ __('student.invoices_title') }}">
    <div class="st-join-hero__copy">
        <p class="st-join-hero__kicker">{{ __('student_timeline.nav_invoices') }}</p>
        <h2 class="st-join-hero__title">
            @if($invoices->count())
                {{ __('student.invoices_title') }}
            @else
                {{ __('student.no_invoices') }}
            @endif
        </h2>
        <p class="st-join-hero__meta">{{ __('student.invoices_subtitle') }}</p>
    </div>
    <div class="st-join-hero__actions">
        @if(Route::has('student.wallet.index'))
            <a href="{{ route('student.wallet.index') }}" class="st-pill st-pill--solid">{{ __('student_timeline.nav_wallet') }}</a>
        @endif
        @if(Route::has('orders.index'))
            <a href="{{ route('orders.index') }}" class="st-pill st-pill--outline">{{ __('student_timeline.nav_orders') }}</a>
        @endif
    </div>
</section>

<section class="st-stats st-stats--classes" aria-label="{{ __('student.invoices_title') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.filter_all') }}</p>
        <p class="st-stat-card__value">{{ method_exists($invoices, 'total') ? $invoices->total() : $invoices->count() }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.paid_status') }}</p>
        <p class="st-stat-card__value">{{ $paidCount }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.pending_status_label') }}</p>
        <p class="st-stat-card__value">{{ $pendingCount }}</p>
    </article>
</section>

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student.invoices_title') }}</h2>
        <p>{{ __('student.invoices_subtitle') }}</p>
    </div>
</section>

@if($invoices->count() > 0)
    <div class="st-order-list" aria-label="{{ __('student.invoices_title') }}">
        @foreach($invoices as $i => $invoice)
            @php $tone = $tones[$i % count($tones)]; @endphp
            <article class="st-order-card st-order-card--{{ $tone }}">
                <div class="st-order-card__main">
                    <div class="st-order-card__copy">
                        <div class="st-order-card__badges">
                            <span class="st-order-card__badge {{ $invoice->status === 'paid' ? 'is-approved' : 'is-pending' }}">
                                {{ $invoice->status === 'paid' ? __('student.paid_status') : __('student.pending_status_label') }}
                            </span>
                            @if($invoice->created_at)
                                <span class="st-order-card__when">{{ $invoice->created_at->format('Y-m-d') }}</span>
                            @endif
                        </div>
                        <h3>{{ __('student.invoice_number') }}: {{ $invoice->invoice_number }}</h3>
                        <p class="st-order-card__meta">{{ currency_symbol() }}</p>
                    </div>
                    <div class="st-order-card__amount">
                        <strong class="tabular-nums">{{ format_money($invoice->total_amount) }}</strong>
                    </div>
                </div>
                <div class="st-order-card__foot">
                    <a href="{{ route('student.invoices.show', $invoice) }}" class="st-pill st-pill--solid">{{ __('common.view') }}</a>
                </div>
            </article>
        @endforeach
    </div>
    @if(method_exists($invoices, 'hasPages') && $invoices->hasPages())
        <div class="st-pager">{{ $invoices->links() }}</div>
    @endif
@else
    <div class="st-empty-panel">
        <h3>{{ __('student.no_invoices') }}</h3>
        <p>{{ __('student_timeline.invoices_empty_hint') }}</p>
    </div>
@endif
@endsection
