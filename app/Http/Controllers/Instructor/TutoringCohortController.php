<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\TutoringGroupCohort;
use App\Services\InstructorCohortCommandCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TutoringCohortController extends Controller
{
    /**
     * Group cohorts are out of Hesetak scope (1:1 only).
     */
    public function index(Request $request, InstructorCohortCommandCenterService $commandCenter): RedirectResponse
    {
        return redirect()
            ->route('instructor.one-to-one-sessions.index')
            ->with('info', app()->getLocale() === 'ar'
                ? 'قيادة الفصول الجماعية غير مفعّلة في حصتك.'
                : 'Group class command is not part of Hesetak.');
    }

    public function show(Request $request, TutoringGroupCohort $cohort, InstructorCohortCommandCenterService $commandCenter): RedirectResponse
    {
        return redirect()->route('instructor.one-to-one-sessions.index');
    }
}
