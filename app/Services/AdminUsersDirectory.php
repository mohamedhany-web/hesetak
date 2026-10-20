<?php

namespace App\Services;

use App\Models\InstructorProfile;
use App\Models\User;
use App\Support\HesetakMatchCatalog;
use App\Support\UserContactPrivacy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * دليل المستخدمين الموحّد: الأنواع الأربعة + فلاتر + صفوف عرض/تصدير.
 */
class AdminUsersDirectory
{
    public const TYPES = ['student', 'parent', 'instructor', 'employee'];

    /**
     * @return array{
     *   users: LengthAwarePaginator,
     *   counts: array<string, int>,
     *   type: string,
     *   can_view_contacts: bool,
     *   filters: array{search:?string,status:?string}
     * }
     */
    public function index(Request $request, ?User $viewer): array
    {
        $type = $this->normalizeType($request->input('type'));
        $canViewContacts = UserContactPrivacy::canViewContacts($viewer);

        $query = $this->baseQuery()->with($this->eagerLoads($type));
        $this->applyTypeFilter($query, $type);
        $this->applyListFilters($query, $request);

        $users = $query
            ->latest('created_at')
            ->paginate(25)
            ->appends($request->query());

        return [
            'users' => $users,
            'counts' => $this->typeCounts(),
            'type' => $type,
            'can_view_contacts' => $canViewContacts,
            'filters' => [
                'search' => $request->filled('search') ? trim((string) $request->input('search')) : null,
                'status' => $request->filled('status') ? (string) $request->input('status') : null,
            ],
        ];
    }

