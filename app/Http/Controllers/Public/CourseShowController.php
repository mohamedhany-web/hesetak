<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\InstructorProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseShowController extends Controller
{
    public function show(Request $request, int $id): View
    {
        $course = AdvancedCourse::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->with(['academicSubject', 'instructor', 'courseCategory'])
            ->withCount('lessons')
            ->firstOrFail();

        $isEnrolled = auth()->check() && auth()->user()->isEnrolledIn($course->id);

        $instructorApproved = false;
        if ($course->instructor_id) {
            $instructorApproved = InstructorProfile::query()
                ->where('user_id', $course->instructor_id)
                ->where('status', 'approved')
                ->exists();
        }

        $relatedCourses = AdvancedCourse::query()
            ->where('is_active', true)
            ->where('id', '!=', $course->id)
            ->when(
                $course->course_category_id,
                fn ($query) => $query->where('course_category_id', $course->course_category_id),
                fn ($query) => $query->where('is_featured', true)
            )
            ->with(['instructor:id,name', 'courseCategory:id,name'])
            ->withCount('lessons')
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        if ($relatedCourses->count() < 3) {
            $extra = AdvancedCourse::query()
                ->where('is_active', true)
                ->where('id', '!=', $course->id)
                ->whereNotIn('id', $relatedCourses->pluck('id'))
                ->with(['instructor:id,name', 'courseCategory:id,name'])
                ->withCount('lessons')
                ->orderByDesc('is_featured')
                ->limit(3 - $relatedCourses->count())
                ->get();
            $relatedCourses = $relatedCourses->concat($extra)->values();
        }

        return view('course-show', [
            'course' => $course,
            'relatedCourses' => $relatedCourses,
            'isEnrolled' => $isEnrolled,
            'instructorApproved' => $instructorApproved,
            'mcActive' => 'courses',
        ]);
    }
}
