<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveRecording;
use App\Models\LiveSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LiveRecordingController extends Controller
{
    public function index(Request $request)
    {
        $this->syncCloudflareR2Recordings();

        $query = LiveRecording::with(['session.course', 'session.instructor']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhereHas('session', function ($sq) use ($request) {
                      $sq->where('title', 'like', "%{$request->search}%");
                  });
            });
        }

        $recordings = $query->latest()->paginate(20)->withQueryString();
        $sessions = LiveSession::select('id', 'title')->orderByDesc('scheduled_at')->limit(200)->get();

        return view('admin.live-recordings.index', compact('recordings', 'sessions'));
    }

    /**
     * مزامنة التسجيلات المرفوعة مباشرة إلى Cloudflare R2
     * وإدراجها في جدول live_recordings إن لم تكن مسجلة بعد.
     */
    private function syncCloudflareR2Recordings(): void
    {
        try {
            $disk = Storage::disk('live_recordings_r2');
            $paths = $disk->allFiles('live-session-audio');
        } catch (\Throwable $e) {
            return;
        }

        if (empty($paths)) {
            return;
        }

        $allowedExtensions = ['webm', 'ogg', 'm4a', 'mp3', 'mp4'];

        foreach ($paths as $path) {
            if (!is_string($path) || $path === '' || str_contains($path, '..')) {
                continue;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            if (!preg_match('/session-(\d+)-audio-/i', basename($path), $matches)) {
                continue;
            }

            $sessionId = (int) ($matches[1] ?? 0);
            if ($sessionId <= 0) {
                continue;
            }

            $session = LiveSession::find($sessionId);
            if (!$session) {
                continue;
            }

            $recording = LiveRecording::firstOrNew([
                'session_id' => $sessionId,
                'file_path' => $path,
                'storage_disk' => 'r2',
            ]);

            if (!$recording->exists) {
                $recording->title = 'تسجيل — ' . $session->title;
                $recording->status = 'ready';
                $recording->is_published = false;
            }

            if ((int) $recording->file_size <= 0) {
                try {
                    $recording->file_size = (int) $disk->size($path);
                } catch (\Throwable $e) {
                    $recording->file_size = 0;
                }
            }

            if ($recording->duration_seconds === null) {
                $recording->duration_seconds = 0;
            }

            $recording->save();

            if (! $recording->is_published && $session) {
                \App\Services\LiveRecordingAutoPublishService::publishForSession($recording->fresh(), $session);
            }
        }
    }

    public function create()
    {
        $sessions = LiveSession::select('id', 'title', 'scheduled_at')
            ->orderByDesc('scheduled_at')
            ->limit(200)
            ->get();

        return view('admin.live-recordings.create', compact('sessions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'session_id'        => 'required|exists:live_sessions,id',
            'title'             => 'nullable|string|max:255',
            'storage_disk'      => 'nullable|in:local,r2',
            'file_path'         => 'nullable|string|max:500',
            'external_url'      => 'nullable|url|max:500',
            'video'             => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo|max:'.config('upload_limits.max_upload_kb', 512000),
            'duration_seconds'  => 'nullable|integer|min:0',
            'file_size'         => 'nullable|integer|min:0',
            'is_published'      => 'boolean',
        ]);

        $hasVideo = $request->hasFile('video');
        $hasPath = filled($validated['file_path'] ?? null);
        $hasExternal = filled($validated['external_url'] ?? null);
        if (! $hasVideo && ! $hasPath && ! $hasExternal) {
            return back()->withInput()->withErrors([
                'file_path' => 'أرفق ملف فيديو أو أدخل مسار الملف أو رابطاً خارجياً.',
            ]);
        }

        $session = LiveSession::find($validated['session_id']);
        $validated['title'] = $validated['title'] ?: ('تسجيل ' . $session->title);
        $validated['status'] = 'ready';
        $validated['is_published'] = $request->boolean('is_published');
        $validated['duration_seconds'] = $validated['duration_seconds'] ?? 0;
        $validated['file_size'] = $validated['file_size'] ?? 0;

        if ($hasVideo) {
            $uploadDisk = $this->resolveLiveRecordingUploadDisk();
            $storageDisk = $uploadDisk === 'live_recordings_r2' ? 'r2' : 'local';
            $file = $request->file('video');
            $path = $file->store(
                'live-recordings/session-'.$validated['session_id'],
                $uploadDisk
            );
            $validated['file_path'] = $path;
            $validated['file_size'] = (int) $file->getSize();
            $validated['storage_disk'] = $storageDisk;
        } else {
            $validated['storage_disk'] = $validated['storage_disk'] ?? 'r2';
        }

        unset($validated['video']);

        LiveRecording::create($validated);

        return redirect()->route('admin.live-recordings.index')->with('success', 'تم إضافة التسجيل بنجاح.');
    }

    private function resolveLiveRecordingUploadDisk(): string
    {
        if (filled(env('R2_LIVE_RECORDINGS_BUCKET')) || filled(env('AWS_BUCKET'))) {
            try {
                Storage::disk('live_recordings_r2');

                return 'live_recordings_r2';
            } catch (\Throwable $e) {
                // fallback to local
            }
        }

        return 'local';
    }

    public function show(LiveRecording $liveRecording)
    {
        $liveRecording->load(['session.course', 'session.instructor']);
        return view('admin.live-recordings.show', compact('liveRecording'));
    }

    public function update(Request $request, LiveRecording $liveRecording)
    {
        $validated = $request->validate([
            'title'        => 'nullable|string|max:255',
            'file_path'    => 'nullable|string|max:500',
            'external_url' => 'nullable|url|max:500',
            'storage_disk' => 'nullable|in:local,r2',
            'is_published' => 'boolean',
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        $liveRecording->update(array_filter($validated, fn ($v) => $v !== null));

        return back()->with('success', 'تم تحديث التسجيل بنجاح');
    }

    public function togglePublish(LiveRecording $liveRecording)
    {
        $liveRecording->update(['is_published' => !$liveRecording->is_published]);
        $msg = $liveRecording->is_published ? 'تم نشر التسجيل' : 'تم إلغاء نشر التسجيل';
        return back()->with('success', $msg);
    }

    public function destroy(LiveRecording $liveRecording)
    {
        $liveRecording->update(['status' => 'deleted']);
        return back()->with('success', 'تم حذف التسجيل');
    }
}
