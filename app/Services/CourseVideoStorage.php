<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * فيديوهات دروس الكورسات — محلي أو Cloudflare R2.
 *
 * .env: PUBLIC_MEDIA_DISK=r2 و AWS_* (ونفس إعدادات الصور العامة).
 */
class CourseVideoStorage
{
    public const DIRECTORY = 'course-videos';

    public static function resolvedDisk(): string
    {
        return PublicMediaStorage::resolvedDisk();
    }

    /**
     * رفع فيديو وإرجاع المسار النسبي داخل القرص (يُحفظ في video_url).
     */
    public static function store(UploadedFile $file, ?string $oldPathOrUrl = null): string
    {
        $disk = self::resolvedDisk();
        $dir = self::DIRECTORY;
        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'mp4'));
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'mp4';
        if (! in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'm4v'], true)) {
            $ext = 'mp4';
        }
        $name = Str::uuid()->toString().'.'.$ext;

        if ($disk === 'public') {
            Storage::disk('public')->makeDirectory($dir);
            $stored = $file->storeAs($dir, $name, 'public');
        } else {
            $stored = Storage::disk($disk)->putFileAs($dir, $file, $name, ['visibility' => 'public']);
        }

        if (! is_string($stored) || $stored === '') {
            throw new \RuntimeException('فشل رفع الفيديو على التخزين.');
        }

        $stored = str_replace('\\', '/', $stored);

        if (is_string($oldPathOrUrl) && $oldPathOrUrl !== '') {
            self::delete($oldPathOrUrl);
        }

        return $stored;
    }

    /**
     * رابط تشغيل للفيديو المخزّن (R2 موقّع / عام أو /storage أو /media).
     */
    public static function publicUrl(?string $pathOrUrl): ?string
    {
        if (! is_string($pathOrUrl) || trim($pathOrUrl) === '') {
            return null;
        }

        $value = trim($pathOrUrl);

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            // روابط Bunny / YouTube / Vimeo تُعاد كما هي
            if (! self::looksLikeStoredMediaUrl($value)) {
                return $value;
            }

            $relative = self::relativePathFromUrl($value);
            if ($relative !== null) {
                return PublicStorageUrl::fromPath($relative, self::resolvedDisk()) ?? $value;
            }

            return $value;
        }

        $relative = self::normalizeStoredPath($value);

        return PublicStorageUrl::fromPath($relative, self::resolvedDisk());
    }

    public static function delete(?string $pathOrUrl): void
    {
        $relative = self::normalizeStoredPath($pathOrUrl ?? '');
        if ($relative === '' || ! str_starts_with($relative, self::DIRECTORY.'/')) {
            return;
        }

        PublicMediaStorage::delete($relative);
    }

    /**
     * هل القيمة تشير إلى ملف مخزّن لدينا (وليس Bunny/YouTube)؟
     */
    public static function looksLikeStoredMediaUrl(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (str_contains($value, 'mediadelivery.net') || str_contains($value, 'youtube.com') || str_contains($value, 'youtu.be') || str_contains($value, 'vimeo.com')) {
            return false;
        }

        if (preg_match('#(?:^|/)(?:storage|media)/'.preg_quote(self::DIRECTORY, '#').'/#', $value)) {
            return true;
        }

        if (str_starts_with($value, self::DIRECTORY.'/')) {
            return true;
        }

        return (bool) preg_match('#cloudflarestorage\.com/.+/'.preg_quote(self::DIRECTORY, '#').'/#', $value);
    }

    public static function normalizeStoredPath(string $pathOrUrl): string
    {
        $value = trim(str_replace('\\', '/', $pathOrUrl));
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return self::relativePathFromUrl($value) ?? '';
        }

        $value = ltrim($value, '/');
        foreach (['storage/', 'media/'] as $prefix) {
            if (str_starts_with($value, $prefix)) {
                $value = substr($value, strlen($prefix));
            }
        }

        return PublicMediaStorage::normalizePath($value);
    }

    public static function relativePathFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        // مسار التخزين النسبي داخل الـ bucket (course-videos/x.mp4)
        if (preg_match('#(?:^|/)(?:storage/|media/)?('.preg_quote(self::DIRECTORY, '#').'/[^?\#]+)$#', $path, $m)) {
            return PublicMediaStorage::normalizePath($m[1]);
        }

        if (str_contains($path, self::DIRECTORY.'/')) {
            $pos = strpos($path, self::DIRECTORY.'/');

            return PublicMediaStorage::normalizePath(substr($path, $pos));
        }

        return null;
    }

    /**
     * نسخ ملف محلي إلى R2 (ترحيل).
     */
    public static function mirrorLocalToR2(string $relativePath): bool
    {
        return PublicMediaStorage::mirrorLocalToR2(self::normalizeStoredPath($relativePath));
    }
}
