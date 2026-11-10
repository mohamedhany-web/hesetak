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
        $children = $actor->isParent()
            ? $actor->children()->orderBy('name')->get(['id', 'name', 'email', 'academic_year_id'])
            : collect();

        $student = $this->resolveStudent($actor, (int) $request->query('child', 0), $children);
        if ($student && $actor->isParent()) {
            $request->session()->put('active_child_id', $student->id);
        }

        $upcoming = collect();
        $past = collect();
        $assignments = collect();

        if ($student) {
            \App\Services\OneToOneSessionService::settleOverdueForStudent((int) $student->id);

            $sessions = OneToOneSession::query()
                ->where('student_id', $student->id)
                ->with(['course:id,title', 'instructor:id,name', 'classroomMeeting'])
                ->orderByRaw('CASE WHEN scheduled_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('scheduled_at')
                ->limit(40)
                ->get();

            $upcoming = $sessions->filter(fn (OneToOneSession $session) => $session->isOpenPlacement()
                && (! $session->scheduled_at || $session->scheduled_at->isFuture() || $session->scheduled_at->isToday())
            )->values();
            $past = $sessions->filter(fn (OneToOneSession $session) => in_array($session->status, [
                OneToOneSession::STATUS_COMPLETED,
                OneToOneSession::STATUS_CANCELLED,
            ], true) || (
                $session->status === OneToOneSession::STATUS_SCHEDULED
                && $session->scheduled_at
                && $session->scheduled_at->copy()->addMinutes((int) ($session->duration_minutes ?: 50))->isPast()
            ))->sortByDesc(fn (OneToOneSession $session) => $session->scheduled_at?->timestamp ?? 0)->values();
            $ledger = [
                'completed' => $sessions->where('status', OneToOneSession::STATUS_COMPLETED)->count(),
                'missed' => 0,
                'remaining' => $sessions->filter(fn (OneToOneSession $session) => $session->isOpenPlacement()
                    && (! $session->scheduled_at || $session->scheduled_at->isFuture() || $session->scheduled_at->isToday()))->count(),
            ];

            $courseIds = $student->courseEnrollments()->pluck('advanced_course_id')
                ->merge(
                    OneToOneSession::query()->where('student_id', $student->id)->pluck('advanced_course_id')
                )
                ->filter()
                ->unique()
                ->values();
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
            'ledger' => $ledger ?? ['completed' => 0, 'missed' => 0, 'remaining' => 0],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $children
     */
    private function resolveStudent(User $actor, int $childId, $children): ?User
    {
        if (! $actor->isParent()) {
            return $actor;
        }

        if ($childId > 0) {
            return $children->firstWhere('id', $childId);
        }

        return $children->first();
    }
}
