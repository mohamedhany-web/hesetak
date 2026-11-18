<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\OneToOneSession;
use App\Models\OneToOneSessionReport;
use App\Services\OneToOneSessionReportService;
use App\Support\OneToOneReportGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SessionReportController extends Controller
{
    public function index(Request $request): View
    {
        $instructorId = (int) $request->user()->id;

        $pending = OneToOneReportGate::overdueQuery($instructorId)
            ->where('status', OneToOneSession::STATUS_COMPLETED)
            ->with('student:id,name')
            ->get();

        $submitted = OneToOneSessionReport::query()
            ->where('instructor_id', $instructorId)
            ->with(['student:id,name', 'session:id,scheduled_at,session_number'])
            ->latest('submitted_at')
            ->paginate(15);

        return view('instructor.session-reports.index', compact('pending', 'submitted'));
    }

    public function edit(Request $request, OneToOneSession $oneToOneSession): View|RedirectResponse
    {
        abort_unless((int) $oneToOneSession->instructor_id === (int) $request->user()->id, 403);

        if ($oneToOneSession->status !== OneToOneSession::STATUS_COMPLETED) {
            return redirect()
                ->route('instructor.one-to-one-sessions.show', $oneToOneSession)
                ->with('error', 'يمكن رفع التقرير بعد اكتمال الحصة فقط.');
        }

        $oneToOneSession->load(['student:id,name', 'course:id,title', 'teacherReport']);

        return view('instructor.session-reports.edit', [
            'session' => $oneToOneSession,
            'report' => $oneToOneSession->teacherReport,
            'levels' => OneToOneSessionReport::performanceLevels(),
        ]);
    }

    public function save(Request $request, OneToOneSession $oneToOneSession): RedirectResponse
    {
        abort_unless((int) $oneToOneSession->instructor_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'summary' => ['required', 'string', 'min:20', 'max:5000'],
            'topics_covered' => ['nullable', 'string', 'max:3000'],
            'performance_level' => ['nullable', 'in:'.implode(',', array_keys(OneToOneSessionReport::performanceLevels()))],
            'homework' => ['nullable', 'string', 'max:3000'],
            'next_steps' => ['nullable', 'string', 'max:3000'],
        ]);

        try {
            OneToOneSessionReportService::submit($oneToOneSession, $request->user(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('instructor.session-reports.index')
            ->with('success', 'تم إرسال التقرير للطالب.');
    }
}
