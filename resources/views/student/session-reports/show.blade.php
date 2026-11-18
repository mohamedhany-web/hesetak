@extends('layouts.student-timeline')

@section('title', 'تقرير الحصة')

@section('content')
@php
    $locale = app()->getLocale();
    $tz = auth()->user()->timezoneCode();
    $blocks = [
        'الموضوعات التي تمت تغطيتها' => $report->topics_covered,
        'الواجب / التدريب المطلوب' => $report->homework,
        'الخطوات القادمة' => $report->next_steps,
    ];
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => 'تقرير الحصة',
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => 'تقارير الحصص', 'url' => route('student.session-reports.index')],
        ['label' => 'تقرير الحصة', 'url' => null],
    ],
])

<section class="st-panel" style="display:grid;gap:1rem">
    <div class="st-section-head">
        <div>
            <h2>تقرير المعلم {{ $report->instructor?->name }}</h2>
            <p>
                {{ $report->session?->scheduled_at?->timezone($tz)->locale('ar')->translatedFormat('l j F Y — g:i A') }}
                @if($report->performanceLabel()) · المستوى: <strong>{{ $report->performanceLabel() }}</strong>@endif
            </p>
        </div>
    </div>

    <div>
        <h3 style="margin:0 0 .35rem">ملخص الحصة</h3>
        <p style="margin:0;line-height:1.9;white-space:pre-line">{{ $report->summary }}</p>
    </div>

    @foreach($blocks as $title => $text)
        @if(filled($text))
            <div>
                <h3 style="margin:0 0 .35rem">{{ $title }}</h3>
                <p style="margin:0;line-height:1.9;white-space:pre-line">{{ $text }}</p>
            </div>
        @endif
    @endforeach

    <div>
        <a href="{{ route('student.session-reports.index') }}" class="st-pill st-pill--outline">كل التقارير</a>
    </div>
</section>
@endsection
