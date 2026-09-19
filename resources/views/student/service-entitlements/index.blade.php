@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_progress'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $entitlements = $entitlements ?? collect();
    $totals = $totals ?? ['individual' => 0, 'collective' => 0, 'private' => 0, 'global' => 0];
    $packages = $packages ?? collect();
    $totalLeft = (int) ($totals['individual'] + $totals['collective'] + $totals['private'] + $totals['global']);
    $tones = ['blue', 'pink', 'orange', 'purple'];
    $rechargeUrl = Route::has('public.service-packages.index')
        ? route('public.service-packages.index')
        : route('dashboard');
    $bookUrl = Route::has('student.learn.index')
        ? route('student.learn.index')
        : (Route::has('public.groups') ? route('public.groups') : route('dashboard'));
    $statusLabels = [
        'active' => __('student_timeline.ent_status_active'),
        'expired' => __('student_timeline.ent_status_expired'),
        'cancelled' => __('student_timeline.ent_status_cancelled'),
    ];
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_progress'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_progress'), 'url' => null],
    ],
    'toolbarView' => 'student.service-entitlements._toolbar',
    'toolbarData' => [
        'totalLeft' => $totalLeft,
        'rechargeUrl' => $rechargeUrl,
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

@if($totalLeft > 0)
    <section class="st-join-hero" aria-label="{{ __('student_timeline.nav_progress') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">{{ __('student_timeline.credits_ready') }}</p>
            <h2 class="st-join-hero__title">{{ __('student_timeline.credits_total', ['count' => $totalLeft]) }}</h2>
            <p class="st-join-hero__meta">{{ __('student_timeline.credits_use_hint') }}</p>
        </div>
        <div class="st-join-hero__actions">
            <a href="{{ $bookUrl }}" class="st-pill st-pill--solid st-pill--lg">{{ __('student_timeline.book_with_credits') }}</a>
            <a href="{{ $rechargeUrl }}" class="st-pill st-pill--outline">{{ __('student_timeline.recharge_package') }}</a>
        </div>
    </section>
@else
    <section class="st-join-hero st-join-hero--muted" aria-label="{{ __('student_timeline.nav_progress') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">{{ __('student_timeline.credits_empty_kicker') }}</p>
            <h2 class="st-join-hero__title">{{ __('student_timeline.credits_empty_title') }}</h2>
            <p class="st-join-hero__meta">{{ __('student_timeline.credits_empty_hint') }}</p>
        </div>
        <div class="st-join-hero__actions">
            <a href="{{ $rechargeUrl }}" class="st-pill st-pill--solid st-pill--lg">{{ __('student_timeline.recharge_package') }}</a>
            <a href="{{ route('dashboard') }}" class="st-pill st-pill--outline">{{ __('student_timeline.school_gate') }}</a>
        </div>
    </section>
@endif

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.credits_breakdown') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.credits_individual') }}</p>
        <p class="st-stat-card__value">{{ (int) $totals['individual'] }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.credits_collective') }}</p>
        <p class="st-stat-card__value">{{ (int) $totals['collective'] }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.credits_private') }}</p>
        <p class="st-stat-card__value">{{ (int) $totals['private'] }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.credits_global') }}</p>
        <p class="st-stat-card__value">{{ (int) $totals['global'] }}</p>
    </article>
</section>

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student_timeline.entitlement_history') }}</h2>
        <p>{{ __('student_timeline.entitlement_history_hint') }}</p>
    </div>
</section>

<section class="st-credit-list" aria-label="{{ __('student_timeline.entitlement_history') }}">
    @forelse($entitlements as $i => $ent)
        @php
            $left = $ent->unitsLeft();
            $bookable = \App\Services\StudentEntitlementService::bookableUnitsLeft($ent);
            $name = $ent->servicePackage?->name
                ?: (\App\Models\ServicePackage::scopes()[$ent->scope] ?? $ent->scope);
            $context = collect([$ent->academicYear?->name, $ent->academicSubject?->name, $ent->tutoringGroup?->title])
                ->filter()
                ->implode(' · ');
            $tone = $tones[$i % count($tones)];
            $status = $statusLabels[$ent->status] ?? $ent->status;
            $isActive = $ent->status === \App\Models\StudentServiceEntitlement::STATUS_ACTIVE && $left > 0;
        @endphp
        <article class="st-credit-card st-credit-card--{{ $tone }} {{ $isActive ? '' : 'is-dim' }}">
            <div class="st-credit-card__main">
                <div class="st-credit-card__copy">
                    <div class="st-credit-card__badges">
                        <span class="st-credit-card__badge {{ $isActive ? 'is-ok' : '' }}">{{ $status }}</span>
                        @if($ent->expires_at)
                            <span class="st-credit-card__mins">
                                {{ __('student_timeline.expires_on', ['date' => $ent->expires_at->format('Y-m-d')]) }}
                            </span>
                        @endif
                    </div>
                    <h3>{{ $name }}</h3>
                    @if($context !== '')
                        <p class="st-credit-card__meta">{{ $context }}</p>
                    @endif
                </div>
                <div class="st-credit-card__nums" aria-label="{{ __('student_timeline.credits_left') }}">
                    <strong>{{ $left }}</strong>
                    <span>/ {{ $ent->units_total }}</span>
                </div>
            </div>
            <div class="st-credit-card__foot">
                <span class="st-credit-card__bookable">
                    {{ __('student_timeline.bookable_now', ['count' => $bookable]) }}
                    @if($left > $bookable)
                        <small>{{ __('student_timeline.bookable_reserved') }}</small>
                    @endif
                </span>
                @if($isActive)
                    <a href="{{ route('student.learn.index') }}" class="st-pill st-pill--solid">{{ __('student_timeline.book_with_credits') }}</a>
                @endif
            </div>
        </article>
    @empty
        <div class="st-empty-panel">
            <h3>{{ __('student_timeline.no_credits') }}</h3>
            <p>{{ __('student_timeline.no_credits_hint') }}</p>
            <div class="st-biz-banner__actions">
                <a href="{{ $rechargeUrl }}" class="st-pill st-pill--solid">{{ __('student_timeline.recharge_package') }}</a>
                <a href="{{ route('dashboard') }}" class="st-pill st-pill--outline">{{ __('student_timeline.school_gate') }}</a>
            </div>
        </div>
    @endforelse
</section>

@if(method_exists($entitlements, 'hasPages') && $entitlements->hasPages())
    <div class="st-pager">
        {{ $entitlements->links() }}
    </div>
@endif

@if($packages->isNotEmpty())
    <section class="st-pkg-section" aria-label="{{ __('student_timeline.suggested_packages') }}">
        <div class="st-msg-intro">
            <div>
                <h2>{{ __('student_timeline.suggested_packages') }}</h2>
                <p>{{ __('student_timeline.suggested_packages_hint') }}</p>
            </div>
            <a href="{{ $rechargeUrl }}" class="st-see">{{ __('student_timeline.see_all') }}</a>
        </div>
        <div class="st-pkg-grid">
            @foreach($packages as $package)
                @php
                    $perks = is_array($package->features) ? $package->features : [];
                    $hoursLabel = $package->units_count
                        ? ($isRtl ? $package->units_count.' حصة' : $package->units_count.' sessions')
                        : null;
                @endphp
                <a href="{{ route('public.service-packages.checkout', $package) }}" class="st-pkg-card {{ $package->is_featured ? 'is-featured' : '' }}">
                    @if(!empty($package->badge))
                        <span class="st-pkg-card__badge">{{ $package->badge }}</span>
                    @endif
                    <h3>{{ $package->name }}</h3>
                    @if($hoursLabel)
                        <p class="st-pkg-card__meta">{{ $hoursLabel }} · {{ $package->sessionMinutes() }} {{ __('student_timeline.minutes') }}</p>
                    @else
                        <p class="st-pkg-card__meta">{{ $package->sessionMinutes() }} {{ __('student_timeline.minutes') }}</p>
                    @endif
                    @if(!empty($package->tagline))
                        <p class="st-pkg-card__valid">{{ $package->tagline }}</p>
                    @else
                        <p class="st-pkg-card__valid">{{ __('student_timeline.valid_for') }} {{ $package->validityLabel() }}</p>
                    @endif
                    <p class="st-pkg-card__price">{{ $package->formattedPrice() }}</p>
                    @if($package->formattedOriginalPrice())
                        <p class="st-pkg-card__old">{{ $package->formattedOriginalPrice() }}</p>
                    @endif
                    <p class="st-pkg-card__unit">{{ $package->formattedPricePerUnit() }} / {{ __('student_timeline.session_unit') }}</p>
                    @if(count($perks) > 0)
                        <ul class="st-pkg-card__perks">
                            @foreach(array_slice($perks, 0, 4) as $perk)
                                <li>{{ is_string($perk) ? $perk : (string) $perk }}</li>
                            @endforeach
                        </ul>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endif
@endsection

@section('events')
<div class="st-events__top">
    <h2>{{ __('student_timeline.quick_links') }}</h2>
</div>

<a href="{{ $rechargeUrl }}" class="st-event-card st-event-card--orange">
    <h3>{{ __('student_timeline.recharge_package') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.recharge_hint') }}</p>
</a>

<a href="{{ $bookUrl }}" class="st-event-card st-event-card--blue">
    <h3>{{ __('student_timeline.book_with_credits') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.book_hint') }}</p>
</a>

@if(Route::has('student.classes.index'))
    <a href="{{ route('student.classes.index') }}" class="st-event-card st-event-card--pink">
        <h3>{{ __('student_timeline.my_classes') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.classes_hint') }}</p>
    </a>
@endif

@if(Route::has('student.private-lectures.index'))
    <a href="{{ route('student.private-lectures.index') }}" class="st-event-card st-event-card--blue">
        <h3>{{ __('student_timeline.nav_lessons') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.private_lessons_hint') }}</p>
    </a>
@endif

<div class="st-events__see">
    <a href="{{ route('dashboard') }}">{{ __('student_timeline.school_gate') }}</a>
</div>
@endsection
