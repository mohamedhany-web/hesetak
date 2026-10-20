<?php

namespace App\Services;

use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\ServicePackage;
use App\Models\ServicePackagePricingRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * كتالوج واجهة الباقات العامة: مراحل + مواد + بطاقات بخيارات تسعير (2 / 8 / مستعجلة).
 */
class PricingStorefrontCatalog
{
    public const BULK_SESSIONS = 8;

    public const SMALL_SESSIONS = 2;

    public function __construct(
        protected PackageCatalogFilterService $catalogFilter,
        protected ServiceSessionRateService $rates,
    ) {}

    /**
     * @return array{
     *   catalog: array,
     *   stages: Collection,
     *   subjects: Collection,
     *   cards: list<array>,
     *   price_options_template: list<array>,
     *   custom_builder_url: string,
     *   private_rule: ?ServicePackagePricingRule,
     *   urgent_rule: ?ServicePackagePricingRule
     * }
     */
    public function build(?int $yearId, ?int $subjectId, ?string $curriculumType): array
    {
        $catalog = $this->catalogFilter->catalog(
            yearId: $yearId,
            subjectId: $subjectId,
            curriculumType: $curriculumType,
            applyViewerDefaults: true,
        );

        $stages = $catalog['years'];
        if ($stages->isNotEmpty() && empty($catalog['selected_year_id'])) {
            $preferred = $stages->first(fn ($y) => in_array((string) ($y->slug ?? ''), ['primary', 'almrhl-alabtdaeyh', 'elementary'], true))
                ?: $stages->first();
            $catalog = $this->catalogFilter->catalog(
                yearId: (int) $preferred->id,
                subjectId: $subjectId,
                curriculumType: $curriculumType,
                applyViewerDefaults: false,
            );
            $stages = $catalog['years'];
        }

        // مواد المرحلة فقط (لا تخلط مواد عامة/تطوير معلمين غير مربوطة)
        $catalog['subjects'] = $this->subjectsForStage(
            $catalog['selected_year_id'] ? (int) $catalog['selected_year_id'] : null
        );
        if ($subjectId && ! $catalog['subjects']->contains('id', $subjectId)) {
            $catalog['selected_subject_id'] = null;
        } else {
            $catalog['selected_subject_id'] = $subjectId;
        }

        $standardRule = $this->standardRule();
        $urgentRule = $this->urgentRule();
        $privateRule = ServicePackagePricingRule::query()
            ->active()
            ->where('kind', 'standard')
            ->whereIn('scope', [ServicePackage::SCOPE_PRIVATE_LESSONS, ServicePackage::SCOPE_TUTORING_INDIVIDUAL])
            ->ordered()
            ->first();

        $unit = $this->unitPriceFor(
            $catalog['selected_year_id'] ? (int) $catalog['selected_year_id'] : null,
            $catalog['selected_curriculum_type'] ?? null,
            $standardRule,
        );

        $priceOptionsTemplate = $this->buildPriceOptions($unit, $standardRule, $urgentRule, null);

        $cards = $this->buildCards($catalog, $priceOptionsTemplate, $standardRule, $urgentRule);

        return [
            'catalog' => $catalog,
            'stages' => $stages,
            'subjects' => $catalog['subjects'],
            'cards' => $cards,
            'price_options_template' => $priceOptionsTemplate,
            'custom_builder_url' => route('public.service-packages.index', array_filter([
                'catalog' => 1,
                'year' => $catalog['selected_year_id'],
                'subject' => $catalog['selected_subject_id'],
            ])).'#private-builder',
            'private_rule' => $privateRule,
            'urgent_rule' => $urgentRule,
        ];
    }

    protected function subjectsForStage(?int $yearId): Collection
    {
        if (! $yearId || ! Schema::hasTable('academic_subjects')) {
            return collect();
        }

        return AcademicSubject::query()
            ->active()
            ->where('academic_year_id', $yearId)
            ->ordered()
            ->get(['id', 'name', 'slug', 'academic_year_id', 'icon']);
    }

