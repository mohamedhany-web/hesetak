<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\LiveRecording;
use App\Models\LiveServer;
use App\Models\LiveSession;
use App\Models\LiveSessionReport;
use App\Models\IntegrationSetting;
use App\Models\LiveSetting;
use App\Models\SessionAttendance;
use App\Support\AppTimezone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LiveSessionController extends Controller
{
    public function index(Request $request)
    {
        $instructorId = auth()->id();

        // حصتك: المدرب لا ينشئ بثاً مستقلاً — هنا تظهر جلساته 1:1 فقط
        $query = \App\Models\OneToOneSession::query()
            ->where('instructor_id', $instructorId)
            ->with(['course:id,title', 'student:id,name', 'classroomMeeting']);

        $status = $request->query('status');
        if ($status === 'live' || $status === 'scheduled') {
            $query->where('status', \App\Models\OneToOneSession::STATUS_SCHEDULED)
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>=', now()->subMinutes(15));
        } elseif ($status === 'pending') {
            $query->where('status', \App\Models\OneToOneSession::STATUS_PENDING);
        } elseif ($status === 'ended' || $status === 'completed') {
            $query->where('status', \App\Models\OneToOneSession::STATUS_COMPLETED);
        }

        $sessions = $query
            ->orderByRaw("CASE status WHEN 'pending_schedule' THEN 0 WHEN 'scheduled' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END")
            ->orderBy('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        $base = \App\Models\OneToOneSession::query()->where('instructor_id', $instructorId);
        $stats = [
            'total' => (clone $base)->count(),
            'live' => (clone $base)
                ->where('status', \App\Models\OneToOneSession::STATUS_SCHEDULED)
                ->whereNotNull('scheduled_at')
                ->whereBetween('scheduled_at', [now()->subMinutes(15), now()->addMinutes(90)])
                ->count(),
            'scheduled' => (clone $base)
                ->where('status', \App\Models\OneToOneSession::STATUS_SCHEDULED)
                ->where(function ($q) {
                    $q->whereNull('scheduled_at')->orWhere('scheduled_at', '>=', now());
                })
                ->count(),
            'ended' => (clone $base)->where('status', \App\Models\OneToOneSession::STATUS_COMPLETED)->count(),
            'pending' => (clone $base)->where('status', \App\Models\OneToOneSession::STATUS_PENDING)->count(),
        ];

        return view('instructor.live-sessions.index', compact('sessions', 'stats'));
    }

    public function create()
    {
        return redirect()
            ->route('instructor.live-sessions.index')
            ->with('info', app()->getLocale() === 'ar'
                ? 'إنشاء بث مستقل غير متاح — جلساتك تظهر من الحصص الخاصة (1:1).'
                : 'Creating standalone broadcasts is disabled — your 1:1 sessions appear here.');
    }

    public function store(Request $request)
    {
        return redirect()
            ->route('instructor.live-sessions.index')
            ->with('error', app()->getLocale() === 'ar'
                ? 'لا يمكن إنشاء جلسة بث مباشرة من لوحة المدرب.'
                : 'Instructors cannot create standalone live sessions.');
    }

    public function show(LiveSession $liveSession)
    {
        if ($liveSession->instructor_id !== auth()->id()) {
            abort(403);
        }

        $liveSession->load(['course', 'recordings']);
        $attendees = $liveSession->attendance()->with('user')->orderByDesc('joined_at')->get();

        return view('instructor.live-sessions.show', compact('liveSession', 'attendees'));
    }

    public function start(LiveSession $liveSession)
    {
        if ($liveSession->instructor_id !== auth()->id()) {
            abort(403);
        }
        if (! $liveSession->isScheduled()) {
            return back()->with('error', 'لا يمكن بدء هذه الجلسة — الحالة الحالية: '.$liveSession->status);
        }

        $liveSession->start();

        SessionAttendance::create([
            'session_id' => $liveSession->id,
            'user_id' => auth()->id(),
            'joined_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'role_in_session' => 'instructor',
        ]);

        return redirect()->route('instructor.live-sessions.room', $liveSession);
    }

    public function room(LiveSession $liveSession)
    {
        if ($liveSession->instructor_id !== auth()->id()) {
            abort(403);
        }
        if (! $liveSession->isLive()) {
            return redirect()->route('instructor.live-sessions.show', $liveSession)
                ->with('info', 'الجلسة ليست في وضع البث');
        }

        $user = auth()->user();
        $subscriptionFeatureMenuItems = [];
        $subscriptionPackageLabel = null;
        $meeting = app(\App\Services\LiveMeetingProvider::class)->roomPayload($liveSession, $user, true);

        return view('instructor.live-sessions.room', array_merge([
            'liveSession' => $liveSession,
            'user' => $user,
            'subscriptionFeatureMenuItems' => $subscriptionFeatureMenuItems,
            'subscriptionPackageLabel' => $subscriptionPackageLabel,
        ], $meeting));
    }

    public function updateStudentWhiteboard(Request $request, LiveSession $liveSession)
    {
        if ($liveSession->instructor_id !== auth()->id()) {
            abort(403);
        }
        if (! $liveSession->isLive()) {
            return response()->json(['message' => 'الجلسة ليست في وضع البث حالياً.'], 422);
        }

        $validated = $request->validate([
            'allow' => ['required', 'boolean'],
        ]);

        $settings = $liveSession->settings ?? [];
        $settings['allow_student_whiteboard'] = $validated['allow'];
        $liveSession->update(['settings' => $settings]);
        $liveSession->refresh();

        return response()->json([
            'ok' => true,
            'allow_student_whiteboard' => $liveSession->allowsStudentWhiteboard(),
        ]);
    }

    public function shareAnnotations(LiveSession $liveSession)
    {
        if ($liveSession->instructor_id !== auth()->id()) {
            abort(403);
        }
        if (! $liveSession->isLive()) {
            return response()->json(['layers' => []]);
        }

        $layers = Cache::get('mx_share_ann_live_'.$liveSession->id, []);

        return response()->json(['layers' => $layers]);
    }

    public function end(LiveSession $liveSession)
    {
        if ($liveSession->instructor_id !== auth()->id()) {
            abort(403);
        }

        $attendance = SessionAttendance::where('session_id', $liveSession->id)
            ->where('user_id', auth()->id())
            ->whereNull('left_at')
            ->first();
        $attendance?->markLeft();

        $liveSession->end();

        return redirect()->route('instructor.live-sessions.show', $liveSession)
            ->with('success', 'تم إنهاء جلسة البث');
    }

    /**
     * تسجيل مغادرة المدرب من الغرفة دون إنهاء الجلسة فوراً —
     * أمر live:auto-end-sessions ينهي الجلسة بعد idle_end_minutes إن لم يعد.
     */
    public function leavePresence(LiveSession $liveSession)
    {
        if ($liveSession->instructor_id !== auth()->id()) {
            abort(403);
        }

        if (! $liveSession->isLive()) {
            return response()->json(['ok' => true, 'status' => $liveSession->status]);
        }

        SessionAttendance::where('session_id', $liveSession->id)
            ->where('user_id', auth()->id())
            ->where('role_in_session', 'instructor')
            ->whereNull('left_at')
            ->each(fn ($attendance) => $attendance->markLeft());

        return response()->json(['ok' => true]);
    }

    /**
     * يطلق إنشاء تقرير ذكاء اصطناعي للجلسة الحالية عبر n8n.
     * يُستدعى من واجهة المعلم داخل غرفة البث أو صفحة الجلسة.
     */
    public function generateAiReport(Request $request, LiveSession $liveSession)
    {
        if ($liveSession->instructor_id !== auth()->id()) {
            abort(403);
        }

        if (! $liveSession->isEnded() && ! $liveSession->isLive()) {
            return back()->with('error', 'لا يمكن إنشاء تقرير لهذه الجلسة في وضعها الحالي.');
        }

        $existing = LiveSessionReport::where('live_session_id', $liveSession->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existing) {
            return back()->with('info', 'هناك طلب تقرير قيد المعالجة بالفعل لهذه الجلسة.');
        }

        $recording = LiveRecording::where('session_id', $liveSession->id)
            ->whereIn('status', ['ready', 'processing'])
            ->latest('id')
            ->first();

        $report = LiveSessionReport::create([
            'live_session_id' => $liveSession->id,
            'instructor_id' => auth()->id(),
            'live_recording_id' => $recording?->id,
            'title' => 'تقرير الجلسة - '.$liveSession->title,
            'status' => 'pending',
        ]);

        $recordingUrl = $recording?->getUrl();
        $callbackUrl = url('/api/n8n/live-session-reports/'.$report->id);

        $webhookUrl = IntegrationSetting::get('n8n_live_session_report_webhook', config('services.n8n.live_session_report_webhook'));
        $token = IntegrationSetting::get('n8n_token', config('services.n8n.token'));

        if (! $webhookUrl || ! $token) {
            return back()->with('error', 'إعدادات تكامل n8n غير مكتملة. تواصل مع مدير النظام.');
        }

        try {
            $response = Http::timeout(45)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-N8N-Token' => $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post($webhookUrl, [
                    'report_id' => $report->id,
                    'live_session_id' => $liveSession->id,
                    'instructor_id' => auth()->id(),
                    'live_recording_id' => $recording?->id,
                    'title' => $report->title,
                    'live_session_title' => $liveSession->title,
                    'live_session_status' => $liveSession->status,
                    'recording' => [
                        'id' => $recording?->id,
                        'file_path' => $recording?->file_path,
                        'storage_disk' => $recording?->storage_disk,
                        'external_url' => $recording?->external_url,
                        'duration_seconds' => $recording?->duration_seconds,
                        'file_size' => $recording?->file_size,
                        'status' => $recording?->status,
                        'download_url' => $recordingUrl,
                    ],
                    'callback' => [
                        'url' => $callbackUrl,
                        'method' => 'PATCH',
                        'header' => 'X-N8N-Token',
                    ],
                ]);

            if ($response->successful()) {
                $executionId = $response->json('execution_id');
                if ($executionId) {
                    $report->update([
                        'n8n_execution_id' => $executionId,
                        'status' => 'processing',
                    ]);
                } else {
                    $report->update(['status' => 'processing']);
                }
            } else {
                $report->update(['status' => 'failed']);

                Log::warning('n8n live-session report webhook failed', [
                    'live_session_id' => $liveSession->id,
                    'report_id' => $report->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return back()->with('error', 'تعذر إرسال الطلب إلى n8n. تحقق من رابط الـ Webhook والتوكن في إعدادات n8n داخل لوحة التحكم.');
            }
        } catch (\Throwable $e) {
            $report->update(['status' => 'failed']);

            Log::error('n8n live-session report webhook exception', [
                'live_session_id' => $liveSession->id,
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'حدث خطأ أثناء الاتصال بخدمة التقارير. الرجاء المحاولة لاحقاً.');
        }

        return back()->with('success', 'تم إرسال طلب إنشاء التقرير، جاري المعالجة عبر n8n.');
    }

    /**
     * تجهيز رابط رفع مباشر للتسجيل الصوتي المنفصل إلى Cloudflare R2.
     */
    public function presignAudioUpload(Request $request, LiveSession $liveSession)
    {
        if ($liveSession->instructor_id !== auth()->id()) {
            abort(403);
        }

        if (! $liveSession->isLive()) {
            return response()->json(['message' => 'الجلسة ليست في وضع البث.'], 422);
        }

        $disk = Storage::disk('live_recordings_r2');
        if (! $disk->providesTemporaryUploadUrls()) {
            return response()->json([
                'direct_upload' => false,
                'message' => 'التخزين الحالي لا يدعم الرفع المباشر. تحقق من إعدادات R2.',
            ], 503);
        }

        $validated = $request->validate([
            'content_type' => ['nullable', 'string', 'max:191'],
        ]);

        $mime = $this->normalizeAudioMime((string) ($validated['content_type'] ?? 'audio/webm'));
        $ext = $this->mimeToAudioExt($mime);
        $directory = 'live-session-audio/'.now()->format('Y/m');
        $fileName = sprintf(
            'session-%d-audio-%s-%s.%s',
            $liveSession->id,
            now()->format('Ymd-His'),
            Str::lower(Str::random(8)),
            $ext
        );
        $path = $directory.'/'.$fileName;

        $token = Str::random(64);
        Cache::put(
            'live_session_audio_presign:'.$token,
            [
                'path' => $path,
                'session_id' => $liveSession->id,
                'user_id' => auth()->id(),
                'mime' => $mime,
            ],
            now()->addMinutes(90)
        );

        try {
            $signed = $disk->temporaryUploadUrl(
                $path,
                now()->addMinutes(75),
                ['ContentType' => $mime]
            );
        } catch (\Throwable $e) {
            Cache::forget('live_session_audio_presign:'.$token);

            return response()->json([
                'direct_upload' => false,
                'message' => 'تعذر تجهيز رابط الرفع إلى التخزين السحابي.',
            ], 503);
        }

        return response()->json([
            'direct_upload' => true,
            'upload_url' => $signed['url'],
            'upload_token' => $token,
            'content_type' => $mime,
            'headers' => $signed['headers'] ?? [],
        ]);
    }

    /**
     * إنهاء رفع التسجيل الصوتي وإنشاء سجل منفصل في live_recordings.
     */
    public function completeAudioUpload(Request $request, LiveSession $liveSession)
    {
        if ($liveSession->instructor_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'upload_token' => ['required', 'string', 'size:64'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:43200'],
        ]);

        $payload = Cache::pull('live_session_audio_presign:'.$validated['upload_token']);
        if (! is_array($payload)
            || (int) ($payload['session_id'] ?? 0) !== (int) $liveSession->id
            || (int) ($payload['user_id'] ?? 0) !== (int) auth()->id()) {
            return response()->json([
                'message' => 'انتهت صلاحية رابط الرفع أو أنه غير صالح.',
            ], 422);
        }

        $path = (string) ($payload['path'] ?? '');
        if ($path === '' || str_contains($path, '..')) {
            return response()->json(['message' => 'مسار التخزين غير صالح.'], 422);
        }

        $disk = Storage::disk('live_recordings_r2');
        if (! $disk->exists($path)) {
            return response()->json([
                'message' => 'الملف غير ظاهر على التخزين بعد. أعد المحاولة بعد ثوانٍ.',
            ], 422);
        }

        $size = (int) $disk->size($path);
        if ($size <= 0) {
            return response()->json(['message' => 'ملف الصوت فارغ.'], 422);
        }

        $maxBytes = 2147483648;
        if ($size > $maxBytes) {
            try {
                $disk->delete($path);
            } catch (\Throwable $e) {
            }

            return response()->json(['message' => 'حجم الملف يتجاوز الحد المسموح (٢ جيجابايت).'], 422);
        }

        $recording = LiveRecording::firstOrNew([
            'session_id' => $liveSession->id,
            'file_path' => $path,
            'storage_disk' => 'r2',
        ]);

        if (! $recording->exists) {
            $recording->title = 'تسجيل — '.$liveSession->title;
            $recording->status = 'ready';
            $recording->is_published = false;
        }

        $recording->file_size = $size;
        $recording->duration_seconds = (int) ($validated['duration_seconds'] ?? 0);
        $recording->save();

        \App\Services\LiveRecordingAutoPublishService::publishForSession($recording->fresh(), $liveSession);

        return response()->json([
            'success' => true,
            'recording_id' => $recording->id,
        ]);
    }

    private function normalizeAudioMime(string $mime): string
    {
        $mime = strtolower(trim($mime));
        $allowed = [
            'audio/webm',
            'audio/ogg',
            'audio/mp4',
            'audio/mpeg',
            'application/octet-stream',
            'binary/octet-stream',
        ];

        if ($mime !== '' && in_array($mime, $allowed, true)) {
            return $mime;
        }

        return 'audio/webm';
    }

    private function mimeToAudioExt(string $mime): string
    {
        return match ($mime) {
            'audio/ogg' => 'ogg',
            'audio/mp4' => 'm4a',
            'audio/mpeg' => 'mp3',
            default => 'webm',
        };
    }
}
