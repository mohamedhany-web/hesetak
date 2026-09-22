@extends('layouts.student-timeline')

@section('title', 'مدرستي')
@section('page_title', 'مدرستي')

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $trialUrl = route('home').'?open_trial=1';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">{{ $isRtl ? 'المدرسة · السنوات والحصص' : 'School · years & classes' }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $isRtl ? 'مدرستي' : 'My School' }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ $isRtl ? 'سنواتك الدراسية، الحصص القادمة، وتوصية تحديد المستوى.' : 'Your school years, upcoming classes, and placement recommendation.' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('public.service-packages.index', $recommendedYear ? ['year' => $recommendedYear->id] : []) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-white px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-box-open text-xs"></i>
                {{ $isRtl ? 'باقات المدرسة' : 'School packages' }}
            </a>
            <a href="{{ Route::has('public.groups') ? route('public.groups') : (Route::has('public.curricula') ? route('public.curricula') : route('public.instructors.index')) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-white px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-school text-xs"></i>
                {{ $isRtl ? 'صفحة المدرسة' : 'School page' }}
            </a>
            <a href="{{ $trialUrl }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-clipboard-check text-xs"></i>
                {{ $isRtl ? 'اختبار تحديد المستوى' : 'Placement test' }}
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-white px-4 py-3 text-sm font-medium text-ink shadow-soft">{{ session('success') }}</div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-2xl border border-line bg-white p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-graduation-cap text-sm"></i></div>
            <p class="mt-3 text-xs text-muted">{{ $isRtl ? 'سنوات منضم إليها' : 'Enrolled years' }}</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $years->count() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-white p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-calendar-day text-sm"></i></div>
            <p class="mt-3 text-xs text-muted">{{ $isRtl ? 'حصص قادمة' : 'Upcoming classes' }}</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $upcoming->count() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-white p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check-circle text-sm"></i></div>
            <p class="mt-3 text-xs text-muted">{{ $isRtl ? 'حصص مكتملة' : 'Completed' }}</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $completedCount }}</p>
        </article>
    </section>

    @if($recommendedYear)
        <article class="overflow-hidden rounded-2xl border border-accent/25 bg-gradient-to-l from-accent/10 to-white p-5 shadow-soft">
            <p class="text-xs font-bold uppercase tracking-wide text-accent">{{ $isRtl ? 'السنة المقترحة / الحالية' : 'Recommended / current year' }}</p>
            <h3 class="mt-1 text-lg font-semibold text-ink">{{ $recommendedYear->name }}</h3>
            @if($recommendedYear->tagline)
                <p class="text-sm text-muted">{{ $recommendedYear->tagline }}</p>
            @endif
            <a href="{{ Route::has('public.school.year') ? route('public.school.year', $recommendedYear->slug) : (Route::has('public.curricula.show') ? route('public.curricula.show', $recommendedYear) : route('public.curricula')) }}" class="mt-3 inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-door-open"></i> {{ $isRtl ? 'عرض فصول السنة' : 'View year classes' }}
            </a>
        </article>
    @elseif(!$placement)
        <article class="rounded-2xl border border-dashed border-line bg-white p-5 text-center shadow-soft">
            <p class="font-semibold text-ink">{{ $isRtl ? 'لم يُسجَّل اختبار تحديد مستوى بعد' : 'No placement assessment yet' }}</p>
            <p class="mt-1 text-sm text-muted">{{ $isRtl ? 'ابدأ باختبار مجاني لنحدد السنة المناسبة.' : 'Start with a free assessment to find the right year.' }}</p>
            <a href="{{ $trialUrl }}" class="mt-3 inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-clipboard-check"></i> {{ $isRtl ? 'احجز الاختبار المجاني' : 'Book free placement test' }}
            </a>
        </article>
    @endif

    @if($placement)
        <article class="overflow-hidden rounded-2xl border border-line bg-white shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">{{ $isRtl ? 'آخر اختبار تحديد مستوى' : 'Latest placement assessment' }}</h3>
            </div>
            <div class="p-4 sm:p-5 text-sm text-muted">
                <p>
                    {{ $placement->starts_at?->format('Y-m-d H:i') }} · {{ $placement->status }}
                    @if($placement->recommendedSchoolYear)
                        · {{ $isRtl ? 'موصى:' : 'Recommended:' }} <span class="font-semibold text-ink">{{ $placement->recommendedSchoolYear->name }}</span>
                    @endif
                </p>
                @if($placement->admin_notes)
                    <p class="mt-3 rounded-xl border border-line bg-slate-50 p-3 text-ink">{{ $placement->admin_notes }}</p>
                @endif
            </div>
        </article>
    @endif

    <article class="overflow-hidden rounded-2xl border border-line bg-white shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">{{ $isRtl ? 'الحصص القادمة' : 'Upcoming classes' }}</h3>
            @if(Route::has('student.tutoring-bookings.index'))
            <a href="{{ route('student.tutoring-bookings.index') }}" class="text-sm font-medium text-accent hover:underline">{{ $isRtl ? 'كل الحجوزات' : 'All bookings' }}</a>
            @elseif(Route::has('student.one-to-one-sessions.index'))
            <a href="{{ route('student.one-to-one-sessions.index') }}" class="text-sm font-medium text-accent hover:underline">{{ $isRtl ? 'حصصي' : 'My sessions' }}</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-slate-50 text-xs font-semibold text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ $isRtl ? 'الفصل' : 'Class' }}</th>
                        <th class="px-4 py-3 text-start">{{ $isRtl ? 'السنة' : 'Year' }}</th>
                        <th class="px-4 py-3 text-start">{{ $isRtl ? 'الموعد' : 'Time' }}</th>
                        <th class="px-4 py-3 text-end">{{ $isRtl ? 'إجراء' : 'Action' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($upcoming as $booking)
                        <tr>
                            <td class="px-4 py-3 font-medium text-ink">{{ $booking->tutoringGroup?->title }}</td>
                            <td class="px-4 py-3 text-muted">{{ $booking->tutoringGroup?->schoolYear?->name ?: '—' }}</td>
                            <td class="px-4 py-3 tabular-nums text-muted">{{ $booking->starts_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-end">
                                <div class="inline-flex flex-wrap justify-end gap-2">
                                    @if($booking->classroomMeeting)
                                        <a href="{{ url('/classroom/join/'.$booking->classroomMeeting->code) }}" class="text-accent hover:underline">{{ $isRtl ? 'دخول' : 'Join' }}</a>
                                    @endif
                                    @if(Route::has('student.tutoring-bookings.show'))
                                    <a href="{{ route('student.tutoring-bookings.show', $booking) }}" class="text-ink-soft hover:underline">{{ $isRtl ? 'تفاصيل' : 'Details' }}</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-muted">{{ $isRtl ? 'لا توجد حصص مدرسية قادمة.' : 'No upcoming school classes.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    @if($allYears->isNotEmpty())
        <section>
            <h3 class="mb-3 text-base font-semibold text-ink">{{ $isRtl ? 'استكشف السنوات' : 'Explore school years' }}</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach($allYears as $y)
                    <a href="{{ Route::has('public.school.year') ? route('public.school.year', $y->slug) : (Route::has('public.curricula.show') ? route('public.curricula.show', $y) : route('public.curricula')) }}" class="rounded-2xl border border-line bg-white p-4 shadow-soft transition hover:border-accent/40">
                        <p class="text-xs font-bold text-accent">{{ str_pad((string) $y->level_number, 2, '0', STR_PAD_LEFT) }}</p>
                        <p class="mt-1 text-sm font-semibold text-ink">{{ $y->name }}</p>
                        <p class="mt-1 text-xs text-muted">{{ $y->tagline }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
