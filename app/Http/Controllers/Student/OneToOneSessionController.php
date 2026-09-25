<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\OneToOneSession;
use App\Models\User;
use App\Services\OneToOneSessionUnlockService;
use App\Services\OneToOneAvailabilityService;
use App\Services\OneToOneSessionService;
use App\Services\OneToOneSessionRatingService;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class OneToOneSessionController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = OneToOneSession::query()
            ->where('student_id', $request->user()->id)
            ->with(['course', 'instructor', 'classroomMeeting'])
            ->orderByRaw("CASE status WHEN 'scheduled' THEN 0 WHEN 'pending_schedule' THEN 1 ELSE 2 END")
            ->orderBy('scheduled_at')
            ->orderBy('session_number')
            ->paginate(20);

        return view('student.one-to-one-sessions.index', compact('sessions'));
    }

    public function show(OneToOneSession $oneToOneSession): View
    {
        abort_unless($oneToOneSession->student_id === auth()->id(), 403);

        $with = ['course', 'instructor', 'classroomMeeting', 'enrollment'];
        if (OneToOneSessionRatingService::isReady()) {
            $with[] = 'rating';
        }
        $oneToOneSession->load($with);

        $availableSlots = collect();
        if ($oneToOneSession->status === OneToOneSession::STATUS_PENDING) {
            $availableSlots = OneToOneAvailabilityService::availableSlots(
                (int) $oneToOneSession->instructor_id,
                now(),
                now()->addWeeks(4),
                (int) ($oneToOneSession->duration_minutes ?? \App\Models\OneToOneSession::defaultDurationMinutes())
            );
        }

        return view('student.one-to-one-sessions.show', [
            'session' => $oneToOneSession,
            'availableSlots' => $availableSlots,
            'canJoinSession' => OneToOneSessionUnlockService::canStudentJoin($oneToOneSession, auth()->user()),
            'sessionLockReason' => OneToOneSessionUnlockService::lockReason($oneToOneSession, auth()->user()),
            'mustRate' => OneToOneSessionRatingService::studentMustRate($oneToOneSession, auth()->user()),
            'sessionRating' => OneToOneSessionRatingService::isReady() ? $oneToOneSession->rating : null,
        ]);
    }

    public function rate(Request $request, OneToOneSession $oneToOneSession): RedirectResponse
    {
        abort_unless($oneToOneSession->student_id === auth()->id(), 403);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            OneToOneSessionRatingService::rate($oneToOneSession, $request->user(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['rating' => $e->getMessage()]);
        }

        return redirect()
            ->route('student.one-to-one-sessions.show', $oneToOneSession)
            ->with('success', 'شكراً لتقييمك — تم حفظه بنجاح.');
    }

    public function book(Request $request, OneToOneSession $oneToOneSession): RedirectResponse
    {
        abort_unless($oneToOneSession->student_id === auth()->id(), 403);
        abort_unless($oneToOneSession->status === OneToOneSession::STATUS_PENDING, 422);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        try {
            OneToOneSessionService::scheduleSession(
                $oneToOneSession,
                AppTimezone::parseAppointmentInput((string) $data['scheduled_at']) ?? Carbon::parse($data['scheduled_at']),
                (int) ($oneToOneSession->duration_minutes ?? \App\Models\OneToOneSession::defaultDurationMinutes()),
                $request->user(),
                requireAvailability: true
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['scheduled_at' => $e->getMessage()]);
        }

        return redirect()
            ->route('student.one-to-one-sessions.show', $oneToOneSession)
            ->with('success', __('student.one_to_one_booking_success'));
    }

    /**
     * حجز موعد من صفحة المعلم العامة — يتطلب اشتراك باقة.
     */
    public function bookWithInstructor(Request $request, User $instructor): RedirectResponse
    {
        abort_unless($request->user()->isStudent(), 403);
        abort_unless($instructor->isInstructor(), 404);

        $data = $request->validate([
            'booking_style' => ['nullable', 'in:single,monthly,multi'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'scheduled_ats' => ['nullable', 'array', 'max:40'],
            'scheduled_ats.*' => ['date', 'after:now'],
            'weeks' => ['nullable', 'integer', 'min:1', 'max:16'],
            'weekly_slots' => ['nullable', 'array', 'max:7'],
            'weekly_slots.*.day_of_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'weekly_slots.*.time' => ['nullable', 'string', 'max:8'],
        ]);

        $style = $data['booking_style'] ?? 'single';

        try {
            if ($style === 'monthly') {
                $weekly = collect($data['weekly_slots'] ?? [])
                    ->filter(fn ($row) => ! empty($row['day_of_week']) && ! empty($row['time']))
                    ->values()
                    ->all();
                $sessions = OneToOneSessionService::bookMonthlySeriesWithInstructor(
                    $request->user(),
                    $instructor,
                    $weekly,
                    (int) ($data['weeks'] ?? 4)
                );

                return redirect()
                    ->route('student.one-to-one-sessions.index')
                    ->with('success', 'تم تثبيت '.$sessions->count().' حصص شهرياً مع المعلم.');
            }

            if ($style === 'multi') {
                $ats = $data['scheduled_ats'] ?? [];
                if (count($ats) < 1) {
                    return back()->withInput()->withErrors(['scheduled_ats' => 'اختر موعداً واحداً على الأقل.']);
                }
                $sessions = OneToOneSessionService::bookMultipleWithInstructor(
                    $request->user(),
                    $instructor,
                    $ats
                );

                return redirect()
                    ->route('student.one-to-one-sessions.index')
                    ->with('success', 'تم حجز '.$sessions->count().' حصص مع المعلم.');
            }

            if (empty($data['scheduled_at'])) {
                return back()->withInput()->withErrors(['scheduled_at' => 'اختر موعداً.']);
            }

            $session = OneToOneSessionService::bookStandaloneWithInstructor(
                $request->user(),
                $instructor,
                AppTimezone::parseAppointmentInput((string) $data['scheduled_at']) ?? Carbon::parse($data['scheduled_at'])
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['scheduled_at' => $e->getMessage()]);
        }

        return redirect()
            ->route('student.one-to-one-sessions.show', $session)
            ->with('success', __('student.one_to_one_booking_success'));
    }
}
