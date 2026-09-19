@extends('layouts.app')

@section('title', __('instructor.agreement_details_title') . ' - ' . config('app.name'))
@section('page_title', __('instructor.agreement_details_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $currency = currency_symbol();
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

<div class="id-page">
    <section class="id-hero" aria-label="{{ $agreement->title }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.agreements_system') }}</p>
            <h2 class="id-hero__title">{{ $agreement->title }}</h2>
            <p class="id-hero__meta" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                <span>{{ __('instructor.agreement_number') }}: {{ $agreement->agreement_number ?? 'N/A' }}</span>
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ $stLabel }}</span>
            </p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.agreements.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.total_payments') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-sack-dollar"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total_earned') }}</span>
                <span class="id-kpi__value" style="font-size:1.15rem">{{ number_format($stats['total_earned'], 2) }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ $currency }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.pending') }}</span>
                <span class="id-kpi__value" style="font-size:1.15rem">{{ number_format($stats['pending_amount'], 2) }}</span>
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
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.paid') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['paid_payments']) }}</span>
            </span>
        </article>
    </section>

    <div class="id-cal-grid">
        <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
            <section class="id-panel">
                <header class="id-panel__head">
                    <h2>{{ __('instructor.agreement_info') }}</h2>
                </header>
                <div class="id-meta">
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-tag" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.agreement_type_label') }}</span>
                        <strong>
                            @if(($agreement->billing_type ?? '') === 'course_percentage')
                                {{ __('instructor.course_percentage_type') }}
                                @if($agreement->advancedCourse)
                                    <span class="id-field__hint" style="display:block;margin-top:2px">{{ $agreement->advancedCourse->title }}</span>
                                @endif
                            @elseif($agreement->type == 'course_price')
                                {{ __('instructor.course_price_full') }}
                            @elseif($agreement->type == 'hourly_rate')
                                {{ __('instructor.hourly_rate_recorded') }}
                            @elseif($agreement->type == 'consultation_session')
                                {{ __('instructor.consultations_type') }}
                            @else
                                {{ __('instructor.monthly_salary') }}
                            @endif
                        </strong>
                    </div>
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-percent" aria-hidden="true"></i></span>
                        <span>{{ (($agreement->billing_type ?? '') === 'course_percentage') ? __('instructor.instructor_share_pct') : __('instructor.rate') }}</span>
                        <strong class="tabular-nums">
                            @if(($agreement->billing_type ?? '') === 'course_percentage')
                                {{ number_format($agreement->course_percentage ?? 0, 2) }}%
                            @else
                                {{ number_format($agreement->rate, 2) }} {{ $currency }}
                            @endif
                        </strong>
                    </div>
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-info" aria-hidden="true"></i></span>
                        <span>{{ __('common.status') }}</span>
                        <strong><span class="id-chip {{ $stChip }}">{{ $stLabel }}</span></strong>
                    </div>
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-calendar" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.start_date') }}</span>
                        <strong>{{ $agreement->start_date ? $agreement->start_date->format('Y-m-d') : '-' }}</strong>
                    </div>
                    @if($agreement->end_date)
                        <div class="id-meta__row">
                            <span class="id-meta__ico"><i class="fas fa-calendar-check" aria-hidden="true"></i></span>
                            <span>{{ __('instructor.end_date') }}</span>
                            <strong>{{ $agreement->end_date->format('Y-m-d') }}</strong>
                        </div>
                    @endif
                </div>

                @if($agreement->description)
                    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #EEF2F7">
                        <p class="id-field__hint" style="margin-bottom:6px">{{ __('instructor.description') }}</p>
                        <p style="margin:0;font-size:14px;font-weight:600;line-height:1.7;color:#3A4A63">{{ $agreement->description }}</p>
                    </div>
                @endif
                @if($agreement->terms)
                    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #EEF2F7">
                        <p class="id-field__hint" style="margin-bottom:6px">{{ __('instructor.contract_terms') }}</p>
                        <div style="font-size:14px;font-weight:600;line-height:1.7;color:#3A4A63;white-space:pre-line">{{ $agreement->terms }}</div>
                    </div>
                @endif
                @if($agreement->notes)
                    <div class="id-alert id-alert--info" style="margin-top:16px">
                        <div>
                            <strong style="display:block;margin-bottom:4px">{{ __('instructor.notes') }}</strong>
                            <div style="white-space:pre-line;font-weight:600">{{ $agreement->notes }}</div>
                        </div>
                    </div>
                @endif
            </section>

            @if(($agreement->billing_type ?? '') === 'course_percentage')
                @php $activationPayments = $agreement->payments->where('type', 'course_activation'); @endphp
                <section class="id-panel id-panel--wide">
                    <header class="id-panel__head">
                        <div>
                            <h2>{{ __('instructor.student_activations_share') }}</h2>
                            <p class="id-field__hint" style="margin:4px 0 0">{{ __('instructor.student_activations_desc') }}</p>
                        </div>
                        <a href="{{ route('instructor.agreements.export-activations', $agreement) }}" class="id-btn id-btn--navy" style="min-height:34px;padding:0 12px;font-size:12px">
                            <i class="fas fa-file-excel" aria-hidden="true"></i>
                            {{ __('instructor.export_excel') }}
                        </a>
                    </header>

                    @if($activationPayments->isNotEmpty())
                        <div class="id-table-wrap">
                            <table class="id-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('common.date') }}</th>
                                        <th>{{ __('instructor.student') }}</th>
                                        <th>{{ __('instructor.purchase_amount') }}</th>
                                        <th>{{ __('instructor.my_percentage') }}</th>
                                        <th>{{ __('instructor.my_share') }}</th>
                                        <th>{{ __('common.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activationPayments as $p)
                                        <tr>
                                            <td class="tabular-nums"><span class="muted">{{ $p->created_at?->format('Y-m-d') ?? '—' }}</span></td>
                                            <td>{{ $p->enrollment?->student?->name ?? '—' }}</td>
                                            <td class="tabular-nums">{{ $p->enrollment ? number_format($p->enrollment->final_price ?? 0, 2) : '—' }}</td>
                                            <td class="tabular-nums">{{ number_format($agreement->course_percentage ?? 0, 2) }}%</td>
                                            <td class="tabular-nums"><strong>{{ number_format($p->amount, 2) }} {{ $currency }}</strong></td>
                                            <td>
                                                @if($p->status === 'paid')
                                                    <span class="id-chip id-chip--ok">{{ __('instructor.paid') }}</span>
                                                @elseif($p->status === 'approved')
                                                    <span class="id-chip id-chip--warn">{{ __('instructor.approved') }}</span>
                                                @else
                                                    <span class="id-chip id-chip--muted">{{ __('instructor.pending_review') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="padding-top:12px;border-top:1px solid #EEF2F7;margin-top:4px;font-size:13px;font-weight:800;color:#152A4A">
                            {{ __('instructor.total_earnings_from_agreement') }}:
                            <span class="tabular-nums">{{ number_format($activationPayments->sum('amount'), 2) }} {{ $currency }}</span>
                        </div>
                    @else
                        <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                            <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-user-graduate"></i></span>
                            <p>{{ __('instructor.no_activations_yet') }}</p>
                            <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.no_activations_desc') }}</p>
                        </div>
                    @endif
                </section>
            @endif

            <section class="id-panel id-panel--wide">
                <header class="id-panel__head">
                    <h2>{{ __('instructor.payments_log') }}</h2>
                </header>
                <div class="id-table-wrap">
                    <table class="id-table">
                        <thead>
                            <tr>
                                <th>{{ __('instructor.payment_number') }}</th>
                                <th>{{ __('instructor.type') }}</th>
                                <th>{{ __('instructor.amount') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('instructor.transfer_receipt') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agreement->payments as $payment)
                                @php
                                    $typeLabels = [
                                        'course_completion' => __('instructor.course_price_full'),
                                        'course_sale' => __('instructor.course_price'),
                                        'course_price' => __('instructor.course_price'),
                                        'hourly_teaching' => __('instructor.hourly_rate_recorded'),
                                        'lecture_hour' => __('instructor.hourly_rate_recorded'),
                                        'hourly_rate' => __('instructor.hourly_rate'),
                                        'monthly_salary' => __('instructor.monthly_salary'),
                                        'consultation_session' => __('instructor.consultations_type'),
                                        'bonus' => __('instructor.bonus'),
                                        'other' => __('instructor.other'),
                                        'course_activation' => __('instructor.activation_share_type'),
                                    ];
                                    $typeLabel = $typeLabels[$payment->type] ?? ($payment->type ?? __('instructor.not_specified'));
                                    $pChip = match ($payment->status) {
                                        'paid' => 'id-chip--ok',
                                        'approved' => 'id-chip--warn',
                                        default => 'id-chip--muted',
                                    };
                                    $pLabel = match ($payment->status) {
                                        'paid' => __('instructor.received'),
                                        'approved' => __('instructor.approved'),
                                        default => __('instructor.pending_review'),
                                    };
                                @endphp
                                <tr>
                                    <td><strong>{{ $payment->payment_number ?? 'N/A' }}</strong></td>
                                    <td>
                                        <span class="id-chip id-chip--muted">{{ $typeLabel }}</span>
                                        @if($payment->type === 'course_activation' && $payment->enrollment)
                                            <div class="muted" style="font-size:12px;margin-top:4px">
                                                {{ __('instructor.student') }}: {{ $payment->enrollment->student->name ?? '—' }}
                                            </div>
                                            <div class="muted" style="font-size:12px">
                                                {{ __('instructor.activation_amount_share', [
                                                    'price' => number_format($payment->enrollment->final_price ?? 0, 2),
                                                    'share' => number_format($payment->amount, 2),
                                                ]) }}
                                            </div>
                                        @endif
                                        @if($payment->course)
                                            <div class="muted" style="font-size:12px;margin-top:2px">{{ $payment->course->title ?? '' }}</div>
                                        @endif
                                        @if($payment->lecture)
                                            <div class="muted" style="font-size:12px;margin-top:2px">{{ $payment->lecture->title ?? '' }}</div>
                                        @endif
                                        @if($payment->hours_count)
                                            <div class="muted" style="font-size:12px;margin-top:2px">{{ $payment->hours_count }} {{ __('instructor.hour') }}</div>
                                        @endif
                                    </td>
                                    <td class="tabular-nums"><strong>{{ number_format($payment->amount, 2) }} {{ $currency }}</strong></td>
                                    <td>
                                        <span class="id-chip {{ $pChip }}">{{ $pLabel }}</span>
                                        @if($payment->status == 'paid')
                                            <div class="id-field__hint" style="margin-top:4px;color:#047857">{{ __('instructor.amount_transferred_info') }}</div>
                                        @endif
                                    </td>
                                    <td class="tabular-nums"><span class="muted">{{ $payment->created_at->format('Y-m-d') }}</span></td>
                                    <td>
                                        @if($payment->status == 'paid' && $payment->transfer_receipt_path)
                                            <a href="{{ storage_asset($payment->transfer_receipt_path) }}" target="_blank" rel="noopener" class="id-btn id-btn--outline" style="min-height:28px;padding:0 10px;font-size:11px">
                                                <i class="fas fa-receipt" aria-hidden="true"></i>
                                                {{ __('instructor.download_receipt') }}
                                            </a>
                                        @else
                                            <span class="muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                                            <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                                            <p>{{ __('instructor.no_payments') }}</p>
                                            <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.no_payments_yet') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside>
            <section class="id-panel">
                <header class="id-panel__head">
                    <h2>{{ __('instructor.quick_tips') }}</h2>
                </header>
                <ul style="margin:0;padding-inline-start:1.1rem;font-size:13px;font-weight:600;color:#6B7A93;line-height:1.8">
                    <li>{{ __('instructor.tips_follow_payments') }}</li>
                    <li>{{ __('instructor.tips_pending') }}</li>
                    <li>{{ __('instructor.tips_completed') }}</li>
                </ul>
            </section>
        </aside>
    </div>
</div>
@endsection
