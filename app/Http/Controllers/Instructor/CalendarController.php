<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Services\TeachingCalendarService;
use App\Support\AppTimezone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:instructor|teacher']);
    }

    public function index()
    {
        $user = Auth::user();
        $viewerTz = AppTimezone::forInstructorId((int) $user->id);
        $academyTz = AppTimezone::academy();
        $events = TeachingCalendarService::forInstructor(
            $user,
            now($viewerTz)->subMonths(1)->utc(),
            now($viewerTz)->addMonths(3)->utc()
        );

        $stats = [
            'total' => $events->count(),
            'upcoming' => $events->filter(function ($event) {
                $start = $event->start_date ?? null;
                if (! $start) {
                    return false;
                }
                $ts = $start instanceof \Carbon\CarbonInterface
                    ? $start->copy()->utc()
                    : \Carbon\Carbon::parse($start)->utc();

                return $ts->gte(now('UTC'));
            })->count(),
        ];

        return view('instructor.calendar.index', compact('events', 'stats', 'viewerTz', 'academyTz'));
    }

    public function getEvents(Request $request)
    {
        $user = Auth::user();
        $viewerTz = AppTimezone::forInstructorId((int) $user->id);
        $start = $request->get('start');
        $end = $request->get('end');
        // FullCalendar يرسل الحدود بتوقيت العرض؛ نحوّلها لـ UTC قبل الاستعلام
        if (is_string($start) && $start !== '') {
            $start = \Carbon\Carbon::parse($start, $viewerTz)->utc();
        }
        if (is_string($end) && $end !== '') {
            $end = \Carbon\Carbon::parse($end, $viewerTz)->utc();
        }
        $events = TeachingCalendarService::forInstructor($user, $start, $end);

        return response()->json(TeachingCalendarService::toFullCalendar($events, $viewerTz));
    }
}
