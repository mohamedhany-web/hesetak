<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\TutoringGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TutoringGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin() && ! $user->hasPermission('manage.tutoring-groups'))) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request, string $type): View
    {
        $type = $this->assertType($type);

        $query = TutoringGroup::query()
            ->where('type', $type)
            ->with('instructor:id,name')
            ->withCount([
                'bookings as bookings_count',
                'bookings as pending_bookings_count' => fn ($q) => $q->where('status', 'pending'),
            ])
            ->orderBy('sort_order')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('slug', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $groups = $query->paginate(20)->withQueryString();

        $base = TutoringGroup::query()->where('type', $type);
        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
            'featured' => (clone $base)->where('is_featured', true)->count(),
        ];

        return view('admin.tutoring-groups.index', [
            'groups' => $groups,
            'stats' => $stats,
            'type' => $type,
            'typeLabel' => $this->typeLabel($type),
        ]);
    }

    public function create(string $type): View
    {
        $type = $this->assertType($type);

        return view('admin.tutoring-groups.form', [
            'group' => new TutoringGroup([
                'type' => $type,
                'capacity' => $type === TutoringGroup::TYPE_INDIVIDUAL ? 1 : 8,
                'duration_minutes' => 60,
                'currency' => platform_currency(),
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'instructors' => $this->instructors(),
            'schoolYears' => $this->schoolYears(),
            'schoolSubjects' => $this->schoolSubjects(),
            'type' => $type,
            'typeLabel' => $this->typeLabel($type),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $type = $this->assertType($type);
        $validated = $this->validateGroup($request, $type);

        if (empty($validated['slug'])) {
            $validated['slug'] = TutoringGroup::uniqueSlug($validated['title']);
        } else {
            $validated['slug'] = TutoringGroup::uniqueSlug($validated['slug']);
        }

        if ($type === TutoringGroup::TYPE_INDIVIDUAL) {
            $validated['capacity'] = 1;
        }

        $validated['type'] = $type;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        foreach (['academic_year_id', 'academic_subject_id'] as $fk) {
            if (empty($validated[$fk])) {
                $validated[$fk] = null;
            }
        }

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('tutoring-groups', 'public');
        }

        unset($validated['image']);
        TutoringGroup::create($validated);

        return redirect()
            ->route('admin.tutoring-groups.index', $type)
            ->with('success', 'تم إنشاء المجموعة بنجاح.');
    }

    public function edit(string $type, TutoringGroup $tutoringGroup): View
    {
        $type = $this->assertType($type);
        $this->assertGroupType($tutoringGroup, $type);

        return view('admin.tutoring-groups.form', [
            'group' => $tutoringGroup,
            'instructors' => $this->instructors(),
            'schoolYears' => $this->schoolYears(),
            'schoolSubjects' => $this->schoolSubjects(),
            'type' => $type,
            'typeLabel' => $this->typeLabel($type),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, string $type, TutoringGroup $tutoringGroup): RedirectResponse
    {
        $type = $this->assertType($type);
        $this->assertGroupType($tutoringGroup, $type);
        $validated = $this->validateGroup($request, $type, $tutoringGroup->id);

        if (empty($validated['slug'])) {
            $validated['slug'] = TutoringGroup::uniqueSlug($validated['title'], $tutoringGroup->id);
        } else {
            $validated['slug'] = TutoringGroup::uniqueSlug($validated['slug'], $tutoringGroup->id);
        }

        if ($type === TutoringGroup::TYPE_INDIVIDUAL) {
            $validated['capacity'] = 1;
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        foreach (['academic_year_id', 'academic_subject_id'] as $fk) {
            if (empty($validated[$fk])) {
                $validated[$fk] = null;
            }
        }

        if ($request->hasFile('image')) {
            if ($tutoringGroup->image_path && ! str_starts_with($tutoringGroup->image_path, 'http')) {
                Storage::disk('public')->delete($tutoringGroup->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('tutoring-groups', 'public');
        }

        unset($validated['image']);
        $tutoringGroup->update($validated);

        return redirect()
            ->route('admin.tutoring-groups.index', $type)
            ->with('success', 'تم تحديث المجموعة بنجاح.');
    }

    public function destroy(string $type, TutoringGroup $tutoringGroup): RedirectResponse
    {
        $type = $this->assertType($type);
        $this->assertGroupType($tutoringGroup, $type);

        if ($tutoringGroup->image_path && ! str_starts_with($tutoringGroup->image_path, 'http')) {
            Storage::disk('public')->delete($tutoringGroup->image_path);
        }

        $tutoringGroup->delete();

        return redirect()
            ->route('admin.tutoring-groups.index', $type)
            ->with('success', 'تم حذف المجموعة.');
    }

    public function toggleStatus(string $type, TutoringGroup $tutoringGroup): RedirectResponse
    {
        $type = $this->assertType($type);
        $this->assertGroupType($tutoringGroup, $type);
        $tutoringGroup->update(['is_active' => ! $tutoringGroup->is_active]);

        return back()->with('success', $tutoringGroup->is_active ? 'تم تفعيل المجموعة.' : 'تم إيقاف المجموعة.');
    }

    protected function validateGroup(Request $request, string $type, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'description' => ['nullable', 'string', 'max:20000'],
            'instructor_id' => ['required', 'exists:users,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'sessions_per_month' => ['nullable', 'integer', 'min:1', 'max:60'],
            'whatsapp_group_url' => ['nullable', 'url', 'max:500'],
            'learning_path' => ['nullable', 'in:arabic,english'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'academic_subject_id' => ['nullable', 'exists:academic_subjects,id'],
            'currency' => ['nullable', 'string', 'max:8'],
            'capacity' => [$type === TutoringGroup::TYPE_COLLECTIVE ? 'required' : 'nullable', 'integer', 'min:1', 'max:500'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:240'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ], [
            'title.required' => 'عنوان المجموعة مطلوب',
            'instructor_id.required' => 'اختر المدرب',
            'slug.regex' => 'الرابط يقبل أحرف إنجليزية صغيرة وأرقام وشرطة فقط',
        ]);
    }

    protected function assertType(string $type): string
    {
        abort_unless(in_array($type, [TutoringGroup::TYPE_INDIVIDUAL, TutoringGroup::TYPE_COLLECTIVE], true), 404);

        return $type;
    }

    protected function assertGroupType(TutoringGroup $group, string $type): void
    {
        abort_unless($group->type === $type, 404);
    }

    protected function typeLabel(string $type): string
    {
        return $type === TutoringGroup::TYPE_INDIVIDUAL ? 'المجموعات الفردية' : 'المجموعات الجماعية';
    }

    protected function instructors()
    {
        return User::query()
            ->whereIn('role', ['instructor', 'teacher', 'admin', 'super_admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    protected function schoolYears()
    {
        return AcademicYear::query()->ordered()->get(['id', 'name', 'level_number', 'code']);
    }

    protected function schoolSubjects()
    {
        return AcademicSubject::query()->ordered()->get(['id', 'name', 'code', 'academic_year_id']);
    }
}
