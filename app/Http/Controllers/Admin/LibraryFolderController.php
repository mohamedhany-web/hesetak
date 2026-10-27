<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LibraryFolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LibraryFolderController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin() && ! $user->hasPermission('manage.live-sessions') && ! $user->hasPermission('manage.lectures') && ! $user->hasPermission('manage.courses'))) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(): View
    {
        try {
            $folders = LibraryFolder::query()
                ->with(['academicYear:id,name', 'instructor:id,name'])
                ->withCount([
                    'libraryVideos',
                    'materials',
                ])
                ->ordered()
                ->get();
        } catch (\Throwable $e) {
            // تجنّب 500 من withCount على علاقات اختيارية — أعرض المجلدات أولاً
            report($e);
            $folders = LibraryFolder::query()
                ->with(['academicYear:id,name', 'instructor:id,name'])
                ->ordered()
                ->get();
        }

        return view('admin.libraries.folders.index', compact('folders'));
    }

    public function create(): View
    {
        $instructors = \App\Models\User::query()
            ->whereIn('role', ['instructor', 'teacher'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $years = \Illuminate\Support\Facades\Schema::hasTable('academic_years')
            ? \App\Models\AcademicYear::query()->ordered()->get(['id', 'name'])
            : collect();

        return view('admin.libraries.folders.form', [
            'mode' => 'create',
            'folder' => new LibraryFolder([
                'icon' => 'fas fa-folder',
                'color' => 'blue',
                'sort_order' => 0,
                'is_active' => true,
                'kind' => LibraryFolder::KIND_VIDEOS,
                'content_theme' => \App\Support\FamilyLibraryThemes::GENERAL,
                'requires_library_entitlement' => true,
            ]),
            'instructors' => $instructors,
            'years' => $years,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        LibraryFolder::create($data);

        return redirect()
            ->route('admin.libraries.folders.index')
            ->with('success', 'تم إنشاء المجلد.');
    }

    public function edit(LibraryFolder $folder): View
    {
        $instructors = \App\Models\User::query()
            ->whereIn('role', ['instructor', 'teacher'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $years = \Illuminate\Support\Facades\Schema::hasTable('academic_years')
            ? \App\Models\AcademicYear::query()->ordered()->get(['id', 'name'])
            : collect();

        return view('admin.libraries.folders.form', [
            'mode' => 'edit',
            'folder' => $folder,
            'instructors' => $instructors,
            'years' => $years,
        ]);
    }

    public function update(Request $request, LibraryFolder $folder): RedirectResponse
    {
        $folder->update($this->validated($request, $folder));

        return redirect()
            ->route('admin.libraries.folders.index')
            ->with('success', 'تم تحديث المجلد.');
    }

    public function destroy(LibraryFolder $folder): RedirectResponse
    {
        $folder->recordings()->update(['library_folder_id' => null]);
        $folder->libraryVideos()->update(['library_folder_id' => null]);
        $folder->materials()->update(['library_folder_id' => null]);
        $folder->delete();

        return back()->with('success', 'تم حذف المجلد.');
    }

    private function validated(Request $request, ?LibraryFolder $folder = null): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:140',
                Rule::unique('library_folders', 'slug')->ignore($folder?->id),
            ],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
            'color' => ['required', Rule::in(LibraryFolder::COLORS)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'kind' => ['required', Rule::in([LibraryFolder::KIND_VIDEOS, LibraryFolder::KIND_MATERIALS, LibraryFolder::KIND_BOTH])],
            'content_theme' => ['required', Rule::in(\App\Support\FamilyLibraryThemes::keys())],
            'requires_library_entitlement' => ['nullable', 'boolean'],
        ]);

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($data['name_en'] ?? $data['name_ar']) ?: ('folder-'.Str::random(6));
        }

        $theme = $data['content_theme'] ?? \App\Support\FamilyLibraryThemes::GENERAL;
        $themeMeta = \App\Support\FamilyLibraryThemes::meta($theme);

        return [
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'slug' => $slug,
            'description_ar' => $data['description_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'icon' => $data['icon'] ?: ($themeMeta['icon'] ?? 'fas fa-folder'),
            'color' => $data['color'] ?: ($themeMeta['tone'] ?? 'blue'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
            'instructor_id' => $data['instructor_id'] ?? null,
            'academic_year_id' => $data['academic_year_id'] ?? null,
            'kind' => $data['kind'],
            'content_theme' => $theme,
            'requires_library_entitlement' => $request->boolean('requires_library_entitlement', true),
        ];
    }
}
