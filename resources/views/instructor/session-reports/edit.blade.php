@extends('layouts.app')

@section('title', 'تقرير الحصة')
@section('page_title', 'تقرير الحصة')

@section('content')
@php
    $field = 'width:100%;padding:.6rem .75rem;border-radius:.6rem;border:1px solid rgba(128,128,128,.35);font:inherit';
    $label = 'display:grid;gap:.35rem;font-weight:700';
@endphp
<div class="id-page">
    @if(session('error'))
        <div class="id-alert id-alert--err" style="margin-bottom:1rem">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="id-alert id-alert--err" style="margin-bottom:1rem">{{ $errors->first() }}</div>
    @endif

    <section class="id-hero" aria-label="تقرير الحصة">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">تقرير إلزامي</p>
            <h2 class="id-hero__title">تقرير حصة {{ $session->student?->name ?? 'الطالب' }}</h2>
            <p class="id-hero__meta">
                @if($session->scheduled_at)<x-app-datetime :at="$session->scheduled_at" pattern="Y-m-d · g:i A" />@endif
                @if($report) · تم الإرسال سابقاً — يمكنك تعديله @endif
            </p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.session-reports.index') }}" class="id-btn id-btn--ghost">رجوع</a>
        </div>
    </section>

    <section class="id-panel" aria-label="نموذج التقرير">
        <form method="POST" action="{{ route('instructor.session-reports.save', $session) }}" style="display:grid;gap:1rem;max-width:44rem">
            @csrf
            <label style="{{ $label }}">ملخص الحصة *
                <textarea name="summary" rows="5" required minlength="20" maxlength="5000" style="{{ $field }}" placeholder="ماذا تناولت الحصة؟ وكيف كان تفاعل الطالب؟">{{ old('summary', $report?->summary) }}</textarea>
            </label>
            <label style="{{ $label }}">الموضوعات التي تمت تغطيتها
                <textarea name="topics_covered" rows="3" maxlength="3000" style="{{ $field }}">{{ old('topics_covered', $report?->topics_covered) }}</textarea>
            </label>
            <label style="{{ $label }}">مستوى الطالب في الحصة
                <select name="performance_level" style="{{ $field }}">
                    <option value="">— اختر —</option>
                    @foreach($levels as $key => $text)
                        <option value="{{ $key }}" @selected(old('performance_level', $report?->performance_level) === $key)>{{ $text }}</option>
                    @endforeach
                </select>
            </label>
            <label style="{{ $label }}">الواجب / التدريب المطلوب
                <textarea name="homework" rows="3" maxlength="3000" style="{{ $field }}">{{ old('homework', $report?->homework) }}</textarea>
            </label>
            <label style="{{ $label }}">الخطوات القادمة
                <textarea name="next_steps" rows="3" maxlength="3000" style="{{ $field }}">{{ old('next_steps', $report?->next_steps) }}</textarea>
            </label>
            <div>
                <button type="submit" class="id-btn id-btn--gold"><i class="fas fa-paper-plane" aria-hidden="true"></i> إرسال التقرير للطالب</button>
            </div>
        </form>
    </section>
</div>
@endsection
