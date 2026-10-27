<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminArchitectureManual;
use App\Support\AdminOpsGuideCatalog;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Throwable;

class OpsGuideController extends Controller
{
    public function index(): View
    {
        $sections = collect(AdminOpsGuideCatalog::sections())->map(function (array $section) {
            $section['items'] = collect($section['items'])->map(function (array $item) {
                $route = $item['route'] ?? null;
                $item['url'] = null;
                if ($route && Route::has($route)) {
                    $params = AdminArchitectureManual::defaultRouteParameters()[$route] ?? [];
                    try {
                        $item['url'] = route($route, $params);
                    } catch (Throwable) {
                        $item['url'] = null;
                    }
                }

                return $item;
            })->all();

            $shot = $section['screenshot'] ?? null;
            $path = $shot ? public_path('img/admin-ops-guide/'.$shot) : null;
            $section['screenshot_url'] = ($path && is_file($path))
                ? public_img_url('admin-ops-guide/'.$shot)
                : null;

            return $section;
        })->all();

        return view('admin.ops-guide.index', [
            'sections' => $sections,
            'updatedAt' => now(),
        ]);
    }
}
