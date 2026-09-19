@extends('layouts.app')

@section('title', __('instructor.new_withdrawal_request') . ' - ' . config('app.name'))
@section('page_title', __('instructor.new_withdrawal_request'))

@section('content')
@php
    $locale = app()->getLocale();
    $currency = currency_symbol();
    $canWithdraw = ($stats['available_amount'] ?? 0) > 0;
    $showBank = old('payment_method', '') === 'bank_transfer';
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.submit_withdrawal') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.withdrawal_requests') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.submit_withdrawal') }}</h2>
            <p class="id-hero__meta">
                {{ __('instructor.available_for_withdrawal') }}:
                <strong class="tabular-nums">{{ number_format($stats['available_amount'], 2) }} {{ $currency }}</strong>
            </p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.withdrawals.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.available_amount') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-sack-dollar"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total_earned') }}</span>
                <span class="id-kpi__value" style="font-size:1.1rem">{{ number_format($stats['total_earned'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-arrow-down"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total_withdrawn') }}</span>
                <span class="id-kpi__value" style="font-size:1.1rem">{{ number_format($stats['total_withdrawn'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-clock"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.pending_withdrawals') }}</span>
                <span class="id-kpi__value" style="font-size:1.1rem">{{ number_format($stats['pending_withdrawals'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-wallet"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.available_amount') }}</span>
                <span class="id-kpi__value" style="font-size:1.1rem">{{ number_format($stats['available_amount'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
    </section>

    @if(session('error'))
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <section class="id-panel">
        @unless($canWithdraw)
            <div class="id-empty" style="border:0;background:transparent;padding:20px 8px">
                <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-wallet"></i></span>
                <p>{{ __('instructor.no_available_amount') }}</p>
                <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.no_available_amount_desc') }}</p>
                <div class="id-empty__actions">
                    <a href="{{ route('instructor.withdrawals.index') }}" class="id-btn id-btn--outline">
                        <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                        {{ __('instructor.back') }}
                    </a>
                </div>
            </div>
        @else
            <header class="id-panel__head">
                <h2>{{ __('instructor.new_withdrawal_request') }}</h2>
            </header>

            <form action="{{ route('instructor.withdrawals.store') }}" method="POST" class="id-form">
                @csrf
                <div class="id-field">
                    <label for="amount">{{ __('instructor.amount_required_egp') }} <span style="color:#B91C1C">*</span></label>
                    <input type="number" name="amount" id="amount" value="{{ old('amount') }}"
                           min="0.01" step="0.01" max="{{ $stats['available_amount'] }}" required
                           class="id-input" placeholder="0.00">
                    <p class="id-field__hint">{{ __('instructor.max_amount') }}: {{ number_format($stats['available_amount'], 2) }} {{ $currency }}</p>
                    @error('amount')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>

                <div class="id-field">
                    <label for="payment_method">{{ __('instructor.payment_receive_method') }} <span style="color:#B91C1C">*</span></label>
                    <select name="payment_method" id="payment_method" required class="id-select">
                        <option value="">{{ __('instructor.choose_payment_method') }}</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>{{ __('instructor.bank_transfer') }}</option>
                        <option value="wallet" @selected(old('payment_method') === 'wallet')>{{ __('instructor.wallet') }}</option>
                        <option value="cash" @selected(old('payment_method') === 'cash')>{{ __('instructor.cash') }}</option>
                        <option value="other" @selected(old('payment_method') === 'other')>{{ __('instructor.other') }}</option>
                    </select>
                    @error('payment_method')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>

                <div id="bank_fields" class="id-slot-card" style="{{ $showBank ? '' : 'display:none' }}">
                    <div class="id-slot-card__head">
                        <strong style="font-size:13px;font-weight:800;color:#152A4A">{{ __('instructor.bank_transfer') }}</strong>
                        @if(Route::has('instructor.transfer-account.index'))
                            <a href="{{ route('instructor.transfer-account.index') }}" class="id-field__hint" style="text-decoration:underline">{{ __('instructor.transfer_account') }}</a>
                        @endif
                    </div>
                    <div class="id-form-grid">
                        <div class="id-field">
                            <label for="bank_name">{{ __('instructor.bank_name') }}</label>
                            <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}"
                                   class="id-input" placeholder="{{ __('instructor.placeholder_bank_example') }}">
                            @error('bank_name')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field">
                            <label for="account_holder_name">{{ __('instructor.account_holder_name') }}</label>
                            <input type="text" name="account_holder_name" id="account_holder_name"
                                   value="{{ old('account_holder_name') }}" class="id-input">
                            @error('account_holder_name')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field">
                            <label for="account_number">{{ __('instructor.account_number') }}</label>
                            <input type="text" name="account_number" id="account_number"
                                   value="{{ old('account_number') }}" dir="ltr" class="id-input">
                            @error('account_number')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field">
                            <label for="iban">{{ __('instructor.iban') }} ({{ __('instructor.optional_label') }})</label>
                            <input type="text" name="iban" id="iban" value="{{ old('iban') }}"
                                   dir="ltr" class="id-input" placeholder="EG...">
                            @error('iban')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="id-field">
                    <label for="notes">{{ __('instructor.notes') }} ({{ __('instructor.optional_label') }})</label>
                    <textarea name="notes" id="notes" rows="3" class="id-input"
                              style="min-height:80px;padding-top:10px;padding-bottom:10px;resize:vertical"
                              placeholder="{{ __('instructor.placeholder_extra_transfer') }}">{{ old('notes') }}</textarea>
                    @error('notes')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>

                <div class="id-foot-actions">
                    <a href="{{ route('instructor.withdrawals.index') }}" class="id-btn id-btn--outline">{{ __('common.cancel') }}</a>
                    <button type="submit" class="id-btn id-btn--navy">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        {{ __('instructor.submit_request_btn') }}
                    </button>
                </div>
            </form>
        @endunless
    </section>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('payment_method')?.addEventListener('change', function () {
    var bankFields = document.getElementById('bank_fields');
    if (bankFields) bankFields.style.display = this.value === 'bank_transfer' ? '' : 'none';
});
</script>
@endpush
