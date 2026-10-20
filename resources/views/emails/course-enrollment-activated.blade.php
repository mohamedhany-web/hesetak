@extends('emails.layouts.brand')

@php
    $student = $enrollment->student;
    $course  = $enrollment->course;
    $emailTitle = 'تم تفعيل الكورس';
    $emailHeading = 'تم تفعيل الكورس الخاص بك';
    $emailEyebrow = 'اشتراكك جاهز';
    $emailPreheader = 'يمكنك الآن بدء التعلّم على منصة حصتك.';
    $emailCtaUrl = url(route('public.course.show', $course?->id ?? 0));
    $emailCtaLabel = 'الدخول إلى صفحة الكورس';
    $c = \App\Support\EmailBranding::colors();
@endphp

@section('content')
    <p style="margin:0 0 12px;font-size:15px;line-height:1.7;color:#334155;">
        مرحباً {{ $student?->name ?? 'طالبنا العزيز' }}،
    </p>
    <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#334155;">
        تم تفعيل اشتراكك. يمكنك الآن الدخول وبدء التعلّم.
    </p>
    <div style="background:#F8FAFC;border-radius:12px;padding:14px 16px;margin:0 0 10px;border-right:4px solid {{ $c['gold'] }};">
        <div style="font-size:11px;color:#64748b;margin-bottom:4px;">اسم الكورس</div>
        <div style="font-weight:700;color:{{ $c['navyDeep'] }};font-size:15px;">{{ $course?->title ?? 'الكورس' }}</div>
    </div>
    @if($course && $course->academicYear)
        <div style="background:#F8FAFC;border-radius:12px;padding:14px 16px;margin:0 0 10px;border-right:4px solid {{ $c['navy'] }};">
            <div style="font-size:11px;color:#64748b;margin-bottom:4px;">المسار / السنة الأكاديمية</div>
            <div style="font-weight:700;color:{{ $c['navyDeep'] }};font-size:15px;">{{ $course->academicYear->name }}</div>
        </div>
    @endif
    <p style="margin:12px 0 0;font-size:13px;line-height:1.7;color:#64748b;">
        سجّل الدخول بنفس البريد المرتبط بحسابك إن لم تُفتح الصفحة مباشرة.
    </p>
@endsection
