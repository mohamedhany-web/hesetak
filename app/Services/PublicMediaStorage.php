<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * وسائط الموقع العامة (صور الكورسات، الباقات، الشعار، …) — محلي أو Cloudflare R2.
 *
 * .env: PUBLIC_MEDIA_DISK=r2 و AWS_* و (يفضّل) R2_PUBLIC_URL أو AWS_URL للرابط العام.
 */
class PublicMediaStorage
{
    /** @var array<int, string> */
    private const CLOUD_DISKS = ['r2', 's3'];

    public static function resolvedDisk(): string
    {
        $d = (string) config('filesystems.public_media_disk', 'public');

        if ($d === 'r2') {
            $bucket = config('filesystems.disks.r2.bucket');
            $endpoint = config('filesystems.disks.r2.endpoint');
            if (empty($bucket) || empty($endpoint)) {
                Log::warning('PUBLIC_MEDIA_DISK=r2 لكن إعدادات R2 غير مكتملة؛ يُستخدم القرص public.');

                return 'public';
            }
        }

        if ($d === 's3') {
            $bucket = config('filesystems.disks.s3.bucket');
            if (empty($bucket)) {
                return 'public';
            }
        }

        if (! in_array($d, ['public', 'r2', 's3'], true)) {
            return 'public';
        }

        return $d;
    }

    public static function publicUrl(?string $path, ?string $hintDisk = null): ?string
    {
        return PublicStorageUrl::fromPath($path, $hintDisk ?? self::resolvedDisk());
    }

    public static function exists(string $path): bool
    {
        return self::diskHolding($path) !== null;
    }

    /**
     * أول قرص يملك الملف (R2 ثم public …) — للقراءة/التحميل بعد توحيد الرفع على Cloudflare.
     */
    public static function diskHolding(string $path): ?string
    {
        $path = self::normalizePath($path);
        if ($path === '') {
            return null;
        }

        foreach (self::disksToProbe(null) as $disk) {
            try {
                if ($disk === 'public') {
                    if (PublicStorageUrl::publicDiskHasFile($path)) {
                        return 'public';
                    }

                    continue;
                }

                if (Storage::disk($disk)->exists($path)) {
                    return $disk;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        // مرفقات CRM القديمة كانت على local
        try {
            if (Storage::disk('local')->exists($path)) {
                return 'local';
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /**
     * @return string المسار النسبي داخل القرص (يُحفظ في قاعدة البيانات)
     */
    public static function store(UploadedFile $file, string $directory, ?string $oldPath = null): string
    {
        $disk = self::resolvedDisk();
        $directory = trim(str_replace('\\', '/', $directory), '/');

        $ext = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg'),
        };
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }

        $name = Str::uuid()->toString().'.'.$ext;

        if ($disk === 'public') {
            Storage::disk('public')->makeDirectory($directory);
            $stored = $file->storeAs($directory, $name, 'public');
        } else {
            $stored = Storage::disk($disk)->putFileAs($directory, $file, $name, 'public');
        }

        if (! is_string($stored) || $stored === '') {
            throw new \RuntimeException('فشل رفع الملف على التخزين.');
        }

        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $stored) {
            self::delete($oldPath);
        }

        return str_replace('\\', '/', $stored);
    }

    public static function storeUploaded(UploadedFile $file, string $directory): string
    {
        return self::store($file, $directory, null);
    }

    /**
     * رفع أي ملف (صورة / فيديو / مستند / مرفق) على قرص الوسائط — R2 عند اكتمال AWS_*.
     *
     * @return string المسار النسبي داخل القرص
     */
    public static function storeFile(UploadedFile $file, string $directory, ?string $oldPath = null): string
    {
        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin'));
        $ext = preg_replace('/[^a-z0-9]+/', '', $ext) ?: 'bin';
        $name = Str::uuid()->toString().'.'.$ext;

        return self::storeNamed($file, $directory, $name, $oldPath);
    }

    /**
     * رفع بملف باسم محدد (شهادات، حضور، …) على نفس قرص الوسائط.
     */
    public static function storeFileAs(UploadedFile $file, string $directory, string $filename, ?string $oldPath = null): string
    {
        $filename = basename(str_replace(['\\', "\0"], ['/', ''], $filename));
        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new \InvalidArgumentException('اسم الملف غير صالح.');
        }

        return self::storeNamed($file, $directory, $filename, $oldPath);
    }

    /**
     * كتابة محتوى ثنائي/نصي مباشرة (مثل PDF مولَّد) على قرص الوسائط.
     */
    public static function putContents(string $path, string|array $contents, array $options = []): string
    {
        $disk = self::resolvedDisk();
        $path = self::normalizePath($path);
        $dir = trim(dirname($path), '.');
        if ($dir !== '' && $dir !== '/') {
            try {
                Storage::disk($disk)->makeDirectory($dir);
            } catch (\Throwable) {
            }
        }

        $ok = Storage::disk($disk)->put($path, $contents, $options);
        if ($ok === false) {
            throw new \RuntimeException('فشل حفظ الملف على التخزين.');
        }

        return $path;
    }

    /**
     * @return string المسار النسبي داخل القرص
     */
    private static function storeNamed(UploadedFile $file, string $directory, string $name, ?string $oldPath = null): string
    {
        $disk = self::resolvedDisk();
        $directory = trim(str_replace('\\', '/', $directory), '/');

        if ($disk === 'public') {
            Storage::disk('public')->makeDirectory($directory);
            $stored = $file->storeAs($directory, $name, 'public');
        } else {
            $stored = Storage::disk($disk)->putFileAs($directory, $file, $name);
        }

        if (! is_string($stored) || $stored === '') {
            throw new \RuntimeException('فشل رفع الملف على التخزين.');
        }

        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $stored) {
            self::delete($oldPath);
        }

        return str_replace('\\', '/', $stored);
    }

    public static function delete(?string $path): void
    {
        if (! is_string($path) || $path === '' || self::isExternalUrl($path)) {
            return;
        }

        $path = self::normalizePath($path);
        foreach (array_unique([self::resolvedDisk(), 'public', 'r2', 's3']) as $disk) {
            if (! in_array($disk, ['public', 'r2', 's3'], true)) {
                continue;
            }
            try {
                if ($disk === 'public') {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }

                    continue;
                }

                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (\Throwable) {
            }
        }
    }

    public static function isExternalUrl(?string $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    /**
     * نسخ ملف من القرص المحلي إلى R2 (للترحيل).
     */
    public static function mirrorLocalToR2(string $path): bool
    {
        $path = self::normalizePath($path);
        if ($path === '' || ! PublicStorageUrl::publicDiskHasFile($path)) {
            return false;
        }

        $targetDisk = self::resolvedDisk();
        if ($targetDisk === 'public') {
            return true;
        }

        try {
            if (Storage::disk($targetDisk)->exists($path)) {
                return true;
            }

            $contents = Storage::disk('public')->get($path);
            Storage::disk($targetDisk)->put($path, $contents, 'public');

            return Storage::disk($targetDisk)->exists($path);
        } catch (\Throwable $e) {
            Log::warning('mirrorLocalToR2 failed', ['path' => $path, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** @return array<int, string> */
    public static function disksToProbe(?string $preferredDisk): array
    {
        return array_values(array_unique(array_filter([
            $preferredDisk,
            self::resolvedDisk(),
            'public',
            ...self::CLOUD_DISKS,
        ])));
    }

    public static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', ltrim($path, '/'));
    }
}
