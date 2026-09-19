@extends('layouts.app')

@section('title', __('instructor.my_requests_to_management') . ' - ' . config('app.name'))
@section('page_title', __('instructor.submit_requests_to_management'))

@section('content')
@php
    $locale = app()->getLocale();
    $statusFilter = request('status');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.my_requests_to_management') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.submit_requests_to_management') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.my_requests_to_management') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.my_requests_description') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.management-requests.create') }}" class="id-btn id-btn--gold">
                <i class="fas fa-plus" aria-hidden="true"></i>
                {{ __('instructor.new_request') }}
            </a>
            @if(Route::has('instructor.tasks.index'))
                <a href="{{ route('instructor.tasks.index') }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-tasks" aria-hidden="true"></i>
                    {{ __('instructor.tasks_from_management') }}
                </a>
            @endif
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.my_requests_to_management') }}">
        <a href="{{ route('instructor.management-requests.index') }}" class="id-kpi">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-inbox"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.my_requests_to_management') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total'] ?? 0) }}</span>
            </span>
        </a>
        <a href="{{ route('instructor.management-requests.index', ['status' => 'pending']) }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-clock"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.pending_review') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['pending'] ?? 0) }}</span>
            </span>
        </a>
        <a href="{{ route('instructor.management-requests.index', ['status' => 'approved']) }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-check"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.approved') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['approved'] ?? 0) }}</span>
            </span>
        </a>
        <a href="{{ route('instructor.management-requests.index', ['status' => 'rejected']) }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-times"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.rejected') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['rejected'] ?? 0) }}</span>
            </span>
        </a>
    </section>

    <nav class="id-filters" aria-label="{{ __('instructor.all_statuses_filter') }}">
        <a href="{{ route('instructor.management-requests.index') }}" class="id-filter {{ ! $statusFilter ? 'is-on' : '' }}">{{ __('instructor.all_statuses_filter') }}</a>
        <a href="{{ route('instructor.management-requests.index', ['status' => 'pending']) }}" class="id-filter {{ $statusFilter === 'pending' ? 'is-on' : '' }}">{{ __('instructor.pending_review') }}</a>
        <a href="{{ route('instructor.management-requests.index', ['status' => 'approved']) }}" class="id-filter {{ $statusFilter === 'approved' ? 'is-on' : '' }}">{{ __('instructor.approved') }}</a>
        <a href="{{ route('instructor.management-requests.index', ['status' => 'rejected']) }}" class="id-filter {{ $statusFilter === 'rejected' ? 'is-on' : '' }}">{{ __('instructor.rejected') }}</a>
    </nav>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.my_requests_to_management') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.my_requests_to_management') }}</h2>
            @if(($stats['pending'] ?? 0) > 0)
                <span class="id-panel__badge">{{ number_format($stats['pending']) }} {{ __('instructor.pending_review') }}</span>
            @endif
        </header>

        <div class="id-table-wrap">
            <table class="id-table">
                <thead>
                    <tr>
                        <th>{{ __('instructor.request_subject') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        @php
                            $chip = match ($req->status) {
                                'pending' => 'id-chip--warn',
                                'approved' => 'id-chip--ok',
                                default => 'id-chip--rose',
                            };
                            $label = match ($req->status) {
                                'pending' => __('instructor.pending_review'),
                                'approved' => __('instructor.approved'),
                                default => __('instructor.rejected'),
                            };
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $req->subject }}</strong>
                                <div class="muted" style="font-size:12px;margin-top:2px">{{ Str::limit($req->message, 60) }}</div>
                            </td>
                            <td><span class="id-chip {{ $chip }}">{{ $label }}</span></td>
                            <td class="tabular-nums"><span class="muted">{{ $req->created_at->format('Y-m-d H:i') }}</span></td>
                            <td class="id-table__end">
                                <a href="{{ route('instructor.management-requests.show', $req) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">
                                    {{ __('common.view') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-inbox"></i></span>
                                    <p>{{ __('instructor.no_requests_yet') }}</p>
                                    <div class="id-empty__actions">
                                        <a href="{{ route('instructor.management-requests.create') }}" class="id-btn id-btn--navy">
                                            <i class="fas fa-plus" aria-hidden="true"></i>
                                            {{ __('instructor.new_request') }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="id-pager">{{ $requests->appends(request()->query())->links() }}</div>
        @endif
    </section>
</div>
@endsection
