<?php

namespace App\Services;

use App\Models\PopupAd;
use App\Support\PublicDemoContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PopupAdResolver
{
    public static function forPage(Request $request, string $pageKey): ?PopupAd
    {
        if (! Schema::hasTable('popup_ads')) {
            return null;
        }

        $views = self::viewsFromCookie($request);
        $query = PopupAd::query()->activeNow();
        if (Schema::hasColumn('popup_ads', 'page_key')) {
            $query->forPage($pageKey);
        }

        // أول إعلان نشط لم يستنفد زائر هذا الجهاز مرات ظهوره (max_views_per_visitor).
        foreach ($query->get() as $candidate) {
            if (PublicDemoContent::recordLooksLikeDemo([$candidate->title, $candidate->body, $candidate->cta_text])) {
                continue;
            }
            $max = max(1, (int) ($candidate->max_views_per_visitor ?? 1));
            if (($views[(int) $candidate->id] ?? 0) >= $max) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * @return array<int, int>  [adId => عدد مرات الظهور]
     */
    private static function viewsFromCookie(Request $request): array
    {
        $decoded = json_decode((string) $request->cookie('popup_ad_views', ''), true);
        if (! is_array($decoded)) {
            return [];
        }

        $views = [];
        foreach ($decoded as $id => $count) {
            $views[(int) $id] = max(0, (int) $count);
        }

        return $views;
    }
}
