<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\FawaterakService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * يجلب سكربت إضافة فواتيرك من خادمهم ويقدّمه من نفس نطاق الموقع.
 * يتفادى حظر تنفيذ سكربتات طرف ثالث (CSP / Safari) رغم نجاح الطلب 200 في Network.
 */
class FawaterkPluginController extends Controller
{
    public function __invoke(): Response
    {
        $upstream = app(FawaterakService::class)->pluginScriptUrl();
        $cacheKey = 'fawaterk.plugin.v1.'.md5($upstream);

        $body = Cache::get($cacheKey);
        if (! is_string($body) || $body === '') {
            try {
                $httpResponse = Http::timeout(60)
                    ->withHeaders([
                        'Accept' => 'application/javascript,text/javascript,*/*;q=0.1',
                        'User-Agent' => 'حصتك/1.0 (Fawaterak plugin proxy)',
                    ])
                    ->get($upstream);
            } catch (\Throwable $e) {
                Log::error('Fawaterk plugin proxy: request failed', [
                    'message' => $e->getMessage(),
                    'upstream' => $upstream,
                ]);

                return $this->jsErrorResponse(503, 'Fawaterk plugin: upstream unreachable');
            }

            if (! $httpResponse->successful()) {
                Log::warning('Fawaterk plugin proxy: upstream HTTP error', [
                    'status' => $httpResponse->status(),
                    'upstream' => $upstream,
                ]);

                return $this->jsErrorResponse(502, 'Fawaterk plugin: upstream HTTP '.$httpResponse->status());
            }

            $body = $httpResponse->body();
            if ($body === '') {
                return $this->jsErrorResponse(502, 'Fawaterk plugin: empty response');
            }

            if (! $this->bodyLooksLikeJavaScript($body)) {
                Log::warning('Fawaterk plugin proxy: upstream body is not JavaScript', [
                    'upstream' => $upstream,
                    'preview' => substr($body, 0, 120),
                ]);

                return $this->jsErrorResponse(502, 'Fawaterk plugin: invalid body from upstream');
            }

            Cache::put($cacheKey, $body, now()->addHours(6));
        }

        if (! $this->bodyLooksLikeJavaScript($body)) {
            Cache::forget($cacheKey);

            return $this->jsErrorResponse(502, 'Fawaterk plugin: cached invalid body');
        }

        return response($body, 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=21600')
            ->header('X-Robots-Tag', 'noindex');
    }

    private function jsErrorResponse(int $status, string $message): Response
    {
        $safe = addcslashes($message, "'\\");

        return response("console.error('{$safe}');", $status)
            ->header('Content-Type', 'application/javascript; charset=UTF-8');
    }

    private function bodyLooksLikeJavaScript(string $body): bool
    {
        $trimmed = ltrim($body, " \t\n\r\0\x0B\xEF\xBB\xBF");
        if ($trimmed === '') {
            return false;
        }

        $lower = strtolower(substr($trimmed, 0, 32));
        if (str_starts_with($lower, '<!doctype') || str_starts_with($lower, '<html') || str_starts_with($lower, '<!--')) {
            return false;
        }
        if (str_starts_with($lower, '<?php') || str_starts_with($lower, '<%')) {
            return false;
        }

        return true;
    }
}
