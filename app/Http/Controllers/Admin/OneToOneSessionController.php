<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OneToOneSession;
use App\Models\ServicePackage;
use App\Models\StudentServiceEntitlement;
use App\Models\User;
use App\Services\OneToOneAvailabilityService;
use App\Services\OneToOneSessionService;
use App\Services\StudentEntitlementService;
use App\Support\AppTimezone;
use App\Services\OneToOneSessionUnlockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OneToOneSessionController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->get('status', 'all');
        $instructorId = (int) $request->get('instructor_id', 0);

        $query = OneToOneSession::query()
            ->with(['student', 'instructor', 'course', 'classroomMeeting', 'bookedBy', 'entitlement'])
            ->orderByRaw("CASE status WHEN 'pending_schedule' THEN 0 WHEN 'scheduled' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END")
            ->orderBy('scheduled_at')
            ->orderBy('session_number')
            ->orderByDesc('id');

        if (in_array($status, array_keys(OneToOneSession::statusLabels()), true)) {
            $query->where('status', $status);
        }
        if ($instructorId > 0) {
            $query->where('instructor_id', $instructorId);
        }

        $sessions = $query->paginate(30)->withQueryString();

        $stats = [
            'pending' => OneToOneSession::where('status', OneToOneSession::STATUS_PENDING)->count(),
            'scheduled' => OneToOneSession::where('status', OneToOneSession::STATUS_SCHEDULED)->count(),
            'completed' => OneToOneSession::where('status', OneToOneSession::STATUS_COMPLETED)->count(),
        ];

        $instructors = User::query()
            ->whereIn('id', OneToOneSession::query()->distinct()->pluck('instructor_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.one-to-one-sessions.index', compact('sessions', 'stats', 'status', 'instructors', 'instructorId'));
    }

    public function create(Request $request): View
    {
        $selectedEntitlementId = (int) $request->integer('entitlement_id');

        return view('admin.one-to-one-sessions.create', [
            'entitlements' => StudentServiceEntitlement::query()
                ->active()
                ->whereIn('scope', [ServicePackage::SCOPE_PRIVATE_LESSONS, ServicePackage::SCOPE_GLOBAL])
                ->with('user:id,name,email,phone')
                ->orderBy('expires_at')
                ->get()
                ->filter(fn (StudentServiceEntitlement $entitlement) => StudentEntitlementService::bookableUnitsLeft($entitlement) > 0)
                ->values(),
            'instructors' => User::query()
                ->whereIn('role', ['instructor', 'teacher'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'timezone']),
            'selectedEntitlementId' => $selectedEntitlementId,
            'slotsUrl' => route('admin.placement.slots'),
            'placementUrl' => route('admin.placement.create', ['mode' => 'private']),
            'grantUrl' => route('admin.student-entitlements.create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_service_entitlement_id' => ['required', 'exists:student_service_entitlements,id'],
            'instructor_id' => ['required', 'exists:users,id'],
            'scheduled_at' => ['nullable', 'date'],
            'timezone' => AppTimezone::inputRules(),
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $entitlement = StudentServiceEntitlement::query()->with('user')->findOrFail($data['student_service_entitlement_id']);
        $instructor = User::query()->findOrFail($data['instructor_id']);
        $data = AppTimezone::shiftRequestDateTime($request, $data, 'scheduled_at', mustBeFuture: true, fallbackUser: $instructor);
        if (! $instructor->isInstructor()) {
            return back()->withInput()->with('error', 'المستخدم المحدد ليس معلماً.');
        }
        if (! in_array($entitlement->scope, [ServicePackage::SCOPE_PRIVATE_LESSONS, ServicePackage::SCOPE_GLOBAL], true)
            || ! $entitlement->isActive()
            || StudentEntitlementService::bookableUnitsLeft($entitlement) < 1) {
            return back()->withInput()->with('error', 'الرصيد غير صالح لحصة خاصة أو لا توجد وحدة قابلة للحجز.');
        }

        $session = OneToOneSession::create([
            'student_service_entitlement_id' => $entitlement->id,
            'instructor_id' => $instructor->id,
            'student_id' => $entitlement->user_id,
            'session_number' => (int) OneToOneSession::query()->where('student_id', $entitlement->user_id)->max('session_number') + 1,
            'duration_minutes' => OneToOneSession::defaultDurationMinutes(),
            'status' => OneToOneSession::STATUS_PENDING,
            'booked_by_user_id' => $request->user()->id,
            'notes' => trim(($data['notes'] ?? '')."\nتسكين يدوي من الإدارة"),
        ]);

        if (! empty($data['scheduled_at'])) {
            try {
                OneToOneSessionService::scheduleSession(
                    $session,
                    $data['scheduled_at'],
                    OneToOneSession::defaultDurationMinutes(),
                    $request->user(),
                    false
                );
            } catch (\InvalidArgumentException $e) {
                $session->delete();

                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        return redirect()->route('admin.one-to-one-sessions.show', $session)
            ->with('success', empty($data['scheduled_at']) ? 'تم تسكين الطالب مع المعلم وبانتظار اختيار الموعد.' : 'تم التسكين والجدولة وإنشاء غرفة Live وإشعار الطرفين.');
    }

    public function show(OneToOneSession $oneToOneSession): View
    {
        $oneToOneSession->load(['student', 'instructor', 'course', 'classroomMeeting', 'enrollment', 'bookedBy', 'entitlement.order', 'studentUnlockedBy']);

        $availability = OneToOneAvailabilityService::rulesForInstructor((int) $oneToOneSession->instructor_id);
        $upcomingSlots = OneToOneAvailabilityService::availableSlots(
            (int) $oneToOneSession->instructor_id,
            now(),
            now()->addWeeks(2),
            (int) ($oneToOneSession->duration_minutes ?? 60),
            $oneToOneSession->id
        );

        return view('admin.one-to-one-sessions.show', [
            'session' => $oneToOneSession,
            'availability' => $availability,
            'upcomingSlots' => $upcomingSlots,
            'dayLabels' => OneToOneAvailabilityService::dayLabels(),
        ]);
    }

    public function updateSchedule(Request $request, OneToOneSession $oneToOneSession): RedirectResponse
    {
        if (! $oneToOneSession->isOpenPlacement()) {
            return back()->with('error', 'لا يمكن تعديل موعد حصة مكتملة أو ملغاة.');
        }

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:180'],
            'timezone' => AppTimezone::inputRules(),
        ]);

        $oneToOneSession->loadMissing('instructor');
        $clockTz = AppTimezone::resolveInput(
            is_string($data['timezone'] ?? null) ? $data['timezone'] : null,
            $oneToOneSession->instructor
        );
        $scheduledAt = AppTimezone::parseAppointmentInput((string) $data['scheduled_at'], $clockTz);
        if (! $scheduledAt) {
            return back()->withInput()->with('error', 'صيغة الموعد غير صالحة.');
        }

        $duration = (int) ($data['duration_minutes'] ?? $oneToOneSession->duration_minutes ?? OneToOneSession::defaultDurationMinutes());

        try {
            OneToOneSessionService::rescheduleSession(
                $oneToOneSession,
                $scheduledAt,
                $duration,
                $request->user(),
                requireAvailability: false,
                mustBeFuture: false,
                notify: true
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم تحديث موعد الحصة.');
    }

    public function unlockForStudent(OneToOneSession $oneToOneSession): RedirectResponse
    {
        if ($oneToOneSession->status !== OneToOneSession::STATUS_SCHEDULED) {
            return back()->with('error', 'يمكن فتح حصص مجدولة فقط.');
        }

        OneToOneSessionUnlockService::adminUnlockForStudent($oneToOneSession, auth()->user());

        return back()->with('success', 'تم فتح الحصة للطالب — يمكنه الدخول حتى لو لم تكتمل الحصة السابقة.');
    }

    public function revokeUnlockForStudent(OneToOneSession $oneToOneSession): RedirectResponse
    {
        OneToOneSessionUnlockService::adminRevokeUnlock($oneToOneSession);

        return back()->with('success', 'تم إلغاء الفتح اليدوي — يعود التسلسل التلقائي.');
    }

    public function destroy(Request $request, OneToOneSession $oneToOneSession): RedirectResponse
    {
        try {
            $count = OneToOneSessionService::cancelSession(
                $oneToOneSession,
                $request->boolean('series'),
                'حذف التسكين من لوحة الإدارة'
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($count < 1) {
            return back()->with('error', 'لا يوجد تسكين قائم للحذف.');
        }

        return redirect()
            ->route('admin.one-to-one-sessions.index')
            ->with('success', $count > 1
                ? 'تم حذف التسكين وإلغاء '.$count.' حصص. الرصيد المحجوز عاد للطالب.'
                : 'تم حذف التسكين وإرجاع الرصيد المحجوز.');
    }
}
