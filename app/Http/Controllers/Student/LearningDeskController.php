<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\OneToOneSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningDeskController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $children = $actor->role === 'parent'
            ? $actor->children()->orderBy('name')->get(['id', 'name', 'email', 'academic_year_id'])
            : collect();

        $student = $this->resolveStudent($actor, (int) $request->query('child', 0), $children);

        $upcoming = collect();
        $past = collect();
        $assignments = collect();

        if ($student) {
            $sessions = OneToOneSession::query()
                ->where('student_id', $student->id)
                ->with(['course:id,title', 'instructor:id,name', 'classroomMeeting'])
                ->orderByRaw('CASE WHEN scheduled_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('scheduled_at')
                ->limit(40)
                ->get();

            $upcoming = $sessions->filter(fn (OneToOneSession $session) => $session->isOpenPlacement())->values();
            $past = $sessions->filter(fn (OneToOneSession $session) => in_array($session->status, [
                OneToOneSession::STATUS_COMPLETED,
                OneToOneSession::STATUS_CANCELLED,
            ], true))->sortByDesc(fn (OneToOneSession $session) => $session->scheduled_at?->timestamp ?? 0)->values();

            $courseIds = $student->activeCourses()->pluck('advanced_courses.id');
            if ($courseIds->isNotEmpty()) {
                $assignments = Assignment::query()
                    ->where('status', 'published')
                    ->where(function ($query) use ($courseIds) {
                        $query->whereIn('advanced_course_id', $courseIds)
                            ->orWhereIn('course_id', $courseIds);
                    })
                    ->with(['course:id,title'])
                    ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('due_date')
                    ->limit(30)
                    ->get();

                $submissions = AssignmentSubmission::query()
                    ->where('student_id', $student->id)
                    ->whereIn('assignment_id', $assignments->pluck('id'))
                    ->get()
                    ->keyBy('assignment_id');

                $assignments->each(function (Assignment $assignment) use ($submissions) {
                    $assignment->setAttribute('my_submission', $submissions->get($assignment->id));
                });
            }
        }

        return view('student.learning-desk.index', [
            'student' => $student,
            'children' => $children,
            'upcoming' => $upcoming,
            'past' => $past,
            'assignments' => $assignments,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $children
     */
    private function resolveStudent(User $actor, int $childId, $children): ?User
    {
        if ($actor->role !== 'parent') {
            return $actor;
        }

        if ($childId > 0) {
            return $children->firstWhere('id', $childId);
        }

        return $children->first();
    }
}
