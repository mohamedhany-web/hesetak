<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SiteService;
use Illuminate\Http\RedirectResponse;

/**
 * صفحات CMS «الخدمات» القديمة — ليست حلقة منتج حصتك.
 */
class SiteServiceController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()
            ->route('public.pricing')
            ->with('info', app()->getLocale() === 'ar'
                ? 'تصفّح باقات الحصص والمسارات التعليمية من صفحة الباقات.'
                : 'Browse session packages and learning paths from Pricing.');
    }

    public function show(SiteService $siteService): RedirectResponse
    {
        return redirect()->route('public.pricing');
    }
}
