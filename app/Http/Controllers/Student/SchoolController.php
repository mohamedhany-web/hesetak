<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentSchoolHomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function index(Request $request, StudentSchoolHomeService $schoolHome): View|RedirectResponse
    {
        if (! student_ui('show_school')) {
            return redirect()->route('dashboard');
        }

        $payload = $schoolHome->build($request->user(), [
            'week' => $request->query('week'),
            'view' => $request->query('view'),
            'sort' => $request->query('sort'),
            'q' => $request->query('q'),
        ]);

        return view('student.school.home', $payload);
    }
}
