<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\OneToOneSessionReport;
use App\Services\OneToOneSessionReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = OneToOneSessionReport::query()
            ->where('student_id', $request->user()->id)
            ->with(['instructor:id,name', 'session:id,scheduled_at,session_number'])
            ->latest('submitted_at')
            ->paginate(15);

        return view('student.session-reports.index', compact('reports'));
    }

    public function show(Request $request, OneToOneSessionReport $report): View
    {
        abort_unless((int) $report->student_id === (int) $request->user()->id, 403);

        $report->load(['instructor:id,name', 'session:id,scheduled_at,session_number']);
        OneToOneSessionReportService::markReadByStudent($report, $request->user());

        return view('student.session-reports.show', compact('report'));
    }
}
