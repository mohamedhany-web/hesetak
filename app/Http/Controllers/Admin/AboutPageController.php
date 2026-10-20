<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PublicAboutSettings;
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

        return view('admin.about.index', [
            'values' => $values,
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
        ]);

        foreach (PublicAboutSettings::editableKeys() as $key) {
            Setting::setValue($key, $validated[$key] ?? null);
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
