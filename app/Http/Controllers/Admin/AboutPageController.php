<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PublicAboutSettings;
use App\Services\PublicMediaStorage;
use App\Services\PublicStorageUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * الإدارة العليا - صفحة من نحن
 */
class AboutPageController extends Controller
{
    public function index(): View
    {
        $defaults = PublicAboutSettings::defaults();
        $stored = Setting::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->all();

        $values = [];
        foreach ($defaults as $key => $default) {
            $values[$key] = array_key_exists($key, $stored) && $stored[$key] !== null && $stored[$key] !== ''
                ? (string) $stored[$key]
                : (string) $default;
        }

        $heroPath = Setting::getValue('home_hero_image_path');
        $heroPreviewUrl = $heroPath ? PublicStorageUrl::fromPath($heroPath) : null;

        return view('admin.about.index', [
            'values' => $values,
            'heroPath' => $heroPath,
            'heroPreviewUrl' => $heroPreviewUrl,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'about_kicker' => ['nullable', 'string', 'max:120'],
            'about_title' => ['required', 'string', 'max:180'],
            'about_lead' => ['required', 'string', 'max:2000'],
            'about_meta_title' => ['nullable', 'string', 'max:180'],
            'about_meta_description' => ['nullable', 'string', 'max:320'],
            'about_section_1_title' => ['nullable', 'string', 'max:180'],
            'about_section_1_body' => ['nullable', 'string', 'max:4000'],
            'about_section_2_title' => ['nullable', 'string', 'max:180'],
            'about_section_2_body' => ['nullable', 'string', 'max:4000'],
            'about_feature_1_title' => ['nullable', 'string', 'max:120'],
            'about_feature_1_body' => ['nullable', 'string', 'max:400'],
            'about_feature_2_title' => ['nullable', 'string', 'max:120'],
            'about_feature_2_body' => ['nullable', 'string', 'max:400'],
            'about_feature_3_title' => ['nullable', 'string', 'max:120'],
            'about_feature_3_body' => ['nullable', 'string', 'max:400'],
            'about_feature_4_title' => ['nullable', 'string', 'max:120'],
            'about_feature_4_body' => ['nullable', 'string', 'max:400'],
            'home_hero_image' => ['nullable', 'image', 'max:'.config('upload_limits.max_upload_kb')],
            'remove_home_hero_image' => ['nullable', 'boolean'],
        ]);

        foreach (PublicAboutSettings::editableKeys() as $key) {
            Setting::setValue($key, $validated[$key] ?? null);
        }

        if ($request->boolean('remove_home_hero_image')) {
            $prev = Setting::getValue('home_hero_image_path');
            if ($prev) {
                PublicMediaStorage::delete($prev);
            }
            Setting::setValue('home_hero_image_path', null);
        } elseif ($request->hasFile('home_hero_image')) {
            $prev = Setting::getValue('home_hero_image_path');
            if ($prev) {
                PublicMediaStorage::delete($prev);
            }
            $path = PublicMediaStorage::store($request->file('home_hero_image'), 'marketing/home-hero');
            Setting::setValue('home_hero_image_path', $path);
        }

        PublicAboutSettings::forgetCache();

        return redirect()
            ->route('admin.about.index')
            ->with('success', 'تم حفظ محتوى صفحة «من نحن».');
    }

    public function viewPublic()
    {
        return redirect()->route('public.about');
    }
}
