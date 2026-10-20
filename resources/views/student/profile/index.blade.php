@extends('layouts.student-timeline')

@section('title', __('student_timeline.profile'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $avatarUrl = $user->avatarDisplayUrl();

    $roleKey = match ($user->role ?? '') {
        'teacher' => 'role_teacher',
        'admin' => 'role_admin',
        'super_admin' => 'role_super_admin',
        default => 'role_student',
    };

    $memberSince = $user->created_at
        ? $user->created_at->copy()->locale($locale)->translatedFormat($isRtl ? 'd F Y' : 'M d, Y')
        : '—';

    $lastLogin = $user->last_login_at
        ? $user->last_login_at->copy()->locale($locale)->diffForHumans()
        : null;

    $memberId = (string) ($user->uuid ?: '');
    $studentPublicCode = ($user->role ?? '') === 'student' ? $user->ensureStudentPublicCode() : '';
    $progressShareToken = $studentPublicCode !== '' ? $studentPublicCode : $user->ensureProgressShareToken();
    $progressShareUrl = $user->progressShareUrl();
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.profile'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_settings'), 'url' => route('settings')],
        ['label' => __('student_timeline.profile'), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="st-flash st-flash--err">{{ $errors->first() }}</div>
@endif

<section class="st-settings-hero" aria-label="{{ __('student_timeline.profile') }}">
    <img class="st-settings-hero__avatar" src="{{ $avatarUrl }}" alt="" width="56" height="56">
    <div class="st-settings-hero__copy">
        <h2>{{ $user->name }}</h2>
        <p>{{ $user->email ?: ($user->phone ?: __('student_timeline.'.$roleKey)) }}</p>
    </div>
    <div class="st-settings-hero__actions">
        <a href="{{ route('settings') }}" class="st-pill st-pill--outline">{{ __('student_timeline.nav_settings') }}</a>
        <a href="{{ route('dashboard') }}" class="st-pill st-pill--solid">{{ __('student_timeline.school_gate') }}</a>
    </div>
</section>

<section class="st-class-id" aria-label="{{ __('student_timeline.class_user_id') }}">
    <div class="st-class-id__copy">
        <p class="st-class-id__kicker">{{ __('student_timeline.class_user_id') }}</p>
        <p class="st-class-id__value" dir="ltr" id="stClassUserId" style="font-size:1.35rem;letter-spacing:.06em">{{ $progressShareToken }}</p>
        <p class="st-class-id__hint">{{ __('student_timeline.class_user_id_hint') }}</p>
        <p class="st-class-id__hint" dir="ltr" style="margin-top:0.35rem;font-size:0.75rem;opacity:0.85;word-break:break-all">{{ $progressShareUrl }}</p>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem">
        <button type="button" class="st-pill st-pill--solid" id="stCopyClassUserId" data-copy="{{ $progressShareToken }}">
            <i class="fas fa-copy" aria-hidden="true"></i>
            {{ __('student_timeline.copy_user_id') }}
        </button>
        <button type="button" class="st-pill st-pill--outline" id="stCopyParentShareLink" data-copy="{{ $progressShareUrl }}">
            <i class="fas fa-link" aria-hidden="true"></i>
            {{ __('student_timeline.copy_share_link') }}
        </button>
    </div>
</section>

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student_timeline.profile_title') }}</h2>
        <p>{{ __('student_timeline.profile_hint') }}</p>
    </div>
</section>

<section class="st-profile-meta" aria-label="{{ __('student_timeline.profile_summary') }}">
    <div class="st-profile-meta__item st-profile-meta__item--accent">
        <span>{{ __('student_timeline.class_user_id') }}</span>
        <strong dir="ltr">{{ $progressShareToken }}</strong>
    </div>
    <div class="st-profile-meta__item">
        <span>{{ __('student_timeline.member_id') }}</span>
        <strong dir="ltr" style="font-size:0.75rem;word-break:break-all">{{ \Illuminate\Support\Str::limit($memberId, 13, '…') }}</strong>
    </div>
    <div class="st-profile-meta__item">
        <span>{{ __('student_timeline.account_type') }}</span>
        <strong>{{ __('student_timeline.'.$roleKey) }}</strong>
    </div>
    <div class="st-profile-meta__item">
        <span>{{ __('student_timeline.join_date') }}</span>
        <strong>{{ $memberSince }}</strong>
    </div>
</section>

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="st-profile-form">
    @csrf
    @method('PUT')

    <section class="st-settings-block" aria-labelledby="stProfBasics">
        <div class="st-settings-block__head">
            <span class="st-settings-block__icon" aria-hidden="true"><i class="fas fa-user"></i></span>
            <div>
                <h3 id="stProfBasics">{{ __('student_timeline.profile_basics') }}</h3>
                <p>{{ __('student_timeline.profile_basics_hint') }}</p>
            </div>
        </div>

        <div class="st-profile-grid">
            <label class="st-field">
                <span>{{ __('student_timeline.full_name') }}</span>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                @error('name')<small class="st-field__err">{{ $message }}</small>@enderror
            </label>

            <label class="st-field">
                <span>{{ __('student_timeline.phone') }}</span>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required autocomplete="tel" dir="ltr">
                @error('phone')<small class="st-field__err">{{ $message }}</small>@enderror
            </label>

            <label class="st-field st-field--full">
                <span>{{ __('student_timeline.email_optional') }}</span>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" dir="ltr">
                @error('email')<small class="st-field__err">{{ $message }}</small>@enderror
            </label>

            <label class="st-field st-field--full">
                <span>{{ app()->getLocale() === 'ar' ? 'المنطقة الزمنية' : 'Timezone' }}</span>
                @php
                    $tzOptions = \App\Support\AppTimezone::commonZones();
                    $tzCurrent = old('timezone', $user->timezone ?: \App\Support\AppTimezone::academy());
                    if ($tzCurrent && ! array_key_exists($tzCurrent, $tzOptions)) {
                        $tzOptions = [$tzCurrent => $tzCurrent] + $tzOptions;
                    }
                @endphp
                <select name="timezone" data-timezone-select required>
                    @foreach ($tzOptions as $tzId => $tzLabel)
                        <option value="{{ $tzId }}" @selected($tzCurrent === $tzId)>{{ $tzLabel }}</option>
                    @endforeach
                </select>
                <small class="st-field__hint" style="opacity:.75;display:block;margin-top:.35rem">
                    {{ app()->getLocale() === 'ar'
                        ? 'تُعرض مواعيد الحصص والفصول بتوقيتك. عند تحديد موعد يمكنك اختيار توقيت البلد (مصر أو أمريكا أو غيرها).'
                        : 'Class times are shown in your timezone. When scheduling, pick the country timezone the clock should use (Egypt, US, etc.).' }}
                </small>
                @error('timezone')<small class="st-field__err">{{ $message }}</small>@enderror
            </label>

            @php
                $academicYears = $academicYears ?? \App\Support\StudentLearningProfile::publicYears();
                $gradesByYear = $gradesByYear ?? \App\Support\StudentLearningProfile::gradesByYearMap($academicYears);
                $curriculumTypes = $curriculumTypes ?? \App\Support\HesetakMatchCatalog::curriculumTypes();
            @endphp
            <div
                x-data="{
                    yearId: @js((string) old('academic_year_id', $user->academic_year_id)),
                    gradeId: @js((string) old('academic_grade_id', $user->academic_grade_id)),
                    map: @js($gradesByYear),
                    placeholder: @js(__('auth.academic_grade_placeholder')),
                    get grades() { return this.map[String(this.yearId)] || []; },
                    get hasGrades() { return this.grades.length > 0; },
                    fillGrades() {
                        const select = this.$refs.gradeSelect;
                        if (!select) return;
                        const current = String(this.gradeId || '');
                        select.innerHTML = '';
                        const empty = document.createElement('option');
                        empty.value = '';
                        empty.textContent = this.placeholder;
                        select.appendChild(empty);
                        this.grades.forEach((grade) => {
                            const option = document.createElement('option');
                            option.value = String(grade.id);
                            option.textContent = grade.name;
                            if (current && current === String(grade.id)) option.selected = true;
                            select.appendChild(option);
                        });
                        if (!this.grades.some((grade) => String(grade.id) === current)) {
                            this.gradeId = '';
                            select.value = '';
                        }
                        select.required = this.hasGrades;
                    }
                }"
                x-init="fillGrades()"
            >
                <label class="st-field">
                    <span>{{ __('auth.academic_stage') }}</span>
                    <select name="academic_year_id" x-model="yearId" @change="fillGrades()">
                        <option value="">{{ __('auth.academic_stage_placeholder') }}</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                    <small class="st-field__hint" style="opacity:.75;display:block;margin-top:.35rem">{{ __('auth.academic_stage_hint') }}</small>
                    @error('academic_year_id')<small class="st-field__err">{{ $message }}</small>@enderror
                </label>
                <label class="st-field" x-show="hasGrades" x-cloak>
                    <span>{{ __('auth.academic_grade') }}</span>
                    <select name="academic_grade_id" x-ref="gradeSelect" x-model="gradeId">
                        <option value="">{{ __('auth.academic_grade_placeholder') }}</option>
                    </select>
                    <small class="st-field__hint" style="opacity:.75;display:block;margin-top:.35rem">{{ __('auth.academic_grade_hint') }}</small>
                    @error('academic_grade_id')<small class="st-field__err">{{ $message }}</small>@enderror
                </label>
            </div>
            <label class="st-field">
                <span>{{ __('auth.curriculum_type') }}</span>
                <select name="preferred_curriculum_type">
                    <option value="">{{ __('auth.curriculum_type_placeholder') }}</option>
                    @foreach($curriculumTypes as $key => $meta)
                        <option value="{{ $key }}" @selected((string) old('preferred_curriculum_type', $user->preferred_curriculum_type) === (string) $key)>{{ $meta['label'] ?? $key }}</option>
                    @endforeach
                </select>
                <small class="st-field__hint" style="opacity:.75;display:block;margin-top:.35rem">{{ __('auth.curriculum_type_hint') }}</small>
                @error('preferred_curriculum_type')<small class="st-field__err">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="st-settings-block" aria-labelledby="stProfPhoto">
        <div class="st-settings-block__head">
            <span class="st-settings-block__icon" aria-hidden="true"><i class="fas fa-camera"></i></span>
            <div>
                <h3 id="stProfPhoto">{{ __('student_timeline.profile_photo') }}</h3>
                <p>{{ __('student_timeline.profile_photo_hint') }}</p>
            </div>
        </div>

        <div class="st-profile-photo">
            <img src="{{ $avatarUrl }}" alt="" width="88" height="88" id="stProfPreview">
            <div class="st-profile-photo__actions">
                <label class="st-pill st-pill--solid st-profile-upload">
                    <i class="fas fa-upload" aria-hidden="true"></i>
                    {{ __('student_timeline.choose_photo') }}
                    <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif" id="stProfFile">
                </label>
                <p>{{ __('student_timeline.photo_limit') }}</p>
                @error('profile_image')<small class="st-field__err">{{ $message }}</small>@enderror
            </div>
        </div>
    </section>

    <section class="st-settings-block" aria-labelledby="stProfPass">
        <div class="st-settings-block__head">
            <span class="st-settings-block__icon" aria-hidden="true"><i class="fas fa-key"></i></span>
            <div>
                <h3 id="stProfPass">{{ __('student_timeline.change_password') }}</h3>
                <p>{{ __('student_timeline.change_password_hint') }}</p>
            </div>
        </div>

        <div class="st-profile-grid">
            <label class="st-field">
                <span>{{ __('student_timeline.current_password') }}</span>
                <input type="password" name="current_password" autocomplete="current-password">
                @error('current_password')<small class="st-field__err">{{ $message }}</small>@enderror
            </label>

            <label class="st-field">
                <span>{{ __('student_timeline.new_password') }}</span>
                <input type="password" name="password" autocomplete="new-password">
                @error('password')<small class="st-field__err">{{ $message }}</small>@enderror
            </label>

            <label class="st-field">
                <span>{{ __('student_timeline.confirm_password') }}</span>
                <input type="password" name="password_confirmation" autocomplete="new-password">
            </label>
        </div>
    </section>

    <div class="st-profile-foot">
        <a href="{{ route('settings') }}" class="st-pill st-pill--outline">{{ __('student_timeline.cancel') }}</a>
        <button type="submit" class="st-pill st-pill--solid">
            <i class="fas fa-save" aria-hidden="true"></i>
            {{ __('student_timeline.save_profile') }}
        </button>
    </div>
