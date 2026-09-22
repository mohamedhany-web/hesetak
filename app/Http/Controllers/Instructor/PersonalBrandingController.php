<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\InstructorProfile;
use App\Services\PublicMediaStorage;
use App\Support\HesetakMatchCatalog;
use App\Support\InstructorMatchCompleteness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PersonalBrandingController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        if (! $user->isInstructor()) {
            abort(403);
        }
        $profile = InstructorProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => InstructorProfile::STATUS_DRAFT]
        );

        $curriculumTypes = HesetakMatchCatalog::curriculumTypes();
        $publicYears = Schema::hasTable('academic_years')
            ? AcademicYear::query()->publicCatalog()->ordered()->get(['id', 'name', 'slug'])
            : collect();
        $subjects = Schema::hasTable('academic_subjects')
            ? AcademicSubject::query()
                ->active()
                ->ordered()
                ->where(function ($q) {
                    $q->whereNull('academic_year_id')
                        ->orWhereHas('academicYear', fn ($y) => $y->publicCatalog());
                })
                ->get(['id', 'name', 'academic_year_id'])
                ->unique(fn ($s) => mb_strtolower($s->name))
                ->values()
            : collect();

        $matchCompleteness = InstructorMatchCompleteness::evaluate($profile, $user);

        return view('instructor.personal-branding.edit', compact(
            'profile',
            'curriculumTypes',
            'publicYears',
            'subjects',
            'matchCompleteness'
        ));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if (! $user->isInstructor()) {
            abort(403);
        }
        $profile = InstructorProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => InstructorProfile::STATUS_DRAFT]
        );

        $allowedCurriculum = HesetakMatchCatalog::activeCurriculumTypeKeys();

        $data = $request->validate([
            'headline' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:5000',
            'experience' => 'nullable|string|max:50000',
            'skills' => 'nullable|string|max:5000',
            'curriculum_types' => 'nullable|array',
            'curriculum_types.*' => 'string|in:'.implode(',', $allowedCurriculum ?: ['__none__']),
            'teaching_year_ids' => 'nullable|array',
            'teaching_year_ids.*' => 'integer|exists:academic_years,id',
            'teaching_subject_ids' => 'nullable|array',
            'teaching_subject_ids.*' => 'integer|exists:academic_subjects,id',
            'consultation_price_egp' => 'nullable|numeric|min:0|max:999999.99',
            'consultation_duration_minutes' => 'nullable|integer|min:15|max:480',
            'photo' => 'nullable|image|max:'.config('upload_limits.max_upload_kb'),
            'gender' => 'nullable|in:male,female',
            'intro_video_url' => 'nullable|url|max:500',
        ], [
            'experience.max' => 'الخبرات في المجال يجب ألا تتجاوز 50 ألف حرف. إن احتجت مساحة أكبر تواصل مع الإدارة.',
            'skills.max' => 'المهارات يجب ألا تتجاوز 5 آلاف حرف.',
            'photo.image' => 'الملف الذي تم رفعه يجب أن يكون صورة',
            'photo.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = PublicMediaStorage::store(
                $request->file('photo'),
                'instructor-profiles',
                $profile->photo_path
            );
        }

        unset($data['photo']);
        $data['social_links'] = $profile->social_links ?? [];
        $data['curriculum_types'] = array_values(array_unique($data['curriculum_types'] ?? []));
        $data['teaching_subject_ids'] = array_values(array_unique(array_map('intval', $data['teaching_subject_ids'] ?? [])));
        $teachingYearIds = array_values(array_unique(array_map('intval', $data['teaching_year_ids'] ?? [])));
        unset($data['teaching_year_ids']);

        foreach (['consultation_price_egp', 'consultation_duration_minutes'] as $k) {
            if (! array_key_exists($k, $data)) {
                continue;
            }
            if ($data[$k] === '' || $data[$k] === null) {
                $data[$k] = null;
            }
        }

        $user->forceFill([
            'gender' => $data['gender'] ?? $user->gender,
            'portfolio_intro_video_url' => $data['intro_video_url'] ?? null,
            // Legacy Quran-centric meta is no longer the matching source of truth.
            'private_teaching_meta' => [
                'subject_ids' => $data['teaching_subject_ids'],
                'curriculum_types' => $data['curriculum_types'],
                'year_ids' => $teachingYearIds,
            ],
        ])->save();

        unset($data['gender'], $data['intro_video_url']);

        $profile->update($data);

        if (Schema::hasTable('academic_year_instructors')) {
            $publicIds = AcademicYear::query()
                ->publicCatalog()
                ->whereIn('id', $teachingYearIds)
                ->pluck('id')
                ->all();
            $user->teachingLearningPaths()->sync($publicIds);
        }

        return back()->with('success', 'تم حفظ ملف المطابقة (مرحلة · مادة · نوع منهج).');
    }

    public function submit()
    {
        $user = auth()->user();
        if (! $user->isInstructor()) {
            abort(403);
        }
        $profile = InstructorProfile::where('user_id', $user->id)->firstOrFail();
        if ($profile->status !== InstructorProfile::STATUS_DRAFT && $profile->status !== InstructorProfile::STATUS_REJECTED) {
            return back()->with('error', 'الملف مقدم مسبقاً أو معتمد.');
        }

        $profile->loadMissing('user');
        $completeness = InstructorMatchCompleteness::evaluate($profile, $user);
        if (! $completeness['ok']) {
            return back()->with(
                'error',
                'أكمل ملف المطابقة قبل الإرسال: '.implode(' · ', $completeness['missing'])
            );
        }

        $profile->update([
            'status' => InstructorProfile::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'تم إرسال الملف التعريفي للمراجعة. سيتم إعلامك بعد مراجعته من الإدارة.');
    }
}
