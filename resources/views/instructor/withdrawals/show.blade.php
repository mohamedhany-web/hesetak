@extends('layouts.app')

@section('title', __('instructor.withdrawal_requests') . ' — ' . ($withdrawal->request_number ?? '#' . $withdrawal->id))
@section('page_title', __('instructor.withdrawal_requests'))

@section('content')
@php
    $locale = app()->getLocale();
    $currency = currency_symbol();
    $methodLabel = match ($withdrawal->payment_method) {
        'bank_transfer' => __('instructor.bank_transfer'),
        'wallet' => __('instructor.wallet'),
        'cash' => __('instructor.cash'),
        default => __('instructor.other'),
    };
    $stChip = match ($withdrawal->status) {
        'completed' => 'id-chip--ok',
        'processing' => 'id-chip--muted',
        'approved' => 'id-chip--warn',
        'pending' => 'id-chip--warn',
        'rejected', 'cancelled' => 'id-chip--rose',
        default => 'id-chip--muted',
    };
    $stLabel = match ($withdrawal->status) {
        'completed' => __('instructor.completed'),
        'processing' => __('instructor.processing'),
        'approved' => __('instructor.approved'),
        'pending' => __('instructor.pending_status'),
        'rejected' => __('instructor.rejected'),
        'cancelled' => __('instructor.cancelled'),
        default => $withdrawal->status_label ?? $withdrawal->status,
    };
    $canCancel = in_array($withdrawal->status, ['pending', 'approved'], true);
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $withdrawal->request_number ?? '#' . $withdrawal->id }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.withdrawal_requests') }}</p>
            <h2 class="id-hero__title">{{ $withdrawal->request_number ?? '#' . $withdrawal->id }}</h2>
            <p class="id-hero__meta" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ $stLabel }}</span>
                <span>{{ $withdrawal->created_at->format('Y-m-d H:i') }}</span>
            </p>
        </div>
        <div class="id-hero__actions">
            @if($canCancel)
                <form action="{{ route('instructor.withdrawals.cancel', $withdrawal) }}" method="POST"
                      onsubmit="return confirm(@json(__('instructor.confirm_cancel_withdrawal')));">
                    @csrf
                    <button type="submit" class="id-btn id-btn--ghost" style="border-color:rgba(255,255,255,.45)">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        {{ __('instructor.cancel') }}
                    </button>
                </form>
            @endif
            <a href="{{ route('instructor.withdrawals.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(2,minmax(0,1fr))" aria-label="{{ __('instructor.amount') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-money-bill-wave"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.amount') }}</span>
                <span class="id-kpi__value" style="font-size:1.2rem">{{ number_format($withdrawal->amount, 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-credit-card"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.payment_method') }}</span>
                <span class="id-kpi__value" style="font-size:1.05rem">{{ $methodLabel }}</span>
            </span>
        </article>
    </section>

    <section class="id-panel">
        <header class="id-panel__head">
            <h2>{{ __('instructor.view_details') }}</h2>
            <span class="id-chip {{ $stChip }}">{{ $stLabel }}</span>
        </header>
        <div class="id-meta">
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-hashtag" aria-hidden="true"></i></span>
                <span>{{ __('instructor.request_number') }}</span>
                <strong>{{ $withdrawal->request_number ?? '#' . $withdrawal->id }}</strong>
            </div>
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-calendar" aria-hidden="true"></i></span>
                <span>{{ __('instructor.request_date') }}</span>
                <strong>{{ $withdrawal->created_at->format('Y-m-d H:i') }}</strong>
            </div>
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-money-bill" aria-hidden="true"></i></span>
                <span>{{ __('instructor.amount') }}</span>
                <strong class="tabular-nums">{{ number_format($withdrawal->amount, 2) }} {{ $currency }}</strong>
            </div>
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-wallet" aria-hidden="true"></i></span>
                <span>{{ __('instructor.payment_method') }}</span>
                <strong>{{ $methodLabel }}</strong>
            </div>
            @if($withdrawal->payment_method === 'bank_transfer')
                @if(filled($withdrawal->bank_name))
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-university" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.bank_name') }}</span>
                        <strong>{{ $withdrawal->bank_name }}</strong>
                    </div>
                @endif
                @if(filled($withdrawal->account_holder_name))
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-user" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.account_holder_name') }}</span>
                        <strong>{{ $withdrawal->account_holder_name }}</strong>
                    </div>
                @endif
                @if(filled($withdrawal->account_number))
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-hashtag" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.account_number') }}</span>
                        <strong dir="ltr">{{ $withdrawal->account_number }}</strong>
                    </div>
                @endif
                @if(filled($withdrawal->iban))
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-barcode" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.iban') }}</span>
                        <strong dir="ltr">{{ $withdrawal->iban }}</strong>
                    </div>
                @endif
            @endif
            @if(filled($withdrawal->admin_notes))
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-comment" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.admin_notes_label') }}</span>
                    <strong>{{ $withdrawal->admin_notes }}</strong>
                </div>
            @endif
        </div>

        @if(filled($withdrawal->notes))
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #EEF2F7">
                <p class="id-field__hint" style="margin-bottom:6px">{{ __('instructor.notes') }}</p>
                <p style="margin:0;font-size:14px;font-weight:600;line-height:1.7;color:#3A4A63;white-space:pre-wrap">{{ $withdrawal->notes }}</p>
            </div>
        @endif
    </section>
</div>
@endsection
