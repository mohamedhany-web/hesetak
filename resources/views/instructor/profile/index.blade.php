@extends('layouts.app')

@section('title', __('instructor.profile') . ' - ' . config('app.name'))
@section('page_title', __('instructor.profile'))

@section('content')
@php
    $user = auth()->user();
    $locale = app()->getLocale();
    $memberSince = $user->created_at
        ? $user->created_at->copy()->locale($locale)->translatedFormat('d F Y')
        : '—';
    $myCoursesCount = \App\Models\AdvancedCourse::where('instructor_id', $user->id)->count();
    $totalStudents = \App\Models\StudentCourseEnrollment::whereHas('course', function ($q) use ($user) {
        $q->where('instructor_id', $user->id);
    })->where('status', 'active')->distinct('user_id')->count();
    $lastLogin = $user->last_login_at
        ? $user->last_login_at->copy()->locale($locale)->diffForHumans()
        : '—';
    $isAcademy = $user->isAcademyWorkingInstructor();
    $publicHref = ($isAcademy && Route::has('public.instructors.show'))
        ? route('public.instructors.show', $user)
        : null;
    $marketingHref = Route::has('instructor.personal-branding.edit')
        ? route('instructor.personal-branding.edit')
        : null;
    $tzOptions = \App\Support\AppTimezone::commonZones();
    $tzCurrent = old('timezone', $user->timezone ?: \App\Support\AppTimezone::academy());
    if ($tzCurrent && ! array_key_exists($tzCurrent, $tzOptions)) {
        $tzOptions = [$tzCurrent => $tzCurrent] + $tzOptions;
    }
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.profile') }}">
        <div class="id-hero__copy" style="display:flex;flex-wrap:wrap;gap:16px;align-items:center">
            <div style="width:72px;height:72px;border-radius:18px;overflow:hidden;border:2px solid rgba(255,255,255,.35);background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                @if($user->profile_image)
                    <img src="{{ $user->profile_image_url }}" alt="" style="width:100%;height:100%;object-fit:cover"
                         onerror="this.style.display='none';this.nextElementSibling?.classList.remove('hidden')">
                    <span class="hidden" style="font-size:28px;font-weight:800;color:#fff">{{ mb_substr($user->name, 0, 1) }}</span>
                @else
                    <span style="font-size:28px;font-weight:800;color:#fff">{{ mb_substr($user->name, 0, 1) }}</span>
                @endif
            </div>
            <div class="min-w-0">
                <p class="id-hero__kicker">{{ __('instructor.instructor_role') }}</p>
                <h2 class="id-hero__title">{{ $user->name }}</h2>
                <p class="id-hero__meta">
                    {{ __('instructor.manage_profile_data') }}
                    @if($user->email)
                        · {{ $user->email }}
                    @endif
                </p>
            </div>
        </div>
        <div class="id-hero__actions">
            @if($publicHref)
                <a href="{{ $publicHref }}" target="_blank" rel="noopener" class="id-btn id-btn--gold">
                    <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                    {{ __('instructor.view_public_profile') }}
                </a>
            @endif
            @if($marketingHref)
                <a href="{{ $marketingHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-bullhorn" aria-hidden="true"></i>
                    {{ __('instructor.personal_branding') }}
                </a>
            @endif
            <a href="{{ route('dashboard') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back_to_dashboard') }}
            </a>
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.profile') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-calendar"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.join_date') }}</span>
                <span class="id-kpi__value" style="font-size:1rem">{{ $memberSince }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-book"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.my_courses') }}</span>
                <span class="id-kpi__value">{{ number_format($myCoursesCount) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-user-graduate"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.students') }}</span>
                <span class="id-kpi__value">{{ number_format($totalStudents) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-sign-in-alt"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.last_login') }}</span>
                <span class="id-kpi__value" style="font-size:1rem">{{ $lastLogin }}</span>
            </span>
        </article>
    </section>

    @if($errors->any())
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="id-cal-grid">
        <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
            <section class="id-panel">
                <header class="id-panel__head">
                    <h2>{{ __('instructor.update_data') }}</h2>
                </header>
                <p class="id-field__hint" style="margin:-4px 0 14px">{{ __('instructor.update_data_subtitle') }}</p>

                <form method="POST" action="{{ route('instructor.profile.update') }}" enctype="multipart/form-data" class="id-form">
                    @csrf
                    @method('PUT')

                    <div class="id-form-grid">
                        <div class="id-field">
                            <label for="name">{{ __('instructor.full_name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="id-input">
                            @error('name')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field">
                            <label for="phone">{{ __('instructor.phone') }}</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" required class="id-input">
                            @error('phone')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field id-field--span2">
                            <label for="email">{{ __('instructor.email_optional') }}</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="id-input">
                            @error('email')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field id-field--span2">
                            <label for="timezone">{{ __('instructor.timezone') }}</label>
                            <select name="timezone" id="timezone" data-timezone-select required class="id-select">
                                @foreach ($tzOptions as $tzId => $tzLabel)
                                    <option value="{{ $tzId }}" @selected($tzCurrent === $tzId)>{{ $tzLabel }}</option>
                                @endforeach
                            </select>
                            <p class="id-field__hint">{{ __('instructor.timezone_hint') }}</p>
                            @error('timezone')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field id-field--span2">
                            <label for="bio">{{ __('instructor.bio_optional') }}</label>
                            <textarea name="bio" id="bio" rows="4" class="id-input"
                                      style="min-height:100px;padding-top:10px;padding-bottom:10px;resize:vertical"
                                      placeholder="{{ __('instructor.bio_placeholder_short') }}">{{ old('bio', $user->bio) }}</textarea>
                            @error('bio')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field id-field--span2">
                            <label>{{ __('instructor.profile_image') }}</label>
                            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
                                <div style="width:88px;height:88px;border-radius:14px;overflow:hidden;border:1px solid #E6EEF8;background:#F7FAFE;display:flex;align-items:center;justify-content:center">
                                    @if($user->profile_image)
                                        <img src="{{ $user->profile_image_url }}" alt="" style="width:100%;height:100%;object-fit:cover"
                                             onerror="this.style.display='none';this.nextElementSibling?.classList.remove('hidden')">
                                        <i class="fas fa-user hidden" style="color:#6B7A93" aria-hidden="true"></i>
                                    @else
                                        <i class="fas fa-user" style="color:#6B7A93" aria-hidden="true"></i>
                                    @endif
                                </div>
                                <label class="id-btn id-btn--outline" style="cursor:pointer">
                                    <i class="fas fa-upload" aria-hidden="true"></i>
                                    {{ __('instructor.choose_image_label') }}
                                    <input type="file" name="profile_image" accept="image/*" class="hidden" style="display:none">
                                </label>
                            </div>
                            @error('profile_image')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="id-slot-card">
                        <div class="id-slot-card__head">
                            <strong style="font-size:13px;font-weight:800;color:#152A4A">{{ __('instructor.change_password') }}</strong>
                        </div>
                        <p class="id-field__hint" style="margin:0 0 12px">{{ __('instructor.leave_empty_if_no_change') }}</p>
                        <div class="id-form-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
                            <div class="id-field">
                                <label for="current_password">{{ __('instructor.current_password') }}</label>
                                <input type="password" name="current_password" id="current_password" class="id-input" autocomplete="current-password">
                                @error('current_password')<p class="id-field__err">{{ $message }}</p>@enderror
                            </div>
                            <div class="id-field">
                                <label for="password">{{ __('instructor.new_password') }}</label>
                                <input type="password" name="password" id="password" class="id-input" autocomplete="new-password">
                                @error('password')<p class="id-field__err">{{ $message }}</p>@enderror
                            </div>
                            <div class="id-field">
                                <label for="password_confirmation">{{ __('instructor.confirm_password') }}</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="id-input" autocomplete="new-password">
                            </div>
                        </div>
                    </div>

                    <div class="id-foot-actions">
                        <a href="{{ route('dashboard') }}" class="id-btn id-btn--outline">
                            <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                            {{ __('instructor.back_to_dashboard') }}
                        </a>
                        <button type="submit" class="id-btn id-btn--navy">
                            <i class="fas fa-save" aria-hidden="true"></i>
                            {{ __('instructor.save_changes') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <aside style="display:flex;flex-direction:column;gap:16px">
            <section class="id-panel">
                <header class="id-panel__head">
                    <h2>{{ __('instructor.account_info') }}</h2>
                </header>
                <div class="id-meta">
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-hashtag" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.membership_number') }}</span>
                        <strong>#{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</strong>
                    </div>
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.account_type') }}</span>
                        <strong><span class="id-chip id-chip--muted">{{ __('instructor.instructor_role') }}</span></strong>
                    </div>
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-info-circle" aria-hidden="true"></i></span>
                        <span>{{ __('common.status') }}</span>
                        <strong>
                            <span class="id-chip {{ $user->is_active ? 'id-chip--ok' : 'id-chip--rose' }}">
                                {{ $user->is_active ? __('instructor.active_status') : __('instructor.not_active') }}
                            </span>
                        </strong>
                    </div>
                    @if($user->phone)
                        <div class="id-meta__row">
                            <span class="id-meta__ico"><i class="fas fa-phone" aria-hidden="true"></i></span>
                            <span>{{ __('instructor.phone') }}</span>
                            <strong dir="ltr">{{ $user->phone }}</strong>
                        </div>
                    @endif
                </div>
            </section>

            @if($isAcademy)
                <section class="id-panel">
                    <header class="id-panel__head">
                        <h2>{{ __('instructor.your_libraries') }}</h2>
                    </header>
                    <p class="id-field__hint" style="margin:-4px 0 12px">{{ __('instructor.your_libraries_desc') }}</p>
                    <div class="id-list">
                        @if(Route::has('instructor.libraries.materials.index'))
                            <a href="{{ route('instructor.libraries.materials.index') }}" class="id-list__row" style="text-decoration:none;color:inherit">
                                <span class="id-list__ico" aria-hidden="true"><i class="fas fa-file-upload"></i></span>
                                <div class="id-list__body">
                                    <div class="id-list__title">{{ __('instructor.upload_materials') }}</div>
                                </div>
                                <i class="fas fa-chevron-{{ $locale === 'ar' ? 'left' : 'right' }} id-act__chev" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if(Route::has('instructor.libraries.curriculum.index'))
                            <a href="{{ route('instructor.libraries.curriculum.index') }}" class="id-list__row" style="text-decoration:none;color:inherit">
                                <span class="id-list__ico id-list__ico--gold" aria-hidden="true"><i class="fas fa-book-open"></i></span>
                                <div class="id-list__body">
                                    <div class="id-list__title">{{ __('instructor.view_academy_curriculum') }}</div>
                                </div>
                                <i class="fas fa-chevron-{{ $locale === 'ar' ? 'left' : 'right' }} id-act__chev" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if(instructor_ui('show_courses', false) && Route::has('instructor.courses.index'))
                            <a href="{{ route('instructor.courses.index') }}" class="id-list__row" style="text-decoration:none;color:inherit">
                                <span class="id-list__ico id-list__ico--teal" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                                <div class="id-list__body">
                                    <div class="id-list__title">{{ __('instructor.build_course_curriculum') }}</div>
                                </div>
                                <i class="fas fa-chevron-{{ $locale === 'ar' ? 'left' : 'right' }} id-act__chev" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if(Route::has('instructor.libraries.videos.index'))
                            <a href="{{ route('instructor.libraries.videos.index') }}" class="id-list__row" style="text-decoration:none;color:inherit">
                                <span class="id-list__ico" aria-hidden="true"><i class="fas fa-video"></i></span>
                                <div class="id-list__body">
                                    <div class="id-list__title">{{ __('instructor.video_library') }}</div>
                                </div>
                                <i class="fas fa-chevron-{{ $locale === 'ar' ? 'left' : 'right' }} id-act__chev" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                </section>
            @endif

            <section class="id-panel">
                <header class="id-panel__head">
                    <h2>{{ __('instructor.tips_for_instructor') }}</h2>
                </header>
                <div class="id-list">
                    <article class="id-list__row">
                        <span class="id-list__ico id-list__ico--teal" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                        <div class="id-list__body">
                            <div class="id-list__title">{{ __('instructor.update_bio') }}</div>
                            <div class="id-list__meta">{{ __('instructor.add_bio_for_students') }}</div>
                        </div>
                    </article>
                    <article class="id-list__row">
                        <span class="id-list__ico id-list__ico--gold" aria-hidden="true"><i class="fas fa-lock"></i></span>
                        <div class="id-list__body">
                            <div class="id-list__title">{{ __('instructor.strong_password') }}</div>
                            <div class="id-list__meta">{{ __('instructor.change_password_regularly') }}</div>
                        </div>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
