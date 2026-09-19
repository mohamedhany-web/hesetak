<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\ServicePackage;
use App\Models\TutoringGroup;
use App\Models\User;
use App\Services\StudentEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ServicePackageController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin() && ! $user->hasPermission('manage.packages') && ! $user->hasPermission('manage.tutoring-groups'))) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(): View
    {
        $packages = ServicePackage::query()
            ->with([
                'tutoringGroup:id,title',
                'academicYear:id,name',
                'academicSubject:id,name',
            ])
            ->withCount('entitlements')
            ->ordered()
            ->get();

        $stats = [
            'total' => $packages->count(),
            'active' => $packages->where('is_active', true)->count(),
            'global' => $packages->where('scope', ServicePackage::SCOPE_GLOBAL)->count(),
            'sold' => $packages->sum('entitlements_count'),
        ];

        return view('admin.service-packages.index', compact('packages', 'stats'));
    }

    public function create(): View
    {
        return view('admin.service-packages.form', $this->formData(new ServicePackage([
            'scope' => ServicePackage::SCOPE_GLOBAL,
            'units_count' => 8,
            'session_minutes' => 60,
            'duration_days' => 60,
            'currency' => (string) config('currency.code', 'SAR'),
            'is_active' => true,
            'sort_order' => (int) ServicePackage::query()->max('sort_order') + 1,
        ]), 'create'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data = $this->normalizeSchoolLinks($data);
        $data = $this->normalizeCommercialPlan($data, $request);
        $data['currency'] = $this->normalizeCurrency($data['currency'] ?? null);
        $data['slug'] = $data['slug'] ?: ServicePackage::uniqueSlug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');

        ServicePackage::create($data);
        $this->bustStorefrontCache();

        return redirect()->route('admin.service-packages.index')->with('success', 'تم إنشاء باقة الخدمات.');
    }

    public function edit(ServicePackage $servicePackage): View
    {
        return view('admin.service-packages.form', $this->formData($servicePackage, 'edit'));
    }

    public function update(Request $request, ServicePackage $servicePackage): RedirectResponse
    {
        $data = $this->validated($request, $servicePackage->id);
        $data = $this->normalizeSchoolLinks($data);
        $data = $this->normalizeCommercialPlan($data, $request);
        $data['currency'] = $this->normalizeCurrency($data['currency'] ?? null);
        $data['slug'] = $data['slug']
            ? ServicePackage::uniqueSlug($data['slug'], $servicePackage->id)
            : ServicePackage::uniqueSlug($data['name'], $servicePackage->id);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        $servicePackage->update($data);
        $this->bustStorefrontCache();

        return redirect()->route('admin.service-packages.index')->with('success', 'تم تحديث الباقة.');
    }

    public function destroy(ServicePackage $servicePackage): RedirectResponse
    {
        if ($servicePackage->entitlements()->exists()) {
            return back()->with('error', 'لا يمكن حذف باقة لها أرصدة طلاب. أوقفها بدل الحذف.');
        }
        $servicePackage->delete();
        $this->bustStorefrontCache();

        return redirect()->route('admin.service-packages.index')->with('success', 'تم حذف الباقة.');
    }

    public function toggleStatus(ServicePackage $servicePackage): RedirectResponse
    {
        $servicePackage->update(['is_active' => ! $servicePackage->is_active]);
        $this->bustStorefrontCache();

        return back()->with('success', $servicePackage->is_active ? 'تم تفعيل الباقة.' : 'تم إيقاف الباقة.');
    }

    public function grantForm(Request $request): View
    {
        $packages = ServicePackage::query()
            ->active()
            ->ordered()
            ->get([
                'id', 'name', 'scope', 'plan_type', 'term_months',
                'units_count', 'weekly_group_sessions', 'weekly_private_sessions',
                'duration_days', 'price', 'currency', 'badge',
            ]);

        return view('admin.service-packages.grant', [
            'packages' => $packages,
            'students' => User::query()
                ->where('role', 'student')
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(1000)
                ->get(['id', 'name', 'email', 'phone']),
            'selectedUserId' => (int) $request->integer('user_id'),
            'selectedPackageId' => (int) $request->integer('service_package_id'),
            'placementUrl' => \Illuminate\Support\Facades\Route::has('admin.placement.create')
                ? route('admin.placement.create')
                : null,
        ]);
    }

    public function grantStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'service_package_id' => ['required', 'exists:service_packages,id'],
            'units_override' => ['nullable', 'integer', 'min:1', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $student = User::query()->findOrFail($data['user_id']);
        if (! $student->isStudent()) {
            return back()->withInput()->with('error', 'المستخدم المحدد ليس طالباً.');
        }

        $package = ServicePackage::query()->findOrFail($data['service_package_id']);
        $notes = trim((string) ($data['notes'] ?? ''));
        if ($notes === '') {
            $notes = 'منح يدوي من لوحة الباقات بواسطة '.($request->user()?->name ?? 'admin');
        } else {
            $notes = 'منح يدوي: '.$notes;
        }

        $entitlement = StudentEntitlementService::grant(
            userId: (int) $student->id,
            package: $package,
            orderId: null,
            unitsOverride: isset($data['units_override']) ? (int) $data['units_override'] : null,
            notes: $notes,
        );

        $message = 'تم تنزيل باقة «'.$package->name.'» على حساب '.$student->name.' (رصيد #'.$entitlement->id.').';

        if ($request->boolean('go_placement') && \Illuminate\Support\Facades\Route::has('admin.placement.create')) {
            return redirect()
                ->route('admin.placement.create', [
                    'user_id' => $student->id,
                    'entitlement_id' => $entitlement->id,
                ])
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.student-entitlements.index', ['search' => $student->email ?: $student->name])
            ->with('success', $message);
    }

    protected function formData(ServicePackage $package, string $mode): array
    {
        return [
            'package' => $package,
            'groups' => TutoringGroup::query()->orderBy('title')->get(['id', 'title', 'type', 'academic_year_id', 'academic_subject_id']),
            'years' => Schema::hasTable('academic_years')
                ? AcademicYear::query()->ordered()->get(['id', 'name', 'level_number'])
                : collect(),
            'subjects' => Schema::hasTable('academic_subjects')
                ? AcademicSubject::query()->ordered()->get(['id', 'name', 'academic_year_id'])
                : collect(),
            'mode' => $mode,
        ];
    }

    protected function normalizeSchoolLinks(array $data): array
    {
        foreach (['tutoring_group_id', 'academic_year_id', 'academic_subject_id'] as $key) {
            if (empty($data[$key])) {
                $data[$key] = null;
            } else {
                $data[$key] = (int) $data[$key];
            }
        }

        if ($data['tutoring_group_id']) {
            $group = TutoringGroup::query()->find($data['tutoring_group_id']);
            if ($group) {
                $data['academic_year_id'] = $data['academic_year_id'] ?: $group->academic_year_id;
                $data['academic_subject_id'] = $data['academic_subject_id'] ?: $group->academic_subject_id;
            }
        }

        if ($data['academic_subject_id'] && ! $data['academic_year_id']) {
            $subject = AcademicSubject::query()->find($data['academic_subject_id']);
            if ($subject?->academic_year_id) {
                $data['academic_year_id'] = (int) $subject->academic_year_id;
            }
        }

        return $data;
    }

    protected function normalizeCommercialPlan(array $data, Request $request): array
    {
        foreach (['plan_type', 'term_months'] as $key) {
            if (empty($data[$key])) {
                $data[$key] = null;
            }
        }

        $data['weekly_group_sessions'] = (int) ($data['weekly_group_sessions'] ?? 0);
        $data['weekly_private_sessions'] = (int) ($data['weekly_private_sessions'] ?? 0);
        $data['includes_community'] = $request->boolean('includes_community');
        $data['includes_libraries'] = $request->boolean('includes_libraries');

        if (! empty($data['features_text'])) {
            $data['features'] = collect(preg_split('/\r\n|\r|\n/', (string) $data['features_text']))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->values()
                ->all();
        } else {
            $data['features'] = null;
        }
        unset($data['features_text']);

        if (! empty($data['gifts_text'])) {
            $data['gifts'] = collect(preg_split('/\r\n|\r|\n/', (string) $data['gifts_text']))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->values()
                ->all();
        } else {
            $data['gifts'] = null;
        }
        unset($data['gifts_text']);

        if ($data['plan_type'] && $data['term_months']) {
            $weekly = $data['weekly_group_sessions'] + $data['weekly_private_sessions'];
            if ($weekly > 0) {
                $data['units_count'] = $weekly * 4 * (int) $data['term_months'];
            }
            if (empty($data['duration_days'])) {
                $data['duration_days'] = (int) $data['term_months'] * 30;
            }

            $data['scope'] = match ($data['plan_type']) {
                ServicePackage::PLAN_SCHOOL => ServicePackage::SCOPE_TUTORING_COLLECTIVE,
                ServicePackage::PLAN_PRIVATE => ServicePackage::SCOPE_PRIVATE_LESSONS,
                ServicePackage::PLAN_PREMIER => ServicePackage::SCOPE_GLOBAL,
                default => $data['scope'],
            };
        }

        return $data;
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:5000'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:64'],
            'scope' => ['required', 'in:global,tutoring_individual,tutoring_collective,private_lessons'],
            'plan_type' => ['nullable', 'in:school,private,premier'],
            'term_months' => ['nullable', 'integer', 'in:1,3,6'],
            'weekly_group_sessions' => ['nullable', 'integer', 'min:0', 'max:14'],
            'weekly_private_sessions' => ['nullable', 'integer', 'min:0', 'max:14'],
            'features_text' => ['nullable', 'string', 'max:5000'],
            'gifts_text' => ['nullable', 'string', 'max:2000'],
            'tutoring_group_id' => ['nullable', 'exists:tutoring_groups,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'academic_subject_id' => ['nullable', 'exists:academic_subjects,id'],
            'units_count' => ['required', 'integer', 'min:1', 'max:500'],
            'session_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:730'],
            'price' => ['required', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'in:SAR,USD,EGP,sar,usd,egp'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    protected function normalizeCurrency(?string $currency): string
    {
        $code = strtoupper(trim((string) ($currency ?: platform_currency())));

        return in_array($code, ['SAR', 'USD', 'EGP'], true) ? $code : platform_currency();
    }

    private function bustStorefrontCache(): void
    {
        foreach (['ar', 'en'] as $locale) {
            Cache::forget('landing.home.v17.'.$locale);
            Cache::forget('landing.home.v16.'.$locale);
            Cache::forget('landing.home.v13.'.$locale);
        }
    }
}
