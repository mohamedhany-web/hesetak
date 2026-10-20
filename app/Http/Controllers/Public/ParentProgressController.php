<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ParentProgressReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentProgressController extends Controller
{
    public function show(Request $request, ParentProgressReportService $reports): View
    {
        // رفض المعرّف الرقمي القديم — التعداد كان يكشف تقارير الطلاب بدون مصادقة.
        if ($request->query->has('student_id')) {
            abort(404);
        }

        $code = trim((string) ($request->query('code') ?: $request->query('token', '')));
        $result = null;

        if ($code !== '') {
            $result = $reports->lookupPublic($code);
        }

        return view('public.parent-progress', [
            'code' => $code !== '' ? $code : null,
            'token' => $code !== '' ? $code : null,
            'result' => $result,
            'indexable' => $code === '',
            'pageTitle' => app()->getLocale() === 'ar'
                ? 'تقرير الطالب لولي الأمر | حصتك'
                : 'Student report for parents | Hesetak',
            'pageDescription' => app()->getLocale() === 'ar'
                ? 'أدخل رقم الطالب واطّلع على حضور الحصص، التقارير، الكورسات، والاختبارات.'
                : 'Enter the student ID to view lessons, reports, courses, and exams.',
        ]);
    }
}
