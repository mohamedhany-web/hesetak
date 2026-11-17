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

        $seenId = (int) $request->cookie('popup_ad_seen', 0);
        $query = PopupAd::query()->activeNow();
        if (Schema::hasColumn('popup_ads', 'page_key')) {
            $query->forPage($pageKey);
        }
        $ad = $query->first();
        if ($ad && PublicDemoContent::recordLooksLikeDemo([$ad->title, $ad->body, $ad->cta_text])) {
            return null;
        }
        if ($ad && (int) $ad->id === $seenId) {
            return null;
        }

        return $ad;
    }
}
