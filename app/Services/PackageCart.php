<?php

namespace App\Services;

use App\Models\ServicePackage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * سلة جلسة لشراء أكثر من باقة ثم الدفع.
 */
class PackageCart
{
    public const SESSION_KEY = 'hesetak_package_cart';

    /**
     * @return list<array{package_id:int,year_id:?int,subject_id:?int,instructor_id:?int,term_months:?int,weekly_sessions:?int,sessions:?int,schedule_notes:?string,label:string,unit_price:float,total:float}>
     */
    public static function items(): array
    {
        $raw = Session::get(self::SESSION_KEY, []);

        return is_array($raw) ? array_values($raw) : [];
    }

    public static function count(): int
    {
        return count(self::items());
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * @param  array{package_id:int,year_id?:?int,subject_id?:?int,instructor_id?:?int,term_months?:?int,weekly_sessions?:?int,sessions?:?int,schedule_notes?:?string}  $item
     */
    public static function add(array $item): void
    {
        $packageId = (int) ($item['package_id'] ?? 0);
        $package = ServicePackage::query()->find($packageId);
        if (! $package || ! $package->is_active) {
            throw new \InvalidArgumentException('الباقة غير متاحة.');
        }

        $sessions = max(1, (int) ($item['sessions'] ?? $package->units_count ?: 1));
        $unit = (float) $package->pricePerUnit();
        $total = round($unit * $sessions, 2);
        if ((float) $package->price > 0 && (int) $package->units_count > 0 && empty($item['sessions'])) {
            $total = (float) $package->price;
            $sessions = (int) $package->units_count;
            $unit = $package->pricePerUnit();
        }

        $entry = [
            'key' => uniqid('pkg_', true),
            'package_id' => $packageId,
            'year_id' => isset($item['year_id']) ? (int) $item['year_id'] ?: null : null,
            'subject_id' => isset($item['subject_id']) ? (int) $item['subject_id'] ?: null : null,
            'instructor_id' => isset($item['instructor_id']) ? (int) $item['instructor_id'] ?: null : null,
            'term_months' => isset($item['term_months']) ? (int) $item['term_months'] ?: null : null,
            'weekly_sessions' => isset($item['weekly_sessions']) ? (int) $item['weekly_sessions'] ?: null : null,
            'sessions' => $sessions,
            'schedule_notes' => isset($item['schedule_notes']) ? trim((string) $item['schedule_notes']) ?: null : null,
            'label' => (string) $package->name,
            'unit_price' => round((float) $unit, 2),
            'total' => $total,
        ];

        $items = self::items();
        $items[] = $entry;
        Session::put(self::SESSION_KEY, $items);
    }

    public static function remove(string $key): void
    {
        $items = array_values(array_filter(self::items(), fn ($row) => ($row['key'] ?? '') !== $key));
        Session::put(self::SESSION_KEY, $items);
    }

    public static function total(): float
    {
        return round(collect(self::items())->sum(fn ($row) => (float) ($row['total'] ?? 0)), 2);
    }

    /**
     * @return Collection<int, ServicePackage>
     */
    public static function packages(): Collection
    {
        $ids = collect(self::items())->pluck('package_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return ServicePackage::query()->whereIn('id', $ids)->get()->keyBy('id');
    }
}
