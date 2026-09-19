<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

/**
 * تقديم صور public/img عبر Laravel — ضروري على Hostinger عندما
 * مسارات /img/* لا تصل إلى index.php.
 */
class PublicImgController extends Controller
{
    /** @var list<string> */
    private const ALLOWED_FOLDERS = ['brand', 'mycourses', 'lasles', 'glottical', 'sanua', 'student-timeline'];

    public function show(string $folder, string $file): Response
    {
        $folder = basename($folder);
        $file = basename($file);

        if (! in_array($folder, self::ALLOWED_FOLDERS, true)) {
            abort(404);
        }

        if (! preg_match('/^[A-Za-z0-9._\-]+$/', $file) || ! preg_match('/\.(png|jpe?g|webp|gif|svg)$/i', $file)) {
            abort(404);
        }

        $path = public_path("img/{$folder}/{$file}");
        if (! is_file($path) || ! is_readable($path)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $types = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
        ];

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => $types[$ext] ?? 'application/octet-stream',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
