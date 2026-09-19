<?php

if (! function_exists('community_disk')) {
    /**
     * قرص تخزين ملفات المجتمع (تقديمات المساهمين).
     *
     * @return string 'r2' أو 'local'
     */
    function community_disk(): string
    {
        $envDisk = env('FILESYSTEM_DISK_COMMUNITY');
        if ($envDisk !== null && $envDisk !== '' && in_array($envDisk, ['r2', 'local'], true)) {
            return $envDisk;
        }

        return config('filesystems.community_disk', 'local');
    }
}

if (! function_exists('storage_public_url')) {
    /**
     * رابط عرض ملف من storage/app/public أو R2.
     */
    function storage_public_url(?string $path, ?string $preferredDisk = null): ?string
    {
        return \App\Services\PublicStorageUrl::fromPath($path, $preferredDisk);
    }
}

if (! function_exists('storage_public_url_stable')) {
    /**
     * رابط ثابت — للسلايدر والمحتوى الإداري (لا روابط موقّعة متغيّرة).
     */
    function storage_public_url_stable(?string $path, ?string $preferredDisk = null): ?string
    {
        return \App\Services\PublicStorageUrl::fromPathStable($path, $preferredDisk);
    }
}

if (! function_exists('storage_asset')) {
    /**
     * بديل asset('storage/...') — يحترم مجلد التطبيق الفرعي ونفس نطاق الطلب.
     */
    function storage_asset(?string $path): ?string
    {
        return storage_public_url($path);
    }
}

if (! function_exists('storage_base_url')) {
    /**
     * قاعدة روابط التخزين للاستخدام في JavaScript: storage_base_url() + '/' + path
     */
    function storage_base_url(): string
    {
        return rtrim(\App\Support\ApplicationUrl::resolveRootUrl(), '/').'/'.\App\Services\PublicStorageUrl::PROXY_PATH;
    }
}

if (! function_exists('versioned_asset')) {
    /**
     * رابط أصل ثابت مع بصمة تعديل الملف — يكسر كاش المتصفح تلقائياً عند كل تحديث.
     * يستخدم مساراً نسبياً لنفس أصل الصفحة حتى لا تنكسر CSS/JS إذا كان APP_URL خاطئاً على السيرفر.
     */
    function versioned_asset(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $full = public_path($path);
        $version = is_file($full) ? (string) filemtime($full) : (string) time();

        // ASSET_URL صريح (CDN) — احترم الإعداد
        $assetUrl = config('app.asset_url');
        if (is_string($assetUrl) && $assetUrl !== '') {
            $url = rtrim($assetUrl, '/').'/'.$path;

            return $url.(str_contains($url, '?') ? '&' : '?').'v='.$version;
        }

        $basePath = \App\Support\ApplicationUrl::scriptBasePath();
        $url = ($basePath !== '' ? $basePath : '').'/'.$path;

        return $url.'?v='.$version;
    }
}

if (! function_exists('public_img_url')) {
    /**
     * رابط صورة من public/img/{folder}/{file} عبر مسار Laravel المضمون /__hesetak/img/...
     * (على Hostinger /img/* غالباً يُخدم كملف ثابت من document root خاطئ → 404).
     *
     * @param  string  $relative  مثال: brand/hesetak-mark.png أو img/mycourses/hero.png
     */
    function public_img_url(string $relative, bool $version = true): string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        if (str_starts_with($relative, 'img/')) {
            $relative = substr($relative, 4);
        }

        $parts = explode('/', $relative, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return versioned_asset('img/'.$relative);
        }

        $folder = $parts[0];
        $file = basename($parts[1]);
        $path = '__hesetak/img/'.$folder.'/'.$file;

        if (! $version) {
            $basePath = \App\Support\ApplicationUrl::scriptBasePath();

            return ($basePath !== '' ? $basePath : '').'/'.$path;
        }

        $full = public_path("img/{$folder}/{$file}");
        $v = is_file($full) ? (string) filemtime($full) : (string) time();
        $basePath = \App\Support\ApplicationUrl::scriptBasePath();
        $url = ($basePath !== '' ? $basePath : '').'/'.$path;

        return $url.'?v='.$v;
    }
}
