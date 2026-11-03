<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\OneToOneSession;
use App\Models\StudentInstructorAssignment;
use App\Services\OneToOneSessionService;
use App\Support\AppTimezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class OneToOneSessionController extends Controller
{
    public function index(Request $request): View
    {
        $instructorId = $request->user()->id;
        $tz = $request->user()->timezoneCode();
        $todayStart = now($tz)->startOfDay()->utc();
        $todayEnd = now($tz)->endOfDay()->utc();

        $sessions = OneToOneSession::query()
            ->where('instructor_id', $instructorId)
            ->with(['course', 'student', 'classroomMeeting', 'enrollment'])
            ->orderByRaw("CASE status WHEN 'pending_schedule' THEN 0 WHEN 'scheduled' THEN 1 ELSE 2 END")
            ->orderByRaw('scheduled_at IS NULL')
            ->orderBy('scheduled_at')
            ->orderBy('session_number')
            ->paginate(25);

        $studentColumns = $this->studentSelectColumns();

        $todaysSchedule = OneToOneSession::query()
            ->where('instructor_id', $instructorId)
            ->where('status', OneToOneSession::STATUS_SCHEDULED)
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->with(['course:id,title', 'student:'.$studentColumns, 'classroomMeeting'])
            ->orderBy('scheduled_at')
            ->get();

        $students = OneToOneSession::query()
            ->where('instructor_id', $instructorId)
            ->with(['student', 'course', 'enrollment'])
            ->get()
            ->groupBy('student_id')
            ->map(function ($group) {
                $first = $group->first();
                $enrollment = $first->enrollment;

                return [
                    'student' => $first->student,
                    'course' => $first->course,
                    'pending' => $group->where('status', OneToOneSession::STATUS_PENDING)->count(),
                    'scheduled' => $group->where('status', OneToOneSession::STATUS_SCHEDULED)->count(),
                    'total' => $group->count(),
                    'starts_at' => $enrollment?->activated_at ?? $enrollment?->enrolled_at ?? $enrollment?->created_at,
                    'ends_at' => $enrollment?->expires_at,
                    'notes' => $enrollment?->notes ?: $first->notes,
                ];
            })
            ->values();

        $newAssignments = collect();
        if (Schema::hasTable('student_instructor_assignments')) {
            $newAssignments = StudentInstructorAssignment::query()
                ->where('instructor_id', $instructorId)
                ->where('status', StudentInstructorAssignment::STATUS_ACTIVE)
                ->whereIn('scope', [
                    StudentInstructorAssignment::SCOPE_INDIVIDUAL,
                    StudentInstructorAssignment::SCOPE_COURSES,
                    StudentInstructorAssignment::SCOPE_GENERAL,
                ])
                ->where(function ($q) {
                    $q->whereNull('instructor_notified_at')
                        ->orWhere('created_at', '>=', now()->subDays(14));
                })
                ->with(['student:'.$studentColumns, 'academicYear:id,name'])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        }

        $lessonDuration = OneToOneSession::defaultDurationMinutes();

        return view('instructor.one-to-one-sessions.index', compact(
            'sessions',
            'students',
            'todaysSchedule',
            'newAssignments',
            'lessonDuration'
        ));
    }

    /**
     * أعمدة الطالب للـ eager load — بدون birth_date إن لم يكن العمود موجوداً بعد.
     */
    private function studentSelectColumns(): string
    {
        $cols = ['id', 'name'];
        if (Schema::hasColumn('users', 'birth_date')) {
            $cols[] = 'birth_date';
        }

        return implode(',', $cols);
    }

    public function show(OneToOneSession $oneToOneSession): View
    {
        abort_unless($oneToOneSession->instructor_id === auth()->id(), 403);

        $oneToOneSession->load(['course', 'student', 'classroomMeeting', 'enrollment']);

        return view('instructor.one-to-one-sessions.show', ['session' => $oneToOneSession]);
    }

    public function schedule(Request $request, OneToOneSession $oneToOneSession): RedirectResponse
    {
        abort_unless($oneToOneSession->instructor_id === auth()->id(), 403);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'in:50'],
            'timezone' => AppTimezone::inputRules(),
        ]);
        $data = AppTimezone::shiftRequestDateTime($request, $data, 'scheduled_at', mustBeFuture: true);

        try {
            OneToOneSessionService::scheduleSession(
                $oneToOneSession,
                $data['scheduled_at'],
                (int) ($data['duration_minutes'] ?? OneToOneSession::defaultDurationMinutes()),
                $request->user(),
                requireAvailability: true
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['scheduled_at' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'تم جدولة الحصة وإشعار الطالب.');
    }

    public function complete(OneToOneSession $oneToOneSession): RedirectResponse
    {
        abort_unless($oneToOneSession->instructor_id === auth()->id(), 403);

        try {
            OneToOneSessionService::markCompleted($oneToOneSession);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم تسجيل الحصة كمكتملة وخصم وحدة الرصيد وإغلاق غرفة Live.');
    }
}
