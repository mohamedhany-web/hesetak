@extends('layouts.admin')

@section('title', 'تعديل الملف التعريفي - ' . ($personal_branding->user->name ?? ''))
@section('page_title', 'تعديل الملف التعريفي')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';

    $statusBadge = match ($personal_branding->status) {
        'approved' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
        'pending_review' => 'border-amber-100 bg-amber-50 text-amber-700',
        'rejected' => 'border-rose-100 bg-rose-50 text-rose-700',
        default => 'border-line bg-canvas text-muted',
    };
@endphp

<div class="space-y-5">
    <nav class="flex flex-wrap items-center gap-1 text-sm text-muted">
        <a href="{{ route('admin.personal-branding.index') }}" class="font-medium text-accent hover:underline">التسويق الشخصي</a>
        <span>/</span>
        <a href="{{ route('admin.personal-branding.show', $personal_branding) }}" class="font-medium text-accent hover:underline">{{ $personal_branding->user->name ?? 'مدرب' }}</a>
        <span>/</span>
        <span class="text-ink">تعديل</span>
    </nav>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · المدربين</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل ملف {{ $personal_branding->user->name ?? 'المدرب' }}</h2>
            <p class="mt-1 text-sm text-muted">{{ $personal_branding->user->email ?? '' }}</p>
            <span class="mt-3 inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $statusBadge }}">
                <span class="size-1.5 rounded-full bg-current"></span>
                {{ \App\Models\InstructorProfile::statusLabel($personal_branding->status) }}
            </span>
            <p class="mt-2 max-w-2xl text-xs text-muted">تعديل المحتوى لا يغيّر الحالة تلقائياً. للموافقة أو الرفض استخدم صفحة المراجعة.</p>
        </div>
        <a href="{{ route('admin.personal-branding.show', $personal_branding) }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للمراجعة
        </a>
    </section>

    <form method="POST" action="{{ route('admin.personal-branding.update', $personal_branding) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-user text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">الملف الشخصي</h3>
                        <p class="mt-0.5 text-xs text-muted">الصورة، العنوان، والنبذة</p>
                    </div>
                </div>
            </div>

            <div class="space-y-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">صورة الملف</label>
                    @if($personal_branding->photo_path)
                        <div class="mb-3 size-24 overflow-hidden rounded-xl border border-line bg-canvas">
                            <img src="{{ $personal_branding->photo_url }}" alt="" class="size-full object-cover" onerror="this.style.display='none'">
                        </div>
                    @endif
                    <input type="file" name="photo" accept="image/*"
                           class="block w-full text-sm text-muted file:me-4 file:rounded-xl file:border-0 file:bg-accent-soft file:px-4 file:py-2 file:text-sm file:font-semibold file:text-accent hover:file:bg-accent/10">
                    @error('photo')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">العنوان التعريفي</label>
                    <input type="text" name="headline" value="{{ old('headline', $personal_branding->headline) }}" class="{{ $fieldClass }}">
                    @error('headline')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">النبذة</label>
                    <textarea name="bio" rows="5" class="{{ $areaClass }}">{{ old('bio', $personal_branding->bio) }}</textarea>
                    @error('bio')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-briefcase text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">الخبرات والمهارات</h3>
                        <p class="mt-0.5 text-xs text-muted">الخبرات في المجال والمهارات</p>
                    </div>
                </div>
            </div>

            <div class="space-y-5 p-4 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">الخبرات في المجال</label>
                    <textarea name="experience" rows="10" class="{{ $areaClass }}">{{ old('experience', $personal_branding->experience) }}</textarea>
                    @error('experience')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">المهارات</label>
                    <p class="mb-2 text-xs text-muted">سطر لكل مهارة أو مفصولة بفاصلة.</p>
                    <textarea name="skills" rows="5" class="{{ $areaClass }}">{{ old('skills', $personal_branding->skills) }}</textarea>
                    @error('skills')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>

                @php
                    $typeOptions = \App\Support\HesetakMatchCatalog::curriculumTypes('ar');
                    $selectedTypes = old('curriculum_types', $personal_branding->curriculumTypeKeys());
                    if (! is_array($selectedTypes)) { $selectedTypes = []; }
                    $publicYears = \Illuminate\Support\Facades\Schema::hasTable('academic_years')
                        ? \App\Models\AcademicYear::query()->publicCatalog()->ordered()->get(['id','name'])
                        : collect();
                    $selectedYearIds = old('teaching_year_ids', $personal_branding->user?->teachingLearningPaths()->pluck('academic_years.id')->all() ?? []);
                    if (! is_array($selectedYearIds)) { $selectedYearIds = []; }
                    $subjects = \Illuminate\Support\Facades\Schema::hasTable('academic_subjects')
                        ? \App\Models\AcademicSubject::query()->active()->ordered()
                            ->where(function ($q) {
                                $q->whereNull('academic_year_id')
                                    ->orWhereHas('academicYear', fn ($y) => $y->publicCatalog());
                            })
                            ->get(['id','name','academic_year_id'])
                            ->unique(fn ($s) => mb_strtolower($s->name))
                            ->values()
                        : collect();
                    $selectedSubjectIds = old('teaching_subject_ids', $personal_branding->teachingSubjectIds());
                    if (! is_array($selectedSubjectIds)) { $selectedSubjectIds = []; }
                    $selectedSubjectIds = array_map('intval', $selectedSubjectIds);
                @endphp

                <div>
                    <label class="{{ $labelClass }}">أنواع المنهج المعتمدة (للمطابقة العامة)</label>
                    <p class="mb-2 text-xs text-muted">تُدار من <a href="{{ route('admin.curriculum-types.index') }}" class="font-semibold text-accent underline">أنواع المنهج</a> · تظهر في دليل المعلمين. ليست ادّعاء فروع.</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach($typeOptions as $type)
                            <label class="flex items-center gap-2 rounded-xl border border-line bg-canvas px-3 py-2 text-sm text-ink">
                                <input type="checkbox" name="curriculum_types[]" value="{{ $type['key'] }}"
                                       @checked(in_array($type['key'], $selectedTypes, true))>
                                <span>{{ $type['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('curriculum_types')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                    @error('curriculum_types.*')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>

                @if($publicYears->isNotEmpty())
                <div>
                    <label class="{{ $labelClass }}">المراحل العامة المرتبطة</label>
                    <p class="mb-2 text-xs text-muted">من كتالوج <a href="{{ route('admin.academic-years.index') }}" class="font-semibold text-accent underline">{{ __('admin.academic_years') }}</a> المنشور للعامة.</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach($publicYears as $year)
                            <label class="flex items-center gap-2 rounded-xl border border-line bg-canvas px-3 py-2 text-sm text-ink">
                                <input type="checkbox" name="teaching_year_ids[]" value="{{ $year->id }}"
                                       @checked(in_array((int) $year->id, array_map('intval', $selectedYearIds), true))>
                                <span>{{ $year->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($subjects->isNotEmpty())
                <div>
                    <label class="{{ $labelClass }}">المواد التي يدرّسها</label>
                    <p class="mb-2 text-xs text-muted">من <a href="{{ route('admin.academic-subjects.index') }}" class="font-semibold text-accent underline">{{ __('admin.skill_groups') }}</a> · تُستخدم في فلتر المادة بدليل المعلمين.</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3 max-h-64 overflow-y-auto rounded-xl border border-line bg-canvas p-3">
                        @foreach($subjects as $subject)
                            <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-ink hover:bg-surface">
                                <input type="checkbox" name="teaching_subject_ids[]" value="{{ $subject->id }}"
                                       @checked(in_array((int) $subject->id, $selectedSubjectIds, true))>
                                <span>{{ $subject->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('teaching_subject_ids')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>
                @endif
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-comments text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">الاستشارة (اختياري)</h3>
                        <p class="mt-0.5 text-xs text-muted">فارغ = الافتراضي من إعدادات المنصة</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 p-4 md:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}">السعر ($)</label>
                    <input type="number" step="0.01" min="0" max="999999.99" name="consultation_price_egp"
                           value="{{ old('consultation_price_egp', $personal_branding->consultation_price_egp) }}"
                           class="{{ $fieldClass }}" dir="ltr">
                    @error('consultation_price_egp')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">المدة (دقيقة)</label>
                    <input type="number" min="15" max="480" name="consultation_duration_minutes"
                           value="{{ old('consultation_duration_minutes', $personal_branding->consultation_duration_minutes) }}"
                           class="{{ $fieldClass }}" dir="ltr">
                    @error('consultation_duration_minutes')<p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line px-4 py-4 sm:px-5">
                <a href="{{ route('admin.personal-branding.show', $personal_branding) }}"
                   class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                    إلغاء
                </a>
                <button type="submit"
                        class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-save text-xs"></i>
                    حفظ التعديلات
                </button>
            </div>
        </article>
    </form>
</div>
@endsection
