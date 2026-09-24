<?php

namespace App\Http\Controllers;

use App\Services\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * تقديم ملفات /media و /storage — محلي أولاً إن وُجد، ثم R2 عبر قراءة مباشرة (بدون الاعتماد على exists).
 */
class StorageFileController extends Controller
{
    public function show(Request $request, string $path): Response
    {
        $path = rawurldecode($path);
        $path = str_replace('..', '', $path);
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '') {
            abort(404);
        }

        $this->assertPubliclyServable($request, $path);

        $mimeFromExtension = static function (string $filePath): string {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            return match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'pdf' => 'application/pdf',
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'mov' => 'video/quicktime',
                'm4v' => 'video/mp4',
                default => 'application/octet-stream',
            };
        };

        foreach ($this->candidatePaths($path) as $candidate) {
            $localResponse = $this->serveLocalPublicFile($candidate, $mimeFromExtension);
            if ($localResponse !== null) {
                return $localResponse;
            }

            foreach (['r2', 's3'] as $cloudDisk) {
                $cloudResponse = $this->serveCloudFile($cloudDisk, $candidate, $mimeFromExtension);
                if ($cloudResponse !== null) {
                    return $cloudResponse;
                }
            }
        }

        Log::warning('Storage file not found', [
            'requested_path' => $path,
            'url' => $request->fullUrl(),
        ]);

        abort(404, 'File not found');
    }

    /**
     * مسارات تحتوي مستندات هوية/عقود/إثباتات — ليست للعرض العام عبر /media.
     * صور/فيديو تقديم المعلمين المعتمدين تُعرض علناً في صفحات المدرّسين.
     *
     * @return list<string>
     */
    private function sensitivePrefixes(): array
    {
        return [
            'tutor-applications/ids/',
            'tutor-applications/certificates/',
            'payment-proofs/',
            'hr_cvs/',
            'receipts/',
            'expenses/',
            'crm/',
            'attendance/teams/',
        ];
    }

    private function assertPubliclyServable(Request $request, string $path): void
    {
        $normalized = ltrim($path, '/');
        if (str_starts_with($normalized, 'public/')) {
            $normalized = substr($normalized, strlen('public/'));
        }

        // أي مسار تحت tutor-applications غير photos/videos يبقى حسّاساً
        if (str_starts_with($normalized, 'tutor-applications/')
            && ! str_starts_with($normalized, 'tutor-applications/photos/')
            && ! str_starts_with($normalized, 'tutor-applications/videos/')) {
            $this->abortUnlessStaff($request);

            return;
        }

        $isSensitive = false;
        foreach ($this->sensitivePrefixes() as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                $isSensitive = true;
                break;
            }
        }

        if (! $isSensitive) {
            return;
        }

        $this->abortUnlessStaff($request);
    }

    private function abortUnlessStaff(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(404);
        }

        $role = (string) ($user->role ?? '');
        if (in_array($role, ['super_admin', 'admin', 'employee'], true) || $user->is_employee) {
            return;
        }

        abort(404);
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(string $path): array
    {
        $path = ltrim($path, '/');
        $candidates = [$path];

        if (str_starts_with($path, 'public/')) {
            $candidates[] = substr($path, strlen('public/'));
        } else {
            $candidates[] = 'public/'.$path;
        }

        $dir = dirname($path);
        $name = pathinfo($path, PATHINFO_FILENAME);
        if ($name !== '' && $dir !== '.' && $dir !== '') {
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $candidates[] = $dir.'/'.$name.'.'.$ext;
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * @param  callable(string): string  $mimeFromExtension
     */
    private function serveLocalPublicFile(string $path, callable $mimeFromExtension): ?Response
    {
        $headers = $this->headersFor($mimeFromExtension($path), $path);

        try {
            $contents = Storage::disk('public')->get($path);
            if (is_string($contents) && $contents !== '') {
                return response($contents, 200, $headers);
            }
        } catch (\Throwable) {
        }

        $basePath = storage_path('app/public');
        $filePath = $basePath.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (! @file_exists($filePath) || ! @is_file($filePath)) {
            return null;
        }

        $realPath = @realpath($filePath) ?: $filePath;
        $allowedPath = @realpath($basePath) ?: $basePath;
        $normalizedRealPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realPath);
        $normalizedAllowedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $allowedPath);

        if ($allowedPath === '' || ! str_starts_with($normalizedRealPath, $normalizedAllowedPath) || ! @is_readable($realPath)) {
            return null;
        }

        $mimeType = @mime_content_type($realPath) ?: $mimeFromExtension($realPath);

        return response()->file($realPath, $this->headersFor($mimeType, $realPath));
    }

    /**
     * @param  callable(string): string  $mimeFromExtension
     */
    private function serveCloudFile(string $diskName, string $path, callable $mimeFromExtension): ?Response
    {
        $headers = $this->headersFor($mimeFromExtension($path), $path);

        try {
            $disk = Storage::disk($diskName);
            $contents = $disk->get($path);
            if (is_string($contents) && $contents !== '') {
                return response($contents, 200, $headers);
            }
        } catch (\Throwable $e) {
            Log::debug('Storage cloud get failed', [
                'disk' => $diskName,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $disk = Storage::disk($diskName);
            $stream = $disk->readStream($path);
            if (is_resource($stream)) {
                return response()->stream(function () use ($stream): void {
                    fpassthru($stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }, 200, $headers);
            }
        } catch (\Throwable $e) {
            Log::debug('Storage cloud stream failed', [
                'disk' => $diskName,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $signed = PublicStorageUrl::cloudSignedUrl($diskName, $path);
            if (! is_string($signed) || $signed === '') {
                return null;
            }

            $remote = Http::timeout(25)->withOptions(['http_errors' => false])->get($signed);
            if ($remote->successful() && $remote->body() !== '') {
                return response($remote->body(), 200, $headers);
            }
        } catch (\Throwable $e) {
            Log::debug('Storage signed fetch failed', [
                'disk' => $diskName,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function headersFor(string $mimeType, ?string $filename = null): array
    {
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ];
        if ($mimeType === 'application/pdf' && $filename) {
            $headers['Content-Disposition'] = 'inline; filename="'.basename($filename).'"';
        }

        return $headers;
    }
}