    protected function standardRule(): ?ServicePackagePricingRule
    {
        return ServicePackagePricingRule::query()
            ->active()
            ->where(function ($q) {
                $q->where('kind', 'standard')->orWhereNull('kind');
            })
            ->whereIn('scope', [
                ServicePackage::SCOPE_TUTORING_INDIVIDUAL,
                ServicePackage::SCOPE_PRIVATE_LESSONS,
            ])
            ->ordered()
            ->first();
    }

    protected function urgentRule(): ?ServicePackagePricingRule
    {
        return ServicePackagePricingRule::query()
            ->active()
            ->where('kind', 'urgent')
            ->ordered()
            ->first();
    }

    protected function unitPriceFor(?int $yearId, ?string $track, ?ServicePackagePricingRule $rule): float
    {
        $rate = $this->rates->rateFor($yearId, $track ?: 'saudi', platform_currency());
        if ($rate !== null) {
            return $this->niceMoney((float) $rate['price_per_session']);
        }

        return $this->niceMoney((float) ($rule?->price_per_session ?? 0));
    }

    /** Round storefront prices to whole, customer-friendly amounts (no 31/47-style fractions). */
    protected function niceMoney(float $amount): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        if ($amount < 20) {
            return (float) max(1, (int) round($amount));
        }

