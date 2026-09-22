@extends('layouts.student-timeline')

@section('title', __('student.wallet_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $wallet = $wallet ?? null;
    $transactions = $transactions ?? collect();
    $balance = (float) ($wallet->balance ?? 0);
    $packagesUrl = Route::has('public.service-packages.index')
        ? route('public.service-packages.index')
        : (Route::has('public.pricing') ? route('public.pricing') : route('dashboard'));
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_wallet'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_wallet'), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

<section class="st-join-hero" aria-label="{{ __('student.wallet_title') }}">
    <div class="st-join-hero__copy">
        <p class="st-join-hero__kicker">{{ __('student.current_balance') }}</p>
        <h2 class="st-join-hero__title tabular-nums">{{ format_money($balance) }}</h2>
        <p class="st-join-hero__meta">{{ __('student.wallet_title') }} · {{ currency_symbol() }}</p>
    </div>
    <div class="st-join-hero__actions">
        <a href="{{ $packagesUrl }}" class="st-pill st-pill--solid st-pill--lg">
            <i class="fas fa-plus" aria-hidden="true"></i>
            {{ __('student_timeline.wallet_topup') }}
        </a>
        @if(Route::has('orders.index'))
            <a href="{{ route('orders.index') }}" class="st-pill st-pill--outline">{{ __('student_timeline.nav_orders') }}</a>
        @endif
    </div>
</section>

<section class="st-stats st-stats--classes" aria-label="{{ __('student.wallet_title') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.current_balance') }}</p>
        <p class="st-stat-card__value tabular-nums" style="font-size:1.35rem">{{ format_money($balance) }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.transactions_log') }}</p>
        <p class="st-stat-card__value">{{ method_exists($transactions, 'total') ? $transactions->total() : $transactions->count() }}</p>
    </article>
</section>

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student.transactions_log') }}</h2>
        <p>{{ __('student_timeline.wallet_tx_hint') }}</p>
    </div>
</section>

@if($transactions->count() > 0)
    <div class="st-order-list" aria-label="{{ __('student.transactions_log') }}">
        @foreach($transactions as $i => $transaction)
            @php
                $isIn = in_array($transaction->type, ['deposit', 'credit', 'إيداع', 'refund'], true);
                $tones = ['blue', 'pink', 'orange', 'purple'];
                $tone = $tones[$i % count($tones)];
            @endphp
            <article class="st-order-card st-order-card--{{ $tone }}">
                <div class="st-order-card__main">
                    <div class="st-order-card__copy">
                        <div class="st-order-card__badges">
                            <span class="st-order-card__badge {{ $isIn ? 'is-approved' : 'is-rejected' }}">
                                {{ $isIn ? __('student.deposit_label') : __('student_timeline.wallet_debit') }}
                            </span>
                            <span class="st-order-card__when">{{ optional($transaction->created_at)->format('Y-m-d H:i') }}</span>
                        </div>
                        <h3>{{ $transaction->description ?? __('student.transaction_default') }}</h3>
                    </div>
                    <div class="st-order-card__amount">
                        <strong class="tabular-nums" style="color:{{ $isIn ? '#047857' : '#b91c1c' }}">
                            {{ $isIn ? '+' : '−' }}{{ format_money(abs((float) ($transaction->amount ?? 0))) }}
                        </strong>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
    @if(method_exists($transactions, 'hasPages') && $transactions->hasPages())
        <div class="st-pager">{{ $transactions->links() }}</div>
    @endif
@else
    <div class="st-empty-panel">
        <h3>{{ __('student.no_transactions') }}</h3>
        <p>{{ __('student_timeline.wallet_empty_hint') }}</p>
        <div class="st-biz-banner__actions">
            <a href="{{ $packagesUrl }}" class="st-pill st-pill--solid">{{ __('student_timeline.wallet_topup') }}</a>
        </div>
    </div>
@endif
@endsection
