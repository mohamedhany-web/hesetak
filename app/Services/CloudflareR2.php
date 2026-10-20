<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * جاهزية قرص Cloudflare R2 من إعدادات filesystems (وليس env() بعد config:cache).
 */
class CloudflareR2
{
    /**
     * @return list<string>
     */
    public static function missingFields(): array
    {
        $cfg = config('filesystems.disks.r2', []);
        $missing = [];
        foreach (['key' => 'AWS_ACCESS_KEY_ID', 'secret' => 'AWS_SECRET_ACCESS_KEY', 'bucket' => 'AWS_BUCKET', 'endpoint' => 'AWS_ENDPOINT'] as $field => $env) {
            if (trim((string) ($cfg[$field] ?? '')) === '') {
                $missing[] = $env;
            }
        }

        return $missing;
    }

    public static function isReady(): bool
    {
        return self::missingFields() === [];
    }

    /**
     * @param  'r2'|'s3'|'public'|'local'|string  $preferred
     */
    public static function resolveDisk(string $preferred = 'r2'): string
    {
        $preferred = strtolower(trim($preferred));
        if ($preferred === '' || $preferred === '0') {
            $preferred = 'r2';
        }

        if ($preferred === 'r2') {
            if (self::isReady()) {
                return 'r2';
            }

            Log::warning('Cloudflare R2 is not ready; falling back to public disk.', [
                'missing' => self::missingFields(),
            ]);

            return 'public';
        }

        if ($preferred === 's3') {
            $bucket = trim((string) config('filesystems.disks.s3.bucket', ''));

            return $bucket !== '' ? 's3' : 'public';
        }

        if (in_array($preferred, ['public', 'local'], true)) {
            return $preferred === 'local' ? 'local' : 'public';
        }

        return self::isReady() ? 'r2' : 'public';
    }

    /**
     * نطاقات مسموحة لرفع المتصفح مباشرة إلى R2 (PUT).
     *
     * @param  list<string>|null  $extra
     * @return list<string>
     */
    public static function browserCorsOrigins(?array $extra = null): array
    {
        $list = [
            rtrim((string) config('app.url'), '/'),
            'https://hissatak.online',
            'https://www.hissatak.online',
        ];
        if (is_array($extra)) {
            $list = array_merge($list, $extra);
        }

        $out = [];
        foreach ($list as $origin) {
            $origin = rtrim(trim((string) $origin), '/');
            if ($origin === '' || ! preg_match('#^https?://[^\s/]+#i', $origin)) {
                continue;
            }
            $out[] = $origin;
        }

        return array_values(array_unique($out));
    }

    /**
     * يكتب قاعدة CORS على الـ bucket حتى يعمل PUT من لوحة الأدمن دون خطأ شبكة.
     *
     * @param  list<string>|null  $extraOrigins
     */
    public static function ensureBrowserUploadCors(?array $extraOrigins = null): bool
    {
        if (! self::isReady()) {
            return false;
        }

        $origins = self::browserCorsOrigins($extraOrigins);
        if ($origins === []) {
            return false;
        }

        $cacheKey = 'r2_browser_cors:'.sha1(implode('|', $origins));
        if (Cache::get($cacheKey)) {
            return true;
        }

        try {
            $cfg = config('filesystems.disks.r2', []);
            $client = new S3Client([
                'version' => 'latest',
                'region' => $cfg['region'] ?? 'auto',
                'credentials' => [
                    'key' => $cfg['key'] ?? '',
                    'secret' => $cfg['secret'] ?? '',
                ],
                'endpoint' => $cfg['endpoint'] ?? null,
                'use_path_style_endpoint' => (bool) ($cfg['use_path_style_endpoint'] ?? true),
                'http' => [
                    'connect_timeout' => 10,
                    'timeout' => 20,
                ],
            ]);

            $bucket = (string) ($cfg['bucket'] ?? '');
            $existingOrigins = [];
            try {
                $current = $client->getBucketCors(['Bucket' => $bucket]);
                foreach ($current['CORSRules'] ?? [] as $rule) {
                    foreach ($rule['AllowedOrigins'] ?? [] as $origin) {
                        $existingOrigins[] = rtrim((string) $origin, '/');
                    }
                }
            } catch (\Throwable) {
                $existingOrigins = [];
            }

            $merged = array_values(array_unique(array_filter(array_merge($existingOrigins, $origins))));
            if (in_array('*', $merged, true)) {
                $merged = ['*'];
            }

            $client->putBucketCors([
                'Bucket' => $bucket,
                'CORSConfiguration' => [
                    'CORSRules' => [[
                        'AllowedOrigins' => $merged,
                        'AllowedMethods' => ['GET', 'PUT', 'HEAD', 'POST'],
                        'AllowedHeaders' => ['*'],
                        'ExposeHeaders' => ['ETag', 'etag', 'x-amz-request-id', 'x-amz-version-id'],
                        'MaxAgeSeconds' => 86400,
                    ]],
                ],
            ]);

            Cache::put($cacheKey, true, now()->addHours(6));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to apply Cloudflare R2 browser CORS', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
