<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * حساب جذر التطبيق (APP_URL) من الطلب الفعلي — ضروري عند التشغيل داخل مجلد فرعي على XAMPP،
 * وعند رفع الموقع لسيرفر بـ APP_URL قديم/خاطئ (localhost أو http بدل https).
 */
class ApplicationUrl
{
    public static function resolveRootUrl(?Request $request = null): string
    {
        $request ??= request();
        $configured = rtrim((string) config('app.url'), '/');

        if (! $request) {
            return $configured;
        }

        $requestRoot = self::rootFromRequest($request);

        if ($configured === '') {
            return $requestRoot;
        }

        if (self::configuredMatchesRequest($configured, $request, $requestRoot)) {
            return $configured;
        }

        // APP_URL لا يطابق الدومين/البروتوكول/المجلد الحالي — اعتمد الطلب الحي
        // حتى لا تنكسر روابط CSS/JS والصور بعد الرفع للسيرفر.
        return $requestRoot !== '' ? $requestRoot : $configured;
    }

    public static function rootFromRequest(Request $request): string
    {
        $basePath = self::scriptBasePath($request);
        $host = rtrim($request->getSchemeAndHttpHost(), '/');

        if ($basePath === '' || $basePath === '/') {
            return $host;
        }

        return $host.$basePath;
    }

    /**
     * مسار التطبيق النسبي من SCRIPT_NAME (مثلاً /hesetak/public أو فارغ على الدومين الجذر).
     */
    public static function scriptBasePath(?Request $request = null): string
    {
        $request ??= request();
        if (! $request) {
            return '';
        }

        $script = $request->server('SCRIPT_NAME') ?: $request->getScriptName();
        if (! is_string($script) || $script === '' || ! str_ends_with($script, '/index.php')) {
            return '';
        }

        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '' || $dir === '.') {
            return '';
        }

        return rtrim($dir, '/');
    }

    private static function configuredMatchesRequest(string $configured, Request $request, string $requestRoot): bool
    {
        $configuredHost = parse_url($configured, PHP_URL_HOST);
        $requestHost = $request->getHost();

        if (! is_string($configuredHost) || $configuredHost === '') {
            return false;
        }

        if (strcasecmp($configuredHost, (string) $requestHost) !== 0) {
            return false;
        }

        // localhost / 127.0.0.1 في APP_URL أثناء السيرفر الحقيقي
        if (in_array(strtolower($configuredHost), ['localhost', '127.0.0.1', '::1'], true)
            && ! in_array(strtolower((string) $requestHost), ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        $configuredScheme = parse_url($configured, PHP_URL_SCHEME);
        if (is_string($configuredScheme) && $configuredScheme !== ''
            && strcasecmp($configuredScheme, $request->getScheme()) !== 0) {
            return false;
        }

        $configuredPath = rtrim((string) parse_url($configured, PHP_URL_PATH), '/');
        $requestPath = self::scriptBasePath($request);

        if ($requestPath !== '' && $configuredPath !== $requestPath && ! str_ends_with($configured, $requestPath)) {
            // مجلد فرعي على السيرفر غير موجود في APP_URL
            if ($requestRoot !== '' && ! str_starts_with($configured, $requestRoot)
                && ! str_starts_with($requestRoot, $configured)) {
                return false;
            }
        }

        return true;
    }
}
