<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SiteService;
use Illuminate\View\View;

class SiteServiceController extends Controller
{
    public function index(): View
    {
        $services = SiteService::active()->ordered()->get();

        return view('public.services.index', compact('services'));
    }

    public function show(SiteService $siteService): View
    {
        if (! $siteService->is_active) {
            abort(404);
        }

        $others = SiteService::active()
            ->where('id', '!=', $siteService->id)
            ->ordered()
            ->limit(6)
            ->get();

        return view('public.services.show', compact('siteService', 'others'));
    }
}
