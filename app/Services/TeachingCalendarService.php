<?php

namespace App\Services;

use App\Models\ConsultationRequest;
use App\Models\Lecture;
use App\Models\LiveSession;
use App\Models\OneToOneSession;
use App\Models\TutoringClassSession;
use App\Models\TutoringGroupBooking;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * أحداث التدريس (1:1 / مجموعات / بث / استشارات) بلحظات UTC للتقويم.
 */
class TeachingCalendarService
{
    /**
     * @return Collection<int, object>
     */
    public static function forInstructor(User $instructor, mixed $startDate = null, mixed $endDate = null): Collection
    {
        $start = self::parseBound($startDate, now()->subMonths(1));
        $end = self::parseBound($endDate, now()->addMonths(3));
        $events = collect();

        if (Schema::hasTable('one_to_one_sessions')) {
            $events = $events->merge(OneToOneSession::calendarItemsForUser($instructor, $start, $end, 'instructor'));
        }
        if (Schema::hasTable('consultation_requests')) {
            $events = $events->merge(ConsultationRequest::calendarItemsForUser($instructor, $start, $end, 'instructor'));
        }
        $events = $events->merge(self::groupBookingsForInstructor($instructor, $start, $end));
        $events = $events->merge(self::classSessionsForInstructor($instructor, $start, $end));
        $events = $events->merge(self::liveSessionsForInstructor($instructor, $start, $end));
        $events = $events->merge(self::lecturesForInstructor($instructor, $start, $end));

        return $events->sortBy(fn ($event) => self::eventStart($event)?->timestamp ?? 0)->values();
    }

    /**
     * حصص الطالب المباشرة (بدون امتحانات/واجبات الكورسات).
     *
     * @return Collection<int, object>
     */
    public static function lessonsForStudent(User $student, mixed $startDate = null, mixed $endDate = null): Collection
    {
        $start = self::parseBound($startDate, now()->subMonths(1));
        $end = self::parseBound($endDate, now()->addMonths(3));
        $events = collect();

        if (Schema::hasTable('one_to_one_sessions')) {
            $events = $events->merge(OneToOneSession::calendarItemsForUser($student, $start, $end, 'student'));
        }
        if (Schema::hasTable('consultation_requests')) {
            $events = $events->merge(ConsultationRequest::calendarItemsForUser($student, $start, $end, 'student'));
        }
        $events = $events->merge(self::groupBookingsForStudent($student, $start, $end));
        $events = $events->merge(self::classSessionsForStudent($student, $start, $end));

        return $events->sortBy(fn ($event) => self::eventStart($event)?->timestamp ?? 0)->values();
    }

    /**
     * @param  Collection<int, object>  $events
     * @return list<array<string, mixed>>
     */
    public static function toFullCalendar(Collection $events, ?string $displayTimezone = null): array
    {
        // مع $displayTimezone: ISO بـ offset (FullCalendar بدون Luxon). بدونها: UTC+Z للتوافق مع الاختبارات القديمة.
        $tz = \App\Support\AppTimezone::normalize($displayTimezone);

        return $events->map(function ($event) use ($tz) {
            $start = self::eventStart($event);
            $end = self::eventEnd($event);

            return [
                'id' => $event->calendar_id ?? $event->id ?? null,
                'title' => $event->title ?? '',
                'start' => $tz
                    ? $start?->copy()->timezone($tz)->toIso8601String()
                    : $start?->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                'end' => $tz
                    ? $end?->copy()->timezone($tz)->toIso8601String()
                    : $end?->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                'allDay' => (bool) ($event->is_all_day ?? false),
                'color' => $event->color ?? '#0B3D91',
                'type' => $event->type ?? 'lesson',
                'url' => $event->url ?? null,
                'description' => $event->description ?? null,
                'extendedProps' => [
                    'priority' => $event->priority ?? 'high',
                    'location' => $event->location ?? null,
                    'type' => $event->type ?? 'lesson',
                ],
            ];
        })->values()->all();
    }

    /**
     * تحويل روابط التقويم إلى صفحات الإدارة عند العرض من تحكم المعلم.
     *
     * @param  Collection<int, object>  $events
     * @return Collection<int, object>
     */
    public static function withAdminLinks(Collection $events): Collection
    {
        return $events->map(function ($event) {
            $type = (string) ($event->type ?? '');
            $id = $event->id ?? null;
            if (! $id) {
                return $event;
            }

            $event->url = match ($type) {
                'one_to_one' => Route::has('admin.one-to-one-sessions.show')
                    ? route('admin.one-to-one-sessions.show', $id)
                    : ($event->url ?? null),
                'group' => Route::has('admin.tutoring-group-bookings.show')
                    ? route('admin.tutoring-group-bookings.show', $id)
                    : ($event->url ?? null),
                'consultation' => Route::has('admin.consultations.show')
                    ? route('admin.consultations.show', $id)
                    : ($event->url ?? null),
                'live' => Route::has('admin.live-sessions.show')
                    ? route('admin.live-sessions.show', $id)
                    : ($event->url ?? null),
                'lecture' => Route::has('admin.lectures.show')
                    ? route('admin.lectures.show', $id)
                    : ($event->url ?? null),
                default => $event->url ?? null,
            };

            return $event;
        });
    }

