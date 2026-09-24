<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\TutorApplication;
use App\Models\TutorInterview;
use App\Models\TutorInterviewSlot;
use App\Services\LiveKitTokenService;
use App\Services\TutorInterviewBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TutorInterviewController extends Controller
{
    public function pick(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isInstructor(), 403);

        $application = TutorApplication::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->firstOrFail();

        if ($application->status === TutorApplication::STATUS_BLOCKED_NO_SHOW) {
            return view('tutor.interview-blocked', compact('application'));
        }

        if (! in_array($application->status, [
            TutorApplication::STATUS_INTERVIEW_PENDING,
            TutorApplication::STATUS_INTERVIEW_SCHEDULED,
        ], true)) {
            return redirect()
                ->route('public.tutor.apply.profile')
                ->with('error', 'لا توجد دعوة مقابلة مفتوحة حالياً.');
        }

        $slots = TutorInterviewSlot::query()
            ->where('is_open', true)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (TutorInterviewSlot $s) => $s->remainingCapacity() > 0)
            ->values();

        $current = TutorInterview::query()
            ->where('tutor_application_id', $application->id)
            ->where('status', TutorInterview::STATUS_SCHEDULED)
            ->latest('id')
            ->first();

        return view('tutor.interview-pick', compact('application', 'slots', 'current'));
    }

    public function book(Request $request, TutorInterviewBookingService $booking): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isInstructor(), 403);

        $data = $request->validate([
            'slot_id' => ['required', 'integer', 'exists:tutor_interview_slots,id'],
        ]);

        $application = TutorApplication::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->firstOrFail();

        $slot = TutorInterviewSlot::query()->findOrFail($data['slot_id']);

        try {
            $booking->bookSlot($application, $slot, $user);
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('tutor.interview.pick')
            ->with('success', 'تم تأكيد موعد المقابلة. تحقق من الإيميل والواتساب.');
    }

    public function join(Request $request, TutorInterview $interview, LiveKitTokenService $tokens): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $interview->load('application');
        $app = $interview->application;
        abort_unless($app, 404);

        $isOwner = (int) $app->user_id === (int) $user->id;
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : ($user->role === 'admin' || $user->role === 'super_admin');
        abort_unless($isOwner || $isAdmin, 403);

        if ($interview->meeting_mode === TutorInterviewSlot::MODE_EXTERNAL && filled($interview->external_url)) {
            return redirect()->away($interview->external_url);
        }

        if (! $tokens->isConfigured()) {
            return view('tutor.interview-join-fallback', [
                'interview' => $interview,
                'message' => 'إعدادات البث غير مكتملة. تواصل مع الإدارة.',
            ]);
        }

        $roomName = $interview->room_name ?: ('tutor-ivw-'.$interview->id);
        $livekitToken = $tokens->createJoinToken($roomName, $user, [
            'canPublish' => true,
            'canSubscribe' => true,
            'roomAdmin' => $isAdmin,
        ]);

        return view('tutor.interview-join', [
            'interview' => $interview,
            'livekitUrl' => $tokens->wsUrl(),
            'livekitToken' => $livekitToken,
            'user' => $user,
            'lkRole' => $isAdmin ? 'host' : 'participant',
        ]);
    }
}
