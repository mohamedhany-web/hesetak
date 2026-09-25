<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityDataset;
use App\Models\CommunityModel;
use App\Models\ContributorProfile;
use App\Services\PublicMediaStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ContributorController extends Controller
{
    private const DIRECTORY = 'community_datasets';

    /** مجلد رفع ملفات النماذج على نفس القرص (R2 أو local) */
    private const MODELS_DIRECTORY = 'community_models';

    /** امتدادات مسموحة للرفع (ملفات بيانات، أرشيفات، نصوص) — تُخزَّن على Cloudflare R2 عند ضبط FILESYSTEM_DISK_COMMUNITY=r2 */
    public const ALLOWED_EXTENSIONS = 'xlsx,xls,csv,json,txt,zip,pdf,xml,tsv';

    /** امتدادات ملفات النماذج (أوزان، أرشيفات، سكربتات بايثون) — تُخزَّن على Cloudflare R2 */
    public const ALLOWED_MODEL_EXTENSIONS = 'pkl,pt,pth,h5,hdf5,onnx,joblib,zip,safetensors,bin,json,py,pyw,ipynb';

    /** أقصى عدد ملفات في تقديم واحد */
    public const MAX_FILES = 20;

    private static function disk(): string
    {
        return community_disk();
    }

    public function dashboard(): View
    {
        $user = Auth::user();
        $myDatasets = CommunityDataset::where('created_by_user_id', $user->id)->ordered()->get();
        $pending = $myDatasets->where('status', CommunityDataset::STATUS_PENDING)->count();
        $approved = $myDatasets->where('status', CommunityDataset::STATUS_APPROVED)->count();
        $rejected = $myDatasets->where('status', CommunityDataset::STATUS_REJECTED)->count();

        return view('community.contributor.dashboard', [
            'user' => $user,
            'myDatasetsCount' => $myDatasets->count(),
            'pendingCount' => $pending,
            'approvedCount' => $approved,
            'rejectedCount' => $rejected,
            'recentSubmissions' => $myDatasets->take(5),
        ]);
    }

    public function datasets(): View
    {
        $user = Auth::user();
        $datasets = CommunityDataset::where('created_by_user_id', $user->id)->ordered()->paginate(15);
        return view('community.contributor.datasets.index', compact('datasets'));
    }

    public function createDataset(): View
    {
        return view('community.contributor.datasets.create');
    }

    public function storeDataset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:' . implode(',', array_keys(CommunityDataset::CATEGORIES)),
            'file' => 'nullable|file|mimes:xlsx,xls,csv,json,txt,zip,pdf,xml|max:' . config('upload_limits.max_upload_kb'),
            'files' => 'nullable|array|max:' . self::MAX_FILES,
            'files.*' => 'file|mimes:xlsx,xls,csv,json,txt,zip,pdf,xml,tsv|max:' . config('upload_limits.max_upload_kb'),
            'file_url' => 'nullable|url|max:500',
        ], [
            'files.*.mimes' => 'الملفات المسموحة: xlsx, xls, csv, json, txt, zip, pdf, xml, tsv',
            'files.*.max' => 'حجم كل ملف لا يتجاوز ' . round(config('upload_limits.max_upload_kb') / 1024) . ' ميجابايت',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();
        $validated['status'] = CommunityDataset::STATUS_PENDING;
        $validated['is_active'] = false;
        $validated['created_by_user_id'] = Auth::id();

        $uploadedFiles = [];
        $disk = self::disk();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $name = $this->uniqueFilename(self::DIRECTORY, $file->getClientOriginalName());
            $path = $file->storeAs(self::DIRECTORY, $name, $disk);
            $uploadedFiles[] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $this->humanFileSize($file->getSize()),
            ];
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if (!$file->isValid()) {
                    continue;
                }
                $name = $this->uniqueFilename(self::DIRECTORY, $file->getClientOriginalName());
                $path = $file->storeAs(self::DIRECTORY, $name, $disk);
                $uploadedFiles[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $this->humanFileSize($file->getSize()),
                ];
            }
        }

        if (!empty($uploadedFiles)) {
            $validated['files'] = $uploadedFiles;
            $first = $uploadedFiles[0];
            $validated['file_path'] = $first['path'];
            $validated['file_size'] = count($uploadedFiles) > 1
                ? $first['size'] . ' + ' . (count($uploadedFiles) - 1) . ' ملف'
                : $first['size'];
        }

        CommunityDataset::create($validated);
        return redirect()->route('community.contributor.datasets.index')
            ->with('success', 'تم إرسال مجموعة البيانات بنجاح. ستتم مراجعتها من الإدارة قبل النشر.');
    }

    /** حد حجم ملف نموذج واحد (مثلاً 500 ميجا) */
    public const MAX_MODEL_FILE_KB = 512000;

    public function models(): View
    {
        $user = Auth::user();
        $models = CommunityModel::where('created_by_user_id', $user->id)->with('dataset')->ordered()->paginate(15);
        return view('community.contributor.models.index', compact('models'));
    }

    public function createModel(): View
    {
        $datasets = CommunityDataset::approved()->active()->ordered()->get(['id', 'title', 'slug']);
        return view('community.contributor.models.create', compact('datasets'));
    }

    public function storeModel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'methodology_steps' => 'required|string|max:50000',
            'community_dataset_id' => 'nullable|exists:community_datasets,id',
            'performance_metrics' => 'nullable|string|max:3000',
            'license' => 'nullable|string|max:100',
            'usage_instructions' => 'nullable|string|max:10000',
            'file' => 'nullable|file|max:' . self::MAX_MODEL_FILE_KB,
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|max:' . self::MAX_MODEL_FILE_KB,
        ], [
            'methodology_steps.required' => 'شرح الخطوات (المنهجية) مطلوب. اذكر كل خطوة مررت بها من تجهيز البيانات حتى النموذج النهائي.',
            'file.max' => 'حجم الملف لا يتجاوز ' . (self::MAX_MODEL_FILE_KB / 1024) . ' ميجا.',
            'files.*.max' => 'حجم كل ملف لا يتجاوز ' . (self::MAX_MODEL_FILE_KB / 1024) . ' ميجا.',
        ]);

        $exts = array_map('trim', explode(',', self::ALLOWED_MODEL_EXTENSIONS));
        if ($request->hasFile('file')) {
            $ext = strtolower($request->file('file')->getClientOriginalExtension());
            if (!in_array($ext, $exts, true)) {
                return back()->withErrors(['file' => 'امتداد الملف غير مسموح. المسموح: ' . self::ALLOWED_MODEL_EXTENSIONS])->withInput();
            }
        }
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $i => $file) {
                if ($file->isValid()) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (!in_array($ext, $exts, true)) {
                        return back()->withErrors(['files.' . $i => 'امتداد الملف غير مسموح. المسموح: ' . self::ALLOWED_MODEL_EXTENSIONS])->withInput();
                    }
                }
            }
        }

        $hasFile = $request->hasFile('file') || ($request->hasFile('files') && count($request->file('files')) > 0);
        if (!$hasFile) {
            return back()->withErrors(['files' => 'يجب رفع ملف واحد على الأقل للنموذج.'])->withInput();
        }

        $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();
        $validated['status'] = CommunityModel::STATUS_PENDING;
        $validated['is_active'] = false;
        $validated['created_by_user_id'] = Auth::id();

        if (!empty($validated['performance_metrics'])) {
            $decoded = json_decode($validated['performance_metrics'], true);
            $validated['performance_metrics'] = is_array($decoded) ? $decoded : null;
        } else {
            $validated['performance_metrics'] = null;
        }

        $uploadedFiles = [];
        $disk = self::disk();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $name = $this->uniqueFilename(self::MODELS_DIRECTORY, $file->getClientOriginalName());
            $path = $file->storeAs(self::MODELS_DIRECTORY, $name, $disk);
            $uploadedFiles[] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $this->humanFileSize($file->getSize()),
            ];
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if (!$file->isValid()) {
                    continue;
                }
                $name = $this->uniqueFilename(self::MODELS_DIRECTORY, $file->getClientOriginalName());
                $path = $file->storeAs(self::MODELS_DIRECTORY, $name, $disk);
                $uploadedFiles[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $this->humanFileSize($file->getSize()),
                ];
            }
        }

        if (!empty($uploadedFiles)) {
            $validated['files'] = $uploadedFiles;
            $first = $uploadedFiles[0];
            $validated['file_path'] = $first['path'];
            $validated['file_size'] = count($uploadedFiles) > 1
                ? $first['size'] . ' + ' . (count($uploadedFiles) - 1) . ' ملف'
                : $first['size'];
        }

        CommunityModel::create($validated);
        return redirect()->route('community.contributor.models.index')
            ->with('success', 'تم إرسال النموذج بنجاح. ستتم مراجعته من الإدارة قبل النشر في مكتبة النماذج.');
    }

    public function profileEdit(): View
    {
        $user = Auth::user();
        $profile = $user->contributorProfile ?? new ContributorProfile(['user_id' => $user->id]);
        return view('community.contributor.profile.edit', compact('user', 'profile'));
    }

    public function profileStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bio' => 'nullable|string|max:2000',
            'experience' => 'nullable|string|max:3000',
            'linkedin_url' => 'nullable|url|max:500',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.config('upload_limits.max_upload_kb'),
        ], [
            'photo.image' => 'يجب أن يكون الملف صورة (jpeg, png, jpg, webp).',
            'photo.max' => 'حجم الصورة لا يتجاوز ' . round(config('upload_limits.max_upload_kb') / 1024) . ' ميجابايت.',
        ]);

        $user = Auth::user();
        $profile = $user->contributorProfile ?? new ContributorProfile(['user_id' => $user->id]);

        if ($request->hasFile('photo')) {
            $path = PublicMediaStorage::store(
                $request->file('photo'),
                'contributor-profiles',
                $profile->photo_path
            );
            $validated['photo_path'] = $path;
        }

        $validated['status'] = ContributorProfile::STATUS_PENDING;
        $validated['submitted_at'] = now();
        $profile->fill($validated);
        $profile->save();

        return redirect()->route('community.contributor.profile.edit')
            ->with('success', 'تم إرسال نبذتك بنجاح. ستتم مراجعتها من الإدارة قبل ظهورها في صفحة المساهمين.');
    }

    private function uniqueFilename(string $directory, string $originalName): string
    {
        $safe = basename(preg_replace('/[\\\\\/:\*\?"<>|]/', '_', $originalName) ?: 'file');
        if (!Storage::disk(self::disk())->exists($directory . '/' . $safe)) {
            return $safe;
        }
        $ext = pathinfo($safe, PATHINFO_EXTENSION);
        $base = pathinfo($safe, PATHINFO_FILENAME) ?: 'file';
        return $base . '_' . uniqid() . ($ext ? '.' . $ext : '');
    }

    private function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
