@extends('layouts.auth-landing')

@section('title', __('auth.register'))
@section('main_class', 'lasles-auth-main--top')

@section('body_attrs', 'x-data="{ showPassword: false, showPasswordConfirm: false }"')

@section('content')
@php
  $isRtl = app()->getLocale() === 'ar';
  $phoneCountries = $phoneCountries ?? config('phone_countries.countries', []);
  $defaultCountry = $defaultCountry ?? collect($phoneCountries)->firstWhere('code', config('phone_countries.default_country', 'SA'));
  $oldDial = old('country_code', $defaultCountry['dial_code'] ?? '+966');
  $oldIso = old('country_iso', $defaultCountry['code'] ?? 'SA');
  $selectedCountry = collect($phoneCountries)->firstWhere('code', $oldIso)
      ?? collect($phoneCountries)->firstWhere('dial_code', $oldDial)
      ?? $defaultCountry;
  $phoneCountriesUi = collect($phoneCountries)->map(fn ($c) => [
      'code' => $c['code'] ?? '',
      'dial_code' => $c['dial_code'] ?? '',
      'name_ar' => $c['name_ar'] ?? '',
      'name_en' => $c['name_en'] ?? ($c['name_ar'] ?? ''),
      'placeholder' => $c['placeholder'] ?? '',
  ])->values()->all();