</form>
@endsection

@section('events')
<div class="st-events__top">
    <h2>{{ __('student_timeline.quick_links') }}</h2>
</div>

@if(($user->role ?? '') === 'parent')
<section class="st-event-card st-event-card--blue" style="margin-bottom:1rem">
    <h3>{{ app()->getLocale() === 'ar' ? 'الأبناء' : 'Children' }}</h3>
    <p class="st-event-card__sub">{{ app()->getLocale() === 'ar' ? 'كل ابن بحساب وبريد خاص، مربوط بحسابك. لا يُعاد استخدام بريد ولي الأمر.' : 'Each child has their own account and email, linked to you. The parent email is not reused.' }}</p>
    @forelse(($children ?? collect()) as $child)
        <p style="margin:.4rem 0">
            <strong>{{ $child->name }}</strong>
            <span dir="ltr">{{ $child->ensureStudentPublicCode() }}</span>
            · {{ \App\Support\LocalizedCatalogLabel::text($child->academicYear->name ?? '') }}
            <a href="{{ route('public.parent-progress', ['code' => $child->student_public_code]) }}">{{ app()->getLocale() === 'ar' ? 'تقرير ولي الأمر' : 'Parent report' }}</a>
            <a href="{{ route('student.learning-desk', ['child' => $child->id]) }}">{{ app()->getLocale() === 'ar' ? 'حصصه' : 'Lessons' }}</a>
        </p>
    @empty
        <p class="st-event-card__sub">{{ app()->getLocale() === 'ar' ? 'لم يُضف أبناء بعد.' : 'No children yet.' }}</p>
    @endforelse
    <form method="POST" action="{{ route('profile.children.store') }}" class="st-form" style="margin-top:1rem">
        @csrf
        <label class="st-field"><span>{{ app()->getLocale() === 'ar' ? 'اسم الابن' : 'Child name' }}</span><input name="child_name" value="{{ old('child_name') }}" required></label>
        <label class="st-field"><span>{{ app()->getLocale() === 'ar' ? 'بريد الابن' : 'Child email' }}</span><input type="email" name="child_email" value="{{ old('child_email') }}" required></label>
        <label class="st-field"><span>{{ app()->getLocale() === 'ar' ? 'كلمة مرور الابن' : 'Child password' }}</span><input type="password" name="child_password" required minlength="8"></label>
        <label class="st-field"><span>{{ app()->getLocale() === 'ar' ? 'تأكيد كلمة المرور' : 'Confirm password' }}</span><input type="password" name="child_password_confirmation" required minlength="8"></label>
        <label class="st-field">
            <span>{{ __('auth.academic_stage') }}</span>
            <select name="child_academic_year_id" required>
                <option value="">{{ __('auth.academic_stage_placeholder') }}</option>
                @foreach(($academicYears ?? collect()) as $year)
                    <option value="{{ $year->id }}" @selected((string) old('child_academic_year_id') === (string) $year->id)>{{ \App\Support\LocalizedCatalogLabel::text($year->name) }}</option>
                @endforeach
            </select>
        </label>
        <label class="st-field">
            <span>{{ __('auth.curriculum_type') }}</span>
            <select name="child_preferred_curriculum_type" required>
                <option value="">{{ __('auth.curriculum_type_placeholder') }}</option>
                @foreach(($curriculumTypes ?? []) as $key => $meta)
                    <option value="{{ $key }}" @selected(old('child_preferred_curriculum_type') === $key)>{{ $meta['label'] ?? $key }}</option>
                @endforeach
            </select>
        </label>
        <button class="st-pill st-pill--solid" type="submit">{{ app()->getLocale() === 'ar' ? 'إضافة ابن' : 'Add child' }}</button>
    </form>
