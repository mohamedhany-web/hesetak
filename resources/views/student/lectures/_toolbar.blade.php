@php
    $searchQuery = $searchQuery ?? '';
    $filter = $filter ?? 'all';
    $courseCount = $courseCount ?? 0;
    $privateCount = $privateCount ?? 0;
    $filterUrl = function (string $key) use ($searchQuery) {
        return route('student.lectures.index', array_filter([
            'filter' => $key === 'all' ? null : $key,
            'q' => $searchQuery !== '' ? $searchQuery : null,
            'lang' => request('lang'),
        ], fn ($v) => $v !== null && $v !== ''));
    };
@endphp
<div class="st-top__row st-top__row--secondary">
    <p class="st-top__count">
        {{ __('student_timeline.lectures_count', ['count' => $courseCount + $privateCount]) }}
    </p>
    <div class="st-top__chips">
        <a href="{{ $filterUrl('all') }}" class="st-chip {{ $filter === 'all' ? 'is-active' : '' }}">{{ __('student_timeline.filter_all') }}</a>
        <a href="{{ $filterUrl('courses') }}" class="st-chip {{ $filter === 'courses' ? 'is-active' : '' }}">{{ __('student_timeline.lectures_courses') }} ({{ $courseCount }})</a>
        <a href="{{ $filterUrl('private') }}" class="st-chip {{ $filter === 'private' ? 'is-active' : '' }}">{{ __('student_timeline.lectures_private') }} ({{ $privateCount }})</a>
    </div>
</div>

<form class="st-search" method="get" action="{{ route('student.lectures.index') }}" role="search">
    @if($filter !== 'all')
        <input type="hidden" name="filter" value="{{ $filter }}">
    @endif
    @if(request('lang'))
        <input type="hidden" name="lang" value="{{ request('lang') }}">
    @endif
    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="{{ __('student_timeline.search_lectures') }}" aria-label="{{ __('student_timeline.search_lectures') }}">
</form>
