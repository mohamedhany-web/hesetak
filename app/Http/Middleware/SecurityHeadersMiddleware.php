<?php

namespace App\Http\Middleware;

use App\Models\LiveSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     * إضافة Security Headers لحماية التطبيق
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            // Security Headers الأساسية (دون CSP مؤقتاً)
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            // Public pages: deny mic/camera so the homepage never prompts for access.
            // Classroom / live rooms: allow same-origin capture for LiveKit.
            $needsAv = $request->routeIs(
                'classroom.*',
                'student.classroom.*',
                'instructor.classroom.*',
                'student.live-sessions.*',
                'instructor.live-sessions.*',
                'admin.live-sessions.*',
            );
            $response->headers->set(
                'Permissions-Policy',
                $needsAv
                    ? 'geolocation=(), microphone=(self), camera=(self), display-capture=(self)'
                    : 'geolocation=(), microphone=(), camera=(), display-capture=()'
            );

            $this->applyContentSecurityPolicy($response, $request);
        } catch (\Throwable $e) {
            // لا نكسر الطلب بالكامل بسبب تعذر إنشاء/تطبيق بعض ترويسات الأمان.
            logger()->warning('SecurityHeadersMiddleware fallback: '.$e->getMessage());
        }

        // Strict Transport Security (HTTPS only)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }

    private function applyContentSecurityPolicy(Response $response, Request $request): void
    {
        // تعطيل CSP عند APP_DEBUG=true و DISABLE_CSP=true (الافتراضي) لتسهيل التطوير المحلي
        $disableCsp = filter_var(env('DISABLE_CSP', true), FILTER_VALIDATE_BOOLEAN);
        if (config('app.debug') && $disableCsp) {
            return;
        }

        $liveHost = '';
        try {
            $liveHost = LiveSetting::getLiveKitHost();
        } catch (\Throwable) {
            $liveHost = '';
        }
        $liveOrigin = $liveHost !== '' ? ' https://'.$liveHost : '';

        // فواتيرك: الإطارات والنماذج والسكربتات الديناميكية من نطاقهم (blob: لمسار احتياطي fetch→Blob على صفحة الدفع)
        $fawaterkCsp = ' https://app.fawaterk.com https://staging.fawaterk.com https://*.fawaterk.com https://fawaterk.com https://www.fawaterk.com https://*.fawaterak.xyz';

        $csp = "default-src 'self'; ".
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: ".
            'https://cdn.tailwindcss.com '.
            'https://cdn.jsdelivr.net '.
            'https://cdnjs.cloudflare.com '.
            'https://unpkg.com '.
            'https://fonts.googleapis.com'.
            $liveOrigin.
            $fawaterkCsp.'; '.
            "style-src 'self' 'unsafe-inline' ".
            'https://fonts.googleapis.com '.
            'https://cdnjs.cloudflare.com '.
            'https://cdn.jsdelivr.net '.
            'https://cdn.tailwindcss.com; '.
            "font-src 'self' data: ".
            'https://fonts.gstatic.com '.
            'https://cdnjs.cloudflare.com '.
            'https://cdn.jsdelivr.net; '.
            "img-src 'self' data: https: blob:; ".
            "media-src 'self' https: blob:; ".
            "connect-src 'self' https: ws: wss:; ".
            "frame-src 'self' ".
            'https://iframe.mediadelivery.net '.
            'https://player.mediadelivery.net '.
            'https://view.officeapps.live.com '.
            $liveOrigin.
            $fawaterkCsp.'; '.
            "object-src 'none'; ".
            "base-uri 'self'; ".
            "form-action 'self'".
            $fawaterkCsp.'; '.
            "worker-src 'self' blob:; ".
            "manifest-src 'self';";

        $response->headers->set('Content-Security-Policy', $csp);
    }
}
