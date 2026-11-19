<?php

namespace App\Helpers;

use App\Services\CourseVideoStorage;

class VideoHelper
{
    /**
     * تحويل رابط الفيديو إلى رابط قابل للتضمين (Bunny، YouTube، Vimeo).
     */
    public static function getEmbedUrl($url)
    {
        if (empty($url)) {
            return null;
        }

        $url = trim((string) $url);

        // Bunny.net (Bunny Stream)
        if (preg_match('/(?:iframe|player)\.mediadelivery\.net\/(embed|play)\/(\d+)\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $mode = $matches[1];
            $libraryId = $matches[2];
            $videoId = $matches[3];
            $base = "https://iframe.mediadelivery.net/{$mode}/{$libraryId}/{$videoId}";
            $parsed = parse_url($url);
            $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';

            return $base.$query;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/|youtube-nocookie\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            $origin = rtrim((string) config('app.url'), '/');
            $query = http_build_query([
                'rel' => 0,
                'modestbranding' => 1,
                'playsinline' => 1,
                'iv_load_policy' => 3,
                'fs' => 1,
                'enablejsapi' => 1,
                'origin' => $origin,
            ]);

            // youtube-nocookie يقلل التشتيت ويُبقي التشغيل داخل الصفحة قدر الإمكان
            return 'https://www.youtube-nocookie.com/embed/'.$m[1].'?'.$query;
        }

        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1].'?title=0&byline=0&portrait=0&dnt=1';
        }

        return null;
    }

    /**
     * هل الرابط يحتاج iframe (يوتيوب/فيميو/باني) بدل وسم video؟
     */
    public static function isEmbedSource(?string $url): bool
    {
        return self::getEmbedUrl($url) !== null;
    }

    /**
     * رابط فيديو مباشر (mp4/webm) للتشغيل عبر <video> — يدعم المسارات على R2/local.
     */
    public static function getDirectVideoUrl($url): ?string
    {
        if (empty($url) || self::getEmbedUrl($url)) {
            return null;
        }

        $url = trim((string) $url);

        // مسار نسبي أو /storage/... أو رابط R2 لملف course-videos / tutor-applications
        if (CourseVideoStorage::looksLikeStoredMediaUrl($url)
            || str_starts_with($url, CourseVideoStorage::DIRECTORY.'/')
            || str_starts_with(ltrim($url, '/'), 'tutor-applications/')) {
            if (str_starts_with(ltrim($url, '/'), 'tutor-applications/')) {
                return \App\Services\TutorApplicationStorage::publicUrl($url);
            }
            $resolved = CourseVideoStorage::publicUrl($url);

            return is_string($resolved) && $resolved !== '' ? $resolved : null;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            if (preg_match('/\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i', $url)) {
                $resolved = CourseVideoStorage::publicUrl($url);

                return is_string($resolved) && $resolved !== '' ? $resolved : null;
            }

            return null;
        }

        if (preg_match('/\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i', $url)) {
            return $url;
        }

        return null;
    }

    /**
     * تحديد نوع مصدر الفيديو
     */
    public static function getVideoSource($url)
    {
        if (empty($url)) {
            return 'unknown';
        }

        if (str_contains($url, 'mediadelivery.net')) {
            return 'bunny';
        }
        if (preg_match('/youtube\.com|youtu\.be|youtube-nocookie\.com/', $url)) {
            return 'youtube';
        }
        if (str_contains($url, 'vimeo.com')) {
            return 'vimeo';
        }
        if (self::getDirectVideoUrl($url)) {
            return 'direct';
        }

        return 'other';
    }

    /**
     * الحصول على صورة مصغرة للفيديو
     */
    public static function getThumbnail($url)
    {
        if (empty($url)) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
            $videoId = $matches[1];

            return "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
        }

        return null;
    }

    /**
     * منصة التشغيل الفعلية: تُستنتج من الرابط أولاً (لتصحيح قيم قديمة محفوظة خطأً)، ثم القيمة المخزّنة.
     */
    public static function resolvePlatform(?string $url, ?string $storedPlatform = null): ?string
    {
        $detected = self::getVideoSource($url);
        if (in_array($detected, ['bunny', 'youtube', 'vimeo', 'direct'], true)) {
            return $detected;
        }

        $stored = strtolower(trim((string) $storedPlatform));

        return $stored !== '' ? $stored : null;
    }

    /**
     * التحقق من صحة رابط الفيديو
     */
    public static function isValidVideoUrl($url)
    {
        if (empty($url)) {
            return false;
        }

        return self::getEmbedUrl($url) !== null || self::getDirectVideoUrl($url) !== null;
    }

    /**
     * إنشاء كود HTML لتضمين الفيديو
     */
    public static function generateEmbedHtml($url, $width = '100%', $height = '400px')
    {
        $embedUrl = self::getEmbedUrl($url);
        $direct = self::getDirectVideoUrl($url);

        if ($embedUrl) {
            $safe = e($embedUrl);

            return "<iframe src='{$safe}' width='{$width}' height='{$height}' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen; web-share' allowfullscreen referrerpolicy='strict-origin-when-cross-origin' loading='lazy' class='w-full h-full' style='border: none;'></iframe>";
        }

        if ($direct) {
            $safe = e($direct);

            return "<video width='{$width}' height='{$height}' class='w-full h-full' controls playsinline preload='metadata'><source src='{$safe}' type='video/mp4'>متصفحك لا يدعم تشغيل الفيديو.</video>";
        }

        return '<div class="bg-red-100 text-red-700 p-4 rounded-lg">رابط الفيديو غير صحيح أو غير مدعوم</div>';
    }
}
