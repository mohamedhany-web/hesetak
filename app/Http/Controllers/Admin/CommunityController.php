<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CommunityNotificationMail;
use App\Models\CommunityCompetition;
use App\Models\CommunityDataset;
use App\Models\CommunityModel;
use App\Models\ContributorProfile;
use App\Models\User;
use App\Services\Community\DatasetFileReaderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * إدارة مجتمع البيانات والذكاء الاصطناعي — للإدارة العليا فقط.
 * (لوحة المجتمع، مسابقات، مجموعات بيانات، تقديمات، مساهمون، مناقشات، إعدادات)
 */
class CommunityController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    public function dashboard(): View
    {
        $stats = [
            'competitions_count' => CommunityCompetition::count(),
            'competitions_active' => CommunityCompetition::active()->count(),
            'datasets_count' => CommunityDataset::count(),
            'datasets_active' => CommunityDataset::active()->count(),
            'pending_submissions' => CommunityDataset::pending()->count(),
            'models_count' => CommunityModel::count(),
            'models_active' => CommunityModel::approved()->where('is_active', true)->count(),
            'pending_models' => CommunityModel::pending()->count(),
        ];
        $recentCompetitions = CommunityCompetition::ordered()->take(4)->get();
        $recentDatasets = CommunityDataset::approved()->ordered()->take(4)->get();

        return view('admin.community.dashboard', [
            'stats' => $stats,
            'recentCompetitions' => $recentCompetitions,
            'recentDatasets' => $recentDatasets,
        ]);
    }

    public function competitions(): View
    {
        return view('admin.community.coming-soon', ['section' => 'competitions']);
    }

    public function datasets(): View
    {
        return view('admin.community.coming-soon', ['section' => 'datasets']);
    }

    public function submissions(): View
    {
        $pendingDatasets = CommunityDataset::pending()->with('creator')->ordered()->get();
        return view('admin.community.submissions', ['pendingDatasets' => $pendingDatasets]);
    }

    /**
     * عرض تقديم مجموعة بيانات: نفس تجربة الموقع العام (قائمة ملفات + معاينة كسولة).
     */
    public function showSubmission(CommunityDataset $dataset): View
    {
        $dataset->load('creator');
        return view('admin.community.submissions-show', ['dataset' => $dataset]);
    }

    /**
     * تحميل ملف مجموعة البيانات (أول ملف أو التوافق مع الرابط القديم).
     */
    public function downloadSubmission(CommunityDataset $dataset): StreamedResponse
    {
        $list = $dataset->files_list;
        if (!empty($list)) {
            $first = $list[0];
            $path = is_array($first) ? ($first['path'] ?? null) : null;
            $name = is_array($first) ? ($first['original_name'] ?? basename($path)) : basename($path);
            if ($path) {
                $disk = community_disk();
                if (Storage::disk($disk)->exists($path)) {
                    return Storage::disk($disk)->download($path, $name);
                }
            }
        }
        if ($dataset->file_path) {
            $disk = community_disk();
            if (Storage::disk($disk)->exists($dataset->file_path)) {
                return Storage::disk($disk)->download($dataset->file_path, basename($dataset->file_path));
            }
        }
        abort(404);
    }

    /**
     * معاينة بيانات التقديم (JSON) — كما في الموقع العام.
     */
    public function submissionPreview(Request $request, DatasetFileReaderService $reader, CommunityDataset $dataset): JsonResponse
    {
        $disk = community_disk();
        $list = $dataset->files_list;
        $fileIndex = (int) $request->input('file', 0);
        if ($fileIndex < 0 || $fileIndex >= count($list)) {
            $fileIndex = 0;
        }
        $item = $list[$fileIndex] ?? null;
        if (!$item) {
            return response()->json(['headers' => [], 'rows' => []]);
        }
        $pathToRead = $item['path'] ?? null;
        if (!$pathToRead || !Storage::disk($disk)->exists($pathToRead)) {
            return response()->json(['headers' => [], 'rows' => []]);
        }
        $ext = strtolower(pathinfo($pathToRead, PATHINFO_EXTENSION));
        if ($ext === 'zip') {
            $entries = $reader->listZipEntriesFromStorage($disk, $pathToRead);
            return response()->json(['zip' => true, 'entries' => $entries]);
        }
        $preview = $reader->readPreviewFromStorage($disk, $pathToRead);
        return response()->json([
            'headers' => $preview['headers'],
            'rows' => $preview['rows'],
        ]);
    }

    /**
     * تحميل ملف واحد من التقديم بالرقم.
     */
    public function submissionDownloadFile(CommunityDataset $dataset, int $index): StreamedResponse
    {
        $list = $dataset->files_list;
        if ($index < 0 || $index >= count($list)) {
            abort(404);
        }
        $item = $list[$index];
        $path = $item['path'] ?? null;
        $name = $item['original_name'] ?? basename($path);
        if (!$path) {
            abort(404);
        }
        $disk = community_disk();
        if (!Storage::disk($disk)->exists($path)) {
            abort(404);
        }
        return Storage::disk($disk)->download($path, $name);
    }

    /**
     * تحميل جميع ملفات التقديم كأرشيف ZIP.
     */
    public function submissionDownloadAll(CommunityDataset $dataset): StreamedResponse
    {
        $list = $dataset->files_list;
        if (empty($list)) {
            abort(404);
        }
        $disk = community_disk();
        $zipPath = tempnam(sys_get_temp_dir(), 'dataset_zip_') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'تعذر إنشاء الأرشيف');
        }
        foreach ($list as $i => $item) {
            $path = $item['path'] ?? null;
            $name = $item['original_name'] ?? ('file_' . $i);
            if (!$path || !Storage::disk($disk)->exists($path)) {
                continue;
            }
            $content = Storage::disk($disk)->get($path);
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $downloadName = \Illuminate\Support\Str::slug($dataset->title) . '-all.zip';
        try {
            return response()->download($zipPath, $downloadName, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            @unlink($zipPath);
            throw $e;
        }
    }

    /**
     * معاينة ملف داخل أرشيف ZIP في التقديم.
     */
    public function submissionPreviewZipEntry(Request $request, DatasetFileReaderService $reader, CommunityDataset $dataset): JsonResponse
    {
        $disk = community_disk();
        $list = $dataset->files_list;
        $fileIndex = (int) $request->input('file', 0);
        $entryName = $request->input('entry', '');
        if ($fileIndex < 0 || $fileIndex >= count($list) || $entryName === '') {
            return response()->json(['headers' => [], 'rows' => []]);
        }
        $item = $list[$fileIndex] ?? null;
        $path = $item['path'] ?? null;
        if (!$path || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'zip') {
            return response()->json(['headers' => [], 'rows' => []]);
        }
        $preview = $reader->readPreviewFromZipEntry($disk, $path, $entryName);
        return response()->json([
            'headers' => $preview['headers'],
            'rows' => $preview['rows'],
        ]);
    }

    /**
     * محتويات ملف ZIP داخل التقديم.
     */
    public function submissionZipContents(Request $request, DatasetFileReaderService $reader, CommunityDataset $dataset): JsonResponse
    {
        $list = $dataset->files_list;
        $fileIndex = (int) $request->input('file', 0);
        if ($fileIndex < 0 || $fileIndex >= count($list)) {
            return response()->json(['entries' => []]);
        }
        $item = $list[$fileIndex] ?? null;
        $path = $item['path'] ?? null;
        if (!$path || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'zip') {
            return response()->json(['entries' => []]);
        }
        $disk = community_disk();
        $entries = $reader->listZipEntriesFromStorage($disk, $path);
        return response()->json(['entries' => $entries]);
    }

    public function approveDataset(Request $request, CommunityDataset $dataset): RedirectResponse
    {
        if ($dataset->status !== CommunityDataset::STATUS_PENDING) {
            return back()->with('error', 'هذا العنصر تمت مراجعته مسبقاً.');
        }
        $dataset->update(['status' => CommunityDataset::STATUS_APPROVED, 'is_active' => true]);
        return redirect()->route('admin.community.submissions.index')->with('success', 'تمت الموافقة على مجموعة البيانات ونشرها.');
    }

    public function rejectDataset(Request $request, CommunityDataset $dataset): RedirectResponse
    {
        if ($dataset->status !== CommunityDataset::STATUS_PENDING) {
            return back()->with('error', 'هذا العنصر تمت مراجعته مسبقاً.');
        }
        $dataset->update(['status' => CommunityDataset::STATUS_REJECTED]);
        return redirect()->route('admin.community.submissions.index')->with('success', 'تم رفض مجموعة البيانات.');
    }

    /** قائمة تقديمات النماذج المعلقة (Model Zoo) */
    public function modelSubmissions(): View
    {
        $pendingModels = CommunityModel::pending()->with(['creator', 'dataset'])->ordered()->get();
        return view('admin.community.submissions-models', ['pendingModels' => $pendingModels]);
    }

    /** عرض تقديم نموذج واحد — المنهجية، الأداء، الملفات، موافقة/رفض */
    public function showModelSubmission(CommunityModel $community_model): View
    {
        $community_model->load(['creator', 'dataset']);
        return view('admin.community.submissions-model-show', ['model' => $community_model]);
    }

    /** الموافقة على نموذج ونشره في مكتبة النماذج */
    public function approveModel(Request $request, CommunityModel $community_model): RedirectResponse
    {
        if ($community_model->status !== CommunityModel::STATUS_PENDING) {
            return back()->with('error', 'هذا النموذج تمت مراجعته مسبقاً.');
        }
        $community_model->update(['status' => CommunityModel::STATUS_APPROVED, 'is_active' => true]);
        return redirect()->route('admin.community.submissions.models.index')->with('success', 'تمت الموافقة على النموذج ونشره في مكتبة النماذج.');
    }

    /** رفض نموذج */
    public function rejectModel(Request $request, CommunityModel $community_model): RedirectResponse
    {
        if ($community_model->status !== CommunityModel::STATUS_PENDING) {
            return back()->with('error', 'هذا النموذج تمت مراجعته مسبقاً.');
        }
        $community_model->update(['status' => CommunityModel::STATUS_REJECTED]);
        return redirect()->route('admin.community.submissions.models.index')->with('success', 'تم رفض النموذج.');
    }

    public function contributors(): View
    {
        $contributorsData = User::where('community_contributor_type', User::COMMUNITY_CONTRIBUTOR_TYPE_DATA)->orderBy('name')->get();
        $contributorsAi = User::where('community_contributor_type', User::COMMUNITY_CONTRIBUTOR_TYPE_AI)->orderBy('name')->get();
        $pendingProfiles = ContributorProfile::pending()->with('user')->orderBy('submitted_at', 'desc')->get();
        return view('admin.community.contributors', [
            'contributorsData' => $contributorsData,
            'contributorsAi' => $contributorsAi,
            'pendingProfiles' => $pendingProfiles,
        ]);
    }

    /**
     * الموافقة على ملف مساهم لعرضه في صفحة المساهمين.
     */
    public function approveContributorProfile(ContributorProfile $profile): RedirectResponse
    {
        if ($profile->status !== ContributorProfile::STATUS_PENDING) {
            return back()->with('error', 'هذا الملف تمت مراجعته مسبقاً.');
        }
        $profile->update([
            'status' => ContributorProfile::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);
        return redirect()->route('admin.community.contributors.index')
            ->with('success', 'تمت الموافقة على ملف المساهم: ' . ($profile->user->name ?? '—'));
    }

    /**
     * رفض ملف مساهم.
     */
    public function rejectContributorProfile(ContributorProfile $profile): RedirectResponse
    {
        if ($profile->status !== ContributorProfile::STATUS_PENDING) {
            return back()->with('error', 'هذا الملف تمت مراجعته مسبقاً.');
        }
        $profile->update([
            'status' => ContributorProfile::STATUS_REJECTED,
            'reviewed_at' => now(),
        ]);
        return redirect()->route('admin.community.contributors.index')
            ->with('success', 'تم رفض ملف المساهم.');
    }

    public function addContributor(Request $request): RedirectResponse
    {
        $request->validate([
            'contributor_type' => 'required|in:data,ai',
            'email' => 'required_without:user_id|nullable|email|exists:users,email',
            'user_id' => 'required_without:email|nullable|exists:users,id',
        ], [
            'contributor_type.required' => 'اختر نوع المساهم: مجتمع البيانات أو الذكاء الاصطناعي.',
            'contributor_type.in' => 'نوع المساهم غير صالح.',
            'email.required_without' => 'أدخل البريد الإلكتروني أو اختر المستخدم.',
            'email.exists' => 'لا يوجد حساب بهذا البريد.',
            'user_id.exists' => 'المستخدم غير موجود.',
        ]);

        if (filled($request->user_id)) {
            $user = User::findOrFail($request->user_id);
        } else {
            $user = User::where('email', $request->email)->firstOrFail();
        }

        $type = $request->contributor_type;
        $user->update([
            'is_community_contributor' => true,
            'community_contributor_type' => $type,
        ]);
        $label = $type === User::COMMUNITY_CONTRIBUTOR_TYPE_DATA ? 'مجتمع البيانات' : 'الذكاء الاصطناعي';
        return redirect()->route('admin.community.contributors.index')->with('success', 'تمت إضافة المساهم (' . $label . '): ' . $user->name);
    }

    public function removeContributor(User $user): RedirectResponse
    {
        $user->update([
            'is_community_contributor' => false,
            'community_contributor_type' => null,
        ]);
        return redirect()->route('admin.community.contributors.index')->with('success', 'تمت إزالة صلاحية المساهم.');
    }

    /**
     * إنشاء حساب مساهم جديد (مستخدم جديد بصلاحية مساهم فقط).
     */
    public function storeNewContributor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'phone' => 'nullable|string|max:50',
            'contributor_type' => 'required|in:data,ai',
        ], [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.unique' => 'هذا البريد مسجل مسبقاً.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'contributor_type.required' => 'اختر نوع المساهم.',
            'contributor_type.in' => 'نوع المساهم غير صالح.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'is_community_contributor' => true,
            'community_contributor_type' => $validated['contributor_type'],
            'is_active' => true,
        ]);

        return redirect()->route('admin.community.contributors.index')
            ->with('success', 'تم إنشاء حساب المساهم: ' . $user->name . ' — يمكنه تسجيل الدخول من صفحة المجتمع.');
    }

    public function discussions(): View
    {
        return view('admin.community.coming-soon', ['section' => 'discussions']);
    }

    public function settings(): View
    {
        return view('admin.community.coming-soon', ['section' => 'settings']);
    }

    /**
     * صفحة إرسال الإشعارات للمجتمع (بريد Gmail يصل للمساهمين).
     */
    public function notificationsForm(): View
    {
        $contributorsCount = User::where('is_community_contributor', true)->where('is_active', true)->count();
        return view('admin.community.notifications', ['contributorsCount' => $contributorsCount]);
    }

    /**
     * إرسال الإشعار بالبريد: إما لمساهمي المجتمع فقط أو لشخص/قائمة معينة.
     */
    public function sendNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audience' => 'required|in:contributors,specific',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:10000',
            'emails' => 'required_if:audience,specific|nullable|string|max:5000',
        ], [
            'audience.required' => 'اختر جهة الإرسال.',
            'subject.required' => 'عنوان الإشعار مطلوب.',
            'body.required' => 'نص الإشعار مطلوب.',
            'emails.required_if' => 'أدخل بريداً واحداً على الأقل عند الإرسال لشخص معين.',
        ]);

        $recipients = collect();

        if ($validated['audience'] === 'contributors') {
            $recipients = User::where('is_community_contributor', true)->where('is_active', true)->get();
            if ($recipients->isEmpty()) {
                return back()->with('error', 'لا يوجد مساهمون نشطون لإرسال الإشعار لهم.')->withInput();
            }
        } else {
            $emailsRaw = preg_replace('/[\s,،]+/', "\n", $validated['emails'] ?? '');
            $emails = array_unique(array_filter(array_map('trim', explode("\n", $emailsRaw))));
            $validEmails = array_filter($emails, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
            if (empty($validEmails)) {
                return back()->withErrors(['emails' => 'أدخل بريداً إلكترونياً صالحاً واحداً على الأقل.'])->withInput();
            }
            foreach ($validEmails as $email) {
                $user = User::where('email', $email)->first();
                $recipients->push((object) [
                    'email' => $email,
                    'name' => $user?->name,
                ]);
            }
        }

        $sent = 0;
        foreach ($recipients as $r) {
            $email = $r->email ?? null;
            $name = $r->name ?? null;
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            try {
                Mail::to($email)->send(new CommunityNotificationMail(
                    $validated['subject'],
                    $validated['body'],
                    $name
                ));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $message = $validated['audience'] === 'contributors'
            ? 'تم إرسال الإشعار إلى ' . $sent . ' مساهم عبر البريد الإلكتروني.'
            : 'تم إرسال الإشعار إلى ' . $sent . ' مستلم عبر البريد الإلكتروني.';

        return redirect()->route('admin.community.notifications.index')->with('success', $message);
    }
}
