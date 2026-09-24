<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Services\AdaptiveLearningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProductTrackCatalogController extends Controller
{
    public function recorded(Request $request): View
    {
        return $this->catalog(
            track: AdaptiveLearningService::TRACK_RECORDED,
            titleAr: 'كورسات مسجّلة',
            titleEn: 'Recorded courses',
            leadAr: 'فيديوهات وشرح بالوتيرة المناسبة — مسار التعلّم الذاتي.',
            leadEn: 'Videos and explanations at your pace — the self-study track.',
            mcActive: 'courses',
            request: $request,
        );
    }

    public function books(Request $request): View
    {
        return $this->catalog(
            track: AdaptiveLearningService::TRACK_BOOK,
            titleAr: 'كتب للقراءة على الموقع',
            titleEn: 'Books to read on-site',
            leadAr: 'مسار الكتب والمراجع — مثالي للتمهيد والمراجعة (upsell للباقات).',
            leadEn: 'Books and references track — ideal for prep and review (package upsell).',
            mcActive: 'courses',
            request: $request,
        );
    }

    protected function catalog(
        string $track,
        string $titleAr,
        string $titleEn,
        string $leadAr,
        string $leadEn,
        string $mcActive,
        Request $request,
    ): View {
        $isRtl = app()->getLocale() === 'ar';
        $courses = collect();

        if (Schema::hasTable('advanced_courses')) {
            $query = AdvancedCourse::query()
                ->where('is_active', true)
                ->with(['instructor:id,name', 'courseCategory:id,name']);

            if (Schema::hasColumn('advanced_courses', 'product_track')) {
                $query->where('product_track', $track);
            } else {
                $query->whereRaw('1 = 0');
            }

            $courses = $query
                ->orderByDesc('is_featured')
                ->orderByDesc('id')
                ->paginate(12)
                ->withQueryString();
        }

        return view('public.product-track-catalog', [
            'mcActive' => $mcActive,
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => ($isRtl ? $titleAr : $titleEn).' — '.__('landing.nav.brand'),
            'pageDescription' => $isRtl ? $leadAr : $leadEn,
            'track' => $track,
            'heroTitle' => $isRtl ? $titleAr : $titleEn,
            'heroLead' => $isRtl ? $leadAr : $leadEn,
            'courses' => $courses,
            'upsellUrl' => route('public.pricing'),
            'libraryUrl' => \Illuminate\Support\Facades\Route::has('public.courses')
                ? route('public.courses')
                : url('/courses'),
        ]);
    }
}
