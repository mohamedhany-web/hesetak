<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TutorHiringSetting;
use App\Models\TutorInterviewSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorInterviewSlotController extends Controller
{
    public function index(): View
    {
        $slots = TutorInterviewSlot::query()
            ->withCount(['interviews as booked_count' => function ($q) {
                $q->whereIn('status', ['scheduled', 'completed']);
            }])
            ->orderByDesc('starts_at')
            ->paginate(30);

        $duration = TutorHiringSetting::int('interview_duration_minutes', 30);

        return view('admin.tutor-interviews.slots', compact('slots', 'duration'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'meeting_mode' => ['required', 'in:livekit,external'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'title' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_open' => ['nullable', 'boolean'],
        ]);

        $starts = \Carbon\Carbon::parse($data['starts_at']);
        $ends = ! empty($data['ends_at'])
            ? \Carbon\Carbon::parse($data['ends_at'])
            : $starts->copy()->addMinutes(TutorHiringSetting::int('interview_duration_minutes', 30));

        if ($data['meeting_mode'] === 'external' && empty($data['external_url'])) {
            return back()->withInput()->withErrors(['external_url' => 'رابط المقابلة الخارجي مطلوب.']);
        }

        TutorInterviewSlot::create([
            'starts_at' => $starts,
            'ends_at' => $ends,
            'capacity' => $data['capacity'] ?? 1,
            'meeting_mode' => $data['meeting_mode'],
            'external_url' => $data['external_url'] ?? null,
            'title' => $data['title'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_open' => $request->boolean('is_open', true),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'تم إضافة موعد المقابلة.');
    }

    public function update(Request $request, TutorInterviewSlot $slot): RedirectResponse
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'meeting_mode' => ['required', 'in:livekit,external'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'title' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_open' => ['nullable', 'boolean'],
        ]);

        $starts = \Carbon\Carbon::parse($data['starts_at']);
        $ends = ! empty($data['ends_at'])
            ? \Carbon\Carbon::parse($data['ends_at'])
            : $starts->copy()->addMinutes(TutorHiringSetting::int('interview_duration_minutes', 30));

        $slot->update([
            'starts_at' => $starts,
            'ends_at' => $ends,
            'capacity' => $data['capacity'] ?? 1,
            'meeting_mode' => $data['meeting_mode'],
            'external_url' => $data['external_url'] ?? null,
            'title' => $data['title'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_open' => $request->boolean('is_open', true),
        ]);

        return back()->with('success', 'تم تحديث الموعد.');
    }

    public function destroy(TutorInterviewSlot $slot): RedirectResponse
    {
        if ($slot->bookedCount() > 0) {
            return back()->with('error', 'لا يمكن حذف موعد عليه حجوزات. أغلقه بدل الحذف.');
        }

        $slot->delete();

        return back()->with('success', 'تم حذف الموعد.');
    }
}
