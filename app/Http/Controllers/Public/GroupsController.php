<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * مسارات الفصول الجماعية القديمة — معطّلة كمنتج أساسي لحصتك.
 * تُعاد توجيه الزائر إلى المناهج / المعلمين.
 */
class GroupsController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->awayToCurricula();
    }

    public function year(string $slug): RedirectResponse
    {
        return $this->awayToCurricula();
    }

    public function groupCourses(): RedirectResponse
    {
        return $this->awayToCurricula();
    }

    public function oneToOneCourses(): RedirectResponse
    {
        return redirect()->route('public.instructors.index', ['focus' => 'private']);
    }

    public function show(string $slug): RedirectResponse
    {
        return $this->awayToCurricula();
    }

    public function book(Request $request, string $slug): RedirectResponse
    {
        return $this->awayToCurricula();
    }

    private function awayToCurricula(): RedirectResponse
    {
        return redirect()
            ->route('public.curricula')
            ->with('info', app()->getLocale() === 'ar'
                ? 'المسار الحالي لحصتك: مناهج وحصص فردية. الفصول الجماعية غير متاحة كمنتج أساسي.'
                : 'Hesetak focuses on curricula and 1:1 lessons. Group classes are not a primary product.');
    }
}
