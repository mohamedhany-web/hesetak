<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FreeTrialBooking;
use App\Models\User;
use App\Services\FreeTrialBookingService;
use App\Support\AppTimezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FreeTrialBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin() && ! $user->hasPermission('manage.free-trial-bookings'))) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $query = FreeTrialBooking::query()->with([
            'user:id,name,email',
            'instructor:id,name,email',
            'oneToOneSession:id,status,is_complimentary,scheduled_at',
            'recommendedSchoolYear:id,name,level_number',
        ]);

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('goal', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, [
            FreeTrialBooking::STATUS_PENDING,
            FreeTrialBooking::STATUS_CONFIRMED,
            FreeTrialBooking::STATUS_CANCELLED,
            FreeTrialBooking::STATUS_COMPLETED,
        ], true)) {
            $query->where('status', $request->status);
        }

        $goalFilter = (string) $request->input('goal', '');
        if ($goalFilter !== '' && array_key_exists($goalFilter, FreeTrialBooking::goalOptions())) {
            $query->where('goal', $goalFilter);
        }

        if ($request->filled('from')) {
            $query->whereDate('starts_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('starts_at', '<=', $request->input('to'));
        }

        $bookings = $query->orderByDesc('starts_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => FreeTrialBooking::count(),
            'pending' => FreeTrialBooking::where('status', FreeTrialBooking::STATUS_PENDING)->count(),
            'confirmed' => FreeTrialBooking::where('status', FreeTrialBooking::STATUS_CONFIRMED)->count(),
            'upcoming' => FreeTrialBooking::where('status', FreeTrialBooking::STATUS_CONFIRMED)
                ->where('starts_at', '>=', now())->count(),
            'today' => FreeTrialBooking::whereDate('starts_at', today())->count(),
            'cancelled' => FreeTrialBooking::where('status', FreeTrialBooking::STATUS_CANCELLED)->count(),
            'completed' => FreeTrialBooking::where('status', FreeTrialBooking::STATUS_COMPLETED)->count(),
            'free_session' => FreeTrialBooking::where('goal', FreeTrialBooking::GOAL_FREE_SESSION)->count(),
            'from_instructor_page' => FreeTrialBooking::where('goal', FreeTrialBooking::GOAL_FREE_SESSION)
                ->whereNotNull('instructor_id')
                ->count(),
        ];

        return view('admin.free-trial-bookings.index', [
            'bookings' => $bookings,
            'stats' => $stats,
            'goalOptions' => FreeTrialBooking::goalOptions(),
        ]);
    }

    public function create(): View
    {
        $students = User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        $instructors = User::query()
            ->whereIn('role', ['instructor', 'teacher'])
            ->where('is_active', true)
            ->whereHas('instructorProfile', fn ($q) => $q->approved())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'timezone']);

        return view('admin.free-trial-bookings.create', [
            'students' => $students,
            'instructors' => $instructors,
            'slotsUrl' => route('admin.placement.slots'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'instructor_id' => ['required', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'timezone' => AppTimezone::inputRules(),
            'notes' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['nullable', 'integer', 'in:30,45,50,60,90'],
        ]);

        $student = User::query()->findOrFail($data['student_id']);
        $instructor = User::query()->findOrFail($data['instructor_id']);

        if (! $student->isStudent()) {
            return back()->withInput()->with('error', 'المستخدم المحدد ليس طالباً.');
        }
        if (! $instructor->isInstructor()) {
            return back()->withInput()->with('error', 'المستخدم المحدد ليس معلماً.');
        }

        $clockTz = AppTimezone::resolveInput(
            is_string($data['timezone'] ?? null) ? $data['timezone'] : null,
            $instructor
        );

        $data = AppTimezone::shiftRequestDateTime(
            $request,
            $data,
            'scheduled_at',
            mustBeFuture: true,
            fallbackUser: $instructor
        );

        try {
            $booking = FreeTrialBookingService::assignManual(
                $student,
                $instructor,
                $data['scheduled_at'],
                $request->user(),
                $data['notes'] ?? null,
                $clockTz,
                isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null,
                requireAvailability: false
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.free-trial-bookings.show', $booking)
            ->with('success', 'تم توصيف الحصة المجانية وظهرت في جدولي الطالب والمعلم دون خصم من الرصيد.');
    }

    public function show(FreeTrialBooking $freeTrialBooking): View
    {
        $freeTrialBooking->load([
            'user:id,name,email,phone',
            'instructor:id,name,email',
            'oneToOneSession',
            'recommendedSchoolYear:id,name,level_number',
        ]);

        $instructors = User::query()
            ->whereIn('role', ['instructor', 'teacher'])
            ->where('is_active', true)
            ->whereHas('instructorProfile', fn ($q) => $q->approved())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.free-trial-bookings.show', [
            'booking' => $freeTrialBooking,
            'schoolYears' => AcademicYear::query()->ordered()->get(['id', 'name', 'level_number', 'code']),
            'instructors' => $instructors,
        ]);
    }

    public function updateStatus(Request $request, FreeTrialBooking $freeTrialBooking): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,completed'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'recommended_academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $freeTrialBooking->update([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $freeTrialBooking->notes,
            'admin_notes' => $data['admin_notes'] ?? $freeTrialBooking->admin_notes,
            'recommended_academic_year_id' => $data['recommended_academic_year_id'] ?? null,
            'instructor_id' => $data['instructor_id'] ?? null,
        ]);

        FreeTrialBookingService::syncLinkedSessionStatus($freeTrialBooking->fresh(), $data['status']);

        return redirect()
            ->route('admin.free-trial-bookings.show', $freeTrialBooking)
            ->with('success', 'تم تحديث الحجز ومزامنة الجدول.');
    }

    public function destroy(FreeTrialBooking $freeTrialBooking): RedirectResponse
    {
        if ($freeTrialBooking->one_to_one_session_id) {
            FreeTrialBookingService::syncLinkedSessionStatus(
                $freeTrialBooking,
                FreeTrialBooking::STATUS_CANCELLED
            );
        }

        $freeTrialBooking->delete();

        return redirect()
            ->route('admin.free-trial-bookings.index')
            ->with('success', 'تم حذف الحجز.');
    }
}
