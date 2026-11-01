<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يمنع تخزين صفحات HTML في كاش المتصفح/البروكسي،
 * حتى تظهر تحديثات Blade فوراً دون مسح كاش يدوي.
 */
class PreventBrowserHtmlCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        $isHtml = $contentType === ''
            || str_contains($contentType, 'text/html')
            || str_contains($contentType, 'application/xhtml+xml');

        if (! $isHtml) {
            return $response;
        }

        // لا تلمس استجابات الملفات/التنزيلات التي قد تُرجع HTML بالخطأ
        if ($response->headers->has('Content-Disposition')) {
            return $response;
        }

        // صفحات التسويق فيها @auth في النافبار — الكاش العام بدون Vary: Cookie
        // يجعل الطالب يرى أزرار «تسجيل دخول / إنشاء حساب» بعد تسجيل الدخول.
        $response->headers->set('Vary', 'Cookie');
        $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->remove('ETag');

        return $response;
    }
}
