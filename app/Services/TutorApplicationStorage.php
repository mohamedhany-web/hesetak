<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * مستندات تقديم المعلمين — صور، PDF، فيديو — على PUBLIC_MEDIA_DISK (Cloudflare R2 افتراضياً).
 */
class TutorApplicationStorage
{
    public const DIR_PHOTOS = 'tutor-applications/photos';

    public const DIR_IDS = 'tutor-applications/ids';

    public const DIR_CERTIFICATES = 'tutor-applications/certificates';

    public const DIR_VIDEOS = 'tutor-applications/videos';

    public static function resolvedDisk(): string
    {
        return PublicMediaStorage::resolvedDisk();
    }

    public static function storedRelativePath(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $value = trim(str_replace('\\', '/', $path));

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $urlPath = parse_url($value, PHP_URL_PATH);
            if (! is_string($urlPath) || $urlPath === '') {
                return null;
            }
            $value = $urlPath;
        }

        $value = ltrim($value, '/');
        foreach (['storage/', 'media/', 'public/'] as $prefix) {
            if (str_starts_with($value, $prefix)) {
                $value = substr($value, strlen($prefix));
            }
        }

        if (preg_match('#(tutor-applications/.+)$#', $value, $m)) {
            return PublicMediaStorage::normalizePath($m[1]);
        }

        $value = PublicMediaStorage::normalizePath($value);

        return $value !== '' ? $value : null;
    }

    public static function publicUrl(?string $path): ?string
    {
        $relative = self::storedRelativePath($path);
        if ($relative === null) {
            return null;
        }

        // ملفات التقديم على R2 الخاص — لا نستخدم رابط CDN العام لأنه يرجع 404.
        return PublicStorageUrl::localWebUrl($relative);
    }

    public static function readContents(?string $path): ?string
    {
        $relative = self::storedRelativePath($path);
        $candidates = [];
        if ($relative !== null) {
            $candidates[] = $relative;
            if (! str_starts_with($relative, 'public/')) {
                $candidates[] = 'public/'.$relative;
            }
        }

        $raw = is_string($path) ? trim($path) : '';
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            try {
                $remote = \Illuminate\Support\Facades\Http::timeout(20)->withOptions(['http_errors' => false])->get($raw);
                if ($remote->successful() && $remote->body() !== '') {
                    return $remote->body();
                }
            } catch (\Throwable) {
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            foreach (['public', 'local', 'r2', 's3'] as $diskName) {
                try {
                    $contents = Storage::disk($diskName)->get($candidate);
                    if (is_string($contents) && $contents !== '') {
                        return $contents;
                    }
                } catch (\Throwable) {
                }
            }
        }

        if ($relative !== null && str_contains($relative, 'tutor-applications/')) {
            $basename = basename($relative);
            $directory = trim(dirname($relative), '.');
            foreach (['public', 'r2'] as $diskName) {
                try {
                    foreach (Storage::disk($diskName)->files($directory) as $file) {
                        if (basename($file) === $basename) {
                            $contents = Storage::disk($diskName)->get($file);
                            if (is_string($contents) && $contents !== '') {
                                return $contents;
                            }
                        }
                    }
                } catch (\Throwable) {
                }
            }
        }

        return null;
    }

    public static function inlineDataUri(?string $path): ?string
    {
        $contents = self::readContents($path);
        if ($contents === null || strlen($contents) > 2_500_000) {
            return null;
        }

        $relative = self::storedRelativePath($path) ?? '';
        $mime = match (strtolower((string) pathinfo($relative, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    public static function storePhoto(UploadedFile $file, ?string $oldPath = null): string
    {
        return self::store($file, self::DIR_PHOTOS, ['jpg', 'jpeg', 'png', 'webp', 'gif'], $oldPath);
    }

    public static function storeIdDocument(UploadedFile $file, ?string $oldPath = null): string
    {
        return self::store($file, self::DIR_IDS, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], $oldPath);
    }

    public static function storeCertificate(UploadedFile $file, ?string $oldPath = null): string
    {
        return self::store($file, self::DIR_CERTIFICATES, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], $oldPath);
    }

    public static function storeVideo(UploadedFile $file, ?string $oldPath = null): string
    {
        return self::store($file, self::DIR_VIDEOS, ['mp4', 'webm', 'mov', 'm4v', 'ogg'], $oldPath);
    }

    /**
     * @param  list<string>  $allowedExt
     */
    public static function store(UploadedFile $file, string $directory, array $allowedExt, ?string $oldPath = null): string
    {
        $disk = self::resolvedDisk();
        $directory = trim(str_replace('\\', '/', $directory), '/');

        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: ''));
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: '';

        if ($ext === '' || ! in_array($ext, $allowedExt, true)) {
            $mime = (string) $file->getMimeType();
            $ext = match (true) {
                str_contains($mime, 'jpeg') => 'jpg',
                str_contains($mime, 'png') => 'png',
                str_contains($mime, 'webp') => 'webp',
                str_contains($mime, 'pdf') => 'pdf',
                str_contains($mime, 'webm') => 'webm',
                str_contains($mime, 'quicktime') => 'mov',
                str_contains($mime, 'mp4') => 'mp4',
                default => $allowedExt[0] ?? 'bin',
            };
            if (! in_array($ext, $allowedExt, true)) {
                $ext = $allowedExt[0] ?? 'bin';
            }
        }

        $name = Str::uuid()->toString().'.'.$ext;

        if ($disk === 'public') {
            Storage::disk('public')->makeDirectory($directory);
            $stored = $file->storeAs($directory, $name, 'public');
        } else {
            $stored = Storage::disk($disk)->putFileAs($directory, $file, $name);
        }

        if (! is_string($stored) || $stored === '') {
            throw new \RuntimeException('فشل رفع الملف على Cloudflare R2 / التخزين.');
        }

        $stored = str_replace('\\', '/', $stored);

        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $stored) {
            PublicMediaStorage::delete($oldPath);
        }

        return $stored;
    }

    public static function delete(?string $path): void
    {
        PublicMediaStorage::delete($path);
    }

    public static function isPdf(?string $path): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }

        return str_ends_with(strtolower(parse_url($path, PHP_URL_PATH) ?: $path), '.pdf');
    }
}
