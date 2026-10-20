<?php

namespace App\Http\Middleware;

use App\Support\ApplicationUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * يضبط جذر روابط Laravel من مسار index.php الفعلي (مثلاً /hesetak/public).
 */
class SetApplicationRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $root = ApplicationUrl::resolveRootUrl($request);
        if ($root !== '') {
            URL::forceRootUrl($root);
            config(['filesystems.disks.public.url' => $root.'/storage']);
        }

        // منع mixed-content: صفحة https مع أصول http عندما APP_URL قديم
        if ($request->isSecure()) {
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
