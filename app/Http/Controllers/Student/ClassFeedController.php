<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassFeedPost;
use App\Models\TutoringGroupCohort;
use App\Services\ClassFeedService;
use App\Services\TutoringClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassFeedController extends Controller
{
    public function index(Request $request, TutoringGroupCohort $cohort): View|RedirectResponse
    {
        if (! $request->routeIs('instructor.*') && ! student_ui('show_classes')) {
            return redirect()->route('dashboard');
        }

        abort_unless(TutoringClassService::userCanAccessCohort($request->user(), $cohort), 403);

        $cohort->load(['tutoringGroup.instructor']);
        $user = $request->user();
        $canModerate = ClassFeedService::canModerate($user, $cohort);

        if ($request->routeIs('instructor.*')) {
            return view('instructor.tutoring-cohorts.community', [
                'cohort' => $cohort,
                'feedPosts' => ClassFeedService::postsFor($cohort, $user),
                'canModerateFeed' => $canModerate,
            ]);
        }

        return view('student.classes.community', [
            'cohort' => $cohort,
            'feedPosts' => ClassFeedService::postsFor($cohort, $user),
            'canModerateFeed' => $canModerate,
        ]);
    }

    public function store(Request $request, TutoringGroupCohort $cohort): RedirectResponse
    {
        abort_unless(TutoringClassService::userCanAccessCohort($request->user(), $cohort), 403);

        $data = $request->validate([
            'body' => 'required|string|max:1000',
            'post_type' => 'nullable|in:question,announcement',
            'is_pinned' => 'nullable|boolean',
        ]);

        try {
            ClassFeedService::createPost(
                $cohort,
                $request->user(),
                $data['body'],
                $data['post_type'] ?? 'question',
                (bool) ($request->boolean('is_pinned')),
            );
        } catch (\InvalidArgumentException|\Illuminate\Validation\ValidationException $e) {
            $message = $e instanceof \Illuminate\Validation\ValidationException
                ? $e->getMessage()
                : $e->getMessage();

            return $this->backToCommunity($request, $cohort)->with('error', $message);
        }

        return $this->backToCommunity($request, $cohort)
            ->with('success', 'تم نشر مشاركتك في مجتمع الفصل.');
    }

    public function comment(Request $request, ClassFeedPost $post): RedirectResponse
    {
        $post->loadMissing('cohort');
        abort_unless($post->cohort && TutoringClassService::userCanAccessCohort($request->user(), $post->cohort), 403);

        $data = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        try {
            ClassFeedService::addComment($post, $request->user(), $data['body']);
        } catch (\InvalidArgumentException|\Illuminate\Validation\ValidationException $e) {
            return $this->backToCommunity($request, $post->cohort)
                ->with('error', $e->getMessage());
        }

        return $this->backToCommunity($request, $post->cohort)
            ->with('success', 'تم إضافة التعليق.');
    }

    public function hide(Request $request, ClassFeedPost $post): RedirectResponse
    {
        ClassFeedService::hidePost($post, $request->user());

        return $this->backToCommunity($request, $post->tutoring_group_cohort_id)
            ->with('success', 'تم إخفاء المنشور.');
    }

    public function unhide(Request $request, ClassFeedPost $post): RedirectResponse
    {
        ClassFeedService::unhidePost($post, $request->user());

        return $this->backToCommunity($request, $post->tutoring_group_cohort_id)
            ->with('success', 'تم إظهار المنشور.');
    }

    public function pin(Request $request, ClassFeedPost $post): RedirectResponse
    {
        ClassFeedService::togglePin($post, $request->user());

        return $this->backToCommunity($request, $post->tutoring_group_cohort_id)
            ->with('success', 'تم تحديث التثبيت.');
    }

    private function backToCommunity(Request $request, TutoringGroupCohort|int $cohort): RedirectResponse
    {
        $cohortModel = $cohort instanceof TutoringGroupCohort
            ? $cohort
            : TutoringGroupCohort::query()->find($cohort);

        $user = $request->user();
        $isInstructor = $user && (
            $request->routeIs('instructor.*')
            || (method_exists($user, 'isInstructor') && $user->isInstructor())
            || (method_exists($user, 'isTeacher') && $user->isTeacher())
        );

        if ($isInstructor && $cohortModel && \Illuminate\Support\Facades\Route::has('instructor.tutoring-cohorts.community')) {
            return redirect()->route('instructor.tutoring-cohorts.community', $cohortModel);
        }

        if ($cohortModel) {
            return redirect()->route('student.classes.community', $cohortModel);
        }

        return back();
    }
}
