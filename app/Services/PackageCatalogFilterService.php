<?php

namespace App\Services;

use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\ServicePackage;
use App\Support\HesetakMatchCatalog;
use App\Support\StudentLearningProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PackageCatalogFilterService
{
    public function __construct(
        protected ServiceSessionRateService $rates,
    ) {}

    /**
     * @return array{
     *   years: Collection,
     *   subjects: Collection,
     *   tracks: array<string, array>,
     *   selected_year_id: int|null,
     *   selected_subject_id: int|null,
     *   selected_curriculum_type: string|null,
     *   packages: Collection<int, array>,
     *   viewer_locked: bool
     * }
     * @param  bool  $featuredOnly  الأكثر مبيعًا كما تُعلَّم من الإدارة.
     */
    public function catalog(
        ?int $yearId = null,
        ?int $subjectId = null,
        ?string $curriculumType = null,
        ?int $limit = null,
        bool $applyViewerDefaults = true,
        bool $featuredOnly = false,
    ): array {
        $tracks = HesetakMatchCatalog::curriculumTypes();
        $viewer = $applyViewerDefaults ? StudentLearningProfile::fromAuth() : new StudentLearningProfile;
        $viewerLocked = $viewer->hasPreferences();

        // الطالب المسجّل بمرحلة/منهج → افتراضي من ملفه ما لم يُمرَّر فلتر صريح في الطلب.
        if ($applyViewerDefaults) {
            if ($yearId === null && $viewer->hasStage()) {
                $yearId = $viewer->yearId;
            }
            if (($curriculumType === null || trim((string) $curriculumType) === '') && $viewer->hasCurriculum()) {
                $curriculumType = $viewer->curriculumType;
            }
        }

        $selectedTrack = $this->rates->normalizeCurriculumType($curriculumType);
        if ($selectedTrack === '' || $selectedTrack === null) {
            $selectedTrack = null;
        }

        $yearsQuery = Schema::hasTable('academic_years')
            ? AcademicYear::query()->publicCatalog()->ordered()
            : null;
        if ($yearsQuery && $selectedTrack) {
            $yearsQuery->forCurriculumType($selectedTrack);
        }
        $years = $yearsQuery
            ? $yearsQuery->get(['id', 'name', 'slug', 'level_number', 'curriculum_type'])
            : collect();

        if ($yearId && $years->isNotEmpty() && ! $years->contains('id', $yearId)) {
            $yearId = null;
        }

        // الزائر بدون فلتر: لا نفرض أول مرحلة — يظهر كل الباقات.
        // الطالب بمرحلة محفوظة: $yearId يكون مضبوطاً أعلاه.

        $subjects = $this->subjectsForYear($yearId, $selectedTrack);
        if ($subjectId && ! $subjects->contains('id', $subjectId)) {
            $subjectId = null;
        }

        $packages = ServicePackage::query()
            ->storefront()
            ->when($featuredOnly, fn ($q) => $q->where('is_featured', true))
            ->forSchoolProgram($yearId, $subjectId)
            ->when(
                $selectedTrack,
                function ($q) use ($selectedTrack) {
                    $q->where(function ($inner) use ($selectedTrack) {
                        $inner->whereNull('curriculum_type')
                            ->orWhere('curriculum_type', $selectedTrack);
                    });
                }
            )
            ->with(['academicYear:id,name', 'academicSubject:id,name'])
            ->get();

        $packages = $packages->reject(function (ServicePackage $pkg) {
            return \App\Support\PublicDemoContent::recordLooksLikeDemo([
                $pkg->name,
                $pkg->tagline,
                $pkg->description,
                $pkg->badge,
            ]);
        })->values();

        if ($limit) {
            $packages = $packages->take($limit)->values();
        }

        $priced = $packages->map(function (ServicePackage $pkg) use ($yearId, $selectedTrack) {
            $quote = $this->rates->quotePackage($pkg, $yearId, $selectedTrack);

            return [
                'id' => $pkg->id,
                'slug' => $pkg->slug,
                'name' => $pkg->name,
                'tagline' => $pkg->tagline,
                'badge' => $pkg->badge,
                'description' => $pkg->description,
                'units_count' => (int) $pkg->units_count,
                'session_minutes' => (int) $pkg->session_minutes,
                'plan_type' => $pkg->plan_type,
                'is_featured' => (bool) $pkg->is_featured,
                'features' => $pkg->featureList(),
                'gifts' => $pkg->giftList(),
                'checkout_url' => route('public.service-packages.checkout', $pkg),
                'gift_url' => route('public.gift-package.show', [
                    'package' => $pkg->id,
                    'year' => $yearId,
                    'curriculum_type' => $selectedTrack,
                    'subject' => $pkg->academic_subject_id,
                ]),
                'quote' => $quote,
                'display_price' => $quote['total'],
                'display_unit' => $quote['unit'],
                'currency' => $quote['currency'],
                'original_price' => $pkg->original_price ? (float) $pkg->original_price : null,
                'model' => $pkg,
            ];
        });

        return [
            'years' => $years,
            'subjects' => $subjects,
            'tracks' => $tracks,
            'selected_year_id' => $yearId,
            'selected_subject_id' => $subjectId,
            'selected_curriculum_type' => $selectedTrack,
            'packages' => $priced,
            'viewer_locked' => $viewerLocked,
        ];
    }

    /**
     * باقات الرئيسية / الأكثر مبيعًا: المميزة أولاً، وإن خلت نعرض أعلى الباقات النشطة.
     */
    public function homeShowcase(int $limit = 6): Collection
    {
        $featured = $this->catalog(
            yearId: null,
            subjectId: null,
            curriculumType: null,
            limit: $limit,
            applyViewerDefaults: false,
            featuredOnly: true,
        )['packages'];

        if ($featured->isNotEmpty()) {
            return $featured;
        }

        return $this->catalog(
            yearId: null,
            subjectId: null,
            curriculumType: null,
            limit: $limit,
            applyViewerDefaults: false,
            featuredOnly: false,
        )['packages'];
    }

    public function subjectsForYear(?int $yearId, ?string $curriculumType = null): Collection
    {
        if (! Schema::hasTable('academic_subjects')) {
            return collect();
        }

        $query = AcademicSubject::query()
            ->active()
            ->where(function ($q) use ($yearId) {
                $q->whereNull('academic_year_id');
                if ($yearId) {
                    $q->orWhere(function ($inner) use ($yearId) {
                        $inner->forYear($yearId);
                    });
                }
            });

        $track = $curriculumType !== null && trim((string) $curriculumType) !== ''
            ? $this->rates->normalizeCurriculumType($curriculumType)
            : null;
        if ($track) {
            $query->forCurriculumType($track);
        }

        return $query
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'academic_year_id', 'curriculum_type']);
    }
}
