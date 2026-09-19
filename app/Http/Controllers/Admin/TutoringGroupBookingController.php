<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServicePackage;
use App\Models\StudentServiceEntitlement;
use App\Models\TutoringGroup;
use App\Models\TutoringGroupBooking;
use App\Models\TutoringGroupCohort;
use App\Models\User;
use App\Services\StudentEntitlementService;
use App\Services\TutoringGroupOrchestrationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TutoringGroupBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin() && ! $user->hasPermission('manage.tutoring-groups'))) {
                abort(403);
            }

            if (! config('admin_ui.show_group_bookings', false) && ! config('admin_ui.show_group_classes', false)) {
                return redirect()
                    ->route('admin.dashboard')
                    ->with('warning', 'حجوزات وتسكين المجموعات خارج نطاق حصتك الحالي (دروس فردية 1:1 فقط).');
            }

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $query = TutoringGroupBooking::query()
            ->with([
                'tutoringGroup:id,title,type,slug,academic_year_id',
                'tutoringGroup.schoolYear:id,name,level_number',
                'instructor:id,name',
                'user:id,name,email,phone',
                'cohort:id,title',
                'classroomMeeting:id,code',
                'entitlement:id,order_id,scope,units_total,units_used',
                'order:id,order_type,status',
            ]);

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('guest_name', 'like', "%{$s}%")
                    ->orWhere('guest_email', 'like', "%{$s}%")
                    ->orWhere('guest_phone', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
                    ->orWhereHas('tutoringGroup', fn ($g) => $g->where('title', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status') && in_array($request->status, [
            TutoringGroupBooking::STATUS_PENDING,
            TutoringGroupBooking::STATUS_CONFIRMED,
            TutoringGroupBooking::STATUS_CANCELLED,
            TutoringGroupBooking::STATUS_COMPLETED,
        ], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type') && in_array($request->type, [
            TutoringGroup::TYPE_INDIVIDUAL,
            TutoringGroup::TYPE_COLLECTIVE,
        ], true)) {
            $query->whereHas('tutoringGroup', fn ($q) => $q->where('type', $request->type));
        }

        if ($request->filled('from')) {
            $query->whereDate('starts_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('starts_at', '<=', $request->input('to'));
        }

        $bookings = $query->orderByDesc('starts_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => TutoringGroupBooking::count(),
            'pending' => TutoringGroupBooking::where('status', TutoringGroupBooking::STATUS_PENDING)->count(),
            'confirmed' => TutoringGroupBooking::where('status', TutoringGroupBooking::STATUS_CONFIRMED)->count(),
            'upcoming' => TutoringGroupBooking::where('status', TutoringGroupBooking::STATUS_CONFIRMED)
                ->where('starts_at', '>=', now())->count(),
            'completed' => TutoringGroupBooking::where('status', TutoringGroupBooking::STATUS_COMPLETED)->count(),
            'credits_active' => StudentServiceEntitlement::active()->count(),
            'credits_bookable' => StudentServiceEntitlement::active()->get()
                ->sum(fn (StudentServiceEntitlement $entitlement) => StudentEntitlementService::bookableUnitsLeft($entitlement)),
        ];

        return view('admin.tutoring-group-bookings.index', compact('bookings', 'stats'));
    }

    public function show(TutoringGroupBooking $tutoringGroupBooking): View
    {
        $tutoringGroupBooking->load([
            'tutoringGroup',
            'instructor:id,name,email,phone',
            'user:id,name,email,phone',
            'cohort',
            'package',
            'classroomMeeting',
            'order',
            'entitlement.servicePackage',
        ]);

        return view('admin.tutoring-group-bookings.show', [
            'booking' => $tutoringGroupBooking,
            'groups' => TutoringGroup::query()->active()->orderBy('title')->get(['id', 'title', 'type', 'duration_minutes']),
            'instructors' => User::query()->whereIn('role', ['instructor', 'teacher'])->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        $entitlements = StudentServiceEntitlement::query()
            ->active()
            ->whereColumn('units_used', '<', 'units_total')
            ->with(['user:id,name,email,phone', 'tutoringGroup:id,title', 'servicePackage:id,name'])
            ->orderBy('expires_at')
            ->limit(1000)
            ->get()
            ->filter(fn (StudentServiceEntitlement $entitlement) => StudentEntitlementService::bookableUnitsLeft($entitlement) > 0);

        return view('admin.tutoring-group-bookings.create', [
            'entitlements' => $entitlements,
            'groups' => TutoringGroup::query()->active()->with('instructor:id,name')->orderBy('title')->get(),
            'instructors' => User::query()->whereIn('role', ['instructor', 'teacher'])->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
            'selectedEntitlementId' => (int) $request->integer('entitlement_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_service_entitlement_id' => ['required', 'exists:student_service_entitlements,id'],
            'tutoring_group_id' => ['required', 'exists:tutoring_groups,id'],
            'instructor_id' => ['required', 'exists:users,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'confirm_now' => ['nullable', 'boolean'],
        ]);

        try {
            $booking = DB::transaction(function () use ($data) {
                $entitlement = StudentServiceEntitlement::query()
                    ->with('user')
                    ->lockForUpdate()
                    ->findOrFail($data['student_service_entitlement_id']);
                $group = TutoringGroup::query()->findOrFail($data['tutoring_group_id']);
                $instructor = User::query()->findOrFail($data['instructor_id']);

                if (! $entitlement->isActive() || StudentEntitlementService::bookableUnitsLeft($entitlement) < 1) {
                    throw new \InvalidArgumentException('الرصيد غير نشط أو لا يحتوي على حصة قابلة للحجز.');
                }
                if (! $instructor->isInstructor()) {
                    throw new \InvalidArgumentException('المستخدم المحدد ليس معلماً.');
                }

                $requiredScope = StudentEntitlementService::scopeForTutoringGroup($group);
                if (! in_array($entitlement->scope, [$requiredScope, ServicePackage::SCOPE_GLOBAL], true)) {
                    throw new \InvalidArgumentException('نطاق الرصيد لا يسمح بالحجز في هذه المجموعة.');
                }
                if ($entitlement->tutoring_group_id && (int) $entitlement->tutoring_group_id !== (int) $group->id) {
                    throw new \InvalidArgumentException('هذا الرصيد مخصص لمجموعة أخرى.');
                }

                $startsAt = Carbon::parse($data['starts_at']);
                $endsAt = $startsAt->copy()->addMinutes(max(30, (int) ($group->duration_minutes ?: 60)));
                $this->assertInstructorAvailable((int) $instructor->id, $startsAt, $endsAt);

                $cohortId = null;
                if ($group->isCollective()) {
                    $cohortId = TutoringGroupCohort::query()
                        ->where('tutoring_group_id', $group->id)
                        ->whereIn('status', [
                            TutoringGroupCohort::STATUS_OPEN,
                            TutoringGroupCohort::STATUS_POSTPONED,
                        ])
                        ->orderByRaw('CASE WHEN starts_at IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('starts_at')
                        ->orderBy('id')
                        ->value('id');
                }

                $booking = TutoringGroupBooking::create([
                    'tutoring_group_id' => $group->id,
                    'cohort_id' => $cohortId,
                    'student_service_entitlement_id' => $entitlement->id,
                    'order_id' => $entitlement->order_id,
                    'payment_status' => TutoringGroupBooking::PAYMENT_PAID,
                    'instructor_id' => $instructor->id,
                    'user_id' => $entitlement->user_id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => TutoringGroupBooking::STATUS_PENDING,
                    'admin_notes' => trim(($data['admin_notes'] ?? '')."\nتسكين يدوي من الإدارة"),
                ]);

                return (bool) ($data['confirm_now'] ?? true)
                    ? TutoringGroupOrchestrationService::confirmBooking($booking)
                    : $booking;
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.tutoring-group-bookings.show', $booking)
            ->with('success', 'تم تسكين الطالب وإنشاء الحجز'.($booking->status === TutoringGroupBooking::STATUS_CONFIRMED ? ' وغرفة Live بنجاح.' : '.'));
    }

    public function updateAssignment(Request $request, TutoringGroupBooking $tutoringGroupBooking): RedirectResponse
    {
        $data = $request->validate([
            'tutoring_group_id' => ['required', 'exists:tutoring_groups,id'],
            'instructor_id' => ['required', 'exists:users,id'],
            'starts_at' => ['required', 'date'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (in_array($tutoringGroupBooking->status, [TutoringGroupBooking::STATUS_COMPLETED, TutoringGroupBooking::STATUS_CANCELLED], true)) {
            return back()->with('error', 'لا يمكن إعادة تسكين حجز مكتمل أو ملغي.');
        }

        try {
            DB::transaction(function () use ($data, $tutoringGroupBooking) {
                $group = TutoringGroup::query()->findOrFail($data['tutoring_group_id']);
                $instructor = User::query()->findOrFail($data['instructor_id']);
                if (! $instructor->isInstructor()) {
                    throw new \InvalidArgumentException('المستخدم المحدد ليس معلماً.');
                }

                $startsAt = Carbon::parse($data['starts_at']);
                $endsAt = $startsAt->copy()->addMinutes(max(30, (int) ($group->duration_minutes ?: 60)));
                $this->assertInstructorAvailable((int) $instructor->id, $startsAt, $endsAt, (int) $tutoringGroupBooking->id);

                $tutoringGroupBooking->update([
                    'tutoring_group_id' => $group->id,
                    'instructor_id' => $instructor->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'admin_notes' => $data['admin_notes'] ?? $tutoringGroupBooking->admin_notes,
                ]);

                if ($tutoringGroupBooking->classroomMeeting) {
                    $tutoringGroupBooking->classroomMeeting->update([
                        'user_id' => $instructor->id,
                        'scheduled_for' => $startsAt,
                        'planned_duration_minutes' => $startsAt->diffInMinutes($endsAt),
                    ]);
                }
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم تحديث المعلم والمجموعة والموعد مع مزامنة غرفة Live.');
    }

    public function updateStatus(Request $request, TutoringGroupBooking $tutoringGroupBooking): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,completed'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tutoringGroupBooking->update([
            'admin_notes' => $data['admin_notes'] ?? $tutoringGroupBooking->admin_notes,
        ]);

        try {
            match ($data['status']) {
                TutoringGroupBooking::STATUS_CONFIRMED => TutoringGroupOrchestrationService::confirmBooking($tutoringGroupBooking),
                TutoringGroupBooking::STATUS_CANCELLED => TutoringGroupOrchestrationService::cancelBooking($tutoringGroupBooking),
                TutoringGroupBooking::STATUS_COMPLETED => TutoringGroupOrchestrationService::completeBooking($tutoringGroupBooking),
                default => $tutoringGroupBooking->update(['status' => $data['status']]),
            };
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.tutoring-group-bookings.show', $tutoringGroupBooking)
            ->with('success', 'تم تحديث حالة الحجز'.($data['status'] === 'confirmed' ? ' وإنشاء غرفة Live.' : '.'));
    }

    public function destroy(TutoringGroupBooking $tutoringGroupBooking): RedirectResponse
    {
        try {
            if (in_array($tutoringGroupBooking->status, [
                TutoringGroupBooking::STATUS_PENDING,
                TutoringGroupBooking::STATUS_CONFIRMED,
            ], true)) {
                TutoringGroupOrchestrationService::cancelBooking($tutoringGroupBooking, 'حذف من لوحة الإدارة');
            }
            $tutoringGroupBooking->delete();
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.tutoring-group-bookings.index')
            ->with('success', 'تم إلغاء الحجز ثم حذفه.');
    }

    private function assertInstructorAvailable(int $instructorId, Carbon $startsAt, Carbon $endsAt, ?int $ignoreBookingId = null): void
    {
        $conflict = TutoringGroupBooking::query()
            ->where('instructor_id', $instructorId)
            ->whereIn('status', [TutoringGroupBooking::STATUS_PENDING, TutoringGroupBooking::STATUS_CONFIRMED])
            ->when($ignoreBookingId, fn ($query) => $query->where('id', '!=', $ignoreBookingId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($conflict) {
            throw new \InvalidArgumentException('المعلم لديه حجز متداخل في هذا الموعد.');
        }
    }
}
