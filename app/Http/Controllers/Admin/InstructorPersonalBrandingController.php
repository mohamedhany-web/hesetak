<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\InstructorProfile;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class InstructorPersonalBrandingController extends Controller
{
    public function index(Request $request)
    {
        $query = InstructorProfile::with(['user', 'reviewedByUser']);

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

        return view('admin.marketing.personal-branding.index', compact('profiles', 'counts'));
    }

    public function show(InstructorProfile $personal_branding)
    {
        $personal_branding->load(['user', 'reviewedByUser']);

        return view('admin.marketing.personal-branding.show', compact('personal_branding'));
    }

    public function edit(InstructorProfile $personal_branding)
    {
        $personal_branding->load('user');

        return view('admin.marketing.personal-branding.edit', compact('personal_branding'));
    }

    public function update(Request $request, InstructorProfile $personal_branding)
    {
        $data = $request->validate([
            'headline' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:5000',
            'experience' => 'nullable|string|max:50000',
            'skills' => 'nullable|string|max:5000',
            'curriculum_types' => 'nullable|array',
            'curriculum_types.*' => 'string|in:'.implode(',', \App\Support\HesetakMatchCatalog::allowedCurriculumTypeKeys()),
            'teaching_year_ids' => 'nullable|array',
            'teaching_year_ids.*' => 'integer|exists:academic_years,id',
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

        $personal_branding->update($data);

        if ($personal_branding->user && Schema::hasTable('academic_year_instructors')) {
            $publicIds = AcademicYear::query()
                ->publicCatalog()
                ->whereIn('id', $teachingYearIds)
                ->pluck('id')
                ->all();
            $personal_branding->user->teachingLearningPaths()->sync($publicIds);
        }

        return redirect()
            ->route('admin.personal-branding.show', $personal_branding)
            ->with('success', 'تم تحديث الملف التعريفي للمدرب.');
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
        if ($personal_branding->status !== InstructorProfile::STATUS_PENDING_REVIEW) {
            return back()->with('error', 'يمكن الموافقة فقط على الملفات قيد المراجعة.');
        }
        $personal_branding->update([
            'status' => InstructorProfile::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'rejection_reason' => null,
        ]);

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
