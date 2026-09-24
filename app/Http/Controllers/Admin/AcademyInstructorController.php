<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\StudentInstructorAssignment;
use App\Models\TutoringGroup;
use App\Models\TutoringGroupBooking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademyInstructorController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $instructors = User::query()
            ->whereIn('role', ['instructor', 'teacher'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'is_active', 'profile_image']);

        $groupStats = TutoringGroup::query()
            ->selectRaw('instructor_id, type, count(*) as total')
            ->where('is_active', true)
            ->groupBy('instructor_id', 'type')
            ->get()
            ->groupBy('instructor_id');

        $courseCounts = AdvancedCourse::query()
            ->selectRaw('instructor_id, count(*) as total')
            ->whereNotNull('instructor_id')
            ->where('is_active', true)
            ->groupBy('instructor_id')
            ->pluck('total', 'instructor_id');

        $assignmentCounts = collect();
        if (Schema::hasTable('student_instructor_assignments')) {
            $assignmentCounts = StudentInstructorAssignment::query()
                ->where('status', StudentInstructorAssignment::STATUS_ACTIVE)
                ->selectRaw('instructor_id, count(*) as total')
                ->groupBy('instructor_id')
                ->pluck('total', 'instructor_id');
        }

        $rows = $instructors->map(function (User $instructor) use ($groupStats, $courseCounts, $assignmentCounts) {
            $byType = $groupStats->get($instructor->id, collect());
            $collective = (int) optional($byType->firstWhere('type', TutoringGroup::TYPE_COLLECTIVE))->total;
            $individual = (int) optional($byType->firstWhere('type', TutoringGroup::TYPE_INDIVIDUAL))->total;

            return [
                'instructor' => $instructor,
                'collective_groups' => $collective,
                'individual_groups' => $individual,
                'courses' => (int) ($courseCounts[$instructor->id] ?? 0),
                'assigned_students' => (int) ($assignmentCounts[$instructor->id] ?? 0),
            ];
        });

        $summary = [
            'instructors' => $rows->count(),
            'collective' => $rows->sum('collective_groups'),
            'individual' => $rows->sum('individual_groups'),
            'assignments' => $rows->sum('assigned_students'),
        ];

        return view('admin.academy-instructors.index', compact('rows', 'summary', 'search'));
    }

    public function show(User $instructor): View
    {
        abort_unless($instructor->isInstructor() || $instructor->isTeacher(), 404);

        $courses = AdvancedCourse::query()
            ->where('instructor_id', $instructor->id)
            ->with(['academicSubject:id,name', 'academicYear:id,name'])
            ->orderBy('title')
            ->get();

        $assignments = Schema::hasTable('student_instructor_assignments')
            ? StudentInstructorAssignment::query()
                ->where('instructor_id', $instructor->id)
                ->with(['student:id,name,email,phone', 'academicYear:id,name', 'assignedBy:id,name'])
                ->latest()
                ->get()
            : collect();

        $students = User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $years = AcademicYear::orderBy('order')->orderBy('name')->get(['id', 'name']);

        return view('admin.academy-instructors.show', compact(
            'instructor',
            'courses',
            'assignments',
            'students',
            'years'
        ));
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => 'required|exists:users,id',
            'instructor_id' => 'required|exists:users,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'scope' => ['required', Rule::in([
                StudentInstructorAssignment::SCOPE_GENERAL,
                StudentInstructorAssignment::SCOPE_COURSES,
            ])],
            'notes' => 'nullable|string|max:2000',
            'starts_at' => 'nullable|date',
        ]);

        $student = User::findOrFail($data['student_id']);
        $instructor = User::findOrFail($data['instructor_id']);

        if ($student->role !== 'student') {
            return back()->withInput()->withErrors(['student_id' => 'المستخدم المحدد ليس طالباً.']);
        }
        if (! $instructor->isInstructor() && ! $instructor->isTeacher()) {
            return back()->withInput()->withErrors(['instructor_id' => 'المستخدم المحدد ليس مدرّباً.']);
        }

        $profile = $instructor->instructorProfile;
        if ($profile && ! empty($data['academic_year_id'])) {
            if (! \App\Services\TeacherSpecialtyMatcher::matches(
                $profile,
                null,
                (int) $data['academic_year_id'],
                null
            )) {
                return back()->withInput()->withErrors([
                    'academic_year_id' => 'مرحلة التوصيف لا تطابق تخصص المعلم المعتمد.',
                ]);
            }
        }

        StudentInstructorAssignment::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'instructor_id' => $data['instructor_id'],
                'scope' => $data['scope'],
            ],
            [
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'status' => StudentInstructorAssignment::STATUS_ACTIVE,
                'notes' => $data['notes'] ?? null,
                'assigned_by' => $request->user()?->id,
                'starts_at' => $data['starts_at'] ?? now(),
                'ends_at' => null,
            ]
        );

        return redirect()
            ->route('admin.academy-instructors.show', $instructor)
            ->with('success', 'تم توصيف المدرّب للطالب بنجاح.');
    }

    public function updateAssignmentStatus(Request $request, StudentInstructorAssignment $assignment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                StudentInstructorAssignment::STATUS_ACTIVE,
                StudentInstructorAssignment::STATUS_PAUSED,
                StudentInstructorAssignment::STATUS_ENDED,
            ])],
        ]);

        $assignment->update([
            'status' => $data['status'],
            'ends_at' => $data['status'] === StudentInstructorAssignment::STATUS_ENDED ? now() : $assignment->ends_at,
        ]);

        return back()->with('success', 'تم تحديث حالة التوصيف.');
    }

    public function destroyAssignment(StudentInstructorAssignment $assignment): RedirectResponse
    {
        $instructorId = $assignment->instructor_id;
        $assignment->delete();

        return redirect()
            ->route('admin.academy-instructors.show', $instructorId)
            ->with('success', 'تم حذف التوصيف.');
    }
}
