<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicSubjectController extends Controller
{
    public function index(Request $request): View
    {
        $trackId = $request->query('track');

        $subjects = AcademicSubject::query()
            ->with(['academicYear:id,name,code'])
            ->withCount(['advancedCourses as courses_count'])
            ->with(['advancedCourses' => function ($q) {
                $q->select('id', 'title', 'academic_subject_id', 'instructor_id', 'is_active', 'price')
                    ->with('instructor:id,name')
                    ->orderBy('title')
                    ->limit(4);
            }])
            ->when($trackId, fn ($q) => $q->forYear((int) $trackId))
            ->orderBy('academic_year_id')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $currentTrack = $trackId ? AcademicYear::find($trackId) : null;
        $tracks = AcademicYear::orderBy('order')->orderBy('name')->get(['id', 'name']);

        $summary = [
            'total' => $subjects->count(),
            'active' => $subjects->where('is_active', true)->count(),
            'courses' => (int) $subjects->sum('courses_count'),
        ];

        return view('admin.academic-subjects.index', compact('subjects', 'summary', 'currentTrack', 'tracks'));
    }

    public function create(Request $request): View
    {
        $academicYears = AcademicYear::orderBy('order')->orderBy('name')->get(['id', 'name', 'order', 'curriculum_type']);
        $selectedTrack = $request->query('track');
        $curriculumTypes = \App\Support\HesetakMatchCatalog::curriculumTypes();

        return view('admin.academic-subjects.create', compact('academicYears', 'selectedTrack', 'curriculumTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'academic_year_ids' => 'nullable|array',
            'academic_year_ids.*' => 'integer|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('academic_subjects', 'code'),
            ],
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:academic_subjects,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'curriculum_type' => ['nullable', 'string', 'max:64', 'in:'.implode(',', array_keys(\App\Support\HesetakMatchCatalog::curriculumTypes() ?: ['__none__']))],
        ], [
            'name.required' => 'اسم المادة مطلوب',
            'code.required' => 'رمز المادة مطلوب',
            'code.unique' => 'رمز المادة مستخدم مسبقاً — اربط المادة بمراحل متعددة بدلاً من تكرارها',
        ]);

        $yearIds = array_values(array_unique(array_map('intval', $request->input('academic_year_ids', []))));
        if ($yearIds === [] && $request->filled('academic_year_id')) {
            $yearIds = [(int) $request->input('academic_year_id')];
        }
        $yearId = $yearIds[0] ?? ($validated['academic_year_id'] ?? null);
        if ($yearId === '' || $yearId === null) {
            $yearId = null;
        }

        $curriculumType = filled($validated['curriculum_type'] ?? null) ? (string) $validated['curriculum_type'] : null;
        if ($curriculumType === null && $yearId) {
            $curriculumType = AcademicYear::query()->whereKey($yearId)->value('curriculum_type');
        }

        $subject = AcademicSubject::create([
            'academic_year_id' => $yearId,
            'curriculum_type' => $curriculumType,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'slug' => ! empty($validated['slug'])
                ? AcademicSubject::uniqueSlug($validated['slug'])
                : AcademicSubject::uniqueSlug($validated['name'].($yearId ? '-y'.$yearId : '')),
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? 'fas fa-book',
            'color' => $validated['color'] ?? '#0B3D91',
            'order' => $validated['order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($yearIds !== []) {
            $subject->years()->sync($yearIds);
        } elseif ($yearId) {
            $subject->years()->sync([$yearId]);
        }

        return redirect()
            ->route('admin.academic-subjects.index', array_filter(['track' => $yearId]))
            ->with('success', 'تم إضافة مادة المدرسة بنجاح');
    }

    public function show(AcademicSubject $academicSubject): View
    {
        $academicSubject->load([
            'academicYear:id,name,code',
            'advancedCourses' => fn ($q) => $q->with('instructor:id,name')->orderBy('title'),
        ]);

        $availableCourses = AdvancedCourse::query()
            ->where(function ($q) use ($academicSubject) {
                $q->whereNull('academic_subject_id')
                    ->orWhere('academic_subject_id', '!=', $academicSubject->id);
            })
            ->when($academicSubject->academic_year_id, function ($q) use ($academicSubject) {
                $q->where(function ($inner) use ($academicSubject) {
                    $inner->whereNull('academic_year_id')
                        ->orWhere('academic_year_id', $academicSubject->academic_year_id);
                });
            })
            ->orderBy('title')
            ->get(['id', 'title', 'academic_subject_id', 'academic_year_id', 'instructor_id', 'is_active']);

        return view('admin.academic-subjects.show', compact('academicSubject', 'availableCourses'));
    }

    public function edit(AcademicSubject $academicSubject): View
    {
        $academicYears = AcademicYear::orderBy('order')->orderBy('name')->get(['id', 'name', 'order', 'curriculum_type']);
        $academicSubject->load('years:id');
        $curriculumTypes = \App\Support\HesetakMatchCatalog::curriculumTypes();

        return view('admin.academic-subjects.edit', compact('academicSubject', 'academicYears', 'curriculumTypes'));
    }

    public function update(Request $request, AcademicSubject $academicSubject): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'academic_year_ids' => 'nullable|array',
            'academic_year_ids.*' => 'integer|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('academic_subjects', 'code')->ignore($academicSubject->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('academic_subjects', 'slug')->ignore($academicSubject->id),
            ],
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'curriculum_type' => ['nullable', 'string', 'max:64', 'in:'.implode(',', array_keys(\App\Support\HesetakMatchCatalog::curriculumTypes() ?: ['__none__']))],
        ], [
            'name.required' => 'اسم المادة مطلوب',
            'code.required' => 'رمز المادة مطلوب',
            'code.unique' => 'رمز المادة مستخدم مسبقاً — اربط المادة بمراحل متعددة بدلاً من تكرارها',
        ]);

        $yearIds = array_values(array_unique(array_map('intval', $request->input('academic_year_ids', []))));
        if ($yearIds === [] && $request->filled('academic_year_id')) {
            $yearIds = [(int) $request->input('academic_year_id')];
        }
        $yearId = $yearIds[0] ?? ($validated['academic_year_id'] ?? null);
        if ($yearId === '' || $yearId === null) {
            $yearId = null;
        }

        $curriculumType = filled($validated['curriculum_type'] ?? null) ? (string) $validated['curriculum_type'] : null;
        if ($curriculumType === null && $yearId) {
            $curriculumType = AcademicYear::query()->whereKey($yearId)->value('curriculum_type');
        }

        $academicSubject->update([
            'academic_year_id' => $yearId,
            'curriculum_type' => $curriculumType,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'slug' => ! empty($validated['slug'])
                ? AcademicSubject::uniqueSlug($validated['slug'], $academicSubject->id)
                : AcademicSubject::uniqueSlug($validated['name'].($yearId ? '-y'.$yearId : ''), $academicSubject->id),
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? 'fas fa-book',
            'color' => $validated['color'] ?? '#0B3D91',
            'order' => $validated['order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($yearIds !== []) {
            $academicSubject->years()->sync($yearIds);
        } elseif ($yearId) {
            $academicSubject->years()->sync([$yearId]);
        } else {
            $academicSubject->years()->detach();
        }

        return redirect()
            ->route('admin.academic-subjects.show', $academicSubject)
            ->with('success', 'تم تحديث مادة المدرسة بنجاح');
    }

    public function destroy(AcademicSubject $academicSubject): RedirectResponse
    {
        if ($academicSubject->advancedCourses()->exists()) {
            return back()->with('error', 'لا يمكن حذف المادة لأنها تحتوي على كورسات. انقل الكورسات أولاً.');
        }

        $trackId = $academicSubject->academic_year_id;
        $academicSubject->delete();

        return redirect()
            ->route('admin.academic-subjects.index', array_filter(['track' => $trackId]))
            ->with('success', 'تم حذف المادة بنجاح');
    }

    public function toggleStatus(Request $request, AcademicSubject $academicSubject)
    {
        $academicSubject->update(['is_active' => ! $academicSubject->is_active]);
        $status = $academicSubject->is_active ? 'تم تفعيل' : 'تم إيقاف';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $status.' المادة بنجاح',
                'is_active' => $academicSubject->is_active,
            ]);
        }

        return back()->with('success', $status.' المادة بنجاح');
    }

    public function attachCourse(Request $request, AcademicSubject $academicSubject): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => 'required|exists:advanced_courses,id',
        ]);

        $course = AdvancedCourse::findOrFail($data['course_id']);
        $course->update([
            'academic_subject_id' => $academicSubject->id,
            'academic_year_id' => $academicSubject->academic_year_id,
        ]);

        return back()->with('success', 'تم ربط الكورس بالمادة: '.$course->title);
    }

    public function detachCourse(AcademicSubject $academicSubject, AdvancedCourse $course): RedirectResponse
    {
        if ((int) $course->academic_subject_id !== (int) $academicSubject->id) {
            return back()->with('error', 'هذا الكورس غير مرتبط بهذه المادة.');
        }

        $course->update(['academic_subject_id' => null]);

        return back()->with('success', 'تم فك ربط الكورس من المادة.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:academic_subjects,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->items as $item) {
            AcademicSubject::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث الترتيب']);
    }
}
