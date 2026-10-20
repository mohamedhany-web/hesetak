<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteTestimonial;
use App\Services\SiteTestimonialImageStorage;
use Illuminate\Http\Request;
use Throwable;

class SiteTestimonialController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            if ($u && ($u->hasPermission('manage.site-testimonials') || $u->hasPermission('manage.site-services'))) {
                return $next($request);
            }
            abort(403);
        });
    }

    public function index(Request $request)
    {
        $query = SiteTestimonial::query()->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('id');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('body', 'like', '%'.$s.'%')
                    ->orWhere('author_name', 'like', '%'.$s.'%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $rows = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => SiteTestimonial::count(),
            'active' => SiteTestimonial::where('is_active', true)->count(),
            'inactive' => SiteTestimonial::where('is_active', false)->count(),
            'featured' => SiteTestimonial::where('is_featured', true)->count(),
            'images' => SiteTestimonial::where('content_type', SiteTestimonial::CONTENT_IMAGE)->count(),
        ];

        return view('admin.site-testimonials.index', compact('rows', 'stats'));
    }

    public function create()
    {
        return view('admin.site-testimonials.create');
    }

    public function store(Request $request)
    {
        $type = $request->input('content_type', SiteTestimonial::CONTENT_TEXT);

        $base = [
            'content_type' => 'required|in:text,image',
            'author_name' => ['nullable', 'string', 'max:190', new \App\Rules\NoDemoPlaceholder],
            'role_label' => ['nullable', 'string', 'max:190', new \App\Rules\NoDemoPlaceholder],
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];

        if ($type === SiteTestimonial::CONTENT_TEXT) {
            $validated = $request->validate(array_merge($base, [
                'body' => ['required', 'string', 'max:8000', new \App\Rules\NoDemoPlaceholder],
            ]), [
                'body.required' => 'نص الرأي مطلوب.',
            ]);
            $imagePath = null;
        } else {
            $validated = $request->validate(array_merge($base, [
                'body' => 'nullable|string|max:2000',
                'image' => 'required|image|mimes:jpeg,jpg,png,webp,gif|max:10240',
            ]), [
                'image.required' => 'ارفع صورة الشهادة.',
            ]);
            try {
                $imagePath = SiteTestimonialImageStorage::store($request->file('image'), null);
            } catch (Throwable $e) {
                report($e);

                return back()->withErrors([
                    'image' => 'تعذّر رفع الصورة. تحقق من إعدادات التخزين (R2 أو public).',
                ])->withInput();
            }
        }

        SiteTestimonial::create([
            'content_type' => $validated['content_type'],
            'body' => $validated['body'] ?? null,
            'author_name' => $validated['author_name'] ?? null,
            'role_label' => $validated['role_label'] ?? null,
            'image_path' => $imagePath,
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.site-testimonials.index')
            ->with('success', 'تم إضافة الرأي بنجاح.');
    }

    public function edit(SiteTestimonial $siteTestimonial)
    {
        return view('admin.site-testimonials.edit', compact('siteTestimonial'));
    }

    public function update(Request $request, SiteTestimonial $siteTestimonial)
    {
        $type = $request->input('content_type', $siteTestimonial->content_type);

        $base = [
            'content_type' => 'required|in:text,image',
            'author_name' => ['nullable', 'string', 'max:190', new \App\Rules\NoDemoPlaceholder],
            'role_label' => ['nullable', 'string', 'max:190', new \App\Rules\NoDemoPlaceholder],
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'remove_image' => 'boolean',
        ];

        if ($type === SiteTestimonial::CONTENT_TEXT) {
            $validated = $request->validate(array_merge($base, [
                'body' => ['required', 'string', 'max:8000', new \App\Rules\NoDemoPlaceholder],
            ]), [
                'body.required' => 'نص الرأي مطلوب.',
            ]);
            SiteTestimonialImageStorage::delete($siteTestimonial->image_path);
            $newImagePath = null;
        } else {
            $validated = $request->validate(array_merge($base, [
                'body' => 'nullable|string|max:2000',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:10240',
            ]));

            $newImagePath = $siteTestimonial->image_path;

            if ($request->hasFile('image')) {
                try {
                    $newImagePath = SiteTestimonialImageStorage::store($request->file('image'), $siteTestimonial->image_path);
                } catch (Throwable $e) {
                    report($e);

                    return back()->withErrors(['image' => 'تعذّر رفع الصورة.'])->withInput();
                }
            } elseif ($request->boolean('remove_image')) {
                SiteTestimonialImageStorage::delete($siteTestimonial->image_path);
                $newImagePath = null;
            }

            if ($newImagePath === null || $newImagePath === '') {
                return back()->withErrors(['image' => 'يلزم وجود صورة لنوع «صورة». ارفع صورة جديدة.'])->withInput();
            }
        }

        $siteTestimonial->update([
            'content_type' => $validated['content_type'],
            'body' => $validated['body'] ?? null,
            'author_name' => $validated['author_name'] ?? null,
            'role_label' => $validated['role_label'] ?? null,
            'image_path' => $newImagePath,
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.site-testimonials.index')
            ->with('success', 'تم تحديث الرأي بنجاح.');
    }

    public function destroy(SiteTestimonial $siteTestimonial)
    {
        $siteTestimonial->delete();

        return redirect()->route('admin.site-testimonials.index')
            ->with('success', 'تم حذف الرأي.');
    }
}
