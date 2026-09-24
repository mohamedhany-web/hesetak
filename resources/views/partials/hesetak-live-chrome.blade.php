{{-- شريط علوي موحّد لغرف البث — حصتك --}}
@php
    $liveRole = $liveRole ?? 'student'; // instructor|student|admin
    $liveBackUrl = $liveBackUrl ?? url('/');
    $liveTitle = $liveTitle ?? 'حصة مباشرة';
    $liveKicker = $liveKicker ?? 'بث مباشر';
    $liveSubtitle = $liveSubtitle ?? null;
    $liveShowBrandName = $liveShowBrandName ?? true;
    $liveRoleLabel = match ($liveRole) {
        'instructor' => 'معلم',
        'admin' => 'إدارة',
        default => 'طالب',
    };
    $brandMark = public_path('img/brand/hesetak-mark.png');
    $brandMarkUrl = is_file($brandMark) ? asset('img/brand/hesetak-mark.png') : null;
@endphp
<header class="hstk-live-top">
    <div class="hstk-live-top__start">
        <a href="{{ $liveBackUrl }}" class="hstk-live-brand" title="{{ config('app.name') }}">
            <span class="hstk-live-brand__mark" aria-hidden="true">
                @if($brandMarkUrl)
                    <img src="{{ $brandMarkUrl }}" alt="">
                @else
                    <i class="fas fa-broadcast-tower"></i>
                @endif
            </span>
            @if($liveShowBrandName)
                <span class="hstk-live-brand__name">{{ config('app.name') }}</span>
            @endif
        </a>
        <div class="hstk-live-meta">
            <p class="hstk-live-meta__kicker">
                <span class="hstk-live-dot" aria-hidden="true"></span>
                {{ $liveKicker }}
                <span class="hstk-live-role-chip">{{ $liveRoleLabel }}</span>
            </p>
            <h1 class="hstk-live-meta__title">{{ $liveTitle }}</h1>
            @if(filled($liveSubtitle))
                <p class="hstk-live-meta__sub">{!! $liveSubtitle !!}</p>
            @endif
        </div>
    </div>
    <div class="hstk-live-actions">
        {!! $liveActions ?? '' !!}
    </div>
</header>
