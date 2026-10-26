<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServicePackage;
use App\Models\StudentServiceEntitlement;
use App\Models\TutoringGroup;
use App\Models\User;
use App\Services\StudentEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentEntitlementController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin() && ! $user->hasPermission('manage.packages') && ! $user->hasPermission('manage.tutoring-groups') && ! $user->hasPermission('manage.users'))) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $query = StudentServiceEntitlement::query()
            ->with([
                'user:id,name,email',
                'servicePackage:id,name',
                'tutoringGroup:id,title',
                'academicYear:id,name',
                'academicSubject:id,name',
                'order:id,order_type,status',
            ])
            ->withCount(['bookings as reserved_units_count' => fn ($q) => $q->blocking()])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }
        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->whereHas('user', function ($q) use ($s) {
                $q->where('role', 'student')
                    ->where(function ($inner) use ($s) {
                        $inner->where('name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%")
                            ->orWhere('phone', 'like', "%{$s}%");
                    });
            });
        }

        $entitlements = $query->paginate(25)->withQueryString();

        $stats = [
            'active' => StudentServiceEntitlement::query()->where('status', 'active')->count(),
            'units_left' => (int) StudentServiceEntitlement::query()
                ->where('status', 'active')
                ->selectRaw('COALESCE(SUM(GREATEST(units_total - units_used, 0)), 0) as left_units')
                ->value('left_units'),
        ];

        return view('admin.student-entitlements.index', compact('entitlements', 'stats'));
    }

    public function create(Request $request): View
    {
        // Placement passes student_id; grant form historically used user_id — accept both.
        $selectedUserId = (int) $request->integer('user_id');
        if ($selectedUserId < 1) {
            $selectedUserId = (int) $request->integer('student_id');
        }

        $returnToPlacement = $request->boolean('return_to_placement')
            || $request->filled('student_id')
            || $request->query('from') === 'placement';

        return view('admin.student-entitlements.form', [
            'students' => User::query()
                ->where('role', 'student')
                ->orderBy('name')
                ->limit(800)
                ->get(['id', 'name', 'email', 'phone']),
            'scopes' => ServicePackage::scopes(),
            'groups' => TutoringGroup::query()->active()->orderBy('title')->get(['id', 'title', 'type']),
            'selectedUserId' => $selectedUserId,
            'returnToPlacement' => $returnToPlacement,
            'placementUrl' => \Illuminate\Support\Facades\Route::has('admin.placement.create')
                ? route('admin.placement.create')
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'scope' => ['required', 'in:global,tutoring_individual,tutoring_collective,private_lessons'],
            'units' => ['required', 'integer', 'min:1', 'max:500'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:730'],
            'tutoring_group_id' => ['nullable', 'exists:tutoring_groups,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'return_to_placement' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['tutoring_group_id'])) {
            $group = TutoringGroup::query()->findOrFail($data['tutoring_group_id']);
            $requiredScope = StudentEntitlementService::scopeForTutoringGroup($group);
            if (! in_array($data['scope'], [$requiredScope, ServicePackage::SCOPE_GLOBAL], true)) {
                return back()->withInput()->with('error', 'نطاق الرصيد لا يطابق نوع المجموعة المحددة.');
            }
        }

        $entitlement = StudentEntitlementService::grantManual(
            userId: (int) $data['user_id'],
            scope: $data['scope'],
            units: (int) $data['units'],
            tutoringGroupId: isset($data['tutoring_group_id']) ? (int) $data['tutoring_group_id'] : null,
            durationDays: isset($data['duration_days']) ? (int) $data['duration_days'] : null,
            notes: $data['notes'] ?? null,
        );

        $studentId = (int) $data['user_id'];
        $returnToPlacement = $request->boolean('return_to_placement')
            && \Illuminate\Support\Facades\Route::has('admin.placement.create');

        if ($returnToPlacement) {
            $mode = in_array($data['scope'], [ServicePackage::SCOPE_PRIVATE_LESSONS, ServicePackage::SCOPE_GLOBAL], true)
                ? 'private'
                : 'group';

            return redirect()
                ->route('admin.placement.create', array_filter([
                    'student_id' => $studentId,
                    'entitlement_id' => $entitlement?->id,
                    'mode' => $mode,
                ]))
                ->with('success', 'تم منح الرصيد. يمكنك الآن اختيار الباقة وإتمام التسكين.');
        }

        return redirect()->route('admin.student-entitlements.index')->with('success', 'تم منح الرصيد للطالب.');
    }

    public function adjust(Request $request, StudentServiceEntitlement $studentEntitlement): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:add,subtract,cancel'],
            'units' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        if ($data['action'] === 'cancel') {
            $studentEntitlement->update(['status' => StudentServiceEntitlement::STATUS_CANCELLED]);
            StudentEntitlementService::syncLinkedSubscriptionsFromEntitlement($studentEntitlement->fresh());

            return back()->with('success', 'تم إلغاء الرصيد ومزامنة الاشتراك المرتبط.');
        }

        $units = (int) ($data['units'] ?? 1);
        if ($data['action'] === 'add') {
            $studentEntitlement->update([
                'units_total' => (int) $studentEntitlement->units_total + $units,
                'status' => StudentServiceEntitlement::STATUS_ACTIVE,
            ]);
        } else {
            $studentEntitlement->units_used = min(
                (int) $studentEntitlement->units_total,
                (int) $studentEntitlement->units_used + $units
            );
            if ($studentEntitlement->units_used >= (int) $studentEntitlement->units_total) {
                $studentEntitlement->status = StudentServiceEntitlement::STATUS_EXPIRED;
            }
            $studentEntitlement->save();
        }

        StudentEntitlementService::syncLinkedSubscriptionsFromEntitlement($studentEntitlement->fresh());

        return back()->with('success', 'تم تعديل الرصيد ومزامنة الاشتراك المرتبط.');
    }
}
