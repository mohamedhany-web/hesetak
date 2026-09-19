@extends('layouts.app')

@section('title', __('instructor.notif_title'))
@section('page_title', __('instructor.notifications'))

@section('content')
@php
    $locale = app()->getLocale();
    $filter = request('status');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.notifications') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.instructor_panel') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.notifications') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.notif_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            @if(($stats['unread'] ?? 0) > 0)
                <form method="post" action="{{ route('instructor.notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="id-btn id-btn--gold">
                        <i class="fas fa-check-double" aria-hidden="true"></i>
                        {{ __('instructor.notif_mark_all_read') }}
                    </button>
                </form>
            @endif
            @if(Route::has('instructor.private-messages.index'))
                <a href="{{ route('instructor.private-messages.index') }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-comments" aria-hidden="true"></i>
                    {{ __('instructor.student_messages') }}
                </a>
            @endif
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(3,minmax(0,1fr))" aria-label="{{ __('instructor.notifications') }}">
        <a href="{{ route('instructor.notifications.index') }}" class="id-kpi">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-bell"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.notif_stat_total') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total'] ?? 0) }}</span>
            </span>
        </a>
        <a href="{{ route('instructor.notifications.index', ['status' => 'unread']) }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-envelope"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.notif_stat_unread') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['unread'] ?? 0) }}</span>
            </span>
        </a>
        <a href="{{ route('instructor.notifications.index', ['status' => 'read']) }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-envelope-open"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.notif_stat_read') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['read'] ?? 0) }}</span>
            </span>
        </a>
    </section>

    <nav class="id-filters" aria-label="{{ __('instructor.notif_filter_all') }}">
        <a href="{{ route('instructor.notifications.index') }}" class="id-filter {{ ! $filter ? 'is-on' : '' }}">{{ __('instructor.notif_filter_all') }}</a>
        <a href="{{ route('instructor.notifications.index', ['status' => 'unread']) }}" class="id-filter {{ $filter === 'unread' ? 'is-on' : '' }}">{{ __('instructor.notif_filter_unread') }}</a>
        <a href="{{ route('instructor.notifications.index', ['status' => 'read']) }}" class="id-filter {{ $filter === 'read' ? 'is-on' : '' }}">{{ __('instructor.notif_filter_read') }}</a>
    </nav>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.notifications') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.notifications') }}</h2>
            @if(($stats['unread'] ?? 0) > 0)
                <span class="id-panel__badge">{{ number_format($stats['unread']) }} {{ __('instructor.notif_stat_unread') }}</span>
            @endif
        </header>

        <div class="id-list">
            @forelse($notifications as $n)
                <article class="id-list__row {{ $n->is_read ? '' : 'is-unread' }}">
                    <span class="id-list__ico {{ $n->is_read ? 'id-list__ico--teal' : '' }}" aria-hidden="true" @unless($n->is_read) style="background:#FBF3E0;color:#C9952A" @endunless>
                        <i class="fas {{ $n->is_read ? 'fa-envelope-open' : 'fa-envelope' }}"></i>
                    </span>
                    <div class="id-list__body">
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:4px">
                            @unless($n->is_read)
                                <span class="id-chip id-chip--warn">{{ __('instructor.notif_filter_unread') }}</span>
                            @else
                                <span class="id-chip id-chip--muted">{{ __('instructor.notif_filter_read') }}</span>
                            @endunless
                            @if($n->sender?->name)
                                <span class="id-chip">{{ $n->sender->name }}</span>
                            @endif
                        </div>
                        <div class="id-list__title" style="white-space:normal">{{ $n->title }}</div>
                        @if($n->message)
                            <div class="id-list__meta" style="white-space:normal;margin-top:4px;line-height:1.5">{{ $n->message }}</div>
                        @endif
                        <div class="id-list__meta" style="margin-top:6px">{{ $n->created_at?->diffForHumans() }}</div>
                    </div>
                    <div class="id-list__actions">
                        @if($n->action_url)
                            <a href="{{ route('instructor.notifications.go', $n) }}" class="id-btn id-btn--navy" style="min-height:36px;padding:0 12px;font-size:12px">
                                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                {{ $n->action_text ?: __('instructor.notif_open') }}
                            </a>
                        @endif
                        @unless($n->is_read)
                            <form method="post" action="{{ route('instructor.notifications.mark-read', $n) }}">
                                @csrf
                                <button type="submit" class="id-btn id-btn--outline" style="min-height:36px;padding:0 12px;font-size:12px">
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                    {{ __('instructor.notif_mark_read') }}
                                </button>
                            </form>
                        @endunless
                    </div>
                </article>
            @empty
                <div class="id-empty">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-bell-slash"></i></span>
                    <p>{{ __('instructor.notif_empty') }}</p>
                </div>
            @endforelse
        </div>

        @if(method_exists($notifications, 'hasPages') && $notifications->hasPages())
            <div class="id-pager">{{ $notifications->links() }}</div>
        @endif
    </section>
</div>
@endsection
