<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\InstructorProfile;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\PackageSlotProposalService;
use App\Support\AppTimezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * الطالب يرسل مواعيد مقترحة (من جدول المعلم أو مواعيد من عنده) لحساب المعلم ليقبلها أو يرفضها.
 */
class PackageSlotProposalController extends Controller
{
    public function store(Request $request, ServicePackage $servicePackage): RedirectResponse
    {
        abort_unless($servicePackage->is_active, 404);

        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'))
                ->with('info', 'سجّل الدخول كطالب لإرسال المواعيد للمعلم.');
        }

        $data = $request->validate([
            'instructor_id' => ['required', 'integer'],
            'year_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'schedule_notes' => ['nullable', 'string', 'max:1000'],
            'suggested_slots' => ['nullable', 'array'],
            'suggested_slots.*' => ['string', 'max:64'],
            'proposed' => ['nullable', 'array', 'max:'.PackageSlotProposalService::MAX_SLOTS],
            'proposed.*.date' => ['nullable', 'date'],
            'proposed.*.hour' => ['nullable', 'integer', 'min:1', 'max:12'],
            'proposed.*.minute' => ['nullable', 'in:00,15,30,45'],
            'proposed.*.period' => ['nullable', 'in:AM,PM'],
        ]);

        $instructor = User::query()
            ->whereKey((int) $data['instructor_id'])
            ->where('is_active', true)
            ->whereIn('role', ['instructor', 'teacher'])
            ->whereHas('instructorProfile', fn ($q) => $q->where('status', InstructorProfile::STATUS_APPROVED))
            ->first();
        if (! $instructor) {
            return back()->withInput()->with('error', 'المعلم غير متاح حالياً.');
        }

        $timezone = AppTimezone::forUser($user);
        $custom = PackageSlotProposalService::parseCustomSlots($data['proposed'] ?? [], $timezone)
            ->map(fn ($slot) => $slot->toIso8601String())
            ->all();

        try {
            PackageSlotProposalService::createProposal(
                $user,
                $instructor,
                array_merge($data['suggested_slots'] ?? [], $custom),
                (int) $servicePackage->id,
                isset($data['subject_id']) ? (int) $data['subject_id'] : null,
                isset($data['year_id']) ? (int) $data['year_id'] : null,
                $data['schedule_notes'] ?? null,
                $timezone,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم إرسال المواعيد إلى حساب المعلم — سيصلك إشعار عند قبولها أو رفضها.');
    }
}
