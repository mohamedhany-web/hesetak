<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\FreeTrialBooking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FreeTrialBookingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $bookings = FreeTrialBooking::query()
            ->where('instructor_id', $user->id)
            ->with(['user:id,name,email'])
            ->orderByDesc('starts_at')
            ->paginate(20);

        return view('instructor.free-trial-bookings.index', compact('bookings'));
    }

    public function show(Request $request, FreeTrialBooking $freeTrialBooking): View
    {
        abort_unless((int) $freeTrialBooking->instructor_id === (int) $request->user()->id, 403);

        $freeTrialBooking->load(['user:id,name,email,phone']);

        return view('instructor.free-trial-bookings.show', [
            'booking' => $freeTrialBooking,
        ]);
    }
}
