<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PopupAd;
use App\Services\PublicMediaStorage;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PopupAdController extends Controller
{
    public function index(Request $request)
    {
        $ads = PopupAd::orderBy('order')->orderByDesc('created_at')->paginate(15);
        return view('admin.marketing.popup-ads.index', compact('ads'));
    }

    public function create()
    {
        return view('admin.marketing.popup-ads.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:'.config('upload_limits.max_upload_kb'),
            'link_url' => 'nullable|url|max:500',
            'cta_text' => 'nullable|string|max:100',
            'starts_at' => 'required|date',
            'duration_days' => 'required|integer|min:1|max:365',
            'max_views_per_visitor' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        $startsAt = Carbon::parse($validated['starts_at']);
        $endsAt = $startsAt->copy()->addDays((int) $validated['duration_days']);

        $imagePath = $request->hasFile('image')
            ? PublicMediaStorage::store($request->file('image'), 'popup_ads')
            : null;

        PopupAd::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'image' => $imagePath,
            'link_url' => $validated['link_url'] ?? null,
            'cta_text' => $validated['cta_text'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'max_views_per_visitor' => (int) $validated['max_views_per_visitor'],
            'is_active' => $request->boolean('is_active', true),
            'order' => PopupAd::max('order') + 1,
        ]);

        return redirect()->route('admin.popup-ads.index')
            ->with('success', 'تم إنشاء الإعلان بنجاح');
    }

    public function edit(PopupAd $popupAd)
    {
        return view('admin.marketing.popup-ads.edit', compact('popupAd'));
    }

    public function update(Request $request, PopupAd $popupAd)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:'.config('upload_limits.max_upload_kb'),
            'link_url' => 'nullable|url|max:500',
            'cta_text' => 'nullable|string|max:100',
            'starts_at' => 'required|date',
            'duration_days' => 'required|integer|min:1|max:365',
            'max_views_per_visitor' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        $startsAt = Carbon::parse($validated['starts_at']);
        $endsAt = $startsAt->copy()->addDays((int) $validated['duration_days']);

        $data = [
            'title' => $validated['title'],
            'body' => $validated['body'],
            'link_url' => $validated['link_url'] ?? null,
            'cta_text' => $validated['cta_text'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'max_views_per_visitor' => (int) $validated['max_views_per_visitor'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('image')) {
            $data['image'] = PublicMediaStorage::store(
                $request->file('image'),
                'popup_ads',
                $popupAd->image
            );
        }

        $popupAd->update($data);

        return redirect()->route('admin.popup-ads.index')
            ->with('success', 'تم تحديث الإعلان بنجاح');
    }

    public function destroy(PopupAd $popupAd)
    {
        PublicMediaStorage::delete($popupAd->image);
        $popupAd->delete();
        return redirect()->route('admin.popup-ads.index')
            ->with('success', 'تم حذف الإعلان بنجاح');
    }
}
