<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\AdaptiveLearningService;
use App\Services\FamilyProgressReportService;
use App\Services\StudentProgressAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressHubController extends Controller
{
    public function index(
        Request $request,
        StudentProgressAnalyticsService $analytics,
        AdaptiveLearningService $adaptive,
    ): View {
        $actor = $request->user();
        abort_unless($actor && ($actor->isStudent() || $actor->isParent()), 403);

        $user = \App\Support\ActiveChildContext::resolve($actor, $request);
        abort_unless($user, 404, 'لا يوجد طالب مرتبط بهذا الحساب.');

        $comparison = $analytics->comparePeriods($user);
        $suggestions = $adaptive->activeFor($user);

        return view('student.progress.index', [
            'user' => $user,
            'comparison' => $comparison,
            'suggestions' => $suggestions,
            'shareUrl' => $user->progressShareUrl(),
            'children' => $actor->isParent() ? $actor->children()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function refreshAdaptive(Request $request, AdaptiveLearningService $adaptive)
    {
        $actor = $request->user();
        abort_unless($actor && ($actor->isStudent() || $actor->isParent()), 403);
        $user = \App\Support\ActiveChildContext::resolve($actor, $request);
        abort_unless($user, 404);

        $adaptive->refreshSuggestionsFor($user);

        return back()->with('success', app()->getLocale() === 'ar'
            ? 'تم تحديث اقتراحات المراجعة.'
            : 'Review suggestions refreshed.');
    }

    public function sendFamilyPreview(Request $request, FamilyProgressReportService $family)
    {
        $actor = $request->user();
        abort_unless($actor && ($actor->isStudent() || $actor->isParent()), 403);
        $user = \App\Support\ActiveChildContext::resolve($actor, $request);
        abort_unless($user, 404);

        $report = $family->sendForStudent($user, 'email');

        return back()->with(
            $report && $report->status === 'sent' ? 'success' : 'error',
            $report && $report->status === 'sent'
                ? (app()->getLocale() === 'ar' ? 'أُرسل تقرير العائلة بالبريد.' : 'Family report emailed.')
                : (app()->getLocale() === 'ar' ? 'تعذّر إرسال التقرير. تحقق من البريد.' : 'Could not send report. Check email settings.')
        );
    }
}
