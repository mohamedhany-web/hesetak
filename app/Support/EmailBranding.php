<?php

namespace App\Support;

use App\Services\SeoAssets;

/**
 * هوية بريد حصتك + روابط مطلقة صالحة في عملاء البريد (Gmail وغيرها).
 */
final class EmailBranding
{
    public static function brandName(): string
    {
        return (string) config('app.name', 'حصتك');
    }

    public static function siteUrl(): string
    {
        $url = rtrim((string) config('app.url', 'https://hissatak.online'), '/');

        // روابط البريد يجب أن تكون عامة — Gmail لا يحمّل localhost
        if ($url === '' || preg_match('#^https?://(localhost|127\.0\.0\.1)(:|/|$)#i', $url)) {
            return 'https://hissatak.online';
        }

        return $url;
    }

    /** شعار عريض للبريد (يفضَّل hesetak-logo.png). */
    public static function logoUrl(): string
    {
        // مسار Laravel المضمون على Hostinger
        return self::siteUrl().'/__hesetak/img/brand/hesetak-logo.png';
    }

    public static function markUrl(): string
    {
        return self::siteUrl().'/__hesetak/img/brand/hesetak-mark.png';
    }

    public static function ogImageUrl(): string
    {
        try {
            return self::absoluteAsset(SeoAssets::ogImageUrl());
        } catch (\Throwable) {
            return self::logoUrl();
        }
    }

    public static function supportUrl(): string
    {
        try {
            $url = route('public.contact');
            if (preg_match('#^https?://(localhost|127\.0\.0\.1)#i', $url)) {
                return self::siteUrl().'/contact';
            }

            return $url;
        } catch (\Throwable) {
            return self::siteUrl().'/contact';
        }
    }

    public static function notificationsUrl(): string
    {
        return self::siteUrl().'/notifications';
    }

    public static function absoluteAsset(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return self::logoUrl();
        }
        if (str_starts_with($url, 'data:')) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }
        if (preg_match('#^https?://#i', $url)) {
            if (preg_match('#^https?://(localhost|127\.0\.0\.1)#i', $url)) {
                $path = parse_url($url, PHP_URL_PATH) ?: '';

                return self::siteUrl().$path;
            }

            return $url;
        }

        return self::siteUrl().'/'.ltrim($url, '/');
    }

    /** @return array{navy:string,navyDeep:string,gold:string,bg:string,text:string,muted:string} */
    public static function colors(): array
    {
        return [
            'navy' => '#1E4E8C',
            'navyDeep' => '#152A4A',
            'gold' => '#C9952A',
            'bg' => '#F4F7FC',
            'text' => '#152A4A',
            'muted' => '#64748B',
        ];
    }
}
