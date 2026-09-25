<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\FreeTrialBooking;
use App\Services\FreeTrialBookingService;
use App\Support\AppTimezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class FreeTrialBookingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $bookings = FreeTrialBooking::query()
            ->where('instructor_id', $user->id)
            ->with(['user:id,name,email'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'confirmed' THEN 1 ELSE 2 END")
            ->orderByDesc('starts_at')
            ->paginate(20);

        return view('instructor.free-trial-bookings.index', compact('bookings'));
    }

    public function show(Request $request, FreeTrialBooking $freeTrialBooking): View
    {
        abort_unless((int) $freeTrialBooking->instructor_id === (int) $request->user()->id, 403);

        $freeTrialBooking->load(['user:id,name,email,phone', 'oneToOneSession']);

        $availableSlots = collect();
        if ($freeTrialBooking->status === FreeTrialBooking::STATUS_PENDING) {
            $availableSlots = FreeTrialBookingService::availableSlots(
                now(),
                now()->addWeeks(3),
                AppTimezone::forUser($request->user()),
                (int) $request->user()->id
            );
        }

        return view('instructor.free-trial-bookings.show', [
            'booking' => $freeTrialBooking,
            'availableSlots' => $availableSlots,
        ]);
    }

    public function accept(Request $request, FreeTrialBooking $freeTrialBooking): RedirectResponse
    {
        abort_unless((int) $freeTrialBooking->instructor_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'starts_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            FreeTrialBookingService::confirmByInstructor(
                $freeTrialBooking,
                $request->user(),
                [
                    'starts_at' => $data['starts_at'] ?? null,
                    'timezone' => AppTimezone::forUser($request->user()),
                    'notes' => $data['notes'] ?? null,
                ]
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['starts_at' => $e->getMessage()]);
        }

        return redirect()
            ->route('instructor.free-trial-bookings.show', $freeTrialBooking)
            ->with('success', 'تم قبول الطلب وتأكيد الموعد.');
    }

    public function reject(Request $request, FreeTrialBooking $freeTrialBooking): RedirectResponse
    {
        abort_unless((int) $freeTrialBooking->instructor_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            FreeTrialBookingService::rejectByInstructor(
                $freeTrialBooking,
                $request->user(),
                $data['reason'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('instructor.free-trial-bookings.index')
            ->with('success', 'تم رفض الطلب وإشعار الطالب.');
    }
}