    /**
     * @return Collection<int, object>
     */
    private static function groupBookingsForInstructor(User $instructor, Carbon $start, Carbon $end): Collection
    {
        if (! Schema::hasTable('tutoring_group_bookings')) {
            return collect();
        }

        return TutoringGroupBooking::query()
            ->where('instructor_id', $instructor->id)
            ->whereIn('status', [TutoringGroupBooking::STATUS_PENDING, TutoringGroupBooking::STATUS_CONFIRMED])
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', $start)
            ->where('starts_at', '<=', $end)
            ->with(['tutoringGroup:id,title', 'user:id,name', 'classroomMeeting'])
            ->get()
            ->map(function (TutoringGroupBooking $booking) {
                $title = ($booking->tutoringGroup->title ?? 'مجموعة').' — '.($booking->user->name ?? 'طالب');
                $url = Route::has('instructor.tutoring-bookings.show')
                    ? route('instructor.tutoring-bookings.show', $booking)
                    : null;

                return self::event(
                    'group_booking_'.$booking->id,
                    $booking->id,
                    'مجموعة: '.$title,
                    $booking->starts_at,
                    $booking->ends_at ?: $booking->starts_at?->copy()->addHour(),
                    'group',
                    '#0B3D91',
                    $url,
                    $booking->classroomMeeting
                        ? \App\Services\ClassroomMeetingAccessService::platformEnterUrl($booking->classroomMeeting)
                        : null
                );
            });
    }

    /**
     * @return Collection<int, object>
     */
    private static function groupBookingsForStudent(User $student, Carbon $start, Carbon $end): Collection
    {
        if (! Schema::hasTable('tutoring_group_bookings')) {
            return collect();
        }

        return TutoringGroupBooking::query()
            ->where('user_id', $student->id)
            ->whereIn('status', [TutoringGroupBooking::STATUS_PENDING, TutoringGroupBooking::STATUS_CONFIRMED])
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', $start)
            ->where('starts_at', '<=', $end)
            ->with(['tutoringGroup:id,title', 'instructor:id,name', 'classroomMeeting'])
            ->get()
            ->map(function (TutoringGroupBooking $booking) {
                $title = ($booking->tutoringGroup->title ?? 'مجموعة').' — '.($booking->instructor->name ?? 'معلم');
                $url = Route::has('student.tutoring-bookings.show')
                    ? route('student.tutoring-bookings.show', $booking)
                    : ($booking->classroomMeeting
                        ? \App\Services\ClassroomMeetingAccessService::platformEnterUrl($booking->classroomMeeting)
                        : null);

                return self::event(
                    'group_booking_'.$booking->id,
                    $booking->id,
                    'مجموعة: '.$title,
                    $booking->starts_at,
                    $booking->ends_at ?: $booking->starts_at?->copy()->addHour(),
                    'group',
                    '#0B3D91',
                    $url,
                    $booking->classroomMeeting
                        ? \App\Services\ClassroomMeetingAccessService::platformEnterUrl($booking->classroomMeeting)
                        : null
                );
            });
    }

    /**
     * @return Collection<int, object>
     */
    private static function classSessionsForInstructor(User $instructor, Carbon $start, Carbon $end): Collection
    {
        if (! Schema::hasTable('tutoring_class_sessions') || ! Schema::hasTable('tutoring_groups')) {
            return collect();
        }

        return TutoringClassSession::query()
            ->where(function ($query) use ($instructor) {
                $query->whereHas('tutoringGroup', fn ($q) => $q->where('instructor_id', $instructor->id));
                if (Schema::hasTable('tutoring_group_cohorts')) {
                    $query->orWhereHas('cohort.tutoringGroup', fn ($q) => $q->where('instructor_id', $instructor->id));
                }
            })
            ->whereNotIn('status', [TutoringClassSession::STATUS_CANCELLED])
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', $start)
            ->where('starts_at', '<=', $end)
            ->with(['tutoringGroup:id,title', 'cohort:id,title,tutoring_group_id', 'classroomMeeting'])
            ->get()
            ->map(function (TutoringClassSession $session) {
                $groupTitle = $session->tutoringGroup->title
                    ?? $session->cohort?->tutoringGroup?->title
                    ?? $session->cohort?->title
                    ?? 'فصل';
                $url = Route::has('instructor.tutoring-cohorts.show') && $session->tutoring_group_cohort_id
                    ? route('instructor.tutoring-cohorts.show', $session->tutoring_group_cohort_id)
                    : null;

                return self::event(
                    'class_session_'.$session->id,
                    $session->id,
                    'حصة فصل: '.$session->displayTitle().' — '.$groupTitle,
                    $session->starts_at,
                    $session->ends_at ?: $session->starts_at?->copy()->addHour(),
                    'class',
                    '#F5B800',
                    $url,
                    $session->joinUrl()
                );
            });
    }

