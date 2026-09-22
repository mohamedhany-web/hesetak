<?php

namespace App\Support;

use App\Models\HesetakCurriculumType;
use Illuminate\Support\Facades\Schema;

class HesetakMatchCatalog
{
    /**
     * @return array<string, array{key:string,label:string,aliases:list<string>}>
     */
    public static function curriculumTypes(?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $out = [];

        foreach (self::rawCurriculumTypeRows() as $key => $row) {
            $label = $locale === 'ar'
                ? (string) ($row['label_ar'] ?? $key)
                : (string) ($row['label_en'] ?? $key);
            $out[$key] = [
                'key' => $key,
                'label' => $label,
                'aliases' => array_values(array_filter(array_map('strval', $row['aliases'] ?? []))),
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function allowedCurriculumTypeKeys(): array
    {
        return array_keys(self::rawCurriculumTypeRows(includeInactive: true));
    }

    /**
     * Active keys only (for public filters / instructor self-serve).
     *
     * @return list<string>
     */
    public static function activeCurriculumTypeKeys(): array
    {
        return array_keys(self::rawCurriculumTypeRows(includeInactive: false));
    }

    public static function curriculumTypeLabel(string $key, ?string $locale = null): string
    {
        return self::curriculumTypes($locale)[$key]['label'] ?? $key;
    }

    /**
     * @param  list<string>|null  $stored
     */
    public static function profileMatchesCurriculumType(?array $stored, string $needle, ?string $haystackText = null): bool
    {
        $needle = trim($needle);
        if ($needle === '') {
            return true;
        }

        $types = self::curriculumTypes();
        $key = isset($types[$needle]) ? $needle : null;
        if ($key === null) {
            foreach ($types as $k => $meta) {
                foreach (array_merge([$meta['label']], $meta['aliases']) as $alias) {
                    if (mb_strtolower($alias) === mb_strtolower($needle)) {
                        $key = $k;
                        break 2;
                    }
                }
            }
        }

        if ($key !== null && is_array($stored) && in_array($key, $stored, true)) {
            return true;
        }

        $text = mb_strtolower((string) $haystackText);
        if ($text === '') {
            return false;
        }

        $aliases = $key !== null
            ? array_merge([$types[$key]['label']], $types[$key]['aliases'])
            : [$needle];

        foreach ($aliases as $alias) {
            $alias = trim((string) $alias);
            if ($alias !== '' && mb_strpos($text, mb_strtolower($alias)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array{label_ar:string,label_en:string,aliases:list<string>}>
     */
    private static function rawCurriculumTypeRows(bool $includeInactive = false): array
    {
        if (Schema::hasTable('hesetak_curriculum_types')) {
            $query = HesetakCurriculumType::query()->ordered();
            if (! $includeInactive) {
                $query->active();
            }
            $rows = $query->get();
            if ($rows->isNotEmpty()) {
                $out = [];
                foreach ($rows as $row) {
                    $out[$row->key] = [
                        'label_ar' => (string) $row->label_ar,
                        'label_en' => (string) $row->label_en,
                        'aliases' => array_values(array_filter(array_map('strval', $row->aliases ?? []))),
                    ];
                }

                return $out;
            }
        }

        $out = [];
        foreach (config('hesetak_match.curriculum_types', []) as $key => $row) {
            $out[(string) $key] = [
                'label_ar' => (string) ($row['label_ar'] ?? $key),
                'label_en' => (string) ($row['label_en'] ?? $key),
                'aliases' => array_values(array_filter(array_map('strval', $row['aliases'] ?? []))),
            ];
        }

        return $out;
    }
}
