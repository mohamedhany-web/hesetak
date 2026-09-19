<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class OneToOneSession extends Model
{
    public const STATUS_PENDING = 'pending_schedule';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'student_course_enrollment_id',
        'student_service_entitlement_id',
        'advanced_course_id',
        'instructor_id',
        'student_id',
        'session_number',
        'scheduled_at',
        'duration_minutes',
        'is_private_lecture',
        'system_channel',
        'is_complimentary',
        'status',
        'classroom_meeting_id',
        'booked_by_user_id',
        'notes',
        'series_id',
        'student_unlocked_at',
        'student_unlocked_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'session_number' => 'integer',
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'is_private_lecture' => 'boolean',
            'is_complimentary' => 'boolean',
            'student_unlocked_at' => 'datetime',
        ];
    }

    public function isComplimentary(): bool
    {
        return (bool) ($this->is_complimentary ?? false);
    }

    public static function allowedDurations(): array
    {
        return [(int) config('private_lessons.lesson_duration_minutes', 50)];
    }

    public static function defaultDurationMinutes(): int
    {
        return (int) config('private_lessons.lesson_duration_minutes', 50);
    }

    public function isAwaitingTeacherStart(): bool
    {
        if ($this->status !== self::STATUS_SCHEDULED || ! $this->scheduled_at) {
            return false;
        }

        $meeting = $this->classroomMeeting;
        if ($meeting && method_exists($meeting, 'isLive') && $meeting->isLive()) {
            return false;
        }

        return $this->scheduled_at->lte(now()->addMinutes(15));
    }

    public function privateLectureLabel(): string
    {
        return 'محاضرة خاصة';
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'بانتظار الجدولة',
            self::STATUS_SCHEDULED => 'مجدولة',
            self::STATUS_COMPLETED => 'مكتملة',
            self::STATUS_CANCELLED => 'ملغاة',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentCourseEnrollment::class, 'student_course_enrollment_id');
    }

    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(StudentServiceEntitlement::class, 'student_service_entitlement_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(AdvancedCourse::class, 'advanced_course_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function classroomMeeting(): BelongsTo
    {
        return $this->belongsTo(ClassroomMeeting::class, 'classroom_meeting_id');
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    public function studentUnlockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_unlocked_by_user_id');
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function isOpenPlacement(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SCHEDULED], true);
    }

    public function joinUrl(): ?string
    {
        $meeting = $this->classroomMeeting;
        if (! $meeting) {
            return null;
        }

        return \App\Services\ClassroomMeetingAccessService::platformEnterUrl($meeting);
    }

    /**
     * @param  'student'|'instructor'  $perspective
     */
    public static function calendarItemsForUser(User $user, $startDate, $endDate, string $perspective): Collection
    {
        $q = static::query()
            ->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at');

        if ($perspective === 'student') {
            $q->where('student_id', $user->id);
        } else {
            $q->where('instructor_id', $user->id);
        }

        if ($startDate) {
            $q->where('scheduled_at', '>=', $startDate);
        }
        if ($endDate) {
            $q->where('scheduled_at', '<=', $endDate);
        }

        return $q->with(['instructor', 'student', 'course', 'classroomMeeting'])->get()->map(function (self $session) use ($perspective) {
            $end = $session->scheduled_at->copy()->addMinutes($session->duration_minutes ?? 60);
            $isStudent = $perspective === 'student';
            $courseTitle = $session->course->title ?? 'كورس فردي';
            if ($session->isComplimentary()) {
                $courseTitle = 'حصة مجانية';
            }

            $title = $isStudent
                ? ('حصة 1:1: '.$courseTitle.' — '.($session->instructor->name ?? ''))
                : ('حصة 1:1: '.$courseTitle.' — '.($session->student->name ?? ''));

            return (object) [
                'calendar_id' => 'one_to_one_'.$session->id,
                'id' => $session->id,
                'title' => $title,
                'description' => $session->joinUrl(),
                'start_date' => $session->scheduled_at,
                'end_date' => $end,
                'is_all_day' => false,
                'type' => $session->isComplimentary() ? 'free_session' : 'one_to_one',
                'color' => $session->isComplimentary() ? '#C9952A' : '#7c3aed',
                'priority' => 'high',
                'url' => $isStudent
                    ? route('student.one-to-one-sessions.show', $session)
                    : route('instructor.one-to-one-sessions.show', $session),
                'location' => $session->joinUrl(),
            ];
        });
    }
}