@endphp
<div class="lasles-auth-card lasles-auth-card--wide">
  <p class="lasles-auth-eyebrow">{{ __('auth.register_eyebrow') }}</p>
  <h1 class="lasles-auth-title">{!! __('auth.register_title_html') !!}</h1>
  <p class="lasles-auth-lead">{{ __('auth.register_subtitle') }}</p>

  <div class="lasles-auth-alert lasles-auth-alert--info">{{ __('auth.register_portal_note') }}</div>

  @if (! empty($pendingReferralCode))
    <div class="lasles-auth-alert lasles-auth-alert--ok">
      <strong>{{ __('auth.referral_invite_title') }}</strong><br>
      {{ __('auth.referral_invite_body', ['code' => $pendingReferralCode]) }}
    </div>
  @endif

  @if ($errors->any())
    <div class="lasles-auth-alert lasles-auth-alert--err">{{ $errors->first() }}</div>
  @endif

  <form action="{{ route('register') }}" method="POST" novalidate>
    @csrf
    <input type="hidden" name="referral_code" value="{{ old('referral_code', $pendingReferralCode ?? '') }}">

    <div class="lasles-auth-field">
      <label for="name">{{ __('auth.full_name') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
        <input
          type="text"
          name="name"
          id="name"
          value="{{ old('name') }}"
          required
          autocomplete="name"
          placeholder="{{ __('auth.enter_full_name') }}"
          class="lasles-auth-input has-icon @error('name') has-error @enderror"
        >
      </div>
      @error('name')<p class="lasles-auth-error">{{ $message }}</p>@enderror
    </div>

    @php
      $academicYears = $academicYears ?? collect();
      $gradesByYear = $gradesByYear ?? [];
      $subjectsByYear = $subjectsByYear ?? [];
      $curriculumTypes = $curriculumTypes ?? [];
      $oldSubjects = array_map('strval', (array) old('preferred_subject_ids', []));
    @endphp
    <div id="student-learning-fields"
      x-data="{
        yearId: @js((string) old('academic_year_id', '')),
        gradeId: @js((string) old('academic_grade_id', '')),
        selectedSubjects: @js($oldSubjects),
        map: @js($gradesByYear),
        subjectsMap: @js($subjectsByYear),
        placeholder: @js(__('auth.academic_grade_placeholder')),
        get grades() { return this.map[String(this.yearId)] || []; },
        get hasGrades() { return this.grades.length > 0; },
        get subjects() { return this.subjectsMap[String(this.yearId)] || []; },
        get hasSubjects() { return this.subjects.length > 0; },
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
          const allowed = this.subjects.map((s) => String(s.id));
          this.selectedSubjects = (this.selectedSubjects || []).filter((id) => allowed.includes(String(id)));
        }
      }"
      x-init="fillGrades()"
    >
      <div class="lasles-auth-field">
        <label for="academic_year_id">{{ __('auth.academic_stage') }}</label>
        <select id="academic_year_id" name="academic_year_id" required class="lasles-auth-input @error('academic_year_id') has-error @enderror"
                x-model="yearId" @change="fillGrades()">
          <option value="">{{ __('auth.academic_stage_placeholder') }}</option>
          @foreach($academicYears as $year)
            <option value="{{ $year->id }}">{{ $year->name }}</option>
          @endforeach
        </select>
        <p class="lasles-auth-hint">{{ __('auth.academic_stage_hint') }}</p>
        @error('academic_year_id')<p class="lasles-auth-error">{{ $message }}</p>@enderror
      </div>

      <div class="lasles-auth-field" id="student-grade-field" x-show="hasGrades" x-cloak>
        <label for="academic_grade_id">{{ __('auth.academic_grade') }}</label>
        <select id="academic_grade_id" name="academic_grade_id" x-ref="gradeSelect" x-model="gradeId"
                class="lasles-auth-input @error('academic_grade_id') has-error @enderror">
          <option value="">{{ __('auth.academic_grade_placeholder') }}</option>
        </select>
        <p class="lasles-auth-hint">{{ __('auth.academic_grade_hint') }}</p>
        @error('academic_grade_id')<p class="lasles-auth-error">{{ $message }}</p>@enderror
      </div>

      <div class="lasles-auth-field" id="preferred-subjects-field" x-show="hasSubjects" x-cloak>
        <label>{{ __('auth.preferred_subjects') }}</label>
        <div class="lasles-auth-choices">
          <template x-for="subject in subjects" :key="subject.id">
            <label class="lasles-auth-choice">
              <input type="checkbox" name="preferred_subject_ids[]" :value="subject.id" x-model="selectedSubjects">
              <span x-text="subject.name"></span>
            </label>
          </template>
        </div>
        <p class="lasles-auth-hint">{{ __('auth.preferred_subjects_hint') }}</p>
        @error('preferred_subject_ids')<p class="lasles-auth-error">{{ $message }}</p>@enderror
      </div>
    </div>

    <div class="lasles-auth-field">
      <label for="preferred_curriculum_type">{{ __('auth.curriculum_type') }}</label>
      <select id="preferred_curriculum_type" name="preferred_curriculum_type" required class="lasles-auth-input @error('preferred_curriculum_type') has-error @enderror">
        <option value="">{{ __('auth.curriculum_type_placeholder') }}</option>
        @foreach($curriculumTypes as $key => $meta)
          <option value="{{ $key }}" @selected((string) old('preferred_curriculum_type') === (string) $key)>{{ $meta['label'] ?? $key }}</option>
        @endforeach
      </select>
      <p class="lasles-auth-hint">{{ __('auth.curriculum_type_hint') }}</p>
      @error('preferred_curriculum_type')<p class="lasles-auth-error">{{ $message }}</p>@enderror
    </div>

    @include('auth.partials.phone-field', [
        'field' => 'phone',
        'dialField' => 'country_code',
        'isoField' => 'country_iso',
        'label' => __('auth.phone_number'),
        'autocomplete' => 'tel-national',
    ])

    @include('auth.partials.phone-field', [
        'field' => 'parent_phone',
        'dialField' => 'parent_country_code',
        'isoField' => 'parent_country_iso',
        'label' => __('auth.parent_phone'),
        'hint' => __('auth.parent_phone_hint'),
        'autocomplete' => 'tel',
    ])

    <div class="lasles-auth-field">
      <label for="email">{{ __('auth.email') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
        <input
          type="email"
          name="email"
          id="email"
          value="{{ old('email') }}"
          required
          autocomplete="email"
          dir="ltr"
          placeholder="{{ __('auth.email_placeholder') }}"
          class="lasles-auth-input has-icon @error('email') has-error @enderror"
        >
      </div>
      @error('email')<p class="lasles-auth-error">{{ $message }}</p>@enderror
    </div>

    <div class="lasles-auth-field">
      <label for="password">{{ __('auth.password') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
        <input
          :type="showPassword ? 'text' : 'password'"
          name="password"
          id="password"
          required
          autocomplete="new-password"
          placeholder="{{ __('auth.enter_strong_password') }}"
          class="lasles-auth-input has-icon @error('password') has-error @enderror"
          style="padding-inline-end:3rem"
        >
        <button type="button" class="lasles-auth-pw-btn" @click="showPassword = !showPassword">
          <span x-text="showPassword ? '{{ __('auth.hide') }}' : '{{ __('auth.show') }}'"></span>
        </button>
      </div>
      <p class="lasles-auth-hint">{{ __('auth.password_rules_hint') }}</p>
      @error('password')<p class="lasles-auth-error">{{ $message }}</p>@enderror
    </div>

    <div class="lasles-auth-field">
      <label for="password_confirmation">{{ __('auth.password_confirmation') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
        <input
          :type="showPasswordConfirm ? 'text' : 'password'"
          name="password_confirmation"
          id="password_confirmation"
          required
          autocomplete="new-password"
          placeholder="{{ __('auth.reenter_password') }}"
          class="lasles-auth-input has-icon"
          style="padding-inline-end:3rem"
        >
        <button type="button" class="lasles-auth-pw-btn" @click="showPasswordConfirm = !showPasswordConfirm">
          <span x-text="showPasswordConfirm ? '{{ __('auth.hide') }}' : '{{ __('auth.show') }}'"></span>
        </button>
      </div>
    </div>

    <div class="lasles-auth-field">
      <label for="timezone">{{ __('auth.timezone') }}</label>
      @php
        $tzOptions = \App\Support\AppTimezone::commonZones();
        $tzValue = old('timezone', session('pending_timezone', ''));
      @endphp
      <select id="timezone" name="timezone" data-timezone-select class="lasles-auth-input">
        <option value="">{{ __('auth.timezone_auto') }}</option>
        @foreach ($tzOptions as $tzId => $tzLabel)
          <option value="{{ $tzId }}" @selected($tzValue === $tzId)>{{ $tzLabel }}</option>
        @endforeach
      </select>
      <input type="hidden" name="timezone_auto" id="timezone_auto" value="">
      <p class="lasles-auth-hint">{{ __('auth.timezone_hint') }}</p>
    </div>

    <label class="lasles-auth-terms">
      <input type="checkbox" id="terms" required>
      <span>
        {{ __('auth.agree_terms') }}
        <a href="{{ route('public.terms') }}" class="lasles-auth-link">{{ __('auth.terms_of_use') }}</a>
        {{ __('auth.and') }}
        <a href="{{ route('public.privacy') }}" class="lasles-auth-link">{{ __('auth.privacy_policy') }}</a>
      </span>
    </label>

    <button type="submit" class="lasles-auth-submit">
      <i class="fas fa-user-plus" aria-hidden="true"></i>
      <span>{{ __('auth.register_cta') }}</span>
    </button>
  </form>

  @if(config('services.google.client_id') && config('services.google.client_secret'))
    <div class="lasles-auth-or" aria-hidden="true"><span>{{ __('auth.or') }}</span></div>
    <a href="{{ route('auth.google.redirect') }}" class="lasles-auth-google">
      <i class="fab fa-google" aria-hidden="true"></i>
      <span>{{ __('auth.signup_with_google') }}</span>
    </a>
  @endif

  <div class="lasles-auth-foot">
    {{ __('auth.already_have_account') }}
    <a href="{{ route('login') }}" class="lasles-auth-link">{{ __('auth.login') }}</a>
  </div>
</div>
@endsection
