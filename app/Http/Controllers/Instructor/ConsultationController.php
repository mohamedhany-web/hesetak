<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassroomMeeting;
use App\Models\ConsultationRequest;
use App\Models\Notification;
use App\Support\AppTimezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:instructor|teacher']);
    }

    public function index(): View
    {
        $requests = ConsultationRequest::query()
            ->where('instructor_id', Auth::id())
            ->with(['student', 'classroomMeeting'])
            ->latest()
            ->paginate(20);

        return view('instructor.consultations.index', compact('requests'));
    }

    public function show(ConsultationRequest $consultation): View
    {
        if ((int) $consultation->instructor_id !== (int) Auth::id()) {
            abort(403);
        }

        $consultation->load(['student', 'classroomMeeting']);

        return view('instructor.consultations.show', compact('consultation'));
    }

    public function schedule(Request $request, ConsultationRequest $consultation): RedirectResponse
    {
        if ((int) $consultation->instructor_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($consultation->status !== ConsultationRequest::STATUS_PAID) {
            return back()->with('error', 'يجب تأكيد الدفع من الإدارة قبل الجدولة.');
        }

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
        ]);
        $data = AppTimezone::shiftRequestDateTime(
            $request,
            $data,
            'scheduled_at',
            mustBeFuture: true,
            fallbackUser: $request->user()
        );

        $scheduledAt = $data['scheduled_at'];
        $duration = (int) ($data['duration_minutes'] ?? $consultation->duration_minutes);

        $meeting = ClassroomMeeting::create([
            'user_id' => $consultation->instructor_id,
            'consultation_request_id' => $consultation->id,
            'code' => ClassroomMeeting::generateCode(),
            'room_name' => 'consultation-'.$consultation->id.'-'.Str::lower(Str::random(6)),
            'title' => 'استشارة',
            'scheduled_for' => $scheduledAt,
            'planned_duration_minutes' => $duration,
            'max_participants' => 12,
            'settings' => [
                'allow_guest_join' => false,
                'consultation' => true,
            ],
        ]);

        $consultation->update([
            'status' => ConsultationRequest::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $duration,
            'classroom_meeting_id' => $meeting->id,
        ]);

        $joinUrl = \App\Services\ClassroomMeetingAccessService::platformEnterUrl($meeting);

        Notification::create([
            'user_id' => $consultation->student_id,
            'sender_id' => auth()->id(),
            'title' => 'تم جدولة الاستشارة',
            'message' => 'موعد الاستشارة: '.$scheduledAt->format('Y-m-d H:i').' — رابط الدخول: '.$joinUrl,
            'type' => 'reminder',
            'priority' => 'high',
            'audience' => 'student',
            'action_url' => route('consultations.show', $consultation),
            'action_text' => 'تفاصيل الاستشارة',
        ]);

        return back()->with('success', 'تم جدولة الاستشارة وإشعار الطالب.');
    }
}
