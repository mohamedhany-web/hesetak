<?php

namespace App\Services;

/**
 * أصول وصور SEO المشتركة (OG / JSON-LD).
 */
class SeoAssets
{
    public static function ogImageUrl(): string
    {
        $configured = config('app.og_image_url');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $local = public_path('img/brand/hesetak-logo.png');
        if (is_file($local) && function_exists('public_img_url')) {
            return public_img_url('brand/hesetak-logo.png');
        }
        if (is_file($local)) {
            return asset('img/brand/hesetak-logo.png');
        }

        $logo = AdminPanelBranding::logoPublicUrl();
        if (is_string($logo) && $logo !== '') {
            return $logo;
        }

        return function_exists('public_img_url')
            ? public_img_url('brand/hesetak-mark.png')
            : asset('img/brand/hesetak-mark.png');
    }

    /**
     * تصغير روابط Unsplash لتقليل حجم LCP.
     */
    public static function optimizedRemoteImage(?string $url, int $width = 1600, int $quality = 72): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (! str_contains($url, 'images.unsplash.com')) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);
        $query['auto'] = $query['auto'] ?? 'format';
        $query['fit'] = $query['fit'] ?? 'crop';
        $query['w'] = (string) $width;
        $query['q'] = (string) $quality;

        $base = $parts['scheme'].'://'.$parts['host'].($parts['path'] ?? '');

        return $base.'?'.http_build_query($query);
    }
}
