<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\ClassroomMeeting;
use App\Models\OneToOneSession;
use App\Models\TutorApplication;
use App\Models\TutoringGroup;
use App\Models\TutoringGroupBooking;
use App\Models\User;
use App\Services\OneToOneAvailabilityService;
use App\Services\OneToOneSessionService;
use App\Services\TutoringGroupAvailabilityService;
use App\Services\TutoringGroupOrchestrationService;
use App\Services\TeachingCalendarService;
use App\Support\WeeklyScheduleTime;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * مركز تحكم المعلم من الإدارة: بيانات، جدول، 1:1، حجوزات، كورسات.
 */
class TeacherControlController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $this->canManageTeachers($user))) {
                abort(403);
            }

            return $next($request);
        });
    }

    private function canManageTeachers(User $user): bool
    {
        if ($user->isAdmin() || in_array((string) $user->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        return $user->hasPermission('manage.users')
            || $user->hasPermission('manage.tutoring-groups')
            || $user->hasPermission('manage.courses');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        $instructors = User::query()
            ->whereIn('role', ['instructor', 'teacher'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        $ids = $instructors->getCollection()->pluck('id');

        $courseCounts = Schema::hasTable('advanced_courses')
            ? AdvancedCourse::query()
                ->whereIn('instructor_id', $ids)
                ->selectRaw('instructor_id, count(*) as total')
                ->groupBy('instructor_id')
                ->pluck('total', 'instructor_id')
            : collect();

        $bookingCounts = Schema::hasTable('tutoring_group_bookings')
            ? TutoringGroupBooking::query()
                ->whereIn('instructor_id', $ids)
                ->where('status', TutoringGroupBooking::STATUS_CONFIRMED)
                ->where('starts_at', '>=', now())
                ->selectRaw('instructor_id, count(*) as total')
                ->groupBy('instructor_id')
                ->pluck('total', 'instructor_id')
            : collect();

        $sessionCounts = Schema::hasTable('one_to_one_sessions')
            ? OneToOneSession::query()
                ->whereIn('instructor_id', $ids)
                ->whereIn('status', [OneToOneSession::STATUS_PENDING, OneToOneSession::STATUS_SCHEDULED])
                ->selectRaw('instructor_id, count(*) as total')
                ->groupBy('instructor_id')
                ->pluck('total', 'instructor_id')
            : collect();

        $summary = [
            'total' => User::query()->whereIn('role', ['instructor', 'teacher'])->count(),
            'active' => User::query()->whereIn('role', ['instructor', 'teacher'])->where('is_active', true)->count(),
            'inactive' => User::query()->whereIn('role', ['instructor', 'teacher'])->where('is_active', false)->count(),
        ];

        return view('admin.teachers.index', compact(
            'instructors',
            'search',
            'status',
            'summary',
            'courseCounts',
            'bookingCounts',
            'sessionCounts'
        ));
    }

    public function show(Request $request, User $teacher): View
    {
        $this->assertTeacher($teacher);

        $tab = (string) $request->query('tab', 'profile');
        if (! in_array($tab, ['profile', 'calendar', 'schedule', 'sessions', 'bookings', 'courses'], true)) {
            $tab = 'profile';
        }

        $range = (string) $request->query('range', 'upcoming');
        if (! in_array($range, ['upcoming', 'past', 'all'], true)) {
            $range = 'upcoming';
        }

        $workRules = TutoringGroupAvailabilityService::rulesForInstructor((int) $teacher->id);
        $workDayLabels = TutoringGroupAvailabilityService::dayLabels();
        $workGrouped = collect($workDayLabels)->map(function ($label, $day) use ($workRules) {
            return [
                'day' => (int) $day,
                'label' => $label,
                'rules' => $workRules->where('day_of_week', $day)->values(),
            ];
        })->values();
        $workSlotsFlat = $workRules->map(fn ($r) => [
            'day_of_week' => (int) $r->day_of_week,
            'start_time' => $r->startTimeString(),
            'end_time' => $r->endTimeString(),
            'slot_duration_minutes' => (int) $r->slot_duration_minutes,
            'applies_to' => (string) $r->applies_to,
            'note' => (string) ($r->note ?? ''),
        ])->values()->all();

        $otoRules = OneToOneAvailabilityService::rulesForInstructor((int) $teacher->id);
        $otoDayLabels = OneToOneAvailabilityService::dayLabels();
        $otoGrouped = collect($otoDayLabels)->map(function ($label, $day) use ($otoRules) {
            return [
                'day' => (int) $day,
                'label' => $label,
                'rules' => $otoRules->where('day_of_week', $day)->values(),
            ];
        })->values();
        $otoSlotsFlat = $otoRules->map(fn ($r) => [
            'day_of_week' => (int) $r->day_of_week,
            'start_time' => substr((string) $r->start_time, 0, 5),
            'end_time' => substr((string) $r->end_time, 0, 5),
            'slot_duration_minutes' => (int) $r->slot_duration_minutes,
        ])->values()->all();

        $sessions = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => 'sessions_page',
        ]);
        if (Schema::hasTable('one_to_one_sessions')) {
            $sessionsQuery = OneToOneSession::query()
                ->where('instructor_id', $teacher->id)
                ->with(['student:id,name,email,phone', 'classroomMeeting'])
                ->orderByRaw("CASE status WHEN 'pending_schedule' THEN 0 WHEN 'scheduled' THEN 1 ELSE 2 END")
                ->orderBy('scheduled_at');

            if ($range === 'upcoming') {
                $sessionsQuery->where(function ($q) {
                    $q->where('status', OneToOneSession::STATUS_PENDING)
                        ->orWhere(function ($inner) {
                            $inner->where('status', OneToOneSession::STATUS_SCHEDULED)
                                ->where('scheduled_at', '>=', now());
                        });
                });
            } elseif ($range === 'past') {
                $sessionsQuery->where(function ($q) {
                    $q->whereIn('status', [OneToOneSession::STATUS_COMPLETED, OneToOneSession::STATUS_CANCELLED])
                        ->orWhere(function ($inner) {
                            $inner->where('status', OneToOneSession::STATUS_SCHEDULED)
                                ->where('scheduled_at', '<', now());
                        });
                });
            }

            $sessions = $sessionsQuery->paginate(20, ['*'], 'sessions_page')->withQueryString();
        }

        $bookings = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => 'bookings_page',
        ]);
        if (Schema::hasTable('tutoring_group_bookings')) {
            $bookingsQuery = TutoringGroupBooking::query()
                ->where('instructor_id', $teacher->id)
                ->with(['user:id,name,email', 'tutoringGroup:id,title,type'])
                ->orderByDesc('starts_at');

            if ($range === 'upcoming') {
                $bookingsQuery->where('starts_at', '>=', now())
                    ->whereNotIn('status', [TutoringGroupBooking::STATUS_CANCELLED, TutoringGroupBooking::STATUS_COMPLETED]);
            } elseif ($range === 'past') {
                $bookingsQuery->where(function ($q) {
                    $q->where('starts_at', '<', now())
                        ->orWhereIn('status', [TutoringGroupBooking::STATUS_CANCELLED, TutoringGroupBooking::STATUS_COMPLETED]);
                });
            }

            $bookings = $bookingsQuery->paginate(20, ['*'], 'bookings_page')->withQueryString();
        }

        $courses = Schema::hasTable('advanced_courses')
            ? AdvancedCourse::query()
                ->where('instructor_id', $teacher->id)
                ->with(['academicSubject:id,name', 'academicYear:id,name'])
                ->orderBy('title')
                ->get()
            : collect();

        $groups = Schema::hasTable('tutoring_groups')
            ? TutoringGroup::query()
                ->where('instructor_id', $teacher->id)
                ->orderBy('title')
                ->get(['id', 'title', 'type', 'is_active'])
            : collect();

        $instructors = User::query()
            ->whereIn('role', ['instructor', 'teacher'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $application = null;
        if (Schema::hasTable('tutor_applications')) {
            $application = TutorApplication::query()
                ->where('user_id', $teacher->id)
                ->latest('id')
                ->first();
        }

        $stats = [
            'courses' => $courses->count(),
            'groups' => $groups->count(),
            'open_sessions' => Schema::hasTable('one_to_one_sessions')
                ? OneToOneSession::query()
                    ->where('instructor_id', $teacher->id)
                    ->whereIn('status', [OneToOneSession::STATUS_PENDING, OneToOneSession::STATUS_SCHEDULED])
                    ->count()
                : 0,
            'upcoming_bookings' => Schema::hasTable('tutoring_group_bookings')
                ? TutoringGroupBooking::query()
                    ->where('instructor_id', $teacher->id)
                    ->where('status', TutoringGroupBooking::STATUS_CONFIRMED)
                    ->where('starts_at', '>=', now())
                    ->count()
                : 0,
            'work_windows' => $workRules->count(),
            'oto_windows' => $otoRules->count(),
        ];

        $calendarEvents = collect();
        if ($tab === 'calendar') {
            $calendarEvents = TeachingCalendarService::withAdminLinks(
                TeachingCalendarService::forInstructor(
                    $teacher,
                    now()->subMonths(1),
                    now()->addMonths(3)
                )
            );
        }

        return view('admin.teachers.show', compact(
            'teacher',
            'tab',
            'range',
            'workGrouped',
            'workDayLabels',
            'workSlotsFlat',
            'otoGrouped',
            'otoDayLabels',
            'otoSlotsFlat',
            'sessions',
            'bookings',
            'courses',
            'groups',
            'instructors',
            'application',
            'stats',
            'calendarEvents'
        ));
    }

    public function calendarEvents(Request $request, User $teacher): JsonResponse
    {
        $this->assertTeacher($teacher);

        $events = TeachingCalendarService::withAdminLinks(
            TeachingCalendarService::forInstructor(
                $teacher,
                $request->get('start'),
                $request->get('end')
            )
        );

        return response()->json(TeachingCalendarService::toFullCalendar($events));
    }

    public function updateProfile(Request $request, User $teacher): RedirectResponse
    {
        $this->assertTeacher($teacher);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher->id)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->ignore($teacher->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'role' => ['required', Rule::in(['instructor', 'teacher'])],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $teacher->update($payload);

        return redirect()
            ->route('admin.teachers.show', ['teacher' => $teacher, 'tab' => 'profile'])
            ->with('success', 'تم تحديث بيانات المعلم.');
    }

    public function syncWorkSchedule(Request $request, User $teacher): RedirectResponse
    {
        $this->assertTeacher($teacher);

        $data = $request->validate(array_merge(
            WeeklyScheduleTime::slotTimeRules(240, true),
            ['timezone' => AppTimezone::inputRules()]
        ));

        AppTimezone::persistForUser(
            $teacher,
            is_string($data['timezone'] ?? null) ? $data['timezone'] : null
        );

        TutoringGroupAvailabilityService::syncRules((int) $teacher->id, $data['slots'] ?? []);

        return redirect()
            ->route('admin.teachers.show', ['teacher' => $teacher, 'tab' => 'schedule'])
            ->with('success', 'تم حفظ جدول عمل المجموعات.');
    }

    public function syncOneToOneAvailability(Request $request, User $teacher): RedirectResponse
    {
        $this->assertTeacher($teacher);

        $data = $request->validate(array_merge(
            WeeklyScheduleTime::slotTimeRules(180),
            ['timezone' => AppTimezone::inputRules()]
        ));

        AppTimezone::persistForUser(
            $teacher,
            is_string($data['timezone'] ?? null) ? $data['timezone'] : null
        );

        OneToOneAvailabilityService::syncRules((int) $teacher->id, $data['slots'] ?? []);

        return redirect()
            ->route('admin.teachers.show', ['teacher' => $teacher, 'tab' => 'schedule'])
            ->with('success', 'تم حفظ توفر الحصص الخاصة (1:1).');
    }

    public function scheduleOneToOne(Request $request, User $teacher, OneToOneSession $session): RedirectResponse
    {
        $this->assertTeacher($teacher);
        $this->assertSessionBelongs($teacher, $session);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'timezone' => AppTimezone::inputRules(),
            'force' => ['nullable', 'boolean'],
        ]);
        $data = AppTimezone::shiftRequestDateTime($request, $data, 'scheduled_at', mustBeFuture: false, fallbackUser: $teacher);

        try {
            $this->adminScheduleOrReschedule(
                $session,
                $data['scheduled_at'],
                $request->user(),
                ! $request->boolean('force')
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.teachers.show', ['teacher' => $teacher, 'tab' => 'sessions'])
            ->with('success', 'تم جدولة / إعادة جدولة الحصة الخاصة.');
    }

    public function completeOneToOne(Request $request, User $teacher, OneToOneSession $session): RedirectResponse
    {
        $this->assertTeacher($teacher);
        $this->assertSessionBelongs($teacher, $session);

        try {
            OneToOneSessionService::markCompleted($session);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.teachers.show', ['teacher' => $teacher, 'tab' => 'sessions'])
            ->with('success', 'تم إكمال الحصة وخصم الرصيد إن وُجد.');
    }

    public function cancelOneToOne(Request $request, User $teacher, OneToOneSession $session): RedirectResponse
    {
        $this->assertTeacher($teacher);
        $this->assertSessionBelongs($teacher, $session);

        if ($session->status === OneToOneSession::STATUS_COMPLETED) {
            return back()->with('error', 'لا يمكن إلغاء حصة مكتملة.');
        }

        try {
            OneToOneSessionService::cancelSession($session, false, 'إلغاء من مركز تحكم المعلم');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.teachers.show', ['teacher' => $teacher, 'tab' => 'sessions'])
            ->with('success', 'تم إلغاء الحصة الخاصة.');
    }

    public function reassignOneToOne(Request $request, User $teacher, OneToOneSession $session): RedirectResponse
    {
        $this->assertTeacher($teacher);
        $this->assertSessionBelongs($teacher, $session);

        $data = $request->validate([
            'instructor_id' => ['required', 'exists:users,id'],
        ]);

        $newInstructor = User::query()->findOrFail($data['instructor_id']);

        try {
            OneToOneSessionService::reassignInstructor($session, $newInstructor);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.teachers.show', ['teacher' => $newInstructor, 'tab' => 'sessions'])
            ->with('success', 'تم نقل الحصة إلى المعلم '.$newInstructor->name.' دون خصم رصيد.');
    }

    public function updateBookingStatus(Request $request, User $teacher, TutoringGroupBooking $booking): RedirectResponse
    {
        $this->assertTeacher($teacher);
        abort_unless((int) $booking->instructor_id === (int) $teacher->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,completed'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $booking->update([
            'admin_notes' => $data['admin_notes'] ?? $booking->admin_notes,
        ]);

        try {
            match ($data['status']) {
                TutoringGroupBooking::STATUS_CONFIRMED => TutoringGroupOrchestrationService::confirmBooking($booking),
                TutoringGroupBooking::STATUS_CANCELLED => TutoringGroupOrchestrationService::cancelBooking($booking),
                TutoringGroupBooking::STATUS_COMPLETED => TutoringGroupOrchestrationService::completeBooking($booking),
                default => $booking->update(['status' => $data['status']]),
            };
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.teachers.show', ['teacher' => $teacher, 'tab' => 'bookings'])
            ->with('success', 'تم تحديث حالة الحجز.');
    }

    private function assertTeacher(User $teacher): void
    {
        abort_unless($teacher->isInstructor() || $teacher->isTeacher(), 404);
    }

    private function assertSessionBelongs(User $teacher, OneToOneSession $session): void
    {
        abort_unless((int) $session->instructor_id === (int) $teacher->id, 404);
    }

    private function adminScheduleOrReschedule(
        OneToOneSession $session,
        Carbon $scheduledAt,
        User $actor,
        bool $requireAvailability
    ): void {
        if ($session->status === OneToOneSession::STATUS_SCHEDULED && $session->classroom_meeting_id) {
            if ($requireAvailability && ! OneToOneAvailabilityService::isSlotAvailable(
                (int) $session->instructor_id,
                $scheduledAt,
                (int) ($session->duration_minutes ?: OneToOneSession::defaultDurationMinutes()),
                $session->id
            )) {
                throw new \InvalidArgumentException('هذا الموعد غير متاح — ربما حُجز أو خارج جدول المعلم.');
            }

            $session->loadMissing('classroomMeeting');
            $session->update([
                'scheduled_at' => $scheduledAt,
                'booked_by_user_id' => $actor->id,
            ]);
            if ($session->classroomMeeting) {
                $session->classroomMeeting->update([
                    'scheduled_for' => $scheduledAt,
                    'planned_duration_minutes' => (int) ($session->duration_minutes ?: OneToOneSession::defaultDurationMinutes()),
                ]);
            }

            return;
        }

        if ($session->status === OneToOneSession::STATUS_PENDING) {
            OneToOneSessionService::scheduleSession(
                $session,
                $scheduledAt,
                (int) ($session->duration_minutes ?: OneToOneSession::defaultDurationMinutes()),
                $actor,
                $requireAvailability
            );

            return;
        }

        // fallback: create meeting if missing
        if (! in_array($session->status, [OneToOneSession::STATUS_PENDING, OneToOneSession::STATUS_SCHEDULED], true)) {
            throw new \InvalidArgumentException('لا يمكن جدولة هذه الحصة في حالتها الحالية.');
        }

        $duration = (int) ($session->duration_minutes ?: OneToOneSession::defaultDurationMinutes());
        if ($requireAvailability && ! OneToOneAvailabilityService::isSlotAvailable(
            (int) $session->instructor_id,
            $scheduledAt,
            $duration,
            $session->id
        )) {
            throw new \InvalidArgumentException('هذا الموعد غير متاح — ربما حُجز أو خارج جدول المعلم.');
        }

        $meeting = ClassroomMeeting::create([
            'user_id' => $session->instructor_id,
            'one_to_one_session_id' => $session->id,
            'code' => ClassroomMeeting::generateCode(),
            'room_name' => 'one-to-one-'.$session->id.'-'.Str::lower(Str::random(6)),
            'title' => 'حصة 1:1',
            'scheduled_for' => $scheduledAt,
            'planned_duration_minutes' => $duration,
            'max_participants' => 4,
        ]);

        $session->update([
            'status' => OneToOneSession::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $duration,
            'classroom_meeting_id' => $meeting->id,
            'booked_by_user_id' => $actor->id,
        ]);
    }
}
