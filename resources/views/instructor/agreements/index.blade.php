@extends('layouts.app')

@section('title', __('instructor.agreements_system') . ' - ' . config('app.name'))
@section('page_title', __('instructor.agreements_system'))

@section('content')
@php
    $currency = currency_symbol();
    $transferHref = Route::has('instructor.transfer-account.index')
        ? route('instructor.transfer-account.index')
        : null;
    $withdrawHref = Route::has('instructor.withdrawals.index')
        ? route('instructor.withdrawals.index')
        : null;
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.agreements_system') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.agreements_system') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.agreements_system') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.agreements_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            @if($transferHref)
                <a href="{{ $transferHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-university" aria-hidden="true"></i>
                    {{ __('instructor.transfer_account') }}
                </a>
            @endif
            @if($withdrawHref)
                <a href="{{ $withdrawHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-money-bill-wave" aria-hidden="true"></i>
                    {{ __('instructor.withdrawal_requests') }}
                </a>
            @endif
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(3,minmax(0,1fr))" aria-label="{{ __('instructor.total_earned') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-sack-dollar"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total_earned') }}</span>
                <span class="id-kpi__value" style="font-size:1.2rem">{{ number_format($stats['total_earned'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.pending') }}</span>
                <span class="id-kpi__value" style="font-size:1.2rem">{{ number_format($stats['pending_amount'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-receipt"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total_payments') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total_payments']) }}</span>
            </span>
        </article>
    </section>

    @if($activeAgreement)
        <section class="id-panel" style="border-color:rgba(4,120,87,.22);background:#F3FBF7" aria-label="{{ __('instructor.active_status') }}">
            <header class="id-panel__head">
                <h2 style="display:flex;flex-wrap:wrap;align-items:center;gap:8px">
                    <span class="id-chip id-chip--ok">{{ __('instructor.active_status') }}</span>
                    {{ $activeAgreement->title }}
                </h2>
                <a href="{{ route('instructor.agreements.show', $activeAgreement) }}" class="id-btn id-btn--navy" style="min-height:34px;padding:0 12px;font-size:12px">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                    {{ __('instructor.view_details') }}
                </a>
            </header>
            <div class="id-meta">
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-hashtag" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.agreement_number') }}</span>
                    <strong>{{ $activeAgreement->agreement_number }}</strong>
                </div>
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-tag" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.type') }}</span>
                    <strong>
                        @if($activeAgreement->type == 'course_price') {{ __('instructor.course_price') }}
                        @elseif($activeAgreement->type == 'hourly_rate') {{ __('instructor.hourly_rate') }}
                        @else {{ __('instructor.monthly_salary') }}
                        @endif
                    </strong>
                </div>
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-percent" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.rate') }}</span>
                    <strong class="tabular-nums">{{ number_format($activeAgreement->rate, 2) }} {{ $currency }}</strong>
                </div>
            </div>
        </section>
    @endif

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.all_agreements') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.all_agreements') }}</h2>
            @if($agreements->count() > 0)
                <span class="id-panel__badge">{{ number_format($agreements->count()) }}</span>
            @endif
        </header>

        <div class="id-table-wrap">
            <table class="id-table">
                <thead>
                    <tr>
                        <th>{{ __('instructor.agreement_number') }}</th>
                        <th>{{ __('instructor.title') }}</th>
                        <th>{{ __('instructor.type') }}</th>
                        <th>{{ __('instructor.rate') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('instructor.start_date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agreements as $agreement)
                        @php
                            $typeLabel = match ($agreement->type) {
                                'course_price' => __('instructor.course_price'),
                                'hourly_rate' => __('instructor.hourly_rate'),
                                default => __('instructor.monthly_salary'),
                            };
                            $stChip = match ($agreement->status) {
                                'active' => 'id-chip--ok',
                                'draft' => 'id-chip--muted',
                                'suspended' => 'id-chip--warn',
                                'terminated' => 'id-chip--rose',
                                default => 'id-chip--muted',
                            };
                            $stLabel = match ($agreement->status) {
                                'active' => __('instructor.active_status'),
                                'draft' => __('instructor.draft'),
                                'suspended' => __('instructor.suspended'),
                                'terminated' => __('instructor.terminated'),
                                default => __('instructor.agreement_completed'),
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $agreement->agreement_number }}</strong></td>
                            <td>{{ $agreement->title }}</td>
                            <td><span class="id-chip id-chip--muted">{{ $typeLabel }}</span></td>
                            <td class="tabular-nums">{{ number_format($agreement->rate, 2) }} <span class="muted">{{ $currency }}</span></td>
                            <td><span class="id-chip {{ $stChip }}">{{ $stLabel }}</span></td>
                            <td class="tabular-nums"><span class="muted">{{ $agreement->start_date->format('Y-m-d') }}</span></td>
                            <td class="id-table__end">
                                <a href="{{ route('instructor.agreements.show', $agreement) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">
                                    {{ __('common.view') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-handshake"></i></span>
                                    <p>{{ __('instructor.no_agreements') }}</p>
                                    <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.no_agreements_description') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
