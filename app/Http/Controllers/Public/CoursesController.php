<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CoursesController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = (int) $request->query('category', 0);
        $track = (string) $request->query('track', '');
        if (! in_array($track, ['recorded', 'book'], true)) {
            $track = '';
        }
        $sort = (string) $request->query('sort', 'featured');
        if (! in_array($sort, ['featured', 'newest', 'price_asc', 'price_desc'], true)) {
            $sort = 'featured';
        }

        $baseQuery = AdvancedCourse::query()->where('is_active', true);
        $totalCourses = (clone $baseQuery)->count();

        $categories = collect();
        if (Schema::hasTable('course_categories')) {
            $categories = CourseCategory::query()
                ->active()
                ->ordered()
                ->withCount([
                    'advancedCourses as courses_count' => fn ($query) => $query->where('is_active', true),
                ])
                ->get(['id', 'name'])
                ->filter(fn (CourseCategory $category) => (int) ($category->courses_count ?? 0) > 0)
                ->values();
        }

        $query = (clone $baseQuery)
            ->with(['instructor:id,name', 'courseCategory:id,name'])
            ->withCount('lessons');

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('skills', 'like', $like)
                    ->orWhere('level', 'like', $like)
                    ->orWhereHas('instructor', fn ($u) => $u->where('name', 'like', $like))
                    ->orWhereHas('courseCategory', fn ($c) => $c->where('name', 'like', $like));
            });
        }

        if ($categoryId > 0) {
            $query->where('course_category_id', $categoryId);
        }

        if ($track !== '' && Schema::hasColumn('advanced_courses', 'product_track')) {
            $query->where('product_track', $track);
        }

        match ($sort) {
            'newest' => $query->orderByDesc('id'),
            'price_asc' => $query->orderBy('price')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            default => $query->orderByDesc('is_featured')->orderByDesc('id'),
        };

        $courses = $query->paginate(12)->withQueryString();

        $activeCategory = $categories->firstWhere('id', $categoryId);

        return view('courses', [
            'courses' => $courses,
            'categories' => $categories,
            'totalCourses' => $totalCourses,
            'filters' => [
                'q' => $q,
                'category' => $categoryId > 0 ? $categoryId : null,
                'sort' => $sort,
                'track' => $track !== '' ? $track : null,
            ],
            'activeCategory' => $activeCategory,
            'mcActive' => 'courses',
        ]);
    }
}
