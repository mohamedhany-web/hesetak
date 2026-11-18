@extends('layouts.student-timeline')

@section('title', 'تقارير الحصص')

@section('content')
@php $locale = app()->getLocale(); @endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => 'تقارير الحصص',
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => 'تقارير الحصص', 'url' => null],
    ],
])

<section class="st-msg-intro">
    <div>
        <h2>تقارير الحصص</h2>
        <p>بعد كل حصة يكتب لك المعلم تقريراً بما تناولتموه والواجب والخطوات القادمة.</p>
    </div>
</section>

<section class="st-panel" aria-label="تقارير الحصص">
    @forelse($reports as $report)
        <a href="{{ route('student.session-reports.show', $report) }}" class="st-pill st-pill--outline st-pill--block" style="display:flex;justify-content:space-between;gap:.75rem;margin-bottom:.6rem;padding:.9rem 1rem;text-align:start">
            <span>
                <strong>{{ $report->instructor?->name ?? 'المعلم' }}</strong>
                <span style="display:block;font-size:.85em;opacity:.75">
                    {{ $report->session?->scheduled_at?->timezone(auth()->user()->timezoneCode())->locale('ar')->translatedFormat('l j F Y — g:i A') ?? $report->submitted_at?->format('Y-m-d') }}
                </span>
            </span>
            @if(! $report->student_read_at)
                <span class="st-pill st-pill--solid" style="min-height:0;padding:.15rem .6rem">جديد</span>
            @endif
        </a>
    @empty
        <p style="margin:0">لا توجد تقارير بعد — ستظهر هنا بعد أن يرفع المعلم تقرير حصتك.</p>
    @endforelse

    @if($reports->hasPages())
        <div style="margin-top:1rem">{{ $reports->links() }}</div>
    @endif
</section>
@endsection
