<?php

namespace App\Services;

use App\Models\ServicePackage;
use App\Models\ServiceSessionRate;
use App\Support\HesetakMatchCatalog;
use Illuminate\Support\Facades\Schema;

class ServiceSessionRateService
{
    public const SOURCE_YEAR_TRACK = 'year_track';

    public const SOURCE_TRACK_GLOBAL = 'track_global';

    public const SOURCE_PACKAGE = 'package_fallback';

    /**
     * @return array{unit: float, total: float, currency: string, source: string, curriculum_type: string, academic_year_id: int|null}|null
     */
    public function quotePackage(
        ServicePackage $package,
        ?int $academicYearId = null,
        ?string $curriculumType = null,
    ): array {
        $units = max(1, (int) $package->units_count);
        $currency = strtoupper((string) ($package->currencyCode() ?: config('currency.code', 'SAR')));
        if (! in_array($currency, ['SAR', 'EGP', 'USD'], true)) {
            $currency = 'SAR';
        }

        $track = $this->normalizeCurriculumType(
            $curriculumType ?: (string) ($package->curriculum_type ?? '')
        ) ?: 'saudi';

        $yearId = $academicYearId ?: ($package->academic_year_id ? (int) $package->academic_year_id : null);

        $rate = $this->rateFor($yearId, $track, $currency);
        if ($rate !== null) {
            $unit = round((float) $rate['price_per_session'], 2);
            $source = $rate['source'];

            return [
                'unit' => $unit,
                'total' => round($unit * $units, 2),
                'currency' => $currency,
                'source' => $source,
                'curriculum_type' => $track,
                'academic_year_id' => $yearId,
                'units' => $units,
            ];
        }

        $unit = round((float) $package->price / $units, 2);
        $total = round((float) $package->price, 2);

        return [
            'unit' => $unit,
            'total' => $total,
            'currency' => $currency,
            'source' => self::SOURCE_PACKAGE,
            'curriculum_type' => $track,
            'academic_year_id' => $yearId,
            'units' => $units,
        ];
    }

    /**
     * @return array{price_per_session: float, source: string}|null
     */
    public function rateFor(?int $academicYearId, string $curriculumType, string $currency = 'SAR'): ?array
    {
        if (! Schema::hasTable('service_session_rates')) {
            return null;
        }

        $track = $this->normalizeCurriculumType($curriculumType);
        if ($track === null) {
            return null;
        }

        $currency = strtoupper($currency);

        if ($academicYearId) {
            $row = ServiceSessionRate::query()
                ->active()
                ->where('academic_year_id', $academicYearId)
                ->where('curriculum_type', $track)
                ->where('currency', $currency)
                ->first();

            if ($row) {
                return [
                    'price_per_session' => (float) $row->price_per_session,
                    'source' => self::SOURCE_YEAR_TRACK,
                ];
            }
        }

        $global = ServiceSessionRate::query()
            ->active()
            ->whereNull('academic_year_id')
            ->where('curriculum_type', $track)
            ->where('currency', $currency)
            ->first();

        if ($global) {
            return [
                'price_per_session' => (float) $global->price_per_session,
                'source' => self::SOURCE_TRACK_GLOBAL,
            ];
        }

        return null;
    }

    public function normalizeCurriculumType(?string $key): ?string
    {
        $key = strtolower(trim((string) $key));
        if ($key === '') {
            return null;
        }

        $allowed = array_keys(HesetakMatchCatalog::curriculumTypes());
        if (in_array($key, $allowed, true)) {
            return $key;
        }

        foreach (config('hesetak_match.curriculum_types', []) as $k => $meta) {
            $aliases = array_map('strtolower', $meta['aliases'] ?? []);
            if ($k === $key || in_array($key, $aliases, true)) {
                return (string) $k;
            }
        }

        return null;
    }
}
