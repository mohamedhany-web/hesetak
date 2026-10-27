<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\InstructorProfile;
use App\Services\PublicMediaStorage;
use App\Support\InstructorMatchCompleteness;
use App\Support\InstructorProfilePublication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class InstructorPersonalBrandingController extends Controller
{
    public function index(Request $request)
    {
        $query = InstructorProfile::with(['user.teachingLearningPaths', 'reviewedByUser']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $profiles = $query->latest('updated_at')->paginate(15)->withQueryString();

        $counts = [
            'pending' => InstructorProfile::pending()->count(),
            'approved' => InstructorProfile::approved()->count(),
            'rejected' => InstructorProfile::where('status', InstructorProfile::STATUS_REJECTED)->count(),
            'draft' => InstructorProfile::where('status', InstructorProfile::STATUS_DRAFT)->count(),
        ];

        $approvedIncomplete = InstructorProfile::query()
            ->approved()
            ->with(['user.teachingLearningPaths'])
            ->get()
            ->filter(fn (InstructorProfile $p) => ! InstructorProfilePublication::isPublishable($p))
            ->values();

        $approvedIncompleteCount = $approvedIncomplete->count();

        if ($request->input('gap') === 'approved') {
            $ids = $approvedIncomplete->pluck('id')->all();
            $profiles = InstructorProfile::query()
                ->with(['user.teachingLearningPaths', 'reviewedByUser'])
                ->whereIn('id', $ids ?: [0])
                ->latest('updated_at')
                ->paginate(30)
                ->withQueryString();
        }

        return view('admin.marketing.personal-branding.index', compact(
            'profiles',
            'counts',
            'approvedIncompleteCount'
        ));
    }

    public function show(InstructorProfile $personal_branding)
    {
        $personal_branding->load(['user.teachingLearningPaths', 'reviewedByUser']);
        $matchCompleteness = InstructorMatchCompleteness::evaluate($personal_branding);
        $publication = InstructorProfilePublication::evaluate($personal_branding);
        $looksLikeDemo = $publication['is_demo'];

        return view('admin.marketing.personal-branding.show', compact(
            'personal_branding',
            'matchCompleteness',
            'publication',
            'looksLikeDemo'
        ));
    }

    public function edit(InstructorProfile $personal_branding)
    {
        $personal_branding->load('user.teachingLearningPaths');
        $publication = InstructorProfilePublication::evaluate($personal_branding);

        return view('admin.marketing.personal-branding.edit', compact('personal_branding', 'publication'));
    }

    public function update(Request $request, InstructorProfile $personal_branding)
    {
        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:255', new \App\Rules\NoDemoPlaceholder],
            'bio' => ['nullable', 'string', 'max:5000', new \App\Rules\NoDemoPlaceholder],
            'experience' => ['nullable', 'string', 'max:50000', new \App\Rules\NoDemoPlaceholder],
            'skills' => ['nullable', 'string', 'max:5000', new \App\Rules\NoDemoPlaceholder],
            'curriculum_types' => 'nullable|array',
            'curriculum_types.*' => 'string|in:'.implode(',', \App\Support\HesetakMatchCatalog::allowedCurriculumTypeKeys() ?: ['__none__']),
            'teaching_year_ids' => 'nullable|array',
            'teaching_year_ids.*' => 'integer|exists:academic_years,id',
            'teaching_subject_ids' => 'nullable|array',
            'teaching_subject_ids.*' => 'integer|exists:academic_subjects,id',
            'consultation_price_egp' => 'nullable|numeric|min:0|max:999999.99',
            'consultation_duration_minutes' => 'nullable|integer|min:15|max:480',
            'photo' => 'nullable|image|max:'.config('upload_limits.max_upload_kb'),
        ], [
            'experience.max' => 'الخبرات في المجال يجب ألا تتجاوز 50 ألف حرف.',
            'skills.max' => 'المهارات يجب ألا تتجاوز 5 آلاف حرف.',
            'photo.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'photo.max' => 'حجم الصورة يتجاوز الحد المسموح.',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = PublicMediaStorage::store(
                $request->file('photo'),
                'instructor-profiles',
                $personal_branding->photo_path
            );
        }

        unset($data['photo']);
        $data['social_links'] = $personal_branding->social_links ?? [];
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

        $wasApproved = $personal_branding->status === InstructorProfile::STATUS_APPROVED;

        // إن كان معتمداً وأصبح غير قابل للنشر بعد التعديل، ننزله قبل الحفظ حتى لا تفشل بوابة الموديل.
        $personal_branding->fill($data);
        if ($personal_branding->user && Schema::hasTable('academic_year_instructors')) {
            $publicIds = AcademicYear::query()
                ->publicCatalog()
                ->whereIn('id', $teachingYearIds)
                ->pluck('id')
                ->all();
            $personal_branding->user->teachingLearningPaths()->sync($publicIds);
            $personal_branding->user->forceFill([
                'private_teaching_meta' => [
                    'subject_ids' => $data['teaching_subject_ids'],
                    'curriculum_types' => $data['curriculum_types'],
                    'year_ids' => $publicIds,
                ],
            ])->save();
            $personal_branding->unsetRelation('user');
            $personal_branding->load('user.teachingLearningPaths');
        }

        $demoted = false;
        if ($wasApproved && ! InstructorProfilePublication::isPublishable($personal_branding)) {
            $personal_branding->status = InstructorProfile::STATUS_PENDING_REVIEW;
            $personal_branding->reviewed_at = null;
            $personal_branding->reviewed_by = null;
            $personal_branding->rejection_reason = 'أُوقف النشر بعد التعديل: ملف المطابقة ناقص أو محتوى تجريبي.';
            $demoted = true;
        }

        $personal_branding->save();

        $message = 'تم تحديث الملف التعريفي للمدرب.';
        if ($demoted) {
            $message .= ' تنبيه: لم يعد يستوفي شروط النشر فأُعيد إلى قيد المراجعة.';
        }

        return redirect()
            ->route('admin.personal-branding.show', $personal_branding)
            ->with($demoted ? 'error' : 'success', $message);
    }

    public function destroy(InstructorProfile $personal_branding)
    {
        $userName = $personal_branding->user?->name ?? 'المدرب';

        if ($personal_branding->photo_path) {
            PublicMediaStorage::delete($personal_branding->photo_path);
        }

        $personal_branding->delete();

        return redirect()
            ->route('admin.personal-branding.index')
            ->with('success', 'تم حذف الملف التعريفي لـ '.$userName.'. يمكن للمدرب إنشاء ملف جديد من لوحته.');
    }

    public function approve(InstructorProfile $personal_branding)
    {
        try {
            InstructorProfilePublication::approve($personal_branding, (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first() ?: 'تعذرت الموافقة.');
        }

        return back()->with('success', 'تمت الموافقة على الملف التعريفي للمدرب ونشره على الموقع.');
    }

    public function reject(Request $request, InstructorProfile $personal_branding)
    {
        if ($personal_branding->status !== InstructorProfile::STATUS_PENDING_REVIEW) {
            return back()->with('error', 'يمكن رفض فقط الملفات قيد المراجعة.');
        }
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:2000',
        ]);

        $personal_branding->update([
            'status' => InstructorProfile::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return back()->with('success', 'تم رفض الملف التعريفي. يمكن للمدرب تعديله وإعادة الإرسال.');
    }

    /**
     * إعادة الملف للمراجعة (من معتمد أو مرفوض).
     */
    public function sendBackForReview(InstructorProfile $personal_branding)
    {
        if (! in_array($personal_branding->status, [InstructorProfile::STATUS_APPROVED, InstructorProfile::STATUS_REJECTED])) {
            return back()->with('error', 'يمكن إعادة المراجعة فقط للملفات المعتمدة أو المرفوضة.');
        }
        $personal_branding->update([
            'status' => InstructorProfile::STATUS_PENDING_REVIEW,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'تم إعادة الملف التعريفي إلى قيد المراجعة.');
    }

    /**
     * إيقاف نشر ملف معتمد ناقص المطابقة / تجريبي.
     */
    public function unpublishIncomplete(InstructorProfile $personal_branding)
    {
        if ($personal_branding->status !== InstructorProfile::STATUS_APPROVED) {
            return back()->with('error', 'هذا الملف ليس معتمداً.');
        }

        $ok = InstructorProfilePublication::demoteIncompleteApproved($personal_branding);
        if (! $ok) {
            return back()->with('error', 'الملف يستوفي شروط النشر — لا حاجة لإيقافه.');
        }

        return back()->with('success', 'تم إيقاف نشر الملف وإعادته لقيد المراجعة لاستكمال المطابقة.');
    }

    /**
     * إيقاف نشر كل المعتمدين الناقصين دفعة واحدة.
     */
    public function unpublishAllIncomplete()
    {
        $demoted = 0;
        InstructorProfile::query()
            ->approved()
            ->with(['user.teachingLearningPaths'])
            ->each(function (InstructorProfile $profile) use (&$demoted) {
                if (InstructorProfilePublication::demoteIncompleteApproved($profile)) {
                    $demoted++;
                }
            });

        return redirect()
            ->route('admin.personal-branding.index', ['gap' => 'approved'])
            ->with(
                $demoted > 0 ? 'success' : 'error',
                $demoted > 0
                    ? "تم إيقاف نشر {$demoted} ملفاً معتمداً ناقص المطابقة."
                    : 'لا توجد ملفات معتمدة ناقصة للمطابقة.'
            );
    }

    /**
     * سعر ومدة الاستشارة بالريال السعودي (للمدرب؛ إن تُرك السعر فارغاً يُستخدم الافتراضي من إعدادات الاستشارات).
     */
    public function updateConsultationPricing(Request $request, InstructorProfile $personal_branding)
    {
        $data = $request->validate([
            'consultation_price_egp' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'consultation_duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
        ]);

        $price = $request->input('consultation_price_egp');
        $duration = $request->input('consultation_duration_minutes');

        $personal_branding->update([
            'consultation_price_egp' => $price === null || $price === '' ? null : $price,
            'consultation_duration_minutes' => $duration === null || $duration === '' ? null : (int) $duration,
        ]);

        return back()->with('success', 'تم حفظ سعر ومدة الاستشارة لهذا المدرب (بالريال السعودي).');
    }
}
