<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CurriculumLibraryItem;
use App\Models\CurriculumLibraryItemFile;
use App\Models\CurriculumLibraryMaterial;
use App\Models\CurriculumLibraryPreviewOpen;
use App\Models\CurriculumLibrarySection;
use App\Models\CurriculumPresentationDerivative;
use App\Services\CurriculumPresentationViewerService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CurriculumLibraryController extends Controller
{
    public function index(Request $request)
    {
        if (! student_ui('show_libraries')) {
            return redirect()->route('dashboard');
        }

        $user = Auth::user();
        $hasFullAccess = $user && $user->hasCurriculumLibraryAccess();
        $usedFreePreview = $user
            && Schema::hasTable('curriculum_library_preview_opens')
            && CurriculumLibraryPreviewOpen::hasUsedFreePreview($user->id);

        $base = CurriculumLibraryItem::active()
            ->where(function ($q) use ($user) {
                $q->whereNull('curriculum_library_items.category_id')
                    ->orWhereHas('category', fn ($cq) => $cq->accessibleByStudent($user));
            });

        $categoryId = (int) $request->query('category_id', 0);
        $grade = trim((string) $request->query('grade', ''));
        $subject = trim((string) $request->query('subject', ''));
        $searchQuery = trim((string) $request->query('q', ''));

        $query = (clone $base)->with('category')->withCount(['sections', 'files'])->ordered();

        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }
        if ($grade !== '') {
            $query->where('grade_level', $grade);
        }
        if ($subject !== '') {
            $query->where('subject', $subject);
        }
        if ($request->filled('language') && in_array($request->language, ['ar', 'en', 'fr'], true)) {
            $query->byLanguage($request->language);
        }
        if ($searchQuery !== '') {
            $query->where(function ($qry) use ($searchQuery) {
                $qry->where('title', 'like', '%'.$searchQuery.'%')
                    ->orWhere('description', 'like', '%'.$searchQuery.'%')
                    ->orWhere('subject', 'like', '%'.$searchQuery.'%')
                    ->orWhere('grade_level', 'like', '%'.$searchQuery.'%');
            });
        }

        $items = $query->get();
        $categories = \App\Models\CurriculumLibraryCategory::active()
            ->ordered()
            ->accessibleByStudent($user)
            ->get();

        $filterSource = (clone $base)->get(['category_id', 'grade_level', 'subject']);
        $grades = $filterSource->pluck('grade_level')->filter(fn ($v) => filled($v))->unique()->sort()->values();
        $subjects = $filterSource->pluck('subject')->filter(fn ($v) => filled($v))->unique()->sort()->values();

        return view('student.library.curriculum', [
            'items' => $items,
            'grouped' => $this->groupUploadedCurricula($items),
            'categories' => $categories,
            'grades' => $grades,
            'subjects' => $subjects,
            'categoryId' => $categoryId,
            'grade' => $grade,
            'subject' => $subject,
            'searchQuery' => $searchQuery,
            'hasFilters' => $categoryId > 0 || $grade !== '' || $subject !== '' || $searchQuery !== '',
            'hasFullAccess' => $hasFullAccess,
            'usedFreePreview' => $usedFreePreview,
            'packagesUrl' => $this->packagesUpsellUrl(),
            'locale' => app()->getLocale(),
        ]);
    }

    public function show(Request $request, CurriculumLibraryItem $item)
    {
        $user = Auth::user();
        $hasFullAccess = $user && $user->hasCurriculumLibraryAccess();

        if (! $item->is_active) {
            abort(404);
        }

        $item->load('category');

        if (! $item->isAccessibleByViewer($user)) {
            abort(403, 'هذا المنهج غير متاح لحسابك.');
        }

        if (! $hasFullAccess) {
            $usedFreePreview = $user ? CurriculumLibraryPreviewOpen::hasUsedFreePreview($user->id) : false;
            if ($usedFreePreview) {
                return redirect()->to($this->packagesUpsellUrl())
                    ->with('error', 'يمكنك معاينة ملف واحد مجاناً فقط. لفتح باقي المناهج التفاعلية اشترك في باقة تتضمن المكتبات.');
            }
            CurriculumLibraryPreviewOpen::recordFreePreviewUsed($user->id, $item->id);
        }

        $sectionTree = CurriculumLibrarySection::treeForItem($item);
        $item->load(['category', 'files']);

        return view('student.curriculum-library.show', compact('item', 'hasFullAccess', 'sectionTree'));
    }

    public function download(CurriculumLibraryItem $item, CurriculumLibraryItemFile $file)
    {
        if ($file->curriculum_library_item_id !== $item->id) {
            abort(404);
        }
        if (! $item->is_active) {
            abort(404);
        }

        $user = Auth::user();
        if (! $item->isAccessibleByViewer($user)) {
            abort(403, 'هذا المنهج غير متاح لحسابك.');
        }

        if (in_array($file->file_type, ['html', 'presentation'], true)) {
            return back()->with('error', 'هذا النوع من الملفات متاح للعرض داخل المنصة فقط ولا يمكن تحميله.');
        }

        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        $diskName = $file->storage_disk ?: 'public';
        $disk = Storage::disk($diskName);
        $path = $file->path;
        if (! $path || ! $disk->exists($path)) {
            abort(404);
        }

        $filename = $file->label ?: basename($path);

        return $disk->download($path, $filename);
    }

    public function viewHtml(CurriculumLibraryItem $item, CurriculumLibraryItemFile $file)
    {
        if ($file->curriculum_library_item_id !== $item->id) {
            abort(404);
        }
        if (! $item->is_active) {
            abort(404);
        }
        if ($file->file_type !== 'html') {
            abort(404);
        }

        $user = Auth::user();
        if (! $item->isAccessibleByViewer($user)) {
            abort(403, 'هذا المنهج غير متاح لحسابك.');
        }

        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        $diskName = $file->storage_disk ?: 'r2';
        $disk = Storage::disk($diskName);
        if (! $file->path || ! $disk->exists($file->path)) {
            abort(404);
        }

        $html = $disk->get($file->path);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; font-src 'self' data:; media-src 'self' data:; object-src 'none'; base-uri 'none'; frame-ancestors 'self';",
        ]);
    }

    public function viewPdf(CurriculumLibraryItem $item, CurriculumLibraryItemFile $file)
    {
        if ($file->curriculum_library_item_id !== $item->id) {
            abort(404);
        }
        if (! $item->is_active) {
            abort(404);
        }
        if ($file->file_type !== 'pdf') {
            abort(404);
        }

        $user = Auth::user();
        if (! $item->isAccessibleByViewer($user)) {
            abort(403, 'هذا المنهج غير متاح لحسابك.');
        }

        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        $diskName = $file->storage_disk ?: 'public';
        $disk = Storage::disk($diskName);
        if (! $file->path || ! $disk->exists($file->path)) {
            abort(404);
        }

        $filename = $file->label ?: basename($file->path);

        if ($diskName === 'public' || $diskName === 'local') {
            $fullPath = $disk->path($file->path);
            if (! is_file($fullPath)) {
                abort(404);
            }

            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            ]);
        }

        $bin = $disk->get($file->path);

        return response($bin, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
        ]);
    }

    public function viewPresentation(CurriculumLibraryItem $item, CurriculumLibraryItemFile $file)
    {
        $this->assertFilePresentationAccess($item, $file);

        $user = Auth::user();
        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        $diskName = $file->storage_disk ?: 'public';
        $disk = Storage::disk($diskName);
        if (! $file->path || ! $disk->exists($file->path)) {
            abort(404);
        }

        $viewer = app(CurriculumPresentationViewerService::class);
        $payload = $viewer->playerPayloadForFile($item, $file);

        return view('student.curriculum-library.presentation', [
            'item' => $item,
            'itemShowUrl' => $this->manahijItemShowUrl($item),
            'presentationTitle' => $file->label ?: 'عرض تفاعلي (PowerPoint)',
            'mode' => $payload['mode'],
            'manifestUrl' => $payload['manifestUrl'],
            'slideCount' => $payload['slideCount'],
            'slideWidth' => $payload['width'],
            'slideHeight' => $payload['height'],
            'playerConfig' => $payload['playerConfig'],
            'publicUrl' => $payload['publicUrl'],
            'embedUrl' => $payload['embedUrl'],
            'canUseOfficeViewer' => $payload['canUseOfficeViewer'],
            'hasAnimationVideo' => false,
            'animationVideoUrl' => null,
        ]);
    }

    public function downloadMaterial(CurriculumLibraryItem $item, CurriculumLibraryMaterial $material)
    {
        $this->assertMaterialForItem($item, $material);
        if (! $item->is_active || ! $material->is_active) {
            abort(404);
        }

        $user = Auth::user();
        if (! $item->isAccessibleByViewer($user)) {
            abort(403, 'هذا المنهج غير متاح لحسابك.');
        }

        if (! $material->effectiveAllowDownload()) {
            return back()->with('error', 'تحميل هذه المادة غير متاح.');
        }

        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        $diskName = $material->storage_disk ?: 'r2';
        $disk = Storage::disk($diskName);
        if (! $material->path || ! $disk->exists($material->path)) {
            abort(404);
        }

        $filename = $material->displayTitle();

        return $disk->download($material->path, $filename);
    }

    public function viewMaterialHtml(CurriculumLibraryItem $item, CurriculumLibraryMaterial $material)
    {
        $this->assertMaterialForItem($item, $material);
        if (! $item->is_active || ! $material->is_active) {
            abort(404);
        }
        if ($material->file_kind !== 'html') {
            abort(404);
        }

        $user = Auth::user();
        if (! $item->isAccessibleByViewer($user)) {
            abort(403, 'هذا المنهج غير متاح لحسابك.');
        }

        if (! $material->effectiveAllowViewInPlatform()) {
            abort(404);
        }

        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        $diskName = $material->storage_disk ?: 'r2';
        $disk = Storage::disk($diskName);
        if (! $material->path || ! $disk->exists($material->path)) {
            abort(404);
        }

        $html = $disk->get($material->path);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; font-src 'self' data:; media-src 'self' data:; object-src 'none'; base-uri 'none'; frame-ancestors 'self';",
        ]);
    }

    public function viewMaterialPdf(CurriculumLibraryItem $item, CurriculumLibraryMaterial $material)
    {
        $this->assertMaterialForItem($item, $material);
        if (! $item->is_active || ! $material->is_active) {
            abort(404);
        }
        if ($material->file_kind !== 'pdf') {
            abort(404);
        }

        $user = Auth::user();
        if (! $item->isAccessibleByViewer($user)) {
            abort(403, 'هذا المنهج غير متاح لحسابك.');
        }

        if (! $material->effectiveAllowViewInPlatform()) {
            abort(404);
        }

        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        $diskName = $material->storage_disk ?: 'r2';
        $disk = Storage::disk($diskName);
        if (! $material->path || ! $disk->exists($material->path)) {
            abort(404);
        }

        $filename = $material->displayTitle();

        if ($diskName === 'public' || $diskName === 'local') {
            $fullPath = $disk->path($material->path);
            if (! is_file($fullPath)) {
                abort(404);
            }

            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            ]);
        }

        $bin = $disk->get($material->path);

        return response($bin, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
        ]);
    }

    public function viewMaterialPresentation(CurriculumLibraryItem $item, CurriculumLibraryMaterial $material)
    {
        $this->assertMaterialPresentationAccess($item, $material);

        $user = Auth::user();
        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        $diskName = $material->storage_disk ?: 'r2';
        $disk = Storage::disk($diskName);
        if (! $material->path || ! $disk->exists($material->path)) {
            abort(404);
        }

        $viewer = app(CurriculumPresentationViewerService::class);
        $payload = $viewer->playerPayloadForMaterial($item, $material);
        $hasAnimationVideo = $material->hasAnimationVideo();

        return view('student.curriculum-library.presentation', [
            'item' => $item,
            'itemShowUrl' => $this->manahijItemShowUrl($item),
            'presentationTitle' => $material->displayTitle(),
            'mode' => $payload['mode'],
            'manifestUrl' => $payload['manifestUrl'],
            'slideCount' => $payload['slideCount'],
            'slideWidth' => $payload['width'],
            'slideHeight' => $payload['height'],
            'playerConfig' => $payload['playerConfig'],
            'publicUrl' => $payload['publicUrl'],
            'embedUrl' => $payload['embedUrl'],
            'canUseOfficeViewer' => $payload['canUseOfficeViewer'],
            'hasAnimationVideo' => $hasAnimationVideo,
            'animationVideoUrl' => $hasAnimationVideo
                ? route('curriculum-library.material.animation-video', [$item, $material])
                : null,
        ]);
    }

    /**
     * Stream/redirect companion animation video. Same gates as presentation; never listed in catalogs.
     */
    public function viewMaterialAnimationVideo(CurriculumLibraryItem $item, CurriculumLibraryMaterial $material)
    {
        $this->assertMaterialPresentationAccess($item, $material);

        $user = Auth::user();
        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        if (! $material->hasAnimationVideo()) {
            abort(404);
        }

        $diskName = $material->animation_video_disk ?: 'r2';
        $path = $material->animation_video_path;
        $disk = Storage::disk($diskName);

        if (! $path || ! $disk->exists($path)) {
            abort(404);
        }

        $mime = $material->animation_video_mime ?: 'video/mp4';
        $filename = $material->animation_video_original_name
            ?: ('animation-'.basename($path));

        if ($diskName === 'r2') {
            $minutes = max(1, (int) config('curriculum_presentation.animation_video_temp_url_minutes', 20));

            return redirect()->away(
                $disk->temporaryUrl($path, now()->addMinutes($minutes))
            );
        }

        if ($diskName === 'public' || $diskName === 'local') {
            $fullPath = $disk->path($path);
            if (! is_file($fullPath)) {
                abort(404);
            }

            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $bin = $disk->get($path);

        return response($bin, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function viewMaterialSlidesManifest(CurriculumLibraryItem $item, CurriculumLibraryMaterial $material)
    {
        $this->assertMaterialPresentationAccess($item, $material);

        $user = Auth::user();
        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        $viewer = app(CurriculumPresentationViewerService::class);
        $derivative = $viewer->resolveReadyDerivative(
            CurriculumPresentationDerivative::SOURCE_MATERIAL,
            (int) $material->id
        );
        $manifest = $derivative ? $viewer->loadAndValidateManifest($derivative) : null;
        if (! $derivative || ! $manifest) {
            abort(404);
        }

        return response()->json(
            $viewer->sanitizedManifestPayload($item, 'material', $material, $derivative, $manifest)
        );
    }

    public function viewMaterialSlideImage(CurriculumLibraryItem $item, CurriculumLibraryMaterial $material, int $slide): Response
    {
        return $this->streamMaterialSlideAsset($item, $material, $slide, 'image');
    }

    public function viewMaterialSlideThumb(CurriculumLibraryItem $item, CurriculumLibraryMaterial $material, int $slide): Response
    {
        return $this->streamMaterialSlideAsset($item, $material, $slide, 'thumb');
    }

    public function viewFileSlidesManifest(CurriculumLibraryItem $item, CurriculumLibraryItemFile $file)
    {
        $this->assertFilePresentationAccess($item, $file);

        $user = Auth::user();
        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            return $redirect;
        }

        $viewer = app(CurriculumPresentationViewerService::class);
        $derivative = $viewer->resolveReadyDerivative(
            CurriculumPresentationDerivative::SOURCE_FILE,
            (int) $file->id
        );
        $manifest = $derivative ? $viewer->loadAndValidateManifest($derivative) : null;
        if (! $derivative || ! $manifest) {
            abort(404);
        }

        return response()->json(
            $viewer->sanitizedManifestPayload($item, 'file', $file, $derivative, $manifest)
        );
    }

    public function viewFileSlideImage(CurriculumLibraryItem $item, CurriculumLibraryItemFile $file, int $slide): Response
    {
        return $this->streamFileSlideAsset($item, $file, $slide, 'image');
    }

    public function viewFileSlideThumb(CurriculumLibraryItem $item, CurriculumLibraryItemFile $file, int $slide): Response
    {
        return $this->streamFileSlideAsset($item, $file, $slide, 'thumb');
    }

    protected function streamMaterialSlideAsset(
        CurriculumLibraryItem $item,
        CurriculumLibraryMaterial $material,
        int $slide,
        string $assetKind
    ): Response {
        $this->assertMaterialPresentationAccess($item, $material);

        $user = Auth::user();
        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            abort(403, 'يتطلب هذا المحتوى اشتراك مناهج X أو معاينة ضمن نفس المنهج.');
        }

        $viewer = app(CurriculumPresentationViewerService::class);
        $derivative = $viewer->resolveReadyDerivative(
            CurriculumPresentationDerivative::SOURCE_MATERIAL,
            (int) $material->id
        );
        $manifest = $derivative ? $viewer->loadAndValidateManifest($derivative) : null;
        if (! $derivative || ! $manifest) {
            abort(404);
        }

        return $viewer->streamSlideAsset($derivative, $manifest, $slide, $assetKind);
    }

    protected function streamFileSlideAsset(
        CurriculumLibraryItem $item,
        CurriculumLibraryItemFile $file,
        int $slide,
        string $assetKind
    ): Response {
        $this->assertFilePresentationAccess($item, $file);

        $user = Auth::user();
        $redirect = $this->previewOrSubscriptionGate($user, $item);
        if ($redirect) {
            abort(403, 'يتطلب هذا المحتوى اشتراك مناهج X أو معاينة ضمن نفس المنهج.');
        }

        $viewer = app(CurriculumPresentationViewerService::class);
        $derivative = $viewer->resolveReadyDerivative(
            CurriculumPresentationDerivative::SOURCE_FILE,
            (int) $file->id
        );
        $manifest = $derivative ? $viewer->loadAndValidateManifest($derivative) : null;
        if (! $derivative || ! $manifest) {
            abort(404);
        }

        return $viewer->streamSlideAsset($derivative, $manifest, $slide, $assetKind);
    }

    protected function assertMaterialPresentationAccess(CurriculumLibraryItem $item, CurriculumLibraryMaterial $material): void
    {
        $this->assertMaterialForItem($item, $material);
        if (! $item->is_active || ! $material->is_active) {
            abort(404);
        }
        if ($material->file_kind !== 'pptx') {
            abort(404);
        }

        $user = Auth::user();
        if (! $item->isAccessibleByViewer($user)) {
            abort(403, 'هذا المنهج غير متاح لحسابك.');
        }

        if (! $material->effectiveAllowViewInPlatform()) {
            abort(404);
        }
    }

    protected function assertFilePresentationAccess(CurriculumLibraryItem $item, CurriculumLibraryItemFile $file): void
    {
        if ((int) $file->curriculum_library_item_id !== (int) $item->id) {
            abort(404);
        }
        if (! $item->is_active) {
            abort(404);
        }
        if ($file->file_type !== 'presentation') {
            abort(404);
        }

        $user = Auth::user();
        if (! $item->isAccessibleByViewer($user)) {
            abort(403, 'هذا المنهج غير متاح لحسابك.');
        }
    }

    protected function assertMaterialForItem(CurriculumLibraryItem $item, CurriculumLibraryMaterial $material): void
    {
        $material->loadMissing('section');
        if (! $material->section || (int) $material->section->curriculum_library_item_id !== (int) $item->id) {
            abort(404);
        }
    }

    protected function previewOrSubscriptionGate($user, CurriculumLibraryItem $item): ?\Illuminate\Http\RedirectResponse
    {
        $hasFullAccess = $user && $user->hasCurriculumLibraryAccess();
        if ($hasFullAccess) {
            return null;
        }
        $used = $user ? CurriculumLibraryPreviewOpen::where('user_id', $user->id)->first() : null;
        if (! $user || ! $used || (int) $used->curriculum_library_item_id !== (int) $item->id) {
            return redirect()->to($this->packagesUpsellUrl())
                ->with('error', 'يتطلب هذا المحتوى باقة مكتبات نشطة أو معاينة ضمن نفس المنهج.');
        }

        return null;
    }

    protected function manahijItemShowUrl(CurriculumLibraryItem $item): string
    {
        $user = Auth::user();
        if ($user && $user->isAcademyWorkingInstructor() && \Illuminate\Support\Facades\Route::has('instructor.libraries.curriculum.show')) {
            return route('instructor.libraries.curriculum.show', $item);
        }

        return route('curriculum-library.show', $item);
    }

    /**
     * @param  Collection<int, CurriculumLibraryItem>  $items
     * @return Collection<int, array{id: int, name: ?string, order: int, items_count: int, grades: Collection}>
     */
    private function groupUploadedCurricula(Collection $items): Collection
    {
        return $items
            ->groupBy(fn (CurriculumLibraryItem $item) => $item->category_id ?? 0)
            ->map(function (Collection $categoryItems, $categoryKey) {
                $category = $categoryItems->first()?->category;
                $grades = $categoryItems
                    ->groupBy(fn (CurriculumLibraryItem $item) => filled($item->grade_level) ? $item->grade_level : '')
                    ->map(function (Collection $gradeItems, $gradeKey) {
                        return [
                            'name' => $gradeKey !== '' ? (string) $gradeKey : null,
                            'items' => $gradeItems->values(),
                        ];
                    })
                    ->sortBy(fn (array $group) => $group['name'] === null ? 'zzz' : mb_strtolower((string) $group['name']))
                    ->values();

                return [
                    'id' => (int) $categoryKey,
                    'name' => $category?->name,
                    'order' => (int) ($category?->order ?? 9999),
                    'items_count' => $categoryItems->count(),
                    'grades' => $grades,
                ];
            })
            ->sortBy(fn (array $group) => $group['id'] === 0 ? 999999 : $group['order'])
            ->values();
    }

    protected function packagesUpsellUrl(): string
    {
        if (\Illuminate\Support\Facades\Route::has('public.service-packages.index')) {
            return route('public.service-packages.index');
        }
        if (\Illuminate\Support\Facades\Route::has('public.pricing')) {
            return route('public.pricing');
        }

        return route('dashboard');
    }
}
