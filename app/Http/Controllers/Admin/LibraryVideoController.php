<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\VideoHelper;
use App\Http\Controllers\Controller;
use App\Models\LibraryFolder;
use App\Models\LibraryVideo;
use App\Services\CloudflareR2;
use App\Services\LibraryVideoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * مكتبة فيديو عامة من الإدارة + عرض فيديوهات المعلمين لطلابهم.
 */
class LibraryVideoController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin()
                && ! in_array((string) $user->role, ['admin', 'super_admin'], true)
                && ! $user->hasPermission('manage.live-sessions')
                && ! $user->hasPermission('manage.lectures')
                && ! $user->hasPermission('manage.courses'))) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $query = LibraryVideo::query()
            ->with(['folder:id,name_ar,name_en', 'creator:id,name', 'instructor:id,name'])
            ->ordered();

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('external_url', 'like', "%{$s}%");
            });
        }
        if ($request->filled('folder_id')) {
            if ($request->folder_id === 'none') {
                $query->whereNull('library_folder_id');
            } else {
                $query->where('library_folder_id', (int) $request->folder_id);
            }
        }
        if ($request->filled('published')) {
            $query->where('is_published', $request->published === '1');
        }
        if ($request->filled('audience')) {
            if ($request->audience === 'teacher_students') {
                $query->where('audience', LibraryVideo::AUDIENCE_TEACHER_STUDENTS);
            } elseif ($request->audience === 'general') {
                $query->general();
            }
        }
        if ($request->filled('source')) {
            if ($request->source === 'link') {
                $query->whereNotNull('external_url')->where('external_url', '!=', '');
            } elseif ($request->source === 'file') {
                $query->whereNotNull('file_path')->where('file_path', '!=', '');
            }
        }

        $videos = $query->paginate(25)->withQueryString();

        $stats = [
            'total' => LibraryVideo::query()->count(),
            'published' => LibraryVideo::query()->where('is_published', true)->count(),
            'general' => LibraryVideo::query()->general()->count(),
            'teacher' => LibraryVideo::query()->where('audience', LibraryVideo::AUDIENCE_TEACHER_STUDENTS)->count(),
        ];

        return view('admin.libraries.videos.index', [
            'videos' => $videos,
            'stats' => $stats,
            'folders' => LibraryFolder::query()->ofKind(LibraryFolder::KIND_VIDEOS)->ordered()->get(['id', 'name_ar', 'name_en', 'slug']),
        ]);
    }

    public function create(): View
    {
        return view('admin.libraries.videos.form', [
            'mode' => 'create',
            'video' => new LibraryVideo([
                'is_published' => true,
                'sort_order' => 0,
                'duration_seconds' => 0,
            ]),
            'folders' => LibraryFolder::query()
                ->ofKind(LibraryFolder::KIND_VIDEOS)
                ->whereNull('instructor_id')
                ->active()
                ->ordered()
                ->get(),
            'uploadDisk' => LibraryVideoUploadService::uploadDiskName(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedMeta($request);

        if (empty($data['external_url']) && empty($data['file_path'])) {
            return back()->withInput()->with('error', 'أدخل رابط فيديو أو ارفع ملفاً إلى Cloudflare.');
        }

        if (! empty($data['external_url']) && ! VideoHelper::isValidVideoUrl($data['external_url'])) {
            return back()->withInput()->withErrors(['external_url' => 'الرابط غير مدعوم (YouTube / Vimeo / Bunny / رابط مباشر).']);
        }

        $video = LibraryVideo::create([
            'library_folder_id' => $data['library_folder_id'] ?? null,
            'created_by' => $request->user()->id,
            'audience' => LibraryVideo::AUDIENCE_GENERAL,
            'instructor_id' => null,
            'title' => $data['title'],
            'series_title' => $data['series_title'] ?? null,
            'age_label' => $data['age_label'] ?? null,
            'content_theme' => $data['content_theme'] ?? \App\Support\FamilyLibraryThemes::GENERAL,
            'description' => $data['description'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'file_path' => $data['file_path'] ?? null,
            'storage_disk' => ! empty($data['file_path']) ? ($data['storage_disk'] ?? LibraryVideoUploadService::uploadDiskName()) : null,
            'file_size' => (int) ($data['file_size'] ?? 0),
            'duration_seconds' => (int) ($data['duration_seconds'] ?? 0),
            'mime_type' => $data['mime_type'] ?? null,
            'is_published' => $request->boolean('is_published', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('admin.libraries.videos.index')
            ->with('success', 'تم إضافة فيديو عام #'.$video->id.' (يظهر للطلاب حسب باقة المكتبة).');
    }

    /**
     * معاينة الفيديو للإدارة للتأكد من أنه يعمل قبل ظهوره للطلاب.
     */
    public function watch(LibraryVideo $libraryVideo): View
    {
        $url = $libraryVideo->getUrl();
        $embedUrl = $url ? VideoHelper::getEmbedUrl($url) : null;
        $directUrl = $url && ! $embedUrl ? (VideoHelper::getDirectVideoUrl($url) ?: $url) : null;

        return view('admin.libraries.videos.watch', [
            'video' => $libraryVideo->loadMissing('folder'),
            'embedUrl' => $embedUrl,
            'directUrl' => $directUrl,
        ]);
    }

    public function edit(LibraryVideo $libraryVideo): View
    {
        return view('admin.libraries.videos.form', [
            'mode' => 'edit',
            'video' => $libraryVideo,
            'folders' => LibraryFolder::query()->ofKind(LibraryFolder::KIND_VIDEOS)->ordered()->get(),
            'uploadDisk' => LibraryVideoUploadService::uploadDiskName(),
        ]);
    }

    public function update(Request $request, LibraryVideo $libraryVideo): RedirectResponse
    {
        $data = $this->validatedMeta($request);

        if (empty($data['external_url']) && empty($data['file_path']) && ! $libraryVideo->file_path) {
            return back()->withInput()->with('error', 'أدخل رابط فيديو أو ارفع ملفاً إلى Cloudflare.');
        }

        if (! empty($data['external_url']) && ! VideoHelper::isValidVideoUrl($data['external_url'])) {
            return back()->withInput()->withErrors(['external_url' => 'الرابط غير مدعوم (YouTube / Vimeo / Bunny / رابط مباشر).']);
        }

        $payload = [
            'library_folder_id' => $data['library_folder_id'] ?? null,
            'title' => $data['title'],
            'series_title' => $data['series_title'] ?? null,
            'age_label' => $data['age_label'] ?? null,
            'content_theme' => $data['content_theme'] ?? \App\Support\FamilyLibraryThemes::GENERAL,
            'description' => $data['description'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'duration_seconds' => (int) ($data['duration_seconds'] ?? 0),
            'is_published' => $request->boolean('is_published', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];

        // الإدارة تضيف فيديوهات عامة فقط؛ فيديوهات المعلم تبقى لطلابه
        if (! $libraryVideo->isTeacherPrivate()) {
            $payload['audience'] = LibraryVideo::AUDIENCE_GENERAL;
            $payload['instructor_id'] = null;
        }

        if (! empty($data['file_path'])) {
            if ($libraryVideo->file_path && $libraryVideo->file_path !== $data['file_path']) {
                LibraryVideoUploadService::deleteStored($libraryVideo->file_path, $libraryVideo->storage_disk);
            }
            $payload['file_path'] = $data['file_path'];
            $payload['storage_disk'] = $data['storage_disk'] ?? LibraryVideoUploadService::uploadDiskName();
            $payload['file_size'] = (int) ($data['file_size'] ?? 0);
            $payload['mime_type'] = $data['mime_type'] ?? $libraryVideo->mime_type;
        }

        if ($request->boolean('clear_file') && $libraryVideo->file_path) {
            LibraryVideoUploadService::deleteStored($libraryVideo->file_path, $libraryVideo->storage_disk);
            $payload['file_path'] = null;
            $payload['storage_disk'] = null;
            $payload['file_size'] = 0;
            $payload['mime_type'] = null;
        }

        $libraryVideo->update($payload);

        return redirect()
            ->route('admin.libraries.videos.index')
            ->with('success', 'تم تحديث الفيديو.');
    }

    public function togglePublish(LibraryVideo $libraryVideo): RedirectResponse
    {
        $libraryVideo->update(['is_published' => ! $libraryVideo->is_published]);

        return back()->with('success', $libraryVideo->is_published ? 'تم نشر الفيديو.' : 'تم إلغاء نشر الفيديو.');
    }

    public function destroy(LibraryVideo $libraryVideo): RedirectResponse
    {
        LibraryVideoUploadService::deleteStored($libraryVideo->file_path, $libraryVideo->storage_disk);
        $libraryVideo->delete();

        return back()->with('success', 'تم حذف الفيديو من المكتبة.');
    }

    public function presignUpload(Request $request): JsonResponse
    {
        $request->validate([
            'content_type' => ['nullable', 'string', 'max:191'],
            'filename' => ['nullable', 'string', 'max:255'],
        ]);

        CloudflareR2::ensureBrowserUploadCors([$request->getSchemeAndHttpHost()]);

        return LibraryVideoUploadService::presign(
            (int) $request->user()->id,
            $request->input('content_type'),
            $request->input('filename')
        );
    }

    public function proxyUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'upload_token' => ['required', 'string', 'size:64'],
            'file' => ['required', 'file', 'max:204800'],
        ]);

        return LibraryVideoUploadService::proxy(
            (int) $request->user()->id,
            $validated['upload_token'],
            $request->file('file')
        );
    }

    public function completeUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'upload_token' => ['required', 'string', 'size:64'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        return LibraryVideoUploadService::complete(
            (int) $request->user()->id,
            $validated['upload_token'],
            isset($validated['duration_seconds']) ? (int) $validated['duration_seconds'] : null
        );
    }

    private function validatedMeta(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'content_theme' => ['nullable', Rule::in(\App\Support\FamilyLibraryThemes::keys())],
            'series_title' => ['nullable', 'string', 'max:255'],
            'age_label' => ['nullable', 'string', 'max:40'],
            'library_folder_id' => ['nullable', 'exists:library_folders,id'],
            'external_url' => ['nullable', 'string', 'max:2000'],
            'file_path' => ['nullable', 'string', 'max:1000'],
            'storage_disk' => ['nullable', 'string', 'max:40'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', 'boolean'],
            'clear_file' => ['nullable', 'boolean'],
        ]);
    }
}
