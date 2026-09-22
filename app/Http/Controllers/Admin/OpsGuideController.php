<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminOpsGuideCatalog;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class OpsGuideController extends Controller
{
    public function index(): View
    {
        $sections = collect(AdminOpsGuideCatalog::sections())->map(function (array $section) {
            $section['items'] = collect($section['items'])->map(function (array $item) {
                $route = $item['route'] ?? null;
                $item['url'] = ($route && Route::has($route)) ? route($route) : null;

                return $item;
            })->all();

            $shot = $section['screenshot'] ?? null;
            $path = $shot ? public_path('img/admin-ops-guide/'.$shot) : null;
            $section['screenshot_url'] = ($path && is_file($path))
                ? asset('img/admin-ops-guide/'.$shot).'?v='.filemtime($path)
                : null;

            return $section;
        })->all();

        return view('admin.ops-guide.index', [
            'sections' => $sections,
            'updatedAt' => now(),
        ]);
    }
}