</section>
@endif

<a href="{{ route('settings') }}" class="st-event-card st-event-card--blue">
    <h3>{{ __('student_timeline.nav_settings') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.settings_hint') }}</p>
</a>

<a href="{{ route('notifications') }}" class="st-event-card st-event-card--orange">
    <h3>{{ __('student_timeline.nav_messages') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.notif_hint') }}</p>
</a>

<a href="{{ route('dashboard') }}" class="st-event-card st-event-card--pink">
    <h3>{{ __('student_timeline.school_gate') }}</h3>
    <p class="st-event-card__sub">{{ __('student_timeline.back_to_timeline') }}</p>
</a>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('stProfFile');
    var preview = document.getElementById('stProfPreview');
    if (input && preview) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            var url = URL.createObjectURL(file);
            preview.src = url;
            preview.onload = function () { URL.revokeObjectURL(url); };
        });
    }

    document.querySelectorAll('[data-copy]').forEach(function (copyBtn) {
        var original = copyBtn.innerHTML;
        copyBtn.addEventListener('click', function () {
            var value = copyBtn.getAttribute('data-copy') || '';
            function done() {
                copyBtn.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> ' + @json(__('student_timeline.copied'));
                setTimeout(function () { copyBtn.innerHTML = original; }, 1600);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(done).catch(function () {
                    window.prompt(@json(__('student_timeline.copy_user_id')), value);
                });
            } else {
                window.prompt(@json(__('student_timeline.copy_user_id')), value);
            }
        });
    });
})();
</script>
@endpush