    /**
     * @return Collection<int, object>
     */
    private static function classSessionsForStudent(User $student, Carbon $start, Carbon $end): Collection
    {
        if (! Schema::hasTable('tutoring_class_sessions') || ! Schema::hasTable('tutoring_cohort_enrollments')) {
            return collect();
        }

        return TutoringClassSession::query()
            ->whereHas('cohort.enrollments', function ($q) use ($student) {
                $q->where('user_id', $student->id)->where('status', \App\Models\TutoringCohortEnrollment::STATUS_ACTIVE);
            })
            ->whereNotIn('status', [TutoringClassSession::STATUS_CANCELLED])
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', $start)
            ->where('starts_at', '<=', $end)
            ->with(['tutoringGroup:id,title', 'cohort:id,title', 'classroomMeeting'])
            ->get()
            ->map(function (TutoringClassSession $session) {
                return self::event(
                    'class_session_'.$session->id,
                    $session->id,
                    'حصة فصل: '.$session->displayTitle(),
                    $session->starts_at,
                    $session->ends_at ?: $session->starts_at?->copy()->addHour(),
                    'class',
                    '#F5B800',
                    $session->joinUrl(),
                    $session->joinUrl()
                );
            });
    }

    /**
     * @return Collection<int, object>
     */
    private static function liveSessionsForInstructor(User $instructor, Carbon $start, Carbon $end): Collection
    {
        if (! Schema::hasTable('live_sessions')) {
            return collect();
        }

        return LiveSession::query()
            ->where('instructor_id', $instructor->id)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', $start)
            ->where('scheduled_at', '<=', $end)
            ->whereIn('status', ['scheduled', 'live'])
            ->get()
            ->map(function (LiveSession $session) {
                $duration = (int) ($session->duration_minutes ?: 60);
                $url = Route::has('instructor.live-sessions.show')
                    ? route('instructor.live-sessions.show', $session)
                    : null;

                return self::event(
                    'live_'.$session->id,
                    $session->id,
                    'بث: '.($session->title ?: 'جلسة مباشرة'),
                    $session->scheduled_at,
                    $session->scheduled_at?->copy()->addMinutes($duration),
                    'live',
                    '#DC2626',
                    $url,
                    null
                );
            });
    }

    /**
     * @return Collection<int, object>
     */
    private static function lecturesForInstructor(User $instructor, Carbon $start, Carbon $end): Collection
    {
        if (! Schema::hasTable('lectures')) {
            return collect();
        }

        return Lecture::query()
            ->where('instructor_id', $instructor->id)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', $start)
            ->where('scheduled_at', '<=', $end)
            ->where('status', 'scheduled')
            ->with(['course:id,title'])
            ->get()
            ->map(function (Lecture $lecture) {
                $duration = (int) ($lecture->duration_minutes ?? 60);
                $url = Route::has('instructor.lectures.show')
                    ? route('instructor.lectures.show', $lecture)
                    : null;

                return self::event(
                    'lecture_'.$lecture->id,
                    $lecture->id,
                    'محاضرة: '.($lecture->title ?? '').' — '.($lecture->course->title ?? ''),
                    $lecture->scheduled_at,
                    $lecture->scheduled_at?->copy()->addMinutes($duration),
                    'lecture',
                    '#3B82F6',
                    $url,
                    $lecture->teams_meeting_link ?? null
                );
            });
    }

    private static function event(
        string $calendarId,
        mixed $id,
        string $title,
        mixed $start,
        mixed $end,
        string $type,
        string $color,
        ?string $url,
        ?string $location
    ): object {
        return (object) [
            'calendar_id' => $calendarId,
            'id' => $id,
            'title' => $title,
            'description' => $location,
            'start_date' => $start instanceof CarbonInterface ? $start->copy()->utc() : ($start ? Carbon::parse($start)->utc() : null),
            'end_date' => $end instanceof CarbonInterface ? $end->copy()->utc() : ($end ? Carbon::parse($end)->utc() : null),
            'is_all_day' => false,
            'type' => $type,
            'color' => $color,
            'priority' => 'high',
            'url' => $url,
            'location' => $location,
        ];
    }

    private static function parseBound(mixed $value, Carbon $fallback): Carbon
    {
        if ($value instanceof CarbonInterface) {
            return $value->copy()->utc();
        }
        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value)->utc();
        }

        return $fallback->copy()->utc();
    }

    private static function eventStart(object $event): ?Carbon
    {
        $start = $event->start_date ?? null;
        if ($start instanceof CarbonInterface) {
            return $start->copy()->utc();
        }
        if ($start) {
            return Carbon::parse($start)->utc();
        }

        return null;
    }

    private static function eventEnd(object $event): ?Carbon
    {
        $end = $event->end_date ?? null;
        if ($end instanceof CarbonInterface) {
            return $end->copy()->utc();
        }
        if ($end) {
            return Carbon::parse($end)->utc();
        }

        return null;
    }
}
