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
        $user = $request->user();
        abort_unless($user && $user->isStudent(), 403);

        $comparison = $analytics->comparePeriods($user);
        $suggestions = $adaptive->activeFor($user);

        return view('student.progress.index', [
            'user' => $user,
            'comparison' => $comparison,
            'suggestions' => $suggestions,
            'shareUrl' => $user->progressShareUrl(),
        ]);
    }

    public function refreshAdaptive(Request $request, AdaptiveLearningService $adaptive)
    {
        $user = $request->user();
        abort_unless($user && $user->isStudent(), 403);

        $adaptive->refreshSuggestionsFor($user);

        return back()->with('success', app()->getLocale() === 'ar'
            ? 'تم تحديث اقتراحات المراجعة.'
            : 'Review suggestions refreshed.');
    }

    public function sendFamilyPreview(Request $request, FamilyProgressReportService $family)
    {
        $user = $request->user();
        abort_unless($user && $user->isStudent(), 403);

        $report = $family->sendForStudent($user, 'email');

        return back()->with(
            $report && $report->status === 'sent' ? 'success' : 'error',
            $report && $report->status === 'sent'
                ? (app()->getLocale() === 'ar' ? 'أُرسل تقرير العائلة بالبريد.' : 'Family report emailed.')
                : (app()->getLocale() === 'ar' ? 'تعذّر إرسال التقرير. تحقق من البريد.' : 'Could not send report. Check email settings.')
        );
    }
}
