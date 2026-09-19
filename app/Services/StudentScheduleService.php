<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\OneToOneSession;
use App\Models\TutoringClassSession;
use App\Models\TutoringCohortEnrollment;
use App\Models\TutoringGroupBooking;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class StudentScheduleService
{
    /**
     * @return Collection<int, object>
     */
    public static function weekAppointments(User $user, ?Carbon $weekStart = null): Collection
    {
        $tz = AppTimezone::forUser($user);
        $start = ($weekStart ?: now($tz))->copy()->timezone($tz)->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();
        // Query bounds in UTC
        $startUtc = $start->copy()->utc();
        $endUtc = $end->copy()->utc();

        $items = collect();

        if (Schema::hasTable('one_to_one_sessions')) {
            OneToOneSession::query()
                ->with(['course:id,title', 'instructor:id,name', 'classroomMeeting'])
                ->where('student_id', $user->id)
                ->where('status', OneToOneSession::STATUS_SCHEDULED)
                ->whereBetween('scheduled_at', [$startUtc, $endUtc])
                ->orderBy('scheduled_at')
                ->get()
                ->each(function (OneToOneSession $session) use ($items) {
                    $dur = max(30, (int) ($session->duration_minutes ?: 50));
                    $starts = $session->scheduled_at;
                    $items->push((object) [
                        'key' => 'private:'.$session->id,
                        'type' => 'private',
                        'ref_id' => $session->id,
                        'title' => $session->course?->title ?: 'حصة خاصة',
                        'subtitle' => $session->instructor?->name ?: 'معلم خاص',
                        'starts_at' => $starts,
                        'ends_at' => $starts?->copy()->addMinutes($dur),
                        'join_url' => Route::has('student.schedule.join')
                            ? route('student.schedule.join', ['type' => 'private', 'id' => $session->id])
                            : $session->joinUrl(),
                        'color' => 'gold',
                        'icon' => 'fa-chalkboard-teacher',
                    ]);
                });
        }

        if (student_ui('show_classes') && Schema::hasTable('tutoring_class_sessions') && Schema::hasTable('tutoring_cohort_enrollments')) {
            $cohortIds = TutoringCohortEnrollment::query()
                ->where('user_id', $user->id)
                ->where('status', TutoringCohortEnrollment::STATUS_ACTIVE)
                ->pluck('tutoring_group_cohort_id');

            if ($cohortIds->isNotEmpty()) {
                TutoringClassSession::query()
                    ->with(['cohort:id,title', 'tutoringGroup:id,title', 'classroomMeeting'])
                    ->whereIn('tutoring_group_cohort_id', $cohortIds)
                    ->where('status', '!=', TutoringClassSession::STATUS_CANCELLED)
                    ->whereBetween('starts_at', [$startUtc, $endUtc])
                    ->orderBy('starts_at')
                    ->get()
                    ->each(function (TutoringClassSession $session) use ($items) {
                        $items->push((object) [
                            'key' => 'class:'.$session->id,
                            'type' => 'class',
                            'ref_id' => $session->id,
                            'title' => $session->displayTitle(),
                            'subtitle' => $session->cohort?->title
                                ?: ($session->tutoringGroup?->title ?: 'فصل جماعي'),
                            'starts_at' => $session->starts_at,
                            'ends_at' => $session->ends_at,
                            'join_url' => Route::has('student.schedule.join')
                                ? route('student.schedule.join', ['type' => 'class', 'id' => $session->id])
                                : $session->joinUrl(),
                            'color' => 'blue',
                            'icon' => 'fa-users',
                        ]);
                    });
            }
        }

        if (student_ui('show_classes') && Schema::hasTable('tutoring_group_bookings')) {
            TutoringGroupBooking::query()
                ->with(['tutoringGroup:id,title', 'classroomMeeting', 'cohort:id,title'])
                ->where('user_id', $user->id)
                ->where('status', TutoringGroupBooking::STATUS_CONFIRMED)
                ->whereBetween('starts_at', [$startUtc, $endUtc])
                ->orderBy('starts_at')
                ->get()
                ->each(function (TutoringGroupBooking $booking) use ($items) {
                    $dup = $items->first(function ($item) use ($booking) {
                        return $item->type === 'class'
                            && $item->starts_at
                            && $booking->starts_at
                            && abs($item->starts_at->diffInMinutes($booking->starts_at, false)) < 5;
                    });
                    if ($dup) {
                        return;
                    }

                    $items->push((object) [
                        'key' => 'booking:'.$booking->id,
                        'type' => 'booking',
                        'ref_id' => $booking->id,
                        'title' => $booking->tutoringGroup?->title ?: 'حصة مجموعة',
                        'subtitle' => $booking->cohort?->title ?: 'مجموعة',
                        'starts_at' => $booking->starts_at,
                        'ends_at' => $booking->ends_at,
                        'join_url' => Route::has('student.schedule.join')
                            ? route('student.schedule.join', ['type' => 'booking', 'id' => $booking->id])
                            : $booking->joinUrl(),
                        'color' => 'teal',
                        'icon' => 'fa-school',
                    ]);
                });
        }

        return $items->sortBy('starts_at')->values();
    }

    /**
     * @return Collection<int, object>
     */
    public static function weekDays(User $user, ?Carbon $weekStart = null): Collection
    {
        $tz = AppTimezone::forUser($user);
        $start = ($weekStart ?: now($tz))->copy()->timezone($tz)->startOfWeek(Carbon::SATURDAY);
        $appointments = self::weekAppointments($user, $start);
        $today = now($tz)->toDateString();

        $labels = [
            Carbon::SATURDAY => ['السبت', 'سبت'],
            Carbon::SUNDAY => ['الأحد', 'أحد'],
            Carbon::MONDAY => ['الإثنين', 'إثن'],
            Carbon::TUESDAY => ['الثلاثاء', 'ثلا'],
            Carbon::WEDNESDAY => ['الأربعاء', 'أرب'],
            Carbon::THURSDAY => ['الخميس', 'خمي'],
            Carbon::FRIDAY => ['الجمعة', 'جمع'],
        ];

        $days = collect();
        foreach (CarbonPeriod::create($start, $start->copy()->addDays(6)) as $date) {
            /** @var Carbon $date */
            $dayKey = $date->toDateString();
            $dow = (int) $date->dayOfWeek;
            $days->push((object) [
                'date' => $date->copy(),
                'label' => $labels[$dow][0] ?? $date->translatedFormat('l'),
                'short' => $labels[$dow][1] ?? $date->format('D'),
                'is_today' => $dayKey === $today,
                'items' => $appointments->filter(
                    fn ($a) => $a->starts_at && $a->starts_at->toDateString() === $dayKey
                )->values(),
            ]);
        }

        return $days;
    }

    public static function resolveJoinUrl(User $user, string $type, int $id): ?string
    {
        if ($type === 'private') {
            $session = OneToOneSession::query()
                ->with('classroomMeeting')
                ->where('student_id', $user->id)
                ->where('id', $id)
                ->first();

            if (! $session || ! OneToOneSessionUnlockService::canStudentJoin($session, $user)) {
                return null;
            }

            return $session->joinUrl();
        }

        if ($type === 'class') {
            return self::joinClassSession($user, $id);
        }

        if ($type === 'booking') {
            return TutoringGroupBooking::query()
                ->with('classroomMeeting')
                ->where('user_id', $user->id)
                ->where('id', $id)
                ->first()
                ?->joinUrl();
        }

        return null;
    }

    protected static function joinClassSession(User $user, int $id): ?string
    {
        $session = TutoringClassSession::query()
            ->with(['classroomMeeting', 'cohort'])
            ->find($id);

        if (! $session?->cohort || ! TutoringClassService::userCanAccessCohort($user, $session->cohort)) {
            return null;
        }

        $meeting = TutoringClassService::ensureSessionMeeting($session);
        TutoringClassService::markAttendanceOnJoin($session, $user);

        if ($session->status === TutoringClassSession::STATUS_SCHEDULED) {
            $session->update(['status' => TutoringClassSession::STATUS_LIVE]);
        }

        return ClassroomMeetingAccessService::platformEnterUrl($meeting);
    }

    public static function sendUpcomingReminders(int $minutes = 30): int
    {
        $windowStart = now()->addMinutes($minutes - 1);
        $windowEnd = now()->addMinutes($minutes + 1);
        $sent = 0;

        if (Schema::hasTable('one_to_one_sessions')) {
            OneToOneSession::query()
                ->with(['course', 'classroomMeeting'])
                ->where('status', OneToOneSession::STATUS_SCHEDULED)
                ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
                ->get()
                ->each(function (OneToOneSession $session) use (&$sent, $minutes) {
                    if (! $session->student_id) {
                        return;
                    }
                    $sent += self::notifyOnce(
                        (int) $session->student_id,
                        'reminder_private_'.$session->id.'_'.$session->scheduled_at?->timestamp,
                        'تذكير: حصتك الخاصة بعد '.$minutes.' دقيقة',
                        ($session->course?->title ?: 'حصة خاصة').' · '.$session->scheduled_at?->format('H:i'),
                        Route::has('student.schedule.join')
                            ? route('student.schedule.join', ['type' => 'private', 'id' => $session->id])
                            : $session->joinUrl(),
                    );
                });
        }

        if (Schema::hasTable('tutoring_class_sessions') && Schema::hasTable('tutoring_cohort_enrollments')) {
            TutoringClassSession::query()
                ->with(['cohort', 'tutoringGroup', 'classroomMeeting'])
                ->where('status', '!=', TutoringClassSession::STATUS_CANCELLED)
                ->whereBetween('starts_at', [$windowStart, $windowEnd])
                ->get()
                ->each(function (TutoringClassSession $session) use (&$sent, $minutes) {
                    $userIds = TutoringCohortEnrollment::query()
                        ->where('tutoring_group_cohort_id', $session->tutoring_group_cohort_id)
                        ->where('status', TutoringCohortEnrollment::STATUS_ACTIVE)
                        ->pluck('user_id');

                    foreach ($userIds as $userId) {
                        $sent += self::notifyOnce(
                            (int) $userId,
                            'reminder_class_'.$session->id.'_'.$session->starts_at?->timestamp,
                            'تذكير: حصتك بعد '.$minutes.' دقيقة',
                            $session->displayTitle().' · '.$session->starts_at?->format('H:i'),
                            route('student.schedule.join', ['type' => 'class', 'id' => $session->id]),
                        );
                    }
                });
        }

        return $sent;
    }

    protected static function notifyOnce(int $userId, string $dedupeKey, string $title, string $message, ?string $actionUrl): int
    {
        $exists = Notification::query()
            ->where('user_id', $userId)
            ->where('type', 'reminder')
            ->where('message', 'like', '%['.$dedupeKey.']%')
            ->exists();

        if (! $exists) {
            try {
                $exists = Notification::query()
                    ->where('user_id', $userId)
                    ->where('type', 'reminder')
                    ->where('data->dedupe', $dedupeKey)
                    ->exists();
            } catch (\Throwable) {
            }
        }

        if ($exists) {
            return 0;
        }

        try {
            Notification::create([
                'user_id' => $userId,
                'sender_id' => null,
                'title' => $title,
                'message' => $message,
                'type' => 'reminder',
                'priority' => 'high',
                'audience' => 'student',
                'action_url' => $actionUrl,
                'action_text' => 'دخول الحصة',
                'data' => ['dedupe' => $dedupeKey],
            ]);
        } catch (\Throwable) {
            Notification::create([
                'user_id' => $userId,
                'sender_id' => null,
                'title' => $title,
                'message' => $message.' ['.$dedupeKey.']',
                'type' => 'reminder',
                'priority' => 'high',
                'audience' => 'student',
                'action_url' => $actionUrl,
                'action_text' => 'دخول الحصة',
            ]);
        }

        return 1;
    }
}
