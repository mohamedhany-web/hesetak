@extends('layouts.student-timeline')

@section('title', __('student.dashboard_title'))

@push('styles')
<style>
    .hs-home {
        --h-navy: #1E4E8C;
        --h-ink: #152A4A;
        --h-gold: #C9952A;
        --h-muted: #6B7A99;
        --h-line: #E8EEF8;
    }
    .hs-hero {
        border-radius: 22px;
        background:
            radial-gradient(ellipse 60% 80% at 100% 0%, rgba(30, 78, 140, 0.14), transparent 55%),
            radial-gradient(ellipse 40% 50% at 0% 100%, rgba(201, 149, 42, 0.12), transparent 50%),
            linear-gradient(135deg, #fff 0%, #F4F7FC 100%);
        border: 1px solid var(--h-line);
        padding: 1.25rem 1.35rem 1.4rem;
    }
    .hs-week {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: .55rem;
    }
    @media (max-width: 900px) {
        .hs-week { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .hs-day {
        border-radius: 16px;
        border: 1.5px solid var(--h-line);
        background: #fff;
        min-height: 140px;
        padding: .7rem .65rem .85rem;
    }
    .hs-day.is-today {
        border-color: rgba(201, 149, 42, 0.55);
        box-shadow: 0 10px 24px -14px rgba(201, 149, 42, 0.45);
        background: linear-gradient(180deg, #FFFBF3, #fff);
    }
    .hs-day__badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2rem; height: 2rem; border-radius: 999px;
        font-weight: 900; font-size: .85rem; color: #fff;
        background: var(--h-navy);
    }
    .hs-day.is-today .hs-day__badge { background: var(--h-gold); color: var(--h-ink); }
    .hs-slot {
        display: block;
        margin-top: .45rem;
        border-radius: 12px;
        padding: .45rem .5rem;
        text-decoration: none !important;
        color: inherit;
        border: 1.5px solid transparent;
        font-size: .72rem;
        line-height: 1.25;
    }
    .hs-slot--gold { background: #FFF3C4; border-color: rgba(201, 149, 42, 0.35); }
    .hs-slot--blue { background: #E8F0FA; border-color: rgba(30, 78, 140, 0.2); }
    .hs-slot--teal { background: #D7F5E8; border-color: rgba(47, 174, 122, 0.25); }
    .hs-slot__time { font-weight: 900; color: var(--h-ink); display: block; }
    .hs-slot__title { font-weight: 800; color: #334; display: block; margin-top: 2px; }
    .hs-hub {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .85rem;
    }
    @media (min-width: 768px) {
        .hs-hub { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    .hs-hub__card {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: .75rem; text-align: center;
        min-height: 140px; padding: 1.1rem .8rem;
        border-radius: 18px; text-decoration: none !important; color: inherit;
        border: 1.5px solid var(--h-line); background: #fff;
        box-shadow: 0 10px 24px -18px rgba(21, 42, 74, 0.28);
        transition: transform .18s ease, box-shadow .18s ease, border-color .15s ease;
    }
    .hs-hub__card:hover {
        transform: translateY(-3px);
        border-color: rgba(30, 78, 140, 0.28);
        box-shadow: 0 16px 32px -16px rgba(21, 42, 74, 0.28);
    }
    .hs-hub__icon {
        width: 3.75rem; height: 3.75rem; border-radius: 16px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.45rem; color: #fff;
    }
    .hs-hub__icon--lessons { background: linear-gradient(135deg, #1E4E8C, #152A4A); }
    .hs-hub__icon--credits { background: linear-gradient(135deg, #C9952A, #A67A1F); color: #152A4A; }
    .hs-hub__icon--teachers { background: linear-gradient(135deg, #2B6CB0, #1E4E8C); }
    .hs-hub__icon--courses { background: linear-gradient(135deg, #3D6FA8, #1E4E8C); }
    .hs-empty {
        margin-top: .55rem; border-radius: 12px; padding: .55rem;
        background: #F7FAFF; color: var(--h-muted); font-size: .68rem; font-weight: 700;
        text-align: center;
    }
</style>
@endpush

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $weekDays = $weekDays ?? collect();
    $todayItems = $todayItems ?? collect();
    $nextAppointment = $nextAppointment ?? null;
@endphp

<div class="hs-home space-y-5">
    <section class="hs-hero">
        <div class="relative flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <p class="text-xs font-black tracking-wide text-[#1E4E8C]/80 mb-1">
                    {{ $isRtl ? 'لوحة الطالب · حصتك' : 'Student panel · Hesetak' }}
                </p>
                <h1 class="text-2xl sm:text-[30px] font-black text-[#152A4A] leading-tight">
                    {{ __('student.welcome_name', ['name' => auth()->user()->name]) }}
                </h1>
                <p class="mt-1 text-sm font-semibold text-[#6B7A99]">
                    @if($nextAppointment)
                        {{ $isRtl ? 'موعدك القادم:' : 'Next up:' }}
                        {{ $nextAppointment->title }}
                        · {{ $nextAppointment->starts_at?->format('g:i A') }}
                    @else
                        {{ $isRtl ? 'احجز حصة فردية أو تابع كورساتك من الاختصارات أدناه.' : 'Book a 1:1 lesson or open your courses from the shortcuts below.' }}
                    @endif
                </p>
            </div>
            @if($todayItems->isNotEmpty())
                <div class="inline-flex items-center gap-2 rounded-full bg-[#C9952A] text-[#152A4A] px-3.5 py-2 text-xs font-black shadow-sm">
                    <i class="fas fa-star"></i>
                    {{ $todayItems->count() }} {{ $isRtl ? 'موعد اليوم' : 'today' }}
                </div>
            @endif
        </div>
    </section>

    <section>
        <div class="flex items-center justify-between mb-3 px-1">
            <h2 class="text-base font-black text-[#152A4A]">{{ $isRtl ? 'تقويمي الأسبوعي' : 'Weekly calendar' }}</h2>
            <p class="text-[11px] font-bold text-[#6B7A99]">{{ $isRtl ? 'تذكير قبل الموعد بـ 30 دقيقة' : 'Reminder 30 min before' }}</p>
        </div>
        <div class="hs-week">
            @foreach($weekDays as $day)
                <article class="hs-day {{ $day->is_today ? 'is-today' : '' }}">
                    <div class="flex items-center justify-between gap-1">
                        <span class="hs-day__badge">{{ $day->date->format('j') }}</span>
                        <div class="text-end min-w-0">
                            <p class="text-[11px] font-black text-[#152A4A] truncate">{{ $day->short }}</p>
                            @if($day->is_today)
                                <p class="text-[10px] font-extrabold text-[#8A6A00]">{{ $isRtl ? 'اليوم' : 'Today' }}</p>
                            @endif
                        </div>
                    </div>

                    @forelse($day->items as $slot)
                        @php
                            $tone = match($slot->color ?? 'blue') {
                                'gold' => 'hs-slot--gold',
                                'teal' => 'hs-slot--teal',
                                default => 'hs-slot--blue',
                            };
                            $href = $slot->join_url
                                ?: (Route::has('student.schedule.join')
                                    ? route('student.schedule.join', ['type' => $slot->type, 'id' => $slot->ref_id])
                                    : '#');
                        @endphp
                        <a href="{{ $href }}" class="hs-slot {{ $tone }}" title="{{ $isRtl ? 'دخول الحصة' : 'Join lesson' }}">
                            <span class="hs-slot__time">
                                <i class="far fa-clock text-[10px] opacity-70"></i>
                                {{ $slot->starts_at?->format('g:i A') }}
                            </span>
                            <span class="hs-slot__title truncate">{{ $slot->title }}</span>
                            <span class="block text-[10px] font-bold text-[#6B7A99] truncate mt-0.5">{{ $slot->subtitle }}</span>
                        </a>
                    @empty
                        <div class="hs-empty">{{ $isRtl ? 'لا مواعيد' : 'Free' }}</div>
                    @endforelse
                </article>
            @endforeach
        </div>
    </section>

    <section>
        <h2 class="text-base font-black text-[#152A4A] mb-3 px-1">{{ $isRtl ? 'اختصارات سريعة' : 'Quick actions' }}</h2>
        <div class="hs-hub">
            @if(Route::has('student.private-lectures.index'))
            <a href="{{ route('student.private-lectures.index') }}" class="hs-hub__card">
                <span class="hs-hub__icon hs-hub__icon--lessons"><i class="fas fa-chalkboard-teacher"></i></span>
                <div>
                    <p class="text-sm font-black text-[#152A4A]">{{ $isRtl ? 'حصصي الخاصة' : 'Private lessons' }}</p>
                    <p class="text-[11px] font-bold text-[#6B7A99] mt-0.5">{{ $isRtl ? 'مواعيدك ودخول الحصة' : 'Schedule & join' }}</p>
                </div>
            </a>
            @endif
            @if(Route::has('student.service-entitlements.index'))
            <a href="{{ route('student.service-entitlements.index') }}" class="hs-hub__card">
                <span class="hs-hub__icon hs-hub__icon--credits"><i class="fas fa-coins"></i></span>
                <div>
                    <p class="text-sm font-black text-[#152A4A]">{{ $isRtl ? 'رصيد الحصص' : 'Credits' }}</p>
                    <p class="text-[11px] font-bold text-[#6B7A99] mt-0.5">{{ $isRtl ? 'باقاتك المتبقية' : 'Remaining packages' }}</p>
                </div>
            </a>
            @endif
            @if(Route::has('public.instructors.index'))
            <a href="{{ route('public.instructors.index') }}" class="hs-hub__card">
                <span class="hs-hub__icon hs-hub__icon--teachers"><i class="fas fa-user-graduate"></i></span>
                <div>
                    <p class="text-sm font-black text-[#152A4A]">{{ $isRtl ? 'ابحث عن معلم' : 'Find a teacher' }}</p>
                    <p class="text-[11px] font-bold text-[#6B7A99] mt-0.5">{{ $isRtl ? 'حجز حصة فردية' : 'Book 1:1' }}</p>
                </div>
            </a>
            @endif
            @if(student_ui('show_courses', true) && Route::has('my-courses.index'))
            <a href="{{ route('my-courses.index') }}" class="hs-hub__card">
                <span class="hs-hub__icon hs-hub__icon--courses"><i class="fas fa-bookmark"></i></span>
                <div>
                    <p class="text-sm font-black text-[#152A4A]">{{ __('student.my_courses') }}</p>
                    <p class="text-[11px] font-bold text-[#6B7A99] mt-0.5">{{ $isRtl ? 'كورساتك المستقلة' : 'Your courses' }}</p>
                </div>
            </a>
            @elseif(Route::has('public.courses'))
            <a href="{{ route('public.courses') }}" class="hs-hub__card">
                <span class="hs-hub__icon hs-hub__icon--courses"><i class="fas fa-compass"></i></span>
                <div>
                    <p class="text-sm font-black text-[#152A4A]">{{ $isRtl ? 'استكشف الكورسات' : 'Browse courses' }}</p>
                    <p class="text-[11px] font-bold text-[#6B7A99] mt-0.5">{{ $isRtl ? 'مسار مستقل' : 'Independent path' }}</p>
                </div>
            </a>
            @endif
        </div>
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @if(Route::has('public.pricing'))
            <a href="{{ route('public.pricing') }}" class="rounded-2xl border border-[#E8EEF8] bg-white px-4 py-4 no-underline text-inherit hover:border-[#C9952A]/50 transition">
                <p class="text-xs font-black text-[#C9952A]">{{ $isRtl ? 'الباقات' : 'Packages' }}</p>
                <p class="mt-1 text-sm font-bold text-[#6B7A99]">{{ $isRtl ? 'اشترِ رصيد حصص واستخدمه مع أي معلم مناسب' : 'Buy session credits and use with any matching teacher' }}</p>
            </a>
        @endif
        @if(Route::has('public.curricula'))
            <a href="{{ route('public.curricula') }}" class="rounded-2xl border border-[#E8EEF8] bg-white px-4 py-4 no-underline text-inherit hover:border-[#1E4E8C]/30 transition">
                <p class="text-xs font-black text-[#1E4E8C]">{{ $isRtl ? 'المناهج' : 'Curricula' }}</p>
                <p class="mt-1 text-sm font-bold text-[#6B7A99]">{{ $isRtl ? 'اختر المرحلة والمادة ونوع المنهج' : 'Pick stage, subject, and curriculum type' }}</p>
            </a>
        @endif
    </section>
</div>
@endsection
