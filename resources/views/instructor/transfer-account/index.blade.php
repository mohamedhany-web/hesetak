@extends('layouts.app')

@section('title', __('instructor.transfer_account') . ' - ' . config('app.name'))
@section('page_title', __('instructor.transfer_account'))

@section('content')
@php
    $fieldsFilled = collect([
        $detail->bank_name,
        $detail->account_holder_name,
        $detail->account_number,
        $detail->iban,
        $detail->branch_name,
        $detail->swift_code,
    ])->filter(fn ($v) => filled($v))->count();
    $isComplete = filled($detail->bank_name) && filled($detail->account_holder_name)
        && (filled($detail->account_number) || filled($detail->iban));
    $agreementsHref = Route::has('instructor.agreements.index')
        ? route('instructor.agreements.index')
        : null;
    $withdrawHref = Route::has('instructor.withdrawals.index')
        ? route('instructor.withdrawals.index')
        : null;
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.transfer_account') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.transfer_account_data') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.transfer_account') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.transfer_account_desc') }}</p>
        </div>
        <div class="id-hero__actions">
            @if($agreementsHref)
                <a href="{{ $agreementsHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-handshake" aria-hidden="true"></i>
                    {{ __('instructor.agreements_system') }}
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

    <section class="id-kpis" style="grid-template-columns:repeat(2,minmax(0,1fr))" aria-label="{{ __('instructor.transfer_account_data') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon {{ $isComplete ? 'id-kpi__icon--teal' : 'id-kpi__icon--gold' }}" aria-hidden="true">
                <i class="fas {{ $isComplete ? 'fa-check-circle' : 'fa-university' }}"></i>
            </span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('common.status') }}</span>
                <span class="id-kpi__value" style="font-size:1.05rem">
                    {{ $isComplete ? __('instructor.active_status') : __('instructor.draft') }}
                </span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-list-check"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.transfer_account_data') }}</span>
                <span class="id-kpi__value">{{ $fieldsFilled }}/6</span>
            </span>
        </article>
    </section>

    @if($errors->any())
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="id-alert id-alert--info" role="note">
        <i class="fas fa-info-circle" aria-hidden="true"></i>
        <span>{{ __('instructor.transfer_account_desc') }}</span>
    </div>

    <section class="id-panel" aria-label="{{ __('instructor.save_transfer_data') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.transfer_account_data') }}</h2>
            @if($isComplete)
                <span class="id-chip id-chip--ok">{{ __('instructor.active_status') }}</span>
            @else
                <span class="id-chip id-chip--warn">{{ __('instructor.draft') }}</span>
            @endif
        </header>

        <form action="{{ route('instructor.transfer-account.store') }}" method="POST" class="id-form">
            @csrf
            <div class="id-form-grid">
                <div class="id-field">
                    <label for="bank_name">{{ __('instructor.bank_name') }}</label>
                    <input type="text" name="bank_name" id="bank_name"
                           value="{{ old('bank_name', $detail->bank_name) }}"
                           class="id-input" placeholder="{{ __('instructor.placeholder_bank_example') }}">
                    @error('bank_name')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="account_holder_name">{{ __('instructor.account_holder_name') }}</label>
                    <input type="text" name="account_holder_name" id="account_holder_name"
                           value="{{ old('account_holder_name', $detail->account_holder_name) }}"
                           class="id-input" placeholder="{{ __('instructor.placeholder_name_on_card') }}">
                    @error('account_holder_name')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="account_number">{{ __('instructor.account_number') }}</label>
                    <input type="text" name="account_number" id="account_number"
                           value="{{ old('account_number', $detail->account_number) }}"
                           dir="ltr" class="id-input" placeholder="{{ __('instructor.placeholder_account_number') }}">
                    @error('account_number')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="iban">{{ __('instructor.iban') }}</label>
                    <input type="text" name="iban" id="iban"
                           value="{{ old('iban', $detail->iban) }}"
                           dir="ltr" class="id-input" placeholder="EG...">
                    @error('iban')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="branch_name">{{ __('instructor.branch_name') }}</label>
                    <input type="text" name="branch_name" id="branch_name"
                           value="{{ old('branch_name', $detail->branch_name) }}"
                           class="id-input" placeholder="{{ __('instructor.placeholder_branch_optional') }}">
                    @error('branch_name')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="swift_code">{{ __('instructor.swift_code') }}</label>
                    <input type="text" name="swift_code" id="swift_code"
                           value="{{ old('swift_code', $detail->swift_code) }}"
                           dir="ltr" class="id-input" placeholder="{{ __('instructor.placeholder_optional') }}">
                    @error('swift_code')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field id-field--span2">
                    <label for="notes">{{ __('instructor.notes') }}</label>
                    <textarea name="notes" id="notes" rows="3" class="id-input"
                              style="min-height:88px;padding-top:10px;padding-bottom:10px;resize:vertical"
                              placeholder="{{ __('instructor.placeholder_extra_transfer') }}">{{ old('notes', $detail->notes) }}</textarea>
                    @error('notes')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="id-foot-actions">
                @if($agreementsHref)
                    <a href="{{ $agreementsHref }}" class="id-btn id-btn--outline">{{ __('instructor.agreements_system') }}</a>
                @else
                    <span></span>
                @endif
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-save" aria-hidden="true"></i>
                    {{ __('instructor.save_transfer_data') }}
                </button>
            </div>
        </form>
    </section>

    @if($isComplete || filled($detail->bank_name) || filled($detail->iban) || filled($detail->account_number))
        <section class="id-panel" aria-label="{{ __('instructor.transfer_account_data') }}">
            <header class="id-panel__head">
                <h2>{{ __('instructor.view_details') }}</h2>
            </header>
            <div class="id-meta">
                @if(filled($detail->bank_name))
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-university" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.bank_name') }}</span>
                        <strong>{{ $detail->bank_name }}</strong>
                    </div>
                @endif
                @if(filled($detail->account_holder_name))
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-user" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.account_holder_name') }}</span>
                        <strong>{{ $detail->account_holder_name }}</strong>
                    </div>
                @endif
                @if(filled($detail->account_number))
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-hashtag" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.account_number') }}</span>
                        <strong dir="ltr">{{ $detail->account_number }}</strong>
                    </div>
                @endif
                @if(filled($detail->iban))
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-barcode" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.iban') }}</span>
                        <strong dir="ltr">{{ $detail->iban }}</strong>
                    </div>
                @endif
                @if(filled($detail->branch_name))
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.branch_name') }}</span>
                        <strong>{{ $detail->branch_name }}</strong>
                    </div>
                @endif
                @if(filled($detail->swift_code))
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-globe" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.swift_code') }}</span>
                        <strong dir="ltr">{{ $detail->swift_code }}</strong>
                    </div>
                @endif
            </div>
        </section>
    @endif
</div>
@endsection
