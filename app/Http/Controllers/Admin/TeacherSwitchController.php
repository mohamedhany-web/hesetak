<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OneToOneSession;
use App\Models\User;
use App\Services\OneToOneSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherSwitchController extends Controller
{
    public function create(Request $request): View
    {
        $studentId = (int) $request->query('student_id', 0);
        $sessionId = (int) $request->query('session_id', 0);

        $students = User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'email']);

        $instructors = User::query()
            ->whereIn('role', ['instructor', 'teacher'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $sessions = collect();
        if ($studentId > 0) {
            $sessions = OneToOneSession::query()
                ->with(['instructor:id,name', 'course:id,title'])
                ->where('student_id', $studentId)
                ->whereIn('status', [OneToOneSession::STATUS_PENDING, OneToOneSession::STATUS_SCHEDULED])
                ->orderByDesc('scheduled_at')
                ->orderByDesc('id')
                ->limit(50)
                ->get();
        }

        $selectedSession = $sessionId > 0
            ? OneToOneSession::query()->with(['student:id,name', 'instructor:id,name'])->find($sessionId)
            : null;

        return view('admin.teacher-switch.create', compact(
            'students',
            'instructors',
            'sessions',
            'studentId',
            'sessionId',
            'selectedSession'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'one_to_one_session_id' => ['required', 'integer', 'exists:one_to_one_sessions,id'],
            'new_instructor_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $student = User::query()->findOrFail($data['student_id']);
        if ($student->role !== 'student') {
            return back()->withInput()->with('error', 'يجب اختيار طالب.');
        }

        $session = OneToOneSession::query()->findOrFail($data['one_to_one_session_id']);
        if ((int) $session->student_id !== (int) $student->id) {
            return back()->withInput()->with('error', 'الحصة لا تخص الطالب المحدد.');
        }

        $newInstructor = User::query()->findOrFail($data['new_instructor_id']);
        if (! $newInstructor->isInstructor() && ! $newInstructor->isTeacher()) {
            return back()->withInput()->with('error', 'المستخدم المحدد ليس معلماً.');
        }

        try {
            OneToOneSessionService::reassignInstructor($session, $newInstructor);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.teacher-switch.create', [
                'student_id' => $student->id,
                'session_id' => $session->id,
            ])
            ->with('success', 'تم تبديل المعلم بدون خصم من رصيد الباقة. المعلم الجديد: '.$newInstructor->name);
    }
}
