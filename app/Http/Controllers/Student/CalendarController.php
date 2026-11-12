<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\CalendarEvent;
use App\Models\Exam;
use App\Models\Lecture;
use App\Models\LectureAssignment;
use App\Services\TeachingCalendarService;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    /**
     * عرض التقويم
     */
    public function index()
    {
        $actor = Auth::user();
        $user = \App\Support\ActiveChildContext::resolve($actor) ?? $actor;

        // جلب جميع الأحداث للطالب (أو الابن لولي الأمر)
        $events = $this->getStudentEvents($user);

        // إحصائيات
        $stats = [
            'total' => $events->count(),
            'exams' => $events->where('type', 'exam')->count(),
            'lectures' => $events->where('type', 'lecture')->count(),
            'assignments' => $events->where('type', 'assignment')->count(),
            'consultations' => $events->where('type', 'consultation')->count(),
            'upcoming' => $events->where('start_date', '>=', now())->count(),
        ];

        $viewerTz = AppTimezone::forUser($user);
        $upcoming = $events
            ->filter(fn ($event) => ($event->start_date ?? now()) >= now())
            ->sortBy('start_date')
            ->values();

        $typeLabels = [
            'exam' => __('student_timeline.cal_exam'),
            'lecture' => __('student_timeline.cal_lecture'),
            'assignment' => __('student_timeline.cal_assignment'),
            'one_to_one' => __('student_timeline.cal_private'),
            'class' => __('student_timeline.cal_class'),
            'group' => __('student_timeline.cal_group'),
            'consultation' => __('student_timeline.cal_consult'),
        ];
        $eventTones = [
            'exam' => 'pink',
            'lecture' => 'blue',
            'assignment' => 'orange',
            'one_to_one' => 'purple',
            'class' => 'purple',
            'consultation' => 'green',
        ];

        return view('student.calendar.index', compact(
            'events',
            'stats',
            'viewerTz',
            'upcoming',
            'typeLabels',
            'eventTones'
        ));
    }

    /**
     * API endpoint للحصول على الأحداث بصيغة JSON (لـ FullCalendar)
     */
    public function getEvents(Request $request)
    {
        $actor = Auth::user();
        $user = \App\Support\ActiveChildContext::resolve($actor, $request) ?? $actor;
        $start = $request->get('start');
        $end = $request->get('end');

        $events = $this->getStudentEvents($user, $start, $end);
        $viewerTz = \App\Support\AppTimezone::forUser($user);

        $calendarEvents = collect(TeachingCalendarService::toFullCalendar($events, $viewerTz))->map(function (array $row) {
            $type = $row['type'] ?? null;

            return [
                'id' => $row['id'],
                'title' => $row['title'],
                'start' => $row['start'],
                'end' => $row['end'],
                'allDay' => (bool) ($row['allDay'] ?? false),
                'color' => $row['color'] ?? $this->getEventColor($type),
                'type' => $type,
                'url' => $row['url'] ?? null,
                'description' => $row['description'] ?? null,
                'extendedProps' => [
                    'priority' => $row['extendedProps']['priority'] ?? 'medium',
                    'location' => $row['extendedProps']['location'] ?? null,
                    'type' => $type,
                ],
            ];
        })->values();

        return response()->json($calendarEvents);
    }

    /**
     * جلب جميع الأحداث للطالب
     */
    private function getStudentEvents($user, $startDate = null, $endDate = null)
    {
        $events = collect();

        // 1. المحاضرات (Lectures)
        $lectures = Lecture::whereHas('course', function ($q) use ($user) {
            $q->whereHas('enrollments', function ($q2) use ($user) {
                $q2->where('user_id', $user->id)
                    ->where('status', 'active');
            });
        })
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', $startDate ?? now()->subMonths(1))
            ->where('scheduled_at', '<=', $endDate ?? now()->addMonths(3))
            ->with(['course', 'instructor'])
            ->get();

        foreach ($lectures as $lecture) {
            $endTime = $lecture->scheduled_at->copy()->addMinutes($lecture->duration_minutes ?? 60);
            $events->push((object) [
                'calendar_id' => 'lecture_'.$lecture->id,
                'id' => $lecture->id,
                'title' => $lecture->title.' - '.($lecture->course->title ?? ''),
                'description' => $lecture->description,
                'start_date' => $lecture->scheduled_at,
                'end_date' => $endTime,
                'is_all_day' => false,
                'type' => 'lecture',
                'color' => '#3B82F6',
                'priority' => 'medium',
                'url' => route('my-courses.learn', $lecture->course_id),
                'location' => $lecture->teams_meeting_link,
            ]);
        }

        // 2. الامتحانات (Exams)
        $exams = Exam::whereHas('course', function ($q) use ($user) {
            $q->whereHas('enrollments', function ($q2) use ($user) {
                $q2->where('user_id', $user->id)
                    ->where('status', 'active');
            });
        })
            ->where('is_active', true)
            ->where('is_published', true)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($q2) use ($startDate, $endDate) {
                    if ($startDate) {
                        $q2->where('start_time', '>=', $startDate);
                    }
                    if ($endDate) {
                        $q2->where('start_time', '<=', $endDate);
                    }
                })
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        if ($startDate) {
                            $q2->where('start_date', '>=', $startDate);
                        }
                        if ($endDate) {
                            $q2->where('start_date', '<=', $endDate);
                        }
                    });
            })
            ->with(['course'])
            ->get();

        foreach ($exams as $exam) {
            $startDate = $exam->start_time ?? ($exam->start_date ? Carbon::parse($exam->start_date) : now());
            $endDate = $exam->end_time ?? ($exam->end_date ? Carbon::parse($exam->end_date) : $startDate->copy()->addMinutes($exam->duration_minutes ?? 60));

            $events->push((object) [
                'calendar_id' => 'exam_'.$exam->id,
                'id' => $exam->id,
                'title' => 'امتحان: '.$exam->title.' - '.($exam->course->title ?? ''),
                'description' => $exam->description ?? $exam->instructions,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_all_day' => false,
                'type' => 'exam',
                'color' => '#EF4444',
                'priority' => 'high',
                'url' => route('student.exams.show', $exam->id),
            ]);
        }

        // 3. الواجبات (Assignments)
        $assignments = Assignment::whereHas('course', function ($q) use ($user) {
            $q->whereHas('enrollments', function ($q2) use ($user) {
                $q2->where('user_id', $user->id)
                    ->where('status', 'active');
            });
        })
            ->where('status', 'published')
            ->where('due_date', '>=', $startDate ?? now()->subMonths(1))
            ->where('due_date', '<=', $endDate ?? now()->addMonths(3))
            ->with(['course'])
            ->get();

        foreach ($assignments as $assignment) {
            $events->push((object) [
                'calendar_id' => 'assignment_'.$assignment->id,
                'id' => $assignment->id,
                'title' => 'واجب: '.$assignment->title.' - '.($assignment->course->title ?? ''),
                'description' => $assignment->description ?? $assignment->instructions,
                'start_date' => $assignment->due_date,
                'end_date' => $assignment->due_date,
                'is_all_day' => true,
                'type' => 'assignment',
                'color' => '#F59E0B',
                'priority' => 'high',
                'url' => route('my-courses.show', $assignment->advanced_course_id ?? $assignment->course_id).'#assignments',
            ]);
        }

        // 4. واجبات المحاضرات (Lecture Assignments)
        $lectureAssignments = LectureAssignment::whereHas('lecture.course', function ($q) use ($user) {
            $q->whereHas('enrollments', function ($q2) use ($user) {
                $q2->where('user_id', $user->id)
                    ->where('status', 'active');
            });
        })
            ->where('status', 'published')
            ->where('due_date', '>=', $startDate ?? now()->subMonths(1))
            ->where('due_date', '<=', $endDate ?? now()->addMonths(3))
            ->with(['lecture.course'])
            ->get();

        foreach ($lectureAssignments as $assignment) {
            $events->push((object) [
                'calendar_id' => 'lecture_assignment_'.$assignment->id,
                'id' => $assignment->id,
                'title' => 'واجب محاضرة: '.$assignment->title,
                'description' => $assignment->description ?? $assignment->instructions,
                'start_date' => $assignment->due_date,
                'end_date' => $assignment->due_date,
                'is_all_day' => true,
                'type' => 'assignment',
                'color' => '#F59E0B',
                'priority' => 'high',
                'url' => route('my-courses.learn', $assignment->lecture->course_id),
            ]);
        }

        // 5. أحداث التقويم المخصصة (Calendar Events)
        $calendarEvents = CalendarEvent::getStudentEvents(
            $user->id,
            $startDate ?? now()->subMonths(1),
            $endDate ?? now()->addMonths(3)
        );

        foreach ($calendarEvents as $event) {
            $events->push((object) [
                'calendar_id' => 'calendar_'.$event->id,
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date ?? $event->start_date,
                'is_all_day' => $event->is_all_day,
                'type' => $event->type,
                'color' => $event->color,
                'priority' => $event->priority,
                'location' => $event->location,
            ]);
        }

        foreach (\App\Services\TeachingCalendarService::lessonsForStudent(
            $user,
            $startDate ?? now()->subMonths(1),
            $endDate ?? now()->addMonths(3)
        ) as $lessonEvent) {
            $events->push($lessonEvent);
        }

        // ترتيب الأحداث حسب التاريخ
        return $events->sortBy('start_date')->values();
    }

    /**
     * الحصول على لون الحدث حسب النوع
     */
    private function getEventColor($type)
    {
        return match ($type) {
            'exam' => '#EF4444',
            'lecture' => '#3B82F6',
            'assignment' => '#F59E0B',
            'meeting' => '#8B5CF6',
            'consultation' => '#059669',
            'deadline' => '#DC2626',
            'review' => '#10B981',
            'personal' => '#6366F1',
            'one_to_one' => '#7c3aed',
            'group' => '#0B3D91',
            'class' => '#F5B800',
            default => '#6B7280',
        };
    }
}
