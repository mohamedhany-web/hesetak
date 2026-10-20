<?php

namespace App\Support;

final class PublicSeo
{
    /**
     * أسماء البراند للبحث التقليدي وGEO (عربي + لاتيني + مطابقة الدومين).
     *
     * @return list<string>
     */
    public static function brandAlternateNames(): array
    {
        return [
            'حصتك',
            'Hesetak',
            'Hissatak',
            'hesetak',
            'hissatak',
            'حصتك اونلاين',
            'منصة حصتك',
        ];
    }

    public static function brandPrimaryName(): string
    {
        return (string) (config('app.name') ?: 'حصتك');
    }

    public static function brandLatinName(): string
    {
        return 'Hesetak';
    }

    public static function siteRoot(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * حوّل أي مسار/رابط نسبي إلى URL مطلق صالح للكراولرز وOG/JSON-LD.
     */
    public static function forceAbsoluteUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return self::siteRoot().'/';
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        return self::siteRoot().'/'.ltrim($url, '/');
    }

    public static function absoluteUrl(?string $path = null): string
    {
        $base = self::siteRoot();
        if ($path === null) {
            $path = '/'.ltrim(request()->path(), '/');
        } else {
            $path = '/'.ltrim($path, '/');
        }

        if ($path === '/' || $path === '/index.php') {
            return $base.'/';
        }

        return $base.$path;
    }

    public static function canonicalUrl(?string $path = null): string
    {
        $url = self::absoluteUrl($path);
        if (app()->getLocale() !== 'en') {
            return $url;
        }

        return self::withQuery($url, ['lang' => 'en']);
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    public static function withQuery(string $url, array $query): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $existing = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $existing);
        }

        foreach ($query as $key => $value) {
            if ($value === null) {
                unset($existing[$key]);
            } else {
                $existing[$key] = $value;
            }
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';
        $qs = http_build_query($existing);

        return $scheme.'://'.$host.$port.$path.($qs !== '' ? '?'.$qs : '').$fragment;
    }

    /**
     * @return array{ar: string, en: string, x-default: string}
     */
    public static function hreflangHrefs(?string $path = null): array
    {
        $base = self::absoluteUrl($path);

        return [
            'ar' => self::withQuery($base, ['lang' => 'ar']),
            'en' => self::withQuery($base, ['lang' => 'en']),
            'x-default' => self::withQuery($base, ['lang' => 'ar']),
        ];
    }

    public static function robots(bool $indexable = true): string
    {
        if (! $indexable) {
            return 'noindex, nofollow';
        }

        return 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }

    public static function isPrivatePath(?string $path = null): bool
    {
        $path = '/'.ltrim($path ?? request()->path(), '/');
        $prefixes = [
            '/login',
            '/register',
            '/forgot-password',
            '/reset-password',
            '/password',
            '/checkout',
            '/dashboard',
            '/admin',
            '/account',
            '/teacher/dashboard',
            '/student/dashboard',
            '/employee',
            '/instructor',
            '/student',
        ];

        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        if (str_contains($path, '/checkout')) {
            return true;
        }

        return false;
    }
}
