<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\TutoringGroupBooking;
use App\Services\TutoringGroupOrchestrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TutoringBookingController extends Controller
{
    /**
     * Group bookings are out of Hesetak scope (1:1 only) — redirect to private lessons.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()
            ->route('instructor.one-to-one-sessions.index')
            ->with('info', app()->getLocale() === 'ar'
                ? 'حصتك تعتمد على الحصص الخاصة (1:1) — تم توجيهك لحصصك الخاصة.'
                : 'Hesetak uses 1:1 private lessons — redirected to your private sessions.');
    }

    public function show(Request $request, TutoringGroupBooking $booking): RedirectResponse
    {
        abort_unless((int) $booking->instructor_id === (int) $request->user()->id, 403);

        return redirect()->route('instructor.one-to-one-sessions.index');
    }

    public function complete(Request $request, TutoringGroupBooking $booking): RedirectResponse
    {
        abort_unless((int) $booking->instructor_id === (int) $request->user()->id, 403);

        if ($booking->starts_at && $booking->starts_at->isFuture()) {
            return back()->with('error', 'لا يمكن إكمال الحصة قبل موعد بدايتها.');
        }

        try {
            TutoringGroupOrchestrationService::completeBooking($booking);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('instructor.one-to-one-sessions.index')
            ->with('success', 'تم إكمال الحصة.');
    }
}
