<?php

namespace App\Support;

class TadrisPublicNav
{
    /** @return array<string, array> */
    public static function tree(): array
    {
        $tree = config('tadris_nav', []);

        return collect($tree)
            ->filter(fn ($node) => ($node['enabled'] ?? true) !== false)
            ->sortBy('order')
            ->all();
    }

    /** Top-level items for primary nav. */
    public static function primary(): array
    {
        return collect(self::tree())
            ->filter(fn ($node) => ($node['nav'] ?? false) === true)
            ->map(function (array $node, string $key) {
                $children = collect($node['children'] ?? [])
                    ->filter(fn ($child) => ($child['enabled'] ?? true) !== false)
                    ->map(fn (array $child, string $childKey) => self::hydrate($childKey, $child))
                    ->values()
                    ->all();

                return self::hydrate($key, $node) + ['children' => $children];
            })
            ->values()
            ->all();
    }

    public static function find(string $key): ?array
    {
        $tree = config('tadris_nav', []);
        if (isset($tree[$key])) {
            return self::hydrate($key, $tree[$key]) + [
                'children' => collect($tree[$key]['children'] ?? [])
                    ->filter(fn ($c) => ($c['enabled'] ?? true) !== false)
                    ->map(fn (array $c, string $ck) => self::hydrate($ck, $c))
                    ->values()
                    ->all(),
            ];
        }

        foreach ($tree as $parentKey => $parent) {
            foreach ($parent['children'] ?? [] as $childKey => $child) {
                if ($childKey === $key) {
                    return self::hydrate($childKey, $child) + [
                        'parent' => self::hydrate($parentKey, $parent),
                        'children' => [],
                    ];
                }
            }
        }

        return null;
    }

    /** Flat list of routable nodes for registration. */
    public static function routable(): array
    {
        $out = [];
        foreach (self::tree() as $key => $node) {
            if (($node['route'] ?? null) && str_starts_with((string) $node['route'], 'public.site.')) {
                $out[$key] = self::hydrate($key, $node);
            }
            foreach ($node['children'] ?? [] as $childKey => $child) {
                if (($child['enabled'] ?? true) === false) {
                    continue;
                }
                if (($child['route'] ?? null) && str_starts_with((string) $child['route'], 'public.site.')) {
                    $out[$childKey] = self::hydrate($childKey, $child);
                }
            }
        }

        return $out;
    }

    public static function isActive(string $key): bool
    {
        $node = self::find($key);
        if (! $node) {
            return false;
        }
        $route = $node['route'] ?? null;
        if ($route && request()->routeIs($route)) {
            return true;
        }
        foreach ($node['children'] ?? [] as $child) {
            if (! empty($child['route']) && request()->routeIs($child['route'])) {
                return true;
            }
        }

        return false;
    }

    protected static function hydrate(string $key, array $node): array
    {
        return [
            'key' => $key,
            'uri' => $node['uri'] ?? '/',
            'route' => $node['route'] ?? null,
            'view' => $node['view'] ?? 'public.site.show',
            'nav' => (bool) ($node['nav'] ?? false),
            'order' => (int) ($node['order'] ?? 100),
            'enabled' => ($node['enabled'] ?? true) !== false,
            'label' => __('site.nav.'.$key),
        ];
    }
}
