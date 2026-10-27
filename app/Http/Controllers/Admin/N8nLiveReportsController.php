<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveSessionReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class N8nLiveReportsController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $instructorId = $request->input('instructor_id');

        $query = LiveSessionReport::with(['session', 'instructor', 'recording'])->latest();

        if ($status && in_array($status, ['pending', 'processing', 'completed', 'failed'], true)) {
            $query->where('status', $status);
        }

        if ($instructorId) {
            $query->where('instructor_id', (int) $instructorId);
        }

        $reports = $query->paginate(25)->withQueryString();

        return view('admin.n8n.live-session-reports.index', compact('reports', 'status', 'instructorId'));
    }

    public function retry(LiveSessionReport $report): RedirectResponse
    {
        $report->update([
            'status' => 'pending',
            'n8n_execution_id' => null,
        ]);

        return back()->with('success', 'تمت إعادة جدولة التقرير للمعالجة.');
    }

    public function markCompleted(LiveSessionReport $report): RedirectResponse
    {
        $report->update(['status' => 'completed']);

        return back()->with('success', 'تم وضع التقرير كمكتمل.');
    }

    public function markFailed(LiveSessionReport $report): RedirectResponse
    {
        $report->update(['status' => 'failed']);

        return back()->with('success', 'تم وضع التقرير كفاشل.');
    }
}

