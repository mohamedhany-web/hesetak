<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityDataset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class CommunityDatasetController extends Controller
{
    private const DIRECTORY = 'community_datasets';

    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    public function index(): View
    {
        $datasets = CommunityDataset::ordered()->paginate(15);
        return view('admin.community.datasets.index', compact('datasets'));
    }

    public function create(): View
    {
        return view('admin.community.datasets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:xlsx,xls,csv|max:'.config('upload_limits.max_upload_kb'),
            'file_url' => 'nullable|url|max:500',
            'file_size' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);
        $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('file')) {
            $name = $this->uniqueFilenameForDirectory(self::DIRECTORY, $request->file('file')->getClientOriginalName(), null);
            $path = $request->file('file')->storeAs(self::DIRECTORY, $name, community_disk());
            $validated['file_path'] = $path;
            $validated['file_size'] = $this->humanFileSize($request->file('file')->getSize());
        }

        CommunityDataset::create($validated);
        return redirect()->route('admin.community.datasets.index')->with('success', 'تم إنشاء مجموعة البيانات بنجاح.');
    }

    public function edit(CommunityDataset $dataset): View
    {
        return view('admin.community.datasets.edit', compact('dataset'));
    }

    public function update(Request $request, CommunityDataset $dataset): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:xlsx,xls,csv|max:'.config('upload_limits.max_upload_kb'),
            'file_url' => 'nullable|url|max:500',
            'file_size' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('file')) {
            $disk = community_disk();
            if ($dataset->file_path && Storage::disk($disk)->exists($dataset->file_path)) {
                Storage::disk($disk)->delete($dataset->file_path);
            }
            $name = $this->uniqueFilenameForDirectory(self::DIRECTORY, $request->file('file')->getClientOriginalName(), $dataset->file_path ? basename($dataset->file_path) : null);
            $path = $request->file('file')->storeAs(self::DIRECTORY, $name, $disk);
            $validated['file_path'] = $path;
            $validated['file_size'] = $this->humanFileSize($request->file('file')->getSize());
        }

        $dataset->update($validated);
        return redirect()->route('admin.community.datasets.index')->with('success', 'تم تحديث مجموعة البيانات بنجاح.');
    }

    public function destroy(CommunityDataset $dataset): RedirectResponse
    {
        $disk = community_disk();
        if ($dataset->file_path && Storage::disk($disk)->exists($dataset->file_path)) {
            Storage::disk($disk)->delete($dataset->file_path);
        }
        $dataset->delete();
        return redirect()->route('admin.community.datasets.index')->with('success', 'تم حذف مجموعة البيانات.');
    }

    /**
     * اسم ملف آمن مع الحفاظ على الاسم الأصلي. إن وُجد ملف بنفس الاسم يُضاف لاحقة فريدة.
     * عند التحديث، إذا كان الاسم الجديد مطابقاً للملف الحالي يُستخدم كما هو.
     */
    private function uniqueFilenameForDirectory(string $directory, string $originalName, ?string $currentFilename): string
    {
        $safe = basename($originalName);
        $safe = preg_replace('/[\\\\\/:\*\?"<>|]/', '_', $safe) ?: 'file';
        $fullPath = $directory . '/' . $safe;
        $disk = community_disk();

        if ($currentFilename === $safe && Storage::disk($disk)->exists($fullPath)) {
            return $safe;
        }

        if (!Storage::disk($disk)->exists($fullPath)) {
            return $safe;
        }

        $ext = pathinfo($safe, PATHINFO_EXTENSION);
        $base = pathinfo($safe, PATHINFO_FILENAME);
        $base = $base !== '' ? $base : 'file';
        $suffix = '_' . uniqid();
        $safe = $ext !== '' ? $base . $suffix . '.' . $ext : $base . $suffix;

        return $safe;
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
