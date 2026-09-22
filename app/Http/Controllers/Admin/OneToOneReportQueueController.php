<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OneToOneSession;
use App\Models\User;
use App\Support\OneToOneReportGate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OneToOneReportQueueController extends Controller
{
    public function index(Request $request): View
    {
        $instructorId = (int) $request->input('instructor_id', 0);

        $base = OneToOneReportGate::overdueQuery($instructorId > 0 ? $instructorId : null);

        $kpis = [
            'total' => (clone $base)->count(),
            'completed_no_report' => (clone $base)->where('status', OneToOneSession::STATUS_COMPLETED)->count(),
            'past_scheduled' => (clone $base)->where('status', OneToOneSession::STATUS_SCHEDULED)->count(),
            'instructors_affected' => (clone $base)->distinct('instructor_id')->count('instructor_id'),
        ];

        $sessions = (clone $base)->paginate(25)->withQueryString();

        $instructors = User::query()
            ->whereIn('role', ['instructor', 'teacher'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.one-to-one-report-queue.index', compact(
            'sessions',
            'kpis',
            'instructors',
            'instructorId'
        ));
    }
}
