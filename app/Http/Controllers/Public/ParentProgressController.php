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

        $token = trim((string) $request->query('token', ''));
        $result = null;

        if ($token !== '') {
            $result = $reports->lookupByShareToken($token);
        }

        return view('public.parent-progress', [
            'token' => $token !== '' ? $token : null,
            'result' => $result,
        ]);
    }
}
