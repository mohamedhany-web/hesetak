<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassFeedPost;
use App\Models\TutoringClassSession;
use App\Models\TutoringCohortEnrollment;
use App\Models\TutoringGroupCohort;
use App\Models\ServicePackage;
use App\Services\ClassFeedService;
use App\Services\StudentEntitlementService;
use App\Services\StudentSchoolGameService;
use App\Services\StudentSchoolHomeService;
use App\Services\TutoringClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ClassController extends Controller
{
    public function index(Request $request, StudentSchoolHomeService $home): View|RedirectResponse
    {
        if (! student_ui('show_classes')) {
            return redirect()->route('dashboard');
        }

        $data = $home->build($request->user(), [
            'q' => trim((string) $request->query('q', '')),
            'sort' => (string) $request->query('sort', 'classes'),
            'view' => 'week',
            'week' => (string) $request->query('week', ''),
        ]);

        $cohortIds = TutoringCohortEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->where('status', TutoringCohortEnrollment::STATUS_ACTIVE)
            ->pluck('tutoring_group_cohort_id');

        $upcoming = TutoringClassSession::query()
            ->with(['cohort.tutoringGroup.instructor', 'classroomMeeting'])
            ->whereIn('tutoring_group_cohort_id', $cohortIds)
            ->where('status', '!=', TutoringClassSession::STATUS_CANCELLED)
            ->where('starts_at', '>=', now()->subHour())
            ->orderBy('starts_at')
            ->limit(12)
            ->get();

        $nextJoinable = $upcoming->first(fn (TutoringClassSession $s) => $s->isJoinable());

        return view('student.classes.index', array_merge($data, [
            'upcoming' => $upcoming,
            'nextJoinable' => $nextJoinable,
            'searchQuery' => (string) ($data['searchQuery'] ?? $request->query('q', '')),
            'sortMode' => (string) ($data['sortMode'] ?? $request->query('sort', 'classes')),
        ]));
    }

    public function show(Request $request, TutoringGroupCohort $cohort): View|RedirectResponse
    {
        if (! student_ui('show_classes')) {
            return redirect()->route('dashboard');
        }

        abort_unless(TutoringClassService::userCanAccessCohort($request->user(), $cohort), 403);

        $cohort->load([
            'tutoringGroup.instructor',
            'classSessions' => fn ($q) => $q->with('classroomMeeting')->orderBy('session_number'),
            'activeEnrollments.user',
        ]);

        $enrollment = TutoringCohortEnrollment::query()
            ->where('tutoring_group_cohort_id', $cohort->id)
            ->where('user_id', $request->user()->id)
            ->where('status', TutoringCohortEnrollment::STATUS_ACTIVE)
            ->first();

        $feedCount = 0;
        if (ClassFeedService::tablesReady()) {
            $feedCount = ClassFeedPost::query()
                ->where('tutoring_group_cohort_id', $cohort->id)
                ->where('is_hidden', false)
                ->count();
        }

        return view('student.classes.show', [
            'cohort' => $cohort,
            'enrollment' => $enrollment,
            'feedCount' => $feedCount,
            'leaderboard' => StudentSchoolGameService::cohortLeaderboard((int) $cohort->id, 8),
            'game' => StudentSchoolGameService::profileSnapshot($request->user()),
        ]);
    }

    public function joinSession(Request $request, TutoringClassSession $session): RedirectResponse
    {
        if (! student_ui('show_classes')) {
            return redirect()->route('dashboard');
        }

        $session->loadMissing(['cohort.tutoringGroup', 'classroomMeeting']);
        abort_unless($session->cohort, 404);
        abort_unless(TutoringClassService::userCanAccessCohort($request->user(), $session->cohort), 403);

        if ($session->status === TutoringClassSession::STATUS_CANCELLED) {
            return back()->with('error', 'هذه الحصة ملغاة.');
        }

        if (! $session->isJoinable() && ! $request->user()->isAdmin()) {
            return back()->with('error', 'لم يفتح باب الدخول بعد. يُسمح قبل الموعد بـ 30 دقيقة.');
        }

        $meeting = TutoringClassService::ensureSessionMeeting($session);
        TutoringClassService::markAttendanceOnJoin($session, $request->user());

        if ($session->status === TutoringClassSession::STATUS_SCHEDULED) {
            $session->update(['status' => TutoringClassSession::STATUS_LIVE]);
        }

        return redirect()->to(\App\Services\ClassroomMeetingAccessService::platformEnterUrl($meeting));
    }

    /**
     * Manual join for free/open cohorts (admin may still require payment via checkout).
     */
    public function enroll(Request $request, TutoringGroupCohort $cohort): RedirectResponse
    {
        if (! student_ui('show_classes')) {
            return redirect()->route('dashboard');
        }

        if (! $cohort->isEnrollmentOpen()) {
            return back()->with('error', 'الانضمام مغلق لهذه الدفعة.');
        }

        $group = $cohort->tutoringGroup;
        $user = $request->user();

        if ($group && (float) ($group->price ?? 0) > 0) {
            $alreadyActive = TutoringCohortEnrollment::query()
                ->where('tutoring_group_cohort_id', $cohort->id)
                ->where('user_id', $user->id)
                ->where('status', TutoringCohortEnrollment::STATUS_ACTIVE)
                ->exists();

            if ($alreadyActive) {
                return redirect()
                    ->route('student.classes.show', $cohort)
                    ->with('info', 'أنت منضم لهذا الفصل بالفعل.');
            }

            $entitlement = StudentEntitlementService::availableFor(
                (int) $user->id,
                ServicePackage::SCOPE_TUTORING_COLLECTIVE,
                (int) $group->id,
                $group->academic_year_id ? (int) $group->academic_year_id : null,
                $group->academic_subject_id ? (int) $group->academic_subject_id : null,
            );

            if ($entitlement) {
                try {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($cohort, $user, $entitlement) {
                        // Debit seat unlock first so concurrent joins cannot double-spend.
                        StudentEntitlementService::consume($entitlement, 1);
                        TutoringClassService::enrollStudent(
                            $cohort,
                            $user,
                            entitlementId: (int) $entitlement->id,
                            countSeat: true,
                            notes: 'enrolled_with_service_package_credit',
                        );
                    });
                } catch (InvalidArgumentException $e) {
                    return back()->with('error', $e->getMessage());
                }

                return redirect()
                    ->route('student.classes.show', $cohort)
                    ->with('success', 'تم انضمامك للفصل باستخدام رصيد باقة المدرسة.');
            }

            return redirect()
                ->route('public.groups.checkout', ['slug' => $group->slug, 'cohort' => $cohort->id])
                ->with('info', 'أكمل الاشتراك للانضمام للفصل.');
        }

        try {
            TutoringClassService::enrollStudent($cohort, $user, countSeat: true);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('student.classes.show', $cohort)
            ->with('success', 'تم انضمامك للفصل.');
    }
}
