@extends('layouts.app')

@section('title', __('instructor.personal_branding') . ' - ' . config('app.name'))
@section('page_title', __('instructor.personal_branding'))

@section('content')
@php
    $plLocale = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $locale = app()->getLocale();
    $user = auth()->user();

    $skillsPreview = $profile->skills_list;
    if (old('skills') !== null) {
        $split = preg_split('/[\r\n,،]+/u', old('skills'), -1, PREG_SPLIT_NO_EMPTY);
        $skillsPreview = array_values(array_filter(array_map('trim', $split)));
    }

    $hasPhoto = filled($profile->photo_path);
    $hasHeadline = filled(old('headline', $profile->headline));
    $hasBio = filled(old('bio', $profile->bio));
    $skillsCount = count($skillsPreview);
    $matchCompleteness = $matchCompleteness ?? \App\Support\InstructorMatchCompleteness::evaluate($profile, $user);
    $matchDone = collect($matchCompleteness['checklist'] ?? [])->filter()->count();
    $matchTotal = max(1, count($matchCompleteness['checklist'] ?? []));
    $readyForReview = (bool) ($matchCompleteness['ok'] ?? false);
    $checklistDone = $matchDone;

    $statusChip = match ($profile->status) {
        'approved' => 'id-chip--ok',
        'pending_review' => 'id-chip--warn',
        'rejected' => 'id-chip--rose',
        default => 'id-chip--muted',
    };
    $statusIcon = match ($profile->status) {
        'approved' => 'fa-check-circle',
        'pending_review' => 'fa-hourglass-half',
        'rejected' => 'fa-times-circle',
        default => 'fa-pen',
    };
    $statusKpiTone = match ($profile->status) {
        'approved' => 'id-kpi__icon--teal',
        'pending_review' => 'id-kpi__icon--gold',
        'rejected' => 'id-kpi__icon--rose',
        default => '',
    };

    $publicHref = ($profile->status === \App\Models\InstructorProfile::STATUS_APPROVED && Route::has('public.instructors.show'))
        ? route('public.instructors.show', $profile->user_id)
        : null;
    $profileHref = Route::has('instructor.profile')
        ? route('instructor.profile')
        : route('dashboard');
    $canSubmit = in_array($profile->status, [
        \App\Models\InstructorProfile::STATUS_DRAFT,
        \App\Models\InstructorProfile::STATUS_REJECTED,
    ], true);
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.personal_branding') }}">
        <div class="id-hero__copy" style="display:flex;flex-wrap:wrap;gap:16px;align-items:center">
            <div style="width:72px;height:72px;border-radius:18px;overflow:hidden;border:2px solid rgba(255,255,255,.35);background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                @if($hasPhoto)
                    <img src="{{ $profile->photo_url }}" alt="" style="width:100%;height:100%;object-fit:cover"
                         onerror="this.style.display='none';this.nextElementSibling?.classList.remove('hidden')">
                    <span class="hidden" style="font-size:28px;font-weight:800;color:#fff">{{ mb_substr($user->name, 0, 1) }}</span>
                @else
                    <span style="font-size:28px;font-weight:800;color:#fff">{{ mb_substr($user->name, 0, 1) }}</span>
                @endif
            </div>
            <div class="min-w-0">
                <p class="id-hero__kicker">{{ __('instructor.personal_branding') }}</p>
                <h2 class="id-hero__title">{{ __('instructor.profile_branding_title') }}</h2>
                <p class="id-hero__meta">{{ __('instructor.personal_branding_desc') }}</p>
            </div>
        </div>
        <div class="id-hero__actions">
            @if($publicHref)
                <a href="{{ $publicHref }}" target="_blank" rel="noopener" class="id-btn id-btn--gold">
                    <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                    {{ __('instructor.view_public_profile') }}
                </a>
            @endif
            <a href="{{ $profileHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-user" aria-hidden="true"></i>
                {{ __('instructor.profile') }}
            </a>
            <a href="{{ route('dashboard') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back_to_dashboard') }}
            </a>
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.status_label') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon {{ $statusKpiTone }}" aria-hidden="true"><i class="fas {{ $statusIcon }}"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.status_label') }}</span>
                <span class="id-kpi__value" style="font-size:1.05rem">{{ \App\Models\InstructorProfile::statusLabel($profile->status) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-list-check"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">اكتمال المطابقة</span>
                <span class="id-kpi__value">{{ $matchDone }}/{{ $matchTotal }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-tags"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.skills') }}</span>
                <span class="id-kpi__value">{{ number_format($skillsCount) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon {{ $readyForReview ? 'id-kpi__icon--teal' : 'id-kpi__icon--rose' }}" aria-hidden="true">
                <i class="fas {{ $readyForReview ? 'fa-paper-plane' : 'fa-exclamation' }}"></i>
            </span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.submit_for_review') }}</span>
                <span class="id-kpi__value" style="font-size:1rem">
                    {{ $readyForReview ? __('instructor.active_status') : __('instructor.draft') }}
                </span>
            </span>
        </article>
    </section>

    @if(session('success'))
        <div class="id-alert id-alert--ok" role="status">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif
    @if($profile->rejection_reason)
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-comment-dots" aria-hidden="true"></i>
            <span>
                <strong>{{ __('instructor.rejection_reason_label') }}:</strong>
                {{ $profile->rejection_reason }}
            </span>
        </div>
    @endif
    @if($publicHref)
        <div class="id-alert id-alert--info" role="status">
            <i class="fas fa-globe" aria-hidden="true"></i>
            <span>
                {{ __('instructor.profile_approved_public') }}
                <a href="{{ $publicHref }}" target="_blank" rel="noopener" style="font-weight:800;text-decoration:underline;color:inherit">
                    {{ __('instructor.view_public_profile') }}
                </a>
            </span>
        </div>
    @endif

    <form method="POST" action="{{ route('instructor.personal-branding.update') }}" enctype="multipart/form-data" class="id-form" style="display:flex;flex-direction:column;gap:16px">
        @csrf
        @method('PUT')

        <section class="id-panel" aria-label="{{ __('instructor.profile_photo') }}">
            <header class="id-panel__head">
                <div>
                    <h2>{{ __('instructor.intro_title') }}</h2>
                    <p class="id-panel__hint">{{ __('instructor.personal_branding_desc') }}</p>
                </div>
                <span class="id-chip {{ $statusChip }}">{{ \App\Models\InstructorProfile::statusLabel($profile->status) }}</span>
            </header>

            <div class="id-form-grid">
                <div class="id-field id-field--span2">
                    <label>{{ __('instructor.profile_photo') }}</label>
                    <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:center">
                        <div style="width:96px;height:96px;border-radius:16px;overflow:hidden;border:1px solid #E6EEF8;background:#F7FAFE;display:flex;align-items:center;justify-content:center;position:relative">
                            @if($hasPhoto)
                                <img src="{{ $profile->photo_url }}" alt="{{ __('instructor.profile_photo_alt') }}"
                                     style="width:100%;height:100%;object-fit:cover"
                                     onerror="this.style.display='none';this.nextElementSibling?.classList.remove('hidden')">
                                <i class="fas fa-user hidden" style="color:#6B7A93;font-size:28px" aria-hidden="true"></i>
                            @else
                                <i class="fas fa-user" style="color:#6B7A93;font-size:28px" aria-hidden="true"></i>
                            @endif
                        </div>
                        <div style="min-width:0;flex:1">
                            <label class="id-btn id-btn--outline" style="cursor:pointer;display:inline-flex">
                                <i class="fas fa-upload" aria-hidden="true"></i>
                                {{ __('instructor.choose_image_label') }}
                                <input type="file" name="photo" accept="image/*" class="hidden" style="display:none">
                            </label>
                            <p class="id-field__hint" style="margin-top:8px">JPG / PNG · حتى 2 ميجابايت</p>
                        </div>
                    </div>
                    @error('photo')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>

                <div class="id-field id-field--span2">
                    <label for="intro_video_url">{{ __('instructor.intro_video_url') }}</label>
                    <input type="url" name="intro_video_url" id="intro_video_url"
                           value="{{ old('intro_video_url', $user->portfolio_intro_video_url) }}"
                           dir="ltr" class="id-input" placeholder="https://youtube.com/...">
                    <p class="id-field__hint">{{ __('instructor.intro_video_hint') }}</p>
                    @error('intro_video_url')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>

                <div class="id-field id-field--span2">
                    <label for="headline">{{ __('instructor.intro_title') }}</label>
                    <input type="text" name="headline" id="headline"
                           value="{{ old('headline', $profile->headline) }}"
                           class="id-input" placeholder="{{ __('instructor.headline_placeholder') }}">
                    @error('headline')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>

                <div class="id-field id-field--span2">
                    <label for="bio">{{ __('instructor.bio') }}</label>
                    <textarea name="bio" id="bio" rows="5" class="id-input"
                              style="min-height:120px;padding-top:10px;padding-bottom:10px;resize:vertical"
                              placeholder="{{ __('instructor.bio_placeholder') }}">{{ old('bio', $profile->bio) }}</textarea>
                    @error('bio')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>

                <div class="id-field id-field--span2">
                    <label for="experience">{{ __('instructor.experience') }}</label>
                    <textarea name="experience" id="experience" rows="8" class="id-input"
                              style="min-height:160px;padding-top:10px;padding-bottom:10px;resize:vertical"
                              placeholder="{{ __('instructor.experience_placeholder') }}">{{ old('experience', $profile->experience) }}</textarea>
                    @error('experience')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>

                <div class="id-field id-field--span2">
                    <label for="skills">{{ __('instructor.skills') }}</label>
                    <p class="id-field__hint" style="margin:0 0 8px">{{ __('instructor.skills_hint') }}</p>
                    <textarea name="skills" id="skills" rows="5" class="id-input"
                              style="min-height:110px;padding-top:10px;padding-bottom:10px;resize:vertical"
                              placeholder="{{ __('instructor.skills_placeholder') }}">{{ old('skills', $profile->skills) }}</textarea>
                    @error('skills')<p class="id-field__err">{{ $message }}</p>@enderror
                    @if($skillsCount > 0)
                        <p class="id-field__hint" style="margin:10px 0 6px">
                            {{ __('instructor.skills_preview') }} ({{ $skillsCount }} {{ __('instructor.skill_count') }}):
                        </p>
                        <div style="display:flex;flex-wrap:wrap;gap:6px">
                            @foreach($skillsPreview as $skill)
                                <span class="id-chip id-chip--muted">{{ $skill }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="id-panel" aria-label="{{ __('instructor.match_profile_title') }}">
            <header class="id-panel__head">
                <div>
                    <h2>{{ __('instructor.match_profile_title') }}</h2>
                    <p class="id-panel__hint">{{ __('instructor.match_profile_desc') }}</p>
                </div>
            </header>

            @unless($matchCompleteness['ok'] ?? false)
                <div class="id-alert id-alert--warn" role="status" style="margin-bottom:16px">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <span>أكمل قبل الإرسال أو النشر: {{ implode(' · ', $matchCompleteness['missing'] ?? []) }}</span>
                </div>
            @endunless

            @php
                $selectedTypes = old('curriculum_types', $profile->curriculumTypeKeys());
                if (! is_array($selectedTypes)) { $selectedTypes = []; }
                $selectedYearIds = old('teaching_year_ids', $user->teachingLearningPaths()->pluck('academic_years.id')->all());
                if (! is_array($selectedYearIds)) { $selectedYearIds = []; }
                $selectedYearIds = array_map('intval', $selectedYearIds);
                $selectedSubjectIds = old('teaching_subject_ids', $profile->teachingSubjectIds());
                if (! is_array($selectedSubjectIds)) { $selectedSubjectIds = []; }
                $selectedSubjectIds = array_map('intval', $selectedSubjectIds);
                $curriculumTypes = $curriculumTypes ?? \App\Support\HesetakMatchCatalog::curriculumTypes();
                $publicYears = $publicYears ?? collect();
                $subjects = $subjects ?? collect();
                $plGender = old('gender', $user->gender);
            @endphp

            <div class="id-field" style="margin-bottom:18px">
                <label>{{ __('instructor.match_curriculum_types') }}</label>
                <p class="id-field__hint" style="margin:0 0 8px">{{ __('instructor.match_curriculum_hint') }}</p>
                <div class="id-check-row" style="margin-top:8px;flex-wrap:wrap">
                    @forelse($curriculumTypes as $type)
                        <label class="id-check">
                            <input type="checkbox" name="curriculum_types[]" value="{{ $type['key'] }}"
                                   @checked(in_array($type['key'], $selectedTypes, true))>
                            {{ $type['label'] }}
                        </label>
                    @empty
                        <p class="id-field__hint">لا أنواع منهج مفعّلة بعد — تضبطها الإدارة.</p>
                    @endforelse
                </div>
                @error('curriculum_types')<p class="id-field__err">{{ $message }}</p>@enderror
            </div>

            <div class="id-field" style="margin-bottom:18px">
                <label>{{ __('instructor.match_stages') }}</label>
                <p class="id-field__hint" style="margin:0 0 8px">{{ __('instructor.match_stages_hint') }}</p>
                <div class="id-check-row" style="margin-top:8px;flex-wrap:wrap">
                    @forelse($publicYears as $year)
                        <label class="id-check">
                            <input type="checkbox" name="teaching_year_ids[]" value="{{ $year->id }}"
                                   @checked(in_array((int) $year->id, $selectedYearIds, true))>
                            {{ $year->name }}
                        </label>
                    @empty
                        <p class="id-field__hint">لا مراحل عامة منشورة بعد.</p>
                    @endforelse
                </div>
            </div>

            <div class="id-field" style="margin-bottom:18px">
                <label>{{ __('instructor.match_subjects') }}</label>
                <p class="id-field__hint" style="margin:0 0 8px">{{ __('instructor.match_subjects_hint') }}</p>
                <div class="id-check-row" style="margin-top:8px;flex-wrap:wrap;max-height:220px;overflow:auto;padding:4px">
                    @forelse($subjects as $subject)
                        <label class="id-check">
                            <input type="checkbox" name="teaching_subject_ids[]" value="{{ $subject->id }}"
                                   @checked(in_array((int) $subject->id, $selectedSubjectIds, true))>
                            {{ $subject->name }}
                        </label>
                    @empty
                        <p class="id-field__hint">لا مواد نشطة في الكتالوج العام.</p>
                    @endforelse
                </div>
                @error('teaching_subject_ids')<p class="id-field__err">{{ $message }}</p>@enderror
            </div>

            <div class="id-field">
                <label>{{ __('instructor.teacher_gender') }} <span style="font-weight:600;color:#6B7A93">({{ __('instructor.optional') }})</span></label>
                <div class="id-check-row" style="margin-top:8px;flex-wrap:wrap">
                    @foreach(config('private_lessons.genders', ['female' => ['ar' => 'معلمة', 'en' => 'Female'], 'male' => ['ar' => 'معلم', 'en' => 'Male']]) as $gKey => $gLabels)
                        <label class="id-check">
                            <input type="radio" name="gender" value="{{ $gKey }}" @checked($plGender === $gKey)>
                            {{ $gLabels[$plLocale] ?? $gKey }}
                        </label>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="id-panel" aria-label="{{ __('instructor.consultations_optional') }}">
            <header class="id-panel__head">
                <div>
                    <h2>{{ __('instructor.consultations_optional') }}</h2>
                    <p class="id-panel__hint">{{ __('instructor.consultations_optional_desc') }}</p>
                </div>
            </header>

            <div class="id-form-grid">
                <div class="id-field">
                    <label for="consultation_price_egp">{{ __('instructor.price_egp') }}</label>
                    <input type="number" step="0.01" min="0" max="999999.99"
                           name="consultation_price_egp" id="consultation_price_egp"
                           value="{{ old('consultation_price_egp', $profile->consultation_price_egp) }}"
                           class="id-input" dir="ltr">
                    @error('consultation_price_egp')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
                <div class="id-field">
                    <label for="consultation_duration_minutes">{{ __('instructor.duration_minutes_label') }}</label>
                    <input type="number" min="15" max="480"
                           name="consultation_duration_minutes" id="consultation_duration_minutes"
                           value="{{ old('consultation_duration_minutes', $profile->consultation_duration_minutes) }}"
                           class="id-input" dir="ltr">
                    @error('consultation_duration_minutes')<p class="id-field__err">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="id-foot-actions" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between">
            <p class="id-field__hint" style="margin:0;max-width:28rem">
                @if($canSubmit && ! $readyForReview)
                    أكمل: عنوان تعريفي + نبذة + 3 مهارات على الأقل قبل الإرسال للمراجعة.
                @elseif($canSubmit)
                    جاهز للإرسال — احفظ التعديلات ثم أرسل للمراجعة.
                @else
                    التعديلات تُحفظ فوراً. النشر العام بعد اعتماد الإدارة.
                @endif
            </p>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-save" aria-hidden="true"></i>
                    {{ __('instructor.save_changes') }}
                </button>
            </div>
        </div>
    </form>

    @if($canSubmit)
        <section class="id-panel" style="margin-top:4px" aria-label="{{ __('instructor.submit_for_review') }}">
            <header class="id-panel__head">
                <div>
                    <h2>{{ __('instructor.submit_for_review') }}</h2>
                    <p class="id-panel__hint">
                        @if($readyForReview)
                            بعد الإرسال ستراجع الإدارة الملف قبل ظهوره للطلاب.
                        @else
                            أكمل الحد الأدنى (عنوان + نبذة + 3 مهارات) ثم احفظ وأعد المحاولة.
                        @endif
                    </p>
                </div>
                <span class="id-chip {{ $readyForReview ? 'id-chip--ok' : 'id-chip--warn' }}">
                    {{ $readyForReview ? __('instructor.active_status') : __('instructor.draft') }}
                </span>
            </header>
            <form method="POST" action="{{ route('instructor.personal-branding.submit') }}">
                @csrf
                <button type="submit" class="id-btn id-btn--gold" @disabled(! $readyForReview)
                        style="{{ $readyForReview ? '' : 'opacity:.55;cursor:not-allowed' }}">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                    {{ __('instructor.submit_for_review') }}
                </button>
            </form>
        </section>
    @endif
</div>
@endsection
