<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\ServicePackage;
use App\Models\TutoringGroup;
use App\Services\StudentEntitlementService;
use App\Services\TutoringGroupAvailabilityService;
use App\Services\TutoringGroupCheckoutService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use InvalidArgumentException;

class GroupsController extends Controller
{
    public function index(): View
    {
        $schoolYears = collect();
        $schoolSubjects = collect();

        if (Schema::hasTable('academic_years')) {
            $schoolYears = AcademicYear::query()
                ->active()
                ->ordered()
                ->withCount([
                    'tutoringGroups as open_classes_count' => fn ($q) => $q->active()->collective(),
                ])
                ->get();
        }

        if (Schema::hasTable('academic_subjects')) {
            $schoolSubjects = AcademicSubject::query()
                ->active()
                ->ordered()
                ->where(function ($q) {
                    $q->whereNull('academic_year_id')
                        ->orWhere('code', 'like', 'SCH-%');
                })
                ->get();
        }

        return view('public.groups', compact('schoolYears', 'schoolSubjects'));
    }

    public function year(string $slug): View
    {
        abort_unless(Schema::hasTable('academic_years'), 404);

        $year = AcademicYear::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $classes = TutoringGroup::query()
            ->active()
            ->collective()
            ->where('academic_year_id', $year->id)
            ->with([
                'instructor:id,name',
                'schoolSubject:id,name',
                'cohorts' => fn ($q) => $q->visible()->orderByDesc('starts_at'),
            ])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->get();

        $servicePackages = ServicePackage::query()
            ->commercial()
            ->where('plan_type', ServicePackage::PLAN_SCHOOL)
            ->forSchoolProgram((int) $year->id)
            ->with(['academicYear:id,name', 'academicSubject:id,name'])
            ->ordered()
            ->get();

        if ($servicePackages->isEmpty()) {
            $servicePackages = ServicePackage::query()
                ->commercial()
                ->whereIn('plan_type', [ServicePackage::PLAN_SCHOOL, ServicePackage::PLAN_PREMIER])
                ->ordered()
                ->get();
        }

        return view('public.school-year', compact('year', 'classes', 'servicePackages'));
    }

    public function groupCourses(): View
    {
        $groups = $this->collectiveQuery()->paginate(12);
        $groupCount = $groups->total();

        return view('public.groups-courses', [
            'groups' => $groups,
            'courses' => $groups,
            'groupCount' => $groupCount,
        ]);
    }

    public function oneToOneCourses(): RedirectResponse
    {
        // توحيد مدخل الحصص الخاصة تحت صفحة المعلمين
        return redirect()->route('public.instructors.index', ['focus' => 'private']);
    }

    public function show(string $slug): View
    {
        $group = TutoringGroup::query()
            ->active()
            ->where('slug', $slug)
            ->with([
                'instructor:id,name',
                'schoolYear:id,name,slug,level_number',
                'schoolSubject:id,name',
                'cohorts' => fn ($q) => $q->visible()->orderByDesc('starts_at'),
                'packages' => fn ($q) => $q->active()->orderBy('sort_order')->orderBy('duration_months'),
            ])
            ->firstOrFail();

        $slots = TutoringGroupAvailabilityService::availableSlots($group);
        $slotsByDate = $slots->groupBy('date');
        $cohorts = $group->cohorts;
        $packages = $group->packages;

        $creditUnits = 0;
        if (auth()->check()) {
            $scope = StudentEntitlementService::scopeForTutoringGroup($group);
            $creditUnits = StudentEntitlementService::unitsLeft(
                (int) auth()->id(),
                $scope,
                (int) $group->id,
                $group->academic_year_id ? (int) $group->academic_year_id : null,
                $group->academic_subject_id ? (int) $group->academic_subject_id : null,
            );
        }

        $servicePackages = collect();
        if ($group->isCollective() || $group->academic_year_id) {
            $servicePackages = ServicePackage::query()
                ->commercial()
                ->whereIn('plan_type', [ServicePackage::PLAN_SCHOOL, ServicePackage::PLAN_PREMIER])
                ->forSchoolProgram(
                    $group->academic_year_id ? (int) $group->academic_year_id : null,
                    $group->academic_subject_id ? (int) $group->academic_subject_id : null,
                )
                ->with(['academicYear:id,name', 'academicSubject:id,name'])
                ->ordered()
                ->limit(6)
                ->get();
        }

        return view('public.groups-show', compact(
            'group',
            'slots',
            'slotsByDate',
            'cohorts',
            'packages',
            'creditUnits',
            'servicePackages',
        ));
    }

    public function book(Request $request, string $slug): RedirectResponse
    {
        $group = TutoringGroup::query()->active()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'cohort_id' => ['nullable', 'integer', 'exists:tutoring_group_cohorts,id'],
            'guest_name' => ['nullable', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'student_notes' => ['nullable', 'string', 'max:2000'],
            'use_credit' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        // Logged-in student with session credits: confirm booking + Live room immediately
        if ($user && $request->boolean('use_credit')) {
            try {
                $booking = TutoringGroupCheckoutService::bookFromEntitlement(
                    $user,
                    $group,
                    Carbon::parse($data['starts_at'])
                );
            } catch (InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['starts_at' => $e->getMessage()]);
            }

            return redirect()
                ->route('student.tutoring-bookings.show', $booking)
                ->with('success', 'تم تأكيد الحجز وحجز وحدة من رصيدك وإنشاء غرفة Live. تُخصم الوحدة نهائياً بعد إكمال الحصة.');
        }

        if (! $user) {
            $data['guest_name'] = $data['guest_name'] ?? null;
            if (empty($data['guest_name'])) {
                return back()->withInput()->withErrors(['guest_name' => 'الاسم مطلوب لغير المسجّلين.']);
            }
        } else {
            $data['guest_name'] = $data['guest_name'] ?? $user->name;
            $data['guest_email'] = $data['guest_email'] ?? $user->email;
            $data['guest_phone'] = $data['guest_phone'] ?? ($user->phone ?? null);
        }

        try {
            TutoringGroupAvailabilityService::book($group, $data, $user?->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['starts_at' => $e->getMessage()]);
        }

        return redirect()
            ->route('public.groups.show', $group->slug)
            ->with('success', 'تم إرسال طلب الحجز بنجاح. سنتواصل معك بعد المراجعة.');
    }

    protected function baseQuery()
    {
        return TutoringGroup::query()
            ->active()
            ->with(['instructor:id,name', 'schoolYear:id,name', 'schoolSubject:id,name'])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('created_at');
    }

    protected function collectiveQuery()
    {
        return $this->baseQuery()->collective();
    }

    protected function individualQuery()
    {
        return $this->baseQuery()->individual();
    }
}