        return (float) (max(5, (int) (round($amount / 5) * 5)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildPriceOptions(
        float $unit,
        ?ServicePackagePricingRule $standard,
        ?ServicePackagePricingRule $urgent,
        ?ServicePackage $package,
        ?int $yearId = null,
        ?int $subjectId = null,
    ): array {
        $currency = platform_currency();
        $options = [];

        $bulkSessions = self::BULK_SESSIONS;
        $smallSessions = self::SMALL_SESSIONS;

        if ($standard) {
            $bulkSessions = $this->nearestAllowedSessions($standard, self::BULK_SESSIONS);
            $smallSessions = $this->nearestAllowedSessions($standard, self::SMALL_SESSIONS);
        }

        $options[] = $this->optionRow(
            kind: 'bulk',
            sessions: $bulkSessions,
            unit: $this->unitAfterDiscount($unit, $bulkSessions, $standard),
            currency: $currency,
            package: $package,
            yearId: $yearId,
            subjectId: $subjectId,
        );

        $options[] = $this->optionRow(
            kind: 'small',
            sessions: $smallSessions,
            unit: $this->unitAfterDiscount($unit, $smallSessions, $standard),
            currency: $currency,
            package: $package,
            yearId: $yearId,
            subjectId: $subjectId,
        );

        if ($urgent) {
            $urgentUnit = $this->niceMoney((float) $urgent->price_per_session);
            $premiumFloor = $this->niceMoney(max($unit, 1) * 1.2);
            if ($urgentUnit < $premiumFloor) {
                $urgentUnit = $premiumFloor;
            }
            $options[] = $this->optionRow(
                kind: 'urgent',
                sessions: 1,
                unit: $urgentUnit,
                currency: $currency,
                package: $package,
                yearId: $yearId,
                subjectId: $subjectId,
            );
        }

        usort($options, static fn (array $a, array $b) => ((int) ($b['sessions'] ?? 0)) <=> ((int) ($a['sessions'] ?? 0)));

        return $options;
    }

    protected function nearestAllowedSessions(ServicePackagePricingRule $rule, int $desired): int
    {
        $min = max(1, (int) $rule->min_sessions);
        $max = max($min, (int) $rule->max_sessions);
        $step = max(1, (int) $rule->session_step);
        $desired = max($min, min($max, $desired));
        $offset = $desired - $min;
        $snapped = $min + (int) (round($offset / $step) * $step);

        return max($min, min($max, $snapped));
    }

    protected function unitAfterDiscount(float $unit, int $sessions, ?ServicePackagePricingRule $rule): float
    {
        if (! $rule || $unit <= 0) {
            return $unit;
        }

        $percent = 0.0;
        foreach ($rule->discount_tiers ?? [] as $tier) {
            $minimum = (int) ($tier['min_sessions'] ?? 0);
            $p = min(100, max(0, (float) ($tier['discount_percent'] ?? 0)));
            if ($sessions >= $minimum && $minimum > 0) {
                $percent = max($percent, $p);
            }
        }

        return $this->niceMoney($unit * (1 - ($percent / 100)));
    }

    /**
     * @return array<string, mixed>
     */
    protected function optionRow(
        string $kind,
        int $sessions,
        float $unit,
        string $currency,
        ?ServicePackage $package,
        ?int $yearId = null,
        ?int $subjectId = null,
    ): array {
        $isRtl = app()->getLocale() === 'ar';
        $label = match ($kind) {
            'urgent' => $isRtl ? 'حصة مستعجلة اليوم' : 'Urgent lesson today',
            'bulk' => $isRtl ? $sessions.' حصص' : $sessions.' lessons',
            default => $isRtl ? $sessions.' حصص' : $sessions.' lessons',
        };

        $href = $this->optionHref($kind, $sessions, $package, $yearId, $subjectId);

        return [
            'kind' => $kind,
            'sessions' => $sessions,
            'label' => $label,
            'unit_price' => $unit,
            'unit_label' => $isRtl
                ? 'سعر الحصة '.number_format($unit, 0).' '.currency_symbol()
                : number_format($unit, 0).' '.currency_symbol().' / lesson',
            'total' => $this->niceMoney($unit * $sessions),
            'currency' => $currency,
            'href' => $href,
            'is_urgent' => $kind === 'urgent',
        ];
    }

    protected function optionHref(
        string $kind,
        int $sessions,
        ?ServicePackage $package,
        ?int $yearId = null,
        ?int $subjectId = null,
    ): string {
        $match = $this->checkoutPackageForSessions($sessions, $package, $yearId, $subjectId);
        if ($match) {
            $query = array_filter([
                'year' => $yearId,
                'subject' => $subjectId,
            ]);

            return route('public.service-packages.checkout', $match).($query !== [] ? '?'.http_build_query($query) : '');
        }

        return route('public.service-packages.index', array_filter([
            'catalog' => 1,
            'year' => $yearId,
            'subject' => $subjectId,
        ])).'#private-builder';
    }

    protected function checkoutPackageForSessions(
        int $sessions,
        ?ServicePackage $preferred,
        ?int $yearId,
        ?int $subjectId,
    ): ?ServicePackage {
        if ($preferred && (int) $preferred->units_count === $sessions) {
            return $preferred;
        }

        $query = ServicePackage::query()
            ->storefront()
            ->where('units_count', $sessions);

        if ($subjectId) {
            $query->where(function ($q) use ($subjectId) {
                $q->where('academic_subject_id', $subjectId)->orWhereNull('academic_subject_id');
            });
        }
        if ($yearId) {
            $query->where(function ($q) use ($yearId) {
                $q->where('academic_year_id', $yearId)->orWhereNull('academic_year_id');
            });
        }

        return $query
            ->orderByRaw('CASE WHEN academic_subject_id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN academic_year_id IS NULL THEN 1 ELSE 0 END')
            ->ordered()
            ->first();
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @param  list<array<string, mixed>>  $priceOptionsTemplate
     * @return list<array<string, mixed>>
     */
    protected function buildCards(
        array $catalog,
        array $priceOptionsTemplate,
        ?ServicePackagePricingRule $standard,
        ?ServicePackagePricingRule $urgent,
    ): array {
        $isRtl = app()->getLocale() === 'ar';
        $packages = $catalog['packages'] ?? collect();
        $subjects = $catalog['subjects'] ?? collect();
        $yearId = $catalog['selected_year_id'] ? (int) $catalog['selected_year_id'] : null;
        $track = $catalog['selected_curriculum_type'] ?? null;
        $selSubject = $catalog['selected_subject_id'] ? (int) $catalog['selected_subject_id'] : null;

        $cards = [];

        // طريقة معلمي: بطاقة لكل مادة تحت المرحلة المختارة
        if ($subjects->isNotEmpty()) {
            $subjectList = $selSubject
                ? $subjects->where('id', $selSubject)->values()
                : $subjects->sortBy(fn ($s) => mb_strtolower((string) $s->name), SORT_NATURAL)->values();

            foreach ($subjectList as $subject) {
                $cards[] = $this->cardFromSubject($subject, $yearId, $track, $standard, $urgent, $isRtl);
            }

            return $cards;
        }

        // سقوط: باقات عامة إن لم توجد مواد للمرحلة
        foreach ($packages as $row) {
            $model = $row['model'] ?? null;
            if (! $model instanceof ServicePackage) {
                continue;
            }
            $unit = (float) ($row['display_unit'] ?? 0);
            if ($unit <= 0) {
                $unit = $this->unitPriceFor($yearId, $track, $standard);
            }
            $cards[] = $this->cardFromPackage($row, $model, $this->buildPriceOptions($unit, $standard, $urgent, $model));
        }

        return $cards;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<array<string, mixed>>  $options
     * @return array<string, mixed>
     */
    protected function cardFromPackage(array $row, ServicePackage $model, array $options): array
    {
        $isRtl = app()->getLocale() === 'ar';
        $features = array_values(array_filter($row['features'] ?? []));
        if ($features === []) {
            $features = $this->defaultFeatures((int) ($row['session_minutes'] ?? 60), $isRtl);
        }

        $desc = trim((string) ($row['tagline'] ?? $row['description'] ?? ''));
        $desc = Str::limit(strip_tags($desc), 120);

        return [
            'key' => 'pkg-'.$model->id,
            'title' => (string) $row['name'],
            'description' => $desc,
            'features' => array_slice($features, 0, 5),
            'devices' => $this->deviceTags($isRtl),
            'tone' => abs(crc32((string) $row['name'])) % 5,
            'icon' => $this->iconForName((string) $row['name']),
            'price_options' => $options,
            'checkout_url' => $row['checkout_url'] ?? route('public.service-packages.checkout', $model),
            'badge' => $row['badge'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function cardFromSubject(
        AcademicSubject $subject,
        ?int $yearId,
        ?string $track,
        ?ServicePackagePricingRule $standard,
        ?ServicePackagePricingRule $urgent,
        bool $isRtl,
    ): array {
        $unit = $this->unitPriceFor($yearId, $track, $standard);
        $yearName = $yearId && Schema::hasTable('academic_years')
            ? (AcademicYear::query()->find($yearId)?->name ?? '')
            : '';

        $linkedPackage = $this->packageForSubject($subject->id, $yearId);
        $options = $this->buildPriceOptions($unit, $standard, $urgent, $linkedPackage, $yearId, (int) $subject->id);

        if ($linkedPackage) {
            $title = $linkedPackage->name !== ''
                ? $linkedPackage->name
                : ($isRtl ? 'باقة '.$subject->name : $subject->name.' package');
            $desc = trim((string) ($linkedPackage->tagline ?: $linkedPackage->description ?: ''));
            if ($desc === '') {
                $desc = $isRtl
                    ? trim('معلم لمادة '.$subject->name.($yearName !== '' ? ' في '.$yearName : ''))
                    : trim('A tutor for '.$subject->name.($yearName !== '' ? ' · '.$yearName : ''));
            }
            $features = $linkedPackage->featureList();
            if ($features === []) {
                $features = $this->defaultFeatures((int) ($linkedPackage->session_minutes ?: 60), $isRtl);
            }

            return [
                'key' => 'sub-'.$subject->id,
                'title' => $title,
                'description' => Str::limit(strip_tags($desc), 120),
                'features' => array_slice($features, 0, 5),
                'devices' => $this->deviceTags($isRtl),
                'tone' => abs(crc32($subject->name)) % 5,
                'icon' => $subject->faIcon() ?: $this->iconForName($subject->name),
                'price_options' => $options,
                'checkout_url' => route('public.service-packages.checkout', $linkedPackage),
                'badge' => $linkedPackage->badge,
                'package_id' => $linkedPackage->id,
            ];
        }

        $title = $isRtl ? 'باقة '.$subject->name : $subject->name.' package';
        $description = $isRtl
            ? trim('معلم أو معلمة لمادة '.$subject->name.($yearName !== '' ? ' لجميع صفوف '.$yearName : ''))
            : trim('A tutor for '.$subject->name.($yearName !== '' ? ' · '.$yearName : ''));

        return [
            'key' => 'sub-'.$subject->id,
            'title' => $title,
            'description' => Str::limit($description, 120),
            'features' => $this->defaultFeatures(60, $isRtl),
            'devices' => $this->deviceTags($isRtl),
            'tone' => abs(crc32($subject->name)) % 5,
            'icon' => $subject->faIcon() ?: $this->iconForName($subject->name),
            'price_options' => $options,
            'checkout_url' => route('public.service-packages.index', array_filter([
                'catalog' => 1,
                'year' => $yearId,
                'subject' => $subject->id,
            ])).'#private-builder',
            'badge' => null,
            'package_id' => null,
        ];
    }

    protected function packageForSubject(int $subjectId, ?int $yearId): ?ServicePackage
    {
        $query = ServicePackage::query()
            ->storefront()
            ->where('academic_subject_id', $subjectId);

        if ($yearId) {
            $query->where(function ($inner) use ($yearId) {
                $inner->whereNull('academic_year_id')->orWhere('academic_year_id', $yearId);
            });
            // مرحلة مطابقة أولاً، ثم باقة عامة للمادة
            $query->orderByRaw('CASE WHEN academic_year_id = ? THEN 0 ELSE 1 END', [$yearId]);
        }

        return $query->ordered()->first();
    }

    /** @return list<string> */
    protected function defaultFeatures(int $minutes, bool $isRtl): array
    {
        if ($isRtl) {
            return [
                'مدة الحصة '.$minutes.' دقيقة',
                'متابعة الدروس',
                'معلمون معتمدون',
                'حصص تفاعلية أونلاين',
                'تقرير بعد الحصة',
            ];
        }

        return [
            $minutes.'-minute sessions',
            'Lesson follow-up',
            'Verified tutors',
            'Interactive live sessions',
            'Report after each session',
        ];
    }

    /** @return list<string> */
    protected function deviceTags(bool $isRtl): array
    {
        return $isRtl
            ? ['كمبيوتر', 'آيباد', 'أجهزة ذكية']
            : ['Computer', 'iPad', 'Smart devices'];
    }

    protected function iconForName(string $name): string
    {
        $n = mb_strtolower($name);

        return match (true) {
            str_contains($n, 'قرآن') || str_contains($n, 'quran') => 'fa-book-quran',
            str_contains($n, 'رياض') || str_contains($n, 'math') => 'fa-square-root-variable',
            str_contains($n, 'علوم') || str_contains($n, 'science') || str_contains($n, 'كيم') || str_contains($n, 'فيز') => 'fa-flask',
            str_contains($n, 'إنجل') || str_contains($n, 'english') => 'fa-language',
            str_contains($n, 'عرب') || str_contains($n, 'arabic') => 'fa-book-open',
            str_contains($n, 'اجتماع') => 'fa-earth-americas',
            default => 'fa-graduation-cap',
        };
    }
}
