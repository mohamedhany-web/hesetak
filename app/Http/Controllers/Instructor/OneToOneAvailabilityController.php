<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Services\OneToOneAvailabilityService;
use App\Support\AppTimezone;
use App\Support\WeeklyScheduleTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OneToOneAvailabilityController extends Controller
{
    public function index(Request $request): View
    {
        $instructorId = $request->user()->id;
        $rules = OneToOneAvailabilityService::rulesForInstructor($instructorId);
        $dayLabels = OneToOneAvailabilityService::dayLabels();

        $grouped = collect($dayLabels)->map(function ($label, $day) use ($rules) {
            return [
                'day' => $day,
                'label' => $label,
                'rules' => $rules->where('day_of_week', $day)->values(),
            ];
        });

        return view('instructor.one-to-one-availability.index', [
            'grouped' => $grouped,
            'dayLabels' => $dayLabels,
            'rules' => $rules,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(array_merge(
            WeeklyScheduleTime::slotTimeRules(180),
            ['timezone' => AppTimezone::inputRules()]
        ));

        AppTimezone::persistForUser(
            $request->user(),
            is_string($data['timezone'] ?? null) ? $data['timezone'] : null
        );

        $slots = $data['slots'] ?? [];
        $distinctWindows = collect($slots)
            ->filter(fn ($s) => filled($s['day_of_week'] ?? null) && filled($s['start_time'] ?? null))
            ->map(fn ($s) => ((int) $s['day_of_week']).'|'.substr((string) $s['start_time'], 0, 5))
            ->unique()
            ->count();

        if ($distinctWindows < 4) {
            return back()
                ->withInput()
                ->withErrors([
                    'slots' => 'يلزم تحديد 4 مواعيد أسبوعية على الأقل (أيام/أوقات مختلفة) لقبول جدولك.',
                ]);
        }

        OneToOneAvailabilityService::syncRules(
            $request->user()->id,
            $slots
        );

        return back()->with('success', __('student.one_to_one_availability_saved'));
    }
}
