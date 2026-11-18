<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\ServicePackage;
use App\Services\PricingStorefrontCatalog;
use App\Services\PublicMediaStorage;
use App\Support\HesetakMatchCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * إدارة بطاقات صفحة /pricing كما يراها الزائر (مادة × مرحلة × منهج).
 */
class PricingStorefrontController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin() && ! $user->hasPermission('manage.packages'))) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request, PricingStorefrontCatalog $catalog): View
    {
        $curriculum = trim((string) $request->query('curriculum', ''));
        $yearId = $request->filled('year') ? $request->integer('year') : null;

        $built = $catalog->build(
            yearId: $yearId,
            subjectId: null,
            curriculumType: $curriculum !== '' ? $curriculum : null,
            subjectIds: [],
        );

        $rows = collect($built['cards'] ?? [])->map(function (array $card) use ($built) {
            $subjectId = null;
            if (str_starts_with((string) ($card['key'] ?? ''), 'sub-')) {
                $subjectId = (int) str_replace('sub-', '', (string) $card['key']);
            }
            $packageId = isset($card['package_id']) ? (int) $card['package_id'] : null;
            $options = $card['price_options'] ?? [];

            return [
                'subject_id' => $subjectId,
                'package_id' => $packageId,
                'title' => (string) ($card['title'] ?? ''),
                'description' => (string) ($card['description'] ?? ''),
                'linked' => $packageId > 0 && $subjectId > 0
                    && ServicePackage::query()
                        ->where('id', $packageId)
                        ->where(function ($q) use ($subjectId) {
                            $q->where('academic_subject_id', $subjectId);
                            if (Schema::hasTable('service_package_subjects')) {
                                $q->orWhereHas('subjects', fn ($s) => $s->where('academic_subjects.id', $subjectId));
                            }
                        })
                        ->exists(),
                'options' => $options,
                'cover' => $card['cover_image_url'] ?? null,
                'badge' => $card['badge'] ?? null,
                'year_id' => $built['catalog']['selected_year_id'] ?? null,
                'curriculum' => $built['catalog']['selected_curriculum_type'] ?? null,
            ];
        })->values();

        $years = Schema::hasTable('academic_years')
            ? AcademicYear::query()->publicCatalog()->ordered()->get(['id', 'name', 'curriculum_type'])
            : collect();

        return view('admin.pricing-storefront.index', [
            'rows' => $rows,
            'years' => $years,
            'tracks' => HesetakMatchCatalog::curriculumTypes(),
            'selectedYearId' => $built['catalog']['selected_year_id'] ?? null,
            'selectedCurriculum' => $built['catalog']['selected_curriculum_type'] ?? $curriculum,
            'selectedYearName' => optional($years->firstWhere('id', $built['catalog']['selected_year_id'] ?? null))->name,
            'pricingUrl' => route('public.pricing', array_filter([
                'curriculum_type' => $built['catalog']['selected_curriculum_type'] ?? null,
                'year' => $built['catalog']['selected_year_id'] ?? null,
            ])),
        ]);
    }

    public function edit(Request $request, int $subject): View
    {
        $subjectModel = AcademicSubject::query()->findOrFail($subject);
        $yearId = $request->filled('year')
            ? $request->integer('year')
            : (int) ($subjectModel->academic_year_id ?: 0);
        $yearId = $yearId > 0 ? $yearId : null;

        $package = app(PricingStorefrontCatalog::class)->storefrontPackageForSubject((int) $subjectModel->id, $yearId);

        return view('admin.pricing-storefront.edit', [
            'subject' => $subjectModel,
            'yearId' => $yearId,
            'year' => $yearId ? AcademicYear::query()->find($yearId) : null,
            'package' => $package,
            'curriculum' => trim((string) $request->query('curriculum', $subjectModel->curriculum_type ?? '')),
        ]);
    }

    public function update(Request $request, int $subject): RedirectResponse
    {
        $subjectModel = AcademicSubject::query()->findOrFail($subject);
        $yearId = $request->filled('year')
            ? $request->integer('year')
            : (int) ($subjectModel->academic_year_id ?: 0);
        $yearId = $yearId > 0 ? $yearId : null;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'badge' => ['nullable', 'string', 'max:80'],
            'features_text' => ['nullable', 'string', 'max:5000'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'units_count' => ['required', 'integer', 'min:1', 'max:500'],
            'session_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:730'],
            'unit_price_small' => ['nullable', 'numeric', 'min:0'],
            'unit_price_bulk' => ['nullable', 'numeric', 'min:0'],
            'unit_price_urgent' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'show_in_storefront' => ['nullable', 'boolean'],
            'subject_visible' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'max:'.config('upload_limits.max_upload_kb', 5120)],
            'remove_cover_image' => ['nullable', 'boolean'],
            'year' => ['nullable', 'integer'],
            'curriculum' => ['nullable', 'string', 'max:40'],
        ]);

        $features = collect(preg_split('/\r\n|\r|\n/', (string) ($data['features_text'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $package = app(PricingStorefrontCatalog::class)->storefrontPackageForSubject((int) $subjectModel->id, $yearId);

        $unitsCount = (int) $data['units_count'];
        $unitPrice = round((float) $data['unit_price'], 2);
        // السعر مختلف لكل باقة: الإجمالي = سعر الحصة × عدد الحصص
        $packagePrice = round($unitPrice * $unitsCount, 2);
        if (isset($data['price']) && is_numeric($data['price']) && (float) $data['price'] > 0 && $unitPrice <= 0) {
            $packagePrice = round((float) $data['price'], 2);
        }

        $payload = [
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
            'badge' => $data['badge'] ?? null,
            'features' => $features !== [] ? $features : null,
            'price' => $packagePrice,
            'units_count' => $unitsCount,
            'session_minutes' => (int) $data['session_minutes'],
            'duration_days' => (int) ($data['duration_days'] ?? 60),
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'plan_type' => ServicePackage::PLAN_PRIVATE,
            'academic_year_id' => $yearId,
            'academic_subject_id' => (int) $subjectModel->id,
            'curriculum_type' => $data['curriculum'] ?: ($subjectModel->curriculum_type ?: null),
            'currency' => (string) config('currency.code', 'SAR'),
            'is_active' => $request->boolean('is_active'),
        ];
        if (Schema::hasColumn('service_packages', 'unit_price_small')) {
            foreach (['unit_price_small', 'unit_price_bulk', 'unit_price_urgent'] as $tierColumn) {
                $tierValue = $data[$tierColumn] ?? null;
                $payload[$tierColumn] = is_numeric($tierValue) && (float) $tierValue > 0
                    ? round((float) $tierValue, 2)
                    : null;
            }
        }
        if (Schema::hasColumn('service_packages', 'show_in_storefront')) {
            $payload['show_in_storefront'] = $request->boolean('show_in_storefront');
        }

        if ($package) {
            $package->update($payload);
        } else {
            $payload['slug'] = ServicePackage::uniqueSlug($payload['name']);
            $payload['sort_order'] = (int) ServicePackage::query()->max('sort_order') + 1;
            $package = ServicePackage::create($payload);
        }

        $package->syncSubjectLinks([(int) $subjectModel->id]);

        if ($request->boolean('remove_cover_image') && $package->cover_image_path) {
            PublicMediaStorage::delete($package->cover_image_path);
            $package->update(['cover_image_path' => null]);
        }
        if ($request->hasFile('cover_image')) {
            $path = PublicMediaStorage::store($request->file('cover_image'), 'service-packages', $package->cover_image_path);
            $package->update(['cover_image_path' => $path]);
        }

        if (Schema::hasColumn('academic_subjects', 'is_active')) {
            $subjectModel->update(['is_active' => $request->boolean('subject_visible')]);
        }

        $this->bustStorefrontCache();

        return redirect()
            ->route('admin.pricing-storefront.index', array_filter([
                'year' => $yearId,
                'curriculum' => $payload['curriculum_type'],
            ]))
            ->with('success', 'تم حفظ بطاقة التسعير «'.$package->name.'» وستظهر على /pricing.');
    }

    protected function bustStorefrontCache(): void
    {
        Cache::forget('pricing_storefront_catalog_v1');
        try {
            Cache::flush();
        } catch (\Throwable) {
            // ignore
        }
    }
}
