@extends('layouts.app')

@section('title', __('instructor.withdrawal_requests') . ' - ' . config('app.name'))
@section('page_title', __('instructor.withdrawal_requests'))

@section('content')
@php
    $currency = currency_symbol();
    $canWithdraw = ($stats['available_amount'] ?? 0) > 0;
    $transferHref = Route::has('instructor.transfer-account.index')
        ? route('instructor.transfer-account.index')
        : null;
    $agreementsHref = Route::has('instructor.agreements.index')
        ? route('instructor.agreements.index')
        : null;
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.withdrawal_requests') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.withdraw_finances') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.withdrawal_requests') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.withdrawals_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            @if($canWithdraw)
                <a href="{{ route('instructor.withdrawals.create') }}" class="id-btn id-btn--gold">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    {{ __('instructor.new_withdrawal_request') }}
                </a>
            @endif
            @if($transferHref)
                <a href="{{ $transferHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-university" aria-hidden="true"></i>
                    {{ __('instructor.transfer_account') }}
                </a>
            @endif
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.available_amount') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-sack-dollar"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total_earned') }}</span>
                <span class="id-kpi__value" style="font-size:1.15rem">{{ number_format($stats['total_earned'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-arrow-down"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total_withdrawn') }}</span>
                <span class="id-kpi__value" style="font-size:1.15rem">{{ number_format($stats['total_withdrawn'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-clock"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.pending_withdrawals') }}</span>
                <span class="id-kpi__value" style="font-size:1.15rem">{{ number_format($stats['pending_withdrawals'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-wallet"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.available_amount') }}</span>
                <span class="id-kpi__value" style="font-size:1.15rem">{{ number_format($stats['available_amount'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
    </section>

    @unless($canWithdraw)
        <div class="id-alert id-alert--info" role="note">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <span>{{ __('instructor.no_available_amount') }} {{ __('instructor.no_available_amount_desc') }}</span>
        </div>
    @endunless

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.withdrawal_requests') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.withdrawal_requests') }}</h2>
            @if(method_exists($withdrawals, 'total') && $withdrawals->total() > 0)
                <span class="id-panel__badge">{{ number_format($withdrawals->total()) }}</span>
            @endif
        </header>

        <div class="id-table-wrap">
            <table class="id-table">
                <thead>
                    <tr>
                        <th>{{ __('instructor.request_number') }}</th>
                        <th>{{ __('instructor.amount') }}</th>
                        <th>{{ __('instructor.payment_method') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('instructor.request_date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $withdrawal)
                        @php
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
                                default => $withdrawal->status,
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $withdrawal->request_number ?? '#' . $withdrawal->id }}</strong></td>
                            <td class="tabular-nums"><strong>{{ number_format($withdrawal->amount, 2) }}</strong> <span class="muted">{{ $currency }}</span></td>
                            <td><span class="id-chip id-chip--muted">{{ $methodLabel }}</span></td>
                            <td><span class="id-chip {{ $stChip }}">{{ $stLabel }}</span></td>
                            <td class="tabular-nums"><span class="muted">{{ $withdrawal->created_at->format('Y-m-d H:i') }}</span></td>
                            <td class="id-table__end">
                                <div style="display:inline-flex;flex-wrap:wrap;gap:6px;justify-content:flex-end">
                                    <a href="{{ route('instructor.withdrawals.show', $withdrawal) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">
                                        {{ __('common.view') }}
                                    </a>
                                    @if(in_array($withdrawal->status, ['pending', 'approved'], true))
                                        <form action="{{ route('instructor.withdrawals.cancel', $withdrawal) }}" method="POST"
                                              onsubmit="return confirm(@json(__('instructor.confirm_cancel_withdrawal')));" style="margin:0">
                                            @csrf
                                            <button type="submit" class="id-btn id-btn--danger" style="min-height:34px;padding:0 12px;font-size:12px">
                                                {{ __('instructor.cancel') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-money-bill-wave"></i></span>
                                    <p>{{ __('instructor.no_withdrawals') }}</p>
                                    <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.no_withdrawals_description') }}</p>
                                    @if($canWithdraw)
                                        <div class="id-empty__actions">
                                            <a href="{{ route('instructor.withdrawals.create') }}" class="id-btn id-btn--navy">
                                                <i class="fas fa-plus" aria-hidden="true"></i>
                                                {{ __('instructor.new_withdrawal_request') }}
                                            </a>
                                        </div>
                                    @elseif($agreementsHref)
                                        <div class="id-empty__actions">
                                            <a href="{{ $agreementsHref }}" class="id-btn id-btn--outline">{{ __('instructor.agreements_system') }}</a>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
            <div class="id-pager">{{ $withdrawals->links() }}</div>
        @endif
    </section>
</div>
@endsection
