<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentLearnHubService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearnHubController extends Controller
{
    public function index(Request $request, StudentLearnHubService $learn): View|RedirectResponse
    {
        $tab = (string) $request->query('tab', 'private');
        if ($tab === 'groups' || $tab === 'school') {
            return redirect()->route('student.learn.index', array_filter([
                'tab' => 'private',
                'q' => $request->query('q'),
                'subject_id' => $request->query('subject_id'),
                'lang' => $request->query('lang'),
            ]));
        }

        $payload = $learn->hub(
            $request->user(),
            $tab,
            [
                'q' => $request->query('q'),
                'subject_id' => $request->query('subject_id'),
                'year_id' => $request->query('year_id'),
                'type' => $request->query('type'),
                'bookable' => $request->query('bookable'),
            ]
        );

        return view('student.learn.index', $payload);
    }

    public function teacher(Request $request, User $instructor, StudentLearnHubService $learn): View|RedirectResponse
    {
        // توحيد ملف المعلم: الصفحة العامة بدل نسخة داخلية مكررة
        if ($instructor->isInstructor() || $instructor->isTeacher()) {
            return redirect()->route('public.instructors.show', $instructor);
        }

        $payload = $learn->teacherPage($request->user(), $instructor);

        return view('student.learn.teacher', $payload);
    }
}