    /**
     * @return Collection<int, array<string, string|null>>
     */
    public function exportRows(Request $request, ?User $viewer, int $limit = 5000): Collection
    {
        $type = $this->normalizeType($request->input('type'));
        $canViewContacts = UserContactPrivacy::canViewContacts($viewer);

        $query = $this->baseQuery()->with($this->eagerLoads($type));
        $this->applyTypeFilter($query, $type);
        $this->applyListFilters($query, $request);

        return $query
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (User $user) => $this->rowPayload($user, $canViewContacts, forExport: true, forceType: $type));
    }

    /**
     * @return array<string, int>
     */
    public function typeCounts(): array
    {
        return [
            'all' => (int) $this->baseQuery()->count(),
            'student' => (int) $this->baseQuery()->where('role', 'student')->count(),
            'parent' => (int) $this->parentScope($this->baseQuery())->count(),
            'instructor' => (int) $this->baseQuery()->whereIn('role', ['instructor', 'teacher'])->count(),
            'employee' => (int) $this->baseQuery()->where('is_employee', true)->count(),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function rowPayload(User $user, bool $canViewContacts, bool $forExport = false, ?string $forceType = null): array
    {
        $type = $forceType && $forceType !== 'all'
            ? $forceType
            : $this->classifyType($user);
        $email = trim((string) ($user->email ?? ''));
        $phone = trim((string) ($user->phone ?? ''));

        if (! $canViewContacts) {
            $email = UserContactPrivacy::maskEmail($email);
            $phone = UserContactPrivacy::maskPhone($phone);
        } elseif ($email === '') {
            $email = $forExport ? '' : '—';
        }
        if ($canViewContacts && $phone === '') {
            $phone = $forExport ? '' : '—';
        }

        return [
            'id' => (string) $user->id,
            'name' => (string) $user->name,
            'email' => $email,
            'phone' => $phone,
            'type' => $type,
            'type_label' => $this->typeLabel($type),
            'status' => $user->is_active ? 'active' : 'inactive',
            'status_label' => $user->is_active ? 'نشط' : 'موقوف',
            'registered_at' => optional($user->created_at)->format('Y-m-d H:i'),
            'last_login_at' => optional($user->last_login_at)->format('Y-m-d H:i') ?: '—',
            'type_detail' => $this->typeDetail($user, $type),
        ];
    }

    public function typeLabel(string $type): string
    {
        return match ($type) {
            'student' => 'طالب',
            'parent' => 'ولي أمر',
            'instructor' => 'معلم',
            'employee' => 'موظف',
            'admin' => 'إداري',
            default => 'مستخدم',
        };
    }

    public function classifyType(User $user): string
    {
        $role = (string) $user->role;

        // الأدوار الأربعة المطلوبة أولاً — حتى لو وُسم الحساب كموظف بالخطأ
        if ($role === 'student') {
            return 'student';
        }
        if ($role === 'parent') {
            return 'parent';
        }
        if (in_array($role, ['instructor', 'teacher'], true)) {
            return 'instructor';
        }
        if ($user->is_employee) {
            return 'employee';
        }

        return match (true) {
            in_array($role, ['admin', 'super_admin'], true) => 'admin',
            default => $role !== '' ? $role : 'other',
        };
    }

    public function normalizeType(mixed $type): string
    {
        $type = strtolower(trim((string) $type));

        return in_array($type, self::TYPES, true) ? $type : 'all';
    }

    protected function baseQuery(): Builder
    {
        return User::query();
    }

    /**
     * @return list<string|array>
     */
    protected function eagerLoads(string $type): array
    {
        $loads = [];

        if ($type === 'all' || $type === 'student') {
            $loads[] = 'academicYear:id,name';
            $loads[] = 'parent:id,name,email';
        }

        if ($type === 'all' || $type === 'parent') {
            $loads[] = 'children:id,name,parent_id';
        }

        if ($type === 'all' || $type === 'instructor') {
            $loads[] = 'instructorProfile:id,user_id,status';
        }

        if ($type === 'all' || $type === 'employee') {
            $loads[] = 'employeeJob:id,name,code';
        }

        return $loads;
    }

    protected function applyTypeFilter(Builder $query, string $type): void
    {
        match ($type) {
            'student' => $query->where('role', 'student'),
            'parent' => $this->parentScope($query),
            'instructor' => $query->whereIn('role', ['instructor', 'teacher']),
            'employee' => $query->where('is_employee', true),
            default => null,
        };
    }

    protected function parentScope(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('role', 'parent')
                ->orWhereHas('children');
        });
    }

    protected function applyListFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status') && in_array((string) $request->status, ['0', '1'], true)) {
            $query->where('is_active', (bool) (int) $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            if ($search !== '') {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }
        }
    }

    protected function typeDetail(User $user, string $type): string
    {
        return match ($type) {
            'student' => $this->studentDetail($user),
            'parent' => $this->parentDetail($user),
            'instructor' => $this->instructorDetail($user),
            'employee' => $this->employeeDetail($user),
            default => '—',
        };
    }

    protected function studentDetail(User $user): string
    {
        $parts = [];
        $year = $user->academicYear?->name;
        if ($year) {
            $parts[] = 'المرحلة: '.$year;
        }

        $track = trim((string) ($user->preferred_curriculum_type ?? ''));
        if ($track !== '') {
            $parts[] = 'المنهج: '.HesetakMatchCatalog::curriculumTypeLabel($track);
        }

        if ($user->parent) {
            $parts[] = 'ولي الأمر: '.$user->parent->name;
        } elseif ($user->parent_id) {
            $parts[] = 'ولي أمر مرتبط';
        } else {
            $parts[] = 'بلا ولي أمر';
        }

        return implode(' · ', $parts);
    }

    protected function parentDetail(User $user): string
    {
        if (! $user->relationLoaded('children')) {
            $user->load('children:id,name,parent_id');
        }

        $names = $user->children->pluck('name')->filter()->values();
        if ($names->isEmpty()) {
            return 'بلا أبناء مربوطين';
        }

        $shown = $names->take(3)->implode('، ');
        $extra = $names->count() - 3;

        return 'الأبناء: '.$shown.($extra > 0 ? ' (+'.$extra.')' : '');
    }

    protected function instructorDetail(User $user): string
    {
        $status = $user->instructorProfile?->status;
        $label = match ($status) {
            InstructorProfile::STATUS_APPROVED => 'معتمد',
            InstructorProfile::STATUS_PENDING_REVIEW => 'قيد المراجعة',
            InstructorProfile::STATUS_REJECTED => 'مرفوض',
            InstructorProfile::STATUS_DRAFT => 'مسودة',
            default => 'ناقص / بلا ملف',
        };

        return 'اعتماد الملف: '.$label;
    }

    protected function employeeDetail(User $user): string
    {
        if (! $user->relationLoaded('employeeJob')) {
            $user->load('employeeJob:id,name,code');
        }

        $job = $user->employeeJob?->name ?: 'بلا مسمى';
        $mode = $user->usesCustomEmployeePermissions() ? 'صلاحيات مخصصة' : 'صلاحيات افتراضية (الوظيفة)';

        return $job.' · '.$mode;
    }
}
